<div class="main-public-padded" style="display:flex;justify-content:center;padding-top:4rem">
  <div style="width:100%;max-width:420px">

    <div class="card">
      <div class="card-header">
        <h2 style="margin:0">Nastavit nové heslo</h2>
      </div>
      <div class="card-body">
        <form method="POST" action="/reset-password">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="form-group">
            <label for="rp-password">Nové heslo</label>
            <input type="password" id="rp-password" name="password" required autocomplete="new-password" placeholder="Min. 8 znaků">
            <div class="password-requirements mt-xs" id="pwd-requirements">
              <div class="pwd-req" id="req-length"><i data-lucide="circle" style="width:12px;height:12px"></i> Min. 8 znaků</div>
              <div class="pwd-req" id="req-upper"><i data-lucide="circle" style="width:12px;height:12px"></i> Alespoň 1 velké písmeno</div>
              <div class="pwd-req" id="req-number"><i data-lucide="circle" style="width:12px;height:12px"></i> Alespoň 1 číslo</div>
            </div>
          </div>

          <div class="form-group">
            <label for="rp-password-confirm">Heslo znovu</label>
            <input type="password" id="rp-password-confirm" name="password_confirmation" required autocomplete="new-password" placeholder="Zopakujte heslo">
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            <i data-lucide="lock"></i> Nastavit nové heslo
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
(function() {
  var pwdInput = document.getElementById('rp-password');
  if (!pwdInput) return;
  var reqs = {
    'req-length': function(v) { return v.length >= 8; },
    'req-upper':  function(v) { return /[A-Z]/.test(v); },
    'req-number': function(v) { return /[0-9]/.test(v); }
  };
  function updateReq(id, met) {
    var el = document.getElementById(id);
    if (!el) return;
    var existing = el.querySelector('i[data-lucide], svg');
    if (existing) existing.remove();
    var newIcon = document.createElement('i');
    newIcon.setAttribute('data-lucide', met ? 'disc' : 'circle');
    newIcon.style.cssText = 'width:12px;height:12px';
    el.prepend(newIcon);
    if (window.lucide) window.lucide.createIcons();
    el.style.color = met ? 'var(--success)' : 'var(--text-muted)';
  }
  pwdInput.addEventListener('input', function() {
    var val = pwdInput.value;
    for (var id in reqs) { updateReq(id, reqs[id](val)); }
  });
})();
</script>
