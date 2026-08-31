import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import ScrollExpandMedia from '../components/ui/scroll-expansion-hero';
import AuroraLayer from '../components/AuroraLayer';
import {
    ArrowRight,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Smartphone,
    Star,
    Tag,
    Users,
    X,
} from 'lucide-react';

const revealUp = {
    hidden: { opacity: 0, y: 28 },
    show: {
        opacity: 1,
        y: 0,
        transition: { duration: 1, ease: [0.16, 1, 0.3, 1] },
    },
};

const staggerGrid = {
    hidden: {},
    show: { transition: { staggerChildren: 0.08 } },
};

/* ── Static data ──────────────────────────────────────── */

const stayCards = [
    {
        title: 'Glacier Bay Suites',
        desc: 'Oceanview rooms facing the black sand coastline',
        img: '/legacy-images/Beach Front 2.jpeg',
    },
    {
        title: 'The Hot Spring Collection',
        desc: 'Rooms with private geothermal soaking pools',
        img: '/legacy-images/gallery/imge (11).jpeg',
    },
    {
        title: 'Northern Lights Lofts',
        desc: 'Top-floor rooms with retractable skylight roofs',
        img: '/legacy-images/prenium room.jpeg',
    },
    {
        title: 'Fjord Family Villas',
        desc: 'Multi-room villas for groups',
        img: '/legacy-images/Coconut Cabana.jpeg',
    },
];

const confidenceCards = [
    {
        Icon: Tag,
        title: 'The best rate, guaranteed',
        text: "Book directly on our site and you'll always get our lowest rate, with free changes up to 48 hours before arrival.",
    },
    {
        Icon: Star,
        title: 'Member perks',
        text: 'Direct bookings earn credit toward future stays and unlock seasonal upgrades like late checkout and spa access.',
    },
    {
        Icon: Smartphone,
        title: 'Manage it all in one place',
        text: 'Handle check-in, room requests, and check-out from your confirmation email — no app required.',
    },
];

// The four room categories. Images are the same files the rooms themselves use,
// so the homepage and /rooms stay visually consistent.
const photoStripCards = [
    {
        title: 'Beach apartments for family',
        text: 'Spacious family layouts with direct beach access and room for everyone.',
        cta: 'Explore family apartments',
        img: '/legacy-images/rooms/Beach apartment.jpg',
    },
    {
        title: 'Premium stays',
        text: 'Top-tier amenities and wide ocean views, built for comfort.',
        cta: 'Explore premium rooms',
        img: '/legacy-images/prenium room.jpeg',
    },
    {
        title: 'Gold rooms',
        text: 'Elegant rooms at the heart of our premier coastal experience.',
        cta: 'Explore gold rooms',
        img: '/legacy-images/rooms/Gold Room 1.jpeg',
    },
    {
        title: 'Silver retreats',
        text: 'Cosy, modern spaces for a quiet getaway by the Atlantic.',
        cta: 'Explore silver rooms',
        img: '/legacy-images/rooms/room (4).jpeg',
    },
];

/* ── Carousel scroll hook ─────────────────────────────── */

function useCarousel(ref, cardWidth = 340) {
    const [canPrev, setCanPrev] = useState(false);
    const [canNext, setCanNext] = useState(true);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const check = () => {
            setCanPrev(el.scrollLeft > 4);
            setCanNext(el.scrollLeft + el.clientWidth < el.scrollWidth - 4);
        };
        el.addEventListener('scroll', check, { passive: true });
        window.addEventListener('resize', check);
        check();
        return () => {
            el.removeEventListener('scroll', check);
            window.removeEventListener('resize', check);
        };
    }, []);

    const scroll = (dir) => {
        ref.current?.scrollBy({ left: dir * cardWidth, behavior: 'smooth' });
    };

    return { canPrev, canNext, prev: () => scroll(-1), next: () => scroll(1) };
}

/* ── HomePage ─────────────────────────────────────────── */

export default function HomePage() {
    const navigate = useNavigate();
    const [searchOpen, setSearchOpen] = useState(false);
    const [checkIn, setCheckIn] = useState('');
    const [checkOut, setCheckOut] = useState('');
    const [guests, setGuests] = useState(1);

    const staysRef = useRef(null);
    const stays = useCarousel(staysRef);
    const stripRef = useRef(null);
    const strip = useCarousel(stripRef, 300);

    useEffect(() => {
        window.scrollTo(0, 0);
        // Restore last search from localStorage (UX: user doesn't have to re-enter)
        try {
            const saved = JSON.parse(localStorage.getItem('hp_search'));
            if (saved?.checkIn) setCheckIn(saved.checkIn);
            if (saved?.checkOut) setCheckOut(saved.checkOut);
            if (saved?.guests) setGuests(saved.guests);
        } catch (e) {
            // localStorage unavailable or corrupted, no-op
        }
    }, []);

    // Save search params whenever they change
    useEffect(() => {
        try {
            localStorage.setItem('hp_search', JSON.stringify({ checkIn, checkOut, guests }));
        } catch (e) {
            // localStorage quota or unavailable
        }
    }, [checkIn, checkOut, guests]);

    const handleSearch = (e) => {
        e.preventDefault();
        const p = new URLSearchParams();
        if (checkIn) p.set('check_in', checkIn);
        if (checkOut) p.set('check_out', checkOut);
        if (guests > 1) p.set('guests', guests);
        navigate(`/book?${p}`);
    };

    return (
        <>
            {/* ─── Hero (unchanged) ─── */}
            <ScrollExpandMedia
                mediaType="video"
                mediaSrc="/legacy-video/icelandbeachhero.mp4"
                posterSrc="/images/hero.jpg"
                bgImageSrc="/images/hero.jpg"
                title="Iceland Beach Resort"
                date="Lagos, Nigeria"
                scrollToExpand="Scroll to explore"
                textBlend
            />

            {/* ─── 1  Search / Filter Bar ─── */}
            <motion.div
                className="hp-search-wrap"
                initial={{ opacity: 0, y: 22 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 1, ease: [0.16, 1, 0.3, 1], delay: 0.15 }}
            >
                {/* Mobile pill trigger */}
                <button
                    className="hp-search-pill"
                    onClick={() => setSearchOpen(true)}
                    aria-label="Open booking search"
                >
                    <CalendarDays size={16} />
                    Find a stay
                </button>

                {/* Full search bar (always visible desktop, overlay on mobile) */}
                <form
                    className={`hp-search-bar${searchOpen ? ' open' : ''}`}
                    onSubmit={handleSearch}
                >
                    <button
                        type="button"
                        className="hp-search-close"
                        onClick={() => setSearchOpen(false)}
                        aria-label="Close search"
                    >
                        <X size={22} />
                    </button>

                    <label className="hp-search-field">
                        <span>Check-in</span>
                        <input
                            type="date"
                            value={checkIn}
                            onChange={(e) => setCheckIn(e.target.value)}
                            required
                        />
                    </label>

                    <label className="hp-search-field">
                        <span>Check-out</span>
                        <input
                            type="date"
                            value={checkOut}
                            onChange={(e) => setCheckOut(e.target.value)}
                            required
                        />
                    </label>

                    <label className="hp-search-field">
                        <span>Guests</span>
                        <input
                            type="number"
                            min="1"
                            max="20"
                            value={guests}
                            onChange={(e) => setGuests(+e.target.value)}
                        />
                    </label>

                    <button type="submit" className="hp-search-cta">
                        Find a stay
                    </button>
                </form>

                {searchOpen && (
                    <div
                        className="hp-search-overlay"
                        onClick={() => setSearchOpen(false)}
                    />
                )}
            </motion.div>

            {/* ─── 2  Explore Stays Carousel ─── */}
            <motion.section
                className="hp-stays"
                initial="hidden"
                whileInView="show"
                viewport={{ once: true, amount: 0.2 }}
                variants={revealUp}
            >
                <div className="hp-stays-intro">
                    <h2>New ways to wake up to the Atlantic</h2>
                    <p>
                        Step beyond the ordinary with our exclusive beachfront suites and VIP beach lofts. Crafted for those who demand the finest, every stay delivers unmatched ocean views, absolute privacy, and elite island hospitality right by the Atlantic.
                    </p>
                    <Link to="/rooms" className="hp-link">
                        Reserve Your Coastal Escape <ArrowRight size={16} />
                    </Link>
                </div>

                <div className="hp-carousel-wrap">
                    <div className="hp-carousel" ref={staysRef}>
                        {stayCards.map((card) => (
                            <Link
                                to="/rooms"
                                className="hp-stay-card"
                                key={card.title}
                            >
                                <img src={card.img} alt={card.title} loading="lazy" />
                                <span className="hp-stay-label">{card.title}</span>
                            </Link>
                        ))}
                    </div>
                    <div className="hp-carousel-nav">
                        <button
                            onClick={stays.prev}
                            disabled={!stays.canPrev}
                            aria-label="Previous stays"
                        >
                            <ChevronLeft size={20} />
                        </button>
                        <button
                            onClick={stays.next}
                            disabled={!stays.canNext}
                            aria-label="Next stays"
                        >
                            <ChevronRight size={20} />
                        </button>
                    </div>
                </div>
            </motion.section>

            {/* ─── 3  Book With Confidence ─── */}
            <motion.section
                className="hp-confidence"
                initial="hidden"
                whileInView="show"
                viewport={{ once: true, amount: 0.2 }}
                variants={staggerGrid}
            >
                <AuroraLayer intensity={0.9} />
                <motion.h2 variants={revealUp}>Book with confidence</motion.h2>
                <motion.p className="hp-confidence-sub" variants={revealUp}>
                    Book directly with us for the best rate, flexible changes, and a
                    stay you can manage start to finish no third parties involved.
                </motion.p>
                <div className="hp-confidence-grid">
                    {confidenceCards.map(({ Icon, title, text }) => (
                        <motion.article key={title} variants={revealUp}>
                            <Icon size={28} />
                            <h3>{title}</h3>
                            <p>{text}</p>
                        </motion.article>
                    ))}
                </div>
            </motion.section>

            {/* ─── 4  Unlock Your Stay ─── */}
            <section
                className="hp-unlock"
                style={{
                    backgroundImage:
                        'linear-gradient(90deg, rgba(0,26,54,.88), rgba(0,26,54,.62)), url("/legacy-images/img (15).jpg")',
                }}
            >
                <motion.div
                    className="hp-unlock-copy"
                    initial={{ opacity: 0, y: 28 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
                >
                    <h2>Unlock the coast, every season</h2>
                    <p>
                        One property, four coastlines of experience from summer&apos;s
                        midnight sun to winter&apos;s aurora skies. However you stay,
                        there&apos;s a season here worth planning around.
                    </p>
                </motion.div>

                <div className="hp-strip-wrap">
                    <div className="hp-strip" ref={stripRef}>
                        {photoStripCards.map((card) => (
                            <article className="hp-strip-card" key={card.title}>
                                <img src={card.img} alt={card.title} loading="lazy" />
                                <div>
                                    <h3>{card.title}</h3>
                                    <p>{card.text}</p>
                                    <Link to="/rooms">
                                        {card.cta} <ArrowRight size={14} />
                                    </Link>
                                </div>
                            </article>
                        ))}
                    </div>
                    <div className="hp-carousel-nav light">
                        <button
                            onClick={strip.prev}
                            disabled={!strip.canPrev}
                            aria-label="Previous"
                        >
                            <ChevronLeft size={20} />
                        </button>
                        <button
                            onClick={strip.next}
                            disabled={!strip.canNext}
                            aria-label="Next"
                        >
                            <ChevronRight size={20} />
                        </button>
                    </div>
                </div>
            </section>

            {/* ─── 5  Partners Crosssell ─── */}
            <motion.section
                className="hp-partners"
                initial={{ opacity: 0, y: 28 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.3 }}
                transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
            >
                <div className="hp-partners-text">
                    <h2>Find your perfect spot</h2>
                    <p>
                        From shaded cabana beds to open-air loungers, our beachside spaces are designed for pure relaxation. Reserve your spot and make the most of your time at Iceland Beach Resort.
                    </p>
                    <Link to="/services" className="hp-link">
                        Explore Our Spaces <ArrowRight size={16} />
                    </Link>
                </div>
                <img
                    src="/legacy-images/gallery/imge (6).jpeg"
                    alt="Iceland Beach partners"
                    loading="lazy"
                />
            </motion.section>

            {/* ─── 6  Footer Banner ─── */}
            <motion.section
                className="hp-banner"
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.4 }}
                transition={{ duration: 0.9, ease: [0.16, 1, 0.3, 1] }}
            >
                <p>
                    <strong>Great stays start with great people.</strong> Our
                    team&apos;s care for every guest is what keeps people coming back
                    season after season.{' '}
                    <Link to="/book">Plan your next stay.</Link>
                </p>
            </motion.section>
        </>
    );
}
