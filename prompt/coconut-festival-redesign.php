<?php
/**
 * Coconut Festival & Black Fragrance Pre-Launch Landing Page
 * Modern Coastal Sophistication Design
 * Pure PHP with embedded CSS and JavaScript
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coconut Festival & Black Fragrance Pre-Launch | July 12, 2026</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
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
        
        /* ============================================
           GLOBAL STYLES
           ============================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: var(--background);
            line-height: 1.6;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
        }
        
        h1 { font-size: 3.5rem; letter-spacing: -0.02em; }
        h2 { font-size: 2.5rem; letter-spacing: -0.01em; }
        h3 { font-size: 1.75rem; }
        h4 { font-size: 1.25rem; }
        
        p { color: var(--text-dark); }
        
        a {
            color: var(--primary);
            text-decoration: none;
            transition: color 180ms ease-out;
        }
        
        a:hover {
            color: var(--accent);
        }
        
        button {
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            border: none;
            transition: all 180ms cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        button:active {
            transform: scale(0.97);
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        @media (min-width: 640px) {
            .container {
                padding: 0 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .container {
                padding: 0 2rem;
            }
        }
        
        /* ============================================
           NAVIGATION
           ============================================ */
        
        nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-subtle);
        }
        
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 4rem;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-logo img {
            height: 2.5rem;
            width: 2.5rem;
        }
        
        .nav-logo span {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-links a {
            font-size: 0.875rem;
            color: var(--text-dark);
            transition: color 180ms ease-out;
        }
        
        .nav-links a:hover {
            color: var(--accent);
        }
        
        .btn-nav {
            background: var(--accent);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            transition: background 180ms ease-out;
        }
        
        .btn-nav:hover {
            background: var(--accent-light);
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 0.5rem;
            }
            
            .nav-links a {
                display: none;
            }
            
            .btn-nav {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
        }
        
        /* ============================================
           HERO SECTION
           ============================================ */
        
        .hero {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('https://d2xsxph8kpxj0f.cloudfront.net/310519663742922467/QB8aS64KM9mwa8miTUkv3g/hero-beach-sunset-atm5NiQP72s5pbJ8errDLn.webp');
            background-size: cover;
            background-position: center;
            z-index: 0;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.4), transparent);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 56rem;
            margin: 0 auto;
            padding: 5rem 1rem;
            text-align: left;
        }
        
        .hero-badge {
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
        
        .hero-badge span {
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
            letter-spacing: 0.05em;
        }
        
        .hero h1 {
            color: white;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .hero h1 .accent {
            color: var(--accent);
        }
        
        .hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 32rem;
            margin-bottom: 2.5rem;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
        }
        
        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .btn-primary {
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
        
        .btn-primary:hover {
            background: var(--accent-light);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-sm);
            border: 2px solid white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 180ms ease-out;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
        
        /* ============================================
           OVERVIEW SECTION
           ============================================ */
        
        .overview {
            padding: 5rem 0;
            background: var(--card-bg);
        }
        
        .overview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        
        .overview-content h2 {
            margin-bottom: 1.5rem;
        }
        
        .overview-content p {
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 1.125rem;
        }
        
        .feature-icon {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: rgba(212, 165, 116, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .feature-icon::before {
            content: '★';
            color: var(--accent);
            font-size: 1rem;
        }
        
        .overview-images {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .overview-images img {
            width: 100%;
            height: 16rem;
            object-fit: cover;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-medium);
        }
        
        .overview-images img:last-child {
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .overview-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .overview-images {
                grid-template-columns: 1fr;
            }
            
            .overview-images img:last-child {
                margin-top: 0;
            }
        }
        
        /* ============================================
           TICKETING SECTION
           ============================================ */
        
        .ticketing {
            padding: 6rem 0;
            background: var(--primary);
            color: white;
        }
        
        .ticketing-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .ticketing-header h2 {
            color: white;
            margin-bottom: 1rem;
        }
        
        .ticketing-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.25rem;
            max-width: 32rem;
            margin: 0 auto;
        }
        
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .ticket-card {
            background: var(--card-bg);
            color: var(--primary);
            padding: 2rem;
            border-radius: var(--radius-lg);
            position: relative;
            overflow: hidden;
            transition: all 200ms cubic-bezier(0.23, 1, 0.32, 1);
            border: 1px solid var(--border-color);
        }
        
        .ticket-card:hover {
            transform: translateY(-1rem);
            box-shadow: var(--shadow-hover);
        }
        
        .ticket-card.highlight {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-1rem);
            box-shadow: 0 20px 40px rgba(212, 165, 116, 0.3);
            border: 2px solid var(--accent);
        }
        
        .ticket-badge {
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
        
        .ticket-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: inherit;
        }
        
        .ticket-price {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .ticket-card.highlight .ticket-price {
            color: white;
        }
        
        .ticket-note {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        
        .ticket-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .ticket-features li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        .ticket-features li::before {
            content: '★';
            color: var(--accent);
            font-size: 0.75rem;
        }
        
        .btn-ticket {
            width: 100%;
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            background: var(--primary);
            color: white;
            transition: all 180ms ease-out;
        }
        
        .btn-ticket:hover {
            background: var(--primary-light);
        }
        
        .ticket-card.highlight .btn-ticket {
            background: white;
            color: var(--primary);
        }
        
        .ticket-card.highlight .btn-ticket:hover {
            background: var(--background);
        }
        
        @media (max-width: 1024px) {
            .tickets-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .tickets-grid {
                grid-template-columns: 1fr;
            }
            
            .ticket-card.highlight {
                transform: none;
            }
        }
        
        /* ============================================
           HIGHLIGHTS SECTION
           ============================================ */
        
        .highlights {
            padding: 5rem 0;
            background: var(--card-bg);
        }
        
        .highlights-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .highlights-header h2 {
            margin-bottom: 0;
        }
        
        .highlights-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
        
        .highlight-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 2rem;
            border-radius: var(--radius-lg);
            transition: all 200ms cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .highlight-card:hover {
            transform: translateY(-0.25rem);
            box-shadow: var(--shadow-medium);
        }
        
        .highlight-icon {
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
        
        .highlight-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .highlight-card p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }
        
        .highlight-detail {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary);
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .highlights-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ============================================
           ENTERTAINMENT SECTION
           ============================================ */
        
        .entertainment {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .entertainment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        
        .entertainment-content h2 {
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .entertainment-content p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }
        
        .entertainment-list {
            list-style: none;
        }
        
        .entertainment-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .entertainment-list li::before {
            content: '♪';
            color: var(--accent);
            font-size: 1.25rem;
        }
        
        .entertainment-image {
            width: 100%;
            height: 24rem;
            object-fit: cover;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .entertainment-grid {
                grid-template-columns: 1fr;
            }
            
            .entertainment-image {
                height: 16rem;
            }
        }
        
        /* ============================================
           SCHEDULE SECTION
           ============================================ */
        
        .schedule {
            padding: 5rem 0;
            background: var(--card-bg);
        }
        
        .schedule-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .schedule-header h2 {
            margin-bottom: 0.5rem;
        }
        
        .schedule-header p {
            color: var(--text-muted);
            font-size: 1.125rem;
        }
        
        .schedule-list {
            list-style: none;
            max-width: 56rem;
            margin: 0 auto;
        }
        
        .schedule-item {
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
        
        .schedule-item:hover {
            box-shadow: var(--shadow-medium);
        }
        
        .schedule-time-icon {
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
        
        .schedule-content p:first-child {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 0.25rem;
        }
        
        .schedule-content p:last-child {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* ============================================
           CTA SECTION
           ============================================ */
        
        .cta {
            padding: 5rem 0;
            background: var(--accent);
            text-align: center;
        }
        
        .cta h2 {
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .cta p {
            color: rgba(26, 58, 82, 0.8);
            font-size: 1.25rem;
            max-width: 32rem;
            margin: 0 auto 2rem;
        }
        
        .btn-cta {
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
        
        .btn-cta:hover {
            background: var(--primary-light);
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        
        footer {
            background: var(--primary);
            color: white;
            padding: 3rem 0;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-col h4 {
            color: white;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .footer-col p, .footer-col ul {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-col a {
            color: rgba(255, 255, 255, 0.7);
            transition: color 180ms ease-out;
        }
        
        .footer-col a:hover {
            color: var(--accent);
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .footer-logo img {
            height: 2rem;
            width: 2rem;
        }
        
        .footer-logo span {
            font-weight: 700;
            color: white;
        }
        
        .footer-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 2rem 0;
        }
        
        .footer-bottom {
            text-align: center;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ============================================
           UTILITY CLASSES
           ============================================ */
        
        .material-icons {
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
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <div class="nav-content">
                <div class="nav-logo">
                    <img src="https://d2xsxph8kpxj0f.cloudfront.net/310519663742922467/QB8aS64KM9mwa8miTUkv3g/coconut-festival-logo-MDzfRGAgBnXJqwveQmdFji.webp" alt="Coconut Festival">
                    <span>Coconut Festival</span>
                </div>
                <div class="nav-links">
                    <a href="#schedule">Schedule</a>
                    <a href="#tickets">Tickets</a>
                    <button class="btn-nav" onclick="document.getElementById('tickets').scrollIntoView({behavior: 'smooth'})">Get Tickets</button>
                </div>
            </div>
        </div>
    </nav>

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
                    <img src="https://d2xsxph8kpxj0f.cloudfront.net/310519663742922467/QB8aS64KM9mwa8miTUkv3g/beachwear-fashion-showcase-DmYwCqveQEaMiGDT2T7c7P.webp" alt="Fashion Showcase">
                    <img src="https://d2xsxph8kpxj0f.cloudfront.net/310519663742922467/QB8aS64KM9mwa8miTUkv3g/coconut-culinary-experience-kqzAdTLDacZ2365FVnbCfH.webp" alt="Culinary Experience">
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
                    <button class="btn-ticket">Book Now</button>
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
                    <button class="btn-ticket">Book VIP</button>
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
                    <button class="btn-ticket">Reserve Buffet</button>
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
                    <div class="highlight-icon">♪</div>
                    <h3>Black Fragrance Fashion Show</h3>
                    <p>Witness the exclusive pre-launch of the new beachwear collection. Featuring 3 dynamic runway shows blending cultural patterns with contemporary coastal aesthetics.</p>
                    <div class="highlight-detail">Runway Times: 7:30 PM | 9:00 PM | 10:00 PM</div>
                </div>
                
                <div class="highlight-card">
                    <div class="highlight-icon">🛍️</div>
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
                
                <img src="https://d2xsxph8kpxj0f.cloudfront.net/310519663742922467/QB8aS64KM9mwa8miTUkv3g/live-entertainment-stage-LAhpk8LJr4wCRyTF26Wsjy.webp" alt="Live Entertainment" class="entertainment-image">
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
                    <div class="schedule-time-icon">🕐</div>
                    <div class="schedule-content">
                        <p>5:00 PM</p>
                        <p>Gates Open & Welcome Reception</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon">🕐</div>
                    <div class="schedule-content">
                        <p>6:00 PM</p>
                        <p>Culinary Experience Begins</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon">🕐</div>
                    <div class="schedule-content">
                        <p>7:30 PM</p>
                        <p>First Fashion Runway Show</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon">🕐</div>
                    <div class="schedule-content">
                        <p>8:00 PM</p>
                        <p>Live Band Performance</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon">🕐</div>
                    <div class="schedule-content">
                        <p>9:00 PM</p>
                        <p>Second Runway Show</p>
                    </div>
                </li>
                <li class="schedule-item">
                    <div class="schedule-time-icon">🕐</div>
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

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="https://d2xsxph8kpxj0f.cloudfront.net/310519663742922467/QB8aS64KM9mwa8miTUkv3g/coconut-festival-logo-MDzfRGAgBnXJqwveQmdFji.webp" alt="Coconut Festival">
                        <span>Coconut Festival</span>
                    </div>
                    <p>A celebration of coastal culture and premium beachwear.</p>
                </div>
                
                <div class="footer-col">
                    <h4>Event</h4>
                    <ul>
                        <li><a href="#schedule">Schedule</a></li>
                        <li><a href="#tickets">Tickets</a></li>
                        <li><a href="#highlights">Highlights</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Location</h4>
                    <p>Iceland Beach<br>July 12, 2026</p>
                </div>
                
                <div class="footer-col">
                    <h4>Contact</h4>
                    <p>info@coconutfestival.com<br>+234 (0) 123 456 7890</p>
                </div>
            </div>
            
            <div class="footer-divider"></div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 Coconut Festival. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
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

        // Add scroll effect to navigation
        let lastScrollTop = 0;
        const nav = document.querySelector('nav');
        
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 100) {
                nav.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
            } else {
                nav.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });

        // Hover effects for ticket cards
        document.querySelectorAll('.ticket-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transition = 'all 200ms cubic-bezier(0.23, 1, 0.32, 1)';
            });
        });

        // Button click feedback
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function() {
                this.style.transform = 'scale(0.97)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            });
        });
    </script>
</body>
</html>
