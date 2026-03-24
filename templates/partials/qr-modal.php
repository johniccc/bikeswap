<div class="modal-overlay" id="qr-modal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h2 class="modal-title">Skenovat QR kód</h2>
      <button type="button" class="modal-close" id="close-qr-modal" aria-label="Zavřít">
        <i data-lucide="x"></i>
      </button>
    </div>
    <div class="modal-body">
      <video id="qr-video" style="width:100%;border-radius:var(--radius-md);background:#000" playsinline></video>
      <canvas id="qr-canvas" style="display:none"></canvas>
      <p id="qr-status" class="text-center text-muted mt-md" style="font-size:0.9rem">Spouštím kameru...</p>
      <div class="text-center mt-lg">
        <label class="form-file-label" style="cursor:pointer">
          <i data-lucide="upload"></i> Nahrát obrázek
          <input type="file" id="qr-file-input" accept="image/*" capture="environment" style="display:none">
        </label>
      </div>

      <div class="divider"></div>

      <form action="/bike/search" method="GET" id="serial-form" class="flex gap-sm" style="align-items:flex-end">
        <div class="form-group mb-0" style="flex:1">
          <label for="serial-input">Sériové číslo</label>
          <input type="text" id="serial-input" name="frame_number" placeholder="Zadejte sériové číslo kola" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i data-lucide="search"></i> Hledat
        </button>
      </form>

      <form id="bike-id-form" class="flex gap-sm mt-md" style="align-items:flex-end">
        <div class="form-group mb-0" style="flex:1">
          <label for="bike-id-input">QR hash kola</label>
          <input type="text" id="bike-id-input" placeholder="Zadejte QR hash kola" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i data-lucide="search"></i> Hledat
        </button>
      </form>
    </div>
  </div>
</div>
