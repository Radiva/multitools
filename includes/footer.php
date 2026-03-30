<?php
/**
 * Multi Tools — Footer Global
 *
 * Cara pakai di setiap halaman:
 *   <?php require 'includes/footer.php'; ?>
 *
 * Breadcrumb otomatis dari $seo['breadcrumbs'] yang diset di halaman.
 * ============================================================ */
?>

</main><!-- /#main-content -->

<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer role="contentinfo">
  <span class="footer-logo">
    Multi<span class="dot">Tools</span>
  </span>

  <span>Dibuat dengan ❤️ · Gratis selamanya</span>

  <nav aria-label="Footer links">
    <a href="/about">Tentang</a>
    <span aria-hidden="true">·</span>
    <a href="/request">Request Tool</a>
    <span aria-hidden="true">·</span>
    <a href="/sitemap.xml">Sitemap</a>
  </nav>
</footer>

<!-- ══ SCRIPTS ══ -->
<script src="/assets/js/main.js" defer></script>

<?php if (!empty($seo['extra_scripts'])): ?>
  <!-- ══ EXTRA SCRIPTS (dari halaman) ══ -->
  <?= $seo['extra_scripts'] ?>
<?php endif; ?>

</body>
</html>