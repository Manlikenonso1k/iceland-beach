<!DOCTYPE html>

<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>ICELAND BEACH RESORT - Annual Events &amp; Gallery</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&amp;family=Raleway:wght@400&amp;family=Bodoni+Moda:wght@500;600&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-error-container": "#ffdad6",
                        "surface-container-low": "#1b1b1b",
                        "primary-container": "#00002b",
                        "tertiary-fixed-dim": "#a8ccd9",
                        "secondary": "#ffd48a",
                        "primary-fixed": "#e0e0ff",
                        "outline": "#919099",
                        "on-primary": "#2a2c56",
                        "inverse-primary": "#585b87",
                        "secondary-container": "#f8b201",
                        "on-background": "#e5e2e1",
                        "tertiary-fixed": "#c4e8f6",
                        "secondary-fixed": "#ffdea9",
                        "surface-container-high": "#2a2a2a",
                        "inverse-on-surface": "#313030",
                        "surface-tint": "#c0c2f5",
                        "surface-container-lowest": "#0e0e0e",
                        "inverse-surface": "#e5e2e1",
                        "error-container": "#93000a",
                        "on-tertiary": "#0e353f",
                        "surface-dim": "#131313",
                        "on-error": "#690005",
                        "outline-variant": "#46464e",
                        "surface-container": "#20201f",
                        "on-surface-variant": "#c7c5cf",
                        "on-tertiary-container": "#5a7d89",
                        "on-tertiary-fixed-variant": "#284c56",
                        "tertiary": "#a8ccd9",
                        "on-primary-fixed-variant": "#40436e",
                        "on-surface": "#e5e2e1",
                        "background": "#131313",
                        "surface": "#131313",
                        "on-tertiary-fixed": "#001f27",
                        "tertiary-container": "#00070a",
                        "secondary-fixed-dim": "#ffba27",
                        "on-secondary": "#422c00",
                        "on-secondary-fixed": "#271900",
                        "primary": "#c0c2f5",
                        "on-primary-fixed": "#141740",
                        "on-primary-container": "#7174a2",
                        "on-secondary-container": "#664700",
                        "surface-variant": "#353535",
                        "surface-bright": "#393939",
                        "error": "#ffb4ab",
                        "surface-container-highest": "#353535",
                        "on-secondary-fixed-variant": "#5e4100",
                        "primary-fixed-dim": "#c0c2f5"
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
        .gold-glow:hover {
            box-shadow: 0px 0px 15px rgba(247, 177, 0, 0.2);
            border-color: #ffd48a;
        }

        .event-card-border {
            border: 1px solid #002a34;
            transition: all 0.3s ease;
        }

        .event-card-border:hover {
            border-color: #ffd48a;
        }

        .bg-teal-gradient {
            background: linear-gradient(180deg, rgba(0, 0, 43, 0.8) 0%, rgba(19, 19, 19, 1) 100%);
        }
    </style>
</head>

<body
    class="bg-background text-on-surface antialiased overflow-x-hidden selection:bg-secondary selection:text-on-secondary">
    <!-- TopNavBar -->
    <!--
    <nav
        class="fixed top-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop py-base max-w-container-max mx-auto bg-surface/80 backdrop-blur-md dark:bg-surface/80 border-b border-outline-variant/30 transition-all duration-300 ease-in-out">
        <div
            class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg tracking-tighter text-on-surface dark:text-on-surface">
            ICELAND BEACH RESORT
        </div>
        <div class="hidden md:flex gap-gutter items-center">
            <a class="font-ui-button text-ui-button text-on-surface-variant hover:text-secondary hover:bg-white/5 transition-colors duration-300 px-3 py-2 rounded-DEFAULT"
                href="#">Resort</a>
            <a class="font-ui-button text-ui-button text-secondary-fixed-dim border-b border-secondary-fixed-dim pb-1 hover:bg-white/5 transition-colors duration-300 px-3 py-2 rounded-DEFAULT"
                href="#">Events</a>
            <a class="font-ui-button text-ui-button text-on-surface-variant hover:text-secondary hover:bg-white/5 transition-colors duration-300 px-3 py-2 rounded-DEFAULT"
                href="#">Amenities</a>
            <a class="font-ui-button text-ui-button text-on-surface-variant hover:text-secondary hover:bg-white/5 transition-colors duration-300 px-3 py-2 rounded-DEFAULT"
                href="#">Bookings</a>
        </div>
        <button
            class="font-ui-button text-ui-button text-secondary dark:text-secondary border border-secondary px-6 py-3 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-colors duration-300">
            Reserve
        </button>
    </nav>
    -->
    <main class="pt-32 pb-stack-lg">
        <!-- Hero Section -->
        <section
            class="relative w-full min-h-[716px] flex items-center justify-center px-margin-mobile md:px-margin-desktop mb-stack-lg bg-teal-gradient">
            <div class="absolute inset-0 z-0">
                <img alt="Abstract architecture" class="w-full h-full object-cover opacity-20 mix-blend-overlay"
                    data-alt="A dramatic, minimalist architectural night view. Deep navy blue and charcoal tones dominate the sky, while subtle, elegant lighting highlights the sleek, modern lines of an exclusive coastal resort structure. The mood is calm, prestige, and atmospheric with a mysterious high-end aesthetic. Matte surfaces contrast with sharp geometric shapes, evoking late-night tranquility."
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuBoRcGTQ9-g37snT3EgyJX_kzKD5rQFw-lqWM54O_l7b6zQcQn1OOg_eHNY0lP0EjprP5GJm4N35BpoHxJOkj7VIZuauEMJYMqF6WqX1sFu9r_kA8bklb6uSCsHqLgFaH6PR8z627kI9YXu7QWF37sdexpdr_Tx5P_5FCqg1CZEbC799nyEOL-i48tvFvaKBTm-WqKssmT9MhPmRky95TNMSSlm4eor-6ySqNPn5x-HTa10T99YJhRDQ2VfP-EXciRbyH_-VwLBk74" />
            </div>
            <div class="relative z-10 text-center max-w-4xl mx-auto">
                <h1
                    class="font-display-lg-mobile text-display-lg-mobile md:font-display-lg md:text-display-lg text-white mb-stack-sm drop-shadow-lg">
                    Curated Year of Excellence
                </h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">
                    Experience a symphony of masterfully designed moments. From intimate cultural showcases to grand
                    coastal celebrations, every event is architected for the discerning few.
                </p>
            </div>
        </section>
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
            <!-- Timeline Line -->
            <div class="fixed left-8 md:left-24 top-0 bottom-0 w-px bg-secondary/20 z-0 hidden lg:block"></div>
            <!-- January -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    January</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill"
                                    style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Iceland New Year Festival
                                    </h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">JAN 03</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">
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
            </section><!-- February -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    February</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">favorite</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Valentine’s Festival</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">FEB 14</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">An intimate
                                    evening of coastal romance and curated dining experiences.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- March -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    March</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">celebration</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Easter Fiesta</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">MAR 29</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">Vibrant spring
                                    celebrations featuring coastal traditions and family festivities.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- April -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    April</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">museum</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Lagos Coastal Culture
                                        Festival</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">APR 25</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">A deep dive
                                    into the rich heritage and artistic expressions of the coast.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- May -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    May</h2>
                <div class="mb-base flex justify-between items-end">
                    <h3 class="font-headline-md text-headline-md text-white">Beach Fashion Festival</h3>
                    <span class="font-label-caps text-label-caps text-secondary/80">MAY 30</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter grid-rows-2 h-auto md:h-[600px]">
                    <div
                        class="md:col-span-8 md:row-span-2 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative flex items-center justify-center group gold-glow">
                        <span class="material-symbols-outlined text-secondary text-6xl opacity-80" data-weight="fill"
                            style="font-variation-settings: 'FILL' 1;">checkroom</span>
                        <div class="absolute bottom-gutter left-gutter">
                            <span
                                class="font-label-caps text-label-caps text-white bg-surface-container-high px-2 py-1 rounded-sm border border-secondary/30">Main
                                Runway</span>
                        </div>
                    </div>
                    <div
                        class="md:col-span-4 md:row-span-1 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter flex items-center justify-center group gold-glow relative">
                        <span class="material-symbols-outlined text-secondary text-4xl opacity-80" data-weight="fill"
                            style="font-variation-settings: 'FILL' 1;">diamond</span>
                    </div>
                    <div
                        class="md:col-span-4 md:row-span-1 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter flex items-center justify-center group gold-glow relative">
                        <span class="material-symbols-outlined text-secondary text-4xl opacity-80" data-weight="fill"
                            style="font-variation-settings: 'FILL' 1;">styler</span>
                    </div>
                </div>
            </section><!-- June -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    June</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">construction</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Preparation</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">JUNE</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">Curating the
                                    next season of excellence. Private resort maintenance and planning.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- July -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    July</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">nutrition</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Coconut Festival</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">JUL 12</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">A tropical
                                    celebration of coastal flavors and island lifestyle.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- August -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    August</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">music_note</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">African Music Festival</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">AUG 20</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">The rhythm of
                                    the continent meets the sound of the waves.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- September -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    September</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">school</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Inter-School Beach Blast
                                    </h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">SEP 11</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">Fostering
                                    community and competition through coastal sports and arts.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- October -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    October</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">flag</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Lagos Freedom Fest</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">OCT 04</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">Celebrating
                                    independence and the spirit of liberty by the sea.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- November -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    November</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">wine_bar</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Palm Wine Festival</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">NOV 15</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">A sophisticated
                                    exploration of traditional spirits and modern mixology.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- December -->
            <section class="relative z-10 mb-stack-lg pl-0 lg:pl-24">
                <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45">
                </div>
                <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl">
                    December</h2>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter mb-stack-md">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">restaurant</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">All Nigerian Cuisine
                                        Festival</h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">DEC 06</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">A culinary
                                    journey through the diverse and rich flavors of Nigeria.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                    <div
                        class="md:col-span-12 event-card-border bg-[#0a0a0a] rounded-DEFAULT p-gutter relative overflow-hidden group gold-glow">
                        <div class="flex flex-col md:flex-row gap-gutter items-center">
                            <div
                                class="w-full md:w-1/3 aspect-square flex items-center justify-center bg-surface-container-low rounded-sm relative">
                                <span class="material-symbols-outlined text-secondary text-6xl opacity-80"
                                    data-weight="fill" style="font-variation-settings: 'FILL' 1;">piano</span>
                            </div>
                            <div class="w-full md:w-2/3 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-base">
                                    <h3 class="font-headline-md text-headline-md text-white">Lagos Highlife Festival
                                    </h3>
                                    <span class="font-label-caps text-label-caps text-secondary/80">DEC 20</span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-stack-sm">The grand
                                    finale of the year, celebrating the timeless elegance of Highlife music.</p>
                                <button
                                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <!-- Footer -->
    <!--
    <footer
        class="w-full px-margin-mobile md:px-margin-desktop py-stack-lg flex flex-col items-center gap-gutter bg-surface-container-lowest dark:bg-surface-container-lowest border-t border-outline-variant/20">
        <div
            class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface">
            ICELAND BEACH RESORT
        </div>
        <div class="flex flex-wrap justify-center gap-6 mb-4">
            <a class="font-label-caps text-label-caps text-on-surface-variant hover:text-secondary transition-colors"
                href="#">Privacy Policy</a>
            <a class="font-label-caps text-label-caps text-on-surface-variant hover:text-secondary transition-colors"
                href="#">Terms of Service</a>
            <a class="font-label-caps text-label-caps text-on-surface-variant hover:text-secondary transition-colors"
                href="#">Press Kit</a>
            <a class="font-label-caps text-label-caps text-on-surface-variant hover:text-secondary transition-colors"
                href="#">Contact</a>
        </div>
        <div class="font-body-md text-body-md text-secondary-fixed dark:text-secondary-fixed opacity-60">
            © 2026 ICELAND BEACH RESORT. ALL RIGHTS RESERVED.
        </div>
    </footer>
    -->
</body>

</html>