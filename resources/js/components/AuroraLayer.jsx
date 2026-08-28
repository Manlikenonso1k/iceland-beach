import { lazy, Suspense } from 'react';

/**
 * Lazy wrapper around AuroraCanvas.
 *
 * The aurora is decorative, so Three.js must never sit in the main bundle —
 * it is code-split here and fetched only once a page that uses it mounts.
 * Until then (and on failure) the section simply renders without the layer.
 */
const AuroraCanvas = lazy(() => import('./AuroraCanvas'));

export default function AuroraLayer(props) {
    return (
        <Suspense fallback={null}>
            <AuroraCanvas {...props} />
        </Suspense>
    );
}
