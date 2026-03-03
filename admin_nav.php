<?php

declare(strict_types=1);

function adminEsc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderAdminHeader(string $activePage, string $subtitle): void
{
    $items = [
        ['key' => 'content', 'label' => 'Content', 'href' => 'admin.php'],
        ['key' => 'locations', 'label' => 'Locaties', 'href' => 'admin_locations.php'],
        ['key' => 'qrcodes', 'label' => 'QR-codes', 'href' => 'admin_qrcodes.php'],
        ['key' => 'public', 'label' => 'Publieke site', 'href' => 'locaties.php'],
        ['key' => 'logout', 'label' => 'Uitloggen', 'href' => 'admin.php?logout=1'],
    ];

    $activeLabel = 'Dashboard';

    foreach ($items as $item) {
        if ($item['key'] === $activePage) {
            $activeLabel = (string) $item['label'];
            break;
        }
    }

    ?>
    <aside class="admin-dashboard-sidebar border-r border-slate-300">
        <div class="admin-dashboard-brand border-b border-slate-300">
            <p class="text-xs uppercase tracking-[0.2em] opacity-80">TNZ Wandelbos</p>
            <p class="mt-1 text-base font-semibold uppercase tracking-wide">Admin</p>
        </div>
        <nav class="admin-dashboard-nav px-3 py-3 text-[11px] uppercase tracking-wider">
            <?php foreach ($items as $item): ?>
                <?php
                $isActive = $activePage === $item['key'];
                $className = $isActive ? 'admin-tab admin-tab-active' : 'admin-tab';
                ?>
                <a href="<?= adminEsc($item['href']) ?>" class="<?= adminEsc($className) ?>"><?= adminEsc($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <header class="admin-dashboard-topbar border-b border-slate-300 px-4 py-3 sm:px-6">
        <div class="admin-dashboard-topbar-inner max-w-6xl mx-auto flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold tracking-wide uppercase"><?= adminEsc($activeLabel) ?></p>
                <p class="text-[11px] uppercase tracking-wider opacity-85"><?= adminEsc($subtitle) ?></p>
            </div>
        </div>
    </header>
    <?php
}

function renderAdminFooter(string $label): void
{
    ?>
    <footer class="company-footer admin-dashboard-footer border-t border-slate-300 px-4 py-3 sm:px-6">
        <div class="max-w-6xl mx-auto text-[11px] uppercase tracking-wider opacity-90">TNZ Wandelbos · <?= adminEsc($label) ?></div>
        <?php renderPartnersFooterLogos('max-w-6xl mx-auto footer-logos'); ?>
    </footer>
    <?php
}
