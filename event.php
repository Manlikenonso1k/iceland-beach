<?php 
$bodyClass = 'event-page';
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
                "colors": {
                    "background": "#ffffff",
                    "surface": "#ffffff",
                    "on-surface": "#000000",
                    "primary": "#227190",
                    "on-primary": "#ffffff",
                    "secondary": "#227190",
                    "on-secondary": "#ffffff",
                    "surface-container-low": "#f8fafc",
                    "surface-container": "#f1f5f9",
                    "surface-container-high": "#e2e8f0",
                    "surface-container-highest": "#cbd5e1",
                    "outline": "#94a3b8",
                    "outline-variant": "#cbd5e1",
                    "on-surface-variant": "#000000",
                    "primary-container": "#e0f2fe",
                    "on-primary-container": "#227190",
                    "secondary-container": "#e0f2fe",
                    "on-secondary-container": "#227190",
                    "surface-container-lowest": "#f8fafc",
                    "inverse-surface": "#0f172a",
                    "inverse-on-surface": "#f8fafc"
                },
                "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "0.25rem",
                    "xl": "0.5rem",
                    "full": "0.75rem"
                },
                "spacing": {
                    "margin-desktop": "64px",
                    "base": "8px",
                    "stack-md": "32px",
                    "gutter": "24px",
                    "stack-sm": "12px",
                    "container-max": "1200px",
                    "stack-lg": "80px",
                    "margin-mobile": "20px"
                },
                "fontFamily": {
                    "ui-button": ["Plus Jakarta Sans"],
                    "body-lg": ["Raleway"],
                    "label-caps": ["Plus Jakarta Sans"],
                    "headline-md": ["Bodoni Moda"],
                    "body-md": ["Raleway"],
                    "headline-sm": ["Bodoni Moda"],
                    "display-lg": ["Bodoni Moda"],
                    "display-lg-mobile": ["Bodoni Moda"]
                },
                "fontSize": {
                    "ui-button": ["14px", { "lineHeight": "1", "letterSpacing": "0.05em", "fontWeight": "600" }],
                    "body-lg": ["18px", { "lineHeight": "1.6", "letterSpacing": "0.01em", "fontWeight": "400" }],
                    "label-caps": ["12px", { "lineHeight": "1", "letterSpacing": "0.15em", "fontWeight": "700" }],
                    "headline-md": ["32px", { "lineHeight": "1.3", "fontWeight": "500" }],
                    "body-md": ["16px", { "lineHeight": "1.6", "fontWeight": "400" }],
                    "headline-sm": ["24px", { "lineHeight": "1.4", "fontWeight": "500" }],
                    "display-lg": ["48px", { "lineHeight": "1.1", "letterSpacing": "-0.02em", "fontWeight": "600" }],
                    "display-lg-mobile": ["32px", { "lineHeight": "1.2", "fontWeight": "600" }]
                }
            }
        }
    }
</script>
<style>
    /* Integrate events styling beautifully with global website header and footer */
    .site-wrapper {
        padding-bottom: 0px !important;
        background-color: #ffffff !important;
        color: #000000 !important;
    }

    .blue-glow:hover {
        box-shadow: 0px 0px 15px rgba(34, 113, 144, 0.2);
        border-color: #227190;
    }

    .event-card-border {
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .event-card-border:hover {
        border-color: #227190;
    }

    .bg-blue-gradient {
        background: linear-gradient(180deg, rgba(34, 113, 144, 0.95) 0%, rgba(17, 56, 72, 0.9) 100%);
    }
</style>

<!-- Hero Section -->
<section
    class="relative w-full min-h-[400px] md:min-h-[500px] flex items-center justify-center px-margin-mobile md:px-margin-desktop mb-stack-lg bg-blue-gradient">
    <div class="absolute inset-0 z-0">
        <img alt="Abstract architecture" class="w-full h-full object-cover opacity-20 mix-blend-overlay"
            src="https://tenstrings.org/wp-content/uploads/2026/06/Beach-hero-scaled.jpg" />
    </div>
    <div class="relative z-10 text-center max-w-4xl mx-auto py-12">
        <h1
            class="font-display-lg-mobile text-display-lg-mobile md:font-display-lg md:text-display-lg text-white mb-stack-sm drop-shadow-lg">
            Curated Year of Excellence
        </h1>
        <p class="font-body-lg text-body-lg text-slate-200 max-w-2xl mx-auto">
            Experience a symphony of masterfully designed moments. From intimate cultural showcases to grand
            coastal celebrations, every event is architected for the discerning few.
        </p>
    </div>
</section>

<div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop bg-white text-black pb-16">
    <div class="relative py-12">
        <!-- Timeline Line -->
        <div class="fixed left-8 md:left-24 top-0 bottom-0 w-px bg-secondary/20 z-0 hidden lg:block"></div>
        
        <!-- January -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                January
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill"
                                style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Iceland New Year Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">JAN 03</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                A stark, beautiful contrast of fire and ice. Minimalist celebrations under the
                                simulated auroras, featuring private ice dining and thermal pool meditations.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- February -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                February
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">favorite</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Valentine’s Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">FEB 14</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                An intimate evening of coastal romance and curated dining experiences.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- March -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                March
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">celebration</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Easter Fiesta</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">MAR 29</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                Vibrant spring celebrations featuring coastal traditions and family festivities.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- April -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                April
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">museum</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Lagos Coastal Culture Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">APR 25</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                A deep dive into the rich heritage and artistic expressions of the coast.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- May -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                May
            </h2>
            <div class="mb-base flex justify-between items-end">
                <h3 class="font-headline-md text-headline-md text-black font-bold">Beach Fashion Festival</h3>
                <span class="font-label-caps text-label-caps text-secondary font-bold">MAY 30</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter grid-rows-2 h-auto md:h-[600px]">
                <div class="md:col-span-8 md:row-span-2 event-card-border bg-white rounded-DEFAULT p-gutter relative flex items-center justify-center group blue-glow shadow-sm border border-slate-100">
                    <span class="material-symbols-outlined text-secondary text-6xl opacity-80" data-weight="fill"
                        style="font-variation-settings: 'FILL' 1;">checkroom</span>
                    <div class="absolute bottom-gutter left-gutter">
                        <span class="font-label-caps text-label-caps text-secondary bg-sky-50 px-2 py-1 rounded-sm border border-secondary/30 font-bold">
                            Main Runway
                        </span>
                    </div>
                </div>
                <div class="md:col-span-4 md:row-span-1 event-card-border bg-white rounded-DEFAULT p-gutter flex items-center justify-center group blue-glow shadow-sm border border-slate-100">
                    <span class="material-symbols-outlined text-secondary text-4xl opacity-80" data-weight="fill"
                        style="font-variation-settings: 'FILL' 1;">diamond</span>
                </div>
                <div class="md:col-span-4 md:row-span-1 event-card-border bg-white rounded-DEFAULT p-gutter flex items-center justify-center group blue-glow shadow-sm border border-slate-100">
                    <span class="material-symbols-outlined text-secondary text-4xl opacity-80" data-weight="fill"
                        style="font-variation-settings: 'FILL' 1;">styler</span>
                </div>
            </div>
        </section>

        <!-- June -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                June
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">construction</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Preparation</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">JUNE</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                Curating the next season of excellence. Private resort maintenance and planning.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- July -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                July
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">nutrition</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Coconut Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">JUL 12</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                A tropical celebration of coastal flavors and island lifestyle.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- August -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                August
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">music_note</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">African Music Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">AUG 20</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                The rhythm of the continent meets the sound of the waves.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- September -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                September
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">school</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Inter-School Beach Blast</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">SEP 11</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                Fostering community and competition through coastal sports and arts.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- October -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                October
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">flag</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Lagos Freedom Fest</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">OCT 04</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                Celebrating independence and the spirit of liberty by the sea.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- November -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                November
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">wine_bar</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Palm Wine Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">NOV 15</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                A sophisticated exploration of traditional spirits and modern mixology.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- December -->
        <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
            <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
            <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
                December
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter mb-stack-md">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">restaurant</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">All Nigerian Cuisine Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">DEC 06</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                A culinary journey through the diverse and rich flavors of Nigeria.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                <div class="md:col-span-12 event-card-border bg-white rounded-DEFAULT p-gutter relative overflow-hidden group blue-glow shadow-sm">
                    <div class="flex flex-col md:flex-row gap-gutter items-center">
                        <div class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-sky-50 rounded-sm relative border border-slate-100">
                            <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                data-weight="fill" style="font-variation-settings: 'FILL' 1;">piano</span>
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center text-left">
                            <div class="flex justify-between items-start mb-base">
                                <h3 class="font-headline-md text-headline-md text-black font-bold">Lagos Highlife Festival</h3>
                                <span class="font-label-caps text-label-caps text-secondary font-bold">DEC 20</span>
                            </div>
                            <p class="font-body-md text-body-md text-black mb-stack-sm">
                                The grand finale of the year, celebrating the timeless elegance of Highlife music.
                            </p>
                            <button
                                class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php 
include "includes/footer.php"; 
?>