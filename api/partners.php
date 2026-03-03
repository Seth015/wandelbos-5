<?php

declare(strict_types=1);

function partnersEsc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function partnersStoragePath(): string
{
    return __DIR__ . '/database/partners.json';
}

function defaultPartners(): array
{
    return [
        ['name' => 'Scalda', 'logo_url' => '../project-logos/Scalda.png'],
        ['name' => 'Oranjeschool', 'logo_url' => '../project-logos/Oranjeschool.png'],
        ['name' => 'Lego League', 'logo_url' => '../project-logos/Lego%20League.png'],
        ['name' => 'Laureyn', 'logo_url' => '../project-logos/Laureyn.png'],
        ['name' => 'Landschaapbeheer Zeeland', 'logo_url' => '../project-logos/Landschaapbeheer%20Zeeland.png'],
        ['name' => 'Gemeente Terneuzen', 'logo_url' => '../project-logos/Gemeente%20Terneuzen.png'],
        ['name' => 'Elevantio', 'logo_url' => '../project-logos/Elevantio.png'],
        ['name' => 'Dow', 'logo_url' => '../project-logos/Dow.png'],
    ];
}

function sanitizePartners(array $partners): array
{
    $clean = [];

    foreach ($partners as $partner) {
        if (!is_array($partner)) {
            continue;
        }

        $name = trim((string) ($partner['name'] ?? ''));
        $logoUrl = trim((string) ($partner['logo_url'] ?? ''));

        if ($name === '' || $logoUrl === '') {
            continue;
        }

        $clean[] = [
            'name' => $name,
            'logo_url' => $logoUrl,
        ];
    }

    return $clean;
}

function loadPartners(): array
{
    $path = partnersStoragePath();

    if (!is_file($path)) {
        return defaultPartners();
    }

    $raw = file_get_contents($path);

    if (!is_string($raw) || $raw === '') {
        return defaultPartners();
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return defaultPartners();
    }

    $partners = sanitizePartners($decoded);

    return $partners === [] ? defaultPartners() : $partners;
}

function savePartners(array $partners): bool
{
    $path = partnersStoragePath();
    $clean = sanitizePartners($partners);

    if ($clean === []) {
        return false;
    }

    $json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!is_string($json)) {
        return false;
    }

    return file_put_contents($path, $json) !== false;
}

function addPartner(string $name, string $logoUrl): bool
{
    $name = trim($name);
    $logoUrl = trim($logoUrl);

    if ($name === '' || $logoUrl === '') {
        return false;
    }

    $partners = loadPartners();
    $partners[] = ['name' => $name, 'logo_url' => $logoUrl];

    return savePartners($partners);
}

function removePartnerByIndex(int $index): bool
{
    $partners = loadPartners();

    if (!isset($partners[$index])) {
        return false;
    }

    array_splice($partners, $index, 1);

    if ($partners === []) {
        return false;
    }

    return savePartners($partners);
}

function renderPartnersFooterLogos(string $containerClass = 'max-w-6xl mx-auto footer-logos'): void
{
    $partners = loadPartners();

    echo '<div class="' . partnersEsc($containerClass) . '" aria-label="Projectlogos">';

    foreach ($partners as $partner) {
        $name = (string) $partner['name'];
        $logoUrl = (string) $partner['logo_url'];

        echo '<span class="footer-logo"><img src="' . partnersEsc($logoUrl) . '" alt="' . partnersEsc($name) . '"></span>';
    }

    echo '</div>';
}
