<?php
include 'header.php';

$officeStmt = $pdo->query("SELECT o.*, COUNT(s.id) AS seat_count, SUM(CASE WHEN s.member_id IS NULL THEN 1 ELSE 0 END) AS available_count
  FROM offices o
  LEFT JOIN office_seats s ON o.id = s.office_id
  GROUP BY o.id
  ORDER BY o.sort_order, o.name");
$offices = $officeStmt->fetchAll();
$seatAssignments = [];
if ($offices) {
    $ids = array_column($offices, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $assignStmt = $pdo->prepare("SELECT s.office_id, s.label, s.member_id, m.name
      FROM office_seats s
      LEFT JOIN members m ON s.member_id = m.id
      WHERE s.office_id IN ($placeholders) AND s.member_id IS NOT NULL
      ORDER BY s.office_id, s.label");
    $assignStmt->execute($ids);
    while ($row = $assignStmt->fetch()) {
        $seatAssignments[$row['office_id']][] = $row;
    }
}
$memberStmt = $pdo->query("SELECT id, name FROM members WHERE status != 'exited' ORDER BY sort_order, name");
$members = $memberStmt->fetchAll();
$memberSeatStmt = $pdo->query("SELECT s.member_id, o.name AS office_name, s.label
  FROM office_seats s
  INNER JOIN offices o ON s.office_id = o.id
  WHERE s.member_id IS NOT NULL
  ORDER BY o.sort_order, o.name, s.label");
$memberSeatAssignments = [];
foreach ($memberSeatStmt as $seat) {
    $memberId = (int)($seat['member_id'] ?? 0);
    if (!$memberId) {
        continue;
    }
    $officeName = $seat['office_name'] ?? '';
    if (!isset($memberSeatAssignments[$memberId][$officeName])) {
        $memberSeatAssignments[$memberId][$officeName] = [];
    }
    $label = $seat['label'] ?? '';
    if ($label !== '') {
        $memberSeatAssignments[$memberId][$officeName][] = $label;
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="bold-target" data-i18n="offices.title">Offices</h2>
  <?php if($_SESSION['role'] === 'manager'): ?>
    <a class="btn btn-success" href="office_edit.php" data-i18n="offices.add">Add Office</a>
  <?php endif; ?>
</div>
<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th data-i18n="offices.table.name">Office Name</th>
        <th data-i18n="offices.table.location">Location Description</th>
        <th data-i18n="offices.table.region">Region</th>
        <th class="text-center" data-i18n="offices.table.seats">Seat Count</th>
        <th class="text-center" data-i18n="offices.table.available">Remaining Seats</th>
        <th data-i18n="offices.table.members">Members in Office</th>
        <th class="text-center" data-i18n="directions.table_actions">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($offices as $office):
        $seatCount = (int)($office['seat_count'] ?? 0);
        $availableCount = (int)($office['available_count'] ?? 0);
        $assignments = $seatAssignments[$office['id']] ?? [];
      ?>
      <tr>
        <td class="fw-bold">
          <a href="office_view.php?id=<?= (int)$office['id']; ?>" class="text-decoration-none">
            <?= htmlspecialchars($office['name']); ?>
          </a>
        </td>
        <td><?= htmlspecialchars($office['location_description'] ?? ''); ?></td>
        <td><?= htmlspecialchars($office['region'] ?? ''); ?></td>
        <td class="text-center fw-semibold"><?= $seatCount; ?></td>
        <td class="text-center">
          <span class="badge <?= $availableCount > 0 ? 'bg-success' : 'bg-danger'; ?> fs-6 px-3 py-2">
            <?= $availableCount; ?>
          </span>
        </td>
        <td>
          <?php if($assignments): ?>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach($assignments as $assignment): ?>
                <span class="badge bg-primary text-wrap">
                  <?= htmlspecialchars($assignment['label']); ?>
                  <?php if(!empty($assignment['name'])): ?>
                    - <?= htmlspecialchars($assignment['name']); ?>
                  <?php endif; ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <span class="text-muted" data-i18n="offices.none">None</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <a class="btn btn-sm btn-info mb-1" href="office_view.php?id=<?= (int)$office['id']; ?>" data-i18n="offices.action.view">View Layout</a>
          <?php if($_SESSION['role'] === 'manager'): ?>
            <a class="btn btn-sm btn-primary mb-1" href="office_edit.php?id=<?= (int)$office['id']; ?>" data-i18n="offices.action.edit">Edit</a>
            <a class="btn btn-sm btn-danger mb-1" href="office_delete.php?id=<?= (int)$office['id']; ?>" onclick="return doubleConfirm('Delete office? / 确认删除办公地点？');" data-i18n="offices.action.delete">Delete</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($offices)): ?>
      <tr>
        <td colspan="7" class="text-center text-muted" data-i18n="offices.none">None</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<div class="card mt-4">
  <div class="card-header" data-i18n="offices.members_overview.title">Member Office Assignments</div>
  <div class="card-body p-0">
    <div class="table-responsive mb-0">
      <table class="table table-bordered table-sm mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th data-i18n="offices.members_overview.member">Member</th>
            <th data-i18n="offices.members_overview.offices">Office &amp; Seats</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($members as $member):
            $memberId = (int)$member['id'];
            $assignments = $memberSeatAssignments[$memberId] ?? [];
          ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($member['name']); ?></td>
            <td>
              <?php if($assignments): ?>
                <ul class="list-unstyled mb-0">
                  <?php foreach($assignments as $officeName => $seats): ?>
                    <li>
                      <span class="fw-semibold"><?= htmlspecialchars($officeName); ?></span>
                      <?php if(!empty($seats)): ?>
                        <span class="text-muted">
                          (
                          <?php foreach($seats as $index => $seatLabel): ?>
                            <?php if($index > 0): ?>, <?php endif; ?>
                            <?= htmlspecialchars($seatLabel); ?>
                          <?php endforeach; ?>
                          )
                        </span>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <span class="text-muted" data-i18n="offices.members_overview.none">None</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($members)): ?>
          <tr>
            <td colspan="2" class="text-center text-muted" data-i18n="offices.none">None</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>
