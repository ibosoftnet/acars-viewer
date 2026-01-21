<style>
/* ATC Datalink Container */
.datalink-container {
    max-width: 100%;
    width: 100%;
    margin: 15px auto 15px auto;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: calc(100% - 30px);
    box-sizing: border-box;
}

/* Mode Tabs */
.mode-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    padding: 0 20px;
}

.mode-tab {
    padding: 12px 24px;
    text-decoration: none;
    color: #6c757d;
    font-weight: 600;
    font-size: 14px;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
}

.mode-tab:hover {
    color: #1e3c72;
    background: rgba(30, 60, 114, 0.05);
}

.mode-tab.active {
    color: #1e3c72;
    border-bottom-color: #1e3c72;
    background: white;
}

/* Header Section */
.datalink-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.datalink-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

/* Connection Status */
.connection-status {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 14px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    position: relative;
}

.status-indicator.connected {
    background: #4caf50;
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.6);
    animation: pulse 2s infinite;
}

.status-indicator.disconnected {
    background: #f44336;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.message-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 5px 12px;
    border-radius: 12px;
    font-weight: 500;
}

/* Filter Container - History Page */
.filter-container {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    max-width: 100%;
    box-sizing: border-box;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    min-width: 0;
}

.filter-group label {
    font-weight: 600;
    font-size: 13px;
    color: #495057;
}

.filter-input {
    padding: 6px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 13px;
    color: #495057 !important;
    background-color: #f8f9fa !important;
    color-scheme: light !important;
}

/* Force light theme for date and time inputs */
.filter-input[type="date"],
.filter-input[type="time"] {
    color: #495057 !important;
    background-color: #f8f9fa !important;
    color-scheme: light !important;
}

.filter-btn {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border: none;
    padding: 7px 20px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.filter-btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 7px 20px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn-secondary:hover {
    background: #5a6268;
}

.pagination-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto;
}

.pagination-group label {
    font-weight: 600;
    font-size: 13px;
    color: #495057;
}

.page-btn {
    background: #fff;
    border: 1px solid #ced4da;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    color: #212529;
    cursor: pointer;
    transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
    background: #e9ecef;
    border-color: #adb5bd;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

#pageInfo {
    font-weight: 600;
    color: #495057;
    min-width: 60px;
    text-align: center;
}

/* Pagination Controls */
.pagination-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
}

.pagination-btn {
    background: #fff;
    border: 1px solid #ced4da;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    color: #212529;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 35px;
}

.pagination-btn:hover:not(:disabled) {
    background: #e9ecef;
    border-color: #adb5bd;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Pagination buttons for header */
.pagination-btn-header {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
    color: #1e3c72;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 32px;
}

.pagination-btn-header:hover:not(:disabled) {
    background: #fff;
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.pagination-btn-header:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

#perPageSelect {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background: white;
    color: #495057;
    cursor: pointer;
}

/* Filter Bar - Additional Filters */
.filter-bar {
    background: #ffffff;
    border-bottom: 1px solid #dee2e6;
}

.filter-bar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    cursor: pointer;
    border-bottom: 1px solid #dee2e6;
    transition: background 0.2s ease;
}

.filter-bar-header:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
}

.filter-bar-title {
    font-weight: 700;
    color: #1e3c72;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.filter-toggle-btn {
    background: transparent;
    border: none;
    color: #1e3c72;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    padding: 4px 8px;
    transition: all 0.2s ease;
}

.filter-toggle-btn:hover {
    color: #2a5298;
}

.filter-bar-content {
    padding: 12px 20px;
    background: #f8f9fa;
}

/* Compact Filter Row - Single Line */
.filter-row-compact {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-item-compact {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 120px;
    flex: 1;
    max-width: 180px;
}

/* Registration and Flight filters - narrower like ACK */
.filter-registration-compact,
.filter-flight-compact {
    max-width: 100px;
    min-width: 100px;
    flex: 0.7;
}

/* ACK filter - narrower */
.filter-ack-compact {
    max-width: 100px;
    min-width: 100px;
    flex: 0.7;
}

/* Label filter - wider */
.filter-label-wide {
    max-width: 420px;
    min-width: 300px;
    flex: 2.5;
}

.filter-item-compact label {
    font-weight: 600;
    font-size: 11px;
    color: #495057;
    white-space: nowrap;
}

.filter-input-compact {
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 12px;
    color: #495057 !important;
    background-color: #ffffff !important;
    color-scheme: light !important;
    width: 100%;
}

.filter-reset-btn-compact {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 32px;
    min-width: 40px;
}

.filter-reset-btn-compact:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(40, 167, 69, 0.5);
}

/* Message Content Search Row */
.search-row {
    border-top: 1px solid #e9ecef;
    padding-top: 10px !important;
    margin-top: 5px;
}

.filter-search-wide {
    flex: 1;
    max-width: 100%;
}

.search-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.filter-input-search {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 13px;
    color: #495057 !important;
    background-color: #ffffff !important;
    color-scheme: light !important;
}

.filter-input-search:focus {
    border-color: #1e3c72;
    outline: none;
    box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.15);
}

.search-btn {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.search-btn:hover {
    background: linear-gradient(135deg, #162d54 0%, #1e3c72 100%);
    box-shadow: 0 2px 6px rgba(30, 60, 114, 0.4);
}

.search-reset-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.search-reset-btn:hover {
    background: #5a6268;
    box-shadow: 0 2px 6px rgba(108, 117, 125, 0.4);
}

/* Multiselect Dropdown Styles */
.multiselect-wrapper {
    position: relative;
    width: 100%;
}

.multiselect-btn {
    width: 100%;
    padding: 6px 8px;
    background: white !important;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: left;
    cursor: pointer;
    font-size: 12px;
    color: #495057 !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
    color-scheme: light !important;
}

.multiselect-btn:hover {
    border-color: #1e3c72;
    box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.1);
}

.multiselect-btn span:first-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}

.dropdown-arrow {
    margin-left: 8px;
    font-size: 10px;
    color: #6c757d;
}

.multiselect-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white !important;
    border: 1px solid #ced4da;
    border-radius: 4px;
    margin-top: 2px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    max-height: 500px;
    overflow-y: auto;
    color-scheme: light !important;
}

.multiselect-search {
    padding: 8px;
    border-bottom: 1px solid #dee2e6;
    position: sticky;
    top: 0;
    background: white !important;
    z-index: 1;
    color-scheme: light !important;
}

.multiselect-search input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 12px;
    background: white !important;
    color: #495057 !important;
    color-scheme: light !important;
}

.multiselect-options {
    padding: 4px 0;
    background: white !important;
}

.multiselect-option {
    display: flex;
    align-items: center;
    background: white !important;
    color: #495057 !important;
    padding: 6px 12px;
    cursor: pointer;
    transition: background 0.2s ease;
    font-size: 12px;
}

.multiselect-option:hover {
    background: #f8f9fa !important;
}

.multiselect-option input[type="checkbox"] {
    margin-right: 8px;
    cursor: pointer;
    color-scheme: light !important;
}

.multiselect-option span {
    flex: 1;
    user-select: none;
    color: #495057 !important;
}

/* Deselect _DEL / _SQ Button */
.deselect-del-sq-btn {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #ffffff;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: normal;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 32px;
    min-width: 65px;
    white-space: nowrap;
    line-height: 1.2;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.deselect-del-sq-btn:hover {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    box-shadow: 0 2px 6px rgba(255, 193, 7, 0.5);
    transform: translateY(-1px);
}

.deselect-del-sq-btn:active {
    transform: translateY(0);
}

/* Deselect All button - gray like date reset */
.deselect-all-btn {
    background: #6c757d;
    color: #ffffff;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: normal;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 32px;
    min-width: 65px;
    white-space: nowrap;
    line-height: 1.2;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.deselect-all-btn:hover {
    background: #5a6268;
    box-shadow: 0 2px 6px rgba(108, 117, 125, 0.5);
    transform: translateY(-1px);
}

.deselect-all-btn:active {
    transform: translateY(0);
}

/* Quick Filters Section inside Dropdown */
.quick-filters {
    padding: 8px 10px;
    background: #f0f4f8;
    border-bottom: 1px solid #dee2e6;
}

.quick-filters-label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.quick-filters-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.qf-btn {
    padding: 4px 8px;
    border: none;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

/* Deselect _DEL/SQ - Amber */
.qf-deselect-delsq {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #ffffff;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}
.qf-deselect-delsq:hover {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
}

/* Select D-ATIS - Blue */
.qf-select-atis {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: #ffffff;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}
.qf-select-atis:hover {
    background: linear-gradient(135deg, #138496 0%, #0e6674 100%);
}

/* Select PDC/DCL - Green */
.qf-select-pdc {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: #ffffff;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}
.qf-select-pdc:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
}

/* Deselect All - Gray */
.qf-deselect-all {
    background: #6c757d;
    color: #ffffff;
}
.qf-deselect-all:hover {
    background: #5a6268;
}

/* Select All - Dark Blue */
.qf-select-all {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #ffffff;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}
.qf-select-all:hover {
    background: linear-gradient(135deg, #162d54 0%, #1e3c72 100%);
}

/* Other Settings Button - Subtle style for header */
.other-settings-btn {
    background: rgba(30, 60, 114, 0.1);
    color: #1e3c72;
    border: 1px solid rgba(30, 60, 114, 0.3);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-left: auto;
    margin-right: 10px;
}

.other-settings-btn:hover {
    background: rgba(30, 60, 114, 0.2);
    border-color: rgba(30, 60, 114, 0.5);
}

.other-settings-btn:active {
    background: rgba(30, 60, 114, 0.3);
}

/* Settings Popup Content */
.popup-settings {
    max-width: 500px;
}

.popup-settings h3 {
    margin: 0 0 20px 0;
    color: #1e3c72;
    font-size: 18px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.settings-option {
    margin-bottom: 15px;
}

.checkbox-container {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    padding: 10px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: background 0.2s ease;
}

.checkbox-container:hover {
    background: #e9ecef;
}

.checkbox-container input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin-top: 2px;
    accent-color: #1e3c72;
}

.checkmark {
    display: none;
}

.setting-text {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    line-height: 1.4;
}

.setting-description {
    margin: 8px 0 0 0;
    font-size: 12px;
    color: #6c757d;
    line-height: 1.5;
    padding-left: 28px;
}

/* Settings Popup Buttons */
.settings-buttons {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.settings-apply-btn {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border: none;
    padding: 8px 24px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.settings-apply-btn:hover {
    background: linear-gradient(135deg, #162d54 0%, #1e3c72 100%);
    box-shadow: 0 2px 8px rgba(30, 60, 114, 0.4);
}

/* Old Filter Styles - Keep for History Page */
.filter-row {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-row:last-child {
    margin-bottom: 0;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 150px;
    flex: 1;
}

.filter-item label {
    font-weight: 600;
    font-size: 12px;
    color: #495057;
}

.filter-select,
.filter-select-multiple,
.filter-item .filter-input {
    padding: 6px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 13px;
    color: #495057 !important;
    background-color: #ffffff !important;
    color-scheme: light !important;
}

.filter-select-multiple {
    min-height: 80px;
    max-height: 120px;
}

.filter-select-multiple option {
    padding: 4px 8px;
}

.filter-select-multiple option:checked {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
}

.filter-reset-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 7px 15px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-left: 5px;
}

.filter-reset-btn:hover {
    background: #5a6268;
}

/* Loading Indicator */
.loading-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    gap: 15px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #1e3c72;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-indicator span {
    color: #6c757d;
    font-size: 14px;
    font-weight: 600;
}

/* No Messages */
.no-messages {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    font-size: 16px;
    font-style: italic;
}

/* Legend Container */
.legend-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
    padding: 12px 20px;
    border-bottom: 2px solid #1e3c72;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.legend-label {
    font-weight: 700;
    color: #1e3c72;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.legend-container .message-tags {
    margin-bottom: 0;
    flex: 1;
}

.receiver-info-btn {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.receiver-info-btn:hover {
    background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    transform: translateY(-1px);
}

/* Show On Map Button */
.show-on-map-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    z-index: 10;
}

.show-on-map-btn:hover {
    background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    transform: translateY(-1px);
}

.show-on-map-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.5;
}

.show-on-map-btn:disabled:hover {
    transform: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Popup Overlay */
.popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.popup-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    animation: popupSlideIn 0.3s ease;
}

@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.popup-close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 32px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
    transition: color 0.3s ease;
    line-height: 1;
}

.popup-close:hover {
    color: #e74c3c;
}

.popup-content h2 {
    color: #1e3c72;
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 22px;
    border-bottom: 2px solid #1e3c72;
    padding-bottom: 10px;
}

.popup-content p {
    color: #000000;
}

.receiver-section {
    margin-bottom: 20px;
}

.receiver-section p {
    color: #000000;
    font-size: 14px;
    margin-bottom: 8px;
}

.receiver-section h3 {
    color: #2a5298;
    font-size: 18px;
    margin-bottom: 10px;
}

.receiver-section h4 {
    color: #555;
    font-size: 15px;
    margin-bottom: 8px;
    font-weight: 600;
}

.receiver-section ul {
    list-style: none !important;
    padding-left: 20px !important;
    margin-left: 0 !important;
    margin-top: 10px !important;
}

.receiver-section ul li {
    display: block !important;
    color: #000 !important;
    padding: 3px 0 !important;
    margin-bottom: 3px !important;
    font-family: inherit !important;
    font-size: 14px !important;
    line-height: 1.6 !important;
    background: none !important;
    border: none !important;
}

/* Messages Container */
.messages-container {
    padding: 15px;
    background: #f8f9fa;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    max-width: 100%;
    box-sizing: border-box;
}

/* Custom Scrollbar */
.messages-container::-webkit-scrollbar {
    width: 8px;
}

.messages-container::-webkit-scrollbar-track {
    background: #e0e0e0;
    border-radius: 4px;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #1e3c72;
    border-radius: 4px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: #2a5298;
}

/* Message Item */
.message-item {
    background: white;
    border-left: 3px solid #1e3c72;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
    position: relative;
}

.message-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateX(2px);
}

/* New Message Animation */
.message-item.new-message {
    animation: slideIn 0.5s ease, highlight 1s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes highlight {
    0%, 100% {
        background: white;
    }
    50% {
        background: #e3f2fd;
    }
}

/* Message Tags */
.message-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 8px;
    max-width: 100%;
    overflow: hidden;
    padding-right: 130px; /* Reserve space for Show on Map button */
}

.tag {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.5px;
    word-break: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
}

.tag-app {
    background: #e0f2f1;
    color: #00695c;
    border: 1px solid #4db6ac;
    font-weight: 700;
}

.tag-timestamp {
    background: #e3f2fd;
    color: #1565c0;
    border: 1px solid #90caf9;
}

.tag-freq {
    background: #f3e5f5;
    color: #6a1b9a;
    border: 1px solid #ce93d8;
}

.tag-station {
    background: #fce4ec;
    color: #c2185b;
    border: 1px solid #f48fb1;
}

.tag-level {
    background: #f1f8e9;
    color: #558b2f;
    border: 1px solid #aed581;
}

.tag-tail {
    background: #e0f7fa;
    color: #00838f;
    border: 1px solid #4dd0e1;
}

.tag-flight {
    background: #ede7f6;
    color: #5e35b1;
    border: 1px solid #b39ddb;
}

.tag-assstat {
    background: #fff9c4;
    color: #f57f17;
    border: 1px solid #fff176;
}

.tag-ack {
    background: #e8eaf6;
    color: #3949ab;
    border: 1px solid #9fa8da;
}

.tag-mode {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #81c784;
}

.tag-label {
    background: #fff3e0;
    color: #ef6c00;
    border: 1px solid #ffb74d;
}

.tag-dl-system {
    background: #e3f2fd;
    color: #0d47a1;
    border: 1px solid #64b5f6;
}

.tag-dl-operation {
    background: #e1f5fe;
    color: #01579b;
    border: 1px solid #4fc3f7;
}

/* Message Content */
.message-content {
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 12px;
    color: #333;
    line-height: 1.5;
    background: #f8f9fa;
    padding: 7px;
    border-radius: 3px;
    width: 100%;
    max-width: 100%;
    overflow-wrap: break-word; /* Break long words to prevent overflow */
    word-wrap: break-word; /* Legacy support */
    word-break: break-word; /* Break words if needed */
    white-space: pre-wrap; /* Preserve line breaks and spaces, but wrap when needed */
    overflow-x: auto; /* Allow horizontal scroll only if absolutely necessary */
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

/* Decoded Message Content */
.message-decoded {
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 11px;
    color: #1565c0;
    line-height: 1.4;
    background: #e3f2fd;
    padding: 8px;
    margin-top: 6px;
    border-radius: 3px;
    border-left: 3px solid #1976d2;
    width: 100%;
    max-width: 100%;
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    white-space: pre-wrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

/* Remove leading/trailing whitespace from message content */
.message-content::before,
.message-content::after {
    content: '';
    display: inline;
}

/* Prevent flex overflow on long lines */
.datalink-container,
.messages-container,
.message-item,
.message-tags,
.message-content {
    min-width: 0; /* Allow flex items to shrink below content size */
}

/* Custom Scrollbar for Message Content - Only in WebKit browsers */
.message-content::-webkit-scrollbar {
    height: 6px;
}

.message-content::-webkit-scrollbar-track {
    background: transparent;
}

.message-content::-webkit-scrollbar-thumb {
    background: #1e3c72;
    border-radius: 3px;
}

.message-content::-webkit-scrollbar-thumb:hover {
    background: #2a5298;
}

/* Empty State */
.messages-container:empty::before {
    content: 'Waiting for messages...';
    display: flex;
    align-items: center;
    justify-content: center;
    height: 400px;
    color: #999;
    font-size: 18px;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .datalink-container {
        max-width: 100vw; /* Never exceed viewport width */
        overflow-x: hidden;
    }
    
    .datalink-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .datalink-header h2 {
        font-size: 20px;
    }
    
    .connection-status {
        width: 100%;
        justify-content: space-between;
    }
    
    .filter-row-compact {
        flex-direction: column;
    }
    
    .filter-item-compact {
        width: 100%;
        max-width: 100%;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-item {
        width: 100%;
        min-width: 100%;
    }
    
    .filter-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group,
    .pagination-group {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }
    
    .messages-container {
        max-height: 500px;
    }
    
    .message-tags {
        font-size: 11px;
    }
    
    .message-content {
        font-size: 12px;
        padding: 10px; /* More padding on mobile for better readability */
    }
}

@media (max-width: 480px) {
    .datalink-container {
        border-radius: 0;
        margin: 0;
        height: 100%;
    }
    
    .datalink-header {
        padding: 15px 20px;
    }
    
    .messages-container {
        padding: 10px;
        max-height: 400px;
    }
    
    .message-item {
        padding: 12px;
    }
    
    .message-content {
        font-size: 11px; /* Slightly smaller on very small screens */
    }
    
    .tag {
        font-size: 10px; /* Smaller tags on mobile */
        padding: 2px 6px;
    }
    
    .filter-item-compact label {
        font-size: 10px;
    }
    
    .filter-input-compact,
    .multiselect-btn {
        font-size: 11px;
    }
}
</style>

