<?php
// Defensive fallback only; unknown routes normally receive a 404 before view selection.
?>
<section class="panel placeholder-panel"><div class="placeholder-icon" aria-hidden="true">◇</div><span class="pill pill-neutral">Not Found</span><h2>This page does not exist</h2><p>Use the sidebar to navigate to an available page.</p><a class="button secondary" href="<?= e(admin_url()) ?>">Back to dashboard</a></section>
