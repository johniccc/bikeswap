<div class="page-header">
  <h1>Nové upozornění na kolo</h1>
</div>

<div class="card mb-lg">
  <div class="card-body">
    <form method="post" action="/admin/warnings/new" id="warning-form">
      <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">

      <!-- Selected bikes -->
      <div class="form-group">
        <label>Vybraná kola *</label>
        <div class="bike-picker-selected" id="selected-bikes">
          <p class="text-muted" id="no-bikes-msg">Zatím nebyla vybrána žádná kola.</p>
        </div>
      </div>

      <!-- Bike picker -->
      <div class="form-group">
        <label>Vybrat kola</label>
        <input type="text" id="bike-search" class="form-control" placeholder="Hledat podle názvu, barvy nebo ID...">
        <div class="bike-picker-grid" id="bike-picker">
          <?php foreach ($allBikes as $bike): ?>
            <?php $photo = $bike->getPrimaryPhoto(); ?>
            <div class="bike-picker-item"
                 data-bike-id="<?= $bike->getId() ?>"
                 data-bike-name="<?= e($bike->getFullName()) ?>"
                 data-bike-color="<?= e($bike->getColor()) ?>"
                 data-bike-photo="<?= $photo ? e($photo->getUrl()) : '' ?>"
                 data-bike-status="<?= e($bike->getStatus()) ?>">
              <div class="bike-picker-photo">
                <?php if ($photo): ?>
                  <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>">
                <?php else: ?>
                  <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
                <?php endif; ?>
              </div>
              <div class="bike-picker-info">
                <span class="bike-picker-name"><?= e($bike->getFullName()) ?></span>
                <span class="bike-picker-meta"><?= e($bike->getColor()) ?> &middot; #<?= $bike->getId() ?></span>
              </div>
              <div class="bike-picker-check">
                <i data-lucide="plus-circle"></i>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label for="deadline">Termín vyzvednutí *</label>
        <input type="date" id="deadline" name="deadline" class="form-control" value="<?= e(old('deadline') ?? '') ?>" required min="<?= date('Y-m-d') ?>">
      </div>

      <div class="form-group">
        <label for="location">Místo nálezu</label>
        <input type="text" id="location" name="location" class="form-control" value="<?= e(old('location') ?? '') ?>" placeholder="Pardubice hlavní nádraží">
      </div>

      <div class="form-group">
        <label for="reason">Důvod upozornění *</label>
        <textarea id="reason" name="reason" class="form-control" rows="4" required><?= e(old('reason') ?? '') ?></textarea>
      </div>

      <div class="form-actions">
        <a href="/admin/warnings" class="btn btn-secondary">Zpět</a>
        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Vytvořit upozornění</button>
      </div>
    </form>
  </div>
</div>

<style>
.bike-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 0.75rem;
  max-height: 400px;
  overflow-y: auto;
  margin-top: 0.5rem;
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
}
.bike-picker-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem;
  border: 2px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.bike-picker-item:hover {
  border-color: var(--primary);
  background: var(--bg-secondary, #f5f5f0);
}
.bike-picker-item.is-selected {
  border-color: var(--primary);
  background: color-mix(in srgb, var(--primary) 8%, transparent);
}
.bike-picker-item.is-hidden { display: none; }
.bike-picker-photo {
  width: 56px;
  height: 42px;
  flex-shrink: 0;
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.bike-picker-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.bike-picker-photo .bike-card-photo-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary, #f5f5f0);
}
.bike-picker-photo .bike-card-photo-placeholder i { width: 20px; height: 20px; opacity: 0.4; }
.bike-picker-info {
  flex: 1;
  min-width: 0;
}
.bike-picker-name {
  display: block;
  font-weight: 600;
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bike-picker-meta {
  display: block;
  font-size: 0.75rem;
  color: var(--text-light);
}
.bike-picker-check {
  flex-shrink: 0;
  color: var(--text-light);
  transition: color 0.15s;
}
.bike-picker-item.is-selected .bike-picker-check { color: var(--primary); }

.bike-picker-selected {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  min-height: 2.5rem;
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--bg-secondary, #f5f5f0);
}
.bike-selected-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.5rem 0.3rem 0.3rem;
  background: var(--bg, #fff);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  font-size: 0.85rem;
}
.bike-selected-tag img {
  width: 32px;
  height: 24px;
  object-fit: cover;
  border-radius: var(--radius-sm);
}
.bike-selected-tag .tag-placeholder {
  width: 32px;
  height: 24px;
  background: var(--bg-secondary, #f5f5f0);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
}
.bike-selected-tag .tag-placeholder i { width: 14px; height: 14px; opacity: 0.4; }
.bike-selected-remove {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.1rem;
  color: var(--text-light);
  display: inline-flex;
}
.bike-selected-remove:hover { color: var(--danger, #c00); }
</style>

<script>
(function() {
  'use strict';

  var selectedBikes = {};
  var container = document.getElementById('selected-bikes');
  var noMsg = document.getElementById('no-bikes-msg');
  var submitBtn = document.getElementById('submit-btn');
  var searchInput = document.getElementById('bike-search');
  var items = document.querySelectorAll('.bike-picker-item');
  var form = document.getElementById('warning-form');
  var preselectedId = <?= json_encode($preselectedId) ?>;

  function createIcon(name) {
    var i = document.createElement('i');
    i.setAttribute('data-lucide', name);
    return i;
  }

  function updateUI() {
    var ids = Object.keys(selectedBikes);
    noMsg.style.display = ids.length ? 'none' : '';
    submitBtn.disabled = ids.length === 0;

    container.querySelectorAll('.bike-selected-tag').forEach(function(t) { t.remove(); });
    form.querySelectorAll('input[name="bike_ids[]"]').forEach(function(i) { i.remove(); });

    ids.forEach(function(id) {
      var b = selectedBikes[id];

      var tag = document.createElement('div');
      tag.className = 'bike-selected-tag';
      tag.dataset.bikeId = id;

      if (b.photo) {
        var img = document.createElement('img');
        img.src = b.photo;
        img.alt = b.name;
        tag.appendChild(img);
      } else {
        var placeholder = document.createElement('span');
        placeholder.className = 'tag-placeholder';
        placeholder.appendChild(createIcon('image'));
        tag.appendChild(placeholder);
      }

      var nameSpan = document.createElement('span');
      nameSpan.textContent = b.name;
      tag.appendChild(nameSpan);

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'bike-selected-remove';
      removeBtn.title = 'Odebrat';
      removeBtn.appendChild(createIcon('x'));
      removeBtn.addEventListener('click', function() { toggleBike(id); });
      tag.appendChild(removeBtn);

      container.appendChild(tag);

      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'bike_ids[]';
      input.value = id;
      form.appendChild(input);
    });

    items.forEach(function(item) {
      var isSelected = !!selectedBikes[item.dataset.bikeId];
      item.classList.toggle('is-selected', isSelected);
      var check = item.querySelector('.bike-picker-check');
      var oldIcon = check.querySelector('i, svg');
      if (oldIcon) oldIcon.remove();
      check.appendChild(createIcon(isSelected ? 'minus-circle' : 'plus-circle'));
    });

    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  function toggleBike(id) {
    if (selectedBikes[id]) {
      delete selectedBikes[id];
    } else {
      var item = document.querySelector('.bike-picker-item[data-bike-id="' + id + '"]');
      if (item) {
        selectedBikes[id] = {
          name: item.dataset.bikeName,
          color: item.dataset.bikeColor,
          photo: item.dataset.bikePhoto
        };
      }
    }
    updateUI();
  }

  items.forEach(function(item) {
    item.addEventListener('click', function() {
      toggleBike(item.dataset.bikeId);
    });
  });

  searchInput.addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    items.forEach(function(item) {
      if (!q) {
        item.classList.remove('is-hidden');
        return;
      }
      var text = (item.dataset.bikeName + ' ' + item.dataset.bikeColor + ' #' + item.dataset.bikeId).toLowerCase();
      item.classList.toggle('is-hidden', text.indexOf(q) === -1);
    });
  });

  if (preselectedId) {
    toggleBike(String(preselectedId));
  }
})();
</script>
