<h1>Nastavení profilu</h1>

<div class="card">
    <div class="card-body">
        <p><strong>E-mail:</strong> <?= e($user->getEmail()) ?></p>
        <p class="text-muted">Změna e-mailu nebo hesla momentálně není dostupná přes web. Kontaktujte správce.</p>
        <a href="/profile" class="btn btn-secondary">← Zpět na profil</a>
    </div>
</div>
