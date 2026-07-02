<?php

/**
 * layout-bottom.php
 * Closing tags and global scripts.
 */

?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mobile Menu Toggle
        var btn = document.getElementById('mobile-menu-btn');
        var menu = document.getElementById('mobile-menu');
        var hamburger = document.getElementById('hamburger-icon');
        var closeIcon = document.getElementById('close-icon');
        if (btn && menu) {
            btn.addEventListener('click', function () {
                var isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                hamburger.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', !isOpen);
            });
        }
    });
</script>
<script type="module" src="/script.js?v=<?= filemtime(__DIR__ . '/../../../script.js') ?>"></script>
<?php if (!empty($page_config['scripts'])) : ?>
    <?php foreach ($page_config['scripts'] as $script) : ?>
        <script src="<?= htmlspecialchars($script) ?>?v=<?= filemtime(__DIR__ . '/../../../' . ltrim($script, '/')) ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>
</div><!-- Closing Standard Container -->
</body>

</html>