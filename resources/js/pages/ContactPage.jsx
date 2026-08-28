import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { CheckCircle2, ChevronRight, Mail, Phone } from 'lucide-react';
import AuroraLayer from '../components/AuroraLayer';

const revealUp = {
    hidden: { opacity: 0, y: 28 },
    show: {
        opacity: 1,
        y: 0,
        transition: { duration: 1, ease: [0.16, 1, 0.3, 1] },
    },
};

export default function ContactPage() {
    const [sent, setSent] = useState(false);

    useEffect(() => {
        window.scrollTo(0, 0);
    }, []);

    return (
        <>
            <section
                className="about-hero"
                style={{
                    backgroundImage:
                        'linear-gradient(90deg, rgba(11,15,14,.82), rgba(11,15,14,.32)), url("/legacy-images/Coconut Cabana.jpeg")',
                }}
            >
                <AuroraLayer intensity={0.75} />
                <motion.div
                    initial={{ opacity: 0, y: 24, filter: 'blur(6px)' }}
                    animate={{ opacity: 1, y: 0, filter: 'blur(0px)' }}
                    transition={{ duration: 1.1, ease: [0.16, 1, 0.3, 1] }}
                >
                    <p className="about-eyebrow">Contact Iceland Beach</p>
                    <h1>We would like to hear from you.</h1>
                    <p>
                        For reservations, memberships, events and general
                        enquiries, get in touch with our team.
                    </p>
                </motion.div>
            </section>

            <section className="contact-page contact-grid">
                <motion.div
                    initial="hidden"
                    whileInView="show"
                    viewport={{ once: true, amount: 0.4 }}
                    variants={revealUp}
                >
                    <h2>Let&apos;s plan your visit.</h2>
                    <p className="lede">
                        Use the booking page for room availability. For all other
                        enquiries, email or call the resort team.
                    </p>
                    <p className="contact-line">
                        <Mail size={18} /> booking@icelandbeach.com
                    </p>
                    <p className="contact-line">
                        <Phone size={18} /> Contact the resort directly
                    </p>
                </motion.div>

                <motion.form
                    className="booking-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        setSent(true);
                    }}
                    initial="hidden"
                    whileInView="show"
                    viewport={{ once: true, amount: 0.3 }}
                    variants={revealUp}
                >
                    <h2>Send an enquiry</h2>
                    <label>
                        Name
                        <input required placeholder="Full name" />
                    </label>
                    <label>
                        Email
                        <input required type="email" placeholder="you@example.com" />
                    </label>
                    <label>
                        Message
                        <textarea required placeholder="How can we help?" rows="5" />
                    </label>
                    {sent && (
                        <div className="notice success">
                            <CheckCircle2 size={18} /> Thanks — please email
                            booking@icelandbeach.com and our team will reply.
                        </div>
                    )}
                    <button className="button full">
                        Prepare email <ChevronRight size={18} />
                    </button>
                </motion.form>
            </section>
        </>
    );
}
