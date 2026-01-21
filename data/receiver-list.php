<?php
/**
 * Receiver List Configuration
 * 
 * This file contains the list of available receivers.
 * Add or remove receivers as needed.
 */

$RECEIVERS = [
    'ANTALYA1',
    // Add more receivers here as needed
    // 'ANKARA1',
    // 'ISTANBUL1',
];

// Make available as JavaScript
echo "<script>\n";
echo "const RECEIVERS = " . json_encode($RECEIVERS, JSON_UNESCAPED_UNICODE) . ";\n";
echo "</script>\n";
?>
