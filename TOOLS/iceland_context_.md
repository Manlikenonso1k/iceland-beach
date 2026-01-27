# Iceland Beach Website - AI Context File

**Last Updated:** January 27, 2026  
**Project Status:** Hero Video Section - In Progress

---

## 🌐 Server & Hosting Details

- **Website URL:** https://icelandbeach.com
- **FTP Server:** 92.112.197.130
- **FTP User:** u519226541.icelandbeach.com
- **Root Directory:** `/public_html/`

---

## 📁 Project Structure

```
public_html/
├── index.php                 # Homepage with hero video
├── includes/
│   ├── header.php           # Navigation, CSS links
│   └── footer.php           # Footer, JS scripts
├── static/
│   ├── styles/
│   │   └── style.css        # Main stylesheet
│   ├── scripts/
│   │   └── script.js        # Main JavaScript
│   ├── images/
│   │   └── hero-poster.jpg  # Video poster image
│   └── videos/
│       └── icelandbeachhero.mp4  # 32MB (needs compression to 3-5MB)
└── .htaccess                # URL rewriting rules
```

---

## 🎨 Tech Stack

- **Backend:** PHP 8.x
- **Frontend:** Bootstrap 5.3.3
- **Database:** MySQL
- **Fonts:** "Self Modern" serif, Font Awesome 6.7.2
- **Server:** Apache with mod_rewrite

---

## 🎬 Hero Video Section - Current Implementation

### HTML Location
File: `index.php` (after header include)

### Video Details
- **File:** `icelandbeachhero.mp4`
- **Current Size:** 32MB (TOO LARGE)
- **Duration:** 38 seconds
- **Source:** DJI Drone footage at 720p
- **Target Size:** 3-5MB compressed
- **URL:** https://icelandbeach.com/static/videos/icelandbeachhero.mp4

### Design Specifications

**Desktop (1024px+):**
- Video height: 100vh (full viewport)
- Text overlay visible at bottom
- Gradient background on text
- "Play Video Once" button: 15px from bottom-right
- Description text: 15px from bottom

**Tablet (768px - 1023px):**
- Video height: 70vh
- Text overlay hidden
- Button visible

**Mobile (< 768px):**
- Video height: 60vh
- Text overlay hidden
- Button visible, smaller size

**Typography:**
- Font family: "Self Modern", serif
- Desktop: 20px (22px on large screens)
- Mobile: 18px
- Font weight: 400
- Line height: 1.6

**Colors:**
- Background: #002a34 (dark teal)
- Text: #f6f1ed (off-white)
- Button background: rgba(246, 241, 237, 0.9)
- Button text: #002a34

---

## ✅ Completed Tasks

- [x] Created hero video section HTML structure
- [x] Added responsive CSS for mobile/tablet/desktop
- [x] Positioned "Play Video Once" button
- [x] Added text overlay with gradient
- [x] Set video to loop continuously
- [x] Added poster image support
- [x] Fixed .htaccess for video file access
- [x] Changed file permissions to 755

---

## 🔧 Current Issues & To-Do

### High Priority
- [ ] **COMPRESS VIDEO:** 32MB → 3-5MB using HandBrake
  - Settings: H.264, 800 kbps bitrate, 1280x720
  - Target: Under 5MB for fast loading
- [ ] **Create poster image:** Screenshot from video, save as hero-poster.jpg
- [ ] **Test on real mobile devices:** Verify 60vh looks good

### Medium Priority
- [ ] Add JavaScript for video modal (full-screen playback)
- [ ] Optimize poster image (compress to <200KB)
- [ ] Test video playback on Safari (iOS)
- [ ] Add loading animation while video buffers

### Low Priority
- [ ] Consider using WebM format for better compression
- [ ] Add fallback image for browsers that don't support video
- [ ] Implement lazy loading for mobile data savings

---

## 📝 Code Snippets to Remember

### .htaccess Video Rules
```apache
# Don't rewrite requests for static files
RewriteCond %{REQUEST_URI} ^/static/ [OR]
RewriteCond %{REQUEST_URI} \.(mp4|webm|ogg|avi|mov|jpg|jpeg|png|gif|svg|css|js|woff|woff2|ttf|eot)$ [NC]
RewriteRule ^ - [L]
```

### CSS File Location
`static/styles/style.css` - Hero section styles start at line [INSERT LINE NUMBER]

### Video Compression Command (FFmpeg)
```bash
ffmpeg -i icelandbeachhero.mp4 -vcodec libx264 -crf 28 -preset slow -vf scale=1280:720 -an output.mp4
```

---

## 🤖 AI Session Notes

### Session 1 (Jan 23, 2026)
- Fixed FTP vs HTTPS URL confusion
- Resolved 403 Forbidden error on video
- Created hero section with responsive design
- Fixed button positioning (15px from bottom)
- Added loop functionality

### Common AI Instructions
When working with AI on this project, provide:
1. This context file
2. Specific file paths
3. Current screen size being tested
4. Error messages (if any)

---

## 💡 Lessons Learned

1. **FTP URLs don't work in browsers** - Always use HTTPS
2. **File naming matters** - No spaces, use hyphens
3. **DJI videos are HUGE** - Always compress before uploading
4. **Mobile-first CSS** - Start with small screens, scale up
5. **.htaccess order matters** - Static files must bypass PHP rewrites

---

## 📞 Contact & Support

- **Developer:** [Your Name]
- **Location:** Ibadan, Oyo State, Nigeria
- **Email:** [Your Email]

---

## 🔗 Useful Resources

- HandBrake: https://handbrake.fr/
- Bootstrap Docs: https://getbootstrap.com/docs/5.3/
- FreeConvert Video Compressor: https://www.freeconvert.com/video-compressor
- CloudConvert: https://cloudconvert.com/
- Deployment fix prompts log: [TOOLS/prompts.md](TOOLS/prompts.md)

---

**END OF CONTEXT FILE**