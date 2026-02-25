<?php
/**
 * MarvelStore v1.0 — Footer Partial
 * Decomposed from new_dashboard.php (Gold Standard)
 * 
 * Script load order (CRITICAL):
 *   1. app.min.js (jQuery + Bootstrap)
 *   2. Extra libraries ($extra_js — page-specific)
 *   3. scripts.js (Otika UI logic)
 *   4. custom.js
 * 
 * Required variables before include:
 *   $extra_js (array, optional) — Additional JS file paths loaded BETWEEN app.min.js and scripts.js
 */
$extra_js = $extra_js ?? [];
?>
        </section>
      </div>
      <footer class="main-footer">
        <div class="footer-left">&copy; <?= date('Y') ?> <?= APP_NAME ?></div>
        <div class="footer-right"></div>
      </footer>
    </div>
  </div>

  <!-- 1. General JS Scripts (jQuery + Bootstrap) -->
  <script src="<?= OTIKA_ASSETS ?>js/app.min.js"></script>

  <!-- 2. Extra Libraries (page-specific) -->
  <?php foreach ($extra_js as $js): ?>
  <script src="<?= $js ?>"></script>
  <?php endforeach; ?>

  <!-- 3. Template JS File (Otika UI logic — sidebar, toggles) -->
  <script src="<?= OTIKA_ASSETS ?>js/scripts.js"></script>

  <!-- 4. Custom JS File -->
  <script src="<?= OTIKA_ASSETS ?>js/custom.js"></script>
</body>
</html>
