// public/js/line-sidebar.js
// Vanilla JS port of React LineSidebar
// Auto-attach ke element dengan attribute [data-line-sidebar]

(function () {
    'use strict';

    const FALLOFF_CURVES = {
        linear: p => p,
        smooth: p => p * p * (3 - 2 * p),
        sharp: p => p * p * p
    };

    // ============ CSS INJECTION ============
    function injectCSS() {
        if (document.getElementById('line-sidebar-style')) return;

        const css = `
        .line-sidebar {
            --accent-color: #3B82F6;
            --text-color: #cbd5e1;
            --marker-color: #475569;
            --marker-length: 40px;
            --marker-gap: 0px;
            --tick-scale: 0.5;
            --max-shift: 20px;
            --item-gap: 4px;
            --font-size: 0.875rem;
            --smoothing: 100ms;
            position: relative;
            display: flex;
            justify-content: flex-start;
            width: 100%;
        }

        .line-sidebar__list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: var(--item-gap);
            width: 100%;
        }

        .line-sidebar__item {
            position: relative;
            cursor: pointer;
            display: block;
            text-decoration: none;
        }

        .line-sidebar__item::before {
            content: '';
            position: absolute;
            inset: -4px -12px;
        }

        .line-sidebar__label {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            font-size: var(--font-size);
            line-height: 1.3;
            font-weight: 500;
            padding: 0.55rem 0.75rem;
            border-radius: 0.6rem;
            color: color-mix(in srgb, var(--accent-color) calc(var(--effect, 0) * 100%), var(--text-color));
            transform: translateX(calc(var(--effect, 0) * var(--max-shift)));
            background-color: color-mix(in srgb, var(--accent-color) calc(var(--effect, 0) * 14%), transparent);
            width: 100%;
        }

        .line-sidebar__item[aria-current="true"] .line-sidebar__label {
            color: var(--accent-color);
            background-color: color-mix(in srgb, var(--accent-color) 14%, transparent);
            font-weight: 600;
        }

        .line-sidebar__item[aria-current="true"] .line-sidebar__label .material-symbols-outlined {
            color: var(--accent-color);
        }

        .line-sidebar__index {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75em;
            opacity: calc(0.55 + var(--effect, 0) * 0.45);
            min-width: 1.5em;
        }

        .line-sidebar__marker {
            position: absolute;
            top: 50%;
            left: 0;
            height: 1.5px;
            width: var(--marker-length);
            background-color: color-mix(in srgb, var(--accent-color) calc(var(--effect, 0) * 100%), var(--marker-color));
            transform-origin: left center;
            transform: translateY(-50%) scaleX(calc(0.7 + var(--effect, 0) * 0.5));
            border-radius: 2px;
            pointer-events: none;
        }
        `;

        const style = document.createElement('style');
        style.id = 'line-sidebar-style';
        style.textContent = css;
        document.head.appendChild(style);
    }

    // ============ INIT ONE SIDEBAR ============
    function initLineSidebar(root, userOpts = {}) {
        if (!root || root.__lineSidebarInit) return;
        root.__lineSidebarInit = true;

        const opts = Object.assign({
            accentColor: '#3B82F6',
            textColor: '#cbd5e1',
            markerColor: '#475569',
            showIndex: false,
            showMarker: true,
            proximityRadius: 100,
            maxShift: 20,
            falloff: 'smooth',
            markerLength: 40,
            markerGap: 0,
            tickScale: 0.5,
            scaleTick: true,
            itemGap: 4,
            fontSize: 0.875,
            smoothing: 100
        }, userOpts);

        root.style.setProperty('--accent-color', opts.accentColor);
        root.style.setProperty('--text-color', opts.textColor);
        root.style.setProperty('--marker-color', opts.markerColor);
        root.style.setProperty('--marker-length', opts.markerLength + 'px');
        root.style.setProperty('--marker-gap', opts.markerGap + 'px');
        root.style.setProperty('--tick-scale', opts.tickScale);
        root.style.setProperty('--max-shift', opts.maxShift + 'px');
        root.style.setProperty('--item-gap', opts.itemGap + 'px');
        root.style.setProperty('--font-size', opts.fontSize + 'rem');
        root.style.setProperty('--smoothing', opts.smoothing + 'ms');

        const items = Array.from(root.querySelectorAll('[data-line-item]'));
        if (items.length === 0) return;

        items.forEach((el, i) => {
            el.classList.add('line-sidebar__item');

            if (!el.querySelector('.line-sidebar__label')) {
                const label = document.createElement('span');
                label.className = 'line-sidebar__label';
                while (el.firstChild) {
                    label.appendChild(el.firstChild);
                }
                el.appendChild(label);
            }

            if (opts.showMarker && !el.querySelector('.line-sidebar__marker')) {
                const marker = document.createElement('span');
                marker.className = 'line-sidebar__marker';
                marker.setAttribute('aria-hidden', 'true');
                el.insertBefore(marker, el.firstChild);
            }

            if (opts.showIndex) {
                const label = el.querySelector('.line-sidebar__label');
                if (label && !label.querySelector('.line-sidebar__index')) {
                    const idx = document.createElement('span');
                    idx.className = 'line-sidebar__index';
                    idx.textContent = String(i + 1).padStart(2, '0');
                    label.insertBefore(idx, label.firstChild);
                }
            }
        });

        const targets = new Array(items.length).fill(0);
        const currents = new Array(items.length).fill(0);
        let rafId = null;
        let lastTime = performance.now();

        function getActiveIndex() {
            return items.findIndex(el => el.getAttribute('aria-current') === 'true');
        }

        function runFrame(now) {
            const dt = Math.min((now - lastTime) / 1000, 0.05);
            lastTime = now;
            const tau = Math.max(opts.smoothing, 1) / 1000;
            const k = 1 - Math.exp(-dt / tau);
            const activeIdx = getActiveIndex();

            let moving = false;
            for (let i = 0; i < items.length; i++) {
                const el = items[i];
                if (!el) continue;
                const target = Math.max(targets[i] || 0, activeIdx === i ? 1 : 0);
                const cur = currents[i] || 0;
                const next = cur + (target - cur) * k;
                const settled = Math.abs(target - next) < 0.0015;
                const value = settled ? target : next;
                currents[i] = value;
                el.style.setProperty('--effect', value.toFixed(4));
                if (!settled) moving = true;
            }

            rafId = moving ? requestAnimationFrame(runFrame) : null;
        }

        function startLoop() {
            if (rafId != null) cancelAnimationFrame(rafId);
            lastTime = performance.now();
            rafId = requestAnimationFrame(runFrame);
        }

        const container = root;

        function onPointerMove(e) {
            const rect = container.getBoundingClientRect();
            const pointerY = e.clientY - rect.top;
            const ease = FALLOFF_CURVES[opts.falloff] || FALLOFF_CURVES.linear;

            for (let i = 0; i < items.length; i++) {
                const el = items[i];
                if (!el) continue;
                const center = el.offsetTop + el.offsetHeight / 2;
                const distance = Math.abs(pointerY - center);
                targets[i] = ease(Math.max(0, 1 - distance / opts.proximityRadius));
            }
            startLoop();
        }

        function onPointerLeave() {
            for (let i = 0; i < targets.length; i++) targets[i] = 0;
            startLoop();
        }

        container.addEventListener('pointermove', onPointerMove, { passive: true });
        container.addEventListener('pointerleave', onPointerLeave, { passive: true });

        startLoop();

        return function destroy() {
            if (rafId) cancelAnimationFrame(rafId);
            container.removeEventListener('pointermove', onPointerMove);
            container.removeEventListener('pointerleave', onPointerLeave);
        };
    }

    // ============ AUTO ATTACH ============
    function autoInit() {
        injectCSS();

        document.querySelectorAll('[data-line-sidebar]').forEach(root => {
            const opts = {
                accentColor: root.dataset.accent || '#3B82F6',
                textColor: root.dataset.textColor || '#cbd5e1',
                markerColor: root.dataset.markerColor || '#475569',
                showIndex: root.dataset.showIndex === 'true',
                showMarker: root.dataset.showMarker !== 'false',
                proximityRadius: parseInt(root.dataset.proximity || '100', 10),
                maxShift: parseInt(root.dataset.maxShift || '20', 10),
                falloff: root.dataset.falloff || 'smooth',
                markerLength: parseInt(root.dataset.markerLength || '40', 10),
                itemGap: parseInt(root.dataset.itemGap || '4', 10),
                fontSize: parseFloat(root.dataset.fontSize || '0.875'),
                smoothing: parseInt(root.dataset.smoothing || '100', 10)
            };
            initLineSidebar(root, opts);
        });
    }

    window.initLineSidebar = initLineSidebar;
    window.injectLineSidebarCSS = injectCSS;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }
})();