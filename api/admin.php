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

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || $token === null) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function adminPasswordIsValid(string $providedPassword): bool
{
    $configuredHash = (string) env('ADMIN_PASSWORD_HASH', '');

    if ($configuredHash !== '') {
        return password_verify($providedPassword, $configuredHash);
    }

    $configuredPassword = (string) env('ADMIN_PASSWORD', 'admin123!');
    return hash_equals($configuredPassword, $providedPassword);
}

function normalizeUploadedFiles(array $fileField): array
{
    $name = $fileField['name'] ?? null;
    $tmpName = $fileField['tmp_name'] ?? null;
    $error = $fileField['error'] ?? null;
    $size = $fileField['size'] ?? null;
    $type = $fileField['type'] ?? null;

    if (!is_array($name)) {
        return [$fileField];
    }

    $files = [];
    $total = count($name);

    for ($index = 0; $index < $total; $index += 1) {
        $files[] = [
            'name' => (string) ($name[$index] ?? ''),
            'type' => (string) (is_array($type) ? ($type[$index] ?? '') : ''),
            'tmp_name' => (string) ($tmpName[$index] ?? ''),
            'error' => (int) ($error[$index] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($size[$index] ?? 0),
        ];
    }

    return $files;
}

function storeProjectImage(array $file): string
{
    $uploadDir = __DIR__ . '/uploads/project';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Uploadmap voor projectfoto\'s kon niet worden aangemaakt.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    $mime = $tmpName !== '' ? (string) (mime_content_type($tmpName) ?: '') : '';

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('Ongeldige uploadaanvraag.');
    }

    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        throw new RuntimeException('Bestand te groot of ongeldig (max 8MB).');
    }

    if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Alleen JPG, PNG en WEBP zijn toegestaan.');
    }

    if (@getimagesize($tmpName) === false) {
        throw new RuntimeException('Bestand is geen geldige afbeelding.');
    }

    $filename = uniqid('project_', true) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Upload mislukt.');
    }

    return 'uploads/project/' . $filename;
}

function normalizeOriginImageReferences(array $lines): array
{
    $normalized = [];

    foreach ($lines as $line) {
        $candidate = trim((string) $line);

        if ($candidate === '') {
            continue;
        }

        $isUploadPath = str_starts_with($candidate, 'uploads/');
        $isRemoteUrl = filter_var($candidate, FILTER_VALIDATE_URL) !== false;

        if (!$isUploadPath && !$isRemoteUrl) {
            throw new RuntimeException('Gebruik alleen geldige links (https://...) of uploads/... paden.');
        }

        $normalized[$candidate] = true;
    }

    return array_keys($normalized);
}

function saveOriginImageReferences(array $references): void
{
    $payload = implode("\n", $references);

    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'INSERT INTO content_blocks (id, title, body)
         VALUES (:id, :title, :body)
         ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)'
    );
    $stmt->execute([
        ':id' => 'home_origin_images',
        ':title' => null,
        ':body' => $payload,
    ]);
}

$adminUser = (string) env('ADMIN_USERNAME', 'admin');

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$loginError = null;
$saveSuccess = false;
$saveSuccessMessage = 'De wijzigingen zijn opgeslagen.';
$saveError = null;
$partnerSuccess = null;
$partnerError = null;

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!validateCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
        $loginError = 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.';
    } elseif ($username === $adminUser && adminPasswordIsValid($password)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Onjuiste gebruikersnaam of wachtwoord.';
    }
}

$isAdmin = !empty($_SESSION['is_admin']);

$contentConfig = [
    'hero' => [
        'label' => 'Hero sectie',
        'fields' => [
            'home_hero_title' => ['label' => 'Hero titel', 'title' => true, 'body' => false, 'textarea' => false],
            'home_hero_subtitle' => ['label' => 'Hero ondertitel / intro', 'title' => false, 'body' => true, 'textarea' => true],
            'home_hero_cta_game' => ['label' => 'Tekst knop "Speel het Spel"', 'title' => false, 'body' => true, 'textarea' => false],
            'home_hero_cta_quiz' => ['label' => 'Tekst knop "Doe de Quiz"', 'title' => false, 'body' => true, 'textarea' => false],
        ],
    ],
    'origin' => [
        'label' => 'Hoe is dit project ontstaan?',
        'fields' => [
            'home_origin_title' => ['label' => 'Sectietitel', 'title' => true, 'body' => false, 'textarea' => false],
            'home_origin_body' => ['label' => 'Tekst (HTML toegestaan)', 'title' => false, 'body' => true, 'textarea' => true],
            'home_origin_images' => ['label' => 'Projectfoto\'s (één URL per regel, bv. uploads/project/foto.jpg)', 'title' => false, 'body' => true, 'textarea' => true],
        ],
    ],
    'why' => [
        'label' => 'Waarom dit project?',
        'fields' => [
            'home_why_title' => ['label' => 'Sectietitel', 'title' => true, 'body' => false, 'textarea' => false],
            'home_why_educatief_title' => ['label' => 'Kaart 1 titel (Educatief)', 'title' => true, 'body' => false, 'textarea' => false],
            'home_why_educatief_body' => ['label' => 'Kaart 1 tekst', 'title' => false, 'body' => true, 'textarea' => true],
            'home_why_interactief_title' => ['label' => 'Kaart 2 titel (Interactief)', 'title' => true, 'body' => false, 'textarea' => false],
            'home_why_interactief_body' => ['label' => 'Kaart 2 tekst', 'title' => false, 'body' => true, 'textarea' => true],
            'home_why_alle_leeftijden_title' => ['label' => 'Kaart 3 titel (Voor alle leeftijden)', 'title' => true, 'body' => false, 'textarea' => false],
            'home_why_alle_leeftijden_body' => ['label' => 'Kaart 3 tekst', 'title' => false, 'body' => true, 'textarea' => true],
        ],
    ],
    'footer' => [
        'label' => 'Footer & contact',
        'fields' => [
            'home_footer_title' => ['label' => 'Footer titel', 'title' => true, 'body' => false, 'textarea' => false],
            'home_footer_body' => ['label' => 'Footer tekst', 'title' => false, 'body' => true, 'textarea' => true],
            'home_footer_contact' => ['label' => 'Contactlabel + e-mailadres', 'title' => true, 'body' => true, 'textarea' => false],
            'home_footer_address' => ['label' => 'Adreslabel + waarde', 'title' => true, 'body' => true, 'textarea' => false],
            'home_footer_phone' => ['label' => 'Telefoonlabel + nummer', 'title' => true, 'body' => true, 'textarea' => false],
            'home_footer_follow' => ['label' => 'Volg-ons-label + tekst', 'title' => true, 'body' => true, 'textarea' => false],
        ],
    ],
];

$allIds = [];

foreach ($contentConfig as $section) {
    foreach ($section['fields'] as $id => $_) {
        $allIds[] = $id;
    }
}

if ($isAdmin && isset($_POST['action']) && $_POST['action'] === 'save_content') {
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!validateCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
        $saveError = 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.';
    } else {
        try {
            $pdo = getPdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO content_blocks (id, title, body)
                 VALUES (:id, :title, :body)
                 ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)'
            );

            foreach ($allIds as $id) {
                $titleKey = $id . '_title';
                $bodyKey = $id . '_body';

                $title = isset($_POST[$titleKey]) ? trim((string) $_POST[$titleKey]) : null;
                $body = isset($_POST[$bodyKey]) ? trim((string) $_POST[$bodyKey]) : null;

                $title = ($title === '') ? null : $title;
                $body = ($body === '') ? null : $body;

                $stmt->execute([
                    ':id' => $id,
                    ':title' => $title,
                    ':body' => $body,
                ]);
            }

            $pdo->commit();
            $saveSuccess = true;
            $saveSuccessMessage = 'De wijzigingen zijn opgeslagen.';
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $saveError = 'Er ging iets mis bij het opslaan. Controleer de databaseconfiguratie.';
        }
    }
}

if ($isAdmin && isset($_POST['action']) && $_POST['action'] === 'upload_origin_images') {
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!validateCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
        $saveError = 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.';
    } else {
        try {
            if (!isset($_FILES['origin_images'])) {
                throw new RuntimeException('Kies minimaal één afbeelding.');
            }

            $uploadedFiles = normalizeUploadedFiles($_FILES['origin_images']);
            $newUrls = [];

            foreach ($uploadedFiles as $uploadedFile) {
                if ((int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $newUrls[] = storeProjectImage($uploadedFile);
            }

            if ($newUrls === []) {
                throw new RuntimeException('Kies minimaal één geldige afbeelding.');
            }

            $existingContent = getContentBlocks();
            $existingRaw = (string) ($existingContent['home_origin_images']['body'] ?? '');
            $existingLines = preg_split('/\R+/', $existingRaw) ?: [];
            $allUrls = normalizeOriginImageReferences(array_merge($existingLines, $newUrls));
            saveOriginImageReferences($allUrls);

            $saveSuccess = true;
            $saveSuccessMessage = count($newUrls) > 1
                ? 'Projectfoto\'s geüpload en toegevoegd bij Over het project.'
                : 'Projectfoto geüpload en toegevoegd bij Over het project.';
        } catch (Throwable $exception) {
            $saveError = $exception->getMessage();
        }
    }
}

if ($isAdmin && isset($_POST['action']) && $_POST['action'] === 'add_origin_image_links') {
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!validateCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
        $saveError = 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.';
    } else {
        try {
            $rawLinks = (string) ($_POST['origin_image_links'] ?? '');
            $newLines = preg_split('/\R+/', $rawLinks) ?: [];

            if ($newLines === []) {
                throw new RuntimeException('Vul minimaal één fotolink in.');
            }

            $existingContent = getContentBlocks();
            $existingRaw = (string) ($existingContent['home_origin_images']['body'] ?? '');
            $existingLines = preg_split('/\R+/', $existingRaw) ?: [];

            $allUrls = normalizeOriginImageReferences(array_merge($existingLines, $newLines));
            saveOriginImageReferences($allUrls);

            $saveSuccess = true;
            $saveSuccessMessage = 'Fotolink(s) toegevoegd bij Over het project.';
        } catch (Throwable $exception) {
            $saveError = $exception->getMessage();
        }
    }
}

if ($isAdmin && isset($_POST['action']) && $_POST['action'] === 'manage_partners') {
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!validateCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
        $partnerError = 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.';
    } else {
        $partnerAction = trim((string) ($_POST['partner_action'] ?? ''));

        if ($partnerAction === 'add') {
            $name = trim((string) ($_POST['partner_name'] ?? ''));
            $logoUrl = trim((string) ($_POST['partner_logo_url'] ?? ''));

            if (!addPartner($name, $logoUrl)) {
                $partnerError = 'Partner toevoegen is mislukt. Controleer naam en logo-URL.';
            } else {
                $partnerSuccess = 'Partner toegevoegd.';
            }
        }

        if ($partnerAction === 'remove') {
            $index = (int) ($_POST['partner_index'] ?? -1);

            if (!removePartnerByIndex($index)) {
                $partnerError = 'Partner verwijderen is mislukt.';
            } else {
                $partnerSuccess = 'Partner verwijderd.';
            }
        }
    }
}

$content = [];
$partners = loadPartners();

try {
    $content = getContentBlocks();
} catch (Throwable $exception) {
    $content = [];
}

function fieldValue(array $content, string $id, string $key): string
{
    if (isset($content[$id][$key]) && $content[$id][$key] !== null) {
        return (string) $content[$id][$key];
    }

    return '';
}

$csrf = getCsrfToken();
$originImagesRaw = fieldValue($content, 'home_origin_images', 'body');
$originImageLines = preg_split('/\R+/', $originImagesRaw) ?: [];
$originImages = [];

foreach ($originImageLines as $line) {
    $trimmed = trim((string) $line);

    if ($trimmed !== '') {
        $originImages[] = $trimmed;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | TNZ Wandelbos</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="seth-theme admin-page admin-content-page min-h-screen bg-slate-100 text-slate-900 <?= $isAdmin ? 'flex flex-col' : 'flex items-center justify-center py-10' ?>">
<?php if (!$isAdmin): ?>
    <div class="w-full max-w-md mx-auto bg-white shadow-lg rounded-2xl border border-slate-200 p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-9 w-9 rounded-md bg-slate-900 flex items-center justify-center text-white text-sm font-semibold">WB</div>
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Admin login</h1>
                <p class="text-xs text-slate-500 mt-0.5">Beheer pagina-content en projectteksten.</p>
            </div>
        </div>

        <?php if ($loginError): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                <?php echo h($loginError); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
            <div>
                <label for="username" class="block text-xs font-medium text-slate-700">Gebruikersnaam</label>
                <input type="text" id="username" name="username" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900" required autocomplete="username">
            </div>
            <div>
                <label for="password" class="block text-xs font-medium text-slate-700">Wachtwoord</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900" required autocomplete="current-password">
            </div>
            <button type="submit" class="w-full inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                Inloggen
            </button>
        </form>
    </div>
<?php else: ?>
    <?php renderAdminHeader('content', 'Content, partners en quizbeheer'); ?>
    <main class="flex-1 px-4 py-6 sm:px-6">
        <div class="w-full max-w-6xl mx-auto bg-white shadow-xl rounded-2xl border border-slate-200">
            <div class="px-6 pt-5 pb-6">
                <div class="mb-5">
                    <h1 class="text-lg font-semibold text-slate-900">Content beheren</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Teksten voor de historische landingspagina beheren.</p>
                </div>
            <section class="admin-panel mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                <h2 class="text-sm font-semibold text-slate-900">Locatie-quiz</h2>
                <p class="mt-1 text-xs text-slate-600">Ga direct naar het quizbeheer van locaties.</p>
                <a href="admin_locations.php#quiz-beheer" class="mt-3 inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">
                    Maak je eigen locatie quiz
                </a>
            </section>

            <?php if ($saveSuccess): ?>
                <div class="admin-flash admin-flash-success mb-4"><?php echo h($saveSuccessMessage); ?></div>
            <?php elseif ($saveError): ?>
                <div class="admin-flash admin-flash-error mb-4"><?php echo h($saveError); ?></div>
            <?php endif; ?>

            <form method="post" class="space-y-8">
                <input type="hidden" name="action" value="save_content">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

                <?php foreach ($contentConfig as $section): ?>
                    <section class="admin-panel border border-slate-100 rounded-xl p-4 sm:p-5">
                        <h2 class="text-sm font-semibold text-slate-900"><?php echo h($section['label']); ?></h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <?php foreach ($section['fields'] as $id => $field): ?>
                                <div class="sm:col-span-<?php echo $field['textarea'] ? '2' : '1'; ?>">
                                    <label class="block text-xs font-medium text-slate-700 mb-1.5"><?php echo h($field['label']); ?></label>

                                    <?php if ($field['title'] && $field['body']): ?>
                                        <div class="grid gap-2">
                                            <input type="text" name="<?php echo h($id . '_title'); ?>" value="<?php echo h(fieldValue($content, $id, 'title')); ?>" class="block w-full rounded-md border-slate-300 shadow-sm text-xs focus:border-slate-900 focus:ring-slate-900" placeholder="Label">
                                            <input type="text" name="<?php echo h($id . '_body'); ?>" value="<?php echo h(fieldValue($content, $id, 'body')); ?>" class="block w-full rounded-md border-slate-300 shadow-sm text-xs focus:border-slate-900 focus:ring-slate-900" placeholder="Waarde">
                                        </div>
                                    <?php elseif ($field['title']): ?>
                                        <input type="text" name="<?php echo h($id . '_title'); ?>" value="<?php echo h(fieldValue($content, $id, 'title')); ?>" class="block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900">
                                    <?php elseif ($field['body'] && $field['textarea']): ?>
                                        <textarea name="<?php echo h($id . '_body'); ?>" rows="5" class="block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900"><?php echo h(fieldValue($content, $id, 'body')); ?></textarea>
                                    <?php elseif ($field['body']): ?>
                                        <input type="text" name="<?php echo h($id . '_body'); ?>" value="<?php echo h(fieldValue($content, $id, 'body')); ?>" class="block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="history.php" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50" target="_blank">Bekijk landingspagina</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                        Wijzigingen opslaan
                    </button>
                </div>
            </form>

            <section id="origin-media" class="admin-panel border border-slate-100 rounded-xl p-4 sm:p-5 mt-8">
                <h2 class="text-sm font-semibold text-slate-900">Foto's bij Over het project</h2>
                <p class="mt-1 text-xs text-slate-600">Upload foto's of beheer URL's in het veld “Projectfoto's” bij de sectie “Hoe is dit project ontstaan?”.</p>

                <form method="post" enctype="multipart/form-data" class="mt-4 space-y-3">
                    <input type="hidden" name="action" value="upload_origin_images">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

                    <div>
                        <label for="origin_images" class="block text-xs font-medium text-slate-700 mb-1.5">Upload projectfoto's</label>
                        <input id="origin_images" type="file" name="origin_images[]" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm">
                        <p class="mt-1 text-xs text-slate-500">Ondersteund: JPG, PNG, WEBP · max 8MB per bestand.</p>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">Foto's uploaden</button>
                </form>

                <form method="post" class="mt-4 space-y-3">
                    <input type="hidden" name="action" value="add_origin_image_links">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

                    <div>
                        <label for="origin_image_links" class="block text-xs font-medium text-slate-700 mb-1.5">Foto-link(s) toevoegen</label>
                        <textarea id="origin_image_links" name="origin_image_links" rows="4" class="block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900" placeholder="https://voorbeeld.nl/foto.jpg&#10;uploads/project/mijn-foto.png"></textarea>
                        <p class="mt-1 text-xs text-slate-500">Gebruik één link per regel. Toegestaan: volledige URL of pad vanaf uploads/...</p>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">Link(s) toevoegen</button>
                </form>

                <?php if ($originImages !== []): ?>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($originImages as $originImage): ?>
                            <div class="rounded-lg border border-slate-200 p-2">
                                <img src="<?php echo h($originImage); ?>" alt="Projectfoto" class="h-32 w-full rounded object-cover" loading="lazy">
                                <p class="mt-2 text-[11px] break-all text-slate-600"><?php echo h($originImage); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="admin-panel border border-slate-100 rounded-xl p-4 sm:p-5 mt-8">
                <h2 class="text-sm font-semibold text-slate-900">Partners beheren</h2>

                <?php if ($partnerSuccess): ?>
                    <div class="admin-flash admin-flash-success mt-3"><?php echo h($partnerSuccess); ?></div>
                <?php elseif ($partnerError): ?>
                    <div class="admin-flash admin-flash-error mt-3"><?php echo h($partnerError); ?></div>
                <?php endif; ?>

                <div class="mt-4 grid gap-3">
                    <?php foreach ($partners as $index => $partner): ?>
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-slate-200 px-3 py-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-900"><?php echo h((string) $partner['name']); ?></p>
                                <p class="text-[11px] text-slate-600 break-all"><?php echo h((string) $partner['logo_url']); ?></p>
                            </div>
                            <form method="post" class="m-0">
                                <input type="hidden" name="action" value="manage_partners">
                                <input type="hidden" name="partner_action" value="remove">
                                <input type="hidden" name="partner_index" value="<?php echo (int) $index; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">Verwijderen</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post" class="mt-4 grid gap-3 sm:grid-cols-3">
                    <input type="hidden" name="action" value="manage_partners">
                    <input type="hidden" name="partner_action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

                    <input type="text" name="partner_name" placeholder="Partner naam" class="block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900" required>
                    <input type="text" name="partner_logo_url" placeholder="../project-logos/Bestand.png" class="block w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900" required>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">Partner toevoegen</button>
                </form>
            </section>
        </div>
    </main>
    <?php renderAdminFooter('contentbeheer'); ?>
<?php endif; ?>
</body>
</html>
