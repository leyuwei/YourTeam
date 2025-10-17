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
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3 align-items-center">
  <h2 class="bold-target mb-0" data-i18n="projects.title">Projects</h2>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-success" id="addProjectBtn" data-i18n="projects.add">Add Project</button>
    <button type="button" class="btn btn-outline-secondary" id="importProjectsBtn" data-i18n="projects.import">Import XLSX</button>
    <a class="btn btn-secondary" href="projects_export.php" id="exportProjects" data-i18n="projects.export">Export XLSX</a>
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
<table class="table table-bordered">
  <thead>
  <tr>
    <th></th>
    <th data-i18n="projects.table_title">Title</th>
    <th data-i18n="projects.table_members">Members</th>
    <th data-i18n="projects.table_begin">Begin</th>
    <th data-i18n="projects.table_end">End</th>
    <th data-i18n="projects.table_status">Status</th>
    <th data-i18n="projects.table_actions">Actions</th>
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
  <tr data-id="<?= $p['id']; ?>"<?= $rowColor ? ' data-custom-bg="'.$rowColor.'" style="background-color:'.$rowColor.';"' : ''; ?>>
    <td class="drag-handle">&#9776;</td>
    <td class="bold-target"><?= htmlspecialchars($p['title']); ?></td>
    <td>
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
    <td><?= htmlspecialchars($p['begin_date']); ?></td>
    <td><?= htmlspecialchars($p['end_date']); ?></td>
    <td data-i18n="projects.status.<?= htmlspecialchars($p['status']); ?>"><?= htmlspecialchars($p['status']); ?></td>
    <td>
      <button type="button" class="btn btn-sm btn-primary btn-edit-project" data-id="<?= $p['id']; ?>" data-i18n="projects.action_edit">Edit</button>
      <a class="btn btn-sm btn-warning" href="project_members.php?id=<?= $p['id']; ?>" data-i18n="projects.action_members">Members</a>
      <a class="btn btn-sm btn-danger" href="project_delete.php?id=<?= $p['id']; ?>" onclick="return doubleConfirm('Delete project?');" data-i18n="projects.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
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
        <h5 class="modal-title" id="projectModalTitle" data-i18n="project_edit.title_add">Add Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="projectFormError"></div>
        <form id="projectForm">
          <input type="hidden" name="id" id="projectIdField">
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_title">Project Title</label>
            <input type="text" name="title" class="form-control" id="projectTitle" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_description">Project Description</label>
            <textarea name="description" class="form-control" rows="3" id="projectDescription"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="project_edit.label_bg">Background Color</label>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <input type="color" name="bg_color" class="form-control form-control-color flex-shrink-0" id="projectBgColor" value="#ffffff">
              <div class="d-flex gap-1 flex-wrap" id="projectColorSuggestions">
                <?php $suggestedColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
                foreach ($suggestedColors as $color): ?>
                  <button type="button" class="btn btn-sm border" data-color="<?= $color; ?>" style="background-color: <?= $color; ?>"></button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" data-i18n="project_edit.label_begin">Begin Date</label>
              <input type="date" name="begin_date" class="form-control" id="projectBegin">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="project_edit.label_end">End Date</label>
              <input type="date" name="end_date" class="form-control" id="projectEnd">
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label" data-i18n="project_edit.label_status">Status</label>
            <select name="status" class="form-select" id="projectStatus">
              <option value="todo" data-i18n="projects.status.todo">Todo</option>
              <option value="ongoing" data-i18n="projects.status.ongoing">Ongoing</option>
              <option value="paused" data-i18n="projects.status.paused">Paused</option>
              <option value="finished" data-i18n="projects.status.finished">Finished</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_edit.cancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveProjectBtn" data-i18n="project_edit.save">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="projectImportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="projects.import_title">Import Projects</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="projectImportError"></div>
        <div class="alert alert-success d-none" id="projectImportSuccess"></div>
        <form id="projectImportForm" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label" data-i18n="projects.import_hint">Upload XLSX file with project data.</label>
            <input type="file" name="file" accept=".xlsx" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="project_edit.cancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitProjectImport" data-i18n="projects.import_submit">Import</button>
      </div>
    </div>
  </div>
</div>
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
  const projectModalEl = document.getElementById('projectModal');
  const projectModal = projectModalEl ? new bootstrap.Modal(projectModalEl) : null;
  const projectForm = document.getElementById('projectForm');
  const projectFormError = document.getElementById('projectFormError');
  const projectModalTitle = document.getElementById('projectModalTitle');
  const projectIdField = document.getElementById('projectIdField');
  const projectTitle = document.getElementById('projectTitle');
  const projectDescription = document.getElementById('projectDescription');
  const projectBgColor = document.getElementById('projectBgColor');
  const projectBegin = document.getElementById('projectBegin');
  const projectEnd = document.getElementById('projectEnd');
  const projectStatus = document.getElementById('projectStatus');
  const saveProjectBtn = document.getElementById('saveProjectBtn');
  const colorButtons = document.querySelectorAll('#projectColorSuggestions button[data-color]');

  function setModalTitle(key) {
    if (projectModalTitle) {
      projectModalTitle.setAttribute('data-i18n', key);
      window.applyTranslations?.();
    }
  }

  function showProjectError(messageKey, fallback) {
    if (!projectFormError) return;
    projectFormError.textContent = t(messageKey, fallback);
    projectFormError.classList.remove('d-none');
  }

  function resetProjectError() {
    if (!projectFormError) return;
    projectFormError.classList.add('d-none');
    projectFormError.textContent = '';
  }

  function fillProjectForm(data) {
    projectIdField.value = data.id || '';
    projectTitle.value = data.title || '';
    projectDescription.value = data.description || '';
    projectBgColor.value = data.bg_color || '#ffffff';
    projectBegin.value = data.begin_date || '';
    projectEnd.value = data.end_date || '';
    projectStatus.value = data.status || 'todo';
  }

  function openProjectModal(mode, id = null) {
    if (!projectModal || !projectForm) return;
    projectForm.reset();
    resetProjectError();
    if (mode === 'add') {
      setModalTitle('project_edit.title_add');
      fillProjectForm({});
      projectModal.show();
      return;
    }
    setModalTitle('project_edit.title_edit');
    fetch('project_edit.php?id=' + encodeURIComponent(id))
      .then(res => res.json())
      .then(data => {
        if (data?.success) {
          fillProjectForm(data.project || {});
          projectModal.show();
        } else {
          alert(t('project_edit.load_failed', 'Unable to load project.'));
        }
      })
      .catch(() => {
        alert(t('project_edit.load_failed', 'Unable to load project.'));
      });
  }

  document.getElementById('addProjectBtn')?.addEventListener('click', () => openProjectModal('add'));
  document.querySelectorAll('.btn-edit-project').forEach(btn => {
    btn.addEventListener('click', () => openProjectModal('edit', btn.dataset.id));
  });

  colorButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (projectBgColor) {
        projectBgColor.value = btn.dataset.color;
      }
    });
  });

  saveProjectBtn?.addEventListener('click', () => {
    if (!projectForm || !projectForm.reportValidity()) return;
    resetProjectError();
    const formData = new FormData(projectForm);
    fetch('project_edit.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (!data?.success) {
          const code = data?.code;
          if (code === 'date_range') {
            showProjectError('project_edit.error_range', data?.message || 'Invalid date range');
          } else if (code === 'title_required') {
            showProjectError('project_edit.error_title', data?.message || 'Title required');
          } else {
            showProjectError('project_edit.error_generic', data?.message || 'Failed to save project');
          }
          return;
        }
        window.location.reload();
      })
      .catch(() => {
        showProjectError('project_edit.error_generic', 'Failed to save project');
      });
  });

  const importModalEl = document.getElementById('projectImportModal');
  const importModal = importModalEl ? new bootstrap.Modal(importModalEl) : null;
  const importBtn = document.getElementById('importProjectsBtn');
  const importForm = document.getElementById('projectImportForm');
  const importError = document.getElementById('projectImportError');
  const importSuccess = document.getElementById('projectImportSuccess');
  const importSubmit = document.getElementById('submitProjectImport');

  function resetImportMessages() {
    if (importError) {
      importError.classList.add('d-none');
      importError.textContent = '';
    }
    if (importSuccess) {
      importSuccess.classList.add('d-none');
      importSuccess.textContent = '';
    }
  }

  importBtn?.addEventListener('click', () => {
    if (!importModal || !importForm) return;
    importForm.reset();
    resetImportMessages();
    importModal.show();
  });

  importSubmit?.addEventListener('click', () => {
    if (!importForm || !importForm.reportValidity()) return;
    resetImportMessages();
    const formData = new FormData(importForm);
    fetch('projects_import.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (!data?.success) {
          if (importError) {
            importError.textContent = data?.message || t('projects.import_error', 'Failed to import.');
            importError.classList.remove('d-none');
          }
          return;
        }
        if (importSuccess) {
          const template = t('projects.import_success', 'Import completed. Added {inserted}, Updated {updated}.');
          importSuccess.textContent = template
            .replace('{inserted}', data.inserted ?? 0)
            .replace('{updated}', data.updated ?? 0);
          importSuccess.classList.remove('d-none');
        }
        setTimeout(() => window.location.reload(), 1000);
      })
      .catch(() => {
        if (importError) {
          importError.textContent = t('projects.import_error', 'Failed to import.');
          importError.classList.remove('d-none');
        }
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
