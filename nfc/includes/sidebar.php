<?php
// includes/sidebar.php

// Tailwind (only once per page, but safe here)
?>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<?php
// -----------------------------------------------
// NAVIGATION ARRAY
// -----------------------------------------------
$navItems = [
    [
        "label" => "Dashboard",
        "url"   => "/nfc/index.php",
        "icon"  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    ],
    [
        "label" => "Students",
        "url"   => "/nfc/students/manage.php",
        "icon"  => '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 10l-4 4-4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    ],
    [
        "label" => "Projects",
        "url"   => "/nfc/projects/manage.php",
        "icon"  => '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 20V6M6 12h12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    ],
    [
        "label" => "Inventory",
        "url"   => "/nfc/inventory/manage.php",
        "icon"  => '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-6a2 2 0 012-2h2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    ],
];
?>

<aside id="main-sidebar" class="w-72 bg-white/60 glass border-r border-gray-200 p-6 hidden lg:block">

    <!-- Logo -->
    <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white text-lg font-bold">C</div>
        <div>
            <div class="font-semibold">Coursue</div>
            <div class="text-xs text-gray-500">Learning platform</div>
        </div>
    </div>

    <!-- Dynamic Navigation -->
    <nav class="space-y-2 text-sm">
        <?php foreach ($navItems as $item): ?>
            <a 
                href="<?= $item['url'] ?>" 
                class="flex items-center gap-3 p-2 rounded-lg 
                <?php if ($_SERVER['REQUEST_URI'] === $item['url']) echo 'bg-violet-50 text-violet-700 font-medium'; else echo 'hover:bg-gray-100'; ?>"
            >
                <?= $item['icon'] ?>
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

</aside>
