import { useEffect } from 'react';
import { motion } from 'framer-motion';
import { CheckCircle2, MapPin, Users } from 'lucide-react';
import AuroraLayer from '../components/AuroraLayer';

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

const features = [
    {
        Icon: MapPin,
        title: 'The setting',
        text: 'A distinctive beach resort experience in Lagos.',
    },
    {
        Icon: Users,
        title: 'The moments',
        text: 'From intimate stays to large celebrations.',
    },
    {
        Icon: CheckCircle2,
        title: 'The service',
        text: 'A team ready to make every visit easy.',
    },
];

export default function AboutPage() {
    useEffect(() => {
        window.scrollTo(0, 0);
    }, []);

    return (
        <>
            <section
                className="about-hero"
                style={{
                    backgroundImage:
                        'linear-gradient(90deg, rgba(11,15,14,.82), rgba(11,15,14,.32)), url("/legacy-images/hero-poster.jpg")',
                }}
            >
                <AuroraLayer intensity={0.75} />
                <motion.div
                    initial={{ opacity: 0, y: 24, filter: 'blur(6px)' }}
                    animate={{ opacity: 1, y: 0, filter: 'blur(0px)' }}
                    transition={{ duration: 1.1, ease: [0.16, 1, 0.3, 1] }}
                >
                    <p className="about-eyebrow">About Iceland Beach</p>
                    <h1>A private escape, built for good times.</h1>
                    <p>
                        Iceland Private Beach Resort brings accommodation, dining,
                        events and beach experiences together in one welcoming
                        destination.
                    </p>
                </motion.div>
            </section>

            <section className="about-content">
                <motion.h2
                    initial="hidden"
                    whileInView="show"
                    viewport={{ once: true, amount: 0.4 }}
                    variants={revealUp}
                >
                    More than a stay.
                </motion.h2>
                <motion.p
                    className="about-lede"
                    initial="hidden"
                    whileInView="show"
                    viewport={{ once: true, amount: 0.4 }}
                    variants={revealUp}
                >
                    Come for the ocean air and leave with stories. Our spaces are
                    designed for quiet getaways, family visits, celebrations and
                    unforgettable moments by the shore.
                </motion.p>

                <motion.div
                    className="about-feature-grid"
                    initial="hidden"
                    whileInView="show"
                    viewport={{ once: true, amount: 0.2 }}
                    variants={staggerGrid}
                >
                    {features.map(({ Icon, title, text }) => (
                        <motion.article key={title} variants={revealUp}>
                            <Icon />
                            <h3>{title}</h3>
                            <p>{text}</p>
                        </motion.article>
                    ))}
                </motion.div>
            </section>
        </>
    );
}
