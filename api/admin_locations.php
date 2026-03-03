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

function normalizeDecimalInput(string $value): ?float
{
    $normalized = trim(str_replace(',', '.', $value));

    if ($normalized === '') {
        return null;
    }

    if (!is_numeric($normalized)) {
        return null;
    }

    return (float) $normalized;
}

function storeUploadedLocationImage(array $file): string
{
    $uploadDir = __DIR__ . '/uploads';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Uploadmap kon niet worden aangemaakt.');
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

    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        throw new RuntimeException('Bestand te groot of ongeldig (max 5MB).');
    }

    if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Alleen JPG, PNG en WEBP zijn toegestaan.');
    }

    if (@getimagesize($tmpName) === false) {
        throw new RuntimeException('Bestand is geen geldige afbeelding.');
    }

    $filename = uniqid('loc_', true) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Upload mislukt.');
    }

    return 'uploads/' . $filename;
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

function validateImageUrlInput(string $imageUrl): string
{
    $trimmed = trim($imageUrl);

    if ($trimmed === '') {
        return '';
    }

    if (str_starts_with($trimmed, 'uploads/')) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('Foto URL is ongeldig.');
    }

    return $trimmed;
}

function normalizePanoramaInput(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return '';
    }

    if (preg_match('/<iframe[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $trimmed, $matches) === 1) {
        $trimmed = trim((string) ($matches[1] ?? ''));
    }

    if ($trimmed === '') {
        return '';
    }

    if (str_starts_with($trimmed, 'uploads/')) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('Panorama URL of iframe is ongeldig.');
    }

    return $trimmed;
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
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    return $flash;
}

function requireAdmin(): void
{
    if (empty($_SESSION['is_admin'])) {
        header('Location: admin.php');
        exit;
    }
}

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $token = $_POST['csrf_token'] ?? null;
    $redirectUrl = 'admin_locations.php';

    if (!validateCsrf(is_string($token) ? $token : null)) {
        flash('error', 'Ongeldige sessie. Vernieuw de pagina en probeer opnieuw.');
        header('Location: admin_locations.php');
        exit;
    }

    try {
        if ($action === 'save_location') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $shortText = trim((string) ($_POST['short_text'] ?? ''));
            $longText = trim((string) ($_POST['long_text'] ?? ''));
            $latitude = normalizeDecimalInput((string) ($_POST['latitude'] ?? ''));
            $longitude = normalizeDecimalInput((string) ($_POST['longitude'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']);
            $panoramaUrl = normalizePanoramaInput((string) ($_POST['panorama_url'] ?? ''));
            $panoramaImageUrlInput = normalizePanoramaInput((string) ($_POST['panorama_image_url'] ?? ''));

            if ($name === '' || $slug === '') {
                throw new RuntimeException('Naam en slug zijn verplicht.');
            }

            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                throw new RuntimeException('Slug mag alleen kleine letters, cijfers en streepjes bevatten.');
            }

            $photoUrl = validateImageUrlInput((string) ($_POST['primary_image_url'] ?? ''));

            if (isset($_FILES['primary_image_file']) && (int) ($_FILES['primary_image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $photoUrl = storeUploadedLocationImage($_FILES['primary_image_file']);
            }

            if ($panoramaImageUrlInput !== '') {
                $panoramaUrl = $panoramaImageUrlInput;
            }

            if (isset($_FILES['panorama_image_file']) && (int) ($_FILES['panorama_image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $panoramaUrl = storeUploadedLocationImage($_FILES['panorama_image_file']);
            }

            $savedLocationId = saveLocation([
                'name' => $name,
                'slug' => $slug,
                'short_text' => $shortText,
                'long_text' => $longText,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'panorama_url' => $panoramaUrl,
                'hero_image_url' => $photoUrl,
            ], $id);

            $redirectUrl = 'admin_locations.php?edit=' . $savedLocationId;

            flash('success', $id > 0 ? 'Locatie bijgewerkt.' : 'Locatie toegevoegd.');
        }

        if ($action === 'upload_image') {
            $locationId = (int) ($_POST['location_id'] ?? 0);
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $yearLabel = trim((string) ($_POST['year_label'] ?? ''));
            $source = trim((string) ($_POST['source'] ?? ''));

            if ($locationId <= 0 || !isset($_FILES['image'])) {
                throw new RuntimeException('Kies een locatie en afbeelding.');
            }

            $uploadedFiles = normalizeUploadedFiles($_FILES['image']);
            $storedCount = 0;

            foreach ($uploadedFiles as $uploadedFile) {
                if ((int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $imageUrl = storeUploadedLocationImage($uploadedFile);
                insertLocationImage($locationId, $imageUrl, $caption, $description, $yearLabel, $source);
                $storedCount += 1;
            }

            if ($storedCount === 0) {
                throw new RuntimeException('Kies minimaal één afbeelding.');
            }

            $redirectUrl = 'admin_locations.php?edit=' . $locationId;
            flash('success', $storedCount > 1 ? 'Afbeeldingen succesvol geüpload.' : 'Afbeelding succesvol geüpload.');
        }

        if ($action === 'update_image') {
            $imageId = (int) ($_POST['image_id'] ?? 0);
            $locationId = (int) ($_POST['location_id'] ?? 0);
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $yearLabel = trim((string) ($_POST['year_label'] ?? ''));
            $source = trim((string) ($_POST['source'] ?? ''));
            $imageUrl = validateImageUrlInput((string) ($_POST['image_url'] ?? ''));
            $currentImageUrl = trim((string) ($_POST['current_image_url'] ?? ''));

            if ($imageId <= 0 || $locationId <= 0) {
                throw new RuntimeException('Ongeldige vondst geselecteerd.');
            }

            if (isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $imageUrl = storeUploadedLocationImage($_FILES['image']);
            }

            if ($imageUrl === '') {
                $imageUrl = validateImageUrlInput($currentImageUrl);
            }

            if ($imageUrl === '') {
                throw new RuntimeException('Vul een geldige foto URL in of upload een afbeelding.');
            }

            $updated = updateLocationImage($imageId, $locationId, $imageUrl, $caption, $description, $yearLabel, $source);

            if (!$updated) {
                throw new RuntimeException('Vondst kon niet worden bijgewerkt.');
            }

            $redirectUrl = 'admin_locations.php?edit=' . $locationId;
            flash('success', 'Vondst bijgewerkt.');
        }

        if ($action === 'add_quiz') {
            $locationId = (int) ($_POST['location_id'] ?? 0);
            $question = trim((string) ($_POST['question'] ?? ''));
            $optionA = trim((string) ($_POST['option_a'] ?? ''));
            $optionB = trim((string) ($_POST['option_b'] ?? ''));
            $optionC = trim((string) ($_POST['option_c'] ?? ''));
            $correct = trim((string) ($_POST['correct_option'] ?? 'a'));

            if ($locationId <= 0 || $question === '' || $optionA === '' || $optionB === '' || $optionC === '') {
                throw new RuntimeException('Vul alle quizvelden in.');
            }

            if (!in_array($correct, ['a', 'b', 'c'], true)) {
                $correct = 'a';
            }

            $pdo = getPdo();
            $stmt = $pdo->prepare(
                'INSERT INTO location_quizzes (location_id, question, option_a, option_b, option_c, correct_option, sort_order)
                 VALUES (:location_id, :question, :option_a, :option_b, :option_c, :correct_option, :sort_order)'
            );
            $stmt->execute([
                ':location_id' => $locationId,
                ':question' => $question,
                ':option_a' => $optionA,
                ':option_b' => $optionB,
                ':option_c' => $optionC,
                ':correct_option' => $correct,
                ':sort_order' => 0,
            ]);

            flash('success', 'Quizvraag toegevoegd.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$locations = fetchLocations(false);
$editId = (int) ($_GET['edit'] ?? 0);
$editLocation = $editId > 0 ? fetchLocationById($editId) : null;
$editImages = $editLocation ? fetchLocationImages((int) $editLocation['id']) : [];
$heroImageUrl = (string) ($editLocation['hero_image_url'] ?? '');
$flash = getFlash();
$csrf = csrfToken();
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Locatiebeheer | TNZ Wandelbos</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        #location-picker-map {
            height: 320px;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.5rem;
            z-index: 0;
        }
    </style>
</head>
<body class="seth-theme admin-page min-h-screen flex flex-col">
<?php renderAdminHeader('locations', 'Locatiebeheer, media en quizvragen'); ?>

<main class="flex-1 px-4 py-6 sm:px-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <?php if ($flash): ?>
            <div class="admin-flash <?= (string) ($flash['type'] ?? '') === 'success' ? 'admin-flash-success' : 'admin-flash-error' ?>">
                <strong><?= h((string) $flash['type']) ?>:</strong> <?= h((string) $flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="admin-panel rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
            <h1 class="text-lg font-semibold"><?= $editLocation ? 'Locatie bewerken' : 'Nieuwe locatie toevoegen' ?></h1>
            <p class="mt-2 text-sm text-slate-700">Beheer routepunten, kaartcoördinaten en locatie-teksten vanuit één dashboard.</p>

            <form method="post" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 gap-4">
                <input type="hidden" name="action" value="save_location">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int) ($editLocation['id'] ?? 0) ?>">

                <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/40 space-y-3">
                    <p class="text-xs uppercase tracking-wider">Basisgegevens</p>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="name">Naam</label>
                            <input id="name" type="text" name="name" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['name'] ?? '')) ?>">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="slug">Slug</label>
                            <input id="slug" type="text" name="slug" required pattern="[a-z0-9\-]+" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['slug'] ?? '')) ?>">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="sort_order">Volgorde</label>
                            <input id="sort_order" type="number" name="sort_order" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['sort_order'] ?? '0')) ?>">
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" <?= !isset($editLocation['is_active']) || (int) $editLocation['is_active'] === 1 ? 'checked' : '' ?>>
                                <span>Actieve locatie</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1" for="short_text">Korte tekst</label>
                        <textarea id="short_text" name="short_text" rows="2" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm"><?= h((string) ($editLocation['short_text'] ?? '')) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wider mb-1" for="long_text">Lange tekst</label>
                        <textarea id="long_text" name="long_text" rows="5" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm"><?= h((string) ($editLocation['long_text'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/40 space-y-3">
                    <p class="text-xs uppercase tracking-wider">Kaart & coördinaten</p>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="latitude">Latitude</label>
                            <input id="latitude" type="number" step="0.0000001" name="latitude" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['latitude'] ?? '')) ?>">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="longitude">Longitude</label>
                            <input id="longitude" type="number" step="0.0000001" name="longitude" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['longitude'] ?? '')) ?>">
                        </div>
                    </div>
                    <p class="text-xs uppercase tracking-wider mb-3">Locatie kiezen op OpenStreetMap</p>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input id="location-search" type="text" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" placeholder="Zoek adres, plaats of punt (bijv. Philippine)">
                        <button id="location-search-button" type="button" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Zoek</button>
                    </div>
                    <p id="location-search-status" class="mt-2 text-xs text-slate-700"></p>
                    <div id="location-picker-map" class="mt-3"></div>
                    <p class="mt-2 text-xs text-slate-700">Klik op de kaart of sleep de marker om latitude/longitude automatisch in te vullen.</p>
                    <a id="osm-preview-link" href="#" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center text-xs font-semibold underline-offset-4 hover:underline">Open in OpenStreetMap</a>
                </div>

                <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/40 space-y-3">
                    <p class="text-xs uppercase tracking-wider">Panorama bron</p>
                    <label class="block text-xs uppercase tracking-wider mb-1" for="panorama_url">Panorama URL</label>
                    <input id="panorama_url" type="text" name="panorama_url" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['panorama_url'] ?? '')) ?>" placeholder="https://... of volledige <iframe ...>">
                </div>

                <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/40">
                    <p class="text-xs uppercase tracking-wider mb-3">360° Panoramabeeld (optioneel, voor interactieve weergave)</p>
                    <div class="mb-3 rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-xs text-slate-700 leading-5">
                        <p class="font-semibold mb-1">Wandelbos tip</p>
                        <p>Je kunt elke panorama-resolutie gebruiken. Plak een directe afbeeldings-URL of upload een bestand voor interactieve weergave met Pannellum. Heb je een externe embed, plak dan de URL of volledige iframe-code bij Panorama URL.</p>
                    </div>
                    <?php if (!empty($editLocation['panorama_url'])): ?>
                        <div class="mb-3">
                            <img src="<?= h((string) $editLocation['panorama_url']) ?>" alt="Huidig panoramabeeld" class="h-28 w-44 rounded-md border border-slate-300 object-cover" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="panorama_image_url">Panorama-afbeelding URL</label>
                            <input id="panorama_image_url" type="text" name="panorama_image_url" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($editLocation['panorama_url'] ?? '')) ?>" placeholder="https://... of uploads/...">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="panorama_image_file">of upload nieuw panoramabeeld</label>
                            <input id="panorama_image_file" type="file" name="panorama_image_file" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                        </div>
                    </div>
                </div>

                <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/40">
                    <p class="text-xs uppercase tracking-wider mb-3">Hoofdfoto (voor locatiekaart/pagina, los van vondsten)</p>
                    <?php if ($heroImageUrl !== ''): ?>
                        <div class="mb-3">
                            <img src="<?= h($heroImageUrl) ?>" alt="Huidige hoofdfoto" class="h-28 w-44 rounded-md border border-slate-300 object-cover" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="primary_image_url">Foto URL</label>
                            <input id="primary_image_url" type="text" name="primary_image_url" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h($heroImageUrl) ?>" placeholder="https://... of uploads/...">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="primary_image_file">of upload nieuwe foto</label>
                            <input id="primary_image_file" type="file" name="primary_image_file" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border border-slate-300 rounded-lg p-4 bg-slate-50/40">
                    <p class="text-xs uppercase tracking-wider">Acties</p>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Opslaan</button>
                        <a href="admin_locations.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Nieuwe invoer</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <article class="admin-panel rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide">Afbeelding uploaden</h2>
                <form method="post" enctype="multipart/form-data" class="mt-4 space-y-3">
                    <input type="hidden" name="action" value="upload_image">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                    <label class="block text-xs uppercase tracking-wider mb-1" for="location_id">Locatie</label>
                    <select id="location_id" name="location_id" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        <option value="">Kies locatie</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= (int) $location['id'] ?>" <?= $editLocation && (int) $editLocation['id'] === (int) $location['id'] ? 'selected' : '' ?>><?= h((string) $location['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="block text-xs uppercase tracking-wider mb-1" for="image">Afbeelding(en)</label>
                    <input id="image" type="file" name="image[]" accept="image/jpeg,image/png,image/webp" multiple required class="w-full text-sm">
                    <p class="text-xs text-slate-700">Je kunt meerdere foto's tegelijk selecteren voor vondsten.</p>

                    <label class="block text-xs uppercase tracking-wider mb-1" for="caption">Bijschrift</label>
                    <input id="caption" type="text" name="caption" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">

                    <label class="block text-xs uppercase tracking-wider mb-1" for="description">Tekst / toelichting</label>
                    <textarea id="description" name="description" rows="3" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" placeholder="Korte uitleg bij deze vondst"></textarea>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="year_label">Periode / jaar</label>
                            <input id="year_label" type="text" name="year_label" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="source">Bron</label>
                            <input id="source" type="text" name="source" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Uploaden</button>
                </form>
            </article>

            <article id="quiz-beheer" class="admin-panel rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide">Quizvraag toevoegen</h2>
                <form method="post" class="mt-4 space-y-3">
                    <input type="hidden" name="action" value="add_quiz">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                    <label class="block text-xs uppercase tracking-wider mb-1" for="quiz_location_id">Locatie</label>
                    <select id="quiz_location_id" name="location_id" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        <option value="">Kies locatie</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= (int) $location['id'] ?>"><?= h((string) $location['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="block text-xs uppercase tracking-wider mb-1" for="question">Vraag</label>
                    <textarea id="question" name="question" rows="3" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm"></textarea>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="option_a">Optie A</label>
                            <input id="option_a" type="text" name="option_a" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="option_b">Optie B</label>
                            <input id="option_b" type="text" name="option_b" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider mb-1" for="option_c">Optie C</label>
                            <input id="option_c" type="text" name="option_c" required class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <label class="block text-xs uppercase tracking-wider mb-1" for="correct_option">Correct antwoord</label>
                    <select id="correct_option" name="correct_option" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                        <option value="a">Optie A</option>
                        <option value="b">Optie B</option>
                        <option value="c">Optie C</option>
                    </select>

                    <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Quiz opslaan</button>
                </form>
            </article>
        </section>

        <?php if ($editLocation): ?>
            <section class="admin-panel rounded-xl border border-slate-300 bg-slate-100 p-5 sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide">Vondsten bewerken · <?= h((string) $editLocation['name']) ?></h2>
                <?php if ($editImages === []): ?>
                    <p class="mt-3 text-sm text-slate-700">Nog geen vondsten voor deze locatie. Gebruik hierboven ‘Afbeelding uploaden’.</p>
                <?php else: ?>
                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <?php foreach ($editImages as $image): ?>
                            <form method="post" enctype="multipart/form-data" class="rounded-lg border border-slate-300 p-4 space-y-3 bg-slate-50/30">
                                <input type="hidden" name="action" value="update_image">
                                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                <input type="hidden" name="location_id" value="<?= (int) $editLocation['id'] ?>">
                                <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                <input type="hidden" name="current_image_url" value="<?= h((string) $image['image_url']) ?>">

                                <img src="<?= h((string) $image['image_url']) ?>" alt="Vondst" class="h-36 w-full rounded-md border border-slate-300 object-cover" loading="lazy">

                                <div>
                                    <label class="block text-xs uppercase tracking-wider mb-1">Foto URL</label>
                                    <input type="text" name="image_url" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) $image['image_url']) ?>">
                                </div>

                                <div>
                                    <label class="block text-xs uppercase tracking-wider mb-1">Nieuwe foto uploaden (optioneel)</label>
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs uppercase tracking-wider mb-1">Bijschrift</label>
                                    <input type="text" name="caption" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($image['caption'] ?? '')) ?>">
                                </div>

                                <div>
                                    <label class="block text-xs uppercase tracking-wider mb-1">Tekst / toelichting</label>
                                    <textarea name="description" rows="3" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" placeholder="Korte uitleg bij deze vondst"><?= h((string) ($image['description'] ?? '')) ?></textarea>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs uppercase tracking-wider mb-1">Periode / jaar</label>
                                        <input type="text" name="year_label" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($image['year_label'] ?? '')) ?>">
                                    </div>
                                    <div>
                                        <label class="block text-xs uppercase tracking-wider mb-1">Bron</label>
                                        <input type="text" name="source" class="w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm" value="<?= h((string) ($image['source'] ?? '')) ?>">
                                    </div>
                                </div>

                                <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider">Vondst opslaan</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-3">Alle locaties</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($locations as $location): ?>
                    <?php
                    $images = fetchLocationImages((int) $location['id']);
                    $imageUrl = (string) ($images[0]['image_url'] ?? 'https://picsum.photos/seed/' . rawurlencode((string) $location['slug']) . '/1200/700');
                    ?>
                    <article class="admin-panel rounded-xl border border-slate-300 bg-slate-100 overflow-hidden">
                        <img src="<?= h($imageUrl) ?>" alt="<?= h((string) $location['name']) ?>" class="h-36 w-full object-cover" loading="lazy">
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide"><?= h((string) $location['name']) ?></h3>
                                <span class="text-[10px] uppercase tracking-wider border border-slate-300 rounded-full px-2 py-0.5"><?= (int) $location['is_active'] === 1 ? 'Actief' : 'Inactief' ?></span>
                            </div>
                            <p class="mt-2 text-xs text-slate-700 min-h-10"><?= h((string) ($location['short_text'] ?? '')) ?></p>
                            <p class="mt-2 text-[11px] break-all">locatie.php?slug=<?= h((string) $location['slug']) ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="admin_locations.php?edit=<?= (int) $location['id'] ?>" class="inline-flex items-center rounded-full border border-slate-300 px-3 py-1 text-[11px] uppercase tracking-wider">Bewerk</a>
                                <a href="locatie.php?slug=<?= rawurlencode((string) $location['slug']) ?>" class="inline-flex items-center rounded-full border border-slate-300 px-3 py-1 text-[11px] uppercase tracking-wider" target="_blank" rel="noopener">Open</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php renderAdminFooter('locatiebeheer'); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const mapElement = document.getElementById('location-picker-map');
    const searchInput = document.getElementById('location-search');
    const searchButton = document.getElementById('location-search-button');
    const searchStatus = document.getElementById('location-search-status');
    const osmLink = document.getElementById('osm-preview-link');

    if (!latInput || !lngInput || !mapElement || typeof L === 'undefined') {
        return;
    }

    const defaultLat = 51.2939;
    const defaultLng = 3.7562;

    function parseNumber(value) {
        const normalized = String(value || '').replace(',', '.').trim();
        const numberValue = Number.parseFloat(normalized);
        return Number.isFinite(numberValue) ? numberValue : null;
    }

    const latParsed = parseNumber(latInput.value);
    const lngParsed = parseNumber(lngInput.value);
    const hasExistingCoords = latParsed !== null && lngParsed !== null;
    const initialLat = latParsed ?? defaultLat;
    const initialLng = lngParsed ?? defaultLng;

    const map = L.map(mapElement).setView([initialLat, initialLng], hasExistingCoords ? 15 : 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap-bijdragers'
    }).addTo(map);

    const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    function formatCoord(value) {
        return (Math.round(value * 10000000) / 10000000).toFixed(7);
    }

    function updateOsmLink(lat, lng) {
        if (!osmLink) {
            return;
        }

        osmLink.href = 'https://www.openstreetmap.org/?mlat=' + encodeURIComponent(lat) + '&mlon=' + encodeURIComponent(lng) + '#map=17/' + encodeURIComponent(lat) + '/' + encodeURIComponent(lng);
    }

    function setCoordinates(lat, lng, centerMap) {
        const formattedLat = formatCoord(lat);
        const formattedLng = formatCoord(lng);

        latInput.value = formattedLat;
        lngInput.value = formattedLng;
        marker.setLatLng([lat, lng]);
        updateOsmLink(formattedLat, formattedLng);

        if (centerMap) {
            map.setView([lat, lng], Math.max(map.getZoom(), 15));
        }
    }

    map.on('click', function (event) {
        setCoordinates(event.latlng.lat, event.latlng.lng, false);
    });

    marker.on('dragend', function () {
        const position = marker.getLatLng();
        setCoordinates(position.lat, position.lng, false);
    });

    latInput.addEventListener('change', function () {
        const lat = parseNumber(latInput.value);
        const lng = parseNumber(lngInput.value);

        if (lat !== null && lng !== null) {
            setCoordinates(lat, lng, true);
        }
    });

    lngInput.addEventListener('change', function () {
        const lat = parseNumber(latInput.value);
        const lng = parseNumber(lngInput.value);

        if (lat !== null && lng !== null) {
            setCoordinates(lat, lng, true);
        }
    });

    async function searchLocation() {
        if (!searchInput) {
            return;
        }

        const query = searchInput.value.trim();

        if (query === '') {
            if (searchStatus) {
                searchStatus.textContent = 'Vul eerst een zoekterm in.';
            }
            return;
        }

        if (searchStatus) {
            searchStatus.textContent = 'Zoeken...';
        }

        try {
            const response = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query));
            const results = await response.json();

            if (!Array.isArray(results) || results.length === 0) {
                if (searchStatus) {
                    searchStatus.textContent = 'Geen locatie gevonden.';
                }
                return;
            }

            const first = results[0];
            const lat = Number.parseFloat(first.lat);
            const lng = Number.parseFloat(first.lon);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                if (searchStatus) {
                    searchStatus.textContent = 'Locatie kon niet worden verwerkt.';
                }
                return;
            }

            setCoordinates(lat, lng, true);

            if (searchStatus) {
                searchStatus.textContent = 'Locatie gevonden: ' + (first.display_name || query);
            }
        } catch (error) {
            if (searchStatus) {
                searchStatus.textContent = 'Zoeken mislukt. Probeer opnieuw.';
            }
        }
    }

    if (searchButton) {
        searchButton.addEventListener('click', searchLocation);
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchLocation();
            }
        });
    }

    setCoordinates(initialLat, initialLng, false);

    setTimeout(function () {
        map.invalidateSize();
    }, 100);
})();
</script>
</body>
</html>
