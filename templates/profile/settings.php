<div class="page-header">
  <h1>Nastavení profilu</h1>
  <div class="page-header-actions">
    <a href="/profile" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na profil
    </a>
  </div>
</div>

<div class="card max-w-lg">
  <div class="card-body">
    <div class="flex gap-md items-center mb-lg">
      <div class="profile-avatar">
        <i data-lucide="user" style="width:24px;height:24px"></i>
      </div>
      <div>
        <h3 style="margin-bottom:0.15rem"><?= e($user->getName()) ?></h3>
        <p class="text-muted text-sm"><?= e($user->getEmail()) ?></p>
      </div>
    </div>

    <div class="alert alert-info">
      <i data-lucide="info"></i>
      <span>Změna e-mailu nebo hesla momentálně není dostupná přes web. Kontaktujte správce.</span>
    </div>
  </div>
</div>
