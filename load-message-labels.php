<?php
// Load message labels from CSV (label-list.csv)
$labelDescriptions = [];
$csvFile = __DIR__ . '/acars-decoding-library/label-list.csv';

if (file_exists($csvFile)) {
    $file = fopen($csvFile, 'r');
    $header = fgetcsv($file, 0, ',', '\\'); // Skip header row

    // First pass: collect all labels with their directions and decodability
    $labelData = [];
    while (($row = fgetcsv($file, 0, ',', '\\')) !== false) {
        if (count($row) >= 5) {
            $direction = strtoupper(trim($row[0])); // up/dn
            $code = trim($row[1]);
            $decodability = trim($row[2]); // decodable/non-decodable/reserved/not-used
            $name = trim($row[4]); // name column

            if (!isset($labelData[$code])) {
                $labelData[$code] = [
                    'directions' => [],
                    'decodable_directions' => []
                ];
            }

            $labelData[$code]['directions'][$direction] = $name;

            // Track decodable directions
            if ($decodability === 'decodable') {
                $labelData[$code]['decodable_directions'][] = $direction;
            }
        }
    }
    fclose($file);

    // Second pass: format the descriptions
    foreach ($labelData as $code => $data) {
        $directions = $data['directions'];
        $hasUp = isset($directions['UP']);
        $hasDn = isset($directions['DN']);

        if ($hasUp && $hasDn) {
            // Both directions exist
            $explanation = $directions['UP'] . ' - UP / ' . $directions['DN'] . ' - DOWN';
            $direction = '';
        } elseif ($hasUp) {
            // Only UP
            $explanation = $directions['UP'] . ' - UP';
            $direction = '';
        } else {
            // Only DN
            $explanation = $directions['DN'] . ' - DOWN';
            $direction = '';
        }

        $labelDescriptions[$code] = [
            'explanation' => $explanation,
            'direction' => $direction,
            'decodable_directions' => $data['decodable_directions'] // e.g., ['UP'], ['DN'], or ['UP', 'DN']
        ];
    }
}

// Make available as both variable names for compatibility
$MESSAGE_LABEL_DESCRIPTIONS = $labelDescriptions;
