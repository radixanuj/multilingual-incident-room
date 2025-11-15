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
            <!-- Report Submission Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">📝 Submit Incident Report</h2>
                <div class="flex gap-4 mb-6">
                    <button id="test-example" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
                        Process Example Reports
                    </button>
                    <button id="show-form" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg">
                        Fill Report Form
                    </button>
                    <button id="upload-json" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-6 rounded-lg">
                        Upload JSON (Advanced)
                    </button>
                </div>

                <!-- Report Form -->
                <div id="report-form" class="hidden">
                    <form id="incident-form" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div>
                                    <label for="incident-text" class="block text-sm font-medium text-gray-700 mb-2">
                                        Incident Description <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="incident-text" name="raw_text" rows="4" required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Describe what happened in detail..."></textarea>
                                </div>

                                <div>
                                    <label for="incident-location" class="block text-sm font-medium text-gray-700 mb-2">
                                        Location <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="incident-location" name="location" required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., Karol Bagh, Delhi or street address">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="original-language" class="block text-sm font-medium text-gray-700 mb-2">
                                            Language
                                        </label>
                                        <select id="original-language" name="original_language"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="auto">Auto-detect</option>
                                            <option value="en">English</option>
                                            <option value="hi">Hindi (हिंदी)</option>
                                            <option value="bn">Bengali (বাংলা)</option>
                                            <option value="ur">Urdu (اردو)</option>
                                            <option value="ta">Tamil (தமிழ்)</option>
                                            <option value="te">Telugu (తెలుగు)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="source-type" class="block text-sm font-medium text-gray-700 mb-2">
                                            Source Type
                                        </label>
                                        <select id="source-type" name="source_type"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="text">Text Report</option>
                                            <option value="voice-transcript">Voice Transcript</option>
                                            <option value="social_media">Social Media</option>
                                            <option value="field_call">Field Call</option>
                                            <option value="citizen_sms">Citizen SMS</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="source-credibility" class="block text-sm font-medium text-gray-700 mb-2">
                                            Source Credibility
                                        </label>
                                        <select id="source-credibility" name="source_credibility"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="unknown">Unknown</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="verified">Verified</option>
                                            <option value="official">Official</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="incident-time" class="block text-sm font-medium text-gray-700 mb-2">
                                            When did this happen?
                                        </label>
                                        <input type="datetime-local" id="incident-time" name="timestamp"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <div>
                                    <label for="image-upload-input" class="block text-sm font-medium text-gray-700 mb-2">
                                        Upload Images (Optional)
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                        <input type="file" id="image-upload-input" multiple accept="image/*" class="hidden">
                                        <div id="image-drop-zone" class="cursor-pointer">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">
                                                <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                                        </div>
                                    </div>
                                    <div id="image-previews" class="mt-3 grid grid-cols-3 gap-3"></div>
                                </div>

                                <div>
                                    <label for="video-upload-input" class="block text-sm font-medium text-gray-700 mb-2">
                                        Upload Videos (Optional)
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                        <input type="file" id="video-upload-input" multiple accept="video/*" class="hidden">
                                        <div id="video-drop-zone" class="cursor-pointer">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">
                                                <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500">MP4, MOV, AVI up to 50MB each</p>
                                        </div>
                                    </div>
                                    <div id="video-previews" class="mt-3 space-y-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 pt-6 border-t">
                            <button type="button" id="cancel-form" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>

                <div id="processing-status" class="mt-4 hidden">
                    <div class="flex items-center text-blue-600">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing report through multilingual pipeline...
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
                                        <span id="incident-event-time">Time</span>
                                        <span id="incident-display-location">Location</span>
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

                                <!-- Media Attachments -->
                                <div id="media-section" class="mb-6 hidden">
                                    <h4 class="font-semibold mb-2">📎 Media Attachments</h4>
                                    <div class="space-y-3">
                                        <div id="attached-images" class="hidden">
                                            <h5 class="font-medium text-sm text-gray-700 mb-2">📷 Images</h5>
                                            <div id="image-gallery" class="grid grid-cols-2 gap-3"></div>
                                        </div>
                                        <div id="attached-videos" class="hidden">
                                            <h5 class="font-medium text-sm text-gray-700 mb-2">🎥 Videos</h5>
                                            <div id="video-gallery" class="space-y-2"></div>
                                        </div>
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
            document.getElementById('upload-json').addEventListener('click', showUploadModal);
            document.getElementById('close-modal').addEventListener('click', hideUploadModal);
            document.getElementById('process-custom').addEventListener('click', processCustomReports);
            document.getElementById('show-form').addEventListener('click', showReportForm);
            document.getElementById('cancel-form').addEventListener('click', hideReportForm);
            document.getElementById('incident-form').addEventListener('submit', submitIncidentForm);

            // Language switching
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    switchLanguage(this.dataset.lang);
                });
            });

            // Set default timestamp to current time
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('incident-time').value = now.toISOString().slice(0, 16);

            // File upload handlers
            setupFileUploads();
        }

        function setupFileUploads() {
            const imageInput = document.getElementById('image-upload-input');
            const videoInput = document.getElementById('video-upload-input');
            const imageDropZone = document.getElementById('image-drop-zone');
            const videoDropZone = document.getElementById('video-drop-zone');

            // Image upload
            imageInput.addEventListener('change', handleImageSelection);
            imageDropZone.addEventListener('click', () => imageInput.click());
            imageDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                imageDropZone.classList.add('border-blue-400', 'bg-blue-50');
            });
            imageDropZone.addEventListener('dragleave', () => {
                imageDropZone.classList.remove('border-blue-400', 'bg-blue-50');
            });
            imageDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                imageDropZone.classList.remove('border-blue-400', 'bg-blue-50');
                const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'));
                handleImageFiles(files);
            });

            // Video upload
            videoInput.addEventListener('change', handleVideoSelection);
            videoDropZone.addEventListener('click', () => videoInput.click());
            videoDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                videoDropZone.classList.add('border-blue-400', 'bg-blue-50');
            });
            videoDropZone.addEventListener('dragleave', () => {
                videoDropZone.classList.remove('border-blue-400', 'bg-blue-50');
            });
            videoDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                videoDropZone.classList.remove('border-blue-400', 'bg-blue-50');
                const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('video/'));
                handleVideoFiles(files);
            });
        }

        let uploadedImages = [];
        let uploadedVideos = [];

        function handleImageSelection(e) {
            const files = Array.from(e.target.files);
            handleImageFiles(files);
        }

        function handleVideoSelection(e) {
            const files = Array.from(e.target.files);
            handleVideoFiles(files);
        }

        function handleImageFiles(files) {
            files.forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    alert(`Image ${file.name} is too large. Please select files under 10MB.`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    uploadedImages.push({
                        file: file,
                        data: e.target.result,
                        name: file.name
                    });
                    displayImagePreview(e.target.result, file.name);
                };
                reader.readAsDataURL(file);
            });
        }

        function handleVideoFiles(files) {
            files.forEach(file => {
                if (file.size > 50 * 1024 * 1024) {
                    alert(`Video ${file.name} is too large. Please select files under 50MB.`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    uploadedVideos.push({
                        file: file,
                        data: e.target.result,
                        name: file.name
                    });
                    displayVideoPreview(e.target.result, file.name);
                };
                reader.readAsDataURL(file);
            });
        }

        function displayImagePreview(src, name) {
            const preview = document.createElement('div');
            preview.className = 'relative group';
            preview.innerHTML = `
                <img src="${src}" alt="${name}" class="w-full h-24 object-cover rounded-lg border">
                <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity" onclick="removeImagePreview(this, '${name}')">
                    ×
                </button>
                <p class="text-xs text-gray-500 mt-1 truncate">${name}</p>
            `;
            document.getElementById('image-previews').appendChild(preview);
        }

        function displayVideoPreview(src, name) {
            const preview = document.createElement('div');
            preview.className = 'relative group flex items-center p-3 bg-gray-50 rounded-lg border';
            preview.innerHTML = `
                <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">${name}</p>
                    <p class="text-xs text-gray-500">${(uploadedVideos.find(v => v.name === name)?.file.size / 1024 / 1024).toFixed(1)}MB</p>
                </div>
                <button type="button" class="ml-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity" onclick="removeVideoPreview(this, '${name}')">
                    ×
                </button>
            `;
            document.getElementById('video-previews').appendChild(preview);
        }

        function removeImagePreview(button, name) {
            uploadedImages = uploadedImages.filter(img => img.name !== name);
            button.parentElement.remove();
        }

        function removeVideoPreview(button, name) {
            uploadedVideos = uploadedVideos.filter(vid => vid.name !== name);
            button.parentElement.remove();
        }

        function showReportForm() {
            document.getElementById('report-form').classList.remove('hidden');
            document.getElementById('show-form').classList.add('hidden');
        }

        function hideReportForm() {
            document.getElementById('report-form').classList.add('hidden');
            document.getElementById('show-form').classList.remove('hidden');
            // Clear form
            document.getElementById('incident-form').reset();
            uploadedImages = [];
            uploadedVideos = [];
            document.getElementById('image-previews').innerHTML = '';
            document.getElementById('video-previews').innerHTML = '';
            // Reset timestamp
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('incident-time').value = now.toISOString().slice(0, 16);
        }

        async function submitIncidentForm(e) {
            e.preventDefault();
            
            const formElement = e.target;
            const formData = new FormData(formElement);
            
            // Add uploaded images to form data
            uploadedImages.forEach((image, index) => {
                formData.append(`images[${index}]`, image.file);
            });
            
            // Add uploaded videos to form data
            uploadedVideos.forEach((video, index) => {
                formData.append(`videos[${index}]`, video.file);
            });

            showProcessingStatus();
            hideReportForm();

            try {
                const response = await fetch('/api/incident-room/submit-form', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();
                
                if (response.ok) {
                    displaySitrep(data);
                    loadRecentSitreps();
                } else {
                    showReportForm(); // Show form again on error
                    showErrorMessage(data);
                }
            } catch (error) {
                showReportForm(); // Show form again on network error
                alert('Network error: ' + error.message);
            } finally {
                hideProcessingStatus();
            }
        }

        function showErrorMessage(data) {
            let errorMessage = 'Error: ';
            
            if (data.details) {
                // Show specific validation errors
                const errors = [];
                for (const field in data.details) {
                    const fieldErrors = data.details[field];
                    if (Array.isArray(fieldErrors)) {
                        errors.push(`${field}: ${fieldErrors.join(', ')}`);
                    }
                }
                
                if (errors.length > 0) {
                    errorMessage += '\n' + errors.join('\n');
                } else {
                    errorMessage += data.error || 'Validation failed';
                }
            } else {
                errorMessage += data.error || 'Unknown error';
            }
            
            alert(errorMessage);
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
                    showErrorMessage(data);
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
                    showErrorMessage(data);
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
            document.getElementById('incident-event-time').textContent = new Date(sitrep.time_window.approx_event_time).toLocaleString();
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

            // Display media attachments if available
            displayMediaAttachments(sitrep);
        }

        function displayMediaAttachments(sitrep) {
            // Check if any reports have media attachments
            let hasMedia = false;
            let allImages = [];
            let allVideos = [];

            // Extract media from all reports in the SITREP
            if (sitrep.sources && sitrep.sources.reports) {
                sitrep.sources.reports.forEach(report => {
                    if (report.media_attachments) {
                        if (report.media_attachments.images) {
                            allImages = allImages.concat(report.media_attachments.images);
                        }
                        if (report.media_attachments.videos) {
                            allVideos = allVideos.concat(report.media_attachments.videos);
                        }
                        hasMedia = true;
                    }
                });
            }

            const mediaSection = document.getElementById('media-section');
            const attachedImages = document.getElementById('attached-images');
            const attachedVideos = document.getElementById('attached-videos');
            const imageGallery = document.getElementById('image-gallery');
            const videoGallery = document.getElementById('video-gallery');

            if (hasMedia) {
                mediaSection.classList.remove('hidden');

                // Display images
                if (allImages.length > 0) {
                    attachedImages.classList.remove('hidden');
                    imageGallery.innerHTML = '';
                    allImages.forEach(image => {
                        const imageDiv = document.createElement('div');
                        imageDiv.className = 'relative group';
                        imageDiv.innerHTML = `
                            <img src="${image.url}" alt="${image.original_name}" 
                                 class="w-full h-32 object-cover rounded-lg border cursor-pointer hover:opacity-80 transition-opacity"
                                 onclick="showImageModal('${image.url}', '${image.original_name}')">
                            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-2 rounded-b-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                <p class="truncate">${image.original_name}</p>
                                <p>${(image.size / 1024 / 1024).toFixed(1)}MB</p>
                            </div>
                        `;
                        imageGallery.appendChild(imageDiv);
                    });
                } else {
                    attachedImages.classList.add('hidden');
                }

                // Display videos
                if (allVideos.length > 0) {
                    attachedVideos.classList.remove('hidden');
                    videoGallery.innerHTML = '';
                    allVideos.forEach(video => {
                        const videoDiv = document.createElement('div');
                        videoDiv.className = 'flex items-center p-3 bg-gray-50 rounded-lg border hover:bg-gray-100 cursor-pointer transition-colors';
                        videoDiv.innerHTML = `
                            <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v3a3 3 0 003 3z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 truncate">${video.original_name}</p>
                                <p class="text-sm text-gray-500">${(video.size / 1024 / 1024).toFixed(1)}MB • ${video.mime_type}</p>
                            </div>
                            <button onclick="window.open('${video.url}', '_blank')"
                                    class="ml-2 bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition-colors">
                                View
                            </button>
                        `;
                        videoGallery.appendChild(videoDiv);
                    });
                } else {
                    attachedVideos.classList.add('hidden');
                }
            } else {
                mediaSection.classList.add('hidden');
            }
        }

        function showImageModal(src, name) {
            // Create image modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999] modal-overlay';
            modal.innerHTML = `
                <div class="relative max-w-4xl max-h-full m-4">
                    <img src="${src}" alt="${name}" class="max-w-full max-h-full object-contain rounded-lg">
                    <button onclick="this.parentElement.parentElement.remove()"
                            class="absolute top-4 right-4 bg-black bg-opacity-50 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75 transition-all">
                        ×
                    </button>
                    <div class="absolute bottom-4 left-4 right-4 bg-black bg-opacity-50 text-white p-3 rounded">
                        <p class="font-medium">${name}</p>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
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
            document.getElementById('incident-display-location').textContent = currentSitrep.location.name;

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
                console.log('Loading recent SITREPs...');
                const response = await fetch('/api/incident-room/sitreps');
                const data = await response.json();
                
                console.log('SITREPs response:', data);
                
                const container = document.getElementById('recent-sitreps');
                container.innerHTML = '';

                if (response.ok) {
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
                } else {
                    container.innerHTML = `<div class="text-red-500">Error loading SITREPs: ${data.error || 'Unknown error'}</div>`;
                    console.error('Error response:', data);
                }
            } catch (error) {
                console.error('Error loading recent SITREPs:', error);
                const container = document.getElementById('recent-sitreps');
                container.innerHTML = `<div class="text-red-500">Network error: ${error.message}</div>`;
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