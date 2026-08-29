import { useEffect, useRef, useState } from 'react';
import { motion } from 'framer-motion';

export default function ScrollExpandMedia({
    mediaType = 'video',
    mediaSrc,
    posterSrc,
    bgImageSrc,
    title,
    date,
    scrollToExpand,
    textBlend,
    children,
}) {
    const [scrollProgress, setScrollProgress] = useState(0);
    const [showContent, setShowContent] = useState(false);
    const [mediaFullyExpanded, setMediaFullyExpanded] = useState(false);
    const [touchStartY, setTouchStartY] = useState(0);
    const [isMobileState, setIsMobileState] = useState(false);
    const [videoReady, setVideoReady] = useState(false);
    const sectionRef = useRef(null);
    const videoRef = useRef(null);

    useEffect(() => {
        setScrollProgress(0);
        setShowContent(false);
        setMediaFullyExpanded(false);
    }, [mediaType]);

    useEffect(() => {
        const handleWheel = (event) => {
            if (mediaFullyExpanded && event.deltaY < 0 && window.scrollY <= 5) {
                setMediaFullyExpanded(false);
                event.preventDefault();
            } else if (!mediaFullyExpanded) {
                event.preventDefault();
                const scrollDelta = event.deltaY * 0.0009;
                const newProgress = Math.min(Math.max(scrollProgress + scrollDelta, 0), 1);
                setScrollProgress(newProgress);

                if (newProgress >= 1) {
                    setMediaFullyExpanded(true);
                    setShowContent(true);
                } else if (newProgress < 0.75) {
                    setShowContent(false);
                }
            }
        };

        const handleTouchStart = (event) => {
            setTouchStartY(event.touches[0].clientY);
        };

        const handleTouchMove = (event) => {
            if (!touchStartY) return;

            const touchY = event.touches[0].clientY;
            const deltaY = touchStartY - touchY;

            if (mediaFullyExpanded && deltaY < -20 && window.scrollY <= 5) {
                setMediaFullyExpanded(false);
                event.preventDefault();
            } else if (!mediaFullyExpanded) {
                event.preventDefault();
                const scrollFactor = deltaY < 0 ? 0.008 : 0.005;
                const scrollDelta = deltaY * scrollFactor;
                const newProgress = Math.min(Math.max(scrollProgress + scrollDelta, 0), 1);
                setScrollProgress(newProgress);

                if (newProgress >= 1) {
                    setMediaFullyExpanded(true);
                    setShowContent(true);
                } else if (newProgress < 0.75) {
                    setShowContent(false);
                }

                setTouchStartY(touchY);
            }
        };

        const handleTouchEnd = () => {
            setTouchStartY(0);
        };

        const handleScroll = () => {
            if (!mediaFullyExpanded) {
                window.scrollTo(0, 0);
            }
        };

        window.addEventListener('wheel', handleWheel, { passive: false });
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('touchstart', handleTouchStart, { passive: false });
        window.addEventListener('touchmove', handleTouchMove, { passive: false });
        window.addEventListener('touchend', handleTouchEnd);

        return () => {
            window.removeEventListener('wheel', handleWheel);
            window.removeEventListener('scroll', handleScroll);
            window.removeEventListener('touchstart', handleTouchStart);
            window.removeEventListener('touchmove', handleTouchMove);
            window.removeEventListener('touchend', handleTouchEnd);
        };
    }, [scrollProgress, mediaFullyExpanded, touchStartY]);

    useEffect(() => {
        const checkIfMobile = () => {
            setIsMobileState(window.innerWidth < 768);
        };

        checkIfMobile();
        window.addEventListener('resize', checkIfMobile);

        return () => window.removeEventListener('resize', checkIfMobile);
    }, []);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting && videoRef.current && !videoReady) {
                    setVideoReady(true);
                    videoRef.current.play().catch(() => {});
                }
            },
            { threshold: 0.1 }
        );

        if (sectionRef.current) observer.observe(sectionRef.current);
        return () => observer.disconnect();
    }, [videoReady]);

    const mediaWidth = 300 + scrollProgress * (isMobileState ? 650 : 1250);
    const mediaHeight = 400 + scrollProgress * (isMobileState ? 200 : 400);
    const textTranslateX = scrollProgress * (isMobileState ? 180 : 150);
    const firstWord = title ? title.split(' ')[0] : '';
    const restOfTitle = title ? title.split(' ').slice(1).join(' ') : '';

    return (
        <div ref={sectionRef} className="relative z-0 transition-colors duration-700 ease-in-out overflow-x-hidden">
            <section className="relative flex flex-col items-center justify-start min-h-[calc(100dvh-78px)]">
                <div className="relative w-full flex flex-col items-center min-h-[calc(100dvh-78px)]">
                    <motion.div
                        className="absolute inset-0 z-0 h-full"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 - scrollProgress }}
                        transition={{ duration: 0.1 }}
                    >
                        <img
                            src={bgImageSrc}
                            alt=""
                            loading="lazy"
                            className="w-full h-full object-cover object-center"
                        />
                        <div className="absolute inset-0 bg-[#002a34]/25" />
                    </motion.div>

                    <div className="container mx-auto flex flex-col items-center justify-start relative z-[1]">
                        <div className="flex flex-col items-center justify-center w-full h-[calc(100dvh-78px)] relative">
                            <div
                                className="absolute z-0 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 transition-none rounded-2xl"
                                style={{
                                    width: `${mediaWidth}px`,
                                    height: `${mediaHeight}px`,
                                    maxWidth: '95vw',
                                    maxHeight: '85vh',
                                    boxShadow: '0px 0px 50px rgba(0, 0, 0, 0.3)',
                                }}
                            >
                                {mediaType === 'video' ? (
                                    <div className="relative w-full h-full pointer-events-none">
                                        <video
                                            ref={videoRef}
                                            src={mediaSrc}
                                            poster={posterSrc}
                                            muted
                                            loop
                                            playsInline
                                            preload="none"
                                            className="w-full h-full object-cover rounded-xl"
                                            controls={false}
                                            disablePictureInPicture
                                            disableRemotePlayback
                                        />
                                        <div className="absolute inset-0 z-[1] pointer-events-none" />
                                        <motion.div
                                            className="absolute inset-0 bg-[#002a34]/30 rounded-xl"
                                            initial={{ opacity: 0.7 }}
                                            animate={{ opacity: 0.5 - scrollProgress * 0.3 }}
                                            transition={{ duration: 0.2 }}
                                        />
                                    </div>
                                ) : (
                                    <div className="relative w-full h-full">
                                        <img
                                            src={mediaSrc}
                                            alt={title || 'Media content'}
                                            className="w-full h-full object-cover rounded-xl"
                                        />
                                        <motion.div
                                            className="absolute inset-0 bg-[#002a34]/50 rounded-xl"
                                            initial={{ opacity: 0.7 }}
                                            animate={{ opacity: 0.7 - scrollProgress * 0.3 }}
                                            transition={{ duration: 0.2 }}
                                        />
                                    </div>
                                )}

                                <div className="flex flex-col items-center text-center relative z-[1] mt-4 transition-none">
                                    {date && (
                                        <p
                                            className="text-xl md:text-2xl text-[#d4eef2] font-medium tracking-wide"
                                            style={{ transform: `translateX(-${textTranslateX}vw)` }}
                                        >
                                            {date}
                                        </p>
                                    )}
                                    {scrollToExpand && (
                                        <p
                                            className="text-[#d4eef2] font-medium text-center text-sm md:text-base"
                                            style={{ transform: `translateX(${textTranslateX}vw)` }}
                                        >
                                            {scrollToExpand}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div
                                className={`flex items-center justify-center text-center gap-2 md:gap-4 w-full relative z-[1] transition-none flex-col px-4 ${
                                    textBlend ? 'mix-blend-difference' : 'mix-blend-normal'
                                }`}
                            >
                                <motion.h2
                                    className="text-4xl md:text-5xl lg:text-6xl font-bold text-[#e8f6f8] transition-none uppercase tracking-tight"
                                    style={{ transform: `translateX(-${textTranslateX}vw)` }}
                                >
                                    {firstWord}
                                </motion.h2>
                                <motion.h2
                                    className="text-4xl md:text-5xl lg:text-6xl font-bold text-center text-[#e8f6f8] transition-none uppercase tracking-tight"
                                    style={{ transform: `translateX(${textTranslateX}vw)` }}
                                >
                                    {restOfTitle}
                                </motion.h2>
                            </div>
                        </div>

                        {children && (
                            <motion.section
                                className="flex flex-col w-full px-8 py-10 md:px-16 lg:py-20"
                                initial={{ opacity: 0 }}
                                animate={{ opacity: showContent ? 1 : 0 }}
                                transition={{ duration: 0.7 }}
                            >
                                {children}
                            </motion.section>
                        )}
                    </div>
                </div>
            </section>
        </div>
    );
}
