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
<!-- </div>Closing Standard Container -->
</body>

</html>