<?php

declare(strict_types=1);

function normalizePanoramaSource(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return '';
    }

    if (str_starts_with($trimmed, 'uploads/')) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) !== false) {
        return $trimmed;
    }

    return '';
}

function normalizeReturnTarget(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '' || str_starts_with($trimmed, '//') || preg_match('/^[a-z]+:/i', $trimmed) === 1) {
        return 'locaties.php';
    }

    if ($trimmed[0] !== '/' && !str_starts_with($trimmed, './') && !str_starts_with($trimmed, '../')) {
        return './' . $trimmed;
    }

    return $trimmed;
}

$source = normalizePanoramaSource((string) ($_GET['src'] ?? ''));
$returnTarget = normalizeReturnTarget((string) ($_GET['return'] ?? 'locaties.php'));
?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>360° Panorama</title>
    <style>
        html, body { height: 100%; margin: 0; padding: 0; background: #000; color: #fff; font-family: Arial, sans-serif; }
        .topbar { position: fixed; top: 0; left: 0; right: 0; z-index: 10; padding: 10px 14px; background: rgba(0, 0, 0, 0.55); }
        .topbar a { color: #fff; text-decoration: none; font-size: 13px; }
        .topbar a:hover { text-decoration: underline; }
        #viewer { width: 100vw; height: 100vh; background: #000; }
        .fallback { width: 100vw; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .fallback img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .message { padding: 16px; font-size: 14px; }

        .pnlm-controls-container {
            top: auto !important;
            bottom: 22px !important;
        }

        @media (max-width: 640px) {
            .pnlm-controls-container {
                bottom: max(16px, env(safe-area-inset-bottom)) !important;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css" />
</head>
<body>
    <div class="topbar"><a id="back-link" href="<?= htmlspecialchars($returnTarget, ENT_QUOTES, 'UTF-8') ?>">← Terug</a></div>
    <?php if ($source === ''): ?>
        <div class="message">Geen geldige panorama-bron gevonden.</div>
    <?php else: ?>
        <div id="viewer"></div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <script>
        (function () {
            const backLink = document.getElementById('back-link');

            if (backLink) {
                backLink.addEventListener('click', function (event) {
                    if (window.history.length > 1) {
                        event.preventDefault();
                        window.history.back();
                    }
                });
            }

            const source = <?= json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            if (!source) {
                return;
            }

            const viewerNode = document.getElementById('viewer');
            if (!viewerNode) {
                return;
            }

            function showFallbackImage() {
                const wrapper = document.createElement('div');
                wrapper.className = 'fallback';

                const image = document.createElement('img');
                image.src = source;
                image.alt = 'Panoramabeeld';
                wrapper.appendChild(image);

                viewerNode.innerHTML = '';
                viewerNode.appendChild(wrapper);
            }

            if (typeof pannellum === 'undefined') {
                showFallbackImage();
                return;
            }

            try {
                const viewer = pannellum.viewer('viewer', {
                    type: 'equirectangular',
                    panorama: source,
                    autoLoad: true,
                    showZoomCtrl: true,
                    showFullscreenCtrl: true,
                    hfov: 100
                });

                setTimeout(function () {
                    if (viewer && typeof viewer.resize === 'function') {
                        viewer.resize();
                    }
                }, 80);
            } catch (error) {
                showFallbackImage();
            }
        })();
    </script>
</body>
</html>
