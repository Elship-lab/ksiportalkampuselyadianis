  </main><!-- /.main-content -->
</div><!-- /.app-layout -->

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
// Live clock
(function() {
  const clockEl = document.getElementById('clock');
  if (!clockEl) return;
  function tick() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    clockEl.textContent = `${h}:${m}:${s}`;
  }
  tick();
  setInterval(tick, 1000);
})();

// Mobile sidebar overlay
const overlay = document.getElementById('sidebarOverlay');
if (overlay) {
  overlay.style.display = 'block';
  overlay.style.opacity = '0';
  overlay.style.transition = 'opacity 0.3s';
}
document.getElementById('hamburger')?.addEventListener('click', () => {
  const sidebar = document.getElementById('sidebar');
  if (overlay) overlay.style.opacity = sidebar.classList.contains('mobile-open') ? '0' : '1';
});
</script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
