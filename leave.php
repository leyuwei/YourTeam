<?php
include 'auth.php';

$is_manager = ($_SESSION['role'] ?? '') === 'manager';
$member_id = (int)($_SESSION['member_id'] ?? 0);
$manager_id = (int)($_SESSION['manager_id'] ?? 0);
$today = date('Y-m-d');
$this_year = (int)date('Y');
$year_start = $this_year . '-01-01';
$year_end = $this_year . '-12-31';

$leave_types = ['personal', 'sick', 'winter_summer', 'internship', 'group_activity', 'other', 'statutory_holiday'];

$leave_table_ready = false;
$leave_bootstrap_error_key = '';
$leave_bootstrap_error_fallback = '';
try {
    $table_check_stmt = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    $leave_table_ready = (bool)$table_check_stmt->fetchColumn();
} catch (Throwable $e) {
    $leave_table_ready = false;
}
if (!$leave_table_ready) {
    $leave_bootstrap_error_key = 'leave.message.db_not_ready';
    $leave_bootstrap_error_fallback = 'Leave data table is missing. Please run update_db.sql first.';
} else {
    try {
        $required_columns = [
            'id',
            'member_id',
            'leave_type',
            'start_date',
            'end_date',
            'reason',
            'status',
            'reject_reason',
            'approved_by',
            'approved_at',
            'rejected_by',
            'rejected_at',
            'actual_end_date',
            'returned_at',
            'created_at',
            'updated_at'
        ];
        $column_stmt = $pdo->query("SHOW COLUMNS FROM leave_requests");
        $existing_columns = [];
        foreach ($column_stmt->fetchAll() as $column_row) {
            $field = trim((string)($column_row['Field'] ?? ''));
            if ($field !== '') {
                $existing_columns[] = $field;
            }
        }
        $missing_columns = array_values(array_diff($required_columns, $existing_columns));
        if (!empty($missing_columns)) {
            $leave_bootstrap_error_key = 'leave.message.db_not_ready';
            $leave_bootstrap_error_fallback = 'Leave data table is outdated. Please run update_db.sql first.';
        }
    } catch (Throwable $e) {
        $leave_bootstrap_error_key = 'leave.message.db_not_ready';
        $leave_bootstrap_error_fallback = 'Failed to validate leave data table. Please run update_db.sql first.';
    }
}

if ($leave_bootstrap_error_key !== '') {
    include 'header.php';
    echo '<div class="alert alert-danger" data-i18n="' . htmlspecialchars($leave_bootstrap_error_key, ENT_QUOTES) . '">' . htmlspecialchars($leave_bootstrap_error_fallback) . '</div>';
    include 'footer.php';
    exit();
}

function leave_is_valid_date($date)
{
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $timestamp = strtotime($date);
    return $timestamp !== false && date('Y-m-d', $timestamp) === $date;
}

function leave_overlap_days($start_date, $end_date, $range_start, $range_end)
{
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    $range_start_ts = strtotime($range_start);
    $range_end_ts = strtotime($range_end);
    if ($start === false || $end === false || $range_start_ts === false || $range_end_ts === false) {
        return 0;
    }
    $effective_start = max($start, $range_start_ts);
    $effective_end = min($end, $range_end_ts);
    if ($effective_start > $effective_end) {
        return 0;
    }
    return (int)(($effective_end - $effective_start) / 86400) + 1;
}

function leave_single_line_text($text)
{
    $value = trim((string)$text);
    if ($value === '') {
        return '';
    }
    $value = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return is_string($value) ? trim($value) : '';
}

function leave_redirect_with_flash($flash_key, $flash_type = 'success')
{
    $flash_key = trim((string)$flash_key);
    if ($flash_key === '') {
        header('Location: leave.php');
        exit();
    }
    $type = ($flash_type === 'danger') ? 'danger' : 'success';
    header('Location: leave.php?flash=' . urlencode($flash_key) . '&type=' . urlencode($type));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_leave') {
            if ($is_manager || $member_id <= 0) {
                leave_redirect_with_flash('leave.message.no_permission', 'danger');
            }
            $leave_type = trim((string)($_POST['leave_type'] ?? ''));
            $start_date = trim((string)($_POST['start_date'] ?? ''));
            $end_date = trim((string)($_POST['end_date'] ?? ''));
            $reason = trim((string)($_POST['reason'] ?? ''));

            if (!in_array($leave_type, $leave_types, true)) {
                leave_redirect_with_flash('leave.message.invalid_type', 'danger');
            }
            if (!leave_is_valid_date($start_date) || !leave_is_valid_date($end_date)) {
                leave_redirect_with_flash('leave.message.invalid_date_format', 'danger');
            }
            if ($start_date > $end_date) {
                leave_redirect_with_flash('leave.message.invalid_date', 'danger');
            }
            if ($reason === '') {
                leave_redirect_with_flash('leave.message.reason_required', 'danger');
            }

            $stmt = $pdo->prepare('INSERT INTO leave_requests (member_id, leave_type, start_date, end_date, reason, status) VALUES (?,?,?,?,?,\'pending\')');
            $stmt->execute([$member_id, $leave_type, $start_date, $end_date, $reason]);
            leave_redirect_with_flash('leave.message.create_success');
        }

        if ($action === 'approve_leave') {
            if (!$is_manager || $manager_id <= 0) {
                leave_redirect_with_flash('leave.message.no_permission', 'danger');
            }
            $request_id = (int)($_POST['request_id'] ?? 0);
            if ($request_id <= 0) {
                leave_redirect_with_flash('leave.message.request_id_required', 'danger');
            }

            $stmt = $pdo->prepare('UPDATE leave_requests SET status=\'approved\', approved_by=?, approved_at=NOW(), reject_reason=NULL, rejected_by=NULL, rejected_at=NULL, actual_end_date=NULL, returned_at=NULL WHERE id=? AND status=\'pending\'');
            $stmt->execute([$manager_id, $request_id]);
            if ($stmt->rowCount() <= 0) {
                leave_redirect_with_flash('leave.message.not_found_or_status', 'danger');
            }
            leave_redirect_with_flash('leave.message.approve_success');
        }

        if ($action === 'reject_leave') {
            if (!$is_manager || $manager_id <= 0) {
                leave_redirect_with_flash('leave.message.no_permission', 'danger');
            }
            $request_id = (int)($_POST['request_id'] ?? 0);
            $reject_reason = trim((string)($_POST['reject_reason'] ?? ''));
            if ($request_id <= 0) {
                leave_redirect_with_flash('leave.message.request_id_required', 'danger');
            }
            if ($reject_reason === '') {
                leave_redirect_with_flash('leave.message.reject_reason_required', 'danger');
            }

            $stmt = $pdo->prepare('UPDATE leave_requests SET status=\'rejected\', reject_reason=?, rejected_by=?, rejected_at=NOW(), approved_by=NULL, approved_at=NULL, actual_end_date=NULL, returned_at=NULL WHERE id=? AND status=\'pending\'');
            $stmt->execute([$reject_reason, $manager_id, $request_id]);
            if ($stmt->rowCount() <= 0) {
                leave_redirect_with_flash('leave.message.not_found_or_status', 'danger');
            }
            leave_redirect_with_flash('leave.message.reject_success');
        }

        if ($action === 'return_leave_early') {
            if ($is_manager || $member_id <= 0) {
                leave_redirect_with_flash('leave.message.no_permission', 'danger');
            }
            $request_id = (int)($_POST['request_id'] ?? 0);
            $actual_end_date = trim((string)($_POST['actual_end_date'] ?? ''));

            if ($request_id <= 0) {
                leave_redirect_with_flash('leave.message.request_id_required', 'danger');
            }
            if (!leave_is_valid_date($actual_end_date)) {
                leave_redirect_with_flash('leave.message.invalid_date_format', 'danger');
            }

            $stmt = $pdo->prepare('SELECT id, start_date, end_date, status FROM leave_requests WHERE id=? AND member_id=?');
            $stmt->execute([$request_id, $member_id]);
            $request_row = $stmt->fetch();
            if (!$request_row || ($request_row['status'] ?? '') !== 'approved') {
                leave_redirect_with_flash('leave.message.not_found_or_status', 'danger');
            }
            if (!($today >= $request_row['start_date'] && $today <= $request_row['end_date'])) {
                leave_redirect_with_flash('leave.message.not_in_leave_period', 'danger');
            }

            $max_allowed_date = ($today < $request_row['end_date']) ? $today : $request_row['end_date'];
            if ($actual_end_date < $request_row['start_date'] || $actual_end_date > $max_allowed_date) {
                leave_redirect_with_flash('leave.message.return_date_range', 'danger');
            }

            $update_stmt = $pdo->prepare('UPDATE leave_requests SET status=\'returned\', actual_end_date=?, returned_at=NOW() WHERE id=? AND member_id=? AND status=\'approved\'');
            $update_stmt->execute([$actual_end_date, $request_id, $member_id]);
            if ($update_stmt->rowCount() <= 0) {
                leave_redirect_with_flash('leave.message.not_found_or_status', 'danger');
            }
            leave_redirect_with_flash('leave.message.return_success');
        }
    } catch (Throwable $e) {
        leave_redirect_with_flash('leave.message.system_error', 'danger');
    }
}

$allowed_flash_keys = [
    'leave.message.create_success',
    'leave.message.approve_success',
    'leave.message.reject_success',
    'leave.message.return_success',
    'leave.message.invalid_date',
    'leave.message.invalid_type',
    'leave.message.reason_required',
    'leave.message.reject_reason_required',
    'leave.message.no_permission',
    'leave.message.not_found_or_status',
    'leave.message.return_date_range',
    'leave.message.not_in_leave_period',
    'leave.message.request_id_required',
    'leave.message.invalid_date_format',
    'leave.message.system_error'
];
$flash_key = trim((string)($_GET['flash'] ?? ''));
if (!in_array($flash_key, $allowed_flash_keys, true)) {
    $flash_key = '';
}
$flash_type = (($_GET['type'] ?? 'success') === 'danger') ? 'danger' : 'success';
$flash_fallbacks = [
    'leave.message.create_success' => 'Leave request submitted.',
    'leave.message.approve_success' => 'Leave request approved.',
    'leave.message.reject_success' => 'Leave request rejected.',
    'leave.message.return_success' => 'Leave ended early successfully.',
    'leave.message.invalid_date' => 'Start date must be on or before end date.',
    'leave.message.invalid_type' => 'Please choose a valid leave type.',
    'leave.message.reason_required' => 'Please provide a reason.',
    'leave.message.reject_reason_required' => 'Please provide a rejection reason.',
    'leave.message.no_permission' => 'You do not have permission to perform this action.',
    'leave.message.not_found_or_status' => 'Request not found or status is no longer valid.',
    'leave.message.return_date_range' => 'Actual return date must be between start date and today.',
    'leave.message.not_in_leave_period' => 'You can only end leave early while currently on leave.',
    'leave.message.request_id_required' => 'Invalid request id.',
    'leave.message.invalid_date_format' => 'Please provide a valid date.',
    'leave.message.system_error' => 'System error. Please run update_db.sql and try again.'
];
$flash_fallback = $flash_fallbacks[$flash_key] ?? '';

$current_leaves = [];
$pending_leaves = [];
$approved_or_returned_leaves = [];
$rejected_leaves = [];
$leaderboard_rows = [];
$member_applied_days = 0;
$member_approved_days = 0;
$page_load_error_key = '';
$page_load_error_fallback = '';

try {
    if ($is_manager) {
        $current_stmt = $pdo->query("SELECT lr.*, m.name, m.department, m.degree_pursuing, m.year_of_join
          FROM leave_requests lr
          JOIN members m ON lr.member_id = m.id
          WHERE lr.status = 'approved' AND CURDATE() BETWEEN lr.start_date AND lr.end_date
          ORDER BY lr.end_date ASC, m.name ASC");
        $current_leaves = $current_stmt->fetchAll();

        $pending_stmt = $pdo->query("SELECT lr.*, m.name, m.department, m.degree_pursuing, m.year_of_join
          FROM leave_requests lr
          JOIN members m ON lr.member_id = m.id
          WHERE lr.status = 'pending'
          ORDER BY lr.start_date ASC, lr.created_at ASC");
        $pending_leaves = $pending_stmt->fetchAll();

        $rejected_stmt = $pdo->query("SELECT lr.*, m.name, m.department, m.degree_pursuing, m.year_of_join, mgr.username AS rejected_by_name
          FROM leave_requests lr
          JOIN members m ON lr.member_id = m.id
          LEFT JOIN managers mgr ON lr.rejected_by = mgr.id
          WHERE lr.status = 'rejected'
          ORDER BY lr.rejected_at DESC, lr.created_at DESC");
        $rejected_leaves = $rejected_stmt->fetchAll();

        $leaderboard_stmt = $pdo->prepare("SELECT
            m.id,
            m.campus_id,
            m.name,
            m.department,
            m.degree_pursuing,
            m.year_of_join,
            COALESCE(SUM(
              CASE
                WHEN lr.id IS NULL THEN 0
                WHEN GREATEST(lr.start_date, ?) <= LEAST(
                  CASE WHEN lr.status = 'returned' THEN COALESCE(lr.actual_end_date, lr.end_date) ELSE lr.end_date END,
                  ?
                )
                THEN DATEDIFF(
                  LEAST(
                    CASE WHEN lr.status = 'returned' THEN COALESCE(lr.actual_end_date, lr.end_date) ELSE lr.end_date END,
                    ?
                  ),
                  GREATEST(lr.start_date, ?)
                ) + 1
                ELSE 0
              END
            ), 0) AS approved_days
          FROM members m
          LEFT JOIN leave_requests lr
            ON lr.member_id = m.id
            AND lr.status IN ('approved', 'returned')
          WHERE m.status = 'in_work'
          GROUP BY m.id, m.campus_id, m.name, m.department, m.degree_pursuing, m.year_of_join
          ORDER BY approved_days DESC, m.name ASC");
        $leaderboard_stmt->execute([$year_start, $year_end, $year_end, $year_start]);
        $leaderboard_rows = $leaderboard_stmt->fetchAll();
    } else {
        $all_stmt = $pdo->prepare("SELECT lr.*, am.username AS approved_by_name, rm.username AS rejected_by_name
          FROM leave_requests lr
          LEFT JOIN managers am ON lr.approved_by = am.id
          LEFT JOIN managers rm ON lr.rejected_by = rm.id
          WHERE lr.member_id = ?
          ORDER BY lr.created_at DESC");
        $all_stmt->execute([$member_id]);
        $member_leaves = $all_stmt->fetchAll();

        foreach ($member_leaves as $row) {
            $status = $row['status'] ?? '';
            if ($status === 'rejected') {
                $rejected_leaves[] = $row;
                continue;
            }
            $member_applied_days += leave_overlap_days($row['start_date'], $row['end_date'], $year_start, $year_end);
            if ($status === 'pending') {
                $pending_leaves[] = $row;
                continue;
            }
            if (in_array($status, ['approved', 'returned'], true)) {
                $approved_end_date = $row['end_date'];
                if ($status === 'returned' && !empty($row['actual_end_date'])) {
                    $approved_end_date = $row['actual_end_date'];
                }
                $member_approved_days += leave_overlap_days($row['start_date'], $approved_end_date, $year_start, $year_end);
                $approved_or_returned_leaves[] = $row;
            }
        }
    }
} catch (Throwable $e) {
    $page_load_error_key = 'leave.message.load_failed';
    $page_load_error_fallback = 'Unable to load leave data. Please run update_db.sql and refresh.';
}

include 'header.php';
if ($page_load_error_key !== '') {
?>
<div class="alert alert-danger mb-4" data-i18n="<?= htmlspecialchars($page_load_error_key); ?>">
  <?= htmlspecialchars($page_load_error_fallback); ?>
</div>
<?php
    include 'footer.php';
    exit();
}
?>
<style>
  .leave-summary-card {
    border: 1px solid var(--app-table-border);
    border-radius: 0.75rem;
    background: var(--app-surface-bg);
    padding: 1rem 1.1rem;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
  }
  .leave-summary-value {
    font-size: 1.9rem;
    line-height: 1.1;
    font-weight: 700;
  }
  .leave-summary-unit {
    color: var(--app-muted-text);
    font-size: 0.9rem;
  }
  .leave-member-meta {
    font-size: 0.84rem;
    color: var(--app-muted-text);
  }
  .leave-reason {
    max-width: 380px;
    white-space: pre-wrap;
    word-break: break-word;
  }
  .leave-rejected-reason {
    color: #b02a37;
    font-weight: 600;
  }
  .leave-rejected-line {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    max-width: 420px;
    min-width: 0;
  }
  .leave-rejected-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .leave-rejected-line .leave-member-meta {
    margin: 0;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .leave-table-actions {
    white-space: nowrap;
  }
  .leave-table thead th {
    white-space: nowrap;
  }
  .leave-table tbody td {
    vertical-align: middle;
  }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 data-i18n="leave.title">Leave</h2>
  <?php if($is_manager): ?>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leaveLeaderboardModal" data-i18n="leave.ranking.open">Leave Ranking</button>
  <?php else: ?>
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#leaveRequestModal" data-i18n="leave.apply">Apply Leave</button>
  <?php endif; ?>
</div>

<?php if($flash_key !== ''): ?>
<div class="alert alert-<?= htmlspecialchars($flash_type); ?> mb-4" data-i18n="<?= htmlspecialchars($flash_key); ?>">
  <?= htmlspecialchars($flash_fallback); ?>
</div>
<?php endif; ?>

<?php if($is_manager): ?>
<div class="mb-4">
  <button class="btn btn-outline-secondary leave-toggle-btn mb-3" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#leaveCurrentCollapse"
          aria-expanded="true"
          aria-controls="leaveCurrentCollapse"
          data-show-key="leave.toggle.show_current"
          data-hide-key="leave.toggle.hide_current"
          data-i18n="leave.toggle.hide_current">Hide members currently on leave</button>
  <div class="collapse show" id="leaveCurrentCollapse">
    <h4 data-i18n="leave.current.title">Currently On Leave</h4>
    <?php if(empty($current_leaves)): ?>
    <div class="alert alert-info mb-0" data-i18n="leave.current.none">No one is currently on leave.</div>
    <?php else: ?>
    <table class="table table-bordered leave-table mb-0">
      <thead>
        <tr>
          <th data-i18n="leave.table.member">Member</th>
          <th data-i18n="leave.table.type">Leave Type</th>
          <th data-i18n="leave.table.start">Start Date</th>
          <th data-i18n="leave.table.end">End Date</th>
          <th data-i18n="leave.table.reason">Reason</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($current_leaves as $row): ?>
        <?php
          $detail_parts = [];
          if(trim((string)($row['department'] ?? '')) !== '') $detail_parts[] = $row['department'];
          if(trim((string)($row['degree_pursuing'] ?? '')) !== '') $detail_parts[] = $row['degree_pursuing'];
          if(trim((string)($row['year_of_join'] ?? '')) !== '') $detail_parts[] = $row['year_of_join'];
          $member_meta = implode(' / ', $detail_parts);
        ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($row['name']); ?></div>
            <?php if($member_meta !== ''): ?><div class="leave-member-meta"><?= htmlspecialchars($member_meta); ?></div><?php endif; ?>
          </td>
          <td><span data-i18n="leave.type.<?= htmlspecialchars($row['leave_type']); ?>"><?= htmlspecialchars($row['leave_type']); ?></span></td>
          <td><?= htmlspecialchars($row['start_date']); ?></td>
          <td><?= htmlspecialchars($row['end_date']); ?></td>
          <td class="leave-reason"><?= nl2br(htmlspecialchars($row['reason'])); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="mb-4">
  <button class="btn btn-outline-secondary leave-toggle-btn mb-3" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#leavePendingCollapse"
          aria-expanded="true"
          aria-controls="leavePendingCollapse"
          data-show-key="leave.toggle.show_pending"
          data-hide-key="leave.toggle.hide_pending"
          data-i18n="leave.toggle.hide_pending">Hide pending requests</button>
  <div class="collapse show" id="leavePendingCollapse">
    <h4 data-i18n="leave.section.pending">Pending Requests</h4>
    <table class="table table-bordered leave-table mb-0">
      <thead>
        <tr>
          <th data-i18n="leave.table.member">Member</th>
          <th data-i18n="leave.table.type">Leave Type</th>
          <th data-i18n="leave.table.start">Start Date</th>
          <th data-i18n="leave.table.end">End Date</th>
          <th data-i18n="leave.table.reason">Reason</th>
          <th data-i18n="leave.table.actions">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($pending_leaves)): ?>
        <tr><td colspan="6" data-i18n="leave.none">No leave records.</td></tr>
      <?php else: ?>
        <?php foreach($pending_leaves as $row): ?>
        <?php
          $detail_parts = [];
          if(trim((string)($row['department'] ?? '')) !== '') $detail_parts[] = $row['department'];
          if(trim((string)($row['degree_pursuing'] ?? '')) !== '') $detail_parts[] = $row['degree_pursuing'];
          if(trim((string)($row['year_of_join'] ?? '')) !== '') $detail_parts[] = $row['year_of_join'];
          $member_meta = implode(' / ', $detail_parts);
        ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($row['name']); ?></div>
            <?php if($member_meta !== ''): ?><div class="leave-member-meta"><?= htmlspecialchars($member_meta); ?></div><?php endif; ?>
          </td>
          <td><span data-i18n="leave.type.<?= htmlspecialchars($row['leave_type']); ?>"><?= htmlspecialchars($row['leave_type']); ?></span></td>
          <td><?= htmlspecialchars($row['start_date']); ?></td>
          <td><?= htmlspecialchars($row['end_date']); ?></td>
          <td class="leave-reason"><?= nl2br(htmlspecialchars($row['reason'])); ?></td>
          <td class="leave-table-actions">
            <form method="post" class="d-inline">
              <input type="hidden" name="action" value="approve_leave">
              <input type="hidden" name="request_id" value="<?= (int)$row['id']; ?>">
              <button type="submit" class="btn btn-sm btn-success" data-i18n="leave.action.approve">Approve</button>
            </form>
            <button type="button"
                    class="btn btn-sm btn-danger reject-leave-btn"
                    data-request-id="<?= (int)$row['id']; ?>"
                    data-member-name="<?= htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#rejectLeaveModal"
                    data-i18n="leave.action.reject">Reject</button>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mb-4">
  <button class="btn btn-outline-secondary leave-toggle-btn mb-3" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#leaveRejectedCollapse"
          aria-expanded="false"
          aria-controls="leaveRejectedCollapse"
          data-show-key="leave.toggle.show_rejected"
          data-hide-key="leave.toggle.hide_rejected"
          data-i18n="leave.toggle.show_rejected">Show rejected requests</button>
  <div class="collapse" id="leaveRejectedCollapse">
    <h4 data-i18n="leave.section.rejected">Rejected Requests</h4>
    <table class="table table-bordered leave-table mb-0">
      <thead>
        <tr>
          <th data-i18n="leave.table.member">Member</th>
          <th data-i18n="leave.table.type">Leave Type</th>
          <th data-i18n="leave.table.start">Start Date</th>
          <th data-i18n="leave.table.end">End Date</th>
          <th data-i18n="leave.table.reason">Reason</th>
          <th data-i18n="leave.table.reject_reason">Rejection Reason</th>
          <th data-i18n="leave.table.status">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($rejected_leaves)): ?>
        <tr><td colspan="7" data-i18n="leave.none">No leave records.</td></tr>
      <?php else: ?>
        <?php foreach($rejected_leaves as $row): ?>
        <?php
          $reject_reason_text = leave_single_line_text($row['reject_reason'] ?? '');
          $reject_by_name = trim((string)($row['rejected_by_name'] ?? ''));
          $reject_tooltip = $reject_reason_text;
          if ($reject_by_name !== '') {
              $reject_tooltip .= ' · ' . $reject_by_name;
          }
          $reject_tooltip = $reject_reason_text;
          if ($reject_by_name !== '') {
              $reject_tooltip .= ' / ' . $reject_by_name;
          }
        ?>
        <tr>
          <td><?= htmlspecialchars($row['name']); ?></td>
          <td><span data-i18n="leave.type.<?= htmlspecialchars($row['leave_type']); ?>"><?= htmlspecialchars($row['leave_type']); ?></span></td>
          <td><?= htmlspecialchars($row['start_date']); ?></td>
          <td><?= htmlspecialchars($row['end_date']); ?></td>
          <td class="leave-reason"><?= nl2br(htmlspecialchars($row['reason'])); ?></td>
          <td class="leave-rejected-reason" title="<?= htmlspecialchars($reject_tooltip); ?>">
            <span class="leave-rejected-line">
              <span class="leave-rejected-text"><?= htmlspecialchars($reject_reason_text !== '' ? $reject_reason_text : '-'); ?></span>
              <?php if($reject_by_name !== ''): ?>
              <span class="leave-member-meta">/ <?= htmlspecialchars($reject_by_name); ?></span>
              <?php endif; ?>
            </span>
          </td>
          <td><span class="badge bg-danger" data-i18n="leave.status.rejected">Rejected</span></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="rejectLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="rejectLeaveForm">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="leave.modal.reject_title">Reject Leave</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="reject_leave">
        <input type="hidden" name="request_id" id="rejectLeaveRequestId" value="">
        <div class="mb-2 fw-semibold" id="rejectLeaveSummary"></div>
        <div class="mb-3">
          <label class="form-label" data-i18n="leave.form.reject_reason">Rejection Reason</label>
          <textarea class="form-control" rows="4" name="reject_reason" id="rejectLeaveReason" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="leave.form.cancel">Cancel</button>
        <button type="submit" class="btn btn-danger" data-i18n="leave.form.confirm">Confirm</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="leaveLeaderboardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="leave.ranking.title">Leave Days Ranking</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" data-i18n="leave.ranking.hint" data-i18n-params='{"year":"<?= (int)$this_year; ?>"}'>Ranking by approved leave days in {year}.</p>
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th data-i18n="leave.ranking.rank">Rank</th>
              <th data-i18n="leave.ranking.member">Member</th>
              <th data-i18n="leave.ranking.days">Approved Days</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($leaderboard_rows)): ?>
            <tr><td colspan="3" data-i18n="leave.ranking.none">No ranking data.</td></tr>
          <?php else: ?>
            <?php foreach($leaderboard_rows as $index => $row): ?>
            <?php
              $detail_parts = [];
              if(trim((string)($row['department'] ?? '')) !== '') $detail_parts[] = $row['department'];
              if(trim((string)($row['degree_pursuing'] ?? '')) !== '') $detail_parts[] = $row['degree_pursuing'];
              if(trim((string)($row['year_of_join'] ?? '')) !== '') $detail_parts[] = $row['year_of_join'];
              $member_meta = implode(' / ', $detail_parts);
            ?>
            <tr>
              <td><?= (int)$index + 1; ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($row['name']); ?></div>
                <div class="leave-member-meta"><?= htmlspecialchars($row['campus_id']); ?></div>
                <?php if($member_meta !== ''): ?><div class="leave-member-meta"><?= htmlspecialchars($member_meta); ?></div><?php endif; ?>
              </td>
              <td><?= (int)$row['approved_days']; ?> <span data-i18n="leave.summary.unit_days">days</span></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="leave.form.cancel">Cancel</button>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="leave-summary-card h-100">
      <div class="text-muted mb-2" data-i18n="leave.summary.year" data-i18n-params='{"year":"<?= (int)$this_year; ?>"}'>Year <?= (int)$this_year; ?></div>
      <div class="mb-1" data-i18n="leave.summary.applied_days">Applied Days</div>
      <div class="leave-summary-value"><?= (int)$member_applied_days; ?></div>
      <div class="leave-summary-unit" data-i18n="leave.summary.unit_days">days</div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="leave-summary-card h-100">
      <div class="text-muted mb-2" data-i18n="leave.summary.year" data-i18n-params='{"year":"<?= (int)$this_year; ?>"}'>Year <?= (int)$this_year; ?></div>
      <div class="mb-1" data-i18n="leave.summary.approved_days">Approved Days</div>
      <div class="leave-summary-value"><?= (int)$member_approved_days; ?></div>
      <div class="leave-summary-unit" data-i18n="leave.summary.unit_days">days</div>
    </div>
  </div>
</div>

<div class="mb-4">
  <button class="btn btn-outline-secondary leave-toggle-btn mb-3" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#leavePendingCollapse"
          aria-expanded="true"
          aria-controls="leavePendingCollapse"
          data-show-key="leave.toggle.show_pending"
          data-hide-key="leave.toggle.hide_pending"
          data-i18n="leave.toggle.hide_pending">Hide pending requests</button>
  <div class="collapse show" id="leavePendingCollapse">
    <h4 data-i18n="leave.section.pending">Pending Requests</h4>
    <table class="table table-bordered leave-table mb-0">
      <thead>
        <tr>
          <th data-i18n="leave.table.type">Leave Type</th>
          <th data-i18n="leave.table.start">Start Date</th>
          <th data-i18n="leave.table.end">End Date</th>
          <th data-i18n="leave.table.reason">Reason</th>
          <th data-i18n="leave.table.status">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($pending_leaves)): ?>
        <tr><td colspan="5" data-i18n="leave.none">No leave records.</td></tr>
      <?php else: ?>
        <?php foreach($pending_leaves as $row): ?>
        <tr>
          <td><span data-i18n="leave.type.<?= htmlspecialchars($row['leave_type']); ?>"><?= htmlspecialchars($row['leave_type']); ?></span></td>
          <td><?= htmlspecialchars($row['start_date']); ?></td>
          <td><?= htmlspecialchars($row['end_date']); ?></td>
          <td class="leave-reason"><?= nl2br(htmlspecialchars($row['reason'])); ?></td>
          <td><span class="badge bg-warning text-dark" data-i18n="leave.status.pending">Pending</span></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mb-4">
  <button class="btn btn-outline-secondary leave-toggle-btn mb-3" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#leaveApprovedCollapse"
          aria-expanded="true"
          aria-controls="leaveApprovedCollapse"
          data-show-key="leave.toggle.show_approved"
          data-hide-key="leave.toggle.hide_approved"
          data-i18n="leave.toggle.hide_approved">Hide approved and returned requests</button>
  <div class="collapse show" id="leaveApprovedCollapse">
    <h4 data-i18n="leave.section.approved">Approved / Returned</h4>
    <table class="table table-bordered leave-table mb-0">
      <thead>
        <tr>
          <th data-i18n="leave.table.type">Leave Type</th>
          <th data-i18n="leave.table.start">Start Date</th>
          <th data-i18n="leave.table.end">End Date</th>
          <th data-i18n="leave.table.actual_end">Actual End Date</th>
          <th data-i18n="leave.table.reason">Reason</th>
          <th data-i18n="leave.table.status">Status</th>
          <th data-i18n="leave.table.actions">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($approved_or_returned_leaves)): ?>
        <tr><td colspan="7" data-i18n="leave.none">No leave records.</td></tr>
      <?php else: ?>
        <?php foreach($approved_or_returned_leaves as $row): ?>
        <?php
          $status = $row['status'] ?? '';
          $can_return_early = ($status === 'approved' && $today >= $row['start_date'] && $today <= $row['end_date']);
          $max_return_date = ($today < $row['end_date']) ? $today : $row['end_date'];
        ?>
        <tr>
          <td><span data-i18n="leave.type.<?= htmlspecialchars($row['leave_type']); ?>"><?= htmlspecialchars($row['leave_type']); ?></span></td>
          <td><?= htmlspecialchars($row['start_date']); ?></td>
          <td><?= htmlspecialchars($row['end_date']); ?></td>
          <td><?= htmlspecialchars($row['actual_end_date'] ?? '-'); ?></td>
          <td class="leave-reason"><?= nl2br(htmlspecialchars($row['reason'])); ?></td>
          <td>
            <?php if($status === 'approved'): ?>
            <span class="badge bg-success" data-i18n="leave.status.approved">Approved</span>
            <?php else: ?>
            <span class="badge bg-secondary" data-i18n="leave.status.returned">Returned Early</span>
            <?php endif; ?>
          </td>
          <td class="leave-table-actions">
            <?php if($can_return_early): ?>
            <button type="button"
                    class="btn btn-sm btn-warning return-leave-btn"
                    data-request-id="<?= (int)$row['id']; ?>"
                    data-start-date="<?= htmlspecialchars($row['start_date']); ?>"
                    data-max-date="<?= htmlspecialchars($max_return_date); ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#returnLeaveModal"
                    data-i18n="leave.action.return">Return Early</button>
            <?php else: ?>
            -
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mb-4">
  <button class="btn btn-outline-secondary leave-toggle-btn mb-3" type="button"
          data-bs-toggle="collapse"
          data-bs-target="#leaveRejectedCollapse"
          aria-expanded="false"
          aria-controls="leaveRejectedCollapse"
          data-show-key="leave.toggle.show_rejected"
          data-hide-key="leave.toggle.hide_rejected"
          data-i18n="leave.toggle.show_rejected">Show rejected requests</button>
  <div class="collapse" id="leaveRejectedCollapse">
    <h4 data-i18n="leave.section.rejected">Rejected Requests</h4>
    <table class="table table-bordered leave-table mb-0">
      <thead>
        <tr>
          <th data-i18n="leave.table.type">Leave Type</th>
          <th data-i18n="leave.table.start">Start Date</th>
          <th data-i18n="leave.table.end">End Date</th>
          <th data-i18n="leave.table.reason">Reason</th>
          <th data-i18n="leave.table.reject_reason">Rejection Reason</th>
          <th data-i18n="leave.table.status">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($rejected_leaves)): ?>
        <tr><td colspan="6" data-i18n="leave.none">No leave records.</td></tr>
      <?php else: ?>
        <?php foreach($rejected_leaves as $row): ?>
        <?php
          $reject_reason_text = leave_single_line_text($row['reject_reason'] ?? '');
          $reject_by_name = trim((string)($row['rejected_by_name'] ?? ''));
          $reject_tooltip = $reject_reason_text;
          if ($reject_by_name !== '') {
              $reject_tooltip .= ' · ' . $reject_by_name;
          }
        ?>
        <tr>
          <td><span data-i18n="leave.type.<?= htmlspecialchars($row['leave_type']); ?>"><?= htmlspecialchars($row['leave_type']); ?></span></td>
          <td><?= htmlspecialchars($row['start_date']); ?></td>
          <td><?= htmlspecialchars($row['end_date']); ?></td>
          <td class="leave-reason"><?= nl2br(htmlspecialchars($row['reason'])); ?></td>
          <td class="leave-rejected-reason" title="<?= htmlspecialchars($reject_tooltip); ?>">
            <span class="leave-rejected-line">
              <span class="leave-rejected-text"><?= htmlspecialchars($reject_reason_text !== '' ? $reject_reason_text : '-'); ?></span>
              <?php if($reject_by_name !== ''): ?>
              <span class="leave-member-meta">/ <?= htmlspecialchars($reject_by_name); ?></span>
              <?php endif; ?>
            </span>
          </td>
          <td><span class="badge bg-danger" data-i18n="leave.status.rejected">Rejected</span></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="leaveRequestForm">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="leave.modal.apply_title">Apply Leave</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create_leave">
        <div class="mb-3">
          <label class="form-label" data-i18n="leave.form.type">Leave Type</label>
          <select class="form-select" name="leave_type" id="leaveTypeInput" required>
            <option value="personal" data-i18n="leave.type.personal">Personal Leave</option>
            <option value="sick" data-i18n="leave.type.sick">Sick Leave</option>
            <option value="winter_summer" data-i18n="leave.type.winter_summer">Winter/Summer Vacation</option>
            <option value="internship" data-i18n="leave.type.internship">Internship Leave</option>
            <option value="group_activity" data-i18n="leave.type.group_activity">Group Activity</option>
            <option value="statutory_holiday" data-i18n="leave.type.statutory_holiday">Statutory Holiday</option>
            <option value="other" data-i18n="leave.type.other">Other</option>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" data-i18n="leave.form.start">Start Date</label>
            <input type="date" class="form-control" name="start_date" id="leaveStartInput" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="leave.form.end">End Date</label>
            <input type="date" class="form-control" name="end_date" id="leaveEndInput" required>
          </div>
        </div>
        <div class="mb-3 mt-3">
          <label class="form-label" data-i18n="leave.form.reason">Reason</label>
          <textarea class="form-control" rows="4" name="reason" id="leaveReasonInput" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="leave.form.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" data-i18n="leave.form.save">Submit</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="returnLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="returnLeaveForm">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="leave.modal.return_title">Return Early</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="return_leave_early">
        <input type="hidden" name="request_id" id="returnLeaveRequestId" value="">
        <div class="mb-3">
          <label class="form-label" data-i18n="leave.form.actual_end">Actual Return Date</label>
          <input type="date" class="form-control" name="actual_end_date" id="returnLeaveDateInput" required>
          <div class="form-text" id="returnLeaveRangeHint"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="leave.form.cancel">Cancel</button>
        <button type="submit" class="btn btn-warning" data-i18n="leave.form.confirm">Confirm</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const getLang = () => document.documentElement.lang || 'zh';
  const getText = (key, fallback = '') => window.translations?.[getLang()]?.[key] || fallback || key;

  const toggleButtons = Array.from(document.querySelectorAll('.leave-toggle-btn'));
  const updateToggleButton = (button) => {
    const target = button.getAttribute('data-bs-target');
    const collapseEl = target ? document.querySelector(target) : null;
    if (!collapseEl) return;
    const key = collapseEl.classList.contains('show') ? button.dataset.hideKey : button.dataset.showKey;
    if (!key) return;
    button.setAttribute('data-i18n', key);
    button.textContent = getText(key, button.textContent);
  };

  toggleButtons.forEach((button) => {
    const target = button.getAttribute('data-bs-target');
    const collapseEl = target ? document.querySelector(target) : null;
    if (!collapseEl) return;
    collapseEl.addEventListener('show.bs.collapse', () => updateToggleButton(button));
    collapseEl.addEventListener('hide.bs.collapse', () => updateToggleButton(button));
    updateToggleButton(button);
  });

  document.getElementById('langToggle')?.addEventListener('click', () => {
    setTimeout(() => toggleButtons.forEach(updateToggleButton), 0);
  });

  const leaveRequestForm = document.getElementById('leaveRequestForm');
  const leaveStartInput = document.getElementById('leaveStartInput');
  const leaveEndInput = document.getElementById('leaveEndInput');
  const leaveReasonInput = document.getElementById('leaveReasonInput');
  leaveRequestForm?.addEventListener('submit', (event) => {
    const start = leaveStartInput?.value || '';
    const end = leaveEndInput?.value || '';
    const reason = (leaveReasonInput?.value || '').trim();
    if (!start || !end || start > end) {
      event.preventDefault();
      alert(getText('leave.message.invalid_date', 'Start date must be on or before end date.'));
      return;
    }
    if (!reason) {
      event.preventDefault();
      alert(getText('leave.message.reason_required', 'Please provide a reason.'));
    }
  });

  const rejectRequestIdInput = document.getElementById('rejectLeaveRequestId');
  const rejectSummary = document.getElementById('rejectLeaveSummary');
  document.querySelectorAll('.reject-leave-btn').forEach((button) => {
    button.addEventListener('click', () => {
      if (rejectRequestIdInput) rejectRequestIdInput.value = button.dataset.requestId || '';
      if (rejectSummary) rejectSummary.textContent = button.dataset.memberName || '';
    });
  });

  const rejectLeaveForm = document.getElementById('rejectLeaveForm');
  const rejectReasonInput = document.getElementById('rejectLeaveReason');
  rejectLeaveForm?.addEventListener('submit', (event) => {
    if (!(rejectReasonInput?.value || '').trim()) {
      event.preventDefault();
      alert(getText('leave.message.reject_reason_required', 'Please provide a rejection reason.'));
    }
  });

  const returnRequestIdInput = document.getElementById('returnLeaveRequestId');
  const returnDateInput = document.getElementById('returnLeaveDateInput');
  const returnRangeHint = document.getElementById('returnLeaveRangeHint');
  document.querySelectorAll('.return-leave-btn').forEach((button) => {
    button.addEventListener('click', () => {
      const requestId = button.dataset.requestId || '';
      const startDate = button.dataset.startDate || '';
      const maxDate = button.dataset.maxDate || '';
      if (returnRequestIdInput) returnRequestIdInput.value = requestId;
      if (returnDateInput) {
        returnDateInput.min = startDate;
        returnDateInput.max = maxDate;
        returnDateInput.value = maxDate;
      }
      if (returnRangeHint) {
        returnRangeHint.textContent = `${startDate} ~ ${maxDate}`;
      }
    });
  });

  const returnLeaveForm = document.getElementById('returnLeaveForm');
  returnLeaveForm?.addEventListener('submit', (event) => {
    const value = returnDateInput?.value || '';
    const min = returnDateInput?.min || '';
    const max = returnDateInput?.max || '';
    if (!value || (min && value < min) || (max && value > max)) {
      event.preventDefault();
      alert(getText('leave.message.return_date_range', 'Actual return date must be between start date and today.'));
    }
  });
});
</script>

<?php include 'footer.php'; ?>
