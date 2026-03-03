<?php

declare(strict_types=1);

require_once __DIR__ . '/../partners.php';

/**
 * Page Layout Template
 *
 * @param string $title Page title for HTML head
 * @param string $pageSubtitle Header subtitle text
 * @param string $content Main page content (HTML)
 * @return void
 */
function renderPage(string $title, string $pageSubtitle, string $content): void
{
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?> - Bosarchief</title>
        <link rel="stylesheet" href="assets/css/tailwind.css">
        <link rel="stylesheet" href="assets/css/site.css">
        <script src="assets/js/app.js" defer></script>
    </head>
    <body class="seth-theme bg-white text-slate-900 overflow-x-hidden flex flex-col min-h-screen">
        <main class="flex-1 w-full bg-white border-y border-slate-300 flex flex-col">
            <header class="company-header border-b border-slate-300 px-4 py-3 sm:px-6">
                <div class="max-w-6xl mx-auto flex flex-col gap-2 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
                    <div>
                        <p class="text-sm font-semibold tracking-wide uppercase">TNZ Wandelbos</p>
                        <p class="text-[11px] uppercase tracking-wider opacity-85 break-words"><?= htmlspecialchars($pageSubtitle) ?></p>
                    </div>
                    <nav class="flex flex-wrap items-center justify-center gap-3 sm:justify-end text-[11px] uppercase tracking-wider">
                        <a href="home.php" class="company-link">Start</a>
                        <a href="locaties.php" class="company-link">Locaties</a>
                        <a href="admin.php" class="company-link">Content</a>
                        <a href="admin_locations.php" class="company-link">Locatiebeheer</a>
                        <a href="admin_qrcodes.php" class="company-link">QR-codes</a>
                    </nav>
                </div>
            </header>

            <?= $content ?>
        </main>

        <footer class="company-footer px-4 py-3 text-center sm:text-left w-full border-t border-slate-300">
            <div class="max-w-6xl mx-auto">
                <p class="text-[10px] tracking-wide uppercase">TNZ Wandelbos</p>
                <p class="text-[10px] opacity-85 break-words">Digitale boswandeling webapplicatie</p>
            </div>
            <?php renderPartnersFooterLogos('max-w-6xl mx-auto footer-logos'); ?>
        </footer>
    </body>
    </html>
    <?php
}

/**
 * Back Navigation Link
 */
function backLink(string $text = 'Terug naar overzicht'): string
{
    return sprintf(
        '<a href="locaties.php" class="inline-flex items-center text-xs text-blue-700 hover:underline mb-4">← %s</a>',
        htmlspecialchars($text)
    );
}

/**
 * Card Component
 */
function card(string $content, string $extraClasses = ''): string
{
    return sprintf(
        '<article class="border border-slate-300 p-4 %s">%s</article>',
        htmlspecialchars($extraClasses),
        $content
    );
}

/**
 * Image Container
 */
function imageContainer(string $src, string $alt, string $heightClasses = 'h-28 sm:h-24'): string
{
    return sprintf(
        '<div class="border border-slate-300 %s bg-slate-50 mb-3 overflow-hidden"><img src="%s" alt="%s" class="h-full w-full object-cover" loading="lazy" /></div>',
        htmlspecialchars($heightClasses),
        htmlspecialchars($src),
        htmlspecialchars($alt)
    );
}
