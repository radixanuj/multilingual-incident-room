Before we start a note -> we have added snapshots in our README as our video quality was poor!

# 🚨 Multilingual Incident Room

> AI-powered emergency response platform using OpenAI GPT-4 Vision, Whisper & Lingo SDK

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![OpenAI](https://img.shields.io/badge/OpenAI-GPT--4-412991?logo=openai)](https://openai.com)
[![Lingo](https://img.shields.io/badge/Lingo-SDK-4285F4)](https://lingo.dev)
[![Tailwind](https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss)](https://tailwindcss.com)

## 📖 About

An intelligent incident reporting system that accepts **text, images, and videos**, analyzes them using AI, and generates multilingual situation reports (SITREPs) for emergency response teams.

**Key Capabilities:**
- 🎥 **Multimodal Analysis** - Process text, images & videos simultaneously
- 🤖 **AI-Powered** - GPT-4 Vision for images, Whisper for audio transcription
- 🌍 **Multilingual** - Bidirectional English ↔ Hindi translation via Lingo SDK
- 📊 **Structured Output** - Law enforcement-grade incident reports
- 🗺️ **Geolocation** - Interactive map with incident pinpointing

## 🏗️ Architecture

```
User Upload → Lingo Normalize → OpenAI Analyze → Lingo Translate → SITREP Output
  (Text +       (Detect lang      (GPT-4 Vision   (English to      (Bilingual
   Images +      & translate       + Whisper       Hindi)           EN + HI)
   Videos)       to English)       + FFmpeg)
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- FFmpeg
- OpenAI API key
- Lingo API key

### Installation

```bash
# Clone & navigate
git clone https://github.com/radixanuj/multilingual-incident-room.git
cd multilingual-incident-room

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure API keys in .env
OPENAI_API_KEY=sk-your-openai-key-here
LINGO_API_KEY=your-lingo-key-here

# Setup database
touch database/database.sqlite
php artisan migrate

# Build frontend
npm run build

# Start server
php artisan serve
```

**Access:** [http://localhost:8000/dashboard](http://localhost:8000/dashboard)

## 🎯 Features

### 🖼️ Image Analysis
GPT-4 Vision identifies people, objects, damage, vehicles, and text from photos

### 🎬 Video Intelligence
- FFmpeg extracts audio → Whisper transcribes speech
- FFmpeg extracts 3 key frames → GPT-4 Vision analyzes visuals
- Combined audio + visual analysis

### 📝 Structured Reports (JSON)
- Summary & detailed description
- People involved (victims, suspects, witnesses)
- Actions taken (emergency response, police, medical)
- Severity assessment & recommendations

### 🌍 Multilingual (Lingo SDK)
- **Input:** Auto-detect Hindi/Bengali/English → translate to English
- **Output:** Translate structured reports to Hindi
- Batch processing: 2-second latency

## 🛠️ Tech Stack

| Category | Technology |
|----------|-----------|
| **Backend** | Laravel 12, PHP 8.2 |
| **AI** | OpenAI GPT-4 Turbo, Whisper |
| **Translation** | Lingo SDK |
| **Frontend** | Tailwind CSS 4, Alpine.js |
| **Database** | SQLite |
| **Maps** | Leaflet.js + OpenStreetMap |
| **Video** | FFmpeg |


## Snapshots

![alt text](assets/image.png)

![alt text](assets/image-1.png)

![alt text](assets/image-2.png)

![alt text](assets/image_hn.png)
---

**Built with ❤️ for Lingo Hackathon** | Laravel + OpenAI + Lingo SDK
