import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Menu, X } from 'lucide-react';

const navLinks = [
    { to: '/about', label: 'About' },
    { to: '/rooms', label: 'Rooms' },
    { to: '/services', label: 'Spaces' },
    { to: '/event', label: 'Events' },
    { to: '/gallery', label: 'Gallery' },
    { to: '/contact', label: 'Contact' },
];

export default function Header() {
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const close = () => setOpen(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 40);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <header className={`site-header${scrolled ? ' is-scrolled' : ''}`}>
            <Link to="/" className="brand" onClick={close}>
                <img src="/images/iceland-logo.png" alt="Iceland Beach Resort" />
            </Link>

            <button
                className="menu"
                onClick={() => setOpen((v) => !v)}
                aria-label="Toggle navigation"
                aria-expanded={open}
            >
                {open ? <X /> : <Menu />}
            </button>

            <nav className={open ? 'open' : ''}>
                {navLinks.map(({ to, label }) => (
                    <Link key={to} to={to} onClick={close}>
                        {label}
                    </Link>
                ))}
                <Link to="/book" onClick={close} className="nav-cta">
                    Book your stay
                </Link>
            </nav>
        </header>
    );
}
