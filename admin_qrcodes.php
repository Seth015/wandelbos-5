<?php

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/partners.php';
require_once __DIR__ . '/admin_nav.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function validateCsrf(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($sessionToken)
        && $sessionToken !== ''
        && is_string($token)
        && hash_equals($sessionToken, $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['qr_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['qr_flash']) || !is_array($_SESSION['qr_flash'])) {
        return null;
    }

    $flash = $_SESSION['qr_flash'];
    unset($_SESSION['qr_flash']);

    return $flash;
}

function locationUrl(string $slug): string
{
    $baseUrl = publicAppBaseUrl();

    if ($baseUrl === '') {
        return 'locatie.php?slug=' . rawurlencode($slug);
    }

    return $baseUrl . '/locatie.php?slug=' . rawurlencode($slug);
}

function qrImageUrl(string $targetUrl): string
{
    return 'https://quickchart.io/qr?size=300&text=' . rawurlencode($targetUrl);
}

function qrFileName(string $slug): string
{
    $normalized = preg_replace('/[^a-z0-9\-]+/i', '-', $slug) ?: 'locatie';

    return trim(strtolower($normalized), '-') . '.png';
}

function createQrZip(string $directoryPath, string $zipPath): bool
{
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $files = glob($directoryPath . DIRECTORY_SEPARATOR . '*.png') ?: [];

    if ($files === []) {
        return false;
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }

    $zip->close();

    return is_file($zipPath);
}

$localQrDir = __DIR__ . '/uploads/qrcodes';
$localQrPublicBase = 'uploads/qrcodes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!validateCsrf(is_string($token) ? $token : null)) {
        flash('error', 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.');
        header('Location: admin_qrcodes.php');
        exit;
    }

    if ($action === 'save_local_qr') {
        if (!is_dir($localQrDir) && !mkdir($localQrDir, 0775, true) && !is_dir($localQrDir)) {
            flash('error', 'Lokale QR-map kon niet worden aangemaakt.');
            header('Location: admin_qrcodes.php');
            exit;
        }

        $locationsForSave = fetchLocations(false);
        $saved = 0;
        $failed = 0;

        foreach ($locationsForSave as $location) {
            $slug = (string) $location['slug'];
            $targetUrl = locationUrl($slug);
            $binary = @file_get_contents(qrImageUrl($targetUrl));

            if ($binary === false) {
                $failed++;
                continue;
            }

            $filePath = $localQrDir . '/' . qrFileName($slug);
            $written = @file_put_contents($filePath, $binary);

            if ($written === false) {
                $failed++;
                continue;
            }

            $saved++;
        }

        if ($failed === 0) {
            flash('success', "Lokale QR-bestanden opgeslagen ({$saved}).");
        } else {
            flash('error', "{$saved} opgeslagen, {$failed} mislukt. Controleer internetverbinding of schrijfrechten.");
        }

        header('Location: admin_qrcodes.php');
        exit;
    }

    if ($action === 'download_zip_qr') {
        if (!is_dir($localQrDir)) {
            flash('error', 'Geen lokale QR-map gevonden. Sla eerst PNG\'s op.');
            header('Location: admin_qrcodes.php');
            exit;
        }

        $zipFileName = 'wandelbos-qrcodes-' . date('Ymd-His') . '.zip';
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;

        if (!createQrZip($localQrDir, $zipPath)) {
            flash('error', 'ZIP maken mislukt. Controleer of de ZIP-extensie actief is en dat er PNG-bestanden zijn.');
            header('Location: admin_qrcodes.php');
            exit;
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . (string) filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }
}

$locations = fetchLocations(false);
$flash = getFlash();
$csrf = csrfToken();
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR-codes | TNZ Wandelbos</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="seth-theme admin-page min-h-screen flex flex-col">
<?php renderAdminHeader('qrcodes', 'QR-codebeheer per routelocatie'); ?>

<main class="flex-1 px-4 py-6 sm:px-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <?php if ($flash): ?>
            <div class="admin-flash <?= (string) ($flash['type'] ?? '') === 'success' ? 'admin-flash-success' : 'admin-flash-error' ?>">
                <strong><?= h((string) $flash['type']) ?>:</strong> <?= h((string) $flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="admin-panel rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <h1 class="text-lg font-semibold">QR-codes beheren</h1>
            <p class="mt-2 text-sm text-slate-700">Print direct, sla lokale PNG's op of download alle QR's als ZIP-bestand.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Print QR-codes</button>
                <form method="post">
                    <input type="hidden" name="action" value="save_local_qr">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Sla op als PNG</button>
                </form>
                <form method="post">
                    <input type="hidden" name="action" value="download_zip_qr">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Download ZIP</button>
                </form>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($locations as $location): ?>
                <?php
                $slug = (string) $location['slug'];
                $targetUrl = locationUrl($slug);
                $localQrFile = $localQrPublicBase . '/' . qrFileName($slug);
                $localQrPath = $localQrDir . '/' . qrFileName($slug);
                ?>
                <article class="admin-panel rounded-xl border border-slate-300 bg-slate-100 p-4 text-center">
                    <h2 class="text-sm font-semibold uppercase tracking-wide"><?= h((string) $location['name']) ?></h2>
                    <img class="mt-3 mx-auto h-44 w-44 rounded-md border border-slate-300" src="<?= h(qrImageUrl($targetUrl)) ?>" alt="QR-code voor <?= h((string) $location['name']) ?>">
                    <a href="<?= h($targetUrl) ?>" target="_blank" rel="noopener" class="mt-3 block text-xs break-all underline-offset-4 hover:underline"><?= h($targetUrl) ?></a>
                    <?php if (is_file($localQrPath)): ?>
                        <a href="<?= h($localQrFile) ?>" download class="mt-3 inline-flex items-center rounded-full border border-slate-300 px-3 py-1 text-[11px] uppercase tracking-wider">Download lokale PNG</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </div>
</main>

<?php renderAdminFooter('QR publicatiemateriaal'); ?>
</body>
</html>
