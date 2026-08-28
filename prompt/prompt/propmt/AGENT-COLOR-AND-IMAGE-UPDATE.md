# 🎨 AGENT: UPDATE COLORS & ADD CAROUSEL IMAGES

## Quick Customization Task

The website is ready. Just need 2 quick updates:

---

## ✅ TASK 1: Change Color Scheme

**Find this in coconut-festival-webpage.html (line ~60):**

```css
:root {
    --color-primary: #d4a574;      /* Gold */
    --color-secondary: #2c3e50;    /* Navy */
    --color-accent: #e8b4a0;       /* Coral */
    --color-dark: #1a1a1a;         /* Black */
    --color-light: #fafaf8;        /* Cream */
}
```

**Replace with:**

```css
:root {
    --color-primary: #0066cc;      /* Blue */
    --color-secondary: #0066cc;    /* Blue */
    --color-accent: #0052a3;       /* Darker Blue */
    --color-dark: #000000;         /* Black */
    --color-light: #ffffff;        /* White */
}
```

**Also update these background colors:**

Find all `background: linear-gradient(135deg, #fff...` and replace with:
```css
background: #ffffff;  /* Pure white */
```

Find all `background: linear-gradient(135deg, #fef...` and replace with:
```css
background: #f0f8ff;  /* Light blue-white */
```

---

## ✅ TASK 2: Add 8-9 Festive Images to Carousel

The carousel is in the payment section (look for `<canvas id="crowdCanvas">`).

**Create an image carousel section. Add this before the crowd canvas:**

```html
<!-- FESTIVE IMAGE CAROUSEL -->
<div class="festive-carousel" style="margin-bottom: 4rem;">
    <h3 style="text-align: center; margin-bottom: 2rem; color: var(--color-dark);">
        Festival Highlights
    </h3>
    
    <div class="carousel-wrapper" style="
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding: 1rem;
        border-radius: 20px;
        background: #f0f8ff;
        scroll-behavior: smooth;
    ">
        <img src="/images/festival/1-coconut-drinks.jpg" alt="Coconut Drinks" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/2-live-band.jpg" alt="Live Band Performance" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/3-dancers.jpg" alt="Dancers" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/4-beachwear.jpg" alt="Black Fragrance Beachwear" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/5-runway.jpg" alt="Fashion Runway" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/6-buffet.jpg" alt="Buffet Food" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/7-beach-sunset.jpg" alt="Beach Sunset" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/8-crowd.jpg" alt="Festival Crowd" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/images/festival/9-coconut-theme.jpg" alt="Coconut Theme Decoration" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
    </div>
</div>
```

---

## 📸 IMAGES YOU NEED (9 total)

Create/get these 9 images and save in `public/images/festival/`:

1. **1-coconut-drinks.jpg** - Coconut drinks/beverages (festive)
2. **2-live-band.jpg** - Live band performance
3. **3-dancers.jpg** - Dancers performing
4. **4-beachwear.jpg** - Black Fragrance beachwear collection
5. **5-runway.jpg** - Fashion runway show
6. **6-buffet.jpg** - Buffet food display
7. **7-beach-sunset.jpg** - Beach/sunset view at Iceland Beach
8. **8-crowd.jpg** - Festival crowd/people enjoying
9. **9-coconut-theme.jpg** - Coconut theme decorations

**Image Requirements:**
- Size: 280px × 200px (or larger, will scale)
- Format: JPG or PNG
- Quality: High quality, festive/professional
- Style: Photos or professional graphics

---

## 🎨 Optional: Update Other Colors

If you want to update other blue shades:

```
Light backgrounds: #f0f8ff (Alice Blue)
Dark blue (hover): #0052a3
Text: #000000 (Black)
White: #ffffff
Light gray: #f5f5f5
```

---

## ✅ Checklist

- [ ] Update CSS colors in `:root` section
- [ ] Replace all background gradients with white/#f0f8ff
- [ ] Add carousel HTML code
- [ ] Gather 9 festive images
- [ ] Save images in `public/images/festival/` folder
- [ ] Test carousel (scroll horizontally)
- [ ] Check colors look good (blue + white + black)
- [ ] Test on mobile
- [ ] Looks festive? ✅

---

## ⏱️ Time to Complete: 30-45 minutes

- Colors: 10 minutes
- Carousel code: 5 minutes  
- Adding images: 15-20 minutes
- Testing: 5-10 minutes

---

## 💡 Tips

- Make carousel images festive/vibrant
- Use high-quality images (professional photos)
- Keep consistent image style
- Images should tell the festival story
- Hover effect (scale up) works automatically
- Mobile: swipe horizontally to see more images

---

**That's it! Once done, the page will be blue & white with a festive image carousel.** 🎉

