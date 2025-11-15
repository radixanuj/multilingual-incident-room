# Multilingual Incident Room - Handoff Document

**Date:** November 15, 2025  
**Repository:** multilingual-incident-room  
**Framework:** Laravel 12 + Tailwind CSS 4  
**Purpose:** Multilingual emergency incident processing and SITREP (Situation Report) generation

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Key Features](#key-features)
4. [Technology Stack](#technology-stack)
5. [Core Services](#core-services)
6. [API Endpoints](#api-endpoints)
7. [Data Flow](#data-flow)
8. [Setup & Installation](#setup--installation)
9. [Environment Configuration](#environment-configuration)
10. [Development Workflow](#development-workflow)
11. [Known Limitations](#known-limitations)
12. [Future Development Opportunities](#future-development-opportunities)
13. [Troubleshooting](#troubleshooting)

---

## 🚀 Quick Start - Running on Localhost

**Get the app running in 5 minutes:**

```bash
# 1. Navigate to project directory
cd /path/to/multilingual-incident-room

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Set up environment file
cp .env.example .env
php artisan key:generate

# 5. Add your Lingo API key to .env file
# Open .env in your editor and add:
# LINGO_API_KEY=your-lingo-api-key-here

# 6. Set up database (SQLite - no config needed)
touch database/database.sqlite
php artisan migrate

# 7. Link storage for file uploads
php artisan storage:link
mkdir -p storage/app/private/sitreps

# 8. Build frontend assets
npm run build

# 9. Start the development server (all-in-one command)
composer run dev
```

**Access the application:**
- 🌐 **Dashboard:** http://localhost:8000/dashboard
- 📊 **Test API:** http://localhost:8000/api/incident-room/test-example

**Alternative (run services separately):**

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Frontend build with hot reload
npm run dev

# Terminal 3 - Queue worker (optional, for background jobs)
php artisan queue:work

# Terminal 4 - Live logs (optional, for debugging)
php artisan pail
```

**First Test:**
1. Open http://localhost:8000/dashboard
2. Click "Process Example Reports" button
3. See a SITREP generated from sample multilingual reports!

---

## Project Overview

The Multilingual Incident Room is an emergency response system that processes incident reports from multiple sources and languages, then generates structured Situation Reports (SITREPs) with verified information. Built for the Lingo Hackathon, it demonstrates real-time multilingual content processing for disaster management.

### Primary Use Case
Emergency responders receive reports in Hindi (हिंदी), Bengali (বাংলা), and English about incidents. The system:
- Translates all reports to English for unified processing
- Clusters related reports about the same incident
- Geocodes locations automatically
- Generates structured SITREPs in all three languages
- Displays incidents on an interactive map with media attachments

---

## Architecture

### High-Level Architecture

```
┌─────────────────┐
│  User Interface │
│  (Blade + JS)   │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│   IncidentRoomController │
│   - Form submission      │
│   - Report processing    │
│   - SITREP retrieval     │
└────────┬────────────────┘
         │
         ├──► ReportProcessingPipeline
         │    ├─ Ingest & validate
         │    ├─ Normalize text
         │    ├─ Translate (LingoSDK)
         │    ├─ Extract structured fields
         │    ├─ Geotag (GeocodingService)
         │    ├─ Cluster & deduplicate
         │    └─ Verify minimal facts
         │
         └──► SitrepSynthesizer
              ├─ Generate canonical English SITREP
              ├─ Localize to hi/bn (LingoSDK)
              ├─ Quality checks
              └─ Save to storage
```

### Directory Structure

```
app/
├── Http/Controllers/
│   └── IncidentRoomController.php    # Main API controller
├── Services/
│   ├── LingoSdkService.php           # Translation wrapper
│   ├── GeocodingService.php          # Location resolution
│   ├── ReportProcessingPipeline.php  # 7-step processing pipeline
│   └── SitrepSynthesizer.php         # SITREP generation

resources/
├── views/
│   ├── dashboard.blade.php           # Main UI (single-page app)
│   └── welcome.blade.php

routes/
└── web.php                            # API routes

storage/
└── app/
    └── private/
        └── sitreps/                   # JSON SITREPs stored here
```

---

## Key Features

### 1. **Multilingual Report Submission**
- **Form-based:** Rich web form with file uploads (images/videos)
- **JSON API:** Direct JSON payload processing
- **Auto-detection:** Automatic language detection for incoming reports

### 2. **Intelligent Processing Pipeline**
7-step pipeline that handles:
- Text normalization (remove filler words, fix encoding)
- Translation to English using Lingo SDK
- Event type extraction (explosion, fire, collapse, etc.)
- Location extraction & geocoding
- Casualty information extraction
- Report clustering by location/time/similarity
- Verification scoring

### 3. **SITREP Generation**
Structured situation reports with:
- **Canonical title** describing the incident
- **Location data** with coordinates and confidence scores
- **Time window** of first/last reports
- **Verification status** (verified/probable/unverified)
- **Summaries** in English, Hindi, Bengali
- **Detail bullets** localized to all three languages
- **Media attachments** (images/videos from reports)
- **Recommended actions** (publish/alert_authorities/request_verification/monitor)

### 4. **Interactive Dashboard**
- Live SITREP display with map integration (Leaflet.js)
- Language switcher (English/Hindi/Bengali)
- Recent SITREPs list
- Media gallery viewer
- Real-time processing status indicators

---

## Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Backend Framework** | Laravel | 12.0 |
| **PHP Version** | PHP | ^8.2 |
| **Translation Service** | Lingo SDK | ^0.1.6 |
| **HTTP Client** | Guzzle | ^7.10 |
| **Frontend CSS** | Tailwind CSS | ^4.0.0 |
| **Frontend Build** | Vite | ^7.0.7 |
| **Map Library** | Leaflet.js | 1.9.4 |
| **Geocoding** | Nominatim (OSM) | - |
| **Database** | SQLite (default) | - |
| **Queue System** | Laravel Queue | Built-in |

---

## Core Services

### 1. LingoSdkService (`app/Services/LingoSdkService.php`)

Wrapper around the Lingo.dev SDK for translation operations.

**Key Methods:**
- `translate(string $text, string $source, string $target): string`
  - Single text translation
  - Auto-detects source language if `$source = 'auto'`
  
- `compile(string $sourceText, array $targetLocales): array`
  - Batch translation from English to multiple languages
  - Returns associative array: `['hi' => '...', 'bn' => '...']`
  
- `detectLanguage(string $text): string`
  - Returns language code ('en', 'hi', 'bn')

**Configuration:**
- API key from `LINGO_API_KEY` environment variable
- 120-second timeout for API calls
- Fallback mechanisms for failed translations

---

### 2. GeocodingService (`app/Services/GeocodingService.php`)

Resolves location strings to latitude/longitude coordinates.

**Key Methods:**
- `resolve(string $locationString): array`
  - Returns: `['lat', 'lng', 'confidence', 'source', 'display_name']`
  - Uses Nominatim (OpenStreetMap) API
  
**Important Notes:**
- No static location database (all live API calls)
- Confidence scoring based on OpenStreetMap importance metric
- Includes location normalization (e.g., "New Delhi" → "Delhi")

---

### 3. ReportProcessingPipeline (`app/Services/ReportProcessingPipeline.php`)

7-step processing pipeline that transforms raw reports into clustered incidents.

**Pipeline Steps:**

1. **Ingest & Validate** - Validate required fields, add defaults
2. **Normalize** - Clean text, remove filler words, fix encoding
3. **Translate** - Convert all reports to English
4. **Extract Fields** - Event type, locations, datetime, casualties, witness count
5. **Geotag** - Add lat/lng coordinates to reports
6. **Cluster** - Group similar reports by location/time/text similarity
7. **Verify** - Score clusters for verification confidence

**Configuration Constants:**
```php
DEDUPE_SIMILARITY_THRESHOLD = 0.82    // Text similarity for clustering
CLUSTER_DISTANCE_METERS = 2000        // Max distance for same incident
MIN_REPORTS_FOR_INCIDENT = 1          // Minimum reports to form cluster
```

**Event Types Detected:**
- explosion, fire, collapse, protest, shooting, accident

---

### 4. SitrepSynthesizer (`app/Services/SitrepSynthesizer.php`)

Generates final SITREP documents from incident clusters.

**Key Methods:**
- `synthesize(array $cluster): array`
  - Input: Single incident cluster from pipeline
  - Output: Complete SITREP with all fields

**SITREP Structure:**
```json
{
  "incident_id": "20251115_karol_bagh_delhi_explosion_abc123",
  "canonical_title": "Explosion reported in Karol Bagh at 07:12",
  "location": {
    "name": "Karol Bagh, Delhi",
    "lat": 28.6519,
    "lng": 77.1903,
    "confidence": 0.9
  },
  "time_window": {
    "first_report": "2025-11-15T07:11:50+05:30",
    "last_report": "2025-11-15T07:13:27+05:30",
    "approx_event_time": "2025-11-15T07:12:38+05:30",
    "time_confidence": 0.7
  },
  "status": "verified|probable|unverified",
  "summary": {
    "en": "Summary in English...",
    "hi": "हिंदी में सारांश...",
    "bn": "বাংলায় সারাংশ..."
  },
  "details": {
    "bullets_en": ["Detail 1", "Detail 2"],
    "bullets_hi": ["विवरण 1", "विवरण 2"],
    "bullets_bn": ["বিস্তারিত ১", "বিস্তারিত ২"]
  },
  "casualty_estimate": {
    "mentioned_count": 0,
    "confidence": 0.8
  },
  "sources": {
    "report_count": 3,
    "report_ids": ["r123_0", "r456_1"],
    "top_3_sources_summary": ["field_call", "citizen_sms"],
    "reports": [...]
  },
  "recommended_action": "publish|alert_authorities|request_verification|monitor",
  "audit": {
    "translations": [...],
    "geocode_attempts": [...],
    "created_at": "2025-11-15T07:15:00+05:30"
  }
}
```

---

## API Endpoints

### Base URL: `/api/incident-room`

#### 1. **Submit Form-Based Report**
```
POST /api/incident-room/submit-form
Content-Type: multipart/form-data

Parameters:
- raw_text (required, string, max:10000)
- location (required, string, max:500)
- original_language (optional, string, default:'auto')
- source_type (optional, string)
- source_credibility (optional, string)
- timestamp (optional, ISO 8601 datetime)
- images[] (optional, file, max:10MB each)
- videos[] (optional, file, max:50MB each)

Response: SITREP JSON (200) or Error (422/500)
```

#### 2. **Process JSON Reports**
```
POST /api/incident-room/process-reports
Content-Type: application/json

Body:
{
  "reports": [
    {
      "raw_text": "Incident description...",
      "location": "City, Area",
      "original_language": "hi",
      "source_type": "text",
      "timestamp": "2025-11-15T07:12:00+05:30",
      "reporter_meta": {
        "source": "field_call",
        "credibility": "medium"
      }
    }
  ]
}

Response: SITREP JSON (200) or Error (422/500)
```

#### 3. **Test with Example Data**
```
GET /api/incident-room/test-example

Response: SITREP JSON using built-in example reports
```

#### 4. **List All SITREPs**
```
GET /api/incident-room/sitreps

Response:
{
  "sitreps": [
    {
      "incident_id": "20251115_...",
      "title": "Explosion reported...",
      "status": "verified",
      "timestamp": "2025-11-15T07:15:00+05:30",
      "location": "Karol Bagh, Delhi"
    }
  ],
  "count": 1
}
```

#### 5. **Get Single SITREP**
```
GET /api/incident-room/sitreps/{incidentId}

Response: Full SITREP JSON (200) or Error (404/500)
```

---

## Data Flow

### Form Submission Flow

```
User fills form → Submit → IncidentRoomController::submitForm()
                              ↓
                         Handle file uploads
                              ↓
                         Build report object
                              ↓
                    Geocode location (GeocodingService)
                              ↓
                ReportProcessingPipeline::processReports()
                              ↓
                         Get clusters
                              ↓
                    SitrepSynthesizer::synthesize()
                              ↓
                      Apply quality checks
                              ↓
                  Save to storage/app/private/sitreps/
                              ↓
                        Return SITREP JSON
```

### Processing Pipeline Detail

```
Raw Reports → [Ingest] → Valid Reports
                ↓
            [Normalize] → Cleaned Text
                ↓
            [Translate] → English Text
                ↓
          [Extract Fields] → Structured Data
                ↓
             [Geotag] → Coordinates Added
                ↓
       [Cluster & Dedupe] → Incident Clusters
                ↓
             [Verify] → Scored Clusters
                ↓
          Select Primary Cluster
                ↓
         Synthesize SITREP
```

---

## Setup & Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm

### Quick Start

```bash
# 1. Clone repository
git clone <repository-url>
cd multilingual-incident-room

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Set up storage
php artisan storage:link
mkdir -p storage/app/private/sitreps

# 5. Configure database (SQLite default)
touch database/database.sqlite
php artisan migrate

# 6. Add Lingo API key to .env
# LINGO_API_KEY=your-lingo-api-key-here

# 7. Build frontend assets
npm run build

# 8. Start development servers
composer run dev
# This starts: Laravel server, queue worker, Pail logs, and Vite
```

### Alternative: Individual Commands

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Queue worker (for async processing)
php artisan queue:listen --tries=1

# Terminal 3: Logs viewer
php artisan pail --timeout=0

# Terminal 4: Frontend build
npm run dev
```

---

## Environment Configuration

### Required Environment Variables

```bash
# App basics
APP_NAME="Multilingual Incident Room"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Lingo SDK (CRITICAL - get from lingo.dev)
LINGO_API_KEY=your-api-key-here

# Database (SQLite default)
DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Queue (for background processing)
QUEUE_CONNECTION=sync  # Use 'database' for production
```

### Optional Variables

```bash
# If using MySQL/PostgreSQL instead of SQLite
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=incident_room
DB_USERNAME=root
DB_PASSWORD=

# Increase PHP limits for file uploads
UPLOAD_MAX_FILESIZE=50M
POST_MAX_SIZE=60M
```

---

## Development Workflow

### Running Tests
```bash
composer run test
# or
php artisan test
```

### Accessing the Application

1. **Dashboard:** http://localhost:8000/dashboard
2. **API Base:** http://localhost:8000/api/incident-room
3. **Test Endpoint:** http://localhost:8000/api/incident-room/test-example

### Testing the Pipeline

#### Option 1: Use Built-in Example
Click "Process Example Reports" button on dashboard

#### Option 2: Form Submission
1. Click "Fill Report Form"
2. Enter incident details
3. Upload images/videos (optional)
4. Submit

#### Option 3: JSON API
```bash
curl -X POST http://localhost:8000/api/incident-room/process-reports \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(php artisan tinker --execute='echo csrf_token();')" \
  -d '{
    "reports": [
      {
        "raw_text": "Loud explosion in central market",
        "location": "Connaught Place, Delhi",
        "original_language": "en",
        "source_type": "citizen_sms",
        "timestamp": "2025-11-15T10:30:00+05:30"
      }
    ]
  }'
```

### Viewing Generated SITREPs

```bash
# List all SITREP files
ls -la storage/app/private/sitreps/

# View specific SITREP
cat storage/app/private/sitreps/20251115_karol_bagh_delhi_explosion_abc123.json | jq
```

---

## Known Limitations

### Current Constraints

1. **Geocoding:**
   - Depends on OpenStreetMap Nominatim API (rate limits apply)
   - No caching of geocoding results
   - Confidence scoring is heuristic-based

2. **File Storage:**
   - Uploaded images/videos stored in `storage/app/public/incidents/`
   - No cloud storage integration
   - No file size optimization/compression

3. **Translation:**
   - Limited to 3 languages (en, hi, bn)
   - Depends entirely on Lingo SDK availability
   - No offline fallback

4. **Database:**
   - SITREPs stored as JSON files (no SQL database)
   - No full-text search capability
   - No relationship tracking between incidents

5. **Processing:**
   - Synchronous processing (can timeout on large batches)
   - Single-threaded pipeline
   - No distributed processing

6. **Quality Checks:**
   - Basic validation only
   - No human-in-the-loop verification
   - Limited fact-checking capabilities

---

## Future Development Opportunities

### High Priority

1. **Database Integration**
   - Migrate from JSON files to PostgreSQL/MySQL
   - Add Eloquent models for Reports, SITREPs, Media
   - Implement full-text search with Scout/Meilisearch

2. **Queue Processing**
   - Move pipeline to background jobs (Redis/RabbitMQ)
   - Add progress tracking for long-running processes
   - Implement retry mechanisms

3. **Media Processing**
   - Cloud storage (S3/Google Cloud Storage)
   - Image optimization & thumbnail generation
   - Video transcoding for web playback
   - EXIF data extraction for additional metadata

4. **Enhanced Geocoding**
   - Cache geocoding results
   - Multiple geocoding provider support (Google Maps, Mapbox)
   - Reverse geocoding for coordinates
   - Geofencing and zone definitions

### Medium Priority

5. **Authentication & Authorization**
   - User accounts with roles (admin, operator, viewer)
   - Organization/team-based access control
   - API token authentication
   - Activity audit logs

6. **Real-time Features**
   - WebSocket integration (Laravel Broadcasting)
   - Live SITREP updates
   - Notification system (email, SMS, push)
   - Alert escalation workflows

7. **Analytics & Reporting**
   - Incident trends dashboard
   - Geographic heat maps
   - Source reliability scoring
   - Export to PDF/Excel

8. **Advanced NLP**
   - Named entity recognition
   - Sentiment analysis
   - Temporal reasoning
   - Confidence interval calculations

### Low Priority

9. **Multi-tenancy**
   - Support multiple organizations
   - Isolated data storage
   - Custom branding per tenant

10. **Mobile App**
    - Native iOS/Android apps
    - Offline report submission
    - Push notifications
    - Photo/video capture

11. **Integration**
    - Emergency services APIs
    - Social media monitoring
    - Weather data integration
    - Traffic/transport data

---

## Troubleshooting

### Localhost Setup Issues

#### 1. "Command not found: composer" or "Command not found: php"
**Problem:** PHP or Composer not installed

**Solutions:**
```bash
# macOS - Install via Homebrew
brew install php
brew install composer

# Verify installation
php --version    # Should show PHP 8.2 or higher
composer --version
```

#### 2. "Command not found: npm"
**Problem:** Node.js not installed

**Solution:**
```bash
# macOS - Install via Homebrew
brew install node

# Or download from: https://nodejs.org/

# Verify installation
node --version    # Should show v18 or higher
npm --version
```

#### 3. Port 8000 Already in Use
**Problem:** Another application is using port 8000

**Solutions:**
```bash
# Option 1: Use a different port
php artisan serve --port=8080
# Then access: http://localhost:8080/dashboard

# Option 2: Find and kill the process using port 8000
lsof -ti:8000 | xargs kill -9
```

#### 4. "Permission denied" on storage directories
**Problem:** Laravel needs write access to storage

**Solution:**
```bash
# Give proper permissions
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/app/private/sitreps

# If on Mac/Linux and using Apache/Nginx
sudo chown -R $USER:www-data storage bootstrap/cache
```

#### 5. ".env file not found"
**Problem:** Environment file missing

**Solution:**
```bash
# Copy example file
cp .env.example .env

# Generate app key
php artisan key:generate

# Open .env and add Lingo API key
nano .env
# or
open .env  # macOS will open in default editor
```

### Common Runtime Issues

#### 1. "LINGO_API_KEY not set" Error
**Solution:** Add your API key to `.env`:
```bash
LINGO_API_KEY=your-actual-key
php artisan config:clear
```

#### 2. File Upload Fails
**Problem:** Files too large or permission denied

**Solutions:**
```bash
# Increase PHP limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Fix storage permissions
chmod -R 775 storage/
chown -R www-data:www-data storage/  # Linux
```

#### 3. Geocoding Returns No Results
**Problem:** Nominatim API rate limits or unknown location

**Solutions:**
- Wait 1 second between requests (API limit)
- Use more specific location strings
- Check logs: `storage/logs/laravel.log`

#### 4. Translation Fails
**Problem:** Lingo SDK timeout or API error

**Debug:**
```bash
php artisan pail
# Then submit a report and watch logs
```

**Fallback:** Text returns untranslated if SDK fails

#### 5. Dashboard Shows "No SITREPs"
**Check:**
```bash
# Verify storage directory exists
ls -la storage/app/private/sitreps/

# Check file permissions
chmod -R 775 storage/app/private/

# View recent logs
tail -f storage/logs/laravel.log
```

#### 6. Map Not Loading
**Problem:** Leaflet.js CDN blocked or CORS issues

**Solution:**
- Check browser console for errors
- Verify internet connection
- Consider self-hosting Leaflet assets

---

## File Structure Reference

```
multilingual-incident-room/
├── app/
│   ├── Http/Controllers/
│   │   └── IncidentRoomController.php       # Main API controller
│   ├── Services/
│   │   ├── GeocodingService.php             # Location resolution
│   │   ├── LingoSdkService.php              # Translation service
│   │   ├── ReportProcessingPipeline.php     # 7-step pipeline
│   │   └── SitrepSynthesizer.php            # SITREP generation
│   └── Models/
│       └── User.php
├── config/
│   ├── app.php
│   ├── services.php                          # Lingo API config here
│   └── ...
├── database/
│   └── database.sqlite                       # SQLite database
├── resources/
│   ├── views/
│   │   ├── dashboard.blade.php               # Main UI
│   │   └── welcome.blade.php
│   ├── css/app.css                           # Tailwind entry
│   └── js/app.js                             # JavaScript entry
├── routes/
│   └── web.php                               # All routes defined here
├── storage/
│   ├── app/
│   │   ├── private/
│   │   │   └── sitreps/                      # JSON SITREPs stored here
│   │   └── public/
│   │       └── incidents/                    # Uploaded media files
│   │           ├── images/
│   │           └── videos/
│   └── logs/
│       └── laravel.log                       # Application logs
├── .env                                      # Environment configuration
├── composer.json                             # PHP dependencies
├── package.json                              # JavaScript dependencies
└── README.md                                 # Original Laravel README
```

---

## Additional Resources

### Documentation
- **Laravel 12:** https://laravel.com/docs/12.x
- **Lingo SDK:** https://lingo.dev/docs
- **Tailwind CSS:** https://tailwindcss.com/docs
- **Leaflet Maps:** https://leafletjs.com/reference.html

### APIs Used
- **Nominatim (OSM):** https://nominatim.org/release-docs/develop/api/Overview/
- **Lingo Translation:** https://lingo.dev

### Support Contacts
- **Technical Issues:** Check `storage/logs/laravel.log`
- **Lingo API:** support@lingo.dev
- **Repository:** [Add GitHub/GitLab URL]

---

## Quick Reference Commands

```bash
# Development
composer run dev              # Start all services (recommended)
php artisan serve            # Just web server
php artisan queue:work       # Process background jobs
php artisan pail             # View live logs

# Maintenance
php artisan config:clear     # Clear config cache
php artisan cache:clear      # Clear application cache
php artisan view:clear       # Clear compiled views
php artisan route:list       # List all routes

# Storage
php artisan storage:link     # Link public storage
rm -rf storage/app/private/sitreps/*  # Clear all SITREPs

# Testing
php artisan test             # Run test suite
composer run test            # Alternative test command

# Build
npm run build                # Production build
npm run dev                  # Development build with HMR
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | Nov 15, 2025 | Initial implementation for Lingo Hackathon |

---

**End of Handoff Document**

For questions or clarifications, please refer to the inline code documentation or create an issue in the repository.
