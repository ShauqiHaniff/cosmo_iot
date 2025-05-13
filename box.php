<?php
$boxId = isset($_GET['boxId']) ? preg_replace('/[^a-f0-9]/', '', $_GET['boxId']) : '6295e37b8301ea001c13362d';
?>

<!-- External Libraries -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    

<div class="container mx-auto p-6">
    <div id="countdown" class="mb-4 text-sm text-blue-600 font-medium">Refreshing in <span id="timer">15</span> seconds...</div>
    <div id="sensorContent">Loading data...</div>
    <div id="map" class="rounded-xl shadow-md mb-6" style="height: 400px;"></div>
</div>

<script>
const boxId = "<?php echo $boxId; ?>";
let charts = [];

function fetchData() {
    fetch("https://api.opensensemap.org/boxes/" + boxId)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('sensorContent');
            const name = data.name;
            const exposure = data.exposure;
            const model = data.model;
            const created = new Date(data.createdAt).toLocaleString();
            const lat = data.currentLocation?.coordinates[1];
            const lon = data.currentLocation?.coordinates[0];

            // Info card
            let html = `
            <div class="bg-white p-6 rounded-xl shadow-md mb-6">
                <h2 class="text-2xl font-bold mb-2">${name}</h2>
                <p><strong>Exposure:</strong> ${exposure}</p>
                <p><strong>Model:</strong> ${model}</p>
                <p><strong>Created at:</strong> ${created}</p>
                <p><strong>Latitude:</strong> ${lat}</p>
                <p><strong>Longitude:</strong> ${lon}</p>
            </div>`;

            // Cards
            html += '<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">';
            data.sensors.forEach(sensor => {
                const latest = sensor.lastMeasurement?.value ?? 'N/A';
                const time = sensor.lastMeasurement?.createdAt ? new Date(sensor.lastMeasurement.createdAt).toLocaleTimeString() : 'N/A';
                html += `
                    <div class="bg-white p-4 rounded-xl shadow-md">
                        <h3 class="text-lg font-semibold mb-1">${sensor.title}</h3>
                        <p class="text-gray-800 text-xl font-bold">${latest} <span class="text-sm text-gray-500">${sensor.unit}</span></p>
                        <p class="text-sm text-gray-500">Last updated: ${time}</p>
                    </div>`;
            });
            html += '</div>';

            // Sensor charts (2 per row)
            html += '<div class="grid md:grid-cols-2 gap-6">';
            charts.forEach(c => c.destroy());
            charts = [];

            data.sensors.forEach((sensor, i) => {
                const canvasId = `chartSensor${i}`;
                html += `
                <div class="bg-white p-4 rounded-xl shadow-md">
                    <h3 class="text-lg font-semibold mb-2">${sensor.title}</h3>
                    <canvas id="${canvasId}" style="height:200px;"></canvas>
                </div>`;

                const url = `https://api.opensensemap.org/boxes/${boxId}/data/${sensor._id}?format=json&from=${new Date(Date.now() - 86400000).toISOString()}`;
                fetch(url)
                    .then(r => r.json())
                    .then(measurements => {
                        const labels = measurements.map(m => new Date(m.createdAt).toLocaleTimeString());
                        const values = measurements.map(m => m.value);
                        const ctx = document.getElementById(canvasId).getContext('2d');
                        const chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: sensor.title,
                                    data: values,
                                    borderColor: 'rgba(59,130,246,1)',
                                    backgroundColor: 'rgba(59,130,246,0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: { title: { display: true, text: 'Time' }},
                                    y: { title: { display: true, text: sensor.unit }}
                                }
                            }
                        });
                        charts.push(chart);
                    });
            });
            html += '</div>';

            container.innerHTML = html;

            // Map
            if (lat && lon) {
                if (!window.leafletMap) {
                    window.leafletMap = L.map('map').setView([lat, lon], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(window.leafletMap);
                    window.marker = L.marker([lat, lon]).addTo(window.leafletMap).bindPopup(name).openPopup();
                } else {
                    window.leafletMap.setView([lat, lon], 16);
                    window.marker.setLatLng([lat, lon]).bindPopup(name).openPopup();
                }
            }
        });
}

function startCountdown() {
    let time = 15;
    const timerEl = document.getElementById('timer');
    timerEl.textContent = time;
    const interval = setInterval(() => {
        time--;
        timerEl.textContent = time;
        if (time <= 0) {
            clearInterval(interval);
            fetchData();
            startCountdown();
        }
    }, 1000);
}

fetchData();
startCountdown();
</script>
