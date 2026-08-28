import { useEffect, useRef } from 'react';
import * as THREE from 'three';

/**
 * Ambient WebGL aurora / volumetric-fog layer.
 *
 * Purely decorative background (aria-hidden, pointer-events:none) — it never
 * sits in front of content or intercepts clicks. Renders a single fullscreen
 * quad driven by an fbm-noise fragment shader: drifting aurora curtains over a
 * low fog bank, in the teal / aurora-green / violet palette.
 *
 * Performance guards:
 *   - render loop is paused whenever the layer is off-screen (IntersectionObserver)
 *   - device pixel ratio capped at 1.5
 *   - prefers-reduced-motion renders one static frame and stops
 *   - falls back to a CSS gradient if WebGL is unavailable
 */

const VERT = `
varying vec2 vUv;
void main() {
    vUv = uv;
    gl_Position = vec4(position, 1.0);
}
`;

const FRAG = `
precision highp float;

uniform float uTime;
uniform float uIntensity;
varying vec2 vUv;

float hash(vec2 p) {
    return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453123);
}

float noise(vec2 p) {
    vec2 i = floor(p);
    vec2 f = fract(p);
    vec2 u = f * f * (3.0 - 2.0 * f);
    return mix(
        mix(hash(i + vec2(0.0, 0.0)), hash(i + vec2(1.0, 0.0)), u.x),
        mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x),
        u.y
    );
}

float fbm(vec2 p) {
    float v = 0.0;
    float a = 0.5;
    for (int i = 0; i < 5; i++) {
        v += a * noise(p);
        p *= 2.02;
        a *= 0.5;
    }
    return v;
}

void main() {
    vec2 uv = vUv;
    float t = uTime * 0.035;

    vec2 p = vec2(uv.x * 2.2, uv.y * 1.1);
    float n1 = fbm(p + vec2(t, t * 0.4));
    float n2 = fbm(p * 1.7 + vec2(-t * 0.7, t * 0.25));

    // ridged, slowly drifting curtain bands
    float band = abs(sin((uv.x * 3.0 + n1 * 2.4 + t * 1.2) * 2.0));
    float curtain = pow(1.0 - band, 3.0);

    // aurora hangs in the upper band of the layer
    float vert = smoothstep(0.0, 0.85, uv.y) * smoothstep(1.05, 0.35, uv.y);
    float a = curtain * vert * (0.55 + n2 * 0.75);

    vec3 teal = vec3(0.16, 0.52, 0.55);
    vec3 green = vec3(0.30, 0.78, 0.62);
    vec3 violet = vec3(0.42, 0.30, 0.62);
    vec3 col = mix(teal, green, smoothstep(0.15, 0.70, n1));
    col = mix(col, violet, smoothstep(0.45, 1.00, n2));

    // low drifting fog bank along the bottom edge
    float fog = fbm(vec2(uv.x * 1.6 + t * 0.5, uv.y * 2.0 - t * 0.2));
    fog *= smoothstep(0.70, 0.0, uv.y) * 0.35;
    col += vec3(0.20, 0.35, 0.38) * fog;
    a += fog * 0.5;

    a = clamp(a, 0.0, 1.0) * uIntensity;
    gl_FragColor = vec4(col * a, a);
}
`;

export default function AuroraCanvas({ className = '', intensity = 1 }) {
    const mountRef = useRef(null);

    useEffect(() => {
        const mount = mountRef.current;
        if (!mount) return undefined;

        // WebGL capability check — degrade to the CSS gradient fallback
        let probe = null;
        try {
            const testCanvas = document.createElement('canvas');
            probe =
                testCanvas.getContext('webgl2') || testCanvas.getContext('webgl');
        } catch (err) {
            probe = null;
        }
        if (!probe) {
            mount.classList.add('aurora-fallback');
            return undefined;
        }

        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        const renderer = new THREE.WebGLRenderer({
            alpha: true,
            antialias: false,
            powerPreference: 'low-power',
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
        renderer.setSize(mount.clientWidth || 1, mount.clientHeight || 1);
        mount.appendChild(renderer.domElement);

        const scene = new THREE.Scene();
        const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

        const uniforms = {
            uTime: { value: 0 },
            uIntensity: { value: intensity },
        };

        const material = new THREE.ShaderMaterial({
            vertexShader: VERT,
            fragmentShader: FRAG,
            uniforms,
            transparent: true,
            depthWrite: false,
            blending: THREE.AdditiveBlending,
        });
        const mesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material);
        scene.add(mesh);

        const handleResize = () => {
            renderer.setSize(mount.clientWidth || 1, mount.clientHeight || 1);
        };
        window.addEventListener('resize', handleResize);

        let frame = null;
        let last = performance.now();
        let elapsed = 0;

        const loop = (now) => {
            frame = requestAnimationFrame(loop);
            const dt = Math.min((now - last) / 1000, 0.05);
            last = now;
            elapsed += dt;
            uniforms.uTime.value = elapsed;
            renderer.render(scene, camera);
        };

        let observer = null;

        if (reduceMotion) {
            // one representative static frame, no loop
            uniforms.uTime.value = 12.0;
            renderer.render(scene, camera);
        } else {
            observer = new IntersectionObserver(
                ([entry]) => {
                    if (entry.isIntersecting && frame === null) {
                        last = performance.now();
                        frame = requestAnimationFrame(loop);
                    } else if (!entry.isIntersecting && frame !== null) {
                        cancelAnimationFrame(frame);
                        frame = null;
                    }
                },
                { threshold: 0.01 }
            );
            observer.observe(mount);
        }

        return () => {
            window.removeEventListener('resize', handleResize);
            if (observer) observer.disconnect();
            if (frame !== null) cancelAnimationFrame(frame);
            mesh.geometry.dispose();
            material.dispose();
            renderer.dispose();
            if (renderer.domElement.parentNode === mount) {
                mount.removeChild(renderer.domElement);
            }
        };
    }, [intensity]);

    return (
        <div
            ref={mountRef}
            className={`aurora-canvas${className ? ` ${className}` : ''}`}
            aria-hidden="true"
        />
    );
}
