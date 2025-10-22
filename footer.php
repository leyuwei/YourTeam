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
        <div class="input-group mt-3">
          <input id="qrLinkInput" type="text" class="form-control" readonly>
          <button type="button" id="qrCopyBtn" class="btn btn-outline-secondary" data-i18n="qr.copy">复制链接</button>
        </div>
        <a id="qrLinkAnchor" class="d-block mt-2 text-break" href="#" target="_blank" rel="noopener noreferrer"></a>
      </div>
    </div>
  </div>
</div>
<script src="./style/bootstrap.bundle.min.js"></script>
<script src="team_name.js"></script>
<script src="app.js"></script>
</body>
</html>
