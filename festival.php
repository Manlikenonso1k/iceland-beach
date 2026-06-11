<?php 
$bodyClass = 'festival-page';
require_once "includes/header.php"; 
?>

<!-- Tailwind Integration & Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Raleway:wght@400&family=Bodoni+Moda:wght@500;600&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    festive: {
                        gold: '#0066cc',
                        navy: '#0066cc',
                        coral: '#0052a3',
                        black: '#000000',
                        cream: '#ffffff',
                        light: '#f0f8ff'
                    }
                },
                fontFamily: {
                    heading: ["Bodoni Moda", "serif"],
                    body: ["Raleway", "sans-serif"],
                    ui: ["Plus Jakarta Sans", "sans-serif"],
                }
            }
        }
    }
</script>
<style>
    body { background-color: #ffffff; color: #000000; }
    .gold-glow:hover { box-shadow: 0px 0px 20px rgba(0, 102, 204, 0.4); border-color: #0066cc; }
    .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(44, 62, 80, 0.1); }
</style>

<!-- Hero Section -->
<section class="relative w-full min-h-[80vh] flex items-center justify-center bg-black overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img alt="Coconut Festival Hero" class="w-full h-full object-cover opacity-60"
            src="https://tenstrings.org/wp-content/uploads/2026/06/Beach-hero-scaled.jpg" />
        <div class="absolute inset-0 bg-[#f0f8ff]/80"></div>
    </div>
    <div class="relative z-10 text-center max-w-4xl mx-auto px-6 py-20 mt-16">
        <span class="inline-block px-4 py-1 border border-festive-gold text-festive-gold font-ui tracking-widest text-sm mb-6 rounded-full">
            JULY 12, 2026 • ICELAND BEACH
        </span>
        <h1 class="font-heading text-5xl md:text-7xl text-black mb-6 leading-tight drop-shadow-lg">
            Coconut Festival <br/> <span class="text-festive-gold">& Black Fragrance Pre-Launch</span>
        </h1>
        <p class="font-body text-xl text-black/90 max-w-2xl mx-auto mb-10 drop-shadow-md">
            An immersive cultural experience blending traditional coastal flavors, live entertainment, and high-end beachwear fashion.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="ticket.php" class="bg-festive-gold text-festive-navy font-ui font-bold px-8 py-4 rounded hover:bg-white transition-colors">
                Get Tickets Now
            </a>
            <a href="#schedule" class="border border-festive-cream text-festive-cream font-ui font-bold px-8 py-4 rounded hover:bg-white/10 transition-colors">
                View Schedule
            </a>
        </div>
    </div>
</section>

<!-- Overview -->
<section class="py-20 px-6 max-w-7xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12 items-center">
        <div>
            <h2 class="font-heading text-4xl text-festive-navy mb-6">Experience the Tropics</h2>
            <p class="font-body text-lg mb-6 leading-relaxed">
                Join us for an unforgettable evening at Iceland Beach. The Coconut Festival is more than an event—it's a celebration of coastal lifestyle. We are also thrilled to host the exclusive pre-launch of the <strong>Black Fragrance Beachwear Collection</strong>.
            </p>
            <ul class="space-y-4 font-body text-lg">
                <li class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-festive-gold">check_circle</span>
                    Immersive coconut-themed culinary experience
                </li>
                <li class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-festive-gold">check_circle</span>
                    Exclusive runway fashion showcases
                </li>
                <li class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-festive-gold">check_circle</span>
                    Live performances by top bands and guest artists
                </li>
            </ul>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <img src="https://tenstrings.org/wp-content/uploads/2026/06/Beach-hero-scaled.jpg" class="rounded-lg w-full h-64 object-cover" alt="Festival Vibe 1">
            <img src="https://tenstrings.org/wp-content/uploads/2026/06/Beach-hero-scaled.jpg" class="rounded-lg w-full h-64 object-cover mt-8" alt="Festival Vibe 2">
        </div>
    </div>
</section>

<!-- Ticket & Buffet Packages -->
<section class="bg-festive-navy py-24 px-6 text-festive-cream">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="font-heading text-4xl md:text-5xl text-white mb-4">Ticketing & Packages</h2>
            <p class="font-body text-xl text-festive-cream/80 max-w-2xl mx-auto">Choose your level of experience.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Regular -->
            <div class="bg-white text-festive-navy rounded-xl p-8 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-festive-gold/10 rounded-bl-full"></div>
                <h3 class="font-heading text-2xl font-bold mb-2">Regular Access</h3>
                <div class="text-4xl font-bold font-ui mb-6 text-festive-gold">₦5,000</div>
                <ul class="space-y-3 font-body mb-8">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-festive-coral text-sm">coconut</span> Event entry</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-festive-coral text-sm">coconut</span> 1 complimentary coconut</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-festive-coral text-sm">coconut</span> Access to main stage</li>
                </ul>
                <a href="ticket.php" class="block w-full text-center bg-festive-navy text-white font-ui py-3 rounded hover:bg-festive-gold transition-colors">Book Now</a>
            </div>
            
            <!-- VIP -->
            <div class="bg-festive-gold text-festive-navy rounded-xl p-8 card-hover relative overflow-hidden transform md:-translate-y-4 shadow-2xl shadow-festive-gold/20 border-2 border-festive-gold">
                <div class="absolute top-0 right-0 bg-white text-festive-navy font-bold text-xs px-3 py-1 rounded-bl-lg">POPULAR</div>
                <h3 class="font-heading text-2xl font-bold mb-2">VIP Experience</h3>
                <div class="text-4xl font-bold font-ui mb-6 text-white">₦10,000</div>
                <ul class="space-y-3 font-body mb-8">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-white text-sm">star</span> Priority access</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-white text-sm">star</span> Premium seating area</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-white text-sm">star</span> 1 complimentary coconut</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-white text-sm">star</span> Dedicated service</li>
                </ul>
                <a href="ticket.php" class="block w-full text-center bg-white text-festive-navy font-ui font-bold py-3 rounded hover:bg-festive-navy hover:text-white transition-colors">Book VIP</a>
            </div>

            <!-- Buffet -->
            <div class="bg-white text-festive-navy rounded-xl p-8 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-festive-coral/10 rounded-bl-full"></div>
                <h3 class="font-heading text-2xl font-bold mb-2">Buffet Package</h3>
                <div class="text-4xl font-bold font-ui mb-2 text-festive-coral">₦25,000</div>
                <p class="text-sm font-body text-slate-500 mb-4">₦40,000 for couples</p>
                <ul class="space-y-3 font-body mb-8">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-festive-coral text-sm">restaurant</span> Coconut-inspired menu</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-festive-coral text-sm">restaurant</span> Exclusive buffet seating</li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-festive-coral text-sm">restaurant</span> Complementary welcome drink</li>
                </ul>
                <a href="ticket.php" class="block w-full text-center border-2 border-festive-navy text-festive-navy font-ui py-3 rounded hover:bg-festive-navy hover:text-white transition-colors">Reserve Buffet</a>
            </div>
        </div>
    </div>
</section>

<!-- Highlights (Fashion & Merch) -->
<section class="py-20 px-6 max-w-7xl mx-auto">
    <div class="text-center mb-16">
        <h2 class="font-heading text-4xl text-festive-navy mb-4">Festival Highlights</h2>
    </div>
    <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-white border border-slate-200 rounded-xl p-8 card-hover">
            <div class="w-16 h-16 bg-festive-coral/20 rounded-full flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-festive-coral text-3xl">checkroom</span>
            </div>
            <h3 class="font-heading text-2xl text-festive-navy mb-4">Black Fragrance Fashion Show</h3>
            <p class="font-body text-slate-600 mb-6">
                Witness the exclusive pre-launch of the new beachwear collection. Featuring 3 dynamic runway shows blending cultural patterns with contemporary coastal aesthetics.
            </p>
            <div class="text-sm font-ui font-bold text-festive-navy">Runway Times: 7:30 PM | 9:00 PM | 10:00 PM</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-8 card-hover">
            <div class="w-16 h-16 bg-festive-gold/20 rounded-full flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-festive-gold text-3xl">shopping_bag</span>
            </div>
            <h3 class="font-heading text-2xl text-festive-navy mb-4">Souvenirs & Activities</h3>
            <p class="font-body text-slate-600 mb-6">
                Take home a piece of the festival with complimentary 15ml coconut oil. Shop exclusive beachwear, totes, and enjoy our dedicated face painting stations.
            </p>
            <div class="text-sm font-ui font-bold text-festive-navy">Coconut Carpet Photo Booth Available</div>
        </div>
    </div>
</section>

<!-- FESTIVE IMAGE CAROUSEL -->
<div class="festive-carousel" style="margin-bottom: 4rem;">
    <h3 style="text-align: center; margin-bottom: 2rem; color: #000000;" class="font-heading text-4xl">
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
        <img src="/public/images/festival/1-coconut-drinks.jpg" alt="Coconut Drinks" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/2-live-band.jpg" alt="Live Band Performance" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/3-dancers.jpg" alt="Dancers" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/4-beachwear.jpg" alt="Black Fragrance Beachwear" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/5-runway.jpg" alt="Fashion Runway" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/6-buffet.jpg" alt="Buffet Food" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/7-beach-sunset.jpg" alt="Beach Sunset" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/8-crowd.jpg" alt="Festival Crowd" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
        
        <img src="/public/images/festival/9-coconut-theme.jpg" alt="Coconut Theme Decoration" 
             style="width: 280px; height: 200px; border-radius: 15px; object-fit: cover; flex-shrink: 0; cursor: pointer; transition: transform 0.3s;" 
             onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
    </div>
</div>

<!-- Schedule -->
<section id="schedule" class="bg-festive-cream py-20 px-6 border-t border-slate-200">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="font-heading text-4xl text-festive-navy mb-4">Entertainment Schedule</h2>
            <p class="font-body text-xl text-slate-600">Don't miss a beat of the celebration.</p>
        </div>
        
        <div class="space-y-6">
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-white rounded-lg p-6 shadow-sm border border-slate-100 hover:border-festive-gold transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-coral text-lg mb-2 md:mb-0">6:00 - 6:30 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Guest Arrival & Welcome Music</h4>
                    <p class="font-body text-slate-500">Settle in with ambient tunes and a welcome coconut.</p>
                </div>
            </div>
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-white rounded-lg p-6 shadow-sm border border-slate-100 hover:border-festive-gold transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-coral text-lg mb-2 md:mb-0">6:30 - 7:30 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Agege Band</h4>
                    <p class="font-body text-slate-500">Live musical performance setting the coastal vibe.</p>
                </div>
            </div>
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-festive-gold/10 rounded-lg p-6 shadow-sm border border-festive-gold hover:bg-festive-gold/20 transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-navy text-lg mb-2 md:mb-0">7:30 - 8:00 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Fashion Showcase</h4>
                    <p class="font-body text-slate-600">Black Fragrance Beachwear debut runway show.</p>
                </div>
            </div>
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-white rounded-lg p-6 shadow-sm border border-slate-100 hover:border-festive-gold transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-coral text-lg mb-2 md:mb-0">8:00 - 8:30 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Guest Artist</h4>
                    <p class="font-body text-slate-500">Live headline performance.</p>
                </div>
            </div>
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-white rounded-lg p-6 shadow-sm border border-slate-100 hover:border-festive-gold transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-coral text-lg mb-2 md:mb-0">8:30 - 9:30 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Ajah Band</h4>
                    <p class="font-body text-slate-500">High-energy live music to keep the party going.</p>
                </div>
            </div>
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-white rounded-lg p-6 shadow-sm border border-slate-100 hover:border-festive-gold transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-coral text-lg mb-2 md:mb-0">9:30 - 10:30 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Dance & Circus Acts</h4>
                    <p class="font-body text-slate-500">Thrilling live entertainment across the venue.</p>
                </div>
            </div>
            <!-- Schedule Item -->
            <div class="flex flex-col md:flex-row bg-white rounded-lg p-6 shadow-sm border border-slate-100 hover:border-festive-gold transition-colors">
                <div class="md:w-1/4 font-ui font-bold text-festive-coral text-lg mb-2 md:mb-0">10:30 - 11:00 PM</div>
                <div class="md:w-3/4">
                    <h4 class="font-heading text-xl text-festive-navy mb-1">Closing DJ Performance</h4>
                    <p class="font-body text-slate-500">Dance into the night with our resident DJ.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
include "includes/footer.php"; 
?>
