import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import ScrollExpandMedia from '../components/ui/scroll-expansion-hero';

const facilities = [
    'Ocean Facing Beds',
    'Games Room',
    'Sport Court',
    'Adult Pool',
    'Children Pool',
    'Children Playground',
    'Childminding',
    'Beach Side Huts',
    'Restaurant & Bar',
    'Snooker Room',
    'Event Space',
    'High-speed Wi-Fi',
];

function LegacyImageRow({ images }) {
    return (
        <section className="legacy-image-row">
            {images.map((image, index) => (
                <img
                    key={image}
                    className={index % 2 ? 'offset' : ''}
                    src={`/legacy-images/${image}`}
                    alt="Iceland Beach Resort"
                />
            ))}
        </section>
    );
}

export default function HomePage() {
    useEffect(() => {
        window.scrollTo(0, 0);
    }, []);

    return (
        <>
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

            <section className="legacy-split">
                <div>
                    <h1>Escape to serenity, Indulge in luxury.</h1>
                    <p>
                        Nestled away from the hustle and bustle, our serene beach provides a haven for artists,
                        writers, and thinkers seeking inspiration. With its tranquil atmosphere, gentle waves, and
                        unspoiled natural beauty, this space is designed to spark creativity and foster collaboration.
                        Whether you&apos;re sketching, journaling, or simply reflecting, our beach offers a safe and
                        peaceful environment where ideas flow effortlessly.
                    </p>
                    <Link to="/services" className="legacy-dark-btn">Explore More</Link>
                    <Link to="/rooms" className="legacy-outline-btn">Book Rooms</Link>
                </div>
                <img src="/legacy-images/image3d.png" alt="3D beach" />
            </section>

            <section className="legacy-welcome">
                <h2>WELCOME TO ICELAND</h2>
                <p>
                    Nestled on the pristine shores, Iceland Private Beach Resort offers an exclusive haven for
                    discerning individuals seeking tranquility and refinement. Our resort embodies the essence of
                    elegance, blending breathtaking natural beauty with exquisite amenities.
                </p>
            </section>

            <LegacyImageRow images={['gallery/imge (11).jpeg', 'gallery/imge (13).jpeg', 'gallery/imge (5).jpeg', 'gallery/imge (4).jpeg']} />

            <section className="legacy-about">
                <img src="/legacy-images/gallery/imge (6).jpeg" alt="Iceland Beach" />
                <div>
                    <small>ABOUT ICELAND PRIVATE BEACH RESORT</small>
                    <h2>A Safe and Quiet Beach for the Creative Community</h2>
                    <p>
                        Nestled away from the hustle and bustle, our serene beach provides a haven for artists,
                        writers, and thinkers seeking inspiration. With its tranquil atmosphere, gentle waves, and
                        unspoiled natural beauty, this space is designed to spark creativity and foster collaboration.
                    </p>
                    <Link to="/about" className="legacy-dark-btn">Learn More</Link>
                </div>
                <img className="short" src="/legacy-images/gallery/imge (9).jpeg" alt="Iceland guest" />
                <div>
                    <small>OUR ROOMS</small>
                    <h2>THE COMFORTABLE AND LUXURIOUS ACCOMMODATION</h2>
                    <p>
                        Our resort offers a variety of luxurious and comfortable accommodations, including suites,
                        villas, and private villas. Our rooms are designed to cater to the needs of our guests,
                        ensuring that you have the perfect escape from the city.
                    </p>
                    <Link to="/rooms" className="legacy-dark-btn">View Our Rooms</Link>
                </div>
            </section>

            <section id="intro-video" className="legacy-intro">
                <h3>HERE IS A INTRODUCTION ABOUT US</h3>
                <a href="https://www.youtube.com/embed/SpdG_JpLmlw" target="_blank" rel="noreferrer">
                    <img src="/legacy-images/img (8).jpg" alt="Introduction to Iceland Beach" />
                    <span aria-hidden="true">&#9654;</span>
                </a>
            </section>

            <section id="facility" className="legacy-facilities">
                <h2>Facilities</h2>
                <div>
                    {facilities.map((facility, index) => (
                        <article key={facility}>
                            <strong>{String(index + 1).padStart(2, '0')}</strong>
                            <h3>{facility}</h3>
                            <p>
                                {facility === 'Ocean Facing Beds'
                                    ? 'Wake up to breathtaking views of the ocean.'
                                    : 'Enjoy more of Iceland Beach Resort during every visit.'}
                            </p>
                        </article>
                    ))}
                </div>
            </section>

            <LegacyImageRow images={['img (15).jpg', 'img (8).jpg', 'img (7).jpg', 'img (4).jpg']} />
        </>
    );
}
