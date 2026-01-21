<?php
// ===============================
// UNIFIED DATA LINK PAGE
// Live Feed (default) + History Mode
// ===============================

// Enable error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();

// Include configuration first (needed for constants)
require_once 'data-link-config.php';

// Determine mode: 'live' (default) or 'history'
$isHistoryMode = isset($_GET['history']);

// Initialize variables
$totalMessages = 0;
$hasMore = false;
$messages = [];
$dbError = false;
$dbErrorMessage = '';
$historyConn = null;
$oldestMessageDate = null;

// History mode specific parameters
if ($isHistoryMode) {
    $startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
    $startTime = $_GET['startTime'] ?? '00:00';
    $endDate = $_GET['endDate'] ?? date('Y-m-d');
    $endTime = $_GET['endTime'] ?? '23:59';
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = HISTORY_QUERY_LIMIT;
    
    // Get filter parameters
    $filterReceiverIds = isset($_GET['filterReceiverId']) ? (is_array($_GET['filterReceiverId']) ? $_GET['filterReceiverId'] : [$_GET['filterReceiverId']]) : [];
    $filterFrequencies = isset($_GET['filterFrequency']) ? (is_array($_GET['filterFrequency']) ? $_GET['filterFrequency'] : [$_GET['filterFrequency']]) : [];
    $filterNetworkTypes = isset($_GET['filterNetworkType']) ? (is_array($_GET['filterNetworkType']) ? $_GET['filterNetworkType'] : [$_GET['filterNetworkType']]) : [];
    $filterRegistration = $_GET['filterRegistration'] ?? '';
    $filterFlightNumber = $_GET['filterFlightNumber'] ?? '';
    $filterAckValues = isset($_GET['filterAck']) ? (is_array($_GET['filterAck']) ? $_GET['filterAck'] : [$_GET['filterAck']]) : [];
    
    // Default filter: Respect DEFAULT_SELECT_ALL_LABELS_HISTORY setting
    // If false, exclude _d and SQ from initial selection
    $defaultDataLinkSystem = array_keys($FRONTEND_GROUP_MAP);
    if (!DEFAULT_SELECT_ALL_LABELS_HISTORY) {
        // Exclude general_response (_d) and sq (SQ) from default selection
        $defaultDataLinkSystem = array_diff($defaultDataLinkSystem, ['general_response', 'sq']);
    }
    $filterDataLinkSystem = isset($_GET['filterDataLinkSystem']) ? (is_array($_GET['filterDataLinkSystem']) ? $_GET['filterDataLinkSystem'] : [$_GET['filterDataLinkSystem']]) : $defaultDataLinkSystem;
    
    $filterMessageContent = $_GET['messageContent'] ?? '';
    $hideIncompleteMessages = isset($_GET['hideIncomplete']) ? $_GET['hideIncomplete'] === '1' : true;
    
    // Build timestamps
    $startTimestamp = strtotime("$startDate $startTime:00");
    $endTimestamp = strtotime("$endDate $endTime:59");
    
    // Include history database helper
    try {
        require_once __DIR__ . '/../db-config-history.php';
        
        if (!isset($historyConn) || !$historyConn) {
            $dbError = true;
            $dbErrorMessage = 'Database connection error.';
        } else {
            // Get oldest message date
            $oldestStmt = $historyConn->prepare("SELECT MIN(timestamp_msg) as oldest FROM messages_json_raw");
            if ($oldestStmt) {
                $oldestStmt->execute();
                $oldestResult = $oldestStmt->get_result();
                if ($oldestResult) {
                    $oldestRow = $oldestResult->fetch_assoc();
                    if ($oldestRow && $oldestRow['oldest']) {
                        $oldestMessageDate = date('Y-m-d', (int)$oldestRow['oldest']);
                    }
                }
                $oldestStmt->close();
            }
        }
    } catch (Exception $e) {
        $dbError = true;
        $dbErrorMessage = 'Database connection error.';
        error_log("History database connection failed: " . $e->getMessage());
    } catch (Error $e) {
        $dbError = true;
        $dbErrorMessage = 'Database connection error.';
        error_log("History database fatal error: " . $e->getMessage());
    }
    
    // Fetch messages from database
    if (!$dbError && $historyConn) {
        try {
            // Build WHERE clause with filters
            $whereConditions = ["timestamp_msg >= ?", "timestamp_msg <= ?"];
            $bindTypes = "dd";
            $bindParams = [$startTimestamp, $endTimestamp];
            
            if (!empty($filterReceiverIds)) {
                $receiverConditions = [];
                foreach ($filterReceiverIds as $receiverId) {
                    $receiverConditions[] = "station_id LIKE ?";
                    $bindTypes .= "s";
                    $bindParams[] = "%{$receiverId}%";
                }
                $whereConditions[] = "(" . implode(" OR ", $receiverConditions) . ")";
            }
            
            if (!empty($filterFrequencies)) {
                $freqConditions = [];
                foreach ($filterFrequencies as $freq) {
                    $freqConditions[] = "freq = ?";
                    $bindTypes .= "s";
                    $bindParams[] = $freq;
                }
                $whereConditions[] = "(" . implode(" OR ", $freqConditions) . ")";
            }
            
            if (!empty($filterNetworkTypes)) {
                $appConditions = [];
                foreach ($filterNetworkTypes as $netType) {
                    if ($netType === 'ACARS') {
                        $appConditions[] = "(app_name = 'acarsdec' OR app_name = 'vdlm2dec')";
                    }
                    // ATN not implemented yet - no filter for it
                }
                if (!empty($appConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $appConditions) . ")";
                }
            }
            
            if (!empty($filterRegistration)) {
                $regWithoutDash = str_replace('-', '', $filterRegistration);
                $whereConditions[] = "(REPLACE(tail, '-', '') LIKE ?)";
                $bindTypes .= "s";
                $bindParams[] = "%{$regWithoutDash}%";
            }
            
            if (!empty($filterFlightNumber)) {
                $whereConditions[] = "flight LIKE ?";
                $bindTypes .= "s";
                $bindParams[] = "%{$filterFlightNumber}%";
            }
            
            if (!empty($filterAckValues)) {
                $ackConditions = [];
                foreach ($filterAckValues as $ackVal) {
                    if ($ackVal === 'ACK') {
                        $ackConditions[] = "ack = 1";
                    } elseif ($ackVal === 'NO ACK') {
                        $ackConditions[] = "(ack = 0 OR ack IS NULL)";
                    }
                }
                if (!empty($ackConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $ackConditions) . ")";
                }
            }
            
            // Get ALL labels from config FIRST (needed for other_labeled filter)
            $allFilterDefinedLabels = getAllDefinedLabels();
            
            // Note: filterDataLinkSystem (label filtering) is now in SQL
            if (!empty($filterDataLinkSystem)) {
                $labelConditions = [];
                $hasOtherLabeled = false;
                
                // Map group names to actual label codes (dynamically from config)
                foreach ($filterDataLinkSystem as $groupName) {
                    // Special case: other_labeled
                    if ($groupName === 'other_labeled') {
                        $hasOtherLabeled = true;
                        continue;
                    }
                    
                    // Try frontend group mapping first
                    if (isset($FRONTEND_GROUP_MAP[$groupName])) {
                        $labels = getLabelsForGroup($FRONTEND_GROUP_MAP[$groupName]);
                        foreach ($labels as $labelCode) {
                            $labelConditions[] = "label = ?";
                            $bindTypes .= "s";
                            $bindParams[] = $labelCode;
                        }
                        continue;
                    }
                    
                    // Not found in config mappings, treat as direct label code
                    $labelConditions[] = "label = ?";
                    $bindTypes .= "s";
                    $bindParams[] = $groupName;
                }
                
                // If "other_labeled" is selected, add condition for labels NOT IN filter-defined list
                if ($hasOtherLabeled && !empty($allFilterDefinedLabels)) {
                    $placeholders = implode(',', array_fill(0, count($allFilterDefinedLabels), '?'));
                    $labelConditions[] = "(label IS NOT NULL AND label NOT IN ($placeholders))";
                    foreach ($allFilterDefinedLabels as $knownLabel) {
                        $bindTypes .= "s";
                        $bindParams[] = $knownLabel;
                    }
                }
                
                if (!empty($labelConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $labelConditions) . ")";
                }
            }
            
            if (!empty($filterMessageContent)) {
                $whereConditions[] = "text LIKE ?";
                $bindTypes .= "s";
                $bindParams[] = "%{$filterMessageContent}%";
            }
            
            if ($hideIncompleteMessages) {
                $whereConditions[] = "(assstat IS NULL OR (assstat != 'in progress' AND assstat != 'out of sequence'))";
            }
            
            $whereClause = implode(" AND ", $whereConditions);
            
            // Count total messages
            $countSql = "SELECT COUNT(*) as total FROM messages_json_raw WHERE {$whereClause}";
            $countStmt = $historyConn->prepare($countSql);
            
            if ($countStmt === false) {
                $dbError = true;
                $dbErrorMessage = 'SQL prepare failed: ' . $historyConn->error;
            } else {
                $countStmt->bind_param($bindTypes, ...$bindParams);
                $countStmt->execute();
                $result = $countStmt->get_result();
                if ($result) {
                    $totalMessages = $result->fetch_assoc()['total'];
                }
                $countStmt->close();

                // Fetch messages with LIMIT+1 to check if there are more
                $fetchLimit = $limit + 1;
                $sql = "SELECT id, received_at, timestamp_msg, station_id, freq, level, mode, label, 
                               ack, tail, text, flight, app_name
                        FROM messages_json_raw 
                        WHERE {$whereClause}
                        ORDER BY timestamp_msg DESC 
                        LIMIT ? OFFSET ?";
                $stmt = $historyConn->prepare($sql);
                
                if ($stmt === false) {
                    $dbError = true;
                    $dbErrorMessage = 'SQL query prepare failed: ' . $historyConn->error;
                } else {
                    $bindTypes .= "ii";
                    $bindParams[] = $fetchLimit;
                    $bindParams[] = $offset;
                    $stmt->bind_param($bindTypes, ...$bindParams);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $count = 0;
                    while ($row = $result->fetch_assoc()) {
                        $count++;
                        if ($count <= $limit) {
                            $msgData = [
                                'timestamp' => $row['timestamp_msg'],
                                'station_id' => $row['station_id'],
                                'freq' => $row['freq'],
                                'level' => $row['level'],
                                'mode' => $row['mode'],
                                'label' => $row['label'],
                                'ack' => $row['ack'],
                                'tail' => $row['tail'],
                                'text' => $row['text'],
                                'flight' => $row['flight'],
                                'app' => ['name' => $row['app_name']]
                            ];
                            $messages[] = $msgData;
                        }
                    }
                    // If we got more than limit, there are more messages
                    $hasMore = ($count > $limit);
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $dbError = true;
            $dbErrorMessage = 'Database query error: ' . $e->getMessage();
        }
        
        if (!$dbError && $historyConn) {
            $historyConn->close();
        }
    }
} else {
    // Live mode - hide incomplete messages default
    $hideIncompleteMessages = true;
}

// Include common resources
include 'page-data-link-style.php';
include 'data/receiver-info-config.php';
include 'data/receiver-list.php';
include 'data/channel-list.php';
include 'load-message-labels.php';

// Get label arrays from config
$datisLabels = getLabelsForGroup('DATIS');
$dclPdcLabels = getLabelsForGroup('DCL_PDC');
$oclLabels = getLabelsForGroup('OCL');
$fsmLabels = getLabelsForGroup('FSM');
$adscLabels = getLabelsForGroup('ADSC');
$cpdlcLabels = getLabelsForGroup('CPDLC');
$printerLabels = getLabelsForGroup('PRINTER');
$freeTextLabels = getLabelsForGroup('FREE_TEXT');
$airlineDefinedLabels = getLabelsForGroup('AIRLINE_DEFINED');

// PHP function to get Data Link System Type and Operation Type
function getDataLinkTypesPHP($labelCode, $MESSAGE_LABEL_DESCRIPTIONS) {
    // Use pre-built mapping from config
    $mapping = buildLabelMapping();
    
    // Check if label exists in mapping
    if (isset($mapping[$labelCode])) {
        return $mapping[$labelCode];
    }
    
    // Default for unknown labels
    return ['system' => 'Other', 'operation' => 'Other'];
}
?>

<main class="main px-4">
    <div class="datalink-container">
        <div class="datalink-header">
            <h2>Data Link - <?php echo $isHistoryMode ? 'Message History' : 'Live Feed'; ?></h2>
            <div class="connection-status">
                <?php if ($isHistoryMode): ?>
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <span id="messageInfo">Showing <?php echo count($messages); ?> of <?php echo $totalMessages; ?> messages</span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button onclick="changePage('first')" class="pagination-btn-header" title="First Page">⏮</button>
                            <button onclick="changePage('prev')" class="pagination-btn-header" title="Previous Page">◀</button>
                            <span id="pageIndicator" style="padding: 0 8px; font-weight: 600; color: #fff; min-width: 50px; text-align: center;">1 / 1</span>
                            <button onclick="changePage('next')" class="pagination-btn-header" title="Next Page">▶</button>
                            <button onclick="changePage('last')" class="pagination-btn-header" title="Last Page">⏭</button>
                        </div>
                        <select id="perPageSelect" onchange="changePerPage()" style="padding: 5px 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; font-size: 13px; background: rgba(255,255,255,0.9); color: #2c3e50; font-weight: 600;">
                            <option value="100">100 per page</option>
                            <option value="500" selected>500 per page</option>
                            <option value="1000">1000 per page</option>
                        </select>
                    </div>
                <?php else: ?>
                    <span class="status-indicator" id="statusIndicator"></span>
                    <span id="statusText">Connecting...</span>
                    <span class="message-count" id="messageCount">Messages: 0</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Message Filters Bar (Common for both modes) -->
        <div class="filter-bar">
            <div class="filter-bar-header" onclick="toggleFilters()">
                <span class="filter-bar-title">🔍 Message Filters</span>
                <button type="button" class="other-settings-btn" onclick="event.stopPropagation(); showOtherSettings()" title="Other Settings">⚙️ Other Settings</button>
                <button type="button" class="filter-toggle-btn" id="filterToggleBtn">▼</button>
            </div>
            <div class="filter-bar-content" id="filterBarContent">
                <?php if ($isHistoryMode): ?>
                <!-- History Mode: Form-based filters -->
                <form method="GET" action="/data-link" id="additionalFiltersForm">
                    <input type="hidden" name="history" value="1" />
                    <input type="hidden" name="hideIncomplete" id="hideIncompleteInput" value="<?php echo $hideIncompleteMessages ? '1' : '0'; ?>" />
                    <div id="hiddenInputsContainer"></div>
                    <?php endif; ?>
                    
                    <!-- Wrapper for 3 filter rows with absolute positioned buttons -->
                    <div style="position: relative;">
                        <!-- Apply and Reset buttons positioned absolutely -->
                        <div style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); display: flex; gap: 5px; z-index: 10;">
                            <?php if ($isHistoryMode): ?>
                            <button type="button" class="filter-btn" style="height: 32px; padding: 6px 16px; font-size: 12px; font-weight: 600;" onclick="populateHiddenInputs(); document.getElementById('additionalFiltersForm').submit();">✔ Apply</button>
                            <button type="button" class="filter-reset-btn-compact" onclick="resetFilters()" title="Reset filters" style="font-weight: 600;">🔄 Reset</button>
                            <?php else: ?>
                            <button type="button" class="filter-btn" style="height: 32px; padding: 6px 16px; font-size: 12px; font-weight: 600;" onclick="applyFilters()">✔ Apply</button>
                            <button type="button" class="filter-reset-btn-compact" onclick="resetFilters()" title="Reset filters" style="font-weight: 600;">🔄 Reset</button>
                            <?php endif; ?>
                        </div>
                    
                    <?php if ($isHistoryMode): ?>
                    <!-- Date/Time Range in Filters -->
                    <div class="filter-row-compact" style="margin-bottom: 10px; position: relative;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 400px; height: 1px; background: #e9ecef;"></div>
                        <div style="display: flex; gap: 10px; background-color: #f0f8ff; padding: 10px; border-radius: 4px; width: fit-content;">
                            <div class="filter-item-compact" style="flex: 0 0 auto;">
                                <label>Start Date (UTC):</label>
                                <input type="date" 
                                       name="startDate" 
                                       id="startDate" 
                                       class="filter-input-compact" 
                                       value="<?php echo htmlspecialchars($startDate); ?>" 
                                       <?php if ($oldestMessageDate): ?>min="<?php echo htmlspecialchars($oldestMessageDate); ?>"<?php endif; ?> 
                                       max="<?php echo date('Y-m-d'); ?>" 
                                       required 
                                       style="width: 140px;" />
                            </div>
                            <div class="filter-item-compact" style="flex: 0 0 auto;">
                                <label>Start Time:</label>
                                <input type="time" 
                                       name="startTime" 
                                       id="startTime" 
                                       class="filter-input-compact" 
                                       value="<?php echo htmlspecialchars($startTime); ?>" 
                                       style="width: 100px;" />
                            </div>
                            <div class="filter-item-compact" style="flex: 0 0 auto;">
                                <label>End Date (UTC):</label>
                                <input type="date" 
                                       name="endDate" 
                                       id="endDate" 
                                       class="filter-input-compact" 
                                       value="<?php echo htmlspecialchars($endDate); ?>" 
                                       <?php if ($oldestMessageDate): ?>min="<?php echo htmlspecialchars($oldestMessageDate); ?>"<?php endif; ?> 
                                       max="<?php echo date('Y-m-d'); ?>" 
                                       required 
                                       style="width: 140px;" />
                            </div>
                            <div class="filter-item-compact" style="flex: 0 0 auto;">
                                <label>End Time:</label>
                                <input type="time" 
                                       name="endTime" 
                                       id="endTime" 
                                       class="filter-input-compact" 
                                       value="<?php echo htmlspecialchars($endTime); ?>" 
                                       style="width: 100px;" />
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="filter-row-compact" style="padding-bottom: 10px; margin-bottom: 5px; position: relative;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 400px; height: 1px; background: #e9ecef;"></div>
                    <div class="filter-item-compact">
                        <label>Receiver ID:</label>
                        <div class="multiselect-wrapper">
                            <button type="button" class="multiselect-btn" onclick="toggleMultiselect(event, 'filterReceiverId')">
                                <span id="filterReceiverId-display">All Selected</span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div class="multiselect-dropdown" id="filterReceiverId-dropdown">
                                <div class="multiselect-options" id="filterReceiverId-options">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-item-compact">
                        <label>Frequency - Subnetwork:</label>
                        <div class="multiselect-wrapper">
                            <button type="button" class="multiselect-btn" onclick="toggleMultiselect(event, 'filterFrequency')">
                                <span id="filterFrequency-display">All Selected</span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div class="multiselect-dropdown" id="filterFrequency-dropdown">
                                <div class="multiselect-options" id="filterFrequency-options">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-item-compact">
                        <label>Network Type:</label>
                        <div class="multiselect-wrapper">
                            <button type="button" class="multiselect-btn" onclick="toggleMultiselect(event, 'filterNetworkType')">
                                <span id="filterNetworkType-display">All Selected</span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div class="multiselect-dropdown" id="filterNetworkType-dropdown">
                                <div class="multiselect-options" id="filterNetworkType-options">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ACARS Network Only Filters -->
                <div class="filter-row-compact" style="padding-top: 5px; margin-top: 5px; align-items: center; padding-bottom: 10px; margin-bottom: 5px; position: relative;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: #e9ecef;"></div>
                    <div class="filter-item-compact" style="flex: 0 0 auto; font-weight: bold; color: #2c5aa0; font-size: 11px; padding-right: 15px; display: flex; align-items: center;">
                        <span>ACARS Network Only:</span>
                    </div>
                    
                    <div class="filter-item-compact filter-registration-compact" id="filterRegistrationContainer">
                        <label>Registration:</label>
                        <input type="text" name="filterRegistration" id="filterRegistration" class="filter-input-compact" placeholder="e.g. TC-IBO" value="<?php echo $isHistoryMode ? htmlspecialchars($filterRegistration) : ''; ?>" />
                    </div>
                    
                    <div class="filter-item-compact filter-flight-compact" id="filterFlightContainer">
                        <label>Flight:</label>
                        <input type="text" name="filterFlightNumber" id="filterFlightNumber" class="filter-input-compact" placeholder="e.g. IBO123" value="<?php echo $isHistoryMode ? htmlspecialchars($filterFlightNumber) : ''; ?>" />
                    </div>
                    
                    <div class="filter-item-compact filter-ack-compact" id="filterAckContainer">
                        <label>Acknowledgement:</label>
                        <div class="multiselect-wrapper">
                            <button type="button" class="multiselect-btn" onclick="toggleMultiselect(event, 'filterAck')">
                                <span id="filterAck-display">All Selected</span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div class="multiselect-dropdown" id="filterAck-dropdown">
                                <div class="multiselect-options" id="filterAck-options">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-item-compact filter-label-wide" id="filterDataLinkSystemContainer">
                        <label>Data Link System and Application Type:</label>
                        <div class="multiselect-wrapper">
                            <button type="button" class="multiselect-btn" onclick="toggleMultiselect(event, 'filterDataLinkSystem')">
                                <span id="filterDataLinkSystem-display">All Selected</span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div class="multiselect-dropdown" id="filterDataLinkSystem-dropdown">
                                <div class="quick-filters">
                                    <span class="quick-filters-label">Quick Filters:</span>
                                    <div class="quick-filters-buttons">
                                        <button type="button" class="qf-btn qf-select-all" onclick="selectAllDLS('filterDataLinkSystem')" title="Select all">Select All</button>
                                        <button type="button" class="qf-btn qf-deselect-all" onclick="deselectAllDLS('filterDataLinkSystem')" title="Deselect all">Deselect All</button>
                                        <button type="button" class="qf-btn qf-deselect-delsq" onclick="deselectDelSQ('filterDataLinkSystem')" title="Deselect _d and SQ">Deselect _DEL/SQ</button>
                                    </div>
                                </div>
                                <div class="multiselect-options" id="filterDataLinkSystem-options">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div><!-- End of 3-row wrapper -->
                
                <!-- Message Content Search Row -->
                <div class="filter-row-compact search-row" style="border-top: none !important; padding-top: 0;">
                    <div class="filter-item-compact filter-search-wide">
                        <label>🔎 Message Content Search:</label>
                        <div class="search-input-wrapper">
                            <?php if ($isHistoryMode): ?>
                                <input type="text" name="messageContent" id="filterMessageContent" class="filter-input-search" placeholder="Search in message content..." value="<?php echo htmlspecialchars($_GET['messageContent'] ?? ''); ?>" />
                                <button type="button" class="search-btn" title="Search" onclick="populateHiddenInputs(); document.getElementById('additionalFiltersForm').submit();">🔍 Search</button>
                            <?php else: ?>
                                <input type="text" id="filterMessageContent" class="filter-input-search" placeholder="Search in message content..." />
                                <button type="button" class="search-btn" onclick="applyFilters()" title="Search">🔍 Search</button>
                            <?php endif; ?>
                            <button type="button" class="search-reset-btn" onclick="clearMessageSearch()" title="Clear Search">❌ Clear</button>
                        </div>
                    </div>
                </div>
                
                <?php if ($isHistoryMode): ?>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="legend-container" style="position: relative;">
            <button class="receiver-info-btn" onclick="showReceiverInfo()" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%);">Available Receivers and Frequencies</button>
            <div class="legend-label">Legend:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 0px; padding-right: 280px;">
                <span class="tag tag-timestamp">Date - Time (UTC)</span>
                <span class="tag tag-station">Receiver ID</span>
                <span class="tag tag-level">Signal Level</span>
                <span class="tag tag-freq">Frequency - Subnetwork - Region - CSP Name</span>
                <span class="tag tag-app">Network Type</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 10px; padding-right: 280px;">
                <div style="font-weight: bold; color: #2c5aa0; font-size: 13px; padding: 4px 8px; background: #f0f4f8; border-radius: 4px; white-space: nowrap;">ACARS Network Only:</div>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    <span class="tag tag-tail">Registration</span>
                    <span class="tag tag-flight">Flight Number</span>
                    <span class="tag tag-ack">Acknowledgement</span>
                    <span class="tag tag-label">Message Label (Format)</span>
                    <span class="tag tag-dl-system">Data Link System Type</span>
                    <span class="tag tag-dl-operation">Data Link Application Type</span>
                </div>
            </div>
        </div>
        
        <!-- Messages Container -->
        <div class="messages-container" id="messagesContainer">
            <?php if ($isHistoryMode): ?>
                <?php if ($dbError): ?>
                    <div class="no-messages" style="padding: 40px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin: 20px 0;">
                        <div style="color: #856404; font-size: 18px; font-weight: bold; margin-bottom: 10px;">
                            ⚠️ Database connection error.
                        </div>
                    </div>
                <?php elseif (empty($messages)): ?>
                    <div class="no-messages">No messages found for the selected time period.</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-item">
                            <!-- Show On Map Button -->
                            <?php if (isset($msg['tail']) && !empty(trim($msg['tail']))): ?>
                                <button class="show-on-map-btn" onclick="openMapHistory('<?php echo htmlspecialchars(str_replace('-', '', $msg['tail'])); ?>', '<?php echo isset($msg['timestamp']) ? date('Y-m-d', (int)$msg['timestamp']) : ''; ?>', '<?php echo isset($msg['timestamp']) ? (int)$msg['timestamp'] : ''; ?>')">Show On Map ↗️</button>
                            <?php else: ?>
                                <button class="show-on-map-btn" disabled>Show On Map ↗️</button>
                            <?php endif; ?>
                            
                            <div class="message-tags">
                                <?php if (isset($msg['timestamp'])): ?>
                                    <span class="tag tag-timestamp"><?php echo strtoupper(gmdate('d M Y H:i:s', (int)$msg['timestamp'])); ?></span>
                                <?php endif; ?>
                                <?php if (isset($msg['station_id'])): ?>
                                    <span class="tag tag-station"><?php echo htmlspecialchars($msg['station_id']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($msg['level'])): ?>
                                    <span class="tag tag-level"><?php echo htmlspecialchars($msg['level']); ?> dBFS</span>
                                <?php endif; ?>
                                <?php if (isset($msg['freq'])): ?>
                                    <span class="tag tag-freq">
                                        <?php 
                                        $freqStr = (string)$msg['freq'];
                                        echo htmlspecialchars($msg['freq']) . ' MHz';
                                        if (isset($CHANNELS[$freqStr])) {
                                            echo ' - ' . htmlspecialchars($CHANNELS[$freqStr]);
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (isset($msg['app']['name'])): ?>
                                    <span class="tag tag-app">
                                        <?php 
                                        if ($msg['app']['name'] === 'acarsdec') echo 'ACARS';
                                        elseif ($msg['app']['name'] === 'vdlm2dec') echo 'CPDLC';
                                        else echo 'Other';
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (isset($msg['tail'])): ?>
                                    <span class="tag tag-tail"><?php echo htmlspecialchars($msg['tail']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($msg['flight'])): ?>
                                    <span class="tag tag-flight"><?php echo htmlspecialchars($msg['flight']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($msg['ack'])): ?>
                                    <span class="tag tag-ack"><?php echo $msg['ack'] ? 'ACK' : 'NO ACK'; ?></span>
                                <?php endif; ?>
                                <?php if (isset($msg['label'])): ?>
                                    <span class="tag tag-label">
                                        <?php 
                                        $labelCode = $msg['label'];
                                        echo htmlspecialchars($labelCode);
                                        if (isset($MESSAGE_LABEL_DESCRIPTIONS[$labelCode])) {
                                            $info = $MESSAGE_LABEL_DESCRIPTIONS[$labelCode];
                                            $display = htmlspecialchars($info['explanation']);
                                            if (!empty($info['direction'])) {
                                                $display .= ' — ' . htmlspecialchars($info['direction']);
                                            }
                                            echo ' (' . $display . ')';
                                        }
                                        
                                        // Get Data Link System and Operation Types
                                        $dlTypes = getDataLinkTypesPHP($labelCode, $MESSAGE_LABEL_DESCRIPTIONS);
                                        ?>
                                    </span>
                                    <span class="tag tag-dl-system"><?php echo htmlspecialchars($dlTypes['system']); ?></span>
                                    <span class="tag tag-dl-operation"><?php echo htmlspecialchars($dlTypes['operation']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-content"><?php 
                                $messageText = isset($msg['text']) ? trim($msg['text']) : '';
                                echo $messageText !== '' ? htmlspecialchars($messageText) : '[No message content]'; 
                            ?></div>
                            <?php if (isset($msg['decoded']) && !empty($msg['decoded'])): ?>
                            <div class="message-decoded"><?php echo htmlspecialchars($msg['decoded']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- Live mode: Messages will be inserted here dynamically -->
            <?php endif; ?>
        </div>
        
        <?php if ($isHistoryMode && $hasMore): ?>
        <!-- Load More Button -->
        <div class="load-more-container" style="text-align: center; padding: 20px;">
            <button id="loadMoreBtn" class="filter-btn" style="padding: 12px 32px; font-size: 14px;" onclick="loadMoreMessages()">
                📥 Load More Messages
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Receiver Info Popup -->
    <div id="receiverInfoPopup" class="popup-overlay" onclick="closeReceiverInfo(event)">
        <div class="popup-content" onclick="event.stopPropagation()">
            <span class="popup-close" onclick="closeReceiverInfo()">&times;</span>
            <div id="receiverInfoContent"></div>
        </div>
    </div>
    
    <!-- Other Settings Popup -->
    <div id="otherSettingsPopup" class="popup-overlay" onclick="closeOtherSettings(event)">
        <div class="popup-content popup-settings" onclick="event.stopPropagation()">
            <span class="popup-close" onclick="closeOtherSettings()">&times;</span>
            <h3>⚙️ Other Settings</h3>
            <div class="settings-option">
                <label class="checkbox-container">
                    <input type="checkbox" id="hideIncompleteMessages" <?php echo $hideIncompleteMessages ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    <span class="setting-text">Display only the completed version of multi-block messages.</span>
                </label>
                <p class="setting-description">When enabled, unprocessed fragments of multi-block messages are not displayed.</p>
            </div>
            <div class="settings-buttons">
                <button type="button" class="settings-apply-btn" onclick="applyOtherSettings()">✔ Apply</button>
            </div>
        </div>
    </div>
</main>

<script>
// Pre-load ACARS library data files to avoid routing issues
window.ACARS_SPEC = <?php echo file_get_contents(__DIR__ . '/acars-decoding-library/ibosoft-acars-spec.json'); ?>;
window.ACARS_LABELS_CSV = <?php echo json_encode(file_get_contents(__DIR__ . '/acars-decoding-library/label-list.csv')); ?>;
window.ACARS_CONFIG = <?php 
    $configPath = __DIR__ . '/acars-decoding-library/config.json';
    echo file_exists($configPath) ? file_get_contents($configPath) : '{}'; 
?>;
</script>
<script src="data-link-files/acars-decoding-library/ibosoft-acars-library.js"></script>
<script>
// Wrap fetch to return pre-loaded data instead of fetching
(function() {
    const originalFetch = window.fetch;
    
    window.fetch = function(url, options) {
        // Intercept library file requests and return pre-loaded data
        if (url === 'ibosoft-acars-spec.json') {
            return Promise.resolve(new Response(JSON.stringify(window.ACARS_SPEC), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            }));
        }
        if (url === 'label-list.csv') {
            return Promise.resolve(new Response(window.ACARS_LABELS_CSV, {
                status: 200,
                headers: { 'Content-Type': 'text/csv' }
            }));
        }
        if (url === 'config.json') {
            return Promise.resolve(new Response(JSON.stringify(window.ACARS_CONFIG), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            }));
        }
        
        return originalFetch.call(this, url, options);
    };
})();
</script>
<script>
// ===============================
// CONFIGURATION
// ===============================
const CONFIG = <?php echo json_encode(exportConfigToJS()); ?>;
const MESSAGE_LABEL_DESCRIPTIONS = <?php echo json_encode($MESSAGE_LABEL_DESCRIPTIONS); ?>;

// ===============================
// COMMON VARIABLES
// ===============================
const IS_HISTORY_MODE = <?php echo $isHistoryMode ? 'true' : 'false'; ?>;

// History mode: Selected filter values from URL
<?php if ($isHistoryMode): ?>
const SELECTED_RECEIVERS = <?php echo json_encode($filterReceiverIds); ?>;
const SELECTED_FREQUENCIES = <?php echo json_encode($filterFrequencies); ?>;
const SELECTED_NETWORK_TYPES = <?php echo json_encode($filterNetworkTypes ?? []); ?>;
const SELECTED_ACK_VALUES = <?php echo json_encode($filterAckValues ?? []); ?>;
const SELECTED_DLS = <?php echo json_encode($filterDataLinkSystem); ?>;
<?php else: ?>
const SELECTED_RECEIVERS = [];
const SELECTED_FREQUENCIES = [];
const SELECTED_NETWORK_TYPES = [];
const SELECTED_ACK_VALUES = [];
const SELECTED_DLS = [];
<?php endif; ?>

// No need for individual label arrays - we get them from CONFIG.LABEL_DEFINITIONS dynamically

// ===============================
// COMMON FUNCTIONS
// ===============================

// Helper function to remove dashes from string
function removeDashes(str) {
    return str.replace(/-/g, '');
}

// Get all selected values from a multiselect dropdown
function getMultiselectValues(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    return Array.from(dropdown.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
}

// Get selected labels from Data Link System filter
function getSelectedLabelsFromDLS() {
    const container = document.getElementById('filterDataLinkSystem-options');
    const advancedMode = container.querySelector('input[value="advanced_manual"]')?.checked;
    
    if (advancedMode) {
        // Advanced mode: return checked label codes
        return Array.from(container.querySelectorAll('.advanced-label-option input[type="checkbox"]:checked'))
            .map(cb => cb.value);
    } else {
        // Grouped mode: collect labels from checked groups
        const selectedLabels = [];
        const checkedOptions = container.querySelectorAll('.dls-option input[type="checkbox"]:checked');
        
        checkedOptions.forEach(checkbox => {
            const option = checkbox.closest('.dls-option');
            const labels = JSON.parse(option.getAttribute('data-labels') || '[]');
            selectedLabels.push(...labels);
        });
        
        // Handle "Other Labeled Messages" - this will be checked during filtering
        const otherLabeledChecked = Array.from(checkedOptions).some(cb => cb.value === 'other_labeled');
        if (otherLabeledChecked) {
            selectedLabels.push('__OTHER_LABELED__'); // Special marker
        }
        
        return selectedLabels;
    }
}

// Get Data Link System Type and Operation Type from label
function getDataLinkTypes(labelCode) {
    // Build mapping from config
    const mapping = {};
    
    // Add all grouped labels from config
    for (const [groupKey, groupData] of Object.entries(CONFIG.LABEL_DEFINITIONS)) {
        groupData.labels.forEach(label => {
            mapping[label] = { system: groupData.system, operation: groupData.operation };
        });
    }
    
    // Check if label exists in mapping
    if (mapping[labelCode]) {
        return mapping[labelCode];
    }
    
    // Default for unknown labels
    return { system: 'Other', operation: 'Other' };
}

// Populate all dropdowns (used by both modes)
function populateDropdowns() {
    // Receiver ID dropdown
    const receiverContainer = document.getElementById('filterReceiverId-options');
    if (receiverContainer && typeof RECEIVERS !== 'undefined') {
        receiverContainer.innerHTML = '';
        RECEIVERS.forEach(receiver => {
            const checked = SELECTED_RECEIVERS.length === 0 || SELECTED_RECEIVERS.includes(receiver) ? 'checked' : '';
            const label = document.createElement('label');
            label.className = 'multiselect-option';
            label.innerHTML = `<input type="checkbox" value="${receiver}" ${checked} onchange="updateMultiselect('filterReceiverId')" data-ui-only="true"><span>${receiver}</span>`;
            receiverContainer.appendChild(label);
        });
        updateMultiselect('filterReceiverId');
    }
    
    // Frequency dropdown
    const frequencyContainer = document.getElementById('filterFrequency-options');
    if (frequencyContainer && typeof CHANNELS !== 'undefined') {
        frequencyContainer.innerHTML = '';
        Object.entries(CHANNELS).forEach(([freq, name]) => {
            const checked = SELECTED_FREQUENCIES.length === 0 || SELECTED_FREQUENCIES.includes(freq) ? 'checked' : '';
            const label = document.createElement('label');
            label.className = 'multiselect-option';
            label.innerHTML = `<input type="checkbox" value="${freq}" ${checked} onchange="updateMultiselect('filterFrequency')" data-ui-only="true"><span>${freq} MHz - ${name}</span>`;
            frequencyContainer.appendChild(label);
        });
        updateMultiselect('filterFrequency');
    }
    
    // Network Type dropdown
    const networkTypeContainer = document.getElementById('filterNetworkType-options');
    if (networkTypeContainer) {
        networkTypeContainer.innerHTML = '';
        const networkTypes = [
            { value: 'ACARS', label: 'ACARS' },
            { value: 'ATN', label: 'ATN (Not Implemented)' }
        ];
        networkTypes.forEach(type => {
            const checked = SELECTED_NETWORK_TYPES.length === 0 || SELECTED_NETWORK_TYPES.includes(type.value) ? 'checked' : '';
            const label = document.createElement('label');
            label.className = 'multiselect-option';
            label.innerHTML = `<input type="checkbox" value="${type.value}" ${checked} onchange="updateMultiselect('filterNetworkType'); updateACARSDependent()" data-ui-only="true"><span>${type.label}</span>`;
            networkTypeContainer.appendChild(label);
        });
        updateMultiselect('filterNetworkType');
    }
    
    // ACK dropdown
    const ackContainer = document.getElementById('filterAck-options');
    if (ackContainer) {
        ackContainer.innerHTML = '';
        const ackOptions = [
            { value: 'ACK', label: 'ACK' },
            { value: 'NO ACK', label: 'NO ACK' }
        ];
        ackOptions.forEach(opt => {
            const checked = SELECTED_ACK_VALUES.length === 0 || SELECTED_ACK_VALUES.includes(opt.value) ? 'checked' : '';
            const label = document.createElement('label');
            label.className = 'multiselect-option';
            label.innerHTML = `<input type="checkbox" value="${opt.value}" ${checked} onchange="updateMultiselect('filterAck')" data-ui-only="true"><span>${opt.label}</span>`;
            ackContainer.appendChild(label);
        });
        updateMultiselect('filterAck');
    }
    
    // Data Link System and Operation Type dropdown
    const dlsContainer = document.getElementById('filterDataLinkSystem-options');
    if (dlsContainer && typeof CONFIG !== 'undefined' && CONFIG.LABEL_DEFINITIONS) {
        dlsContainer.innerHTML = '';
        
        try {
            // Frontend to backend mapping
            const frontendMap = <?php echo json_encode($FRONTEND_GROUP_MAP); ?>;
            
            // Build structure dynamically from CONFIG
            const systemGroups = {};
            
            // Group label definitions by system
            for (const [key, def] of Object.entries(CONFIG.LABEL_DEFINITIONS)) {
                const system = def.system;
                if (!systemGroups[system]) {
                    systemGroups[system] = [];
                }
                systemGroups[system].push({
                    key: key,
                    definition: def
                });
            }
            
            // Build final structure
            const dlsStructure = [
                {
                    type: 'checkbox',
                    value: 'advanced_manual',
                    label: 'Advanced (Manual Label Selection)',
                    onChange: 'toggleAdvancedMode(this)'
                }
            ];
            
            // Add groups in specific order
            const groupOrder = [
                'ATS Applications',
                'FANS 1/A & FANS 1/A+',
                'Service Related',
                'Service Related - Various Reports [Qx]',
                'Messages from Avionics Subsystem',
                'AMS-Protected Messages',
                'DSP Defined Messages',
                'User Defined Messages',
                'Vendor Defined Messages',
                'System Control'
            ];
            
            groupOrder.forEach(groupName => {
                if (systemGroups[groupName]) {
                    const items = systemGroups[groupName].map(item => {
                        // Get frontend key from mapping
                        let frontendKey = null;
                        for (const [fKey, defKey] of Object.entries(frontendMap)) {
                            if (defKey === item.key) {
                                frontendKey = fKey;
                                break;
                            }
                        }
                        
                        return {
                            value: frontendKey || item.key.toLowerCase(),
                            label: item.definition.display_name,
                            labels: item.definition.labels
                        };
                    });
                    
                    dlsStructure.push({
                        type: 'group',
                        label: groupName,
                        items: items
                    });
                }
            });
            
            dlsStructure.forEach((item, index) => {
                if (item.type === 'checkbox') {
                    const label = document.createElement('label');
                    label.className = 'multiselect-option advanced-mode-checkbox';
                    label.style.fontWeight = 'bold';
                    label.style.borderBottom = '2px solid #ddd';
                    label.style.marginBottom = '8px';
                    label.style.paddingBottom = '8px';
                    label.style.background = '#f8f9fa';
                    label.innerHTML = `<input type="checkbox" value="${item.value}" onchange="${item.onChange}" data-ui-only="true"><span style="font-style: italic;">${item.label}</span>`;
                    dlsContainer.appendChild(label);
                } else if (item.type === 'group') {
                    // If group has only one item, display it as a single checkbox without hierarchy
                    if (item.items.length === 1) {
                        const subItem = item.items[0];
                        const label = document.createElement('label');
                        label.className = 'multiselect-option dls-option';
                        label.setAttribute('data-labels', JSON.stringify(subItem.labels));
                        
                        // _d and SQ: follow CONFIG setting, others: always checked
                        let isChecked;
                        if (subItem.value === 'general_response' || subItem.value === 'sq') {
                            // _d and SQ selection depends on CONFIG
                            isChecked = IS_HISTORY_MODE ? 
                                CONFIG.DEFAULT_SELECT_ALL_LABELS_HISTORY : 
                                CONFIG.DEFAULT_SELECT_ALL_LABELS_LIVE;
                        } else {
                            // All other labels always checked
                            isChecked = true;
                        }
                        
                        label.innerHTML = `<input type="checkbox" value="${subItem.value}" ${isChecked ? 'checked' : ''} onchange="updateMultiselect('filterDataLinkSystem')" data-ui-only="true"><span>${subItem.label}</span>`;
                        dlsContainer.appendChild(label);
                    } else {
                        // Multiple items: show as group with hierarchy
                        const groupContainer = document.createElement('div');
                        groupContainer.className = 'dls-group-container';
                        groupContainer.setAttribute('data-group-id', `group-${index}`);
                        
                        const groupHeader = document.createElement('div');
                        groupHeader.className = 'multiselect-group-label';
                        groupHeader.style.fontWeight = 'bold';
                        groupHeader.style.padding = '8px 12px 4px';
                        groupHeader.style.color = '#666';
                        groupHeader.style.display = 'flex';
                        groupHeader.style.alignItems = 'center';
                        groupHeader.style.gap = '8px';
                        
                        const groupCheckbox = document.createElement('input');
                        groupCheckbox.type = 'checkbox';
                        // Check based on CONFIG and defaultUnchecked flag
                        if (item.defaultUnchecked) {
                            groupCheckbox.checked = false;
                        } else {
                            groupCheckbox.checked = IS_HISTORY_MODE ? 
                                CONFIG.DEFAULT_SELECT_ALL_LABELS_HISTORY : 
                                CONFIG.DEFAULT_SELECT_ALL_LABELS_LIVE;
                        }
                        groupCheckbox.className = 'group-toggle-checkbox';
                        groupCheckbox.setAttribute('data-group-id', `group-${index}`);
                        groupCheckbox.setAttribute('data-ui-only', 'true');
                        groupCheckbox.onclick = function() { toggleGroupSelection(this, `group-${index}`); };
                        
                        const groupLabelText = document.createElement('span');
                        groupLabelText.textContent = item.label;
                        
                        groupHeader.appendChild(groupCheckbox);
                        groupHeader.appendChild(groupLabelText);
                        groupContainer.appendChild(groupHeader);
                        dlsContainer.appendChild(groupContainer);
                        
                        item.items.forEach(subItem => {
                            const label = document.createElement('label');
                            label.className = 'multiselect-option dls-option';
                            label.setAttribute('data-labels', JSON.stringify(subItem.labels));
                            label.setAttribute('data-group-id', `group-${index}`);
                            label.style.paddingLeft = '32px';
                            
                            // _d and SQ: follow CONFIG setting, others: always checked
                            let isChecked;
                            if (subItem.value === 'general_response' || subItem.value === 'sq') {
                                // _d and SQ selection depends on CONFIG
                                isChecked = IS_HISTORY_MODE ? 
                                    CONFIG.DEFAULT_SELECT_ALL_LABELS_HISTORY : 
                                    CONFIG.DEFAULT_SELECT_ALL_LABELS_LIVE;
                            } else {
                                // All other labels always checked
                                isChecked = true;
                            }
                            
                            label.innerHTML = `<input type="checkbox" value="${subItem.value}" ${isChecked ? 'checked' : ''} onchange="updateMultiselect('filterDataLinkSystem'); updateGroupCheckbox('group-${index}')" data-ui-only="true"><span>${subItem.label}</span>`;
                            groupContainer.appendChild(label);
                        });
                        
                        // Update group checkbox state after creating all items
                        updateGroupCheckbox(`group-${index}`);
                    }
                }
            });
            updateMultiselect('filterDataLinkSystem');
        } catch (error) {
            console.error('Error populating DLS filter:', error);
            dlsContainer.innerHTML = '<div style="padding: 10px; color: red;">Error loading filters. Please refresh the page.</div>';
        }
    }
    
    // Update ACARS-dependent fields
    updateACARSDependent();
    
    // After populating, restore filters from URL if in history mode
    if (IS_HISTORY_MODE) {
        restoreFiltersFromURL();
    }
}

// Popup Functions
function showReceiverInfo() {
    const popup = document.getElementById('receiverInfoPopup');
    const content = document.getElementById('receiverInfoContent');
    content.innerHTML = RECEIVER_INFO;
    popup.style.display = 'flex';
}

function closeReceiverInfo(event) {
    document.getElementById('receiverInfoPopup').style.display = 'none';
}

function showOtherSettings() {
    document.getElementById('otherSettingsPopup').style.display = 'flex';
}

function closeOtherSettings(event) {
    document.getElementById('otherSettingsPopup').style.display = 'none';
}

// Open map with history trace (for history mode)
function openMapHistory(reg, date, timestamp) {
    const mapUrl = CONFIG.SURVEILLANCE_MAP_BASE_URL_HISTORY + 
                   CONFIG.SURVEILLANCE_MAP_URL_QUERY_HISTORY
                       .replace('{REG}', reg)
                       .replace('{DATE}', date)
                       .replace('{TIMESTAMP}', timestamp);
    window.open(mapUrl, '_blank');
}

// Filter toggle functionality
function toggleFilters() {
    const content = document.getElementById('filterBarContent');
    const toggleBtn = document.getElementById('filterToggleBtn');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggleBtn.textContent = '▼';
    } else {
        content.style.display = 'none';
        toggleBtn.textContent = '►';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.multiselect-wrapper')) {
        document.querySelectorAll('.multiselect-dropdown').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});

// Filter multiselect options (search)
function filterMultiselectOptions(input, filterId) {
    const searchTerm = input.value.toLowerCase();
    const options = document.getElementById(filterId + '-options').querySelectorAll('.multiselect-option');
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        option.style.display = text.includes(searchTerm) ? 'flex' : 'none';
    });
}

// Quick filter functions
function selectAllLabels(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]').forEach(cb => cb.checked = true);
    updateMultiselect(filterId);
}

function deselectAllLabels(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]').forEach(cb => cb.checked = false);
    window.updateMultiselect(filterId);
}

function selectDAtis(filterId) {
    const datisLabels = ['5D', 'A9', 'B9'];
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]').forEach(cb => {
        if (datisLabels.includes(cb.value)) cb.checked = true;
    });
    updateMultiselect(filterId);
}

function selectPdcDcl(filterId) {
    const pdcLabels = ['A1', 'A3', 'AC', 'AD', 'B1', 'B2', 'B3', 'B4', 'BC', 'BD'];
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]').forEach(cb => {
        if (pdcLabels.includes(cb.value)) cb.checked = true;
    });
    window.updateMultiselect(filterId);
}

// Update ACARS-dependent fields (Registration, Flight, ACK, Data Link System)
function updateACARSDependent() {
    const networkTypeDropdown = document.getElementById('filterNetworkType-dropdown');
    const acarsChecked = networkTypeDropdown ? 
        Array.from(networkTypeDropdown.querySelectorAll('input[type="checkbox"]:checked'))
            .some(cb => cb.value === 'ACARS') : true;
    
    // Registration, Flight, ACK containers
    const registrationContainer = document.getElementById('filterRegistrationContainer');
    const flightContainer = document.getElementById('filterFlightContainer');
    const ackContainer = document.getElementById('filterAckContainer');
    const dlsContainer = document.getElementById('filterDataLinkSystemContainer');
    
    [registrationContainer, flightContainer, ackContainer, dlsContainer].forEach(container => {
        if (container) {
            const inputs = container.querySelectorAll('input, button');
            inputs.forEach(input => {
                input.disabled = !acarsChecked;
                input.style.opacity = acarsChecked ? '1' : '0.5';
                input.style.cursor = acarsChecked ? 'pointer' : 'not-allowed';
            });
            if (!acarsChecked) {
                // Clear text inputs
                const textInputs = container.querySelectorAll('input[type="text"]');
                textInputs.forEach(input => input.value = '');
            }
        }
    });
}

// Toggle advanced manual label selection mode
function toggleAdvancedMode(checkbox) {
    const container = document.getElementById('filterDataLinkSystem-options');
    const dlsOptions = container.querySelectorAll('.dls-option');
    
    if (checkbox.checked) {
        // Hide grouped options, show all labels
        dlsOptions.forEach(opt => opt.style.display = 'none');
        container.querySelectorAll('.multiselect-group-label').forEach(lbl => lbl.style.display = 'none');
        container.querySelectorAll('.dls-group-container').forEach(grp => grp.style.display = 'none');
        
        // Add search box if not exists
        let searchBox = container.querySelector('.advanced-search-box');
        if (!searchBox) {
            searchBox = document.createElement('input');
            searchBox.type = 'text';
            searchBox.className = 'advanced-search-box';
            searchBox.placeholder = 'Search labels...';
            searchBox.style.cssText = 'width: calc(100% - 16px); padding: 8px; margin: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;';
            searchBox.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const advancedLabels = container.querySelectorAll('.advanced-label-option');
                advancedLabels.forEach(label => {
                    const text = label.textContent.toLowerCase();
                    label.style.display = text.includes(searchTerm) ? 'block' : 'none';
                });
            });
            // Insert after the advanced mode checkbox
            const advancedCheckbox = container.querySelector('input[value="advanced_manual"]').closest('label');
            advancedCheckbox.parentNode.insertBefore(searchBox, advancedCheckbox.nextSibling);
        }
        searchBox.style.display = 'block';
        
        // Populate with all message labels
        if (typeof MESSAGE_LABEL_DESCRIPTIONS !== 'undefined') {
            const labelOptions = [];
            for (const [code, info] of Object.entries(MESSAGE_LABEL_DESCRIPTIONS)) {
                let text = `${code} (${info.explanation}`;
                if (info.direction) {
                    text += ` — ${info.direction}`;
                }
                text += ')';
                labelOptions.push({ code: code, text: text });
            }
            labelOptions.sort((a, b) => a.code.localeCompare(b.code));
            
            labelOptions.forEach(option => {
                const label = document.createElement('label');
                label.className = 'multiselect-option advanced-label-option';
                // If SELECTED_DLS contains label codes (not group names), use them; otherwise check all
                // Label codes are uppercase with numbers/letters (A1, B2, SQ, etc.)
                // Group names are lowercase (datis, dcl_pdc, etc.)
                const hasLabelCodes = SELECTED_DLS.length > 0 && SELECTED_DLS.some(item => item.match(/^[A-Z0-9_~]+$/));
                const isChecked = hasLabelCodes ? SELECTED_DLS.includes(option.code) : true;
                label.innerHTML = `<input type="checkbox" value="${option.code}" ${isChecked ? 'checked' : ''} onchange="updateMultiselect('filterDataLinkSystem')"><span>${option.text}</span>`;
                container.appendChild(label);
            });
        }
    } else {
        // Remove advanced labels, show grouped options
        container.querySelectorAll('.advanced-label-option').forEach(opt => opt.remove());
        dlsOptions.forEach(opt => opt.style.display = 'flex');
        container.querySelectorAll('.multiselect-group-label').forEach(lbl => lbl.style.display = 'flex');
        container.querySelectorAll('.dls-group-container').forEach(grp => grp.style.display = 'block');
        
        // Check all group options and group checkboxes when switching back to normal mode
        container.querySelectorAll('.dls-option input[type="checkbox"]').forEach(cb => cb.checked = true);
        container.querySelectorAll('.group-toggle-checkbox').forEach(cb => cb.checked = true);
        
        // Hide search box
        const searchBox = container.querySelector('.advanced-search-box');
        if (searchBox) {
            searchBox.style.display = 'none';
            searchBox.value = ''; // Clear search
        }
    }
    updateMultiselect('filterDataLinkSystem');
}

// Toggle all options in a group
function toggleGroupSelection(checkbox, groupId) {
    const container = document.getElementById('filterDataLinkSystem-options');
    const groupOptions = container.querySelectorAll(`.dls-option[data-group-id="${groupId}"] input[type="checkbox"]`);
    
    groupOptions.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    
    updateMultiselect('filterDataLinkSystem');
}

// Update group checkbox based on children state
function updateGroupCheckbox(groupId) {
    const container = document.getElementById('filterDataLinkSystem-options');
    const groupCheckbox = container.querySelector(`.group-toggle-checkbox[data-group-id="${groupId}"]`);
    const groupOptions = container.querySelectorAll(`.dls-option[data-group-id="${groupId}"] input[type="checkbox"]`);
    
    if (groupCheckbox && groupOptions.length > 0) {
        const checkedCount = Array.from(groupOptions).filter(cb => cb.checked).length;
        groupCheckbox.checked = checkedCount === groupOptions.length;
        groupCheckbox.indeterminate = checkedCount > 0 && checkedCount < groupOptions.length;
    }
}

// ===============================
// MODE-SPECIFIC FUNCTIONS
// ===============================

<?php if ($isHistoryMode): ?>
// HISTORY MODE FUNCTIONS

function applyFilters() {
    // In History mode, filters are applied server-side via form submission
    // This function just submits the form
    document.getElementById('additionalFiltersForm').submit();
}

function resetFilters() {
    // Clear all filters and redirect to default 7-day range
    const today = new Date();
    const endDate = today.toISOString().split('T')[0];
    const startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    window.location.href = '/data-link?history=1&startDate=' + startDate + '&startTime=00:00&endDate=' + endDate + '&endTime=23:59';
}

function resetDateTimeFilters() {
    // Only reset date range, preserve other filters
    const today = new Date();
    const endDate = today.toISOString().split('T')[0];
    const startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    // Update the date/time inputs
    document.getElementById('startDate').value = startDate;
    document.getElementById('startTime').value = '00:00';
    document.getElementById('endDate').value = endDate;
    document.getElementById('endTime').value = '23:59';
    
    // Submit the form to apply the reset date range with existing filters
    document.getElementById('additionalFiltersForm').submit();
}

function clearMessageSearch() {
    document.getElementById('filterMessageContent').value = '';
    document.getElementById('additionalFiltersForm').submit();
}

function applyOtherSettings() {
    // Update hidden input from checkbox before submitting
    const hideIncomplete = document.getElementById('hideIncompleteMessages');
    const hiddenInput = document.getElementById('hideIncompleteInput');
    if (hideIncomplete && hiddenInput) {
        hiddenInput.value = hideIncomplete.checked ? '1' : '0';
    }
    
    closeOtherSettings();
    populateHiddenInputs();
    document.getElementById('additionalFiltersForm').submit();
}

<?php else: ?>
// LIVE MODE FUNCTIONS

function applyFilters() {
    const receiverIds = getMultiselectValues('filterReceiverId').map(v => v.toLowerCase());
    const frequencies = getMultiselectValues('filterFrequency');
    const networkTypes = getMultiselectValues('filterNetworkType');
    const registration = document.getElementById('filterRegistration').value.toLowerCase();
    const flightNumber = document.getElementById('filterFlightNumber').value.toLowerCase();
    const ackValues = getMultiselectValues('filterAck');
    const selectedLabels = getSelectedLabelsFromDLS();
    const messageContent = document.getElementById('filterMessageContent').value.toLowerCase();
    
    // Debug: log selected labels when filtering
    console.log('[FILTER DEBUG - LIVE MODE] Selected labels count:', selectedLabels.length, '| Labels:', selectedLabels);
    
    const messages = document.querySelectorAll('.message-item');
    let visibleCount = 0;
    
    // Get all known labels for "Other Labeled Messages" check
    const container = document.getElementById('filterDataLinkSystem-options');
    const allKnownLabels = [];
    container.querySelectorAll('.dls-option').forEach(opt => {
        if (opt.querySelector('input').value !== 'other_labeled') {
            const labels = JSON.parse(opt.getAttribute('data-labels') || '[]');
            allKnownLabels.push(...labels);
        }
    });
    
    messages.forEach(message => {
        let show = true;
        const tags = message.querySelector('.message-tags');
        const textContent = message.querySelector('.message-content');
        
        const stationTag = tags.querySelector('.tag-station');
        const freqTag = tags.querySelector('.tag-freq');
        const appTag = tags.querySelector('.tag-app');
        const tailTag = tags.querySelector('.tag-tail');
        const flightTag = tags.querySelector('.tag-flight');
        const ackTag = tags.querySelector('.tag-ack');
        const labelTag = tags.querySelector('.tag-label');
        
        if (receiverIds.length > 0 && stationTag) {
            const stationText = stationTag.textContent.toLowerCase();
            if (!receiverIds.some(id => stationText.includes(id))) show = false;
        }
        
        if (frequencies.length > 0 && freqTag) {
            if (!frequencies.some(freq => freqTag.textContent.includes(freq))) show = false;
        }
        
        // Network Type filtering (only apply if not all options are selected)
        if (networkTypes.length > 0 && networkTypes.length < 2) {
            const appText = appTag ? appTag.textContent : '';
            let matchesNetwork = false;
            
            if (networkTypes.includes('ACARS')) {
                if (appText === 'ACARS' || appText === 'CPDLC') matchesNetwork = true;
            }
            
            // ATN not implemented - no messages should show when ATN is selected
            if (networkTypes.includes('ATN')) {
                matchesNetwork = false; // Force hide all messages for ATN
            }
            
            if (!matchesNetwork) show = false;
        }
        
        if (registration && tailTag) {
            if (!removeDashes(tailTag.textContent.toLowerCase()).includes(removeDashes(registration))) show = false;
        }
        
        if (flightNumber && flightTag && !flightTag.textContent.toLowerCase().includes(flightNumber)) {
            show = false;
        }
        
        if (ackValues.length > 0 && ackValues.length < 2 && ackTag) {
            if (!ackValues.includes(ackTag.textContent)) show = false;
        }
        
        // Data Link System filtering
        if (selectedLabels.length > 0 && labelTag) {
            const labelText = labelTag.textContent.trim();
            const labelCode = labelText.split(' ')[0].split('(')[0]; // Get just the code part (before space or parenthesis)
            
            // Debug: log SQ messages to help diagnose filtering issues
            if (labelCode === 'SQ') {
                console.log('[DEBUG - LIVE MODE] SQ message found. Selected labels:', selectedLabels, 'Includes SQ?', selectedLabels.includes('SQ'));
            }
            
            let matchesLabel = false;
            
            // Check if label code is in the selected labels list
            if (selectedLabels.includes(labelCode)) {
                matchesLabel = true;
            }
            
            // Check for "Other Labeled Messages"
            if (selectedLabels.includes('__OTHER_LABELED__')) {
                if (!allKnownLabels.includes(labelCode)) {
                    matchesLabel = true;
                }
            }
            
            if (!matchesLabel) show = false;
        }
        
        if (messageContent && textContent) {
            if (!textContent.textContent.toLowerCase().includes(messageContent)) show = false;
        }
        
        // Check for incomplete multi-block messages
        const hideIncomplete = document.getElementById('hideIncompleteMessages');
        if (hideIncomplete && hideIncomplete.checked) {
            const assstat = message.getAttribute('data-assstat');
            if (assstat === 'in progress' || assstat === 'out of sequence') show = false;
        }
        
        message.style.display = show ? 'block' : 'none';
        if (show) visibleCount++;
    });
    
    // Update message count for History mode  
    const messageInfo = document.getElementById('messageInfo');
    if (messageInfo) {
        messageInfo.textContent = `Showing ${visibleCount} messages`;
    }
}

function resetFilters() {
    document.querySelectorAll('.multiselect-dropdown input[type="checkbox"]').forEach(cb => cb.checked = true);
    ['filterReceiverId', 'filterFrequency', 'filterNetworkType', 'filterAck', 'filterDataLinkSystem'].forEach(id => updateMultiselect(id));
    document.getElementById('filterRegistration').value = '';
    document.getElementById('filterFlightNumber').value = '';
    document.getElementById('filterMessageContent').value = '';
    updateACARSDependent();
    applyFilters();
}

function clearMessageSearch() {
    document.getElementById('filterMessageContent').value = '';
    applyFilters();
}

function applyOtherSettings() {
    closeOtherSettings();
    applyFilters();
}

<?php endif; ?>

// ===============================
// COMMON FUNCTIONS (BOTH MODES)
// ===============================

// Client-side Pagination for History Mode
let currentPage = 1;
let messagesPerPage = <?php echo HISTORY_MESSAGES_PER_PAGE; ?>;
let allMessages = [];

function initializePagination() {
    if (!IS_HISTORY_MODE) return;
    
    // Get all message elements
    const container = document.getElementById('messagesContainer');
    allMessages = Array.from(container.querySelectorAll('.message-item'));
    
    // Apply initial pagination
    updatePagination();
}

function updatePagination() {
    const totalPages = Math.ceil(allMessages.length / messagesPerPage);
    const startIndex = (currentPage - 1) * messagesPerPage;
    const endIndex = startIndex + messagesPerPage;
    
    // Hide all messages
    allMessages.forEach((msg, index) => {
        if (index >= startIndex && index < endIndex) {
            msg.style.display = 'block';
        } else {
            msg.style.display = 'none';
        }
    });
    
    // Update page indicator
    const pageIndicator = document.getElementById('pageIndicator');
    if (pageIndicator) {
        pageIndicator.textContent = `${currentPage} / ${totalPages}`;
    }
    
    // Scroll to top of messages
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function changePage(direction) {
    const totalPages = Math.ceil(allMessages.length / messagesPerPage);
    
    switch(direction) {
        case 'first':
            currentPage = 1;
            break;
        case 'prev':
            if (currentPage > 1) currentPage--;
            break;
        case 'next':
            if (currentPage < totalPages) currentPage++;
            break;
        case 'last':
            currentPage = totalPages;
            break;
    }
    
    updatePagination();
}

function changePerPage() {
    const select = document.getElementById('perPageSelect');
    messagesPerPage = parseInt(select.value);
    currentPage = 1; // Reset to first page
    updatePagination();
}

// Load More Messages Function
function loadMoreMessages() {
    const btn = document.getElementById('loadMoreBtn');
    if (!btn) return;
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.textContent = '⏳ Loading...';
    
    // Get current parameters
    const urlParams = new URLSearchParams(window.location.search);
    const currentOffset = parseInt(urlParams.get('offset') || '0');
    const newOffset = currentOffset + 5000;
    
    // Set offset parameter
    urlParams.set('offset', newOffset);
    
    // Build URL
    const url = '/data-link?' + urlParams.toString();
    
    // Fetch new messages
    fetch(url)
        .then(response => response.text())
        .then(html => {
            // Parse HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract messages
            const newMessages = doc.querySelectorAll('.message-item');
            const messagesContainer = document.getElementById('messagesContainer');
            
            // Append new messages
            newMessages.forEach(msg => {
                messagesContainer.appendChild(msg.cloneNode(true));
            });
            
            // Re-initialize pagination with new messages
            allMessages = Array.from(messagesContainer.querySelectorAll('.message-item'));
            updatePagination();
            
            // Update message count
            const messageInfo = document.getElementById('messageInfo');
            const totalCount = doc.querySelector('#messageInfo')?.textContent.match(/of (\d+)/)?.[1] || allMessages.length;
            if (messageInfo) {
                messageInfo.textContent = `Showing ${allMessages.length} of ${totalCount} messages`;
            }
            
            // Check if there are more messages
            const loadMoreContainer = doc.querySelector('.load-more-container');
            if (loadMoreContainer) {
                // Update button text with remaining count
                const remainingText = loadMoreContainer.querySelector('#loadMoreBtn')?.textContent;
                btn.textContent = remainingText || '📥 Load More Messages';
                btn.disabled = false;
                
                // Update URL without reloading
                window.history.pushState({}, '', url);
            } else {
                // No more messages
                btn.parentElement.remove();
            }
        })
        .catch(error => {
            console.error('Error loading more messages:', error);
            btn.textContent = '❌ Error - Click to retry';
            btn.disabled = false;
        });
}

function restoreFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Restore multiselect filters
    const filterMap = {
        'filterReceiverId': 'filterReceiverId-dropdown',
        'filterFrequency': 'filterFrequency-dropdown',
        'filterNetworkType': 'filterNetworkType-dropdown',
        'filterAck': 'filterAck-dropdown',
        'filterDataLinkSystem': 'filterDataLinkSystem-dropdown'
    };
    
    Object.entries(filterMap).forEach(([paramName, dropdownId]) => {
        const values = urlParams.getAll(paramName + '[]');
        
        // Only modify checkboxes if URL has parameters for this filter
        if (values.length > 0) {
            const dropdown = document.getElementById(dropdownId);
            
            if (dropdown) {
                // First, uncheck ALL checkboxes in this dropdown
                dropdown.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                
                // Then check only the ones from URL
                values.forEach(value => {
                    const checkbox = dropdown.querySelector(`input[type="checkbox"][value="${CSS.escape(value)}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                
                // Update the dropdown display
                const filterId = dropdownId.replace('-dropdown', '');
                updateMultiselect(filterId);
                
                // For Data Link System, update group checkboxes
                if (filterId === 'filterDataLinkSystem') {
                    // Find all group IDs
                    const groups = dropdown.querySelectorAll('[data-group-id]');
                    const groupIds = new Set();
                    groups.forEach(el => {
                        const groupId = el.getAttribute('data-group-id');
                        if (groupId && groupId.startsWith('group-')) {
                            groupIds.add(groupId);
                        }
                    });
                    
                    // Update each group checkbox
                    groupIds.forEach(groupId => {
                        updateGroupCheckbox(groupId);
                    });
                }
            }
        }
    });
    
    // Restore text inputs
    ['filterRegistration', 'filterFlightNumber', 'messageContent'].forEach(fieldName => {
        const value = urlParams.get(fieldName);
        if (value) {
            const input = document.getElementById(fieldName);
            if (input) {
                input.value = value;
            }
        }
    });
    
    // Restore advanced mode from URL
    const advancedMode = urlParams.get('advancedMode');
    if (advancedMode === '1') {
        const advancedCheckbox = document.querySelector('input[value="advanced_manual"]');
        if (advancedCheckbox) {
            advancedCheckbox.checked = true;
            toggleAdvancedMode(advancedCheckbox);
        }
    }
}

function populateHiddenInputs() {
    const container = document.getElementById('hiddenInputsContainer');
    if (!container) {
        return;
    }
    container.innerHTML = '';
    
    // Populate multiselect filters
    const filterMap = {
        'filterReceiverId': 'filterReceiverId[]',
        'filterFrequency': 'filterFrequency[]',
        'filterNetworkType': 'filterNetworkType[]',
        'filterAck': 'filterAck[]',
        'filterDataLinkSystem': 'filterDataLinkSystem[]'
    };
    
    Object.entries(filterMap).forEach(([filterId, fieldName]) => {
        const dropdown = document.getElementById(filterId + '-dropdown');
        if (dropdown) {
            const checkedBoxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
            
            checkedBoxes.forEach(cb => {
                // Skip advanced_manual checkbox, group checkboxes (value='on'), and other invalid values
                if (cb.value !== 'advanced_manual' && cb.value !== 'on' && cb.value !== '') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = fieldName;
                    input.value = cb.value;
                    container.appendChild(input);
                }
            });
        }
    });
    
    // Update hideIncomplete hidden input value from checkbox
    const hideIncomplete = document.getElementById('hideIncompleteMessages');
    const hiddenInput = document.getElementById('hideIncompleteInput');
    if (hideIncomplete && hiddenInput) {
        hiddenInput.value = hideIncomplete.checked ? '1' : '0';
    }
    
    // Add advanced mode parameter if checked
    const advancedCheckbox = document.querySelector('input[value="advanced_manual"]');
    if (advancedCheckbox && advancedCheckbox.checked) {
        const advancedInput = document.createElement('input');
        advancedInput.type = 'hidden';
        advancedInput.name = 'advancedMode';
        advancedInput.value = '1';
        container.appendChild(advancedInput);
    }
}

// Define all functions that will be used in onclick handlers (must be before DOMContentLoaded)
window.toggleMultiselect = function(event, filterId) {
    event.stopPropagation();
    const dropdown = document.getElementById(filterId + '-dropdown');
    document.querySelectorAll('.multiselect-dropdown').forEach(dd => {
        if (dd.id !== filterId + '-dropdown') dd.style.display = 'none';
    });
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
};

window.updateMultiselect = function(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    
    // For Data Link System, exclude group checkboxes and advanced_manual checkbox
    let checkboxes;
    if (filterId === 'filterDataLinkSystem') {
        checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:not(.group-toggle-checkbox):not([value="advanced_manual"])');
    } else {
        checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
    }
    
    const displaySpan = document.getElementById(filterId + '-display');
    
    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const totalCount = checkboxes.length;
    
    if (checkedCount === 0) {
        displaySpan.textContent = 'None Selected';
    } else if (checkedCount === totalCount) {
        displaySpan.textContent = 'All Selected';
    } else {
        displaySpan.textContent = `${checkedCount} of ${totalCount} Selected`;
    }
};

window.selectAllDLS = function(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]:not([value="advanced_manual"])').forEach(cb => cb.checked = true);
    window.updateMultiselect(filterId);
};

window.deselectAllDLS = function(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]:not([value="advanced_manual"])').forEach(cb => cb.checked = false);
    window.updateMultiselect(filterId);
};

window.deselectDelSQ = function(filterId) {
    const dropdown = document.getElementById(filterId + '-dropdown');
    dropdown.querySelectorAll('.multiselect-options input[type="checkbox"]').forEach(cb => {
        if (cb.value === 'general_response' || cb.value === 'sq') cb.checked = false;
    });
    window.updateMultiselect(filterId);
    // Also update group checkbox for System Control
    const groupCheckboxes = dropdown.querySelectorAll('.group-toggle-checkbox');
    groupCheckboxes.forEach(gcb => {
        window.updateGroupCheckbox(gcb.getAttribute('data-group-id'));
    });
};

window.updateGroupCheckbox = function(groupId) {
    const groupContainer = document.querySelector(`.dls-group-container[data-group-id="${groupId}"]`);
    if (!groupContainer) return;
    
    const groupCheckbox = groupContainer.querySelector('.group-toggle-checkbox');
    const itemCheckboxes = groupContainer.querySelectorAll('.dls-option input[type="checkbox"]');
    
    if (itemCheckboxes.length === 0) return;
    
    const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
    
    if (checkedCount === 0) {
        // None checked
        groupCheckbox.checked = false;
        groupCheckbox.indeterminate = false;
    } else if (checkedCount === itemCheckboxes.length) {
        // All checked
        groupCheckbox.checked = true;
        groupCheckbox.indeterminate = false;
    } else {
        // Some checked (partial)
        groupCheckbox.checked = false;
        groupCheckbox.indeterminate = true;
    }
};

window.toggleGroupSelection = function(groupCheckbox, groupId) {
    const groupContainer = document.querySelector(`.dls-group-container[data-group-id="${groupId}"]`);
    if (!groupContainer) return;
    
    const itemCheckboxes = groupContainer.querySelectorAll('.dls-option input[type="checkbox"]');
    itemCheckboxes.forEach(cb => cb.checked = groupCheckbox.checked);
    
    window.updateMultiselect('filterDataLinkSystem');
};

document.addEventListener('DOMContentLoaded', function() {
    
    // Populate all dropdowns (this will call restoreFiltersFromURL after completion)
    populateDropdowns();
    
    // Date validation for History mode
    if (IS_HISTORY_MODE) {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        
        if (startDateInput && endDateInput) {
            // Update end date min when start date changes
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            });
            
            // Update start date max when end date changes
            endDateInput.addEventListener('change', function() {
                startDateInput.max = this.value;
                if (startDateInput.value && startDateInput.value > this.value) {
                    startDateInput.value = this.value;
                }
            });
            
            // Set initial constraints
            if (startDateInput.value) {
                endDateInput.min = startDateInput.value;
            }
            if (endDateInput.value) {
                startDateInput.max = endDateInput.value;
            }
        }
        
        // Populate hidden inputs before form submission
        const filtersForm = document.getElementById('additionalFiltersForm');
        if (filtersForm) {
            filtersForm.addEventListener('submit', function(e) {
                populateHiddenInputs();
            });
        }
    } else {
        // Live mode - no date validation needed
    }
    
    // Initialize pagination for History mode
    if (IS_HISTORY_MODE) {
        initializePagination();
    }
    
    // Only apply client-side filters in Live mode if there are URL parameters
    if (!IS_HISTORY_MODE) {
        const hasFilterParams = SELECTED_RECEIVERS.length > 0 || SELECTED_FREQUENCIES.length > 0 || 
                                SELECTED_NETWORK_TYPES.length > 0 || SELECTED_ACK_VALUES.length > 0 || 
                                SELECTED_DLS.length > 0;
        
        if (hasFilterParams) {
            applyFilters();
        } else {
            // Show all messages by default
            document.querySelectorAll('.message-item').forEach(msg => msg.style.display = 'block');
            const totalMessagesElement = document.getElementById('totalMessages');
            if (totalMessagesElement) {
                const messageCount = document.querySelectorAll('.message-item').length;
                totalMessagesElement.textContent = `Total: ${messageCount}`;
            }
        }
    }
});

<?php if ($isHistoryMode): ?>
// Decode history messages - MUST be outside document.ready for immediate execution
(async function decodeHistoryMessages() {
    // Wait a bit for DOM to be fully ready and for scripts to load
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const messages = document.querySelectorAll('.message-item');
    // Legacy API decoder labels (H1, MA, A6, B6, AA, BA)
    const legacyDecoderLabels = ['A6', 'AA', 'B6', 'BA', 'H1', 'SA', 'MA'];
    const DECODE_API_URL = CONFIG.DECODE_API_URL;
    const MAX_RETRIES = CONFIG.DECODER_MAX_RETRIES;
    const TIMEOUT_MS = CONFIG.DECODER_TIMEOUT_MS;
    
    let consecutiveFailures = 0;
    let decoderOffline = false;
    
    for (const messageDiv of messages) {
        const labelTag = messageDiv.querySelector('.tag-label');
        if (!labelTag) continue;
        
        // Label formatı: "SA (Media Advisory — DN)" gibi
        // İlk kelimeyi (label kodunu) al
        const labelText = labelTag.textContent.trim();
        const label = labelText.split(/[\s(]/)[0].toUpperCase();
        
        const contentDiv = messageDiv.querySelector('.message-content');
        if (!contentDiv) continue;
        
        const text = contentDiv.textContent.trim();
        if (!text || text === '[No message content]' || text === '[No message content.]') continue;
        
        // Check if already decoded
        if (messageDiv.querySelector('.message-decoded')) continue;
        
        // Check if label is decodable (either legacy or new library)
        const labelInfo = MESSAGE_LABEL_DESCRIPTIONS[label];
        const isLegacyDecodable = legacyDecoderLabels.includes(label);
        const isLibraryDecodable = labelInfo && labelInfo.decodable_directions && labelInfo.decodable_directions.length > 0;
        
        if (!isLegacyDecodable && !isLibraryDecodable) continue;
        
        // ALWAYS show blue box for decodable messages
        const decodedDiv = document.createElement('div');
        decodedDiv.className = 'message-decoded';
        
        if (isLibraryDecodable && typeof iboDecodeAcarsMsg === 'function') {
            // Use new ACARS decoding library
            const decodableDirections = labelInfo.decodable_directions;
            let decodedText = '';
            
            // Show temporary loading message
            decodedDiv.textContent = '[Decoding...]';
            messageDiv.appendChild(decodedDiv);
            
            // Decode asynchronously
            (async () => {
                for (const direction of decodableDirections) {
                    const dirLower = direction.toLowerCase(); // 'up' or 'dn'
                    const dirDisplay = direction === 'UP' ? 'UPLINK' : 'DOWNLINK';
                    
                    decodedText += `ASSUMED AS ${dirDisplay}:\n`;
                    try {
                        const decoded = await iboDecodeAcarsMsg(dirLower, label, text);
                        decodedText += String(decoded) + '\n';
                    } catch (error) {
                        decodedText += `[Decoding error: ${error.message}]\n`;
                    }
                    
                    if (decodableDirections.length > 1) {
                        decodedText += '\n';
                    }
                }
                
                decodedDiv.textContent = decodedText.trim();
            })();
            continue;
        }
        
        if (isLegacyDecodable) {
            // Use legacy API decoder for H1, MA, SA, A6, B6, AA, BA
            // If decoder is offline, skip trying and show error immediately
            if (decoderOffline) {
                decodedDiv.textContent = '[Error occurred during connecting to decoder.]';
                messageDiv.appendChild(decodedDiv);
                continue;
            }
            
            decodedDiv.textContent = '[Connecting to decoder...]';
            messageDiv.appendChild(decodedDiv);
            
            let retryCount = 0;
            let success = false;
            
            while (retryCount < MAX_RETRIES && !success) {
                try {
                    // Create timeout promise
                    const timeoutPromise = new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('Timeout')), TIMEOUT_MS)
                    );
                    
                    // Create fetch promise
                    const fetchPromise = fetch(DECODE_API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ label, text })
                    });
                    
                    // Race between fetch and timeout
                    const response = await Promise.race([fetchPromise, timeoutPromise]);
                    
                    if (response.ok) {
                        const result = await response.json();
                        
                        if (result.decodable && result.decoded && result.decoded.trim()) {
                            decodedDiv.textContent = 'Decoded:\n' + result.decoded;
                            success = true;
                            consecutiveFailures = 0; // Reset failure counter
                        } else {
                            // Decoder responded but no content
                            decodedDiv.textContent = '[No decoding available.]';
                            success = true;
                            consecutiveFailures = 0; // Reset failure counter
                        }
                    } else {
                        console.error('❌ Decode failed:', response.status);
                        retryCount++;
                        if (retryCount < MAX_RETRIES) {
                            await new Promise(resolve => setTimeout(resolve, CONFIG.DECODER_RETRY_DELAY_MS));
                        }
                    }
                } catch (error) {
                    console.error('❌ Decode error:', error.message);
                    retryCount++;
                    if (retryCount < MAX_RETRIES) {
                        await new Promise(resolve => setTimeout(resolve, CONFIG.DECODER_RETRY_DELAY_MS));
                    }
                }
            }
            
            // If all retries failed, show error message
            if (!success) {
                decodedDiv.textContent = '[Error occurred during connecting to decoder.]';
                console.error('❌ Decode: Max retries reached for', label);
                consecutiveFailures++;
                
                // If 3 consecutive failures, mark decoder as offline
                if (consecutiveFailures >= 3) {
                    decoderOffline = true;
                    console.error('❌ Decode: Decoder marked as offline, skipping remaining messages');
                }
            }
        }
    }
})();

<?php endif; ?>

<?php if (!$isHistoryMode): ?>
// LIVE MODE FUNCTIONS

const SSE_HOST = CONFIG.API_HOST;
const SSE_PORT = CONFIG.API_PORT;
const MAX_DISPLAY_MESSAGES = CONFIG.MAX_DISPLAY_MESSAGES;
const DECODE_API_URL = CONFIG.DECODE_API_URL;

let eventSource = null;
let reconnectInterval = null;
let healthCheckInterval = null;
let tcpConnected = false;

// Initialize SSE connection
function initSSE() {
    const sseUrl = CONFIG.SSE_STREAM_URL;
    
    try {
        eventSource = new EventSource(sseUrl);
        
        eventSource.onopen = function(e) {
            // Check TCP status immediately
            checkTCPHealth();
            
            // Start periodic health check
            if (healthCheckInterval) {
                clearInterval(healthCheckInterval);
            }
            healthCheckInterval = setInterval(checkTCPHealth, CONFIG.HEALTH_CHECK_INTERVAL_MS);
            
            if (reconnectInterval) {
                clearInterval(reconnectInterval);
                reconnectInterval = null;
            }
        };
        
        eventSource.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                if (data.type === 'history') {
                    const messagesToShow = data.messages.slice(-MAX_DISPLAY_MESSAGES);
                    messagesToShow.forEach(msg => addMessage(msg, false));
                } else if (data.type === 'message') {
                    addMessage(data.data, true);
                }
            } catch (e) {
                console.error('Error parsing SSE message:', e);
            }
        };
        
        eventSource.onerror = function(error) {
            console.error('SSE connection error:', error);
            updateConnectionStatus(false, 'unknown');
            
            if (healthCheckInterval) {
                clearInterval(healthCheckInterval);
                healthCheckInterval = null;
            }
            
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            if (!reconnectInterval) {
                reconnectInterval = setInterval(initSSE, 5000);
            }
        };
    } catch (error) {
        console.error('Failed to create SSE connection:', error);
        updateConnectionStatus(false, 'unknown');
    }
}

function updateConnectionStatus(sseConnected, tcpStatus) {
    const indicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    
    // Show as connected only if both SSE is connected AND TCP is connected
    const isFullyConnected = sseConnected && tcpStatus === 'connected';
    
    indicator.className = 'status-indicator ' + (isFullyConnected ? 'connected' : 'disconnected');
    statusText.textContent = isFullyConnected ? 'Connected' : 'Disconnected';
}

// Check TCP health from backend
function checkTCPHealth() {
    fetch(CONFIG.HEALTH_URL)
        .then(response => response.json())
        .then(data => {
            tcpConnected = data.tcp_status === 'connected';
            const sseConnected = eventSource && eventSource.readyState === EventSource.OPEN;
            updateConnectionStatus(sseConnected, data.tcp_status);
        })
        .catch(error => {
            tcpConnected = false;
            const sseConnected = eventSource && eventSource.readyState === EventSource.OPEN;
            updateConnectionStatus(sseConnected, 'unknown');
        });
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp * 1000);
    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    const pad = n => String(n).padStart(2, '0');
    return `${pad(date.getUTCDate())} ${months[date.getUTCMonth()]} ${date.getUTCFullYear()} ${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())}`;
}

function addMessage(messageData, isNew) {
    const container = document.getElementById('messagesContainer');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message-item' + (isNew ? ' new-message' : '');
    
    if (messageData.assstat) {
        messageDiv.setAttribute('data-assstat', messageData.assstat);
    }
    
    const tagsDiv = document.createElement('div');
    tagsDiv.className = 'message-tags';
    
    // Timestamp
    if (messageData.timestamp) {
        const tag = document.createElement('span');
        tag.className = 'tag tag-timestamp';
        tag.textContent = formatTimestamp(messageData.timestamp);
        tagsDiv.appendChild(tag);
    }
    
    // Station ID
    const stationTag = document.createElement('span');
    stationTag.className = 'tag tag-station';
    stationTag.textContent = messageData.station_id || '';
    tagsDiv.appendChild(stationTag);
    
    // Level
    const levelTag = document.createElement('span');
    levelTag.className = 'tag tag-level';
    levelTag.textContent = (messageData.level !== undefined && messageData.level !== null) ? `${messageData.level} dBFS` : '';
    tagsDiv.appendChild(levelTag);
    
    // Frequency
    const freqTag = document.createElement('span');
    freqTag.className = 'tag tag-freq';
    if (messageData.freq) {
        const freqStr = String(messageData.freq);
        const freqName = FREQUENCY_NAMES[freqStr];
        freqTag.textContent = freqName ? `${messageData.freq} MHz - ${freqName}` : `${messageData.freq} MHz`;
    }
    tagsDiv.appendChild(freqTag);
    
    // App Type
    const appTag = document.createElement('span');
    appTag.className = 'tag tag-app';
    if (messageData.app && messageData.app.name) {
        if (messageData.app.name === 'acarsdec') appTag.textContent = 'ACARS';
        else if (messageData.app.name === 'vdlm2dec') appTag.textContent = 'CPDLC';
        else appTag.textContent = 'Other';
    }
    tagsDiv.appendChild(appTag);
    
    // Tail
    const tailTag = document.createElement('span');
    tailTag.className = 'tag tag-tail';
    tailTag.textContent = messageData.tail || '';
    tagsDiv.appendChild(tailTag);
    
    // Flight
    const flightTag = document.createElement('span');
    flightTag.className = 'tag tag-flight';
    flightTag.textContent = messageData.flight || '';
    tagsDiv.appendChild(flightTag);
    
    // ACK
    const ackTag = document.createElement('span');
    ackTag.className = 'tag tag-ack';
    ackTag.textContent = ('ack' in messageData && messageData.ack) ? 'ACK' : 'NO ACK';
    tagsDiv.appendChild(ackTag);
    
    // Label
    const labelTag = document.createElement('span');
    labelTag.className = 'tag tag-label';
    if (messageData.label) {
        const labelInfo = MESSAGE_LABEL_DESCRIPTIONS[messageData.label];
        if (labelInfo) {
            let labelText = `${messageData.label} (${labelInfo.explanation}`;
            if (labelInfo.direction) {
                labelText += ` — ${labelInfo.direction}`;
            }
            labelText += ')';
            labelTag.textContent = labelText;
        } else {
            labelTag.textContent = messageData.label;
        }
    }
    tagsDiv.appendChild(labelTag);
    
    // Data Link System Type and Operation Type (after label)
    if (messageData.label) {
        const dlTypes = getDataLinkTypes(messageData.label);
        
        const systemTypeTag = document.createElement('span');
        systemTypeTag.className = 'tag tag-dl-system';
        systemTypeTag.textContent = dlTypes.system;
        tagsDiv.appendChild(systemTypeTag);
        
        const operationTypeTag = document.createElement('span');
        operationTypeTag.className = 'tag tag-dl-operation';
        operationTypeTag.textContent = dlTypes.operation;
        tagsDiv.appendChild(operationTypeTag);
    }
    
    messageDiv.appendChild(tagsDiv);
    
    // Show On Map button
    const showOnMapBtn = document.createElement('button');
    showOnMapBtn.className = 'show-on-map-btn';
    showOnMapBtn.textContent = 'Show On Map ↗️';
    if (messageData.tail && messageData.tail.trim()) {
        const tailForUrl = messageData.tail.replace(/-/g, '');
        const mapUrl = CONFIG.SURVEILLANCE_MAP_BASE_URL + CONFIG.SURVEILLANCE_MAP_URL_QUERY.replace('{REG}', tailForUrl);
        showOnMapBtn.onclick = function() {
            window.open(mapUrl, '_blank');
        };
    } else {
        showOnMapBtn.disabled = true;
    }
    
    // Content
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    contentDiv.textContent = (messageData.text || '').trim() || '[No message content.]';
    
    // Check if label is decodable and decode if needed
    const label = messageData.label;
    const text = messageData.text;
    let decodedContent = messageData.decoded; // Backend decoded content (for legacy labels)
    
    if (label && text && text.trim()) {
        const labelInfo = MESSAGE_LABEL_DESCRIPTIONS[label];
        const legacyDecoderLabels = ['A6', 'AA', 'B6', 'BA', 'H1', 'SA', 'MA'];
        const isLibraryDecodable = labelInfo && labelInfo.decodable_directions && labelInfo.decodable_directions.length > 0;
        
        // Use library decoder for new labels (not legacy)
        if (isLibraryDecodable && !legacyDecoderLabels.includes(label) && typeof iboDecodeAcarsMsg === 'function') {
            const decodableDirections = labelInfo.decodable_directions;
            
            // Decode asynchronously and update message when ready
            (async () => {
                let decodedText = '';
                
                for (const direction of decodableDirections) {
                    const dirLower = direction.toLowerCase(); // 'up' or 'dn'
                    const dirDisplay = direction === 'UP' ? 'UPLINK' : 'DOWNLINK';
                    
                    decodedText += `ASSUMED AS ${dirDisplay}:\n`;
                    try {
                        const decoded = await iboDecodeAcarsMsg(dirLower, label, text);
                        decodedText += String(decoded) + '\n';
                    } catch (error) {
                        decodedText += `[Decoding error: ${error.message}]\n`;
                    }
                    
                    if (decodableDirections.length > 1) {
                        decodedText += '\n';
                    }
                }
                
                // Find the decoded div and update it
                const decodedDiv = messageDiv.querySelector('.message-decoded');
                if (decodedDiv) {
                    decodedDiv.textContent = decodedText.trim();
                }
            })();
            
            // Set placeholder while decoding
            decodedContent = '[Decoding...]';
        }
    }
    
    // Decoded content (if available)
    if (decodedContent && decodedContent.trim()) {
        const decodedDiv = document.createElement('div');
        decodedDiv.className = 'message-decoded';
        decodedDiv.textContent = decodedContent;
        messageDiv.appendChild(showOnMapBtn);
        messageDiv.appendChild(tagsDiv);
        messageDiv.appendChild(contentDiv);
        messageDiv.appendChild(decodedDiv);
    } else {
        messageDiv.appendChild(showOnMapBtn);
        messageDiv.appendChild(tagsDiv);
        messageDiv.appendChild(contentDiv);
    }
    
    container.insertBefore(messageDiv, container.firstChild);
    
    if (isNew) {
        setTimeout(() => messageDiv.classList.remove('new-message'), 1000);
    }
    
    // Maintain message limit
    const messages = container.getElementsByClassName('message-item');
    while (messages.length > MAX_DISPLAY_MESSAGES) {
        container.removeChild(messages[messages.length - 1]);
    }
    
    document.getElementById('messageCount').textContent = `Messages: ${messages.length}`;
    
    // Apply filters to new message
    if (isNew) {
        applyFilters();
    }
}

function applyFilters() {
    const receiverIds = getMultiselectValues('filterReceiverId').map(v => v.toLowerCase());
    const frequencies = getMultiselectValues('filterFrequency');
    const networkTypes = getMultiselectValues('filterNetworkType');
    const registration = document.getElementById('filterRegistration').value.toLowerCase();
    const flightNumber = document.getElementById('filterFlightNumber').value.toLowerCase();
    const ackValues = getMultiselectValues('filterAck');
    const selectedLabels = getSelectedLabelsFromDLS();
    const messageContent = document.getElementById('filterMessageContent').value.toLowerCase();
    
    // Debug: log selected labels when filtering
    console.log('[FILTER DEBUG] Selected labels count:', selectedLabels.length, '| Labels:', selectedLabels);
    
    const messages = document.querySelectorAll('.message-item');
    let visibleCount = 0;
    
    // Get all known labels for "Other Labeled Messages" check
    const container = document.getElementById('filterDataLinkSystem-options');
    const allKnownLabels = [];
    container.querySelectorAll('.dls-option').forEach(opt => {
        if (opt.querySelector('input').value !== 'other_labeled') {
            const labels = JSON.parse(opt.getAttribute('data-labels') || '[]');
            allKnownLabels.push(...labels);
        }
    });
    
    messages.forEach(message => {
        let show = true;
        const tags = message.querySelector('.message-tags');
        const textContent = message.querySelector('.message-content');
        
        const stationTag = tags.querySelector('.tag-station');
        const freqTag = tags.querySelector('.tag-freq');
        const appTag = tags.querySelector('.tag-app');
        const tailTag = tags.querySelector('.tag-tail');
        const flightTag = tags.querySelector('.tag-flight');
        const ackTag = tags.querySelector('.tag-ack');
        const labelTag = tags.querySelector('.tag-label');
        
        if (receiverIds.length > 0 && stationTag) {
            const stationText = stationTag.textContent.toLowerCase();
            if (!receiverIds.some(id => stationText.includes(id))) show = false;
        }
        
        if (frequencies.length > 0 && freqTag) {
            if (!frequencies.some(freq => freqTag.textContent.includes(freq))) show = false;
        }
        
        // Network Type filtering (only apply if not all options are selected)
        if (networkTypes.length > 0 && networkTypes.length < 2) {
            const appText = appTag ? appTag.textContent : '';
            let matchesNetwork = false;
            
            if (networkTypes.includes('ACARS')) {
                if (appText === 'ACARS' || appText === 'CPDLC') matchesNetwork = true;
            }
            
            // ATN not implemented - no messages should show when ATN is selected
            if (networkTypes.includes('ATN')) {
                matchesNetwork = false; // Force hide all messages for ATN
            }
            
            if (!matchesNetwork) show = false;
        }
        
        if (registration && tailTag) {
            if (!removeDashes(tailTag.textContent.toLowerCase()).includes(removeDashes(registration))) show = false;
        }
        
        if (flightNumber && flightTag && !flightTag.textContent.toLowerCase().includes(flightNumber)) {
            show = false;
        }
        
        if (ackValues.length > 0 && ackValues.length < 2 && ackTag) {
            if (!ackValues.includes(ackTag.textContent)) show = false;
        }
        
        // Data Link System filtering
        if (selectedLabels.length > 0 && labelTag) {
            const labelText = labelTag.textContent.trim();
            const labelCode = labelText.split(' ')[0].split('(')[0]; // Get just the code part (before space or parenthesis)
            
            // Debug: log SQ messages to help diagnose filtering issues
            if (labelCode === 'SQ') {
                console.log('[DEBUG] SQ message found. Selected labels:', selectedLabels, 'Includes SQ?', selectedLabels.includes('SQ'));
            }
            
            let matchesLabel = false;
            
            // Check if label code is in the selected labels list
            if (selectedLabels.includes(labelCode)) {
                matchesLabel = true;
            }
            
            // Check for "Other Labeled Messages"
            if (selectedLabels.includes('__OTHER_LABELED__')) {
                if (!allKnownLabels.includes(labelCode)) {
                    matchesLabel = true;
                }
            }
            
            if (!matchesLabel) show = false;
        }
        
        if (messageContent && textContent) {
            if (!textContent.textContent.toLowerCase().includes(messageContent)) show = false;
        }
        
        // Check for incomplete multi-block messages
        const hideIncomplete = document.getElementById('hideIncompleteMessages');
        if (hideIncomplete && hideIncomplete.checked) {
            const assstat = message.getAttribute('data-assstat');
            if (assstat === 'in progress' || assstat === 'out of sequence') show = false;
        }
        
        message.style.display = show ? 'block' : 'none';
        if (show) visibleCount++;
    });
    
    // Update message count for Live mode
    const messageCountElement = document.getElementById('messageCount');
    if (messageCountElement) {
        messageCountElement.textContent = `Messages: ${visibleCount}`;
    }
}

function resetFilters() {
    document.querySelectorAll('.multiselect-dropdown input[type="checkbox"]').forEach(cb => cb.checked = true);
    ['filterReceiverId', 'filterFrequency', 'filterNetworkType', 'filterAck', 'filterDataLinkSystem'].forEach(id => updateMultiselect(id));
    document.getElementById('filterRegistration').value = '';
    document.getElementById('filterFlightNumber').value = '';
    document.getElementById('filterMessageContent').value = '';
    updateACARSDependent();
    applyFilters();
}

function clearMessageSearch() {
    document.getElementById('filterMessageContent').value = '';
    applyFilters();
}

function applyOtherSettings() {
    closeOtherSettings();
    applyFilters();
}

// Populate dropdowns on page load
document.addEventListener('DOMContentLoaded', function() {
    // Use common dropdown population function
    populateDropdowns();
    
    // Apply initial filters (to hide unchecked categories like Link Management)
    applyFilters();
    
    // Initialize SSE
    initSSE();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (reconnectInterval) clearInterval(reconnectInterval);
    if (eventSource) eventSource.close();
});

<?php endif; ?>
</script>

<?php ob_end_flush(); ?>
