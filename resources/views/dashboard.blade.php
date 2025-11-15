<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Command Center - SITREP Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0a0a;
            color: #ffffff;
        }
        
        .font-display {
            font-family: 'Space Grotesk', sans-serif;
        }
        
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        
        /* Dark gradient backgrounds */
        .hero-gradient {
            background: radial-gradient(ellipse at top, rgba(239, 68, 68, 0.15) 0%, transparent 50%),
                        radial-gradient(ellipse at bottom right, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                        #0a0a0a;
        }
        
        .gradient-dark {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);
            backdrop-filter: blur(20px);
        }
        
        /* Glassmorphism cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(239, 68, 68, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        /* Neon glow effects */
        .glow-red {
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.4),
                        0 0 40px rgba(239, 68, 68, 0.2);
        }
        
        .glow-blue {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3),
                        0 0 40px rgba(59, 130, 246, 0.15);
        }
        
        .glow-green {
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.3),
                        0 0 40px rgba(34, 197, 94, 0.15);
        }
        
        .glow-purple {
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.3),
                        0 0 40px rgba(168, 85, 247, 0.15);
        }
        
        .glow-orange {
            box-shadow: 0 0 30px rgba(251, 146, 60, 0.5),
                        0 0 60px rgba(251, 146, 60, 0.25);
        }
        
        /* Button animations */
        .btn-primary {
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px rgba(239, 68, 68, 0.5),
                        0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        /* Input fields dark theme */
        .input-dark {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transition: all 0.3s ease;
        }
        
        .input-dark:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(239, 68, 68, 0.5);
            outline: none;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }
        
        .input-dark::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ef4444 0%, #fb923c 100%);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #dc2626 0%, #f97316 100%);
        }
        
        /* Map container dark theme */
        .leaflet-container {
            z-index: 1 !important;
            background: #1a1a1a !important;
            border-radius: 12px;
        }
        
        .leaflet-control-container {
            z-index: 2 !important;
        }
        
        .modal-overlay {
            z-index: 9999 !important;
        }
        
        /* Animations */
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .status-verified {
            animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Status badges with neon accents */
        .status-badge-verified {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.2);
        }
        
        .status-badge-probable {
            background: rgba(251, 146, 60, 0.1);
            border: 1px solid rgba(251, 146, 60, 0.3);
            color: #fb923c;
            box-shadow: 0 0 15px rgba(251, 146, 60, 0.2);
        }
        
        .status-badge-unverified {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }
        
        /* Grid background */
        .grid-background {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #ffffff 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Upload zones */
        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.3s ease;
        }
        
        .upload-zone:hover {
            border-color: rgba(239, 68, 68, 0.4);
            background: rgba(239, 68, 68, 0.05);
        }
        
        /* Select dropdowns dark theme */
        select.input-dark option {
            background: #1a1a1a;
            color: #ffffff;
        }
        
        /* Link styles */
        a {
            color: #60a5fa;
            transition: color 0.2s ease;
        }
        
        a:hover {
            color: #93c5fd;
        }
    </style>
</head>
<body class="hero-gradient grid-background">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="gradient-dark border-b border-white/10 sticky top-0 z-50">
            <div class="container mx-auto px-6 py-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="bg-red-600/20 p-3 rounded-lg border border-red-500/30 glow-red">
                            <i class="fas fa-shield-halved text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold font-display tracking-tight">Incident Command Center</h1>
                            <p class="text-gray-400 text-sm mt-1">Multilingual Real-time SITREP Generation</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="text-right">
                            <div class="text-xs text-gray-500 uppercase tracking-wider font-medium">System Time</div>
                            <div id="current-time" class="text-sm font-mono text-gray-300 mt-1"></div>
                        </div>
                        <div class="h-8 w-px bg-white/20"></div>
                        <div class="flex items-center space-x-2">
                            <div class="h-2 w-2 bg-green-400 rounded-full pulse-slow"></div>
                            <span class="text-xs text-gray-300 font-medium">SYSTEM ONLINE</span>
                        </div>
                        <a href="/" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fas fa-home"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container mx-auto px-6 py-8">
            <!-- Report Submission Section -->
            <div class="glass-card p-8 mb-8 fade-in">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-600/20 p-2.5 rounded-lg border border-blue-500/30 glow-blue">
                            <i class="fas fa-file-circle-plus text-blue-400 text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold font-display">Submit Incident Report</h2>
                    </div>
                </div>
                
                <div class="flex gap-3 mb-6 flex-wrap">
                    <button id="test-example" class="btn-primary flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium py-3 px-6 rounded-lg glow-blue">
                        <i class="fas fa-flask text-sm"></i>
                        <span>Process Example Reports</span>
                    </button>
                    <button id="show-form" class="btn-primary flex items-center space-x-2 bg-gradient-to-r from-green-600 to-green-700 text-white font-medium py-3 px-6 rounded-lg glow-green">
                        <i class="fas fa-pen-to-square text-sm"></i>
                        <span>Fill Report Form</span>
                    </button>
                    <button id="upload-json" class="btn-primary flex items-center space-x-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-medium py-3 px-6 rounded-lg glow-purple">
                        <i class="fas fa-code text-sm"></i>
                        <span>Upload JSON (Advanced)</span>
                    </button>
                </div>

                <!-- Report Form -->
                <div id="report-form" class="hidden fade-in">
                    <form id="incident-form" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div class="space-y-5">
                                <div>
                                    <label for="incident-text" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                        <i class="fas fa-align-left text-gray-500 text-xs"></i>
                                        <span>Incident Description <span class="text-red-400">*</span></span>
                                    </label>
                                    <textarea id="incident-text" name="raw_text" rows="5" required
                                        class="w-full input-dark rounded-lg px-4 py-3.5"
                                        placeholder="Provide detailed description of the incident..."></textarea>
                                </div>

                                <div>
                                    <label for="incident-location" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                        <i class="fas fa-location-dot text-gray-500 text-xs"></i>
                                        <span>Location <span class="text-red-400">*</span></span>
                                    </label>
                                    <input type="text" id="incident-location" name="location" required
                                        class="w-full input-dark rounded-lg px-4 py-3.5"
                                        placeholder="e.g., Karol Bagh, Delhi or specific address">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="original-language" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                            <i class="fas fa-language text-gray-500 text-xs"></i>
                                            <span>Language</span>
                                        </label>
                                        <select id="original-language" name="original_language"
                                            class="w-full input-dark rounded-lg px-4 py-3.5">
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
                                        <label for="source-type" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                            <i class="fas fa-podcast text-gray-500 text-xs"></i>
                                            <span>Source Type</span>
                                        </label>
                                        <select id="source-type" name="source_type"
                                            class="w-full input-dark rounded-lg px-4 py-3.5">
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
                                        <label for="source-credibility" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                            <i class="fas fa-shield-check text-gray-500 text-xs"></i>
                                            <span>Source Credibility</span>
                                        </label>
                                        <select id="source-credibility" name="source_credibility"
                                            class="w-full input-dark rounded-lg px-4 py-3.5">
                                            <option value="unknown">Unknown</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="verified">Verified</option>
                                            <option value="official">Official</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="incident-time" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                            <i class="fas fa-clock text-gray-500 text-xs"></i>
                                            <span>Incident Time</span>
                                        </label>
                                        <input type="datetime-local" id="incident-time" name="timestamp"
                                            class="w-full input-dark rounded-lg px-4 py-3.5">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-5">
                                <div>
                                    <label for="image-upload-input" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                        <i class="fas fa-images text-gray-500 text-xs"></i>
                                        <span>Upload Images (Optional)</span>
                                    </label>
                                    <div class="upload-zone rounded-lg p-8 text-center cursor-pointer">
                                        <input type="file" id="image-upload-input" multiple accept="image/*" class="hidden">
                                        <div id="image-drop-zone">
                                            <i class="fas fa-cloud-arrow-up text-4xl text-gray-600 mb-3"></i>
                                            <p class="text-sm text-gray-400">
                                                <span class="font-medium text-blue-400">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB each</p>
                                        </div>
                                    </div>
                                    <div id="image-previews" class="mt-4 grid grid-cols-3 gap-3"></div>
                                </div>

                                <div>
                                    <label for="video-upload-input" class="flex items-center space-x-2 text-sm font-medium text-gray-300 mb-3">
                                        <i class="fas fa-video text-gray-500 text-xs"></i>
                                        <span>Upload Videos (Optional)</span>
                                    </label>
                                    <div class="upload-zone rounded-lg p-8 text-center cursor-pointer">
                                        <input type="file" id="video-upload-input" multiple accept="video/*" class="hidden">
                                        <div id="video-drop-zone">
                                            <i class="fas fa-film text-4xl text-gray-600 mb-3"></i>
                                            <p class="text-sm text-gray-400">
                                                <span class="font-medium text-purple-400">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">MP4, MOV, AVI up to 50MB each</p>
                                        </div>
                                    </div>
                                    <div id="video-previews" class="mt-4 space-y-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-6 border-t border-white/10">
                            <button type="button" id="cancel-form" class="flex items-center space-x-2 px-6 py-3 border border-white/20 rounded-lg text-gray-300 hover:bg-white/5 transition-all">
                                <i class="fas fa-xmark text-sm"></i>
                                <span>Cancel</span>
                            </button>
                            <button type="submit" class="btn-primary flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg glow-green">
                                <i class="fas fa-paper-plane text-sm"></i>
                                <span>Submit Report</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div id="processing-status" class="mt-6 hidden">
                    <div class="flex items-center justify-center space-x-3 bg-blue-600/10 border border-blue-500/30 rounded-lg p-4 text-blue-400 glow-blue">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-medium">Processing report through multilingual pipeline...</span>
                    </div>
                </div>
            </div>

            <!-- Current SITREP Display -->
            <div id="sitrep-container" class="hidden fade-in">
                <div class="glass-card p-8 mb-8">
                    <div class="flex justify-between items-center mb-8 pb-6 border-b border-white/10">
                        <div class="flex items-center space-x-3">
                            <div class="bg-emerald-600/20 p-2.5 rounded-lg border border-emerald-500/30 glow-green">
                                <i class="fas fa-chart-line text-emerald-400 text-xl"></i>
                            </div>
                            <h2 class="text-2xl font-semibold font-display">Current SITREP</h2>
                        </div>
                        <div class="flex gap-2">
                            <button id="lang-en" class="lang-btn px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-red-600/80 text-white glow-red" data-lang="en">
                                <i class="fas fa-flag-usa mr-1.5"></i>English
                            </button>
                            <button id="lang-hi" class="lang-btn px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10" data-lang="hi">
                                <i class="fas fa-flag mr-1.5"></i>हिंदी
                            </button>
                            <button id="lang-bn" class="lang-btn px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10" data-lang="bn">
                                <i class="fas fa-flag mr-1.5"></i>বাংলা
                            </button>
                        </div>
                    </div>

                    <!-- SITREP Content -->
                    <div id="sitrep-content">
                        <div class="grid md:grid-cols-2 gap-8">
                            <!-- Main Info -->
                            <div class="space-y-6">
                                <div class="bg-white/5 border border-white/10 rounded-lg p-6 backdrop-blur-sm">
                                    <h3 class="text-xl font-semibold mb-3" id="incident-title">Loading...</h3>
                                    <div class="flex flex-wrap items-center gap-3 text-sm">
                                        <span id="incident-status" class="px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-gray-300 font-medium flex items-center space-x-1.5">
                                            <i class="fas fa-circle-info text-xs"></i>
                                            <span>Status</span>
                                        </span>
                                        <span id="incident-event-time" class="flex items-center space-x-1.5 text-gray-400">
                                            <i class="fas fa-clock text-gray-500"></i>
                                            <span>Time</span>
                                        </span>
                                        <span id="incident-display-location" class="flex items-center space-x-1.5 text-gray-400">
                                            <i class="fas fa-location-dot text-gray-500"></i>
                                            <span>Location</span>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="flex items-center space-x-2 font-semibold mb-3 text-gray-300">
                                        <i class="fas fa-file-lines text-gray-500"></i>
                                        <span>Summary</span>
                                    </h4>
                                    <p id="incident-summary" class="text-gray-400 leading-relaxed bg-white/5 border border-white/10 p-4 rounded-lg backdrop-blur-sm">Loading summary...</p>
                                </div>

                                <div>
                                    <h4 class="flex items-center space-x-2 font-semibold mb-3 text-gray-300">
                                        <i class="fas fa-list-check text-gray-500"></i>
                                        <span>Details</span>
                                    </h4>
                                    <ul id="incident-details" class="space-y-2">
                                        <li class="flex items-start space-x-2 text-gray-400">
                                            <i class="fas fa-circle text-xs text-gray-600 mt-1.5"></i>
                                            <span>Loading details...</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-gradient-to-br from-white/5 to-white/10 rounded-lg p-5 border border-white/10 backdrop-blur-sm">
                                    <h4 class="flex items-center space-x-2 font-semibold mb-4 text-gray-300">
                                        <i class="fas fa-database text-gray-500"></i>
                                        <span>Source Information</span>
                                    </h4>
                                    <div class="space-y-2.5 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-400 font-medium">Reports Processed:</span>
                                            <span id="report-count" class="font-mono font-semibold text-white bg-white/10 px-3 py-1 rounded-md border border-white/20">0</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-400 font-medium">Sources:</span>
                                            <span id="source-types" class="text-gray-300 bg-white/10 px-3 py-1 rounded-md border border-white/20">Unknown</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-400 font-medium">Recommended Action:</span>
                                            <span id="recommended-action" class="font-semibold text-blue-400 bg-blue-600/20 border border-blue-500/30 px-3 py-1 rounded-md">Unknown</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Media Attachments -->
                                <div id="media-section" class="mb-6 hidden">
                                    <h4 class="flex items-center space-x-2 font-semibold mb-4 text-slate-700">
                                        <i class="fas fa-paperclip text-slate-400"></i>
                                        <span>Media Attachments</span>
                                    </h4>
                                    <div class="space-y-4">
                                        <div id="attached-images" class="hidden">
                                            <h5 class="flex items-center space-x-2 font-medium text-sm text-slate-600 mb-3">
                                                <i class="fas fa-image text-slate-400 text-xs"></i>
                                                <span>Images</span>
                                            </h5>
                                            <div id="image-gallery" class="grid grid-cols-2 gap-3"></div>
                                        </div>
                                        <div id="attached-videos" class="hidden">
                                            <h5 class="flex items-center space-x-2 font-medium text-sm text-slate-600 mb-3">
                                                <i class="fas fa-video text-slate-400 text-xs"></i>
                                                <span>Videos</span>
                                            </h5>
                                            <div id="video-gallery" class="space-y-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Map -->
                            <div class="space-y-4">
                                <h4 class="flex items-center space-x-2 font-semibold text-slate-700">
                                    <i class="fas fa-map-location-dot text-slate-400"></i>
                                    <span>Incident Location</span>
                                </h4>
                                <div id="incident-map" class="h-96 bg-slate-100 rounded-lg shadow-inner border border-slate-200 relative z-10"></div>
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                    <div class="flex items-center justify-between text-sm">
                                        <span id="coordinates" class="flex items-center space-x-2 text-slate-600 font-mono">
                                            <i class="fas fa-compass text-slate-400"></i>
                                            <span>Coordinates: Loading...</span>
                                        </span>
                                        <span class="flex items-center space-x-2">
                                            <span class="text-slate-600">Confidence:</span>
                                            <span id="location-confidence" class="font-semibold text-slate-800 bg-white px-2.5 py-1 rounded-md">0%</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent SITREPs -->
            <div class="glass-card p-8 fade-in">
                <div class="flex items-center space-x-3 mb-6 pb-5 border-b border-white/10">
                    <div class="bg-indigo-600/20 p-2.5 rounded-lg border border-indigo-500/30">
                        <i class="fas fa-clock-rotate-left text-indigo-400 text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-semibold font-display">Recent SITREPs</h2>
                </div>
                <div id="recent-sitreps" class="space-y-3">
                    <div class="text-gray-400 text-center py-8 bg-white/5 rounded-lg border border-white/10">
                        <i class="fas fa-inbox text-3xl text-gray-600 mb-3"></i>
                        <p>No SITREPs generated yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-[9999] modal-overlay">
        <div class="glass-card p-8 max-w-2xl w-full mx-4 relative z-[10000] fade-in">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/10">
                <h3 class="text-2xl font-semibold font-display flex items-center space-x-3">
                    <div class="bg-purple-600/20 p-2 rounded-lg border border-purple-500/30 glow-purple">
                        <i class="fas fa-code text-purple-400"></i>
                    </div>
                    <span>Upload Custom Reports (JSON)</span>
                </h3>
                <button id="close-modal" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-xmark text-2xl"></i>
                </button>
            </div>
            <textarea id="custom-reports" class="w-full h-64 input-dark rounded-lg p-4 text-sm font-mono"
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
            <div class="flex gap-3 mt-6">
                <button id="process-custom" class="btn-primary flex items-center space-x-2 bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-3 rounded-lg glow-green flex-1">
                    <i class="fas fa-play text-sm"></i>
                    <span>Process Reports</span>
                </button>
                <button id="close-modal-btn" class="flex items-center space-x-2 bg-white/5 border border-white/10 hover:bg-white/10 text-gray-300 px-6 py-3 rounded-lg transition-all">
                    <i class="fas fa-xmark text-sm"></i>
                    <span>Cancel</span>
                </button>
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
            document.getElementById('close-modal-btn').addEventListener('click', hideUploadModal);
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
                <img src="${src}" alt="${name}" class="w-full h-24 object-cover rounded-lg border border-slate-200 shadow-sm group-hover:shadow-md transition-all">
                <button type="button" class="absolute top-1.5 right-1.5 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity shadow-md hover:bg-red-600" onclick="removeImagePreview(this, '${name}')">
                    <i class="fas fa-xmark"></i>
                </button>
                <p class="text-xs text-slate-500 mt-1.5 truncate font-medium">${name}</p>
            `;
            document.getElementById('image-previews').appendChild(preview);
        }

        function displayVideoPreview(src, name) {
            const preview = document.createElement('div');
            preview.className = 'relative group flex items-center p-3 bg-slate-50 rounded-lg border border-slate-200 hover:bg-slate-100 transition-all';
            preview.innerHTML = `
                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-video text-purple-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 truncate">${name}</p>
                    <p class="text-xs text-slate-500">${(uploadedVideos.find(v => v.name === name)?.file.size / 1024 / 1024).toFixed(1)}MB</p>
                </div>
                <button type="button" class="ml-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity shadow-md hover:bg-red-600" onclick="removeVideoPreview(this, '${name}')">
                    <i class="fas fa-xmark"></i>
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
            const statusText = status.charAt(0).toUpperCase() + status.slice(1);
            
            let statusClass, statusIcon;
            if (status === 'verified') {
                statusClass = 'status-badge-verified status-verified';
                statusIcon = 'fa-circle-check';
            } else if (status === 'probable') {
                statusClass = 'status-badge-probable';
                statusIcon = 'fa-circle-exclamation';
            } else {
                statusClass = 'status-badge-unverified';
                statusIcon = 'fa-circle-xmark';
            }
            
            statusElement.className = `px-3 py-1.5 rounded-full font-medium flex items-center space-x-1.5 ${statusClass}`;
            statusElement.innerHTML = `<i class="fas ${statusIcon} text-xs"></i><span>${statusText}</span>`;

            // Basic info
            document.getElementById('incident-event-time').innerHTML = `<i class="fas fa-clock text-slate-400"></i><span>${new Date(sitrep.time_window.approx_event_time).toLocaleString()}</span>`;
            document.getElementById('report-count').textContent = sitrep.sources.report_count;
            document.getElementById('source-types').textContent = sitrep.sources.top_3_sources_summary.join(', ');
            document.getElementById('recommended-action').textContent = sitrep.recommended_action.replace('_', ' ');

            // Coordinates
            if (sitrep.location.lat && sitrep.location.lng) {
                document.getElementById('coordinates').innerHTML =
                    `<i class="fas fa-compass text-slate-400"></i><span>Coordinates: ${sitrep.location.lat.toFixed(4)}, ${sitrep.location.lng.toFixed(4)}</span>`;
                document.getElementById('location-confidence').textContent =
                    `${(sitrep.location.confidence * 100).toFixed(0)}%`;
                initializeMap(sitrep.location);
            } else {
                document.getElementById('coordinates').innerHTML = '<i class="fas fa-compass text-slate-400"></i><span>Coordinates: Unknown</span>';
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
                    btn.className = 'lang-btn px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-red-600/80 text-white glow-red';
                } else {
                    btn.className = 'lang-btn px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10';
                }
            });

            // Update content
            document.getElementById('incident-title').textContent = currentSitrep.canonical_title;
            document.getElementById('incident-summary').textContent = currentSitrep.summary[lang] || 'Translation not available';
            document.getElementById('incident-display-location').innerHTML = `<i class="fas fa-location-dot text-gray-500"></i><span>${currentSitrep.location.name}</span>`;

            // Update details
            const detailsList = document.getElementById('incident-details');
            detailsList.innerHTML = '';
            const details = currentSitrep.details[`bullets_${lang}`] || [];
            details.forEach(detail => {
                const li = document.createElement('li');
                li.className = 'flex items-start space-x-2 text-gray-400';
                li.innerHTML = `<i class="fas fa-circle text-xs text-gray-600 mt-1.5"></i><span>${detail}</span>`;
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
                            div.className = 'flex justify-between items-center p-4 bg-white/5 border border-white/10 rounded-lg cursor-pointer hover:bg-white/10 hover:border-white/20 transition-all group';
                            
                            let statusClass, statusIcon;
                            if (sitrep.status === 'verified') {
                                statusClass = 'status-badge-verified status-verified';
                                statusIcon = 'fa-circle-check';
                            } else if (sitrep.status === 'probable') {
                                statusClass = 'status-badge-probable';
                                statusIcon = 'fa-circle-exclamation';
                            } else {
                                statusClass = 'status-badge-unverified';
                                statusIcon = 'fa-circle-xmark';
                            }
                            
                            div.innerHTML = `
                                <div class="flex-1">
                                    <div class="font-medium group-hover:text-white mb-1">${sitrep.title}</div>
                                    <div class="text-sm text-gray-400 flex items-center space-x-3">
                                        <span class="flex items-center space-x-1.5">
                                            <i class="fas fa-location-dot text-gray-500 text-xs"></i>
                                            <span>${sitrep.location}</span>
                                        </span>
                                        <span class="flex items-center space-x-1.5">
                                            <i class="fas fa-clock text-gray-500 text-xs"></i>
                                            <span>${new Date(sitrep.timestamp).toLocaleString()}</span>
                                        </span>
                                    </div>
                                </div>
                                <span class="px-3 py-1.5 text-xs rounded-full font-medium flex items-center space-x-1.5 ${statusClass}">
                                    <i class="fas ${statusIcon}"></i>
                                    <span>${sitrep.status}</span>
                                </span>
                            `;
                            div.addEventListener('click', () => loadSitrep(sitrep.incident_id));
                            container.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<div class="text-gray-400 text-center py-8 bg-white/5 rounded-lg border border-white/10"><i class="fas fa-inbox text-3xl text-gray-600 mb-3 block"></i><p>No SITREPs generated yet.</p></div>';
                    }
                } else {
                    container.innerHTML = `<div class="text-red-600 text-center py-8 bg-red-50 rounded-lg border border-red-200"><i class="fas fa-circle-exclamation text-3xl text-red-300 mb-3 block"></i><p>Error loading SITREPs: ${data.error || 'Unknown error'}</p></div>`;
                    console.error('Error response:', data);
                }
            } catch (error) {
                console.error('Error loading recent SITREPs:', error);
                const container = document.getElementById('recent-sitreps');
                container.innerHTML = `<div class="text-red-600 text-center py-8 bg-red-50 rounded-lg border border-red-200"><i class="fas fa-triangle-exclamation text-3xl text-red-300 mb-3 block"></i><p>Network error: ${error.message}</p></div>`;
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