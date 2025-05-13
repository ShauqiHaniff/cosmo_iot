<?php
$sensorId = '62b0cc8ecf1a3b001b2c12cb'; // Example: temperature sensor ID
$boxId = '6295e37b8301ea001c13362d';
$phenomenon = 'Temperatur'; // Match the sensor name exactly

$url = "https://api.opensensemap.org/boxes/$boxId/data/$sensorId?format=json&phenomenon=" . urlencode($phenomenon) . "&from=" . date("Y-m-d", strtotime("-1 day"));

$response = file_get_contents($url);
$data = json_decode($response, true);

$labels = [];
$values = [];

if (is_array($data)) {
    foreach ($data as $entry) {
        $labels[] = date("H:i", strtotime($entry['createdAt']));
        $values[] = $entry['value'];
    }
}

header('Content-Type: application/json');
echo json_encode(['labels' => $labels, 'values' => $values]);
?>
