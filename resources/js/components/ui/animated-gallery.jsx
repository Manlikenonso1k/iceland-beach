import { createContext, forwardRef, useContext, useRef } from 'react';
import { motion, useScroll, useTransform } from 'framer-motion';
import { cn } from '../../lib/utils';

const SPRING_CONFIG = {
    type: 'spring',
    stiffness: 100,
    damping: 16,
    mass: 0.75,
    restDelta: 0.005,
    duration: 0.3,
};

const blurVariants = {
    hidden: {
        filter: 'blur(10px)',
        opacity: 0,
    },
    visible: {
        filter: 'blur(0px)',
        opacity: 1,
    },
};

const ContainerScrollContext = createContext(undefined);

function useContainerScrollContext() {
    const context = useContext(ContainerScrollContext);
    if (!context) {
        throw new Error('useContainerScrollContext must be used within a ContainerScroll component');
    }
    return context;
}

export function ContainerScroll({ children, className, style, ...props }) {
    const scrollRef = useRef(null);
    const { scrollYProgress } = useScroll({
        target: scrollRef,
    });

    return (
        <ContainerScrollContext.Provider value={{ scrollYProgress }}>
            <div
                ref={scrollRef}
                className={cn('relative min-h-[120vh]', className)}
                style={{
                    perspective: '1000px',
                    perspectiveOrigin: 'center top',
                    transformStyle: 'preserve-3d',
                    ...style,
                }}
                {...props}
            >
                {children}
            </div>
        </ContainerScrollContext.Provider>
    );
}

export function ContainerSticky({ className, style, ...props }) {
    return (
        <div
            className={cn('sticky left-0 top-0 min-h-[30rem] w-full overflow-hidden', className)}
            style={{
                perspective: '1000px',
                perspectiveOrigin: 'center top',
                transformStyle: 'preserve-3d',
                transformOrigin: '50% 50%',
                ...style,
            }}
            {...props}
        />
    );
}

export function GalleryContainer({ children, className, style, ...props }) {
    const { scrollYProgress } = useContainerScrollContext();
    const rotateX = useTransform(scrollYProgress, [0, 0.5], [75, 0]);
    const scale = useTransform(scrollYProgress, [0.5, 0.9], [1.2, 1]);

    return (
        <motion.div
            className={cn('relative grid size-full grid-cols-3 gap-2 rounded-2xl', className)}
            style={{
                rotateX,
                scale,
                transformStyle: 'preserve-3d',
                perspective: '1000px',
                ...style,
            }}
            {...props}
        >
            {children}
        </motion.div>
    );
}

export function GalleryCol({ className, style, yRange = ['0%', '-10%'], ...props }) {
    const { scrollYProgress } = useContainerScrollContext();
    const y = useTransform(scrollYProgress, [0.5, 1], yRange);

    return (
        <motion.div
            className={cn('relative flex w-full flex-col gap-2', className)}
            style={{
                y,
                ...style,
            }}
            {...props}
        />
    );
}

export const ContainerStagger = forwardRef(function ContainerStagger(
    { className, viewport, transition, ...props },
    ref,
) {
    return (
        <motion.div
            className={cn('relative', className)}
            ref={ref}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, ...viewport }}
            transition={{
                staggerChildren: transition?.staggerChildren || 0.2,
                ...transition,
            }}
            {...props}
        />
    );
});

export const ContainerAnimated = forwardRef(function ContainerAnimated(
    { className, transition, ...props },
    ref,
) {
    return (
        <motion.div
            ref={ref}
            className={cn(className)}
            variants={blurVariants}
            transition={transition || SPRING_CONFIG}
            {...props}
        />
    );
});
