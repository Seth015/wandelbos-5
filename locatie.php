<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/partners.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function locationPublicUrl(string $slug): string
{
    $baseUrl = publicAppBaseUrl();

    if ($baseUrl === '') {
        return 'locatie.php?slug=' . rawurlencode($slug);
    }

    return $baseUrl . '/locatie.php?slug=' . rawurlencode($slug);
}

function qrFileName(string $slug): string
{
    $normalized = preg_replace('/[^a-z0-9\-]+/i', '-', $slug) ?: 'locatie';

    return trim(strtolower($normalized), '-') . '.png';
}

function locationQrImageUrl(string $slug, string $targetUrl): string
{
    $fileName = qrFileName($slug);
    $localPath = __DIR__ . '/uploads/qrcodes/' . $fileName;

    if (is_file($localPath)) {
        return 'uploads/qrcodes/' . rawurlencode($fileName);
    }

    return 'https://quickchart.io/qr?size=320&text=' . rawurlencode($targetUrl);
}

function isPanoramaImageUrl(string $value): bool
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return false;
    }

    if (str_starts_with($trimmed, 'uploads/')) {
        return true;
    }

    return preg_match('/\.(jpe?g|png|webp)(\?.*)?$/i', $trimmed) === 1;
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$location = $slug !== '' ? fetchLocationBySlug($slug) : null;
$images = $location ? fetchLocationImages((int) $location['id']) : [];
$quizzes = $location ? fetchLocationQuizzes((int) $location['id']) : [];
$locationPublicLink = $location ? locationPublicUrl((string) $location['slug']) : '';
$locationQrImage = $location ? locationQrImageUrl((string) $location['slug'], $locationPublicLink) : '';
$panoramaUrl = $location ? trim((string) ($location['panorama_url'] ?? '')) : '';
$panoramaIsImage = isPanoramaImageUrl($panoramaUrl);
$returnTo = (string) ($_SERVER['REQUEST_URI'] ?? 'locaties.php');
$panoramaViewerLink = $panoramaIsImage
    ? 'panorama.php?src=' . rawurlencode($panoramaUrl) . '&return=' . rawurlencode($returnTo)
    : '';

if ($location === null) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $location ? h((string) $location['name']) : 'Locatie niet gevonden' ?> | TNZ Wandelbos</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="seth-theme min-h-screen flex flex-col">
<header class="company-header border-b border-slate-300 px-4 py-3 sm:px-6">
    <div class="location-page-wrap mx-auto flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold tracking-wide uppercase">TNZ Wandelbos</p>
            <p class="text-[11px] uppercase tracking-wider opacity-85">Locatiebeleving met foto, quiz en spel</p>
        </div>
        <nav class="flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wider">
            <a href="home.php" class="company-link">Start</a>
            <a href="locaties.php" class="company-link">Locaties</a>
            <a href="history.php" class="company-link">Project</a>
        </nav>
    </div>
</header>

<main class="flex-1 px-4 py-6 sm:px-6">
    <div class="location-page-wrap mx-auto space-y-6">
        <?php if ($location === null): ?>
            <section class="rounded-xl border border-slate-300 bg-slate-100 p-5">
                <h1 class="text-lg font-semibold">Locatie niet gevonden</h1>
                <p class="mt-2 text-sm text-slate-700">Deze link is niet (meer) geldig. Kies een locatie vanuit het overzicht.</p>
                <a href="locaties.php" class="mt-3 inline-flex items-center text-xs font-semibold hover:underline">← Terug naar locaties</a>
            </section>
        <?php else: ?>
            <?php
            $heroImage = (string) ($location['hero_image_url'] ?? '');
            if ($heroImage === '') {
                $heroImage = (string) ($images[0]['image_url'] ?? 'https://picsum.photos/seed/' . rawurlencode((string) $location['slug']) . '/1600/900');
            }
            $vondsten = array_slice($images, 0, 2);
            $extraImages = array_slice($images, 2);
            $lat = isset($location['latitude']) && $location['latitude'] !== null ? number_format((float) $location['latitude'], 6, '.', '') : 'onbekend';
            $lng = isset($location['longitude']) && $location['longitude'] !== null ? number_format((float) $location['longitude'], 6, '.', '') : 'onbekend';
            $periode = (string) ($images[0]['year_label'] ?? 'Vroege Middeleeuwen');
            ?>
            <section class="location-shell rounded-xl border border-slate-300 p-5 sm:p-8">
                <a href="locaties.php" class="inline-flex items-center text-xs hover:underline">← Terug naar locaties</a>
                <p class="mt-4 text-xs uppercase tracking-[0.22em] text-slate-700">Bosarchief</p>
                <h1 class="mt-2 text-3xl sm:text-5xl font-semibold uppercase tracking-wide">Locatie: <?= h((string) $location['name']) ?></h1>
                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-700">Wandelbos archeologische route · Philippine</p>

                <div class="location-grid mt-6">
                    <div class="space-y-5">
                        <section class="location-hero-frame overflow-hidden">
                            <img src="<?= h($heroImage) ?>" alt="<?= h((string) $location['name']) ?>" class="location-hero-image w-full object-cover" loading="lazy">
                        </section>

                        <section class="locatie-info-card">
                            <h2>Historische informatie</h2>
                            <?php if ((string) ($location['short_text'] ?? '') !== ''): ?>
                                <p class="mb-3 text-sm leading-6 font-semibold"><?= h((string) $location['short_text']) ?></p>
                            <?php endif; ?>
                            <p class="text-sm leading-7"><?= nl2br(h((string) ($location['long_text'] ?? ''))) ?></p>
                        </section>

                        <?php if ($quizzes !== []): ?>
                            <div>
                                <a href="#quiz-section" class="location-start-quiz">Start quiz ▶</a>
                                <p class="mt-2 text-[11px] uppercase tracking-[0.14em] text-slate-700">Test je kennis over deze locatie en verdien punten voor je team</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($panoramaUrl !== ''): ?>
                            <section class="rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                                <h2 class="text-sm font-semibold uppercase tracking-wide">360° panorama</h2>
                                <?php if ($panoramaIsImage): ?>
                                    <a href="<?= h($panoramaViewerLink) ?>" class="mt-4 inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider bg-white hover:bg-slate-200" rel="noopener noreferrer">Bekijk 360° beeld</a>
                                    <a href="<?= h($panoramaUrl) ?>" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center text-xs font-semibold underline-offset-4 hover:underline">Open panorama-afbeelding direct</a>
                                <?php else: ?>
                                    <iframe class="mt-4 w-full h-72 rounded-lg border border-slate-300" src="<?= h($panoramaUrl) ?>" title="360 graden panorama" loading="lazy" allowfullscreen></iframe>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                    </div>

                    <aside class="space-y-5">
                        <section class="location-side-panel rounded-xl border border-slate-300 p-5 sm:p-6">
                            <h2 class="text-xl font-semibold uppercase tracking-wide mb-3">Vondsten</h2>
                            <?php if ($vondsten === []): ?>
                                <article class="location-vondst-card">
                                    <img src="<?= h($heroImage) ?>" alt="Voorbeeld vondst" class="location-vondst-image w-full object-cover" loading="lazy">
                                    <div class="p-3">
                                        <h3 class="text-sm font-semibold uppercase">Geen vondsten</h3>
                                        <p class="mt-1 text-xs">Er zijn nog geen vondsten toegevoegd voor deze locatie.</p>
                                    </div>
                                </article>
                            <?php else: ?>
                                <?php foreach ($vondsten as $vondst): ?>
                                    <article class="location-vondst-card">
                                        <a href="<?= h((string) $vondst['image_url']) ?>" target="_blank" rel="noopener noreferrer">
                                            <img src="<?= h((string) $vondst['image_url']) ?>" alt="<?= h((string) ($vondst['caption'] ?: 'Historische vondst')) ?>" class="location-vondst-image w-full object-cover" loading="lazy">
                                        </a>
                                        <div class="p-3">
                                            <h3 class="text-sm font-semibold uppercase"><?= h((string) ($vondst['caption'] ?: 'Historische vondst')) ?></h3>
                                            <?php if (!empty($vondst['description'])): ?><p class="mt-1 text-xs leading-5"><?= nl2br(h((string) $vondst['description'])) ?></p><?php endif; ?>
                                            <?php if (!empty($vondst['year_label'])): ?><p class="mt-1 text-xs">Periode: <?= h((string) $vondst['year_label']) ?></p><?php endif; ?>
                                            <?php if (!empty($vondst['source'])): ?><p class="mt-1 text-xs">Bron: <?= h((string) $vondst['source']) ?></p><?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </section>

                        <section class="location-side-panel rounded-xl border border-slate-300 p-5 sm:p-6">
                            <h2 class="text-sm font-semibold uppercase tracking-wide">Locatie metadata</h2>
                            <dl class="mt-3 space-y-2 text-xs">
                                <div class="flex items-start justify-between gap-4"><dt class="text-slate-700">Coördinaten</dt><dd class="font-semibold"><?= h($lat) ?>° N, <?= h($lng) ?>° E</dd></div>
                                <div class="flex items-start justify-between gap-4"><dt class="text-slate-700">Periode</dt><dd class="font-semibold"><?= h($periode) ?></dd></div>
                                <div class="flex items-start justify-between gap-4"><dt class="text-slate-700">Status</dt><dd class="font-semibold">Beschermd gebied</dd></div>
                            </dl>
                        </section>

                        <section class="location-side-panel rounded-xl border border-slate-300 p-5 sm:p-6">
                            <h2 class="text-sm font-semibold uppercase tracking-wide">QR-code</h2>
                            <p class="mt-2 text-xs text-slate-700">Scan deze QR om direct naar deze locatiepagina te gaan.</p>
                            <img src="<?= h($locationQrImage) ?>" alt="QR-code voor <?= h((string) $location['name']) ?>" class="mt-3 h-44 w-44 rounded-lg border border-slate-300 bg-slate-50 p-2" loading="lazy">
                            <a href="<?= h($locationPublicLink) ?>" class="mt-2 inline-flex items-center text-xs font-semibold underline-offset-4 hover:underline" target="_blank" rel="noopener noreferrer">Open locatie-link</a>
                        </section>
                    </aside>
                </div>

                <?php if ($extraImages !== []): ?>
                    <section class="mt-6 rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                        <h2 class="text-sm font-semibold uppercase tracking-wide">Meer beeldmateriaal</h2>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <?php foreach ($extraImages as $image): ?>
                                <figure class="rounded-lg border border-slate-300 bg-slate-50 overflow-hidden">
                                    <a href="<?= h((string) $image['image_url']) ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?= h((string) $image['image_url']) ?>" alt="<?= h((string) ($image['caption'] ?: 'Historische afbeelding')) ?>" class="h-40 w-full object-cover" loading="lazy">
                                    </a>
                                    <figcaption class="p-3 text-xs text-slate-700 space-y-1">
                                        <p class="font-semibold"><?= h((string) ($image['caption'] ?: 'Zonder bijschrift')) ?></p>
                                        <?php if (!empty($image['description'])): ?><p class="leading-5"><?= nl2br(h((string) $image['description'])) ?></p><?php endif; ?>
                                        <?php if (!empty($image['year_label'])): ?><p>Periode: <?= h((string) $image['year_label']) ?></p><?php endif; ?>
                                        <?php if (!empty($image['source'])): ?><p>Bron: <?= h((string) $image['source']) ?></p><?php endif; ?>
                                    </figcaption>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($quizzes !== []): ?>
                    <section id="quiz-section" class="mt-6 rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                        <h2 class="text-sm font-semibold uppercase tracking-wide">Kennistoets</h2>
                        <p class="mt-2 text-sm text-slate-700">Test je kennis over deze historische locatie.</p>
                        <form id="quiz-form" class="mt-4 space-y-4">
                            <?php foreach ($quizzes as $index => $quiz): ?>
                                <fieldset class="rounded-lg border border-slate-300 p-4" data-correct="<?= h((string) $quiz['correct_option']) ?>">
                                    <legend class="font-semibold text-sm mb-2"><?= ($index + 1) . '. ' . h((string) $quiz['question']) ?></legend>
                                    <label class="flex items-start gap-2 text-sm mb-2"><input type="radio" name="q<?= (int) $quiz['id'] ?>" value="a"> <span><?= h((string) $quiz['option_a']) ?></span></label>
                                    <label class="flex items-start gap-2 text-sm mb-2"><input type="radio" name="q<?= (int) $quiz['id'] ?>" value="b"> <span><?= h((string) $quiz['option_b']) ?></span></label>
                                    <label class="flex items-start gap-2 text-sm"><input type="radio" name="q<?= (int) $quiz['id'] ?>" value="c"> <span><?= h((string) $quiz['option_c']) ?></span></label>
                                </fieldset>
                            <?php endforeach; ?>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" id="check-quiz" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Controleer antwoorden</button>
                                <p id="quiz-result" class="text-sm font-semibold" aria-live="polite"></p>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>

                <section class="mt-6 rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide">Interactief memory-spel</h2>
                    <p class="mt-2 text-sm text-slate-700">Kies een niveau en vind alle paren.</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                        <label for="level">Niveau:</label>
                        <select id="level" class="rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs">
                            <option value="easy">Kind (4 paren)</option>
                            <option value="medium" selected>Jongere (6 paren)</option>
                            <option value="hard">Volwassene (8 paren)</option>
                        </select>
                        <button type="button" id="start-game" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Start spel</button>
                    </div>
                    <div id="memory-board" class="mt-4 grid grid-cols-4 sm:grid-cols-6 gap-2"></div>
                    <p id="memory-status" class="mt-3 text-sm"></p>
                </section>
            </section>
        <?php endif; ?>
    </div>
</main>

<footer class="company-footer border-t border-slate-300 px-4 py-3 sm:px-6">
    <div class="location-page-wrap mx-auto text-[11px] uppercase tracking-wider opacity-90">TNZ Wandelbos · locatiebeleving</div>
    <?php renderPartnersFooterLogos('location-page-wrap mx-auto footer-logos'); ?>
</footer>

<script>
(function () {
    const checkButton = document.getElementById('check-quiz');
    const quizResult = document.getElementById('quiz-result');

    if (checkButton && quizResult) {
        checkButton.addEventListener('click', function () {
            const blocks = Array.from(document.querySelectorAll('fieldset[data-correct]'));
            let score = 0;

            blocks.forEach((block) => {
                const selected = block.querySelector('input[type="radio"]:checked');
                if (selected && selected.value === block.dataset.correct) {
                    score += 1;
                }
            });

            quizResult.textContent = 'Je score: ' + score + ' van ' + blocks.length;
        });
    }

    const board = document.getElementById('memory-board');
    const levelSelect = document.getElementById('level');
    const startButton = document.getElementById('start-game');
    const status = document.getElementById('memory-status');

    if (!board || !levelSelect || !startButton || !status) {
        return;
    }

    const allIcons = ['🌳', '🏰', '📜', '⛵', '🕰️', '🧭', '🏞️', '🧱'];
    let firstCard = null;
    let locked = false;
    let matches = 0;
    let neededMatches = 0;

    function shuffle(array) {
        for (let index = array.length - 1; index > 0; index -= 1) {
            const randomIndex = Math.floor(Math.random() * (index + 1));
            [array[index], array[randomIndex]] = [array[randomIndex], array[index]];
        }
        return array;
    }

    function hide(card) {
        if (card.classList.contains('matched')) {
            return;
        }
        card.textContent = '?';
        card.classList.remove('bg-emerald-700/40');
    }

    function reveal(card) {
        card.textContent = card.dataset.value;
        card.classList.add('bg-emerald-700/40');
    }

    function createBoard() {
        const level = levelSelect.value;
        const pairCount = level === 'easy' ? 4 : level === 'hard' ? 8 : 6;
        const deck = shuffle([...allIcons.slice(0, pairCount), ...allIcons.slice(0, pairCount)]);

        firstCard = null;
        locked = false;
        matches = 0;
        neededMatches = pairCount;
        status.textContent = 'Zoek alle paren!';
        board.innerHTML = '';

        deck.forEach((icon) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.dataset.value = icon;
            card.className = 'rounded-md border border-slate-300 bg-slate-50 h-14 text-lg font-semibold';
            card.textContent = '?';

            card.addEventListener('click', function () {
                if (locked || card.classList.contains('matched') || card === firstCard) {
                    return;
                }

                reveal(card);

                if (!firstCard) {
                    firstCard = card;
                    return;
                }

                if (firstCard.dataset.value === card.dataset.value) {
                    firstCard.classList.add('matched');
                    card.classList.add('matched');
                    firstCard = null;
                    matches += 1;

                    if (matches === neededMatches) {
                        status.textContent = 'Klaar! Je hebt alle paren gevonden.';
                    }

                    return;
                }

                const previous = firstCard;
                firstCard = null;
                locked = true;

                setTimeout(function () {
                    hide(previous);
                    hide(card);
                    locked = false;
                }, 650);
            });

            board.appendChild(card);
        });
    }

    startButton.addEventListener('click', createBoard);
    createBoard();
})();
</script>
</body>
</html>
