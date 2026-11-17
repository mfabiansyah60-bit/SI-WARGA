<?php
/**
 * resources/views/components/glass-icons.php
 * Glass Icons — vanilla PHP/CSS/JS
 * Layout: 3 kolom dengan card segi empat di belakang
 */

$glass_items = $glass_items ?? [];

$gradients = [
    'blue'     => 'linear-gradient(135deg, hsl(214, 95%, 66%), hsl(226, 80%, 50%))',
    'purple'   => 'linear-gradient(135deg, hsl(276, 84%, 70%), hsl(262, 66%, 52%))',
    'red'      => 'linear-gradient(135deg, hsl(354, 95%, 68%), hsl(342, 80%, 52%))',
    'indigo'   => 'linear-gradient(135deg, hsl(236, 90%, 72%), hsl(246, 64%, 54%))',
    'orange'   => 'linear-gradient(135deg, hsl(38, 100%, 62%), hsl(20, 94%, 54%))',
    'green'    => 'linear-gradient(135deg, hsl(146, 66%, 54%), hsl(162, 82%, 34%))',
    'teal'     => 'linear-gradient(135deg, hsl(184, 80%, 56%), hsl(196, 84%, 40%))',
    'pink'     => 'linear-gradient(135deg, hsl(326, 92%, 74%), hsl(314, 70%, 54%))',
    'graphite' => 'linear-gradient(135deg, hsl(250, 6%, 46%), hsl(250, 8%, 22%))',
];

$uid = 'gi-' . substr(md5(uniqid('', true)), 0, 8);
?>

<style>
/* ============ GLASS ICONS — 3 COLUMN GRID ============ */
.gi-wrap {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    padding: 8px 0;
    width: 100%;
}
@media (max-width: 900px) { .gi-wrap { gap: 16px; } }
@media (max-width: 640px) { .gi-wrap { grid-template-columns: repeat(2, 1fr); gap: 12px; } }

/* ============ CARD WRAPPER (segi empat) ============ */
.gi-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 26px 18px 20px;
    text-decoration: none;
    cursor: pointer;
    border-radius: 18px;

    /* SEGI EMPAT DI BELAKANG */
    background: var(--bg-soft, #f8fafc);
    border: 1px solid var(--border, #e8edf3);

    opacity: 0;
    transform: translateY(18px) scale(.92);
    animation: giIntro .55s cubic-bezier(.34, 1.56, .64, 1) forwards;
    animation-delay: calc(var(--gi-i) * 75ms + 100ms);
    will-change: transform, opacity;
    font-family: inherit;

    transition:
        background .25s ease,
        border-color .25s ease,
        transform .3s ease,
        box-shadow .25s ease;
}

/* Dark mode card */
[data-theme="dark"] .gi-item,
.dark .gi-item {
    background: rgba(255,255,255,.025);
    border-color: rgba(255,255,255,.06);
}

/* Hover card */
.gi-item:hover {
    background: var(--bg-card, #ffffff);
    border-color: #cbd5e1;
    box-shadow: 0 8px 24px rgba(15,23,42,.08);
    transform: translateY(-2px);
}
[data-theme="dark"] .gi-item:hover,
.dark .gi-item:hover {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.14);
    box-shadow: 0 8px 24px rgba(0,0,0,.4);
}

@keyframes giIntro {
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Stack: plate + glass + icon */
.gi-stack {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    transition: transform .35s cubic-bezier(.34, 1.56, .64, 1);
    margin: 6px 6px 0 0;
}
.gi-item:hover .gi-stack {
    transform: translateY(-4px) scale(1.08);
}
.gi-item:active .gi-stack {
    transform: translateY(-1px) scale(.98);
}

/* 1. Plate — gradient, di belakang */
.gi-plate {
    position: absolute;
    inset: 0;
    border-radius: 22px;
    transform: translate(6px, -6px) rotate(-8deg);
    box-shadow:
        0 8px 20px rgba(0,0,0,.2),
        0 3px 6px rgba(0,0,0,.12);
    transition: transform .35s cubic-bezier(.34, 1.56, .64, 1);
    z-index: 1;
}
.gi-item:hover .gi-plate {
    transform: translate(9px, -9px) rotate(-10deg);
    box-shadow:
        0 12px 28px rgba(0,0,0,.25),
        0 4px 8px rgba(0,0,0,.15);
}

/* 2. Glass */
.gi-glass {
    position: absolute;
    inset: 0;
    border-radius: 22px;
    z-index: 2;
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(12px) saturate(1.4) brightness(1.05);
    -webkit-backdrop-filter: blur(12px) saturate(1.4) brightness(1.05);
    border: 1px solid rgba(255,255,255,.28);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.5),
        inset 0 -1px 0 rgba(0,0,0,.1),
        0 4px 12px rgba(0,0,0,.15);
    transition: box-shadow .3s ease, background .3s ease;
    pointer-events: none;
}
.gi-item:hover .gi-glass {
    background: rgba(255,255,255,.14);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.65),
        inset 0 -1px 0 rgba(0,0,0,.12),
        0 8px 20px rgba(0,0,0,.2);
}
.gi-glass::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255,255,255,.4) 0%,
        rgba(255,255,255,.05) 32%,
        rgba(255,255,255,0) 62%
    );
    pointer-events: none;
}

/* 3. Icon */
.gi-icon {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    color: #fff;
    pointer-events: none;
}
.gi-icon .material-symbols-outlined {
    font-size: 36px;
    font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,.3));
    transition: transform .35s cubic-bezier(.34, 1.56, .64, 1);
}
.gi-item:hover .gi-icon .material-symbols-outlined {
    transform: scale(1.12);
}

/* ============ LABEL (LIGHT) ============ */
.gi-label {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    text-align: center;
    letter-spacing: -.2px;
    line-height: 1.35;
    transition: color .2s;
    padding: 0 6px;
    word-break: break-word;
    max-width: 100%;
}
.gi-item:hover .gi-label {
    color: #2563eb;
}

/* ============ LABEL (DARK) ============ */
[data-theme="dark"] .gi-label,
.dark .gi-label {
    color: #f1f5f9 !important;
    font-weight: 700;
}
[data-theme="dark"] .gi-item:hover .gi-label,
.dark .gi-item:hover .gi-label {
    color: #93c5fd !important;
}

/* ============ DARK MODE GLASS ============ */
[data-theme="dark"] .gi-glass,
.dark .gi-glass {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.18);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.25),
        inset 0 -1px 0 rgba(0,0,0,.3),
        0 4px 12px rgba(0,0,0,.4);
}
[data-theme="dark"] .gi-item:hover .gi-glass,
.dark .gi-item:hover .gi-glass {
    background: rgba(255,255,255,.1);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.35),
        0 8px 20px rgba(0,0,0,.5);
}
</style>

<div class="gi-wrap" id="<?= $uid ?>">
    <?php foreach ($glass_items as $i => $item):
        $color   = $item['color'] ?? 'blue';
        $grad    = $gradients[$color] ?? $gradients['blue'];
        $icon    = $item['icon'] ?? 'apps';
        $label   = $item['label'] ?? '';
        $href    = $item['href'] ?? null;
        $onclick = $item['onclick'] ?? null;
        $Tag     = $href ? 'a' : 'button';
    ?>
        <<?= $Tag ?>
            class="gi-item"
            style="--gi-i: <?= $i ?>;"
            <?php if ($href): ?>href="<?= htmlspecialchars($href) ?>"<?php endif; ?>
            <?php if (!$href): ?>type="button"<?php endif; ?>
            <?php if ($onclick): ?>onclick="<?= htmlspecialchars($onclick) ?>"<?php endif; ?>
            aria-label="<?= htmlspecialchars($label) ?>"
        >
            <span class="gi-stack">
                <span class="gi-plate" style="background: <?= $grad ?>;"></span>
                <span class="gi-glass"></span>
                <span class="gi-icon">
                    <span class="material-symbols-outlined"><?= htmlspecialchars($icon) ?></span>
                </span>
            </span>
            <?php if ($label): ?>
                <span class="gi-label"><?= htmlspecialchars($label) ?></span>
            <?php endif; ?>
        </<?= $Tag ?>>
    <?php endforeach; ?>
</div>

<script>
(function() {
    var wrap = document.getElementById('<?= $uid ?>');
    if (!wrap) return;
    wrap.querySelectorAll('.gi-item').forEach(function(el) {
        el.style.animation = 'none';
        el.offsetHeight;
        el.style.animation = '';
    });
})();
</script>