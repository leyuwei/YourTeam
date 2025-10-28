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
<style>
  .project-table-wrapper {
    overflow-x: auto;
  }
  .project-table {
    table-layout: auto;
    width: 100%;
  }
  .project-table th.fixed-column,
  .project-table td.fixed-column {
    white-space: nowrap;
  }
  .project-table td.members-column,
  .project-table th.members-column {
    white-space: normal;
  }
  .project-table td.members-column {
    min-width: 220px;
  }
  .project-table .drag-handle {
    cursor: move;
    white-space: nowrap;
  }
  .project-member-item:not(:last-child) {
    margin-bottom: 0.25rem;
  }
</style>
<div class="table-responsive project-table-wrapper">
<table class="table table-bordered project-table">
  <thead>
  <tr>
    <th class="fixed-column"></th>
    <th class="fixed-column" data-i18n="projects.table_title">Title</th>
    <th class="members-column" data-i18n="projects.table_members">Members</th>
    <th class="fixed-column" data-i18n="projects.table_begin">Begin</th>
    <th class="fixed-column" data-i18n="projects.table_end">End</th>
    <th class="fixed-column" data-i18n="projects.table_status">Status</th>
    <th class="fixed-column" data-i18n="projects.table_actions">Actions</th>
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
    $projectPayload = [
        'id' => (int)$p['id'],
        'title' => $p['title'] ?? '',
        'description' => $p['description'] ?? '',
        'bg_color' => $p['bg_color'] ?? '#ffffff',
        'begin_date' => $p['begin_date'] ?? '',
        'end_date' => $p['end_date'] ?? '',
        'status' => $p['status'] ?? 'todo'
    ];
    $projectAttr = htmlspecialchars(json_encode($projectPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  ?>
  <tr data-id="<?= $p['id']; ?>" data-project="<?= $projectAttr; ?>"<?= $rowColor ? ' data-custom-bg="'.$rowColor.'" style="background-color:'.$rowColor.';"' : ''; ?>>
    <td class="drag-handle fixed-column">&#9776;</td>
    <td class="bold-target fixed-column"><?= htmlspecialchars($p['title']); ?></td>
    <td class="members-column">
      <?php if($memberList): ?>
        <?php foreach($memberList as $m): ?>
          <div class="project-member-item">
            <span class="member-name bold-target" data-bs-toggle="tooltip" title="<?= htmlspecialchars($memberDirections[$m['id']] ?? '') ?>"<?php if(empty($memberDirections[$m['id']])) echo ' data-i18n-title="projects.no_direction"'; ?>>
              <?= htmlspecialchars($m['name']); ?>
              <span class="member-detail text-muted">(<?= htmlspecialchars($m['degree']); ?>,<?= htmlspecialchars($m['year']); ?>)</span>
            </span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <em data-i18n="directions.none">None</em>
      <?php endif; ?>
    </td>
    <td class="fixed-column"><?= htmlspecialchars($p['begin_date']); ?></td>
    <td class="fixed-column"><?= htmlspecialchars($p['end_date']); ?></td>
    <td class="fixed-column" data-i18n="projects.status.<?= htmlspecialchars($p['status']); ?>"><?= htmlspecialchars($p['status']); ?></td>
    <td class="fixed-column">
      <button type="button" class="btn btn-sm btn-primary project-edit-btn" data-i18n="projects.action_edit">Edit</button>
      <button type="button" class="btn btn-sm btn-warning project-members-btn" data-project-id="<?= $p['id']; ?>" data-i18n="projects.action_members">Members</button>
      <a class="btn btn-sm btn-danger" href="project_delete.php?id=<?= $p['id']; ?>" onclick="return doubleConfirm('Delete project?');" data-i18n="projects.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
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

<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="projectFormError" role="alert"></div>
        <form id="projectForm">
          <input type="hidden" name="id">
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_title">Project Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_description">Project Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_bg">Background Color</label>
            <input type="color" name="bg_color" class="form-control form-control-color" value="#ffffff">
            <div class="mt-2" id="projectColorSuggestions">
              <?php
              $suggestedColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
              foreach ($suggestedColors as $color) {
                  echo "<button type=\"button\" class=\"btn btn-sm border me-1 project-color-swatch\" data-color=\"$color\" style=\"background-color:$color;\" title=\"$color\"></button>";
              }
              ?>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_begin">Begin Date</label>
            <input type="date" name="begin_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_end">End Date</label>
            <input type="date" name="end_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_status">Status</label>
            <select name="status" class="form-select">
              <?php
              $statuses = [
                'todo'    => ['key'=>'projects.status.todo',    'text'=>'Todo'],
                'ongoing' => ['key'=>'projects.status.ongoing', 'text'=>'Ongoing'],
                'paused'  => ['key'=>'projects.status.paused',  'text'=>'Paused'],
                'finished'=> ['key'=>'projects.status.finished', 'text'=>'Finished']
              ];
              foreach($statuses as $key=>$info){
                  echo "<option value='$key' data-i18n='{$info['key']}'>{$info['text']}</option>";
              }
              ?>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_edit.cancel">Cancel</button>
        <button type="submit" form="projectForm" class="btn btn-primary" data-i18n="project_edit.save">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="projectMembersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectMembersModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="projectMembersError" role="alert"></div>
        <p class="text-muted" id="memberReorderHint" data-i18n="project_members.reorder_hint"></p>
        <h6 data-i18n="project_members.current_members">Current Members</h6>
        <div class="table-responsive">
          <table class="table table-bordered mb-3">
            <thead>
              <tr>
                <th style="width:40px;"></th>
                <th data-i18n="members.table.campus_id">Campus ID</th>
                <th data-i18n="members.table.name">Name</th>
                <th data-i18n="project_members.join_date">Join Date</th>
                <th data-i18n="members.table.actions">Actions</th>
              </tr>
            </thead>
            <tbody id="projectMembersActive"></tbody>
          </table>
        </div>
        <h6 data-i18n="project_members.add_member">Add Member</h6>
        <form id="projectMemberAddForm" class="row g-3 align-items-end mb-4">
          <input type="hidden" name="project_id">
          <div class="col-md-6">
            <label class="form-label" data-i18n="project_members.label_member">Member</label>
            <select name="member_id" class="form-select" required></select>
          </div>
          <div class="col-md-4">
            <label class="form-label" data-i18n="project_members.label_join">Join Date</label>
            <input type="date" name="join_time" class="form-control" required>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100" data-i18n="project_members.save">Add</button>
          </div>
        </form>
        <h6 data-i18n="project_members.history_title">Member History</h6>
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead>
              <tr>
                <th data-i18n="project_members.history_member">Member</th>
                <th data-i18n="project_members.history_join">Join Date</th>
                <th data-i18n="project_members.history_exit">Exit Date</th>
              </tr>
            </thead>
            <tbody id="projectMembersHistory"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="projectMemberRemoveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="projectMemberRemoveForm">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="project_members.remove_title">Remove Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="projectMemberRemoveText" class="mb-3"></p>
        <div class="mb-3">
          <label class="form-label" data-i18n="project_members.remove_date">Exit Date</label>
          <input type="date" name="exit_time" class="form-control" required>
        </div>
        <div class="alert alert-danger d-none" id="projectMemberRemoveError" role="alert"></div>
        <input type="hidden" name="log_id">
        <input type="hidden" name="project_id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_members.remove_cancel">Cancel</button>
        <button type="submit" class="btn btn-danger" data-i18n="project_members.remove_confirm">Remove</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const langGetter = () => document.documentElement.lang || 'zh';
  const projectModalEl = document.getElementById('projectModal');
  const projectModal = projectModalEl ? new bootstrap.Modal(projectModalEl) : null;
  const projectForm = document.getElementById('projectForm');
  const projectFormError = document.getElementById('projectFormError');
  const projectModalLabel = document.getElementById('projectModalLabel');
  const projectColorInput = projectForm?.querySelector('input[name="bg_color"]');
  projectModalEl?.querySelectorAll('.project-color-swatch').forEach(btn => {
    btn.addEventListener('click', () => {
      if (projectColorInput) {
        projectColorInput.value = btn.getAttribute('data-color');
      }
    });
  });

  function showProjectError(key) {
    if (!projectFormError) return;
    const lang = langGetter();
    const message = translations[lang][key] || key;
    projectFormError.textContent = message;
    projectFormError.classList.remove('d-none');
  }

  function setProjectModal(mode, data) {
    if (!projectModalLabel || !projectForm) return;
    const lang = langGetter();
    const titleKey = mode === 'edit' ? 'project_edit.title_edit' : 'project_edit.title_add';
    projectModalLabel.textContent = translations[lang][titleKey] || '';
    projectFormError?.classList.add('d-none');
    projectForm.reset();
    if (projectColorInput) {
      projectColorInput.value = '#ffffff';
    }
    projectForm.querySelector('input[name="id"]').value = data?.id || '';
    projectForm.querySelector('input[name="title"]').value = data?.title || '';
    projectForm.querySelector('textarea[name="description"]').value = data?.description || '';
    if (projectColorInput) {
      projectColorInput.value = data?.bg_color || '#ffffff';
    }
    projectForm.querySelector('input[name="begin_date"]').value = data?.begin_date || '';
    projectForm.querySelector('input[name="end_date"]').value = data?.end_date || '';
    projectForm.querySelector('select[name="status"]').value = data?.status || 'todo';
  }

  document.getElementById('addProjectBtn')?.addEventListener('click', () => {
    setProjectModal('add', null);
    projectModal?.show();
  });

  document.querySelectorAll('.project-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      let data = null;
      if (row && row.dataset.project) {
        try {
          data = JSON.parse(row.dataset.project);
        } catch (err) {
          console.error('Invalid project payload', err);
        }
      }
      setProjectModal('edit', data);
      projectModal?.show();
    });
  });

  projectForm?.addEventListener('submit', function(e){
    e.preventDefault();
    projectFormError?.classList.add('d-none');
    const begin = projectForm.querySelector('input[name="begin_date"]').value;
    const end = projectForm.querySelector('input[name="end_date"]').value;
    if(begin && end && new Date(end) <= new Date(begin)){
      showProjectError('project_edit.error_range');
      return;
    }
    const formData = new FormData(projectForm);
    fetch('project_edit.php', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: formData
    }).then(resp => resp.json())
      .then(data => {
        if(data.status === 'ok'){
          projectModal?.hide();
          window.location.reload();
        } else {
          showProjectError(data.error_key || 'project_edit.error_generic');
        }
      })
      .catch(() => {
        showProjectError('project_edit.error_generic');
      });
  });

  const memberModalEl = document.getElementById('projectMembersModal');
  const memberModal = memberModalEl ? new bootstrap.Modal(memberModalEl) : null;
  const memberError = document.getElementById('projectMembersError');
  const memberTitle = document.getElementById('projectMembersModalLabel');
  const memberListBody = document.getElementById('projectMembersActive');
  const memberHistoryBody = document.getElementById('projectMembersHistory');
  const memberAddForm = document.getElementById('projectMemberAddForm');
  const memberSelect = memberAddForm?.querySelector('select[name="member_id"]');
  const memberJoinInput = memberAddForm?.querySelector('input[name="join_time"]');
  const removeModalEl = document.getElementById('projectMemberRemoveModal');
  const removeModal = removeModalEl ? new bootstrap.Modal(removeModalEl) : null;
  const removeForm = document.getElementById('projectMemberRemoveForm');
  const removeError = document.getElementById('projectMemberRemoveError');
  const removeText = document.getElementById('projectMemberRemoveText');
  let currentProjectId = null;
  let memberSortable = null;

  function showMemberError(key) {
    if (!memberError) return;
    const message = translations[langGetter()][key] || key;
    memberError.textContent = message;
    memberError.classList.remove('d-none');
  }

  function clearMemberError() {
    memberError?.classList.add('d-none');
  }

  function attachRemoveHandlers() {
    if (!memberListBody) return;
    memberListBody.querySelectorAll('.project-member-remove').forEach(btn => {
      btn.addEventListener('click', () => {
        if (!removeForm || !removeModal) return;
        const logId = btn.getAttribute('data-log-id');
        const memberName = btn.getAttribute('data-member-name') || '';
        removeForm.querySelector('input[name="log_id"]').value = logId || '';
        removeForm.querySelector('input[name="project_id"]').value = currentProjectId || '';
        removeForm.querySelector('input[name="exit_time"]').value = '';
        const template = translations[langGetter()]['project_members.remove_instruction'] || '{name}';
        removeText.textContent = template.replace('{name}', memberName);
        removeError?.classList.add('d-none');
        removeModal.show();
      });
    });
  }

  function renderActiveMembers(activeMembers) {
    if (!memberListBody) return;
    memberListBody.innerHTML = '';
    if (memberSortable) {
      memberSortable.destroy();
      memberSortable = null;
    }
    const lang = langGetter();
    if (!activeMembers.length) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 5;
      cell.className = 'text-center text-muted';
      cell.textContent = translations[lang]['project_members.current_empty'] || '';
      row.appendChild(cell);
      memberListBody.appendChild(row);
      return;
    }
    activeMembers.forEach(member => {
      const row = document.createElement('tr');
      row.dataset.id = member.id;
      const handleCell = document.createElement('td');
      handleCell.className = 'drag-handle';
      handleCell.innerHTML = '&#9776;';
      const campusCell = document.createElement('td');
      campusCell.textContent = member.campus_id || '';
      const nameCell = document.createElement('td');
      nameCell.textContent = member.name || '';
      const joinCell = document.createElement('td');
      joinCell.textContent = member.join_time || '';
      const actionCell = document.createElement('td');
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn btn-sm btn-danger project-member-remove';
      removeBtn.setAttribute('data-log-id', member.id);
      removeBtn.setAttribute('data-member-name', member.name || '');
      removeBtn.textContent = translations[lang]['project_members.remove'];
      actionCell.appendChild(removeBtn);
      row.append(handleCell, campusCell, nameCell, joinCell, actionCell);
      memberListBody.appendChild(row);
    });
    memberSortable = Sortable.create(memberListBody, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function(){
        const order = Array.from(memberListBody.querySelectorAll('tr')).map((row, index) => ({id: row.dataset.id, position: index}));
        fetch('project_member_order.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({order: order})
        }).then(resp => resp.json())
          .then(data => {
            if (data.status !== 'ok') {
              showMemberError('project_members.error_reorder');
            }
          })
          .catch(() => showMemberError('project_members.error_reorder'));
      }
    });
    attachRemoveHandlers();
  }

  function renderHistory(history) {
    if (!memberHistoryBody) return;
    memberHistoryBody.innerHTML = '';
    const lang = langGetter();
    if (!history.length) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 3;
      cell.className = 'text-center text-muted';
      cell.textContent = translations[lang]['project_members.history_empty'] || '';
      row.appendChild(cell);
      memberHistoryBody.appendChild(row);
      return;
    }
    history.forEach(item => {
      const row = document.createElement('tr');
      const nameCell = document.createElement('td');
      nameCell.textContent = `${item.name || ''} ${item.campus_id ? '(' + item.campus_id + ')' : ''}`.trim();
      const joinCell = document.createElement('td');
      joinCell.textContent = item.join_time || '';
      const exitCell = document.createElement('td');
      exitCell.textContent = item.exit_time || '';
      row.append(nameCell, joinCell, exitCell);
      memberHistoryBody.appendChild(row);
    });
  }

  function renderMemberModal(data) {
    if (!memberModal) return;
    const lang = langGetter();
    const template = translations[lang]['project_members.modal_title'] || `${translations[lang]['project_members.title_prefix'] || ''} {title}`;
    memberTitle.textContent = template.replace('{title}', data.project.title || '');
    clearMemberError();
    currentProjectId = data.project.id;
    if (memberSelect) {
      memberSelect.innerHTML = '';
      const placeholderOption = document.createElement('option');
      placeholderOption.value = '';
      placeholderOption.textContent = translations[lang]['project_members.select_member'] || '';
      memberSelect.appendChild(placeholderOption);
      data.members.forEach(member => {
        const option = document.createElement('option');
        option.value = member.id;
        option.textContent = `${member.name || ''} ${member.campus_id ? '(' + member.campus_id + ')' : ''}`.trim();
        memberSelect.appendChild(option);
      });
    }
    if (memberJoinInput) {
      memberJoinInput.value = '';
    }
    memberAddForm?.querySelector('input[name="project_id"]').value = data.project.id;
    renderActiveMembers(data.active_members || []);
    renderHistory(data.history || []);
  }

  function resetMemberModal() {
    if (memberTitle) {
      memberTitle.textContent = '';
    }
    if (memberListBody) {
      memberListBody.innerHTML = '';
    }
    if (memberHistoryBody) {
      memberHistoryBody.innerHTML = '';
    }
    if (memberSelect) {
      memberSelect.innerHTML = '';
    }
    if (memberJoinInput) {
      memberJoinInput.value = '';
    }
    currentProjectId = null;
  }

  function loadMembers(projectId, showModal = false) {
    if (!projectId) return;
    clearMemberError();
    if (showModal) {
      resetMemberModal();
      memberModal?.show();
    }
    fetch(`project_members.php?id=${projectId}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    }).then(resp => resp.json())
      .then(data => {
        if (data.status === 'ok') {
          renderMemberModal(data);
          if (showModal && memberModalEl && !memberModalEl.classList.contains('show')) {
            memberModal?.show();
          }
        } else {
          showMemberError('project_members.error_load');
          if (showModal && memberModalEl && !memberModalEl.classList.contains('show')) {
            memberModal?.show();
          }
        }
      })
      .catch(() => {
        showMemberError('project_members.error_load');
        if (showModal && memberModalEl && !memberModalEl.classList.contains('show')) {
          memberModal?.show();
        }
      });
  }

  document.querySelectorAll('.project-members-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const projectId = btn.getAttribute('data-project-id');
      loadMembers(projectId, true);
    });
  });

  memberAddForm?.addEventListener('submit', function(e){
    e.preventDefault();
    clearMemberError();
    const formData = new FormData(memberAddForm);
    fetch('project_member_add.php', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: formData
    }).then(resp => resp.json())
      .then(data => {
        if (data.status === 'ok') {
          if (memberSelect) memberSelect.value = '';
          if (memberJoinInput) memberJoinInput.value = '';
          loadMembers(currentProjectId);
        } else {
          showMemberError(data.error_key || 'project_members.error_add');
        }
      })
      .catch(() => showMemberError('project_members.error_add'));
  });

  removeForm?.addEventListener('submit', function(e){
    e.preventDefault();
    removeError?.classList.add('d-none');
    const formData = new FormData(removeForm);
    fetch('project_member_remove.php', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: formData
    }).then(resp => resp.json())
      .then(data => {
        if (data.status === 'ok') {
          removeModal?.hide();
          loadMembers(currentProjectId);
        } else {
          const key = data.error_key || 'project_members.error_remove';
          const message = translations[langGetter()][key] || key;
          removeError.textContent = message;
          removeError.classList.remove('d-none');
        }
      })
      .catch(() => {
        const message = translations[langGetter()]['project_members.error_remove'] || '';
        removeError.textContent = message;
        removeError.classList.remove('d-none');
      });
  });

  Sortable.create(document.getElementById('projectList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(){
      const order = Array.from(document.querySelectorAll('#projectList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
      fetch('project_order.php', {
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

  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
});
</script>
<?php include 'footer.php'; ?>
