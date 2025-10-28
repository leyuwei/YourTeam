<?php
include_once 'auth.php';

$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (strpos($acceptHeader, 'application/json') !== false)
);

$project_id = $_GET['id'] ?? ($_POST['project_id'] ?? null);
if (!$project_id) {
    if ($isAjax) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'error' => 'missing_project'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    header('Location: projects.php');
    exit();
}

$projectStmt = $pdo->prepare('SELECT * FROM projects WHERE id=?');
$projectStmt->execute([$project_id]);
$project = $projectStmt->fetch();
if (!$project) {
    if ($isAjax) {
        header('Content-Type: application/json', true, 404);
        echo json_encode(['status' => 'error', 'error' => 'project_not_found'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    header('Location: projects.php');
    exit();
}

$activeStmt = $pdo->prepare('SELECT l.id, m.campus_id, m.name, l.join_time FROM project_member_log l JOIN members m ON l.member_id=m.id WHERE l.project_id=? AND l.exit_time IS NULL ORDER BY l.sort_order');
$activeStmt->execute([$project_id]);
$active_members = $activeStmt->fetchAll();

$logsStmt = $pdo->prepare('SELECT l.*, m.name, m.campus_id FROM project_member_log l JOIN members m ON l.member_id=m.id WHERE l.project_id=? ORDER BY l.join_time');
$logsStmt->execute([$project_id]);
$logs = $logsStmt->fetchAll();

$members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited' ORDER BY name")->fetchAll();

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'project' => [
            'id' => (int)$project['id'],
            'title' => $project['title']
        ],
        'active_members' => array_map(static function ($row) {
            return [
                'id' => (int)$row['id'],
                'campus_id' => $row['campus_id'],
                'name' => $row['name'],
                'join_time' => $row['join_time'],
            ];
        }, $active_members),
        'history' => array_map(static function ($row) {
            return [
                'name' => $row['name'],
                'campus_id' => $row['campus_id'],
                'join_time' => $row['join_time'],
                'exit_time' => $row['exit_time'],
            ];
        }, $logs),
        'members' => array_map(static function ($row) {
            return [
                'id' => (int)$row['id'],
                'campus_id' => $row['campus_id'],
                'name' => $row['name'],
            ];
        }, $members),
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

include 'header.php';
?>
<h2><span data-i18n="project_members.title_prefix">Project Members -</span> <?php echo htmlspecialchars($project['title']); ?></h2>
<h4 data-i18n="project_members.current_members">Current Members</h4>
<table class="table table-bordered">
<tr><th></th><th data-i18n="members.table.campus_id">Campus ID</th><th data-i18n="members.table.name">Name</th><th data-i18n="project_members.join_date">Join Date</th><th data-i18n="members.table.actions">Actions</th></tr>
<tbody id="memberList">
<?php foreach($active_members as $a): ?>
<tr data-id="<?= $a['id']; ?>">
  <td class="drag-handle">&#9776;</td>
  <td><?= htmlspecialchars($a['campus_id']); ?></td>
  <td><?= htmlspecialchars($a['name']); ?></td>
  <td><?= htmlspecialchars($a['join_time']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="project_member_remove.php?log_id=<?= $a['id']; ?>&project_id=<?= $project_id; ?>" onclick="return doubleConfirm('Remove member from project?');" data-i18n="project_members.remove">Remove</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<br><br>
<h4 data-i18n="project_members.add_member">Add Member</h4>
<form method="post" action="project_member_add.php">
  <input type="hidden" name="project_id" value="<?= $project_id; ?>">
  <div class="mb-3">
    <label class="form-label" data-i18n="project_members.label_member">Member</label>
    <select name="member_id" class="form-select" required>
      <option value="" data-i18n="project_members.select_member">Select Member</option>
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="project_members.label_join">Join Date</label>
    <input type="date" name="join_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="project_members.save">Add</button>
  <a href="projects.php" class="btn btn-secondary" data-i18n="project_members.back">Back</a>
</form>
<br>
<h4 class="mt-5" data-i18n="project_members.history_title">Member History</h4>
<table class="table table-bordered">
<tr><th data-i18n="project_members.history_member">Member</th><th data-i18n="project_members.history_join">Join Date</th><th data-i18n="project_members.history_exit">Exit Date</th></tr>
<?php foreach($logs as $l): ?>
<tr>
  <td><?= htmlspecialchars($l['name']); ?> (<?= htmlspecialchars($l['campus_id']); ?>)</td>
  <td><?= htmlspecialchars($l['join_time']); ?></td>
  <td><?= htmlspecialchars($l['exit_time']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Sortable.create(document.getElementById('memberList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function() {
      const order = Array.from(document.querySelectorAll('#memberList tr')).map((row, index) => ({id: row.dataset.id, position:index}));
      fetch('project_member_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });
});
</script>
<?php include 'footer.php'; ?>
