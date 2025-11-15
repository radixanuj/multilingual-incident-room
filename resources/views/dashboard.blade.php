<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multilingual Incident Room - SITREP Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Ensure map stays below modals */
        .leaflet-container {
            z-index: 1 !important;
        }
        .leaflet-control-container {
            z-index: 2 !important;
        }
        /* Ensure modal is always on top */
        .modal-overlay {
            z-index: 9999 !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-red-600 text-white shadow-lg">
            <div class="container mx-auto px-4 py-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold">🚨 Multilingual Incident Room</h1>
                    <div class="text-sm">
                        <span id="current-time"></span>
                    </div>
                </div>
                <p class="text-red-100 mt-2">Real-time incident processing and SITREP generation</p>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            <!-- Test Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">🧪 Test Processing Pipeline</h2>
                <div class="flex gap-4">
                    <button id="test-example" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
                        Process Example Reports
                    </button>
                    <button id="upload-reports" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg">
                        Upload Custom Reports
                    </button>
                </div>
                <div id="processing-status" class="mt-4 hidden">
                    <div class="flex items-center text-blue-600">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing reports through multilingual pipeline...
                    </div>
                </div>
            </div>

            <!-- Current SITREP Display -->
            <div id="sitrep-container" class="hidden">
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">📊 Current SITREP</h2>
                        <div class="flex gap-2">
                            <button id="lang-en" class="lang-btn bg-blue-600 text-white px-3 py-1 rounded" data-lang="en">English</button>
                            <button id="lang-hi" class="lang-btn bg-gray-300 text-gray-700 px-3 py-1 rounded" data-lang="hi">हिंदी</button>
                            <button id="lang-bn" class="lang-btn bg-gray-300 text-gray-700 px-3 py-1 rounded" data-lang="bn">বাংলা</button>
                        </div>
                    </div>

                    <!-- SITREP Content -->
                    <div id="sitrep-content">
                        <div class="grid md:grid-cols-2 gap-8">
                            <!-- Main Info -->
                            <div>
                                <div class="mb-6">
                                    <h3 class="text-xl font-semibold mb-2" id="incident-title">Loading...</h3>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        <span id="incident-status" class="px-2 py-1 rounded-full bg-gray-100">Status</span>
                                        <span id="incident-time">Time</span>
                                        <span id="incident-location">Location</span>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <h4 class="font-semibold mb-2">Summary</h4>
                                    <p id="incident-summary" class="text-gray-700">Loading summary...</p>
                                </div>

                                <div class="mb-6">
                                    <h4 class="font-semibold mb-2">Details</h4>
                                    <ul id="incident-details" class="list-disc list-inside text-gray-700 space-y-1">
                                        <li>Loading details...</li>
                                    </ul>
                                </div>

                                <div class="mb-6">
                                    <h4 class="font-semibold mb-2">Source Information</h4>
                                    <div class="bg-gray-50 p-3 rounded">
                                        <p><strong>Reports processed:</strong> <span id="report-count">0</span></p>
                                        <p><strong>Sources:</strong> <span id="source-types">Unknown</span></p>
                                        <p><strong>Recommended Action:</strong> <span id="recommended-action">Unknown</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Map -->
                            <div>
                                <h4 class="font-semibold mb-2">Location</h4>
                                <div id="incident-map" class="h-80 bg-gray-200 rounded-lg relative z-10"></div>
                                <div class="mt-2 text-sm text-gray-600">
                                    <span id="coordinates">Coordinates: Loading...</span>
                                    <span class="ml-4">Confidence: <span id="location-confidence">0%</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent SITREPs -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">📋 Recent SITREPs</h2>
                <div id="recent-sitreps" class="space-y-3">
                    <div class="text-gray-500">No SITREPs generated yet.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999] modal-overlay">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 relative z-[10000]">
            <h3 class="text-lg font-semibold mb-4">Upload Custom Reports</h3>
            <textarea id="custom-reports" class="w-full h-40 border rounded p-3 text-sm" 
                placeholder='Paste JSON reports here:
[
  {
    "raw_text": "Your incident report text...",
    "location": "City Name or Address",
    "original_language": "en",
    "source_type": "text",
    "timestamp": "2025-11-15T07:12:00+05:30",
    "reporter_meta": {"source": "manual", "credibility": "medium"}
  }
]'></textarea>
            <div class="flex gap-2 mt-4">
                <button id="process-custom" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Process</button>
                <button id="close-modal" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let map = null;
        let currentSitrep = null;
        let currentLanguage = 'en';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateTime();
            setInterval(updateTime, 1000);
            loadRecentSitreps();
            setupEventListeners();
        });

        function updateTime() {
            document.getElementById('current-time').textContent = new Date().toLocaleString();
        }

        function setupEventListeners() {
            document.getElementById('test-example').addEventListener('click', processExampleReports);
            document.getElementById('upload-reports').addEventListener('click', showUploadModal);
            document.getElementById('close-modal').addEventListener('click', hideUploadModal);
            document.getElementById('process-custom').addEventListener('click', processCustomReports);

            // Language switching
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    switchLanguage(this.dataset.lang);
                });
            });
        }

        async function processExampleReports() {
            showProcessingStatus();
            try {
                const response = await fetch('/api/incident-room/test-example');
                const data = await response.json();
                
                if (response.ok) {
                    displaySitrep(data);
                    loadRecentSitreps();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            } finally {
                hideProcessingStatus();
            }
        }

        async function processCustomReports() {
            const reportsText = document.getElementById('custom-reports').value.trim();
            if (!reportsText) {
                alert('Please enter report data');
                return;
            }

            try {
                const reports = JSON.parse(reportsText);
                showProcessingStatus();
                hideUploadModal();

                const response = await fetch('/api/incident-room/process-reports', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ reports })
                });

                const data = await response.json();
                
                if (response.ok) {
                    displaySitrep(data);
                    loadRecentSitreps();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Invalid JSON or network error: ' + error.message);
            } finally {
                hideProcessingStatus();
            }
        }

        function displaySitrep(sitrep) {
            currentSitrep = sitrep;
            document.getElementById('sitrep-container').classList.remove('hidden');
            
            // Update status badge color
            const statusElement = document.getElementById('incident-status');
            const status = sitrep.status;
            statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusElement.className = `px-2 py-1 rounded-full text-white ${
                status === 'verified' ? 'bg-green-600' : 
                status === 'probable' ? 'bg-yellow-600' : 'bg-red-600'
            }`;

            // Basic info
            document.getElementById('incident-time').textContent = new Date(sitrep.time_window.approx_event_time).toLocaleString();
            document.getElementById('report-count').textContent = sitrep.sources.report_count;
            document.getElementById('source-types').textContent = sitrep.sources.top_3_sources_summary.join(', ');
            document.getElementById('recommended-action').textContent = sitrep.recommended_action.replace('_', ' ');
            
            // Coordinates
            if (sitrep.location.lat && sitrep.location.lng) {
                document.getElementById('coordinates').textContent = 
                    `Coordinates: ${sitrep.location.lat.toFixed(4)}, ${sitrep.location.lng.toFixed(4)}`;
                document.getElementById('location-confidence').textContent = 
                    `${(sitrep.location.confidence * 100).toFixed(0)}%`;
                initializeMap(sitrep.location);
            } else {
                document.getElementById('coordinates').textContent = 'Coordinates: Unknown';
                document.getElementById('location-confidence').textContent = '0%';
            }

            // Switch to current language
            switchLanguage(currentLanguage);
        }

        function switchLanguage(lang) {
            if (!currentSitrep) return;

            currentLanguage = lang;
            
            // Update language buttons
            document.querySelectorAll('.lang-btn').forEach(btn => {
                if (btn.dataset.lang === lang) {
                    btn.className = 'lang-btn bg-blue-600 text-white px-3 py-1 rounded';
                } else {
                    btn.className = 'lang-btn bg-gray-300 text-gray-700 px-3 py-1 rounded';
                }
            });

            // Update content
            document.getElementById('incident-title').textContent = currentSitrep.canonical_title;
            document.getElementById('incident-summary').textContent = currentSitrep.summary[lang] || 'Translation not available';
            document.getElementById('incident-location').textContent = currentSitrep.location.name;

            // Update details
            const detailsList = document.getElementById('incident-details');
            detailsList.innerHTML = '';
            const details = currentSitrep.details[`bullets_${lang}`] || [];
            details.forEach(detail => {
                const li = document.createElement('li');
                li.textContent = detail;
                detailsList.appendChild(li);
            });
        }

        function initializeMap(location) {
            if (map) {
                map.remove();
            }

            map = L.map('incident-map').setView([location.lat, location.lng], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            L.marker([location.lat, location.lng])
                .addTo(map)
                .bindPopup(`<strong>${location.name}</strong><br>Confidence: ${(location.confidence * 100).toFixed(0)}%`);
        }

        async function loadRecentSitreps() {
            try {
                const response = await fetch('/api/incident-room/sitreps');
                const data = await response.json();
                
                const container = document.getElementById('recent-sitreps');
                container.innerHTML = '';

                if (data.sitreps && data.sitreps.length > 0) {
                    data.sitreps.forEach(sitrep => {
                        const div = document.createElement('div');
                        div.className = 'flex justify-between items-center p-3 bg-gray-50 rounded cursor-pointer hover:bg-gray-100';
                        div.innerHTML = `
                            <div>
                                <div class="font-medium">${sitrep.title}</div>
                                <div class="text-sm text-gray-600">${sitrep.location} • ${new Date(sitrep.timestamp).toLocaleString()}</div>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full ${
                                sitrep.status === 'verified' ? 'bg-green-100 text-green-800' : 
                                sitrep.status === 'probable' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
                            }">${sitrep.status}</span>
                        `;
                        div.addEventListener('click', () => loadSitrep(sitrep.incident_id));
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div class="text-gray-500">No SITREPs generated yet.</div>';
                }
            } catch (error) {
                console.error('Error loading recent SITREPs:', error);
            }
        }

        async function loadSitrep(incidentId) {
            try {
                const response = await fetch(`/api/incident-room/sitreps/${incidentId}`);
                const data = await response.json();
                
                if (response.ok) {
                    displaySitrep(data);
                }
            } catch (error) {
                console.error('Error loading SITREP:', error);
            }
        }

        function showProcessingStatus() {
            document.getElementById('processing-status').classList.remove('hidden');
        }

        function hideProcessingStatus() {
            document.getElementById('processing-status').classList.add('hidden');
        }

        function showUploadModal() {
            document.getElementById('upload-modal').classList.remove('hidden');
            document.getElementById('upload-modal').classList.add('flex');
        }

        function hideUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
            document.getElementById('upload-modal').classList.remove('flex');
        }
    </script>
</body>
</html>