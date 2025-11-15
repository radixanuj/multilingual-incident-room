<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Incident Command Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        .font-display {
            font-family: 'Space Grotesk', sans-serif;
        }
        
        /* Animated gradient background */
        .hero-gradient {
            background: radial-gradient(ellipse at top, rgba(239, 68, 68, 0.15) 0%, transparent 50%),
                        radial-gradient(ellipse at bottom right, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                        #0a0a0a;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Neon glow effects */
        .glow-red {
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.4),
                        0 0 40px rgba(239, 68, 68, 0.2),
                        0 0 60px rgba(239, 68, 68, 0.1);
        }
        
        .glow-blue {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3),
                        0 0 40px rgba(59, 130, 246, 0.15);
        }
        
        .glow-green {
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.4),
                        0 0 40px rgba(34, 197, 94, 0.2);
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
            box-shadow: 0 0 40px rgba(239, 68, 68, 0.6),
                        0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .btn-secondary {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }
        
        /* Feature cards */
        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .feature-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(239, 68, 68, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Pulse animation */
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        .pulse-slow {
            animation: pulse-slow 3s ease-in-out infinite;
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
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
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
        
        /* Dashboard preview mockup */
        .dashboard-mockup {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 2px solid rgba(239, 68, 68, 0.2);
            border-radius: 20px;
            padding: 20px;
            position: relative;
        }
        
        .alert-card {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 16px;
            backdrop-filter: blur(10px);
        }
        
        /* Scan line effect */
        @keyframes scanline {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }
        
        .scanline {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.8), transparent);
            animation: scanline 4s linear infinite;
        }
    </style>
</head>
<body class="hero-gradient grid-background">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 backdrop-blur-md bg-black/30 border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-red-600/20 p-2.5 rounded-lg border border-red-500/30 glow-red">
                        <i class="fas fa-shield-halved text-red-400 text-xl"></i>
                    </div>
                    <span class="text-xl font-bold font-display">Incident Command Center</span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-2 text-sm">
                        <div class="h-2 w-2 bg-green-400 rounded-full pulse-slow"></div>
                        <span class="text-gray-400">SYSTEM ONLINE</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center pt-20 pb-10 px-6">
        <div class="container mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Column: Content -->
                <div class="space-y-8 animate-fade-in">
                    <!-- Status Badge -->
                    <div class="inline-flex items-center space-x-2 bg-red-600/10 border border-red-500/30 rounded-full px-4 py-2 text-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        <span class="text-red-400 font-medium">LIVE SYSTEM</span>
                    </div>
                    
                    <!-- Headline -->
                    <h1 class="text-5xl md:text-7xl font-bold font-display leading-tight">
                        <span class="gradient-text">REAL-TIME</span><br>
                        <span class="text-white">INCIDENT REPORTING</span><br>
                        <span class="text-white">& ANALYSIS</span>
                    </h1>
                    
                    <!-- Sub-headline -->
                    <p class="text-xl text-gray-400 leading-relaxed max-w-xl">
                        A multilingual crisis-intake system that detects, clusters, and verifies reports from the public—across languages, formats, and regions.
                    </p>
                    
                    <!-- Feature Tags -->
                    <div class="flex flex-wrap gap-3">
                        <div class="feature-tag bg-white/5 border border-white/10 rounded-full px-4 py-2 text-sm flex items-center space-x-2">
                            <i class="fas fa-bolt text-yellow-400"></i>
                            <span>Rapid Reporting</span>
                        </div>
                        <div class="feature-tag bg-white/5 border border-white/10 rounded-full px-4 py-2 text-sm flex items-center space-x-2">
                            <i class="fas fa-language text-blue-400"></i>
                            <span>Multilingual Engine</span>
                        </div>
                        <div class="feature-tag bg-white/5 border border-white/10 rounded-full px-4 py-2 text-sm flex items-center space-x-2">
                            <i class="fas fa-cloud-arrow-up text-orange-400"></i>
                            <span>Evidence Upload</span>
                        </div>
                    </div>
                    
                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <a href="/dashboard?action=report" class="btn-primary group inline-flex items-center justify-center space-x-3 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white font-semibold px-8 py-4 rounded-xl text-lg glow-orange">
                            <span class="relative z-10">Report an Incident</span>
                            <i class="fas fa-arrow-right relative z-10 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="/dashboard" class="btn-secondary inline-flex items-center justify-center space-x-3 border-2 border-white/20 hover:border-white/40 text-white font-semibold px-8 py-4 rounded-xl text-lg backdrop-blur-sm">
                            <span>Open Dashboard</span>
                            <i class="fas fa-chart-line"></i>
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t border-white/10">
                        <div>
                            <div class="text-3xl font-bold gradient-text">3</div>
                            <div class="text-sm text-gray-500 mt-1">Languages</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold gradient-text">24/7</div>
                            <div class="text-sm text-gray-500 mt-1">Monitoring</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold gradient-text">Real-Time</div>
                            <div class="text-sm text-gray-500 mt-1">Processing</div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Dashboard Mockup -->
                <div class="relative float-animation">
                    <div class="scanline"></div>
                    
                    <!-- Main Dashboard Window -->
                    <div class="dashboard-mockup shadow-2xl glow-blue">
                        <!-- Header Bar -->
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                            <div class="flex items-center space-x-2">
                                <div class="h-3 w-3 bg-red-500 rounded-full"></div>
                                <div class="h-3 w-3 bg-yellow-500 rounded-full"></div>
                                <div class="h-3 w-3 bg-green-500 rounded-full"></div>
                            </div>
                            <div class="text-xs text-gray-500 font-mono">incident-command.local</div>
                        </div>
                        
                        <!-- Dashboard Content -->
                        <div class="space-y-4">
                            <!-- Map Preview -->
                            <div class="bg-slate-900/50 rounded-lg p-4 border border-white/5">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-sm text-gray-400">
                                        <i class="fas fa-map-marker-alt text-red-400 mr-2"></i>
                                        Live Incident Map
                                    </div>
                                    <div class="flex space-x-1">
                                        <div class="h-2 w-2 bg-red-500 rounded-full pulse-slow"></div>
                                        <div class="h-2 w-2 bg-orange-500 rounded-full pulse-slow" style="animation-delay: 0.5s"></div>
                                        <div class="h-2 w-2 bg-yellow-500 rounded-full pulse-slow" style="animation-delay: 1s"></div>
                                    </div>
                                </div>
                                <div class="relative h-32 bg-gradient-to-br from-slate-800 to-slate-900 rounded-lg overflow-hidden">
                                    <!-- Simulated map pins -->
                                    <div class="absolute top-1/4 left-1/3 h-3 w-3 bg-red-500 rounded-full animate-ping"></div>
                                    <div class="absolute top-1/2 right-1/4 h-3 w-3 bg-orange-500 rounded-full animate-ping" style="animation-delay: 0.5s"></div>
                                    <div class="absolute bottom-1/4 left-1/2 h-3 w-3 bg-yellow-500 rounded-full animate-ping" style="animation-delay: 1s"></div>
                                </div>
                            </div>
                            
                            <!-- Alert Cards -->
                            <div class="alert-card">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <i class="fas fa-circle-exclamation text-red-400 text-sm"></i>
                                            <span class="text-xs text-red-400 font-semibold">VERIFIED</span>
                                        </div>
                                        <div class="text-sm text-white mb-1">Explosion reported in Karol Bagh</div>
                                        <div class="text-xs text-gray-400">
                                            <i class="fas fa-language mr-1"></i>
                                            3 reports · Hindi, Bengali, English
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">07:12</div>
                                </div>
                            </div>
                            
                            <div class="alert-card" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <i class="fas fa-circle-info text-blue-400 text-sm"></i>
                                            <span class="text-xs text-blue-400 font-semibold">PROBABLE</span>
                                        </div>
                                        <div class="text-sm text-white mb-1">Fire incident near Central Market</div>
                                        <div class="text-xs text-gray-400">
                                            <i class="fas fa-image mr-1"></i>
                                            2 images · 1 video attached
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">15:45</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute -top-4 -right-4 bg-red-600/20 border border-red-500/30 rounded-lg p-3 backdrop-blur-sm glow-red" style="animation: float 4s ease-in-out infinite;">
                        <i class="fas fa-bell text-red-400 text-xl"></i>
                    </div>
                    <div class="absolute -bottom-4 -left-4 bg-blue-600/20 border border-blue-500/30 rounded-lg p-3 backdrop-blur-sm glow-blue" style="animation: float 5s ease-in-out infinite; animation-delay: 1s;">
                        <i class="fas fa-globe text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-6 border-t border-white/5">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold font-display mb-4">
                    <span class="gradient-text">Powered by Lingo.dev</span>
                </h2>
                <p class="text-xl text-gray-400">Advanced multilingual processing for crisis management</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card p-8 rounded-2xl">
                    <div class="bg-red-600/10 w-14 h-14 rounded-xl flex items-center justify-center mb-6 glow-red">
                        <i class="fas fa-language text-red-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Multilingual Translation</h3>
                    <p class="text-gray-400">Automatically translates reports from Hindi, Bengali, and English using Lingo SDK for unified processing.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card p-8 rounded-2xl">
                    <div class="bg-blue-600/10 w-14 h-14 rounded-xl flex items-center justify-center mb-6 glow-blue">
                        <i class="fas fa-layer-group text-blue-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Smart Clustering</h3>
                    <p class="text-gray-400">Groups related incident reports by location, time, and similarity to identify patterns and reduce duplicates.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card p-8 rounded-2xl">
                    <div class="bg-green-600/10 w-14 h-14 rounded-xl flex items-center justify-center mb-6 glow-green">
                        <i class="fas fa-circle-check text-green-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Automated Verification</h3>
                    <p class="text-gray-400">Scores incident credibility based on source reliability, cross-references, and confidence metrics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/5 py-8 px-6">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="text-gray-500 text-sm mb-4 md:mb-0">
                    © 2025 Incident Command Center. Built for Lingo Hackathon.
                </div>
                <div class="flex items-center space-x-6 text-sm text-gray-500">
                    <a href="/dashboard" class="hover:text-white transition-colors">Dashboard</a>
                    <a href="#" class="hover:text-white transition-colors">Documentation</a>
                    <a href="https://github.com" class="hover:text-white transition-colors">
                        <i class="fab fa-github mr-1"></i>GitHub
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Add fade-in animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
        
        // Feature tag hover effect
        document.querySelectorAll('.feature-tag').forEach(tag => {
            tag.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.borderColor = 'rgba(239, 68, 68, 0.5)';
            });
            tag.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            });
        });
    </script>
</body>
</html>
