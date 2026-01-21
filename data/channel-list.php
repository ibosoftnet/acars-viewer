<?php
/**
 * Channel (Frequency) List Configuration
 * 
 * This file contains the frequency-to-name mappings.
 * Format: 'frequency' => 'Subnetwork - Description'
 */

$CHANNELS = [
    '131.525' => 'VDL M0/A - EUR',
    '131.725' => 'VDL M0/A - EUR - SITA Aircom',
    '131.825' => 'VDL M0/A - EUR - ARINC GlobalLink',
];

// Make available as both PHP variable and JavaScript
$FREQUENCY_NAMES = $CHANNELS;

// Make available as JavaScript
echo "<script>\n";
echo "const FREQUENCY_NAMES = " . json_encode($CHANNELS, JSON_UNESCAPED_UNICODE) . ";\n";
echo "const CHANNELS = " . json_encode($CHANNELS, JSON_UNESCAPED_UNICODE) . ";\n";
echo "</script>\n";
?>
