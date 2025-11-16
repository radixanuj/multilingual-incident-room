# AI-Powered Multimodal Incident Analysis

## Overview

This system uses **Google Gemini AI** with advanced multimodal capabilities to analyze incident evidence and generate comprehensive reports. The AI can "see" images, "watch" videos, and read text to build a complete story of what happened.

## What the System Analyzes

### 📸 **Image Analysis**
The AI examines images to extract:
- **People**: Count, physical descriptions, clothing, injuries, actions/poses
- **Objects**: Weapons, vehicles (type, color, license plates), tools, evidence items
- **Scene Details**: Location type (indoor/outdoor), building features, damage, environmental conditions
- **Visible Text**: Street signs, license plates, documents, graffiti, any text in the image
- **Evidence**: Visual indicators of injuries, property damage, criminal activity, safety hazards

**Example**: If you upload a photo of a car accident, the AI will:
- Identify vehicle types and colors
- Read license plate numbers (if visible)
- Count people involved
- Assess visible damage
- Note location details (street signs, landmarks)
- Identify injuries or safety hazards

### 🎥 **Video Analysis**
The AI watches videos frame-by-frame to understand:
- **Event Sequence**: What happened first, next, and last (chronological flow)
- **People & Actions**: All individuals involved and what they're doing
- **Dialogue/Audio**: Speech or sounds (if transcripts available)
- **Time Indicators**: Clocks, timestamps, daylight changes
- **Motion**: Movement patterns, chase sequences, escape routes
- **Evidence**: Actions that indicate intent, aggression, or response

**Example**: If you upload a security camera video, the AI will:
- Track each person's movements
- Identify suspicious actions
- Note when events occurred
- Describe the sequence of events
- Extract vehicle movements

### 📝 **Text Analysis**
The AI processes written descriptions:
- User-provided incident descriptions
- Witness statements
- Reporter notes
- **Multilingual support**: Understands text in multiple languages

### 🧩 **Evidence Integration**
The AI combines ALL sources to create a complete narrative:
- Cross-references visual evidence with text descriptions
- Identifies details in images/videos NOT mentioned in text
- Notes discrepancies between sources
- Builds a coherent chronological story

**Example Scenario**:
```
Text says: "Two people had a fight"

Image shows: 
- Male in blue shirt with visible injury to face
- Female in red jacket holding phone (recording)
- Broken glass on ground
- Street sign reading "Main St & 5th Ave"

AI Report includes:
✅ Two individuals involved
✅ One victim (male, blue shirt, facial injury)
✅ One witness (female, red jacket, documenting with phone)
✅ Property damage (broken glass)
✅ Precise location (Main St & 5th Ave intersection)
```

## Workflow

```
User Upload
    ↓
1. Text Normalization (Lingo translates to English if needed)
    ↓
2. AI Analysis (Gemini examines text + images + videos)
    ↓
3. Report Generation (Structured incident report in English)
    ↓
4. Translation (Lingo translates to Hindi + other languages)
    ↓
5. Display (Multilingual SITREP available)
```

## Report Structure

The AI generates a structured report containing:

### 1. **Incident Title**
- Auto-generated descriptive title based on all evidence
- Example: "Two-vehicle collision with injuries at Main Street intersection"

### 2. **Date & Time**
- Uses user-provided datetime OR
- Infers from visual evidence (clocks, daylight, shadows) OR
- States "Not specified"

### 3. **Location**
- Uses user-provided location OR
- Extracts from images (street signs, landmarks, building names) OR
- States "Not specified"

### 4. **Summary**
- 3-4 sentence overview of the incident
- Synthesizes all evidence into key points

### 5. **Description**
- Chronological narrative of events
- Combines visual evidence with text descriptions
- Notes what happened first, next, and last

### 6. **People Involved**
- **Victims**: Description, injuries, role
- **Suspects**: Description, alleged actions, relationship to incident
- **Witnesses**: Description, what they reported/documented

### 7. **Actions Taken**
- **Emergency Response**: Ambulance, fire, first aid
- **Police Actions**: Arrival, securing scene, questioning, arrests
- **Medical Interventions**: Hospital transport, treatment

## Key Features

### ✅ **Visual Intelligence**
- The AI "sees" what's in images and videos
- Extracts information humans might miss
- Reads text visible in images (OCR capability)

### ✅ **No Hallucination**
- If something is unclear, AI states "Not specified"
- Does not invent details
- Uses neutral language ("alleged", "appears to", "reported")

### ✅ **Multilingual Input**
- Accepts text in any language
- Translates to English for analysis
- Final report available in English + Hindi

### ✅ **Comprehensive Analysis**
- Combines text + images + videos into one story
- Notes conflicts between evidence sources
- Prioritizes observed facts over assumptions

## Usage Tips

### 📋 **What to Include**

**Text Description:**
- Brief description of what happened
- Any witness statements
- Reporter's observations

**Images:**
- Photos of the scene
- Pictures of people involved
- Damage to property/vehicles
- Any visible injuries
- Documents, signs, or text relevant to the incident

**Videos:**
- Security camera footage
- Witness recordings
- Dashcam videos
- Any video showing the sequence of events

### 🎯 **Best Practices**

1. **Upload Multiple Evidence Types**: More evidence = more complete analysis
2. **Clear Images**: Higher quality images yield better identification
3. **Multiple Angles**: Different perspectives help build complete picture
4. **Include Context**: Brief text description helps AI understand what to look for
5. **Upload Videos**: Video analysis reveals event sequences text cannot capture

### ⚠️ **Limitations**

- Cannot analyze audio-only files (must have visual component)
- Video files must be under 50MB
- Image files must be under 10MB
- Gemini API key required (set in `.env`)
- Internet connection needed for AI analysis

## Technical Details

**AI Model**: Google Gemini 2.0 Flash Exp
- Multimodal capabilities (text, image, video)
- 120-second timeout for complex analysis
- 2048 token output limit for reports

**Supported Formats**:
- Images: JPEG, PNG, GIF, WebP
- Videos: MP4, MOV, AVI, MKV, WebM

**Processing Time**:
- Images: ~5-10 seconds
- Videos: ~30-60 seconds (depending on length)
- Total: Usually under 2 minutes

## Privacy & Security

- All evidence is processed securely
- Images/videos sent to Google Gemini API over HTTPS
- Files stored locally in `storage/app/private/`
- Reports saved as JSON in `storage/app/private/sitreps/`
- No data retention on Google's servers (per Gemini API terms)

## Configuration

Add to `.env`:
```env
GEMINI_API_KEY=your_gemini_api_key_here
LINGO_API_KEY=your_lingo_api_key_here
```

Get Gemini API key: https://aistudio.google.com/apikey

## Example Use Cases

### 🚗 **Traffic Accident**
Upload: Dashcam video, photos of damage, description
AI Extracts: Vehicle types, license plates, impact point, injuries, sequence of events

### 🏠 **Property Damage**
Upload: Photos of damage, witness statement
AI Extracts: Extent of damage, cause indicators, location details, time estimation

### 👮 **Crime Scene**
Upload: Security footage, photos, police notes
AI Extracts: Suspect descriptions, actions, victims, timeline, evidence items

### 🔥 **Emergency Response**
Upload: Photos, responder notes, witness video
AI Extracts: Hazards, people affected, actions taken, location, time

---

**The AI analyzes what it sees, not what it assumes. Upload comprehensive evidence for the most accurate reports.**
