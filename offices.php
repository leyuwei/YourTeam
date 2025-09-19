<?php
include 'header.php';

$officeStmt = $pdo->query("SELECT o.*,
    COALESCE(SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END), 0) AS seat_count,
    COALESCE(SUM(CASE WHEN s.id IS NOT NULL AND s.member_id IS NULL THEN 1 ELSE 0 END), 0) AS available_count
  FROM offices o
  LEFT JOIN office_seats s ON o.id = s.office_id
  GROUP BY o.id
  ORDER BY o.sort_order, o.name");
$offices = $officeStmt->fetchAll();
$canManageOffices = ($_SESSION['role'] ?? '') === 'manager';
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
$memberStmt = $pdo->query("SELECT id, name, year_of_join, degree_pursuing FROM members WHERE status != 'exited' ORDER BY sort_order, name");
$members = $memberStmt->fetchAll();
$memberSeatStmt = $pdo->query("SELECT s.member_id, o.name AS office_name, o.region, o.location_description, s.label
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
    $seatLabel = trim($seat['label'] ?? '');
    if ($seatLabel === '') {
        continue;
    }
    $labelParts = [];
    $region = trim($seat['region'] ?? '');
    if ($region !== '') {
        $labelParts[] = $region;
    }
    $location = trim($seat['location_description'] ?? '');
    if ($location !== '') {
        $labelParts[] = $location;
    }
    $labelParts[] = $seatLabel;
    $memberSeatAssignments[$memberId][$officeName][] = implode('-', $labelParts);
}

$totalSeats = 0;
$totalAvailableSeats = 0;
$membersWithoutSeat = 0;
foreach ($offices as $office) {
    $totalSeats += (int)($office['seat_count'] ?? 0);
    $totalAvailableSeats += (int)($office['available_count'] ?? 0);
}
$activeMemberCountStmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'in_work'");
$activeMemberCount = (int)($activeMemberCountStmt->fetchColumn() ?: 0);
foreach ($members as $member) {
    $memberId = (int)($member['id'] ?? 0);
    if (!$memberId) {
        continue;
    }
    if (empty($memberSeatAssignments[$memberId])) {
        $membersWithoutSeat++;
    }
}
?>
<style>
  .office-summary-title { white-space: nowrap; letter-spacing: .08em; }
  .office-summary-heading {
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #495057;
    margin-bottom: 0;
  }
  .badge-slim { display: inline-flex; align-items: center; justify-content: center; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 1rem; font-weight: 600; min-width: 3rem; }
  .badge-seat-count { background-color: rgba(255, 193, 7, 0.15); color: #7a5a00; border: 1px solid rgba(255, 193, 7, 0.35); }
  .badge-available { background-color: rgba(25, 135, 84, 0.15); color: #146c43; border: 1px solid rgba(25, 135, 84, 0.35); }
  .badge-available-zero { background-color: rgba(220, 53, 69, 0.15); color: #842029; border: 1px solid rgba(220, 53, 69, 0.35); }
  .badge-missing { background-color: rgba(13, 110, 253, 0.12); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.3); }
  .member-distribution-header[data-sort] { cursor: pointer; user-select: none; white-space: nowrap; }
  .member-distribution-header.sorting-asc::after { content: '\2191'; font-size: 0.75rem; margin-left: 0.25rem; }
  .member-distribution-header.sorting-desc::after { content: '\2193'; font-size: 0.75rem; margin-left: 0.25rem; }
  .seat-occupant-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; }
  .seat-occupant-card {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.12), rgba(13, 110, 253, 0.25));
    border: 1px solid rgba(13, 110, 253, 0.2);
    color: #0b3d91;
    min-height: 1.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .seat-occupant-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.35rem 0.65rem rgba(13, 110, 253, 0.18);
  }
  .seat-occupant-seat {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.1rem 0.4rem;
    border-radius: 0.45rem;
    background-color: #0d6efd;
    color: #fff;
    font-weight: 600;
    font-size: 0.85rem;
    min-width: 2.3rem;
    line-height: 1.05;
    letter-spacing: 0.03em;
  }
  .seat-occupant-name {
    font-weight: 600;
    line-height: 1.2;
    font-size: 0.85rem;
    white-space: nowrap;
  }
  .seat-occupant-name.text-muted {
    font-weight: 500;
  }
  .office-table th {
    white-space: nowrap;
  }
  .office-table td {
    vertical-align: middle;
  }
  .office-table td:not(.office-members-col) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .office-table .office-name-col,
  .office-table .office-actions-col {
    white-space: nowrap;
  }
  .office-table .office-info-col {
    max-width: 240px;
  }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="bold-target" data-i18n="offices.title">Offices</h2>
  <?php if($_SESSION['role'] === 'manager'): ?>
    <a class="btn btn-success" href="office_edit.php" data-i18n="offices.add">Add Office</a>
  <?php endif; ?>
</div>
<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <h5 class="office-summary-heading" data-i18n="offices.summary.title">Seat Overview</h5>
    <div class="d-flex flex-column flex-md-row gap-4 mt-2">
      <div class="d-flex flex-column">
        <span class="text-muted office-summary-title" data-i18n="offices.summary.total_seats">Total Seats</span>
        <span class="badge-slim badge-seat-count align-self-start"><?= $totalSeats; ?></span>
      </div>
      <div class="d-flex flex-column">
        <span class="text-muted office-summary-title" data-i18n="offices.summary.available_seats">Unassigned Seats</span>
        <span class="badge-slim <?= $totalAvailableSeats > 0 ? 'badge-available' : 'badge-available-zero'; ?> align-self-start"><?= $totalAvailableSeats; ?></span>
      </div>
      <div class="d-flex flex-column">
        <span class="text-muted office-summary-title" data-i18n="offices.summary.active_members">Active Members</span>
        <span class="badge-slim badge-missing align-self-start"><?= $activeMemberCount; ?></span>
      </div>
      <div class="d-flex flex-column">
        <span class="text-muted office-summary-title" data-i18n="offices.summary.unassigned_members">Members Without Seats</span>
        <span class="badge-slim badge-available-zero align-self-start"><?= $membersWithoutSeat; ?></span>
      </div>
    </div>
  </div>
</div>
<div class="table-responsive">
  <table class="table table-bordered align-middle office-table">
    <thead class="table-light">
      <tr>
        <?php if($canManageOffices): ?>
        <th style="width: 2.5rem;"></th>
        <?php endif; ?>
        <th data-i18n="offices.table.name">Office Name</th>
        <th data-i18n="offices.table.location">Location Description</th>
        <th data-i18n="offices.table.region">Region</th>
        <th class="text-center" data-i18n="offices.table.seats">Seat Count</th>
        <th class="text-center" data-i18n="offices.table.available">Remaining Seats</th>
        <th data-i18n="offices.table.members">Members in Office</th>
        <th class="text-center" data-i18n="directions.table_actions">Actions</th>
      </tr>
    </thead>
    <tbody id="officeList">
      <?php foreach($offices as $office):
        $seatCount = (int)($office['seat_count'] ?? 0);
        $availableCount = (int)($office['available_count'] ?? 0);
        $assignments = $seatAssignments[$office['id']] ?? [];
      ?>
      <tr data-id="<?= (int)$office['id']; ?>">
        <?php if($canManageOffices): ?>
        <td class="drag-handle text-center">&#9776;</td>
        <?php endif; ?>
        <td class="fw-bold office-name-col">
          <a href="office_view.php?id=<?= (int)$office['id']; ?>" class="text-decoration-none">
            <?= htmlspecialchars($office['name']); ?>
          </a>
        </td>
        <td class="office-info-col"><?= htmlspecialchars($office['location_description'] ?? ''); ?></td>
        <td class="office-info-col"><?= htmlspecialchars($office['region'] ?? ''); ?></td>
        <td class="text-center office-info-col">
          <span class="badge-slim badge-seat-count"><?= $seatCount; ?></span>
        </td>
        <td class="text-center office-info-col">
          <span class="badge-slim <?= $availableCount > 0 ? 'badge-available' : 'badge-available-zero'; ?>"><?= $availableCount; ?></span>
        </td>
        <td class="office-members-col">
          <?php if($assignments): ?>
            <div class="seat-occupant-grid">
              <?php foreach($assignments as $assignment):
                $seatLabel = trim($assignment['label'] ?? '');
                $memberName = trim($assignment['name'] ?? '');
              ?>
                <div class="seat-occupant-card">
                  <span class="seat-occupant-seat"><?= htmlspecialchars($seatLabel); ?></span>
                  <?php if($memberName !== ''): ?>
                    <span class="seat-occupant-name"><?= htmlspecialchars($memberName); ?></span>
                  <?php else: ?>
                    <span class="seat-occupant-name text-muted">-</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <span class="text-muted" data-i18n="offices.none">None</span>
          <?php endif; ?>
        </td>
        <td class="text-center office-actions-col">
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
<?php if($canManageOffices): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const officeList = document.getElementById('officeList');
  if(!officeList){
    return;
  }
  Sortable.create(officeList, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(){
      const order = Array.from(officeList.querySelectorAll('tr[data-id]')).map((row, index) => ({
        id: row.dataset.id,
        position: index
      }));
      fetch('office_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });
});
</script>
<?php endif; ?>
<div class="card mt-4">
  <div class="card-header" data-i18n="offices.members_overview.title">Member Office Assignments</div>
  <div class="card-body p-0">
    <div class="table-responsive mb-0">
      <table class="table table-bordered table-sm mb-0 align-middle" id="memberDistributionTable">
        <thead class="table-light">
          <tr>
            <th class="member-distribution-header" data-sort="name" data-i18n="offices.members_overview.member">Member</th>
            <th class="member-distribution-header text-center" data-sort="year" data-i18n="offices.members_overview.year_of_join">Year of Join</th>
            <th class="member-distribution-header" data-sort="degree" data-i18n="offices.members_overview.degree">Degree Pursuing</th>
            <th data-i18n="offices.members_overview.offices">Office &amp; Seats</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($members as $member):
            $memberId = (int)$member['id'];
            $assignments = $memberSeatAssignments[$memberId] ?? [];
            $nameValue = trim((string)($member['name'] ?? ''));
            $yearValue = trim((string)($member['year_of_join'] ?? ''));
            $degreeValue = trim((string)($member['degree_pursuing'] ?? ''));
          ?>
          <tr data-name="<?= htmlspecialchars($nameValue, ENT_QUOTES); ?>" data-year="<?= htmlspecialchars($yearValue, ENT_QUOTES); ?>" data-degree="<?= htmlspecialchars($degreeValue, ENT_QUOTES); ?>">
            <td class="fw-semibold text-nowrap"><?= htmlspecialchars($nameValue); ?></td>
            <td class="text-center text-nowrap">
              <?php if($yearValue !== ''): ?>
                <?= htmlspecialchars($yearValue); ?>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="text-nowrap">
              <?php if($degreeValue !== ''): ?>
                <?= htmlspecialchars($degreeValue); ?>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
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
<script>
document.addEventListener('DOMContentLoaded', function(){
  const distributionTable = document.getElementById('memberDistributionTable');
  if(!distributionTable){
    return;
  }
  const tbody = distributionTable.querySelector('tbody');
  const headers = distributionTable.querySelectorAll('.member-distribution-header[data-sort]');
  let currentSortKey = null;
  let currentSortDir = 'asc';

  headers.forEach((header) => {
    header.addEventListener('click', () => {
      const key = header.dataset.sort;
      if(currentSortKey === key){
        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
      } else {
        currentSortKey = key;
        currentSortDir = 'asc';
      }
      headers.forEach(h => h.classList.remove('sorting-asc', 'sorting-desc'));
      header.classList.add(currentSortDir === 'asc' ? 'sorting-asc' : 'sorting-desc');

      const rows = Array.from(tbody.querySelectorAll('tr'));
      const isNumeric = (value) => value !== '' && !Number.isNaN(Number(value));
      rows.sort((a, b) => {
        const aVal = (a.dataset[key] || '').trim();
        const bVal = (b.dataset[key] || '').trim();
        if(isNumeric(aVal) && isNumeric(bVal)){
          return currentSortDir === 'asc' ? Number(aVal) - Number(bVal) : Number(bVal) - Number(aVal);
        }
        return currentSortDir === 'asc'
          ? aVal.localeCompare(bVal, undefined, {numeric: true, sensitivity: 'base'})
          : bVal.localeCompare(aVal, undefined, {numeric: true, sensitivity: 'base'});
      });
      rows.forEach(row => tbody.appendChild(row));
    });
  });
});
</script>
<?php include 'footer.php'; ?>
