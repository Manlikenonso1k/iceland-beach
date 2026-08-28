import { Link } from 'react-router-dom';
import { ChevronRight, ImageIcon } from 'lucide-react';
import {
    ContainerAnimated,
    ContainerScroll,
    ContainerStagger,
    ContainerSticky,
    GalleryCol,
    GalleryContainer,
} from '../components/ui/animated-gallery';

const legacyImage = (file) => `/legacy-images/${file}`;

const IMAGES_1 = [
    'gallery.jpg',
    'Beach Front 2.jpeg',
    'Coconut Cabana.jpeg',
    'DJ space.jpg',
];

const IMAGES_2 = [
    'Double Bed (2).jpeg',
    'Canopy Tent 1.jpeg',
    'music (1).jpeg',
    'img (1).jpg',
];

const IMAGES_3 = [
    'img (2).jpg',
    'img (3).jpg',
    'img (4).jpg',
    'img (5).jpg',
];

function GalleryImage({ src, alt }) {
    return (
        <img
            className="aspect-video block h-auto max-h-full w-full rounded-md object-cover shadow-md"
            src={legacyImage(src)}
            alt={alt}
            loading="lazy"
        />
    );
}

export default function GalleryPage() {
    return (
        <main className="relative bg-[#f5f1e8]">
            <ContainerStagger className="relative z-[1] -mb-12 place-self-center px-6 pt-12 text-center">
                <ContainerAnimated>
                    <p className="text-sm font-bold uppercase tracking-[0.2em] text-[#005b7f]">
                        Iceland memories
                    </p>
                </ContainerAnimated>
                <ContainerAnimated>
                    <h1 className="font-serif text-4xl font-extralight text-[#103b37] md:text-5xl">
                        The view is always{' '}
                        <span className="font-serif font-extralight text-[#005b7f]">better here</span>
                    </h1>
                </ContainerAnimated>
                <ContainerAnimated>
                    <h2 className="font-serif text-3xl font-extralight text-[#103b37] md:text-4xl">
                        at Iceland Beach Resort
                    </h2>
                </ContainerAnimated>

                <ContainerAnimated className="my-4">
                    <p className="leading-normal tracking-tight text-[#4b5b59]">
                        A few moments from life at the beach  rooms, spaces, events,
                        <br className="hidden md:block" /> and the shoreline that brings it all together.
                    </p>
                </ContainerAnimated>

                <ContainerAnimated className="flex flex-wrap items-center justify-center gap-3">
                    <Link
                        to="/book"
                        className="inline-flex items-center gap-2 rounded-md bg-[#005b7f] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#004a66]"
                    >
                        Book your stay <ChevronRight className="size-4" />
                    </Link>
                    <Link
                        to="/about"
                        className="inline-flex items-center gap-2 text-sm font-semibold text-[#005b7f] underline-offset-4 hover:underline"
                    >
                        About Iceland <ImageIcon className="size-4" />
                    </Link>
                </ContainerAnimated>
            </ContainerStagger>

            <div
                className="pointer-events-none absolute z-10 h-[70vh] w-full"
                style={{
                    background: 'linear-gradient(to right, #005b7f, #083c39, #d6732c)',
                    filter: 'blur(84px)',
                    mixBlendMode: 'screen',
                    opacity: 0.35,
                }}
            />

            <ContainerScroll className="relative h-[350vh] px-4 md:px-8">
                <ContainerSticky className="h-svh">
                    <GalleryContainer>
                        <GalleryCol yRange={['-10%', '2%']} className="-mt-2">
                            {IMAGES_1.map((image, index) => (
                                <GalleryImage
                                    key={image}
                                    src={image}
                                    alt={`Iceland Beach Resort gallery ${index + 1}`}
                                />
                            ))}
                        </GalleryCol>
                        <GalleryCol className="mt-[-50%]" yRange={['15%', '5%']}>
                            {IMAGES_2.map((image, index) => (
                                <GalleryImage
                                    key={image}
                                    src={image}
                                    alt={`Iceland Beach Resort gallery ${index + 5}`}
                                />
                            ))}
                        </GalleryCol>
                        <GalleryCol yRange={['-10%', '2%']} className="-mt-2">
                            {IMAGES_3.map((image, index) => (
                                <GalleryImage
                                    key={image}
                                    src={image}
                                    alt={`Iceland Beach Resort gallery ${index + 9}`}
                                />
                            ))}
                        </GalleryCol>
                    </GalleryContainer>
                </ContainerSticky>
            </ContainerScroll>
        </main>
    );
}
