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
<div class="d-flex justify-content-between mb-3">
  <h2 class="bold-target" data-i18n="directions.title">Research Directions</h2>
  <div>
    <button type="button" class="btn btn-success" id="addDirectionBtn" data-i18n="directions.add">Add Direction</button>
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
  <tr data-id="<?= $d['id']; ?>" data-title="<?= htmlspecialchars($d['title'], ENT_QUOTES); ?>" data-description="<?= htmlspecialchars($d['description'] ?? '', ENT_QUOTES); ?>" data-bg="<?= htmlspecialchars($d['bg_color'] ?? '#ffffff', ENT_QUOTES); ?>"<?= $rowColor ? ' data-custom-bg="'.$rowColor.'" style="background-color:'.$rowColor.';"' : ''; ?>>
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
      <button type="button" class="btn btn-sm btn-primary direction-edit-btn" data-i18n="directions.action_edit">Edit</button>
      <button type="button" class="btn btn-sm btn-warning direction-members-btn" data-title="<?= htmlspecialchars($d['title'], ENT_QUOTES); ?>" data-i18n="directions.action_members">Members</button>
      <a class="btn btn-sm btn-danger" href="direction_delete.php?id=<?= $d['id']; ?>" onclick="return doubleConfirm('Delete direction?');" data-i18n="directions.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
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
<div class="modal fade" id="directionEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="directionEditForm">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="direction_edit.title_add">Add Research Direction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="directionIdInput">
          <div class="mb-3">
            <label class="form-label" for="directionTitleInput" data-i18n="direction_edit.label_title">Direction Title</label>
            <input type="text" name="title" id="directionTitleInput" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="directionDescriptionInput" data-i18n="direction_edit.label_description">Description</label>
            <textarea name="description" id="directionDescriptionInput" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="directionBgInput" data-i18n="direction_edit.label_bg">Background Color</label>
            <input type="color" name="bg_color" id="directionBgInput" class="form-control form-control-color" value="#ffffff">
            <div class="mt-2">
              <?php
              $suggestedColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
              foreach ($suggestedColors as $color) {
                  echo "<button type=\"button\" class=\"btn btn-sm border me-1\" style=\"background-color:$color;\" title=\"$color\" onclick=\"document.getElementById('directionBgInput').value='$color'\"></button>";
              }
              ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="direction_edit.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" data-i18n="direction_edit.save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="directionMembersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <span data-i18n="direction_members.title_prefix">Direction Members -</span>
          <span id="memberModalDirectionTitle" class="fw-semibold"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="memberAddForm" class="row g-2 align-items-end mb-3">
          <input type="hidden" name="direction_id" id="memberDirectionId">
          <div class="col-md-8">
            <label class="form-label" for="modalMemberSelect" data-i18n="direction_members.label_member">Member</label>
            <select name="member_id" id="modalMemberSelect" class="form-select" required>
              <option value="" data-i18n="direction_members.select_member">Select Member</option>
            </select>
          </div>
          <div class="col-md-4 text-md-end">
            <button type="submit" class="btn btn-primary w-100" data-i18n="direction_members.save">Add</button>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th></th>
                <th data-i18n="members.table.campus_id">Campus ID</th>
                <th data-i18n="members.table.name">Name</th>
                <th data-i18n="members.table.actions">Actions</th>
              </tr>
            </thead>
            <tbody id="modalMemberList"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="direction_edit.cancel">Cancel</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const directionListEl = document.getElementById('directionList');
  if (directionListEl) {
    Sortable.create(directionListEl, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function(){
        const order = Array.from(directionListEl.querySelectorAll('tr')).map((row, index) => ({id: row.dataset.id, position: index}));
        fetch('direction_order.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({order: order})
        });
      }
    });
  }

  const detailToggle = document.getElementById('detailToggle');
  if (detailToggle) {
    detailToggle.addEventListener('change', function(){
      document.querySelectorAll('.member-detail').forEach(span => {
        span.style.display = this.checked ? 'inline' : 'none';
      });
    });
  }

  const boldToggle = document.getElementById('boldToggle');
  if (boldToggle) {
    boldToggle.addEventListener('change', function(){
      document.querySelectorAll('.bold-target').forEach(el => {
        el.classList.toggle('fw-bold', this.checked);
      });
    });
  }

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[match]);

  const addDirectionBtn = document.getElementById('addDirectionBtn');
  const editModalEl = document.getElementById('directionEditModal');
  if (editModalEl && typeof bootstrap !== 'undefined') {
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('directionEditForm');
    const idInput = document.getElementById('directionIdInput');
    const titleInput = document.getElementById('directionTitleInput');
    const descriptionInput = document.getElementById('directionDescriptionInput');
    const bgInput = document.getElementById('directionBgInput');
    const editModalTitle = editModalEl.querySelector('.modal-title');

    const setEditMode = (isEdit) => {
      editModalTitle.setAttribute('data-i18n', isEdit ? 'direction_edit.title_edit' : 'direction_edit.title_add');
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      } else {
        editModalTitle.textContent = isEdit ? 'Edit Research Direction' : 'Add Research Direction';
      }
    };

    addDirectionBtn?.addEventListener('click', () => {
      editForm.reset();
      idInput.value = '';
      bgInput.value = '#ffffff';
      setEditMode(false);
      editModal.show();
    });

    document.querySelectorAll('.direction-edit-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        if (!row) return;
        idInput.value = row.dataset.id || '';
        titleInput.value = row.dataset.title || '';
        descriptionInput.value = row.dataset.description || '';
        bgInput.value = row.dataset.bg || '#ffffff';
        setEditMode(true);
        editModal.show();
      });
    });

    editForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      const formData = new FormData(editForm);
      fetch('direction_edit.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(response => response.json())
        .then(result => {
          if (result.status === 'ok') {
            window.location.reload();
          }
        })
        .catch(error => console.error(error));
    });
  }

  const memberModalEl = document.getElementById('directionMembersModal');
  if (memberModalEl && typeof bootstrap !== 'undefined') {
    const memberModal = new bootstrap.Modal(memberModalEl);
    const memberList = document.getElementById('modalMemberList');
    const memberForm = document.getElementById('memberAddForm');
    const memberSelect = document.getElementById('modalMemberSelect');
    const memberDirectionIdInput = document.getElementById('memberDirectionId');
    const memberTitle = document.getElementById('memberModalDirectionTitle');
    let memberSortable = null;

    const setupMemberSortable = () => {
      if (memberSortable) {
        memberSortable.destroy();
        memberSortable = null;
      }
      const rows = memberList.querySelectorAll('tr[data-id]');
      if (!rows.length) {
        return;
      }
      memberSortable = Sortable.create(memberList, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: () => {
          const order = Array.from(memberList.querySelectorAll('tr[data-id]')).map(row => row.dataset.id);
          fetch('direction_member_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({direction_id: memberDirectionIdInput.value, order})
          });
        }
      });
    };

    const renderEmptyRow = () => {
      memberList.innerHTML = '';
      const emptyRow = document.createElement('tr');
      emptyRow.setAttribute('data-placeholder', 'true');
      emptyRow.innerHTML = '<td colspan="4"><em data-i18n="directions.none">None</em></td>';
      memberList.appendChild(emptyRow);
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const addMemberRow = (member) => {
      memberList.querySelector('tr[data-placeholder]')?.remove();
      const row = document.createElement('tr');
      row.dataset.id = member.id;
      row.innerHTML = `
        <td class="drag-handle">&#9776;</td>
        <td>${escapeHtml(member.campus_id ?? '')}</td>
        <td>${escapeHtml(member.name ?? '')}</td>
        <td><button type="button" class="btn btn-sm btn-danger member-remove-btn" data-member-id="${escapeHtml(member.id)}" data-member-label="${escapeHtml((member.name ?? '') + ' (' + (member.campus_id ?? '') + ')')}" data-i18n="direction_members.remove">Remove</button></td>
      `;
      memberList.appendChild(row);
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
      setupMemberSortable();
    };

    const renderMembers = (members) => {
      memberList.innerHTML = '';
      if (!members.length) {
        renderEmptyRow();
        return;
      }
      members.forEach(addMemberRow);
    };

    const renderMemberSelect = (availableMembers, currentMembers) => {
      const currentIds = new Set(currentMembers.map(m => String(m.id)));
      memberSelect.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.setAttribute('data-i18n', 'direction_members.select_member');
      placeholder.textContent = 'Select Member';
      memberSelect.appendChild(placeholder);
      availableMembers.forEach(member => {
        if (currentIds.has(String(member.id))) {
          return;
        }
        const option = document.createElement('option');
        option.value = member.id;
        option.textContent = `${member.name ?? ''} (${member.campus_id ?? ''})`;
        memberSelect.appendChild(option);
      });
      memberSelect.value = '';
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const openMembersModal = (directionId, directionTitle) => {
      fetch(`direction_members.php?format=json&id=${encodeURIComponent(directionId)}`)
        .then(response => response.json())
        .then(data => {
          if (data.status !== 'ok') {
            return;
          }
          memberDirectionIdInput.value = directionId;
          memberTitle.textContent = directionTitle || data.direction.title || '';
          renderMembers(data.members || []);
          renderMemberSelect(data.available_members || [], data.members || []);
          if (typeof applyTranslations === 'function') {
            applyTranslations();
          }
          memberModal.show();
        })
        .catch(error => console.error(error));
    };

    document.querySelectorAll('.direction-members-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        if (!row) return;
        const directionId = row.dataset.id;
        const directionTitle = btn.getAttribute('data-title') || row.dataset.title || '';
        openMembersModal(directionId, directionTitle);
      });
    });

    memberForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!memberSelect.value) {
        return;
      }
      const formData = new FormData(memberForm);
      fetch('direction_member_add.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'ok' && data.member) {
            addMemberRow(data.member);
            const option = Array.from(memberSelect.options).find(opt => opt.value === String(data.member.id));
            option?.remove();
            memberSelect.value = '';
            if (typeof applyTranslations === 'function') {
              applyTranslations();
            }
          }
        })
        .catch(error => console.error(error));
    });

    memberList.addEventListener('click', (event) => {
      const btn = event.target.closest('.member-remove-btn');
      if (!btn) {
        return;
      }
      const memberId = btn.dataset.memberId;
      if (!memberId) {
        return;
      }
      const formData = new FormData();
      formData.append('direction_id', memberDirectionIdInput.value);
      formData.append('member_id', memberId);
      fetch('direction_member_remove.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'ok') {
            const row = btn.closest('tr');
            const label = btn.dataset.memberLabel || '';
            row?.remove();
            if (label) {
              const option = document.createElement('option');
              option.value = memberId;
              option.textContent = label;
              memberSelect.appendChild(option);
            }
            memberSelect.value = '';
            if (!memberList.querySelector('tr[data-id]')) {
              renderEmptyRow();
            } else {
              setupMemberSortable();
            }
            if (typeof applyTranslations === 'function') {
              applyTranslations();
            }
          }
        })
        .catch(error => console.error(error));
    });
  }
});
</script>
<?php include 'footer.php'; ?>
