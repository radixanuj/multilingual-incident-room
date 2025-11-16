# 🚨 AI-Powered Multilingual Incident Room

> Advanced emergency response system using OpenAI GPT-4 Vision & Whisper for multimodal incident analysis

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)
![OpenAI](https://img.shields.io/badge/OpenAI-GPT--4-412991?logo=openai)
![Lingo](https://img.shields.io/badge/Lingo-Translation-4285F4)
![Tailwind](https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss)

## 🎯 Overview

An intelligent incident reporting system that analyzes **text, images, and videos** using AI, extracts structured information, and generates multilingual situation reports (SITREPs) for law enforcement and emergency response teams.

### Key Features

✅ **Multimodal AI Analysis** - GPT-4 Vision analyzes images & video frames, Whisper transcribes audio  
✅ **Video Intelligence** - Extracts audio transcripts + visual content from videos  
✅ **Image Analysis** - Identifies people, objects, damage, locations from photos  
✅ **Structured Reports** - AI generates law enforcement-grade incident reports  
✅ **Multilingual** - English + Hindi support via Lingo translation API  
✅ **Interactive Dashboard** - Live SITREP display with map integration

## 🏗️ Architecture

\`\`\`
User Upload (Text + Images + Videos)
    ↓
Lingo SDK: Normalize & translate text to English
    ↓
OpenAI Analysis:
    ├─ Text: GPT-4 extracts structured information
    ├─ Images: GPT-4 Vision analyzes each image
    └─ Videos:
        ├─ FFmpeg extracts audio → Whisper transcribes
        └─ FFmpeg extracts frames → GPT-4 Vision analyzes
    ↓
AI generates structured incident report
    ↓
Lingo SDK: Translate report to Hindi
    ↓
Save multilingual SITREP + Display on dashboard
\`\`\`

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Composer  
- Node.js 20+
- FFmpeg (for video processing)
- OpenAI API key
- Lingo API key

### Installation

\`\`\`bash
# Clone repository
git clone https://github.com/radixanuj/multilingual-incident-room.git
cd multilingual-incident-room

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Add API keys to .env:
# OPENAI_API_KEY=sk-your-key-here
# LINGO_API_KEY=your-lingo-key

# Database setup (SQLite)
touch database/database.sqlite
php artisan migrate

# Build frontend
npm run build

# Start server
php artisan serve
\`\`\`

Visit: **http://localhost:8000/dashboard**

## 🤖 AI Capabilities

### Image Analysis
- High-detail GPT-4 Vision analysis
- Identifies: people, objects, damage, vehicles, text, time indicators

### Video Analysis
1. **Audio**: FFmpeg extracts → Whisper transcribes speech
2. **Visual**: Extracts 3 key frames → GPT-4 Vision analyzes each
3. **Combined**: Audio transcript + visual observations

### Structured Output
- Summary & detailed description
- People involved (victims, suspects, witnesses)  
- Actions taken (emergency, police, medical)
- Severity assessment & recommendations

## 🌍 Multilingual Support

- **Primary**: English (AI analysis)
- **Translation**: Hindi (via Lingo SDK)
- Easily extendable to 100+ languages

## 🛠️ Technology Stack

- **Backend**: Laravel 12, PHP 8.2
- **AI**: OpenAI GPT-4 Turbo + Whisper
- **Translation**: Lingo SDK  
- **Frontend**: Tailwind CSS 4, Alpine.js
- **Maps**: Leaflet.js + OpenStreetMap
- **Video**: FFmpeg

---

**Made with ❤️ using Laravel + OpenAI + Lingo**
