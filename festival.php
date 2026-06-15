<?php 
$bodyClass = 'festival-page';
require_once "includes/header.php"; 
?>

<!-- Tailwind Integration & Fonts -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<!-- Google Fonts for Redesign -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<style>
    /* ============================================
       MODERN COASTAL SOPHISTICATION DESIGN SYSTEM
       ============================================ */
    
    :root {
        /* Color Palette */
        --primary: #1a3a52;
        --primary-light: #2c5aa0;
        --accent: #d4a574;
        --accent-light: #e8c9a0;
        --secondary: #e8a89d;
        --background: #fafaf8;
        --card-bg: #ffffff;
        --text-dark: #2c2c2c;
        --text-muted: #666666;
        --border-color: #d4ccc4;
        --shadow-subtle: 0 2px 8px rgba(0, 0, 0, 0.08);
        --shadow-medium: 0 8px 24px rgba(0, 0, 0, 0.12);
        --shadow-hover: 0 12px 32px rgba(0, 0, 0, 0.15);
        
        /* Spacing */
        --spacing-xs: 0.5rem;
        --spacing-sm: 1rem;
        --spacing-md: 1.5rem;
        --spacing-lg: 2rem;
        --spacing-xl: 3rem;
        --spacing-2xl: 5rem;
        
        /* Radius */
        --radius-sm: 6px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 20px;
    }
    
    .festival-redesign-wrapper {
        font-family: 'Inter', sans-serif;
        color: var(--text-dark);
        background-color: var(--background);
        line-height: 1.6;
    }
    
    .festival-redesign-wrapper h1, .festival-redesign-wrapper h2, .festival-redesign-wrapper h3, .festival-redesign-wrapper h4, .festival-redesign-wrapper h5, .festival-redesign-wrapper h6 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--primary);
        line-height: 1.2;
    }
    
    .festival-redesign-wrapper h1 { font-size: 3.5rem; letter-spacing: -0.02em; }
    .festival-redesign-wrapper h2 { font-size: 2.5rem; letter-spacing: -0.01em; }
    .festival-redesign-wrapper h3 { font-size: 1.75rem; }
    .festival-redesign-wrapper h4 { font-size: 1.25rem; }
    
    .festival-redesign-wrapper p { color: var(--text-dark); }
    
    .festival-redesign-wrapper a {
        color: var(--primary);
        text-decoration: none;
        transition: color 180ms ease-out;
    }
    
    .festival-redesign-wrapper a:hover {
        color: var(--accent);
    }
    
    .festival-redesign-wrapper button, .festival-redesign-wrapper .btn-ticket {
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        border: none;
        transition: all 180ms cubic-bezier(0.23, 1, 0.32, 1);
    }
    
    .festival-redesign-wrapper button:active, .festival-redesign-wrapper .btn-ticket:active {
        transform: scale(0.97);
    }
    
    .festival-redesign-wrapper .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    @media (min-width: 640px) {
        .festival-redesign-wrapper .container {
            padding: 0 1.5rem;
        }
    }
    
    @media (min-width: 1024px) {
        .festival-redesign-wrapper .container {
            padding: 0 2rem;
        }
    }
    
    /* ============================================
       HERO SECTION
       ============================================ */
    
    .festival-redesign-wrapper .hero {
        position: relative;
        min-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .festival-redesign-wrapper .hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url('static/redesign%20images/hero-beach-sunset.png');
        background-size: cover;
        background-position: center;
        z-index: 0;
    }
    
    .festival-redesign-wrapper .hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.4), transparent);
        z-index: 1;
    }
    
    .festival-redesign-wrapper .hero-content {
        position: relative;
        z-index: 10;
        max-width: 56rem;
        margin: 0 auto;
        padding: 5rem 1rem;
        text-align: left;
    }
    
    .festival-redesign-wrapper .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 9999px;
        margin-bottom: 1.5rem;
    }
    
    .festival-redesign-wrapper .hero-badge span {
        font-size: 0.875rem;
        font-weight: 500;
        color: white;
        letter-spacing: 0.05em;
    }
    
    .festival-redesign-wrapper .hero h1 {
        color: white;
        margin-bottom: 1.5rem;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .festival-redesign-wrapper .hero h1 .accent {
        color: var(--accent);
    }
    
    .festival-redesign-wrapper .hero p {
        font-size: 1.25rem;
        color: rgba(255, 255, 255, 0.9);
        max-width: 32rem;
        margin-bottom: 2.5rem;
        text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
    }
    
    .festival-redesign-wrapper .hero-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .festival-redesign-wrapper .btn-primary {
        background: var(--accent);
        color: var(--primary);
        padding: 1rem 2rem;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 180ms ease-out;
    }
    
    .festival-redesign-wrapper .btn-primary:hover {
        background: var(--accent-light);
    }
    
    .festival-redesign-wrapper .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        padding: 1rem 2rem;
        border-radius: var(--radius-sm);
        border: 2px solid white;
        font-weight: 600;
        font-size: 1rem;
        transition: all 180ms ease-out;
    }
    
    .festival-redesign-wrapper .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    @media (max-width: 768px) {
        .festival-redesign-wrapper .hero h1 {
            font-size: 2.5rem;
        }
        
        .festival-redesign-wrapper .hero p {
            font-size: 1rem;
        }
        
        .festival-redesign-wrapper .hero-buttons {
            flex-direction: column;
        }
        
        .festival-redesign-wrapper .btn-primary, .festival-redesign-wrapper .btn-secondary {
            width: 100%;
            text-align: center;
            justify-content: center;
        }
    }
    
    /* ============================================
       OVERVIEW SECTION
       ============================================ */
    
    .festival-redesign-wrapper .overview {
        padding: 5rem 0;
        background: var(--card-bg);
    }
    
    .festival-redesign-wrapper .overview-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        align-items: center;
    }
    
    .festival-redesign-wrapper .overview-content h2 {
        margin-bottom: 1.5rem;
    }
    
    .festival-redesign-wrapper .overview-content p {
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
        line-height: 1.8;
    }
    
    .festival-redesign-wrapper .feature-list {
        list-style: none;
        padding: 0;
    }
    
    .festival-redesign-wrapper .feature-list li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        font-size: 1.125rem;
    }
    
    .festival-redesign-wrapper .feature-icon {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        background: rgba(212, 165, 116, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .festival-redesign-wrapper .feature-icon::before {
        content: '★';
        color: var(--accent);
        font-size: 1rem;
    }
    
    .festival-redesign-wrapper .overview-images {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .festival-redesign-wrapper .overview-images img {
        width: 100%;
        height: 16rem;
        object-fit: cover;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-medium);
    }
    
    .festival-redesign-wrapper .overview-images img:last-child {
        margin-top: 2rem;
    }
    
    @media (max-width: 768px) {
        .festival-redesign-wrapper .overview-grid {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        .festival-redesign-wrapper .overview-images {
            grid-template-columns: 1fr;
        }
        
        .festival-redesign-wrapper .overview-images img:last-child {
            margin-top: 0;
        }
    }
    
    /* ============================================
       TICKETING SECTION
       ============================================ */
    
    .festival-redesign-wrapper .ticketing {
        padding: 6rem 0;
        background: var(--primary);
        color: white;
    }
    
    .festival-redesign-wrapper .ticketing-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .festival-redesign-wrapper .ticketing-header h2 {
        color: white;
        margin-bottom: 1rem;
    }
    
    .festival-redesign-wrapper .ticketing-header p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.25rem;
        max-width: 32rem;
        margin: 0 auto;
    }
    
    .festival-redesign-wrapper .tickets-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .festival-redesign-wrapper .ticket-card {
        background: var(--card-bg);
        color: var(--primary);
        padding: 2rem;
        border-radius: var(--radius-lg);
        position: relative;
        overflow: hidden;
        transition: all 200ms cubic-bezier(0.23, 1, 0.32, 1);
        border: 1px solid var(--border-color);
    }
    
    .festival-redesign-wrapper .ticket-card:hover {
        transform: translateY(-1rem);
        box-shadow: var(--shadow-hover);
    }
    
    .festival-redesign-wrapper .ticket-card.highlight {
        background: var(--accent);
        color: var(--primary);
        transform: translateY(-1rem);
        box-shadow: 0 20px 40px rgba(212, 165, 116, 0.3);
        border: 2px solid var(--accent);
    }
    
    .festival-redesign-wrapper .ticket-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: white;
        color: var(--primary);
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 0 0 0 0.75rem;
    }
    
    .festival-redesign-wrapper .ticket-card h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: inherit;
    }
    
    .festival-redesign-wrapper .ticket-price {
        font-size: 2.25rem;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 1rem;
    }
    
    .festival-redesign-wrapper .ticket-card.highlight .ticket-price {
        color: white;
    }
    
    .festival-redesign-wrapper .ticket-note {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }
    
    .festival-redesign-wrapper .ticket-features {
        list-style: none;
        margin-bottom: 2rem;
        padding: 0;
    }
    
    .festival-redesign-wrapper .ticket-features li {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
    }
    
    .festival-redesign-wrapper .ticket-features li::before {
        content: '★';
        color: var(--accent);
        font-size: 0.75rem;
    }
    
    .festival-redesign-wrapper .btn-ticket {
        width: 100%;
        padding: 0.75rem;
        border-radius: var(--radius-sm);
        font-weight: 600;
        background: var(--primary);
        color: white;
        transition: all 180ms ease-out;
    }
    
    .festival-redesign-wrapper .btn-ticket:hover {
        background: var(--primary-light);
    }
    
    .festival-redesign-wrapper .ticket-card.highlight .btn-ticket {
        background: white;
        color: var(--primary);
    }
    
    .festival-redesign-wrapper .ticket-card.highlight .btn-ticket:hover {
        background: var(--background);
    }
    
    @media (max-width: 1024px) {
        .festival-redesign-wrapper .tickets-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .festival-redesign-wrapper .tickets-grid {
            grid-template-columns: 1fr;
        }
        
        .festival-redesign-wrapper .ticket-card.highlight {
            transform: none;
        }
    }
    
    /* ============================================
       HIGHLIGHTS SECTION
       ============================================ */
    
    .festival-redesign-wrapper .highlights {
        padding: 5rem 0;
        background: var(--card-bg);
    }
    
    .festival-redesign-wrapper .highlights-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .festival-redesign-wrapper .highlights-header h2 {
        margin-bottom: 0;
    }
    
    .festival-redesign-wrapper .highlights-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
    }
    
    .festival-redesign-wrapper .highlight-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 2rem;
        border-radius: var(--radius-lg);
        transition: all 200ms cubic-bezier(0.23, 1, 0.32, 1);
    }
    
    .festival-redesign-wrapper .highlight-card:hover {
        transform: translateY(-0.25rem);
        box-shadow: var(--shadow-medium);
    }
    
    .festival-redesign-wrapper .highlight-icon {
        width: 4rem;
        height: 4rem;
        background: rgba(212, 165, 116, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 2rem;
    }
    
    .festival-redesign-wrapper .highlight-card h3 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .festival-redesign-wrapper .highlight-card p {
        font-size: 1rem;
        margin-bottom: 1.5rem;
        line-height: 1.8;
    }
    
    .festival-redesign-wrapper .highlight-detail {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--primary);
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    
    @media (max-width: 768px) {
        .festival-redesign-wrapper .highlights-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* ============================================
       ENTERTAINMENT SECTION
       ============================================ */
    
    .festival-redesign-wrapper .entertainment {
        padding: 5rem 0;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .festival-redesign-wrapper .entertainment-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        align-items: center;
    }
    
    .festival-redesign-wrapper .entertainment-content h2 {
        color: white;
        margin-bottom: 1.5rem;
    }
    
    .festival-redesign-wrapper .entertainment-content p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
        line-height: 1.8;
    }
    
    .festival-redesign-wrapper .entertainment-list {
        list-style: none;
        padding: 0;
    }
    
    .festival-redesign-wrapper .entertainment-list li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .festival-redesign-wrapper .entertainment-list li::before {
        content: '•';
        color: var(--accent);
        font-size: 1.25rem;
    }
    
    .festival-redesign-wrapper .entertainment-image {
        width: 100%;
        height: 24rem;
        object-fit: cover;
        border-radius: var(--radius-lg);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    @media (max-width: 768px) {
        .festival-redesign-wrapper .entertainment-grid {
            grid-template-columns: 1fr;
        }
        
        .festival-redesign-wrapper .entertainment-image {
            height: 16rem;
        }
    }
    
    /* ============================================
       SCHEDULE SECTION
       ============================================ */
    
    .festival-redesign-wrapper .schedule {
        padding: 5rem 0;
        background: var(--card-bg);
    }
    
    .festival-redesign-wrapper .schedule-header {
        text-align: center;
        margin-bottom: 4rem;
    }
    
    .festival-redesign-wrapper .schedule-header h2 {
        margin-bottom: 0.5rem;
    }
    
    .festival-redesign-wrapper .schedule-header p {
        color: var(--text-muted);
        font-size: 1.125rem;
    }
    
    .festival-redesign-wrapper .schedule-list {
        list-style: none;
        max-width: 56rem;
        margin: 0 auto;
        padding: 0;
    }
    
    .festival-redesign-wrapper .schedule-item {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 1.5rem;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        margin-bottom: 1rem;
        transition: all 200ms ease-out;
    }
    
    .festival-redesign-wrapper .schedule-item:hover {
        box-shadow: var(--shadow-medium);
    }
    
    .festival-redesign-wrapper .schedule-time-icon {
        width: 3rem;
        height: 3rem;
        background: rgba(212, 165, 116, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--accent);
        font-size: 1.5rem;
    }
    
    .festival-redesign-wrapper .schedule-content p:first-child {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 0.25rem;
    }
    
    .festival-redesign-wrapper .schedule-content p:last-child {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--primary);
    }
    
    /* ============================================
       CTA SECTION
       ============================================ */
    
    .festival-redesign-wrapper .cta {
        padding: 5rem 0;
        background: var(--accent);
        text-align: center;
    }
    
    .festival-redesign-wrapper .cta h2 {
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    .festival-redesign-wrapper .cta p {
        color: rgba(26, 58, 82, 0.8);
        font-size: 1.25rem;
        max-width: 32rem;
        margin: 0 auto 2rem;
    }
    
    .festival-redesign-wrapper .btn-cta {
        background: var(--primary);
        color: white;
        padding: 1rem 2rem;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 1.125rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 180ms ease-out;
    }
    
    .festival-redesign-wrapper .btn-cta:hover {
        background: var(--primary-light);
    }
    
    /* ============================================
       UTILITY CLASSES
       ============================================ */
    
    .festival-redesign-wrapper .material-icons {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 1.5rem;
        display: inline-block;
        line-height: 1;
        text-transform: none;
        letter-spacing: normal;
        word-wrap: normal;
        white-space: nowrap;
        direction: ltr;
    }
</style>

<div class="festival-redesign-wrapper">
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="material-icons" style="font-size: 1rem;">location_on</span>
                    <span>JULY 12, 2026 - ICELAND BEACH</span>
                </div>
                
                <h1>
                    Coconut Festival
                    <br>
                    <span class="accent">& Black Fragrance Pre-Launch</span>
                </h1>
                
                <p>An immersive cultural experience blending traditional coastal flavors, live entertainment, and high-end beachwear fashion.</p>
                
                <div class="hero-buttons">
                    <button class="btn-primary" onclick="document.getElementById('tickets').scrollIntoView({behavior: 'smooth'})">
                        Secure Your Spot
                        <span class="material-icons" style="font-size: 1rem;">chevron_right</span>
                    </button>
                    <button class="btn-secondary" onclick="document.getElementById('schedule').scrollIntoView({behavior: 'smooth'})">
                        View Schedule
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Overview Section -->
    <section class="overview">
        <div class="container">
            <div class="overview-grid">
                <div class="overview-content">
                    <h2>Experience the Tropics</h2>
                    <p>Join us for an unforgettable evening at Iceland Beach. The Coconut Festival is more than an event—it's a celebration of coastal lifestyle. We are also thrilled to host the exclusive pre-launch of the <strong>Black Fragrance Beachwear Collection</strong>.</p>
                    
                    <ul class="feature-list">
                        <li>
                            <div class="feature-icon"></div>
                            <span>Immersive coconut-themed culinary experience</span>
                        </li>
                        <li>
                            <div class="feature-icon"></div>
                            <span>Exclusive runway fashion showcases</span>
                        </li>
                        <li>
                            <div class="feature-icon"></div>
                            <span>Live performances by top bands and guest artists</span>
                        </li>
                    </ul>
                </div>
                
                <div class="overview-images">
                    <img src="static/redesign%20images/beachwear-fashion-showcase.png" alt="Fashion Showcase">
                    <img src="static/redesign%20images/coconut-culinary-experience.png" alt="Culinary Experience">
                </div>
            </div>
        </div>
    </section>

    <!-- Ticketing Section -->
    <section class="ticketing" id="tickets">
        <div class="container">
            <div class="ticketing-header">
                <h2>Ticketing & Packages</h2>
                <p>Choose your level of experience.</p>
            </div>
            
            <div class="tickets-grid">
                <!-- Regular Access -->
                <div class="ticket-card">
                    <h3>Regular Access</h3>
                    <div class="ticket-price">₦5,000</div>
                    <ul class="ticket-features">
                        <li>Event entry</li>
                        <li>1 complimentary coconut</li>
                        <li>Access to main stage</li>
                    </ul>
                    <a href="ticket.php" class="btn-ticket" style="display: block; text-align: center; text-decoration: none; box-sizing: border-box;">Book Now</a>
                </div>
                
                <!-- VIP Experience -->
                <div class="ticket-card highlight">
                    <div class="ticket-badge">POPULAR</div>
                    <h3>VIP Experience</h3>
                    <div class="ticket-price">₦10,000</div>
                    <ul class="ticket-features">
                        <li>Priority access</li>
                        <li>Premium seating area</li>
                        <li>1 complimentary coconut</li>
                        <li>Dedicated service</li>
                    </ul>
                    <a href="ticket.php" class="btn-ticket" style="display: block; text-align: center; text-decoration: none; box-sizing: border-box;">Book VIP</a>
                </div>
                
                <!-- Buffet Package -->
                <div class="ticket-card">
                    <h3>Buffet Package</h3>
                    <div class="ticket-price">₦25,000</div>
                    <div class="ticket-note">₦40,000 for couples</div>
                    <ul class="ticket-features">
                        <li>Coconut-inspired menu</li>
                        <li>Exclusive buffet seating</li>
                        <li>Complementary welcome drink</li>
                    </ul>
                    <a href="ticket.php" class="btn-ticket" style="display: block; text-align: center; text-decoration: none; box-sizing: border-box;">Reserve Buffet</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Highlights Section -->
    <section class="highlights">
        <div class="container">
            <div class="highlights-header">
                <h2>Festival Highlights</h2>
            </div>
            
            <div class="highlights-grid">
                <div class="highlight-card">
                    <div class="highlight-icon"><span class="material-icons">music_note</span></div>
                    <h3>Black Fragrance Fashion Show</h3>
                    <p>Witness the exclusive pre-launch of the new beachwear collection. Featuring 3 dynamic runway shows blending cultural patterns with contemporary coastal aesthetics.</p>
                    <div class="highlight-detail">Runway Times: 7:30 PM | 9:00 PM | 10:00 PM</div>
                </div>
                
                <div class="highlight-card">
                    <div class="highlight-icon"><span class="material-icons">shopping_bag</span></div>
                    <h3>Souvenirs & Activities</h3>
                    <p>Take home a piece of the festival with complimentary 15ml coconut oil. Shop exclusive beachwear, totes, and enjoy our dedicated face painting stations.</p>
                    <div class="highlight-detail">Coconut Carpet Photo Booth Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Entertainment Section -->
    <section class="entertainment">
        <div class="container">
            <div class="entertainment-grid">
                <div class="entertainment-content">
                    <h2>Live Entertainment</h2>
                    <p>Experience world-class performances from renowned artists and emerging talents. Our curated lineup features live bands, DJ sets, and cultural performances that celebrate the spirit of coastal celebration.</p>
                    <ul class="entertainment-list">
                        <li>International & Local Artists</li>
                        <li>Multiple Performance Stages</li>
                        <li>Interactive Performances</li>
                    </ul>
                </div>
                
                <img src="static/redesign%20images/live-entertainment-stage.png" alt="Live Entertainment" class="entertainment-image">
            </div>
        </div>
    </section>

    <!-- Schedule Section -->
    <section class="schedule" id="schedule">
        <div class="container">
            <div class="schedule-header">
                <h2>Event Schedule</h2>
                <p>July 12, 2026 - Iceland Beach</p>
            </div>
            
            <ul class="schedule-list">
                <li class="schedule-item">
                    <div class="schedule-time-icon"><span class="material-icons">schedule</span></div>
                    <div class="schedule-content">
                        <p>5:00 PM</p>
                        <p>Gates Open & Welcome Reception</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon"><span class="material-icons">schedule</span></div>
                    <div class="schedule-content">
                        <p>6:00 PM</p>
                        <p>Culinary Experience Begins</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon"><span class="material-icons">schedule</span></div>
                    <div class="schedule-content">
                        <p>7:30 PM</p>
                        <p>First Fashion Runway Show</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon"><span class="material-icons">schedule</span></div>
                    <div class="schedule-content">
                        <p>8:00 PM</p>
                        <p>Live Band Performance</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon"><span class="material-icons">schedule</span></div>
                    <div class="schedule-content">
                        <p>9:00 PM</p>
                        <p>Second Runway Show</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon"><span class="material-icons">schedule</span></div>
                    <div class="schedule-content">
                        <p>10:00 PM</p>
                        <p>Final Runway Show & Celebration</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Don't Miss Out</h2>
            <p>Secure your spot at the most anticipated coastal celebration of the year. Limited tickets available.</p>
            <button class="btn-cta" onclick="document.getElementById('tickets').scrollIntoView({behavior: 'smooth'})">
                Reserve Your Experience Now
                <span class="material-icons" style="font-size: 1rem;">chevron_right</span>
            </button>
        </div>
    </section>
</div>

<!-- JavaScript -->
<script>
    // Smooth scroll for anchor links
    document.querySelectorAll('.festival-redesign-wrapper a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Hover effects for ticket cards
    document.querySelectorAll('.festival-redesign-wrapper .ticket-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 200ms cubic-bezier(0.23, 1, 0.32, 1)';
        });
    });

    // Button click feedback
    document.querySelectorAll('.festival-redesign-wrapper button, .festival-redesign-wrapper .btn-ticket').forEach(button => {
        button.addEventListener('click', function() {
            this.style.transform = 'scale(0.97)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
</script>

<style>
    .crowd-animation-container {
        position: relative;
        width: 100%;
        height: 60vh;
        min-height: 400px;
        overflow: hidden;
        background-color: #f8f9fa; /* fallback background */
        border-top: 1px solid #e5e7eb;
        margin-top: 3rem;
    }
    .crowd-animation-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
</style>

<section class="crowd-animation-container">
    <canvas id="crowdCanvas" class="crowd-animation-canvas"></canvas>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('crowdCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const container = canvas.parentElement;

        function resizeCanvas() {
            canvas.width = container.clientWidth;
            canvas.height = container.clientHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        const img = new Image();
        // Trying relative path to public folder since the file is in the root
        img.src = 'public/images/peeps/all-peeps.png';
        const rows = 15;
        const cols = 7;
        const totalPeeps = 150;
        const peepsArray = [];

        img.onload = () => {
            const spriteWidth = img.width / cols;
            const spriteHeight = img.height / rows;

            for(let i = 0; i < totalPeeps; i++) {
                peepsArray.push({
                    x: Math.random() * canvas.width,
                    y: (Math.random() * (canvas.height - spriteHeight)) + (spriteHeight / 2),
                    colIndex: Math.floor(Math.random() * cols),
                    rowIndex: Math.floor(Math.random() * rows),
                    speedX: (Math.random() * 1.5) - 0.75,
                    bobSpeed: Math.random() * 0.05 + 0.02,
                    bobHeight: Math.random() * 15 + 5,
                    scale: Math.random() * 0.4 + 0.6,
                    timeOffset: Math.random() * Math.PI * 2
                });
            }
            requestAnimationFrame(animateCrowd);
        };

        function animateCrowd(timestamp) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const spriteWidth = img.width / cols;
            const spriteHeight = img.height / rows;

            peepsArray.sort((a, b) => a.y - b.y);

            peepsArray.forEach(peep => {
                peep.x += peep.speedX;
                if (peep.x > canvas.width + spriteWidth) peep.x = -spriteWidth;
                if (peep.x < -spriteWidth * 2) peep.x = canvas.width;

                const currentY = peep.y + Math.sin(timestamp * peep.bobSpeed + peep.timeOffset) * peep.bobHeight;
                const sourceX = peep.colIndex * spriteWidth;
                const sourceY = peep.rowIndex * spriteHeight;

                ctx.drawImage(
                    img,
                    sourceX, sourceY, spriteWidth, spriteHeight,
                    peep.x, currentY, spriteWidth * peep.scale, spriteHeight * peep.scale
                );
            });
            requestAnimationFrame(animateCrowd);
        }
    });
</script>

<?php 
include "includes/footer.php"; 
?>
