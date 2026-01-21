<?php
// ===============================
// DATA LINK CONFIGURATION
// ===============================

// API and Service URLs
define('DATALINK_API_HOST', 'dlink-api.ibosoft.net.tr');
define('DATALINK_API_PORT', 2053);
define('DATALINK_API_BASE_URL', 'https://' . DATALINK_API_HOST . ':' . DATALINK_API_PORT);
define('DATALINK_DECODE_API_URL', DATALINK_API_BASE_URL . '/decode');
define('DATALINK_SSE_STREAM_URL', DATALINK_API_BASE_URL . '/stream');
define('DATALINK_HEALTH_URL', DATALINK_API_BASE_URL . '/health');

// Surveillance Map URL
// {REG} placeholder will be replaced with aircraft registration (without dashes)
// {DATE} placeholder will be replaced with date in Y-m-d format (UTC)
// {TIMESTAMP} placeholder will be replaced with Unix timestamp
define('SURVEILLANCE_MAP_BASE_URL', 'https://atc.ibosoft.net.tr/surveillance/');
define('SURVEILLANCE_MAP_URL_QUERY', '?reg={REG}&noIsolation');
define('SURVEILLANCE_MAP_BASE_URL_HISTORY', 'https://atc.ibosoft.net.tr/surveillance/');
define('SURVEILLANCE_MAP_URL_QUERY_HISTORY', '?reg={REG}&showTrace={DATE}&timestamp={TIMESTAMP}&zoom=7.5');

// Default Filter Settings
// Controls default selection behavior for label categories on page load
// true = ALL labels selected (including _d and SQ)
// false = ALL labels selected EXCEPT _d and SQ
define('DEFAULT_SELECT_ALL_LABELS_LIVE', true);     // Live mode: ALL labels selected (including _d and SQ)
define('DEFAULT_SELECT_ALL_LABELS_HISTORY', false);  // History mode: ALL labels EXCEPT _d and SQ

// Message Display Limits
define('MAX_DISPLAY_MESSAGES', 1000);        // Maximum messages to display in live mode
define('HISTORY_QUERY_LIMIT', 5000);         // Maximum messages to fetch per history query
define('HISTORY_MESSAGES_PER_PAGE', 500);    // Default messages per page in history mode

// Decoder Configuration
define('DECODER_MAX_RETRIES', 3);            // Maximum retries for decoder API calls
define('DECODER_TIMEOUT_MS', 10000);         // Decoder API timeout in milliseconds (10 seconds)
define('DECODER_RETRY_DELAY_MS', 1000);      // Delay between retries in milliseconds (1 second)

// Health Check Configuration
define('HEALTH_CHECK_INTERVAL_MS', 5000);    // TCP health check interval in milliseconds (5 seconds)


// Frontend to Backend Label Group Mapping
// Maps frontend filter names (lowercase) to $LABEL_DEFINITIONS keys (uppercase)
$FRONTEND_GROUP_MAP = [
    'datis' => 'DATIS',
    'dcl_pdc' => 'DCL_PDC',
    'ocl' => 'OCL',
    'fsm' => 'FSM',
    'twip' => 'TWIP',
    'afn' => 'AFN',
    'wpr' => 'WPR',
    'free_text_atc' => 'FREE_TEXT_ATC',
    'adsc' => 'ADSC',
    'cpdlc' => 'CPDLC',
    'emergency' => 'EMERGENCY',
    'time_request' => 'TIME_REQUEST',
    'atis_request' => 'ATIS_REQUEST',
    'weather_request' => 'WEATHER_REQUEST',
    'airline_designated' => 'AIRLINE_DESIGNATED',
    'aircrew_related' => 'AIRCREW_RELATED',
    'printer' => 'PRINTER',
    'command_response' => 'COMMAND_RESPONSE',
    'network_reports' => 'NETWORK_REPORTS',
    'lru_config' => 'LRU_CONFIG',
    'meteorological' => 'METEOROLOGICAL',
    'refueling' => 'REFUELING',
    'deicing' => 'DEICING',
    'media_advisory' => 'MEDIA_ADVISORY',
    'email' => 'EMAIL',
    'undelivered' => 'UNDELIVERED',
    'link_test' => 'LINK_TEST',
    'departure_arrival' => 'DEPARTURE_ARRIVAL',
    'eta_report' => 'ETA_REPORT',
    'clock_update' => 'CLOCK_UPDATE',
    'delay_message' => 'DELAY_MESSAGE',
    'out_fuel_iata' => 'OUT_FUEL_IATA',
    'off_iata' => 'OFF_IATA',
    'on_iata' => 'ON_IATA',
    'in_fuel_iata' => 'IN_FUEL_IATA',
    'out_fuel_dest_iata' => 'OUT_FUEL_DEST_IATA',
    'off_dest_iata' => 'OFF_DEST_IATA',
    'out_return_iata' => 'OUT_RETURN_IATA',
    'out_iata' => 'OUT_IATA',
    'landing_iata' => 'LANDING_IATA',
    'arrival_iata' => 'ARRIVAL_IATA',
    'arrival_info_iata' => 'ARRIVAL_INFO_IATA',
    'diversion_iata' => 'DIVERSION_IATA',
    'out_icao' => 'OUT_ICAO',
    'off_icao' => 'OFF_ICAO',
    'on_icao' => 'ON_ICAO',
    'in_icao' => 'IN_ICAO',
    'out_return_icao' => 'OUT_RETURN_ICAO',
    'h1' => 'AVIONICS_SUBSYSTEM',
    'ams_protected' => 'AMS_PROTECTED',
    'dsp_defined' => 'DSP_DEFINED',
    'user_defined' => 'USER_DEFINED',
    'vendor_defined' => 'VENDOR_DEFINED',
    'data_transceiver' => 'DATA_TRANSCEIVER',
    'dsp_autotune' => 'DSP_AUTOTUNE',
    'poa_aoa' => 'POA_AOA',
    'general_response' => 'GENERAL_RESPONSE',
    'general_response_polled' => 'GENERAL_RESPONSE_POLLED',
    'acars_frequency' => 'ACARS_FREQUENCY',
    'temp_suspension' => 'TEMP_SUSPENSION',
    'vdl_switch' => 'VDL_SWITCH',
    'printer_status' => 'PRINTER_STATUS',
    'transceiver_advisory' => 'TRANSCEIVER_ADVISORY',
    'loopback' => 'LOOPBACK',
    'voice_busy' => 'VOICE_BUSY',
    'unable_deliver' => 'UNABLE_DELIVER',
    'voice_data' => 'VOICE_DATA',
    'intercept' => 'INTERCEPT',
    'autotune_reject' => 'AUTOTUNE_REJECT',
    'sq' => 'SQUITTER'
];

// Label Definitions for Data Link Systems
$LABEL_DEFINITIONS = [
    // ===============================
    // ATS Applications
    // ===============================
    
    // D-ATIS (Data link – Automatic Terminal Information Service)
    'DATIS' => [
        'labels' => ['A9', 'B9'],
        'system' => 'ATS Applications',
        'operation' => 'D-ATIS',
        'display_name' => 'D-ATIS (Data link – automatic terminal information service) [A9,B9]'
    ],
    
    // DCL/PDC (Departure Clearance/Pre-Departure Clearance)
    'DCL_PDC' => [
        'labels' => ['A3', 'A8', 'AC', 'AD', 'B3', 'B4', 'B8', 'BC', 'BD'],
        'system' => 'ATS Applications',
        'operation' => 'DCL/PDC',
        'display_name' => 'DCL/PDC (Departure clearance/Pre-departure clearance) [A3,A8,AC,AD,B3,B4,B8,BC,BD]'
    ],
    
    // OCL (Oceanic Clearance)
    'OCL' => [
        'labels' => ['A1', 'B1', 'B2'],
        'system' => 'ATS Applications',
        'operation' => 'OCL',
        'display_name' => 'OCL (Oceanic clearance) [A1,B1,B2]'
    ],
    
    // FSM (Flight System Message)
    'FSM' => [
        'labels' => ['A4'],
        'system' => 'ATS Applications',
        'operation' => 'FSM',
        'display_name' => 'FSM (Flight system message) [A4]'
    ],
    
    // TWIP (Terminal Weather Information for Pilots)
    'TWIP' => [
        'labels' => ['AB', 'BB'],
        'system' => 'ATS Applications',
        'operation' => 'TWIP',
        'display_name' => 'TWIP (Terminal weather information for pilots) [AB,BB]'
    ],
    
    // AFN (ATS Facilities Notification)
    'AFN' => [
        'labels' => ['A0', 'B0'],
        'system' => 'ATS Applications',
        'operation' => 'AFN',
        'display_name' => 'AFN (ATS Facilities Notification) [A0,B0]'
    ],
    
    // WPR (Waypoint Position Report)
    'WPR' => [
        'labels' => ['B5'],
        'system' => 'ATS Applications',
        'operation' => 'WPR',
        'display_name' => 'WPR (Waypoint Position Report) [B5]'
    ],
    
    // Free Text to ATC
    'FREE_TEXT_ATC' => [
        'labels' => ['A7', 'B7'],
        'system' => 'ATS Applications',
        'operation' => 'Free Text to ATC',
        'display_name' => '"Free Text" to ATC [A7,B7]'
    ],
    
    // ===============================
    // FANS 1/A & FANS 1/A+
    // ===============================
    
    // ADS-C (Automatic Dependent Surveillance — Contract)
    'ADSC' => [
        'labels' => ['A6', 'B6'],
        'system' => 'FANS 1/A & FANS 1/A+',
        'operation' => 'ADS-C',
        'display_name' => 'ADS-C (Automatic dependent surveillance — contract) [A6,B6]'
    ],
    
    // CPDLC (Controller-Pilot Data Link Communications)
    'CPDLC' => [
        'labels' => ['AA', 'AF', 'BA', 'BE', 'BF'],
        'system' => 'FANS 1/A & FANS 1/A+',
        'operation' => 'CPDLC',
        'display_name' => 'CPDLC (Controller-pilot data link communications) [AA,AF,BA,BE,BF]'
    ],
    
    // ===============================
    // Service Related
    // ===============================
    
    // Aircrew Related Messages
    'AIRCREW_RELATED' => [
        'labels' => ['57', '5R', '5Y', '7A', '7B', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '8~'],
        'system' => 'Service Related',
        'operation' => 'Aircrew Related Messages',
        'display_name' => 'Aircrew Related Messages [57,5R,5Y,7A,7B,80-8~]'
    ],
    
    // Airline Designated Downlink
    'AIRLINE_DESIGNATED' => [
        'labels' => ['5Z'],
        'system' => 'Service Related',
        'operation' => 'Airline Designated Downlink',
        'display_name' => 'Airline Designated Downlink [5Z]'
    ],
    
    // ATIS Request
    'ATIS_REQUEST' => [
        'labels' => ['5D'],
        'system' => 'Service Related',
        'operation' => 'ATIS Request',
        'display_name' => 'ATIS Request [5D]'
    ],
    
    // Cockpit/Cabin Printer Messages
    'PRINTER' => [
        'labels' => ['C0', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9'],
        'system' => 'Service Related',
        'operation' => 'Cockpit/Cabin Printer Messages',
        'display_name' => 'Cockpit/Cabin Printer Messages [C0-C9]'
    ],
    
    // Command/Response Uplink/Downlink
    'COMMAND_RESPONSE' => [
        'labels' => ['RA'],
        'system' => 'Service Related',
        'operation' => 'Command/Response',
        'display_name' => 'Command/Response Uplink/Downlink [RA]'
    ],
    
    // De-Icing
    'DEICING' => [
        'labels' => ['DI'],
        'system' => 'Service Related',
        'operation' => 'De-Icing',
        'display_name' => 'De-Icing [DI]'
    ],
    
    // Emergency Situation Report (Aircraft Hijack)
    'EMERGENCY' => [
        'labels' => ['00'],
        'system' => 'Service Related',
        'operation' => 'Emergency Situation Report',
        'display_name' => 'Emergency Situation Report (Aircraft Hijack) [00]'
    ],
    
    // GMT/UTC Requests/Updates
    'TIME_REQUEST' => [
        'labels' => ['51', '52'],
        'system' => 'Service Related',
        'operation' => 'GMT/UTC Requests/Updates',
        'display_name' => 'GMT/UTC Requests/Updates [51,52]'
    ],
    
    // Internet E-Mail Messages
    'EMAIL' => [
        'labels' => ['E1', 'E2'],
        'system' => 'Service Related',
        'operation' => 'Internet E-Mail Messages',
        'display_name' => 'Internet E-Mail Messages [E1,E2]'
    ],
    
    // LRU Configuration Profile Report Request
    'LRU_CONFIG' => [
        'labels' => ['S3'],
        'system' => 'Service Related',
        'operation' => 'LRU Configuration Profile Report Request',
        'display_name' => 'LRU Configuration Profile Report Request [S3]'
    ],
    
    // Media Advisory
    'MEDIA_ADVISORY' => [
        'labels' => ['SA'],
        'system' => 'Service Related',
        'operation' => 'Media Advisory',
        'display_name' => 'Media Advisory [SA]'
    ],
    
    // Meteorological Messages
    'METEOROLOGICAL' => [
        'labels' => ['H2', 'H3', 'H4'],
        'system' => 'Service Related',
        'operation' => 'Meteorological Messages',
        'display_name' => 'Meteorological Messages [H2,H3,H4]'
    ],
    
    // Network Related Reports
    'NETWORK_REPORTS' => [
        'labels' => ['S1', 'S2'],
        'system' => 'Service Related',
        'operation' => 'Network Related Reports',
        'display_name' => 'Network Related Reports [S1,S2]'
    ],
    
    // Refueling Related Messages
    'REFUELING' => [
        'labels' => ['RE', 'RF'],
        'system' => 'Service Related',
        'operation' => 'Refueling Related Messages',
        'display_name' => 'Refueling Related Messages [RE,RF]'
    ],
    
    // Undelivered Uplink Report
    'UNDELIVERED' => [
        'labels' => ['HX'],
        'system' => 'Service Related',
        'operation' => 'Undelivered Uplink Report',
        'display_name' => 'Undelivered Uplink Report [HX]'
    ],
    
    // Weather Request
    'WEATHER_REQUEST' => [
        'labels' => ['5U'],
        'system' => 'Service Related',
        'operation' => 'Weather Request',
        'display_name' => 'Weather Request [5U]'
    ],
    
    // ===============================
    // Service Related - Various Reports [Qx]
    // ===============================
    
    // Link Test
    'LINK_TEST' => [
        'labels' => ['Q0'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Link Test',
        'display_name' => 'Link Test [Q0]'
    ],
    
    // Departure/Arrival Reports (IATA Airport Code)
    'DEPARTURE_ARRIVAL' => [
        'labels' => ['Q1'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Departure/Arrival Reports',
        'display_name' => 'Departure/Arrival Reports (IATA Airport Code) [Q1]'
    ],
    
    // ETA Report
    'ETA_REPORT' => [
        'labels' => ['Q2'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'ETA Report',
        'display_name' => 'ETA Report [Q2]'
    ],
    
    // Clock Update Advisory
    'CLOCK_UPDATE' => [
        'labels' => ['Q3'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Clock Update Advisory',
        'display_name' => 'Clock Update Advisory [Q3]'
    ],
    
    // Delay Message
    'DELAY_MESSAGE' => [
        'labels' => ['Q7'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Delay Message',
        'display_name' => 'Delay Message [Q7]'
    ],
    
    // Out/Fuel Report (IATA Airport Code)
    'OUT_FUEL_IATA' => [
        'labels' => ['QA'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Out/Fuel Report',
        'display_name' => 'Out/Fuel Report (IATA Airport Code) [QA]'
    ],
    
    // OFF Report (IATA Airport Code)
    'OFF_IATA' => [
        'labels' => ['QB'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OFF Report',
        'display_name' => 'OFF Report (IATA Airport Code) [QB]'
    ],
    
    // ON Report (IATA Airport Code)
    'ON_IATA' => [
        'labels' => ['QC'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'ON Report',
        'display_name' => 'ON Report (IATA Airport Code) [QC]'
    ],
    
    // IN/Fuel Report (IATA Airport Code)
    'IN_FUEL_IATA' => [
        'labels' => ['QD'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'IN/Fuel Report',
        'display_name' => 'IN/Fuel Report (IATA Airport Code) [QD]'
    ],
    
    // OUT/Fuel Destination Report (IATA Airport Code)
    'OUT_FUEL_DEST_IATA' => [
        'labels' => ['QE'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OUT/Fuel Destination Report',
        'display_name' => 'OUT/Fuel Destination Report (IATA Airport Code) [QE]'
    ],
    
    // OFF/Destination Report (IATA Airport Code)
    'OFF_DEST_IATA' => [
        'labels' => ['QF'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OFF/Destination Report',
        'display_name' => 'OFF/Destination Report (IATA Airport Code) [QF]'
    ],
    
    // OUT/Return IN Report (IATA Airport Code)
    'OUT_RETURN_IATA' => [
        'labels' => ['QG'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OUT/Return IN Report',
        'display_name' => 'OUT/Return IN Report (IATA Airport Code) [QG]'
    ],
    
    // OUT Report - (IATA Airport Code)
    'OUT_IATA' => [
        'labels' => ['QH'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OUT Report',
        'display_name' => 'OUT Report - (IATA Airport Code) [QH]'
    ],
    
    // Landing Report (IATA Airport Code)
    'LANDING_IATA' => [
        'labels' => ['QK'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Landing Report',
        'display_name' => 'Landing Report (IATA Airport Code) [QK]'
    ],
    
    // Arrival Report (IATA Airport Code)
    'ARRIVAL_IATA' => [
        'labels' => ['QL'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Arrival Report',
        'display_name' => 'Arrival Report (IATA Airport Code) [QL]'
    ],
    
    // Arrival Information Report (IATA Airport Code)
    'ARRIVAL_INFO_IATA' => [
        'labels' => ['QM'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Arrival Information Report',
        'display_name' => 'Arrival Information Report (IATA Airport Code) [QM]'
    ],
    
    // Diversion Report (IATA Airport Code)
    'DIVERSION_IATA' => [
        'labels' => ['QN'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'Diversion Report',
        'display_name' => 'Diversion Report (IATA Airport Code) [QN]'
    ],
    
    // OUT Report (ICAO Airport Code)
    'OUT_ICAO' => [
        'labels' => ['QP'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OUT Report',
        'display_name' => 'OUT Report (ICAO Airport Code) [QP]'
    ],
    
    // OFF Report (ICAO Airport Code)
    'OFF_ICAO' => [
        'labels' => ['QQ'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OFF Report',
        'display_name' => 'OFF Report (ICAO Airport Code) [QQ]'
    ],
    
    // ON Report (ICAO Airport Code)
    'ON_ICAO' => [
        'labels' => ['QR'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'ON Report',
        'display_name' => 'ON Report (ICAO Airport Code) [QR]'
    ],
    
    // IN Report (ICAO Airport Code)
    'IN_ICAO' => [
        'labels' => ['QS'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'IN Report',
        'display_name' => 'IN Report (ICAO Airport Code) [QS]'
    ],
    
    // OUT/Return IN Report (ICAO Airport Code)
    'OUT_RETURN_ICAO' => [
        'labels' => ['QT'],
        'system' => 'Service Related - Various Reports [Qx]',
        'operation' => 'OUT/Return IN Report',
        'display_name' => 'OUT/Return IN Report (ICAO Airport Code) [QT]'
    ],
    
    // ===============================
    // Other Categories (No Subcategories)
    // ===============================
    
    // Messages from Avionics Subsystem
    'AVIONICS_SUBSYSTEM' => [
        'labels' => ['H1'],
        'system' => 'Messages from Avionics Subsystem',
        'operation' => 'Avionics Subsystem Messages',
        'display_name' => 'Messages from Avionics Subsystem [H1]'
    ],
    
    // AMS-Protected Messages
    'AMS_PROTECTED' => [
        'labels' => ['P0', 'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9', 'PA', 'PB', 'PC'],
        'system' => 'AMS-Protected Messages',
        'operation' => 'AMS-Protected Messages',
        'display_name' => 'AMS-Protected Messages [P0-P9,PA-PC]'
    ],
    
    // DSP Defined Messages
    'DSP_DEFINED' => [
        'labels' => ['X1', 'X2', 'X3', 'X4', 'X5', 'X6', 'X7', 'X8', 'X9'],
        'system' => 'DSP Defined Messages',
        'operation' => 'DSP Defined Messages',
        'display_name' => 'DSP Defined Messages [X1-X9]'
    ],
    
    // User Defined Messages
    'USER_DEFINED' => [
        'labels' => ['10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
                     '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
                     '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
                     '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '4~'],
        'system' => 'User Defined Messages',
        'operation' => 'User Defined Messages',
        'display_name' => 'User Defined Messages [10-4~]'
    ],
    
    // Vendor Defined Messages
    'VENDOR_DEFINED' => [
        'labels' => ['VA', 'VB', 'VC', 'VD', 'VE', 'VF', 'VG', 'VH', 'VI', 'VJ', 'VK', 'VL', 'VM', 'VN', 'VO', 'VP', 'VQ', 'VR', 'VS', 'VT', 'VU', 'VV', 'VW', 'VX', 'VY', 'VZ',
                     'V0', 'V1', 'V2', 'V3', 'V4', 'V5', 'V6', 'V7', 'V8', 'V9'],
        'system' => 'Vendor Defined Messages',
        'operation' => 'Vendor Defined Messages',
        'display_name' => 'Vendor Defined Messages [VA-VZ,V0-V9]'
    ],
    
    // ===============================
    // System Control
    // ===============================
    
    // Data Transceiver Autotune
    'DATA_TRANSCEIVER' => [
        'labels' => [':;'],
        'system' => 'System Control',
        'operation' => 'Data Transceiver Autotune',
        'display_name' => 'Data Transceiver Autotune [:;]'
    ],
    
    // DSP Autotune Broadcast Uplink
    'DSP_AUTOTUNE' => [
        'labels' => ['::'],
        'system' => 'System Control',
        'operation' => 'DSP Autotune Broadcast Uplink',
        'display_name' => 'DSP Autotune Broadcast Uplink [::]'
    ],
    
    // POA to AOA Retune
    'POA_AOA' => [
        'labels' => [':}'],
        'system' => 'System Control',
        'operation' => 'POA to AOA Retune',
        'display_name' => 'POA to AOA Retune [:}]'
    ],
    
    // General Response
    'GENERAL_RESPONSE' => [
        'labels' => ['_d'],
        'system' => 'System Control',
        'operation' => 'General Response',
        'display_name' => 'General Response [_d]'
    ],
    
    // General Response, Polled Mode
    'GENERAL_RESPONSE_POLLED' => [
        'labels' => ['_j'],
        'system' => 'System Control',
        'operation' => 'General Response, Polled Mode',
        'display_name' => 'General Response, Polled Mode [_j]'
    ],
    
    // ACARS Frequency Uplink or Voice Go-Ahead (UP) / Voice Contact Request (DOWN)
    'ACARS_FREQUENCY' => [
        'labels' => ['54'],
        'system' => 'System Control',
        'operation' => 'ACARS Frequency',
        'display_name' => 'ACARS Frequency Uplink or Voice Go-Ahead (UP) / Voice Contact Request (DOWN) [54]'
    ],
    
    // Temporary Suspension
    'TEMP_SUSPENSION' => [
        'labels' => ['5P'],
        'system' => 'System Control',
        'operation' => 'Temporary Suspension',
        'display_name' => 'Temporary Suspension [5P]'
    ],
    
    // VDL Switch Advisory
    'VDL_SWITCH' => [
        'labels' => ['5V'],
        'system' => 'System Control',
        'operation' => 'VDL Switch Advisory',
        'display_name' => 'VDL Switch Advisory [5V]'
    ],
    
    // Cockpit Printer Status
    'PRINTER_STATUS' => [
        'labels' => ['CA', 'CB', 'CC', 'CD', 'CE', 'CF'],
        'system' => 'System Control',
        'operation' => 'Cockpit Printer Status',
        'display_name' => 'Cockpit Printer Status [CA-CF]'
    ],
    
    // Dedicated Transceiver Advisory
    'TRANSCEIVER_ADVISORY' => [
        'labels' => ['F3'],
        'system' => 'System Control',
        'operation' => 'Dedicated Transceiver Advisory',
        'display_name' => 'Dedicated Transceiver Advisory [F3]'
    ],
    
    // Loopback Response
    'LOOPBACK' => [
        'labels' => ['KB'],
        'system' => 'System Control',
        'operation' => 'Loopback Response',
        'display_name' => 'Loopback Response [KB]'
    ],
    
    // Voice Circuit Busy
    'VOICE_BUSY' => [
        'labels' => ['Q4'],
        'system' => 'System Control',
        'operation' => 'Voice Circuit Busy',
        'display_name' => 'Voice Circuit Busy [Q4]'
    ],
    
    // Unable to Deliver Uplinked Message
    'UNABLE_DELIVER' => [
        'labels' => ['Q5'],
        'system' => 'System Control',
        'operation' => 'Unable to Deliver Uplinked Message',
        'display_name' => 'Unable to Deliver Uplinked Message [Q5]'
    ],
    
    // Voice to Data Channel Changeover Advisory
    'VOICE_DATA' => [
        'labels' => ['Q6'],
        'system' => 'System Control',
        'operation' => 'Voice to Data Channel Changeover Advisory',
        'display_name' => 'Voice to Data Channel Changeover Advisory [Q6]'
    ],
    
    // Intercept/Unable to Process
    'INTERCEPT' => [
        'labels' => ['QX'],
        'system' => 'System Control',
        'operation' => 'Intercept/Unable to Process',
        'display_name' => 'Intercept/Unable to Process [QX]'
    ],
    
    // Autotune Reject
    'AUTOTUNE_REJECT' => [
        'labels' => ['QV'],
        'system' => 'System Control',
        'operation' => 'Autotune Reject',
        'display_name' => 'Autotune Reject [QV]'
    ],
    
    // Uplink Squitter
    'SQUITTER' => [
        'labels' => ['SQ'],
        'system' => 'System Control',
        'operation' => 'Uplink Squitter',
        'display_name' => 'Uplink Squitter [SQ]'
    ]
];

// Get all labels from a specific group
function getLabelsForGroup($groupKey) {
    global $LABEL_DEFINITIONS;
    return isset($LABEL_DEFINITIONS[$groupKey]) ? $LABEL_DEFINITIONS[$groupKey]['labels'] : [];
}

// Get all labels as a flat array (for convenience in JavaScript)
function getAllDefinedLabels() {
    global $LABEL_DEFINITIONS;
    $allLabels = [];
    foreach ($LABEL_DEFINITIONS as $group) {
        $allLabels = array_merge($allLabels, $group['labels']);
    }
    return $allLabels;
}

// Export configuration to JavaScript
function exportConfigToJS() {
    global $LABEL_DEFINITIONS;
    
    $jsConfig = [
        'API_HOST' => DATALINK_API_HOST,
        'API_PORT' => DATALINK_API_PORT,
        'DECODE_API_URL' => DATALINK_DECODE_API_URL,
        'SSE_STREAM_URL' => DATALINK_SSE_STREAM_URL,
        'HEALTH_URL' => DATALINK_HEALTH_URL,
        'SURVEILLANCE_MAP_BASE_URL' => SURVEILLANCE_MAP_BASE_URL,
        'SURVEILLANCE_MAP_BASE_URL_HISTORY' => SURVEILLANCE_MAP_BASE_URL_HISTORY,
        'SURVEILLANCE_MAP_URL_QUERY' => SURVEILLANCE_MAP_URL_QUERY,
        'SURVEILLANCE_MAP_URL_QUERY_HISTORY' => SURVEILLANCE_MAP_URL_QUERY_HISTORY,
        'MAX_DISPLAY_MESSAGES' => MAX_DISPLAY_MESSAGES,
        'DECODER_MAX_RETRIES' => DECODER_MAX_RETRIES,
        'DECODER_TIMEOUT_MS' => DECODER_TIMEOUT_MS,
        'DECODER_RETRY_DELAY_MS' => DECODER_RETRY_DELAY_MS,
        'HEALTH_CHECK_INTERVAL_MS' => HEALTH_CHECK_INTERVAL_MS,
        'DEFAULT_SELECT_ALL_LABELS_LIVE' => DEFAULT_SELECT_ALL_LABELS_LIVE,
        'DEFAULT_SELECT_ALL_LABELS_HISTORY' => DEFAULT_SELECT_ALL_LABELS_HISTORY,
        'LABEL_DEFINITIONS' => $LABEL_DEFINITIONS
    ];
    
    return $jsConfig;
}

// Build label mapping for PHP (for getDataLinkTypesPHP function)
function buildLabelMapping() {
    global $LABEL_DEFINITIONS;
    
    $mapping = [];
    
    // Add all grouped labels
    foreach ($LABEL_DEFINITIONS as $groupKey => $groupData) {
        foreach ($groupData['labels'] as $label) {
            $mapping[$label] = [
                'system' => $groupData['system'],
                'operation' => $groupData['operation']
            ];
        }
    }
    
    return $mapping;
}
