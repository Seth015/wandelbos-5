<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/partners.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$locations = fetchLocations(true);
$mapPoints = array_values(array_filter(array_map(static function (array $location): ?array {
    if ($location['latitude'] === null || $location['longitude'] === null) {
        return null;
    }

    return [
        'name' => (string) $location['name'],
        'slug' => (string) $location['slug'],
        'latitude' => (float) $location['latitude'],
        'longitude' => (float) $location['longitude'],
    ];
}, $locations)));
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Locaties | TNZ Wandelbos</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .company-header {
            z-index: 2000 !important;
        }

        .leaflet-container,
        .leaflet-pane,
        .leaflet-top,
        .leaflet-bottom,
        .leaflet-control {
            z-index: 1 !important;
        }
    </style>
</head>
<body class="seth-theme min-h-screen flex flex-col">
<header class="company-header border-b border-slate-300 px-4 py-3 sm:px-6">
    <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold tracking-wide uppercase">TNZ Wandelbos</p>
            <p class="text-[11px] uppercase tracking-wider opacity-85">Historische route met interactieve locaties</p>
        </div>
        <nav class="flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wider">
            <a href="home.php" class="company-link">Start</a>
            <a href="locaties.php" class="company-link">Locaties</a>
            <a href="admin.php" class="company-link">Beheer</a>
            <a href="history.php#over" class="company-link">Over het project</a>
            <a href="history.php#spel" class="company-link">Spel</a>
            <a href="history.php#quiz" class="company-link">Quiz</a>
            <a href="history.php#contact" class="company-link">Contact</a>
        </nav>
    </div>
</header>

<main class="flex-1 px-4 py-6 sm:px-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <section class="rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold">Route-overzicht</h1>
                    <p class="mt-2 text-sm text-slate-700">Ontdek alle wandelpunten op kaart, open locatie-info en scan QR-codes tijdens de route.</p>
                </div>
                <div class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs uppercase tracking-wider">
                    <?= count($locations) ?> actieve locaties
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-300 bg-slate-100 p-4 sm:p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Digitale kaart</h2>
            <div id="route-map" class="h-72 sm:h-80 rounded-lg border border-slate-300 bg-slate-50"></div>
        </section>

        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Locaties</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($locations as $location): ?>
                    <?php
                    $slug = (string) $location['slug'];
                    $images = fetchLocationImages((int) $location['id']);
                    $imageUrl = (string) ($location['hero_image_url'] ?? '');
                    if ($imageUrl === '') {
                        $imageUrl = (string) ($images[0]['image_url'] ?? 'https://picsum.photos/seed/' . rawurlencode($slug) . '/1200/700');
                    }
                    ?>
                    <article class="rounded-xl border border-slate-300 bg-slate-100 overflow-hidden">
                        <img src="<?= h($imageUrl) ?>" alt="<?= h((string) $location['name']) ?>" class="h-44 w-full object-cover" loading="lazy">
                        <div class="p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide"><?= h((string) $location['name']) ?></h3>
                            <p class="mt-2 text-xs text-slate-700 min-h-10"><?= h((string) ($location['short_text'] ?? '')) ?></p>
                            <a href="locatie.php?slug=<?= rawurlencode($slug) ?>" class="mt-3 inline-flex items-center text-xs font-semibold underline-offset-4 hover:underline">
                                Bekijk locatie →
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<footer class="company-footer border-t border-slate-300 px-4 py-3 sm:px-6">
    <div class="max-w-6xl mx-auto text-[11px] uppercase tracking-wider opacity-90">TNZ Wandelbos · educatieve en interactieve wandelroute</div>
    <?php renderPartnersFooterLogos('max-w-6xl mx-auto footer-logos'); ?>
</footer>

<script>
window.WB_LOCATIONS = <?= json_encode($mapPoints, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    const mapEl = document.getElementById('route-map');
    const points = Array.isArray(window.WB_LOCATIONS) ? window.WB_LOCATIONS : [];

    if (!mapEl || typeof L === 'undefined' || points.length === 0) {
        return;
    }

    const map = L.map(mapEl).setView([points[0].latitude, points[0].longitude], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    points.forEach((point) => {
        const marker = L.marker([point.latitude, point.longitude]).addTo(map);
        marker.bindPopup('<strong>' + point.name + '</strong><br><a href="locatie.php?slug=' + encodeURIComponent(point.slug) + '">Open locatie</a>');
    });
})();
</script>
</body>
</html>
