</div>
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="qr.scan">扫码进入</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="qrImage" src="" alt="QR Code">
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="app.js"></script>
<script src="team_name.js"></script>
<script>
function applyTeamName() {
  if (typeof TEAM_NAME === 'undefined') return;
  const lang = localStorage.getItem('lang') || document.documentElement.lang || 'en';
  const name = typeof TEAM_NAME === 'string' ? TEAM_NAME : (TEAM_NAME[lang] || '');
  if (!name) return;
  const regex = /(团队|Team)/g;
  if (regex.test(document.title)) {
    document.title = document.title.replace(regex, name + '$1');
  }
  (function walk(node) {
    node.childNodes.forEach(child => {
      if (child.nodeType === Node.TEXT_NODE) {
        if (regex.test(child.textContent)) {
          child.textContent = child.textContent.replace(regex, name + '$1');
        }
      } else {
        walk(child);
      }
    });
  })(document.body);
}
document.addEventListener('DOMContentLoaded', applyTeamName);
</script>
</body>
</html>
