<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/partners.php';
require_once __DIR__ . '/config.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$checks = [];
$ok = true;

try {
    $pdo = getPdo();
    $checks[] = [
        'label' => 'Database verbinding',
        'status' => true,
        'message' => 'Verbinding met MySQL is gelukt.',
    ];

    $requiredTables = ['content_blocks', 'locations', 'location_images', 'location_quizzes'];

    foreach ($requiredTables as $table) {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute([':table_name' => $table]);
        $exists = (bool) $stmt->fetchColumn();

        $checks[] = [
            'label' => 'Tabel: ' . $table,
            'status' => $exists,
            'message' => $exists ? 'Aanwezig' : 'Ontbreekt',
        ];

        if (!$exists) {
            $ok = false;
        }
    }
} catch (Throwable $exception) {
    $ok = false;
    $checks[] = [
        'label' => 'Database verbinding',
        'status' => false,
        'message' => $exception->getMessage(),
    ];
}

$adminUser = (string) env('ADMIN_USERNAME', 'admin');
$hasHash = (string) env('ADMIN_PASSWORD_HASH', '') !== '';
$hasPassword = (string) env('ADMIN_PASSWORD', '') !== '';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup check | TNZ Wandelbos</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="seth-theme min-h-screen flex flex-col">
<header class="company-header border-b border-slate-300 px-4 py-3 sm:px-6">
    <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold tracking-wide uppercase">TNZ Wandelbos Setup</p>
            <p class="text-[11px] uppercase tracking-wider opacity-85">Systeemcontrole en database readiness</p>
        </div>
        <nav class="flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wider">
            <a href="home.php" class="company-link">Start</a>
            <a href="locaties.php" class="company-link">Locaties</a>
            <a href="admin.php" class="company-link">Admin</a>
        </nav>
    </div>
</header>

<main class="flex-1 px-4 py-6 sm:px-6">
    <div class="max-w-5xl mx-auto space-y-6">
        <section class="rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <h1 class="text-lg font-semibold"><?= $ok ? 'Setup geslaagd' : 'Setup vereist aandacht' ?></h1>
            <p class="mt-2 text-sm text-slate-700">
                <?= $ok
                    ? 'De app kan starten: database en basisschema zijn klaar voor gebruik.'
                    : 'Er zijn problemen gevonden. Controleer onderstaande punten en probeer opnieuw.' ?>
            </p>
        </section>

        <section class="rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Controlepunten</h2>
            <div class="space-y-2">
                <?php foreach ($checks as $check): ?>
                    <div class="rounded-md border border-slate-300 px-3 py-2 text-sm flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold"><?= h((string) $check['label']) ?></p>
                            <p class="text-xs text-slate-700 break-all"><?= h((string) $check['message']) ?></p>
                        </div>
                        <span class="inline-flex items-center rounded-full border border-slate-300 px-2 py-0.5 text-[11px] uppercase tracking-wider">
                            <?= $check['status'] ? 'OK' : 'Fout' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Admin configuratie</h2>
            <ul class="space-y-2 text-sm">
                <li><strong>Gebruikersnaam:</strong> <?= h($adminUser) ?></li>
                <li><strong>Wachtwoord bron:</strong> <?= $hasHash ? 'ADMIN_PASSWORD_HASH' : ($hasPassword ? 'ADMIN_PASSWORD' : 'default fallback') ?></li>
                <li><strong>Aanbeveling:</strong> gebruik een sterke hash in ADMIN_PASSWORD_HASH voor productie.</li>
            </ul>
        </section>

        <section class="rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Volgende stap</h2>
            <div class="flex flex-wrap gap-2">
                <a href="history.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Open projectpagina</a>
                <a href="locaties.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Open locaties</a>
                <a href="admin.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Open admin</a>
            </div>
        </section>
    </div>
</main>

<footer class="company-footer border-t border-slate-300 px-4 py-3 sm:px-6">
    <div class="max-w-5xl mx-auto text-[11px] uppercase tracking-wider opacity-90">TNZ Wandelbos · setup checker</div>
    <?php renderPartnersFooterLogos('max-w-5xl mx-auto footer-logos'); ?>
</footer>
</body>
</html>
