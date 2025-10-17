<?php include 'header.php';
if($_SESSION['role']==='member'){
    $stmt = $pdo->prepare('SELECT d.* FROM research_directions d JOIN direction_members dm ON d.id=dm.direction_id WHERE dm.member_id=? ORDER BY dm.sort_order');
    $stmt->execute([$_SESSION['member_id']]);
    $directions = $stmt->fetchAll();
?>
<h2 class="bold-target" data-i18n="directions.title">Research Directions</h2>
<ul>
<?php foreach($directions as $d): ?>
  <li><?= htmlspecialchars($d['title']); ?></li>
<?php endforeach; ?>
</ul>
<?php include 'footer.php'; exit; }
// Fetch research directions along with their members' details
$stmt = $pdo->query("SELECT d.*, GROUP_CONCAT(CONCAT(m.name, '(', COALESCE(m.degree_pursuing, ''), ',', COALESCE(m.year_of_join, ''), ')') ORDER BY dm.sort_order SEPARATOR ', ') AS member_names
                     FROM research_directions d
                     LEFT JOIN direction_members dm ON d.id = dm.direction_id
                     LEFT JOIN members m ON dm.member_id = m.id AND m.status != 'exited'
                     GROUP BY d.id
                     ORDER BY d.sort_order");
$directions = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3 align-items-center">
  <h2 class="bold-target mb-0" data-i18n="directions.title">Research Directions</h2>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-success" id="addDirectionBtn" data-i18n="directions.add">Add Direction</button>
    <button type="button" class="btn btn-outline-secondary" id="importDirectionsBtn" data-i18n="directions.import">Import XLSX</button>
    <a class="btn btn-secondary" href="directions_export.php" id="exportDirections" data-i18n="directions.export">Export XLSX</a>
  </div>
</div>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="detailToggle" checked>
  <label class="form-check-label" for="detailToggle" data-i18n="directions.toggle_details">Show Member Details</label>
</div>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="boldToggle">
  <label class="form-check-label" for="boldToggle" data-i18n="bold_font">Bold font</label>
</div>
<table class="table table-bordered">
  <thead>
  <tr>
    <th></th>
    <th data-i18n="directions.table_title">Title</th>
    <th data-i18n="directions.table_members">Members</th>
    <th data-i18n="directions.table_actions">Actions</th>
  </tr>
  </thead>
  <tbody id="directionList">
  <?php foreach($directions as $d): ?>
  <?php $rowColor = $d['bg_color'] ? htmlspecialchars($d['bg_color']) : ''; ?>
  <tr data-id="<?= $d['id']; ?>"<?= $rowColor ? ' data-custom-bg="'.$rowColor.'" style="background-color:'.$rowColor.';"' : ''; ?>>
    <td class="drag-handle">&#9776;</td>
    <td class="bold-target"><?= htmlspecialchars($d['title']); ?></td>
    <td>
      <?php
      if ($d['member_names']) {
          $members = explode(', ', $d['member_names']);
          foreach ($members as $idx => $mem) {
              if (preg_match('/^([^()]+)(?:\(([^()]*)\))?$/', $mem, $m)) {
                  echo '<span class="member-name bold-target">' . htmlspecialchars($m[1]) . '</span>';
                  if (!empty($m[2])) {
                      echo '<span class="member-detail text-muted">(' . htmlspecialchars($m[2]) . ')</span>';
                  }
                  if ($idx < count($members) - 1) echo ', ';
              }
          }
      } else {
          echo '<em data-i18n="directions.none">None</em>';
      }
      ?>
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-primary btn-edit-direction" data-id="<?= $d['id']; ?>" data-i18n="directions.action_edit">Edit</button>
      <a class="btn btn-sm btn-warning" href="direction_members.php?id=<?= $d['id']; ?>" data-i18n="directions.action_members">Members</a>
      <a class="btn btn-sm btn-danger" href="direction_delete.php?id=<?= $d['id']; ?>" onclick="return doubleConfirm('Delete direction?');" data-i18n="directions.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="modal fade" id="directionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="directionModalTitle" data-i18n="direction_edit.title_add">Add Direction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="directionFormError"></div>
        <form id="directionForm">
          <input type="hidden" name="id" id="directionIdField">
          <div class="mb-3">
            <label class="form-label" data-i18n="direction_edit.label_title">Direction Title</label>
            <input type="text" name="title" class="form-control" id="directionTitle" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="direction_edit.label_description">Description</label>
            <textarea name="description" class="form-control" rows="3" id="directionDescription"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="direction_edit.label_bg">Background Color</label>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <input type="color" name="bg_color" class="form-control form-control-color" id="directionBgColor" value="#ffffff">
              <div class="d-flex gap-1 flex-wrap" id="directionColorSuggestions">
                <?php $directionColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
                foreach ($directionColors as $color): ?>
                  <button type="button" class="btn btn-sm border" data-color="<?= $color; ?>" style="background-color: <?= $color; ?>"></button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="direction_edit.cancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveDirectionBtn" data-i18n="direction_edit.save">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="directionImportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="directions.import_title">Import Research Directions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="directionImportError"></div>
        <div class="alert alert-success d-none" id="directionImportSuccess"></div>
        <form id="directionImportForm" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label" data-i18n="directions.import_hint">Upload an XLSX file with research direction data.</label>
            <input type="file" name="file" accept=".xlsx" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="direction_edit.cancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitDirectionImport" data-i18n="directions.import_submit">Import</button>
      </div>
    </div>
  </div>
</div>
<h3 class="mt-4 bold-target" data-i18n="directions.assignment_title">Research Direction Assignments</h3>
<table class="table table-bordered">
  <tr><th data-i18n="directions.assignment_member">Member</th><th data-i18n="directions.assignment_direction">Research Directions</th></tr>
  <?php
  $memberDirs = $pdo->query("SELECT m.name, m.degree_pursuing, m.year_of_join, GROUP_CONCAT(d.title ORDER BY dm.sort_order SEPARATOR ', ') AS dirs FROM members m LEFT JOIN direction_members dm ON m.id=dm.member_id LEFT JOIN research_directions d ON dm.direction_id=d.id WHERE m.status != 'exited' GROUP BY m.id ORDER BY m.sort_order")->fetchAll();
  foreach($memberDirs as $md): ?>
  <tr>
    <td><span class="member-name bold-target"><?= htmlspecialchars($md["name"]); ?></span><span class="member-detail text-muted">(<?= htmlspecialchars($md["degree_pursuing"]); ?>,<?= htmlspecialchars($md["year_of_join"]); ?>)</span></td>
    <td class="bold-target"><?= $md['dirs'] ? htmlspecialchars($md['dirs']) : '<span style="color:red"><em data-i18n="directions.none">None</em></span>'; ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const langKey = () => document.documentElement.lang || 'zh';
  const t = (key, fallback = '') => {
    try {
      return translations?.[langKey()]?.[key] ?? fallback;
    } catch (e) {
      return fallback;
    }
  };
  const directionModalEl = document.getElementById('directionModal');
  const directionModal = directionModalEl ? new bootstrap.Modal(directionModalEl) : null;
  const directionForm = document.getElementById('directionForm');
  const directionFormError = document.getElementById('directionFormError');
  const directionModalTitle = document.getElementById('directionModalTitle');
  const directionIdField = document.getElementById('directionIdField');
  const directionTitle = document.getElementById('directionTitle');
  const directionDescription = document.getElementById('directionDescription');
  const directionBgColor = document.getElementById('directionBgColor');
  const saveDirectionBtn = document.getElementById('saveDirectionBtn');
  const directionColorButtons = document.querySelectorAll('#directionColorSuggestions button[data-color]');

  function setDirectionModalTitle(key) {
    if (directionModalTitle) {
      directionModalTitle.setAttribute('data-i18n', key);
      window.applyTranslations?.();
    }
  }

  function resetDirectionError() {
    if (!directionFormError) return;
    directionFormError.classList.add('d-none');
    directionFormError.textContent = '';
  }

  function showDirectionError(key, fallback) {
    if (!directionFormError) return;
    directionFormError.textContent = t(key, fallback);
    directionFormError.classList.remove('d-none');
  }

  function fillDirectionForm(data) {
    directionIdField.value = data.id || '';
    directionTitle.value = data.title || '';
    directionDescription.value = data.description || '';
    directionBgColor.value = data.bg_color || '#ffffff';
  }

  function openDirectionModal(mode, id = null) {
    if (!directionModal || !directionForm) return;
    directionForm.reset();
    resetDirectionError();
    if (mode === 'add') {
      setDirectionModalTitle('direction_edit.title_add');
      fillDirectionForm({});
      directionModal.show();
      return;
    }
    setDirectionModalTitle('direction_edit.title_edit');
    fetch('direction_edit.php?id=' + encodeURIComponent(id))
      .then(res => res.json())
      .then(data => {
        if (data?.success) {
          fillDirectionForm(data.direction || {});
          directionModal.show();
        } else {
          alert(t('direction_edit.load_failed', 'Unable to load research direction.'));
        }
      })
      .catch(() => {
        alert(t('direction_edit.load_failed', 'Unable to load research direction.'));
      });
  }

  document.getElementById('addDirectionBtn')?.addEventListener('click', () => openDirectionModal('add'));
  document.querySelectorAll('.btn-edit-direction').forEach(btn => {
    btn.addEventListener('click', () => openDirectionModal('edit', btn.dataset.id));
  });

  directionColorButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (directionBgColor) {
        directionBgColor.value = btn.dataset.color;
      }
    });
  });

  saveDirectionBtn?.addEventListener('click', () => {
    if (!directionForm || !directionForm.reportValidity()) return;
    resetDirectionError();
    const formData = new FormData(directionForm);
    fetch('direction_edit.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (!data?.success) {
          const code = data?.code;
          if (code === 'title_required') {
            showDirectionError('direction_edit.error_title', data?.message || 'Title required');
          } else {
            showDirectionError('direction_edit.error_generic', data?.message || 'Failed to save direction');
          }
          return;
        }
        window.location.reload();
      })
      .catch(() => {
        showDirectionError('direction_edit.error_generic', 'Failed to save direction');
      });
  });

  const directionImportModalEl = document.getElementById('directionImportModal');
  const directionImportModalInstance = directionImportModalEl ? new bootstrap.Modal(directionImportModalEl) : null;
  const importDirectionsBtn = document.getElementById('importDirectionsBtn');
  const directionImportForm = document.getElementById('directionImportForm');
  const directionImportError = document.getElementById('directionImportError');
  const directionImportSuccess = document.getElementById('directionImportSuccess');
  const submitDirectionImport = document.getElementById('submitDirectionImport');

  function resetDirectionImportMessages() {
    if (directionImportError) {
      directionImportError.classList.add('d-none');
      directionImportError.textContent = '';
    }
    if (directionImportSuccess) {
      directionImportSuccess.classList.add('d-none');
      directionImportSuccess.textContent = '';
    }
  }

  importDirectionsBtn?.addEventListener('click', () => {
    if (!directionImportModalInstance || !directionImportForm) return;
    directionImportForm.reset();
    resetDirectionImportMessages();
    directionImportModalInstance.show();
  });

  submitDirectionImport?.addEventListener('click', () => {
    if (!directionImportForm || !directionImportForm.reportValidity()) return;
    resetDirectionImportMessages();
    const formData = new FormData(directionImportForm);
    fetch('directions_import.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (!data?.success) {
          if (directionImportError) {
            directionImportError.textContent = data?.message || t('directions.import_error', 'Failed to import.');
            directionImportError.classList.remove('d-none');
          }
          return;
        }
        if (directionImportSuccess) {
          const template = t('directions.import_success', 'Import completed. Added {inserted}, Updated {updated}.');
          directionImportSuccess.textContent = template
            .replace('{inserted}', data.inserted ?? 0)
            .replace('{updated}', data.updated ?? 0);
          directionImportSuccess.classList.remove('d-none');
        }
        setTimeout(() => window.location.reload(), 1000);
      })
      .catch(() => {
        if (directionImportError) {
          directionImportError.textContent = t('directions.import_error', 'Failed to import.');
          directionImportError.classList.remove('d-none');
        }
      });
  });

  Sortable.create(document.getElementById('directionList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(){
      const order = Array.from(document.querySelectorAll('#directionList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
      fetch('direction_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });

  document.getElementById('detailToggle').addEventListener('change', function(){
    document.querySelectorAll('.member-detail').forEach(span => {
      span.style.display = this.checked ? 'inline' : 'none';
    });
  });

  document.getElementById('boldToggle').addEventListener('change', function(){
    document.querySelectorAll('.bold-target').forEach(el => {
      el.classList.toggle('fw-bold', this.checked);
    });
  });
});
</script>
<?php include 'footer.php'; ?>
