<?php include 'header.php';
if($_SESSION['role']==='member'){
    $stmt = $pdo->prepare('SELECT p.* FROM projects p JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL WHERE l.member_id=? ORDER BY p.sort_order');
    $stmt->execute([$_SESSION['member_id']]);
    $projects = $stmt->fetchAll();
?>
<h2 class="bold-target" data-i18n="projects.title">Projects</h2>
<table class="table table-bordered">
  <tr><th data-i18n="projects.table_title">Title</th><th data-i18n="projects.table_begin">Begin</th><th data-i18n="projects.table_end">End</th><th data-i18n="projects.table_status">Status</th></tr>
  <?php foreach($projects as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['title']); ?></td>
    <td><?= htmlspecialchars($p['begin_date']); ?></td>
    <td><?= htmlspecialchars($p['end_date']); ?></td>
    <td><?= htmlspecialchars($p['status']); ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include 'footer.php'; exit; }
$status = $_GET['status'] ?? '';
$dirMapStmt = $pdo->query("SELECT dm.member_id, GROUP_CONCAT(rd.title SEPARATOR ', ') AS dirs FROM direction_members dm JOIN research_directions rd ON dm.direction_id=rd.id GROUP BY dm.member_id");
$memberDirections = [];
foreach($dirMapStmt as $row){
    $memberDirections[$row['member_id']] = $row['dirs'];
}
if($status){
    $stmt = $pdo->prepare('SELECT p.*, GROUP_CONCAT(CONCAT(m.id, "|", m.name, "|", COALESCE(m.degree_pursuing, ""), "|", COALESCE(m.year_of_join, "")) ORDER BY l.sort_order SEPARATOR ";") AS member_data FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id AND m.status != "exited" WHERE p.status=? GROUP BY p.id ORDER BY p.sort_order');
    $stmt->execute([$status]);
    $projects = $stmt->fetchAll();
} else {
    $projects = $pdo->query('SELECT p.*, GROUP_CONCAT(CONCAT(m.id, "|", m.name, "|", COALESCE(m.degree_pursuing, ""), "|", COALESCE(m.year_of_join, "")) ORDER BY l.sort_order SEPARATOR ";") AS member_data FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id AND m.status != "exited" GROUP BY p.id ORDER BY p.sort_order')->fetchAll();
}
?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="bold-target" data-i18n="projects.title">Projects</h2>
  <div>
    <button type="button" class="btn btn-success" id="addProjectBtn" data-i18n="projects.add">Add Project</button>
  </div>
</div>
<form class="row g-3 mb-3" method="get">
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="" data-i18n="projects.filter_all">All Status</option>
      <option value="todo" <?= $status=='todo'?'selected':''; ?> data-i18n="projects.filter.todo">Todo</option>
      <option value="ongoing" <?= $status=='ongoing'?'selected':''; ?> data-i18n="projects.filter.ongoing">Ongoing</option>
      <option value="paused" <?= $status=='paused'?'selected':''; ?> data-i18n="projects.filter.paused">Paused</option>
      <option value="finished" <?= $status=='finished'?'selected':''; ?> data-i18n="projects.filter.finished">Finished</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary" data-i18n="projects.filter.button">Filter</button>
  </div>
</form>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="detailToggle" checked>
  <label class="form-check-label" for="detailToggle" data-i18n="projects.toggle_details">Show Member Details</label>
</div>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="boldToggle">
  <label class="form-check-label" for="boldToggle" data-i18n="bold_font">Bold font</label>
</div>
<div class="table-responsive project-table-responsive">
  <table class="table table-bordered align-middle">
    <thead>
    <tr>
      <th class="text-nowrap"></th>
      <th class="text-nowrap" data-i18n="projects.table_title">Title</th>
      <th class="text-nowrap" data-i18n="projects.table_members">Members</th>
      <th class="text-nowrap" data-i18n="projects.table_begin">Begin</th>
      <th class="text-nowrap" data-i18n="projects.table_end">End</th>
      <th class="text-nowrap" data-i18n="projects.table_status">Status</th>
      <th class="text-nowrap" data-i18n="projects.table_actions">Actions</th>
    </tr>
    </thead>
    <tbody id="projectList">
  <?php foreach($projects as $p): ?>
  <?php
    $memberList = [];
    if ($p['member_data']) {
        foreach(explode(';', $p['member_data']) as $md){
            if(!$md) continue;
            list($mid,$mname,$mdegree,$myear) = explode('|',$md);
            $memberList[] = ['id'=>$mid,'name'=>$mname,'degree'=>$mdegree,'year'=>$myear];
        }
    }
    $rowColor = $p['bg_color'] ? htmlspecialchars($p['bg_color']) : '';
  ?>
  <tr data-id="<?= $p['id']; ?>" data-title="<?= htmlspecialchars($p['title'], ENT_QUOTES); ?>" data-description="<?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES); ?>" data-bg="<?= htmlspecialchars($p['bg_color'] ?? '#ffffff', ENT_QUOTES); ?>" data-begin="<?= htmlspecialchars($p['begin_date'] ?? '', ENT_QUOTES); ?>" data-end="<?= htmlspecialchars($p['end_date'] ?? '', ENT_QUOTES); ?>" data-status="<?= htmlspecialchars($p['status'] ?? 'todo', ENT_QUOTES); ?>"<?= $rowColor ? ' data-custom-bg="'.$rowColor.'" style="background-color:'.$rowColor.';"' : ''; ?>>
    <td class="drag-handle text-nowrap">&#9776;</td>
    <td class="bold-target text-nowrap"><?= htmlspecialchars($p['title']); ?></td>
    <td class="text-nowrap">
      <?php if($memberList): ?>
        <?php foreach($memberList as $idx=>$m): ?>
          <span class="member-name bold-target" data-bs-toggle="tooltip" title="<?= htmlspecialchars($memberDirections[$m['id']] ?? '') ?>"<?php if(empty($memberDirections[$m['id']])) echo ' data-i18n-title="projects.no_direction"'; ?>>
            <?= htmlspecialchars($m['name']); ?>
            <span class="member-detail text-muted">(<?= htmlspecialchars($m['degree']); ?>,<?= htmlspecialchars($m['year']); ?>)</span>
          </span><?= $idx < count($memberList)-1 ? ', ' : ''; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <em data-i18n="directions.none">None</em>
      <?php endif; ?>
    </td>
    <td class="text-nowrap"><?= htmlspecialchars($p['begin_date']); ?></td>
    <td class="text-nowrap"><?= htmlspecialchars($p['end_date']); ?></td>
    <td class="text-nowrap" data-i18n="projects.status.<?= htmlspecialchars($p['status']); ?>"><?= htmlspecialchars($p['status']); ?></td>
    <td class="text-nowrap">
      <button type="button" class="btn btn-sm btn-primary project-edit-btn" data-i18n="projects.action_edit">Edit</button>
      <button type="button" class="btn btn-sm btn-warning project-members-btn" data-project-title="<?= htmlspecialchars($p['title'], ENT_QUOTES); ?>" data-i18n="projects.action_members">Members</button>
      <a class="btn btn-sm btn-danger" href="project_delete.php?id=<?= $p['id']; ?>" onclick="return doubleConfirm('Delete project?');" data-i18n="projects.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="projectEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="projectEditForm">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="project_edit.title_add">Add Project</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="projectIdInput">
          <div class="mb-3">
            <label class="form-label" for="projectTitleInput" data-i18n="project_edit.label_title">Project Title</label>
            <input type="text" name="title" id="projectTitleInput" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="projectDescriptionInput" data-i18n="project_edit.label_description">Project Description</label>
            <textarea name="description" id="projectDescriptionInput" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="projectBgInput" data-i18n="project_edit.label_bg">Background Color</label>
            <input type="color" name="bg_color" id="projectBgInput" class="form-control form-control-color" value="#ffffff">
            <div class="mt-2">
              <?php $suggestedColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
              foreach ($suggestedColors as $color): ?>
                <button type="button" class="btn btn-sm border me-1" style="background-color:<?= $color; ?>;" title="<?= $color; ?>" onclick="document.getElementById('projectBgInput').value='<?= $color; ?>'"></button>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="projectBeginInput" data-i18n="project_edit.label_begin">Begin Date</label>
              <input type="date" name="begin_date" id="projectBeginInput" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="projectEndInput" data-i18n="project_edit.label_end">End Date</label>
              <input type="date" name="end_date" id="projectEndInput" class="form-control">
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label" for="projectStatusSelect" data-i18n="project_edit.label_status">Status</label>
            <select name="status" id="projectStatusSelect" class="form-select">
              <option value="todo" data-i18n="projects.status.todo">Todo</option>
              <option value="ongoing" data-i18n="projects.status.ongoing">Ongoing</option>
              <option value="paused" data-i18n="projects.status.paused">Paused</option>
              <option value="finished" data-i18n="projects.status.finished">Finished</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <div class="me-auto text-danger small" id="projectEditError" role="alert" hidden></div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_edit.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" data-i18n="project_edit.save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="projectMembersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <span data-i18n="project_members.title_prefix">Project Members -</span>
          <span id="projectMemberModalTitle" class="fw-semibold"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="projectMemberAddForm" class="row g-2 align-items-end mb-3">
          <input type="hidden" name="project_id" id="projectMemberProjectId">
          <div class="col-md-5">
            <label class="form-label" for="projectMemberSelect" data-i18n="project_members.label_member">Member</label>
            <select name="member_id" id="projectMemberSelect" class="form-select" required>
              <option value="" data-i18n="project_members.select_member">Select Member</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="projectMemberJoinInput" data-i18n="project_members.label_join">Join Date</label>
            <input type="date" name="join_time" id="projectMemberJoinInput" class="form-control" required>
          </div>
          <div class="col-md-3 text-md-end">
            <button type="submit" class="btn btn-primary w-100" data-i18n="project_members.save">Add</button>
          </div>
        </form>
        <div class="table-responsive mb-4">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th class="text-nowrap"></th>
                <th class="text-nowrap" data-i18n="members.table.campus_id">Campus ID</th>
                <th class="text-nowrap" data-i18n="members.table.name">Name</th>
                <th class="text-nowrap" data-i18n="project_members.join_date">Join Date</th>
                <th class="text-nowrap" data-i18n="members.table.actions">Actions</th>
              </tr>
            </thead>
            <tbody id="projectModalMemberList"></tbody>
          </table>
        </div>
        <h5 class="fw-semibold" data-i18n="project_members.history_title">Member History</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th class="text-nowrap" data-i18n="project_members.history_member">Member</th>
                <th class="text-nowrap" data-i18n="project_members.history_join">Join Date</th>
                <th class="text-nowrap" data-i18n="project_members.history_exit">Exit Date</th>
              </tr>
            </thead>
            <tbody id="projectModalHistoryList"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_edit.cancel">Cancel</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="projectMemberRemoveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="projectMemberRemoveForm">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="project_members.remove_confirm_title">Remove Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2" data-i18n="project_members.remove_confirm">Please confirm the exit date for the member.</p>
          <p id="projectMemberRemoveName" class="fw-semibold"></p>
          <input type="hidden" name="project_id" id="projectRemoveProjectId">
          <input type="hidden" name="log_id" id="projectRemoveLogId">
          <div class="mb-3">
            <label class="form-label" for="projectRemoveExitInput" data-i18n="project_members.label_exit">Exit Date</label>
            <input type="date" name="exit_time" id="projectRemoveExitInput" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_edit.cancel">Cancel</button>
          <button type="submit" class="btn btn-danger" data-i18n="project_members.remove">Remove</button>
        </div>
      </form>
    </div>
  </div>
</div>

<h3 class="mt-4 bold-target" data-i18n="projects.participation_title">项目参与人员情况</h3>
<table class="table table-bordered">
  <tr><th data-i18n="projects.participation.member">成员</th><th data-i18n="projects.participation.projects">参与项目</th></tr>
  <?php
  $memberProjects = $pdo->query("SELECT m.name, m.degree_pursuing, m.year_of_join, GROUP_CONCAT(p.title ORDER BY l.sort_order SEPARATOR ', ') AS proj FROM members m LEFT JOIN project_member_log l ON m.id=l.member_id AND l.exit_time IS NULL LEFT JOIN projects p ON l.project_id=p.id WHERE m.status != 'exited' GROUP BY m.id ORDER BY m.sort_order")->fetchAll();
  foreach($memberProjects as $mp): ?>
  <tr>
    <td><span class="member-name bold-target"><?= htmlspecialchars($mp["name"]); ?></span><span class="member-detail text-muted">(<?= htmlspecialchars($mp["degree_pursuing"]); ?>,<?= htmlspecialchars($mp["year_of_join"]); ?>)</span></td>
    <td class="bold-target"><?= $mp['proj'] ? htmlspecialchars($mp['proj']) : '<span style="color:red"><em data-i18n="directions.none">None</em></span>'; ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const projectListEl = document.getElementById('projectList');
  if (projectListEl && typeof Sortable !== 'undefined') {
    Sortable.create(projectListEl, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function(){
        const order = Array.from(projectListEl.querySelectorAll('tr')).map((row, index) => ({id: row.dataset.id, position: index}));
        fetch('project_order.php', {
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

  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[match]);

  const cssEscape = (value) => {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return CSS.escape(value);
    }
    return String(value).replace(/[^a-zA-Z0-9_\-]/g, '\\$&');
  };

  const addProjectBtn = document.getElementById('addProjectBtn');
  const projectEditModalEl = document.getElementById('projectEditModal');
  if (projectEditModalEl && typeof bootstrap !== 'undefined') {
    const projectEditModal = new bootstrap.Modal(projectEditModalEl);
    const projectEditForm = document.getElementById('projectEditForm');
    const idInput = document.getElementById('projectIdInput');
    const titleInput = document.getElementById('projectTitleInput');
    const descriptionInput = document.getElementById('projectDescriptionInput');
    const bgInput = document.getElementById('projectBgInput');
    const beginInput = document.getElementById('projectBeginInput');
    const endInput = document.getElementById('projectEndInput');
    const statusSelect = document.getElementById('projectStatusSelect');
    const errorBox = document.getElementById('projectEditError');
    const modalTitle = projectEditModalEl.querySelector('.modal-title');

    const setEditMode = (isEdit) => {
      modalTitle.setAttribute('data-i18n', isEdit ? 'project_edit.title_edit' : 'project_edit.title_add');
      modalTitle.textContent = isEdit ? 'Edit Project' : 'Add Project';
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const resetForm = () => {
      projectEditForm?.reset();
      if (bgInput) bgInput.value = '#ffffff';
      if (statusSelect) statusSelect.value = 'todo';
      if (errorBox) {
        errorBox.hidden = true;
        errorBox.textContent = '';
      }
    };

    addProjectBtn?.addEventListener('click', () => {
      resetForm();
      if (idInput) idInput.value = '';
      setEditMode(false);
      projectEditModal.show();
    });

    document.querySelectorAll('.project-edit-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        if (!row) return;
        resetForm();
        if (idInput) idInput.value = row.dataset.id || '';
        if (titleInput) titleInput.value = row.dataset.title || '';
        if (descriptionInput) descriptionInput.value = row.dataset.description || '';
        if (bgInput) bgInput.value = row.dataset.bg || '#ffffff';
        if (beginInput) beginInput.value = row.dataset.begin || '';
        if (endInput) endInput.value = row.dataset.end || '';
        if (statusSelect) statusSelect.value = row.dataset.status || 'todo';
        setEditMode(true);
        projectEditModal.show();
      });
    });

    projectEditForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!projectEditForm) {
        return;
      }
      if (errorBox) {
        errorBox.hidden = true;
        errorBox.textContent = '';
      }
      const beginValue = beginInput?.value;
      const endValue = endInput?.value;
      if (beginValue && endValue && new Date(endValue) <= new Date(beginValue)) {
        const lang = document.documentElement.lang || 'zh';
        const message = translations?.[lang]?.['project_edit.error_range'] || 'End date must be after begin date';
        if (errorBox) {
          errorBox.textContent = message;
          errorBox.hidden = false;
        } else {
          alert(message);
        }
        return;
      }
      const formData = new FormData(projectEditForm);
      fetch('project_edit.php', {
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
            return;
          }
          const lang = document.documentElement.lang || 'zh';
          const key = result.message || 'project_edit.error_generic';
          const fallback = typeof result.message === 'string' ? result.message : 'Save failed';
          const message = translations?.[lang]?.[key] || translations?.[lang]?.['project_edit.error_generic'] || fallback;
          if (errorBox) {
            errorBox.textContent = message;
            errorBox.hidden = false;
          } else {
            alert(message);
          }
        })
        .catch(() => {
          const lang = document.documentElement.lang || 'zh';
          const message = translations?.[lang]?.['project_edit.error_generic'] || 'Save failed';
          if (errorBox) {
            errorBox.textContent = message;
            errorBox.hidden = false;
          } else {
            alert(message);
          }
        });
    });
  }

  const membersModalEl = document.getElementById('projectMembersModal');
  const removeModalEl = document.getElementById('projectMemberRemoveModal');
  if (membersModalEl && removeModalEl && typeof bootstrap !== 'undefined') {
    const membersModal = new bootstrap.Modal(membersModalEl);
    const removeModal = new bootstrap.Modal(removeModalEl);
    const memberList = document.getElementById('projectModalMemberList');
    const historyList = document.getElementById('projectModalHistoryList');
    const memberForm = document.getElementById('projectMemberAddForm');
    const memberSelect = document.getElementById('projectMemberSelect');
    const joinInput = document.getElementById('projectMemberJoinInput');
    const projectIdInput = document.getElementById('projectMemberProjectId');
    const titleEl = document.getElementById('projectMemberModalTitle');
    const removeNameEl = document.getElementById('projectMemberRemoveName');
    const removeProjectIdInput = document.getElementById('projectRemoveProjectId');
    const removeLogIdInput = document.getElementById('projectRemoveLogId');
    const removeExitInput = document.getElementById('projectRemoveExitInput');
    const removeForm = document.getElementById('projectMemberRemoveForm');
    let memberSortable = null;

    const todayString = () => {
      const now = new Date();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      return `${now.getFullYear()}-${month}-${day}`;
    };

    const setupMemberSortable = () => {
      if (!memberList || typeof Sortable === 'undefined') {
        return;
      }
      if (memberSortable) {
        memberSortable.destroy();
        memberSortable = null;
      }
      const rows = memberList.querySelectorAll('tr[data-log-id]');
      if (!rows.length) {
        return;
      }
      memberSortable = Sortable.create(memberList, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: () => {
          const order = Array.from(memberList.querySelectorAll('tr[data-log-id]')).map((row, index) => ({id: row.dataset.logId, position: index}));
          fetch('project_member_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order})
          });
        }
      });
    };

    const renderMemberPlaceholder = () => {
      if (!memberList) return;
      if (memberSortable) {
        memberSortable.destroy();
        memberSortable = null;
      }
      memberList.innerHTML = '';
      const placeholder = document.createElement('tr');
      placeholder.setAttribute('data-placeholder', 'true');
      placeholder.innerHTML = '<td colspan="5"><em data-i18n="directions.none">None</em></td>';
      memberList.appendChild(placeholder);
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const renderHistoryPlaceholder = () => {
      if (!historyList) return;
      historyList.innerHTML = '';
      const placeholder = document.createElement('tr');
      placeholder.setAttribute('data-placeholder', 'true');
      placeholder.innerHTML = '<td colspan="3"><em data-i18n="directions.none">None</em></td>';
      historyList.appendChild(placeholder);
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const addHistoryRow = (entry) => {
      if (!historyList || !entry) return;
      historyList.querySelector('tr[data-placeholder]')?.remove();
      let row = historyList.querySelector(`tr[data-log-id="${cssEscape(String(entry.log_id))}"]`);
      const label = `${entry.name ?? ''}${entry.campus_id ? ` (${entry.campus_id})` : ''}`;
      if (!row) {
        row = document.createElement('tr');
        row.dataset.logId = entry.log_id;
        historyList.appendChild(row);
      }
      row.innerHTML = `
        <td class="text-nowrap">${escapeHtml(label)}</td>
        <td class="text-nowrap">${escapeHtml(entry.join_time ?? '')}</td>
        <td class="text-nowrap">${escapeHtml(entry.exit_time ?? '')}</td>
      `;
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const addMemberRow = (member) => {
      if (!memberList || !member) return;
      memberList.querySelector('tr[data-placeholder]')?.remove();
      const row = document.createElement('tr');
      row.dataset.logId = member.log_id;
      row.dataset.memberId = member.member_id;
      row.innerHTML = `
        <td class="drag-handle text-nowrap">&#9776;</td>
        <td class="text-nowrap">${escapeHtml(member.campus_id ?? '')}</td>
        <td class="text-nowrap">${escapeHtml(member.name ?? '')}</td>
        <td class="text-nowrap">${escapeHtml(member.join_time ?? '')}</td>
        <td class="text-nowrap">
          <button type="button" class="btn btn-sm btn-danger project-member-remove-btn" data-log-id="${escapeHtml(member.log_id)}" data-member-id="${escapeHtml(member.member_id)}" data-member-label="${escapeHtml(`${member.name ?? ''}${member.campus_id ? ` (${member.campus_id})` : ''}`)}" data-i18n="project_members.remove">Remove</button>
        </td>
      `;
      memberList.appendChild(row);
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
      setupMemberSortable();
    };

    const renderMembers = (members) => {
      if (!memberList) return;
      memberList.innerHTML = '';
      if (!members || !members.length) {
        renderMemberPlaceholder();
        return;
      }
      members.forEach(addMemberRow);
    };

    const renderHistory = (entries) => {
      if (!historyList) return;
      historyList.innerHTML = '';
      if (!entries || !entries.length) {
        renderHistoryPlaceholder();
        return;
      }
      entries.forEach(addHistoryRow);
    };

    const renderMemberSelect = (availableMembers, currentMembers) => {
      if (!memberSelect) return;
      const currentIds = new Set((currentMembers || []).map(m => String(m.member_id ?? m.id)));
      memberSelect.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.setAttribute('data-i18n', 'project_members.select_member');
      placeholder.textContent = 'Select Member';
      memberSelect.appendChild(placeholder);
      (availableMembers || []).forEach(member => {
        const id = String(member.id);
        if (currentIds.has(id)) {
          return;
        }
        const option = document.createElement('option');
        option.value = id;
        option.textContent = `${member.name ?? ''}${member.campus_id ? ` (${member.campus_id})` : ''}`;
        memberSelect.appendChild(option);
      });
      memberSelect.value = '';
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
    };

    const openMembersModal = (projectId, projectTitle) => {
      fetch(`project_members.php?format=json&id=${encodeURIComponent(projectId)}`, {
        headers: {'Accept': 'application/json'}
      })
        .then(response => response.json())
        .then(data => {
          if (data.status !== 'ok') {
            const lang = document.documentElement.lang || 'zh';
            const key = data.message || 'project_members.invalid_request';
            const message = translations?.[lang]?.[key] || translations?.[lang]?.['project_members.invalid_request'] || 'Operation failed';
            alert(message);
            return;
          }
          if (projectIdInput) projectIdInput.value = projectId;
          if (titleEl) titleEl.textContent = projectTitle || data.project?.title || '';
          renderMembers(data.members || []);
          renderHistory(data.history || []);
          renderMemberSelect(data.available_members || [], data.members || []);
          if (joinInput) joinInput.value = todayString();
          if (typeof applyTranslations === 'function') {
            applyTranslations();
          }
          membersModal.show();
        })
        .catch(() => {
          const lang = document.documentElement.lang || 'zh';
          const message = translations?.[lang]?.['project_members.invalid_request'] || 'Operation failed';
          alert(message);
        });
    };

    document.querySelectorAll('.project-members-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        if (!row) return;
        const projectId = row.dataset.id;
        if (!projectId) return;
        const projectTitle = btn.getAttribute('data-project-title') || row.dataset.title || '';
        openMembersModal(projectId, projectTitle);
      });
    });

    memberForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!memberForm) return;
      if (!memberSelect?.value) {
        return;
      }
      const formData = new FormData(memberForm);
      fetch('project_member_add.php', {
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
            if (data.history_entry) {
              addHistoryRow(data.history_entry);
            }
            const option = Array.from(memberSelect.options).find(opt => opt.value === String(data.member.member_id));
            option?.remove();
            memberSelect.value = '';
            if (joinInput) joinInput.value = todayString();
            if (typeof applyTranslations === 'function') {
              applyTranslations();
            }
            return;
          }
          const lang = document.documentElement.lang || 'zh';
          const key = data.message || 'project_members.invalid_request';
          const message = translations?.[lang]?.[key] || translations?.[lang]?.['project_members.invalid_request'] || 'Operation failed';
          alert(message);
        })
        .catch(() => {
          const lang = document.documentElement.lang || 'zh';
          const message = translations?.[lang]?.['project_members.invalid_request'] || 'Operation failed';
          alert(message);
        });
    });

    memberList?.addEventListener('click', (event) => {
      const btn = event.target.closest('.project-member-remove-btn');
      if (!btn) return;
      const logId = btn.dataset.logId;
      const memberId = btn.dataset.memberId;
      const memberLabel = btn.dataset.memberLabel || '';
      if (!logId) return;
      if (removeProjectIdInput) removeProjectIdInput.value = projectIdInput?.value || '';
      if (removeLogIdInput) removeLogIdInput.value = logId;
      if (removeExitInput) removeExitInput.value = todayString();
      if (removeNameEl) removeNameEl.textContent = memberLabel;
      removeForm?.setAttribute('data-member-id', memberId || '');
      removeForm?.setAttribute('data-member-label', memberLabel);
      if (typeof applyTranslations === 'function') {
        applyTranslations();
      }
      removeModal.show();
    });

    removeForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!removeForm) return;
      const formData = new FormData(removeForm);
      fetch('project_member_remove.php', {
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
            const logId = String(data.log_id);
            const memberId = removeForm.getAttribute('data-member-id');
            const memberLabel = removeForm.getAttribute('data-member-label') || '';
            const row = memberList?.querySelector(`tr[data-log-id="${cssEscape(logId)}"]`);
            const campusId = row?.querySelector('td:nth-child(2)')?.textContent?.trim() || '';
            const joinDate = row?.querySelector('td:nth-child(4)')?.textContent?.trim() || '';
            if (row) {
              row.remove();
            }
            if (!memberList?.querySelector('tr[data-log-id]')) {
              renderMemberPlaceholder();
            } else {
              setupMemberSortable();
            }
            if (memberId) {
              const exists = Array.from(memberSelect?.options || []).some(opt => opt.value === memberId);
              if (!exists && memberSelect) {
                const option = document.createElement('option');
                option.value = memberId;
                option.textContent = memberLabel || `${row?.querySelector('td:nth-child(3)')?.textContent?.trim() || ''}${campusId ? ` (${campusId})` : ''}`;
                memberSelect.appendChild(option);
              }
              memberSelect.value = '';
            }
            addHistoryRow({
              log_id: logId,
              name: memberLabel.replace(/\s*\([^)]*\)$/, '') || (row?.querySelector('td:nth-child(3)')?.textContent?.trim() || ''),
              campus_id: campusId,
              join_time: joinDate,
              exit_time: data.exit_time || todayString()
            });
            if (typeof applyTranslations === 'function') {
              applyTranslations();
            }
            removeModal.hide();
            return;
          }
          const lang = document.documentElement.lang || 'zh';
          const key = data.message || 'project_members.invalid_request';
          const message = translations?.[lang]?.[key] || translations?.[lang]?.['project_members.invalid_request'] || 'Operation failed';
          alert(message);
        })
        .catch(() => {
          const lang = document.documentElement.lang || 'zh';
          const message = translations?.[lang]?.['project_members.invalid_request'] || 'Operation failed';
          alert(message);
        });
    });

    membersModalEl.addEventListener('hidden.bs.modal', () => {
      memberList?.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => el.removeAttribute('title'));
    });
  }
});
</script>
<?php include 'footer.php'; ?>
