import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';

const schema = {
    '@context': 'https://schema.org',
    '@type': 'EducationalOrganization',
    name: 'Iceland Private Beach Resorts',
    url: 'https://tenstrings.org',
    description:
        'Nestled on the pristine shores, Iceland Private Beach Resort offers an exclusive haven for discerning individuals seeking tranquility and refinement. Our resort embodies the essence of elegance, blending breathtaking natural beauty with exquisite amenities.',
    address: {
        '@type': 'PostalAddress',
        addressLocality: 'Lagos',
        addressCountry: 'Nigeria',
    },
    course: [
        { '@type': 'Services', name: 'About Iceland', url: 'https://iceland.com/about' },
        { '@type': 'Services', name: 'Iceland Services', url: 'https://iceland.com/soon' },
        { '@type': 'Services', name: 'Our Gallery', url: 'https://iceland.com/gallery' },
        { '@type': 'Services', name: 'Book Room', url: 'https://iceland.com/rooms' },
    ],
};

const socialLinks = [
    { glyph: 'f', label: 'Facebook' },
    { glyph: 'X', label: 'Twitter' },
    { glyph: 'ig', label: 'Instagram' },
    { glyph: 'in', label: 'LinkedIn' },
    { glyph: 'yt', label: 'YouTube' },
];

export default function Footer() {
    useEffect(() => {
        const scriptId = 'iceland-footer-schema';
        if (document.getElementById(scriptId)) {
            return;
        }

        const script = document.createElement('script');
        script.id = scriptId;
        script.type = 'application/ld+json';
        script.textContent = JSON.stringify(schema);
        document.head.appendChild(script);

        return () => {
            document.getElementById(scriptId)?.remove();
        };
    }, []);

    return (
        <motion.footer
            className="legacy-footer"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, amount: 0.15 }}
            transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
        >
            <div className="legacy-footer-glow" aria-hidden="true" />

            <div className="legacy-footer-grid">
                <section>
                    <img src="/images/iceland-logo.png" alt="Iceland Beach logo" width="90" />
                    <p>
                        Escape to the pristine shores of Iceland Beach, where tranquility meets adventure.
                        Enjoy our top-notch facilities and immerse yourself in the beauty of nature.
                    </p>
                </section>

                <section>
                    <h3>Quick Links</h3>
                    <Link to="/">Home</Link>
                    <a href="/#facility">Facilities</a>
                    <Link to="/about">About Us</Link>
                    <Link to="/contact">Contact</Link>
                    <a href="https://nonsostudio.com/" target="_blank" rel="noreferrer">
                        Create Your business website
                    </a>
                </section>

                <section>
                    <h3>Follow Us</h3>
                    <div className="social-links">
                        {socialLinks.map(({ glyph, label }) => (
                            <a href="#" aria-label={label} key={label}>
                                {glyph}
                            </a>
                        ))}
                    </div>
                </section>
            </div>

            <div className="legacy-footer-bottom">
                <p>&copy; {new Date().getFullYear()} Iceland Beach. All Rights Reserved.</p>
                <p>
                    Made with love{' '}
                    <a href="https://nonsostudio.com/" target="_blank" rel="noreferrer">
                        Chinonso
                    </a>
                </p>
            </div>
        </motion.footer>
    );
}
