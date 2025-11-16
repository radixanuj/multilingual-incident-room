# System Update Documentation
**Date:** November 16, 2025  
**Update Version:** 2.0 - Gemini AI Integration  
**Previous Version:** 1.0 - Basic Multilingual Processing

---

## 🎯 Overview

The Multilingual Incident Room system has been completely overhauled with a new AI-powered workflow:

**NEW WORKFLOW:**
```
Upload → Lingo Normalize → Gemini Analyze → Store Canonical EN → Lingo Fan-out to 10+ Languages
```

**Previous Workflow:**
```
Upload → Translate → Extract → Cluster → Synthesize → Translate (3 languages)
```

---

## 🚀 Major Changes

### 1. **AI-Powered Incident Analysis (Gemini Integration)**

**New Service: `GeminiAnalysisService.php`**
- Analyzes multimodal evidence (text, images, videos)
- Generates structured incident reports following law enforcement format
- Handles uncertainty with "Not specified" responses
- No hallucination - only factual analysis

**AI Prompt Features:**
- Understands mixed-language inputs
- Generates neutral, factual reports
- Follows strict output format:
  - Incident Title
  - Date & Time
  - Location
  - Summary (3-4 lines)
  - Chronological Description
  - People Involved (Victims, Suspects, Witnesses)
  - Actions Taken (Emergency, Police, Medical)

**Configuration Required:**
```bash
# Add to .env file
GEMINI_API_KEY=your_gemini_api_key_here
```

Get your API key from: https://aistudio.google.com/apikey

---

### 2. **Updated Form Fields**

**Added:**
- `incident_title` (required) - User provides incident name
- `incident_datetime` (optional) - Specific date/time of incident

**Removed:**
- `source_type` dropdown - System now auto-infers from uploaded evidence

**UI Changes:**
- New incident title input field at top of form
- Renamed "Incident Time" to "Incident Date & Time"
- Language dropdown expanded to 13+ languages
- Source type removed (auto-detected from files)

---

### 3. **Expanded Language Support**

**From 3 to 13 Languages:**

Original:
- English (en)
- Hindi (hi)
- Bengali (bn)

Added:
- Urdu (ur)
- Tamil (ta)
- Telugu (te)
- French (fr)
- Spanish (es)
- German (de)
- Chinese (zh)
- Arabic (ar)
- Portuguese (pt)
- Japanese (ja)
- Russian (ru)

**Implementation:**
- `LingoSdkService` updated with `getDefaultTargetLanguages()` method
- All SITREPs now translated to 13 languages automatically
- UI supports all 13 languages (language selector can be extended)

---

### 4. **New SITREP Structure**

**Previous Structure:**
```json
{
  "summary": {"en": "...", "hi": "...", "bn": "..."},
  "details": {
    "bullets_en": [...],
    "bullets_hi": [...],
    "bullets_bn": [...]
  }
}
```

**New Structure:**
```json
{
  "summary": {"en": "...", "hi": "...", "bn": "...", "fr": "...", ...},
  "description": {"en": "...", "hi": "...", "bn": "...", ...},
  "people_involved": {
    "victims": [{"en": "...", "hi": "...", ...}],
    "suspects": [{"en": "...", "hi": "...", ...}],
    "witnesses": [{"en": "...", "hi": "...", ...}]
  },
  "actions_taken": {
    "emergency_response": [{"en": "...", "hi": "...", ...}],
    "police_actions": [{"en": "...", "hi": "...", ...}],
    "medical_interventions": [{"en": "...", "hi": "...", ...}]
  },
  "details": {
    "bullets_en": [...],
    "bullets_hi": [...],
    ...all 13 languages...
  }
}
```

---

### 5. **Enhanced Progress Tracking**

**New Progress Bar UI:**
```
┌─────────────────────────────────────┐
│ Processing Incident Report     45% │
├─────────────────────────────────────┤
│ ✓ Uploading evidence                │
│ ✓ Normalizing content (Lingo)       │
│ ⟳ AI Analysis (Gemini)              │
│   Translating to 10+ languages      │
│   Generating SITREP                 │
└─────────────────────────────────────┘
```

**Implementation:**
- Visual progress bar with gradient animation
- Step-by-step status indicators
- Smooth transitions between steps
- Auto-hides after completion

---

### 6. **New Services & Classes**

**Created:**
1. `app/Services/GeminiAnalysisService.php` - Gemini API integration
2. `app/Services/SitrepSynthesizerV2.php` - New SITREP generation for Gemini reports

**Updated:**
1. `app/Services/ReportProcessingPipeline.php` - Added `processIncidentWithGemini()` method
2. `app/Services/LingoSdkService.php` - Added support for 13 languages
3. `app/Http/Controllers/IncidentRoomController.php` - New workflow integration

---

## 📝 File Changes Summary

### Modified Files
- `resources/views/dashboard.blade.php` - Form fields, progress bar, UI enhancements
- `app/Services/ReportProcessingPipeline.php` - New Gemini workflow
- `app/Services/LingoSdkService.php` - 13-language support
- `app/Http/Controllers/IncidentRoomController.php` - New submission workflow
- `config/services.php` - Gemini API configuration
- `.env.example` - Gemini API key placeholder

### New Files
- `app/Services/GeminiAnalysisService.php` - AI analysis engine
- `app/Services/SitrepSynthesizerV2.php` - New SITREP generation

---

## 🔧 Setup Instructions

### 1. Update Environment Variables

```bash
# Copy new .env.example if starting fresh
cp .env.example .env

# Add these keys to your .env file:
LINGO_API_KEY=your_lingo_api_key_here
GEMINI_API_KEY=your_gemini_api_key_here
```

### 2. Install Dependencies (if needed)

```bash
composer install
npm install
```

### 3. Rebuild Frontend Assets

```bash
npm run build
```

### 4. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 5. Test the System

```bash
# Start all services
composer run dev

# Or individually:
php artisan serve
npm run dev
```

**Access:**
- Dashboard: http://localhost:8000/dashboard
- Test API: http://localhost:8000/api/incident-room/test-example

---

## 🎮 How to Use

### Submit New Incident Report

1. **Navigate to Dashboard** - http://localhost:8000/dashboard

2. **Click "Fill Report Form"**

3. **Fill Required Fields:**
   - **Incident Title** (NEW) - e.g., "Building collapse in residential area"
   - **Incident Description** - Detailed text
   - **Location** - City/area name
   - **Language** - Select from 13 options or auto-detect
   - **Incident Date & Time** (optional) - When it occurred
   - **Source Credibility** - How trustworthy is this report

4. **Upload Evidence (Optional):**
   - **Images** - Photos of the incident (up to 10MB each)
   - **Videos** - Video footage (up to 50MB each)

5. **Submit** - Watch the progress bar as it processes:
   - Uploads files
   - Normalizes text with Lingo
   - Analyzes with Gemini AI
   - Translates to 13 languages
   - Generates final SITREP

6. **View Results** - SITREP displayed with:
   - Map showing location
   - Multilingual summary
   - Detailed chronological description
   - People involved sections
   - Actions taken sections
   - Media attachments gallery

---

## 🔍 API Changes

### Form Submission Endpoint

**Endpoint:** `POST /api/incident-room/submit-form`

**New Required Parameters:**
```json
{
  "incident_title": "Building collapse in residential area",
  "raw_text": "Detailed description...",
  "location": "City, Area",
  "incident_datetime": "2025-11-16T14:30:00+05:30",
  "original_language": "en",
  "source_credibility": "medium",
  "images": [/* file uploads */],
  "videos": [/* file uploads */]
}
```

**Removed Parameters:**
- `source_type` - Now auto-inferred

**Response Structure:**
```json
{
  "incident_id": "20251116_143000_building_collapse_abc123",
  "canonical_title": "Building collapse reported...",
  "summary": {
    "en": "Summary in English",
    "hi": "हिंदी में सारांश",
    "bn": "বাংলায় সারাংশ",
    "fr": "Résumé en français",
    ...13 languages total...
  },
  "description": {
    "en": "Detailed chronological description",
    ...13 languages...
  },
  "people_involved": {
    "victims": [{en: "...", hi: "...", ...}],
    "suspects": [{en: "...", hi: "...", ...}],
    "witnesses": [{en: "...", hi: "...", ...}]
  },
  "actions_taken": {
    "emergency_response": [...],
    "police_actions": [...],
    "medical_interventions": [...]
  },
  "audit": {
    "gemini_analysis_used": true,
    "is_fallback": false,
    "created_at": "2025-11-16T14:35:00+05:30"
  }
}
```

---

## 🐛 Troubleshooting

### Issue: "Gemini API key not configured"

**Solution:**
```bash
# Add to .env
GEMINI_API_KEY=your_key_here

# Clear config
php artisan config:clear
```

### Issue: "Translation fails for some languages"

**Cause:** Lingo API rate limits or unsupported language pairs

**Solution:** System will fallback to English if translation fails. Check `storage/logs/laravel.log` for details.

### Issue: "Image upload fails"

**Cause:** File too large or memory limit

**Solution:**
```bash
# Increase PHP limits in php.ini or .env
upload_max_filesize = 50M
post_max_size = 60M
memory_limit = 512M
```

### Issue: "Progress bar stuck"

**Cause:** Gemini API timeout or network issue

**Solution:**
- Check internet connection
- Verify Gemini API key is valid
- Check `storage/logs/laravel.log` for error details
- System will use fallback report if Gemini fails

---

## 🔒 Security Notes

1. **API Keys** - Never commit .env file to version control
2. **File Uploads** - System validates file types and sizes
3. **Content Safety** - Gemini safety filters set to BLOCK_NONE for emergency context
4. **Input Validation** - All form fields validated server-side

---

## 📊 Performance Considerations

**Processing Time:**
- Text-only: ~2-5 seconds
- With images: ~5-10 seconds
- With videos: ~10-20 seconds (depends on size)

**Bottlenecks:**
1. Gemini API response time (~2-5 seconds)
2. Lingo translation for 13 languages (~3-5 seconds)
3. Image base64 encoding for Gemini

**Optimizations:**
- Increased PHP timeout to 300 seconds
- Memory limit set to 512M
- Async processing recommended for production

---

## 🚧 Known Limitations

1. **Video Analysis:** Gemini doesn't process video content directly yet. System uses filename and metadata only.

2. **Language Quality:** Translation quality depends on Lingo API. Some language pairs may be better than others.

3. **Fallback Reports:** If Gemini fails, system generates basic report without AI analysis.

4. **Geocoding:** Still depends on OpenStreetMap Nominatim (rate limits apply).

5. **No Database:** SITREPs still stored as JSON files. Consider database migration for production.

---

## 🎯 Future Enhancements

### Short Term
- Video transcription for Gemini analysis
- Real-time progress updates via WebSockets
- Batch processing for multiple incidents

### Medium Term
- Database integration for SITREPs
- Advanced filtering and search
- Export to PDF with multilingual support

### Long Term
- Integration with emergency services APIs
- Mobile app for field reporting
- Real-time collaboration features

---

## 📞 Support

**Documentation:**
- Original HANDOFF.md - System overview
- This UPDATE.md - Recent changes

**Logs:**
- `storage/logs/laravel.log` - Application logs
- `php artisan pail` - Live log viewer

**Testing:**
- Use "Process Example Reports" button for quick testing
- Check SITREP storage: `storage/app/private/sitreps/`

---

## ✅ Verification Checklist

After updating, verify:

- [ ] Dashboard loads without errors
- [ ] Form shows incident_title field
- [ ] Source type dropdown removed
- [ ] Progress bar displays during processing
- [ ] Gemini API key configured in .env
- [ ] Test submission works (click "Process Example Reports")
- [ ] SITREP shows in 13 languages
- [ ] Map displays correctly
- [ ] Image/video uploads work
- [ ] Logs show Gemini analysis messages

---

**End of Update Documentation**
