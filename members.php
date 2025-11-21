<?php
require_once 'config.php';
include_once 'auth.php';
require_once 'member_extra_helpers.php';

$role = $_SESSION['role'] ?? '';
$isManager = $role === 'manager';
$sessionMemberId = (int)($_SESSION['member_id'] ?? 0);
$extraAttributes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['member_action'] ?? '') === 'save') {
    $memberId = isset($_POST['member_id']) && $_POST['member_id'] !== ''
        ? (int)$_POST['member_id']
        : null;

    $campus_id = trim($_POST['campus_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $identity_number = trim($_POST['identity_number'] ?? '');
    $year_of_join = trim($_POST['year_of_join'] ?? '');
    $current_degree = trim($_POST['current_degree'] ?? '');
    $degree_pursuing = trim($_POST['degree_pursuing'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $wechat = trim($_POST['wechat'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $workplace = trim($_POST['workplace'] ?? '');
    $homeplace = trim($_POST['homeplace'] ?? '');
    $status = ($_POST['status'] ?? 'in_work') === 'exited' ? 'exited' : 'in_work';

    $extraAttributes = getMemberExtraAttributes($pdo);
    $extraUploads = $_FILES['extra_attrs'] ?? null;
    $extraValues = isset($_POST['extra_attrs']) && is_array($_POST['extra_attrs']) ? $_POST['extra_attrs'] : [];
    $existingExtraValues = [];
    if ($memberId) {
        $existingExtraValues = getMemberExtraValues($pdo, [$memberId]);
        $existingExtraValues = $existingExtraValues[$memberId] ?? [];
    }

    if ($isManager) {
        if ($memberId) {
            $stmt = $pdo->prepare('UPDATE members SET campus_id=?, name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=?, status=? WHERE id=?');
            $stmt->execute([
                $campus_id,
                $name,
                $email,
                $identity_number,
                $year_of_join,
                $current_degree,
                $degree_pursuing,
                $phone,
                $wechat,
                $department,
                $workplace,
                $homeplace,
                $status,
                $memberId
            ]);
        } else {
            $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM members');
            $nextOrder = (int)($orderStmt->fetchColumn() ?? 0);
            $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $campus_id,
                $name,
                $email,
                $identity_number,
                $year_of_join,
                $current_degree,
                $degree_pursuing,
                $phone,
                $wechat,
                $department,
                $workplace,
                $homeplace,
                $status,
                $nextOrder
            ]);
            $memberId = (int)$pdo->lastInsertId();
            $existingExtraValues = [];
        }
    } elseif ($role === 'member' && $memberId && $memberId === $sessionMemberId) {
        $stmt = $pdo->prepare('UPDATE members SET campus_id=?, name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=? WHERE id=?');
        $stmt->execute([
            $campus_id,
            $name,
            $email,
            $identity_number,
            $year_of_join,
            $current_degree,
            $degree_pursuing,
            $phone,
            $wechat,
            $department,
            $workplace,
            $homeplace,
            $memberId
        ]);
        $existingExtraValues = getMemberExtraValues($pdo, [$memberId]);
        $existingExtraValues = $existingExtraValues[$memberId] ?? [];
    } else {
        header('Location: members.php');
        exit();
    }

    $preparedValues = prepareMemberExtraValues((int)$memberId, $extraAttributes, $extraValues, $extraUploads, $existingExtraValues);
    ensureMemberExtraValues($pdo, (int)$memberId, $preparedValues, $extraAttributes);

    header('Location: members.php');
    exit();
}

// Column definitions used for both manager and member views
$columns = [
    'campus_id' => ['key' => 'members.table.campus_id', 'label' => '一卡通号'],
    'name' => ['key' => 'members.table.name', 'label' => '姓名'],
    'status' => ['key' => 'members.table.status', 'label' => '状态'],
    'email' => ['key' => 'members.table.email', 'label' => '正式邮箱'],
    'identity_number' => ['key' => 'members.table.identity_number', 'label' => '身份证号'],
    'year_of_join' => ['key' => 'members.table.year_of_join', 'label' => '入学年份'],
    'current_degree' => ['key' => 'members.table.current_degree', 'label' => '已获学位'],
    'degree_pursuing' => ['key' => 'members.table.degree_pursuing', 'label' => '当前学历'],
    'phone' => ['key' => 'members.table.phone', 'label' => '手机号'],
    'wechat' => ['key' => 'members.table.wechat', 'label' => '微信号'],
    'department' => ['key' => 'members.table.department', 'label' => '所处学院/单位'],
    'workplace' => ['key' => 'members.table.workplace', 'label' => '工作地点'],
    'homeplace' => ['key' => 'members.table.homeplace', 'label' => '家庭住址']
];

$extraAttributes = $extraAttributes ?? getMemberExtraAttributes($pdo);

if($role === 'member') {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id=?');
    $stmt->execute([$sessionMemberId]);
    $members = $stmt->fetchAll();
    $sort = 'sort_order';
    $dir = 'ASC';
    $statusFilter = 'all';
    $inWorkTotal = 0;
    $inWorkByDegree = [];
} else {
    // Determine sorting column and direction from query parameters
    $sort = $_GET['sort'] ?? 'sort_order';
    if (!array_key_exists($sort, $columns) && $sort !== 'sort_order') {
        $sort = 'sort_order';
    }
    $dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

    $statusFilter = $_GET['status'] ?? 'in_work';
    $where = '';
    $params = [];
    if (in_array($statusFilter, ['in_work','exited'])) {
        $where = 'WHERE status = ?';
        $params[] = $statusFilter;
    }
    $sql = "SELECT * FROM members $where ORDER BY (status='exited'), $sort $dir";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    $inWorkTotalStmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'in_work'");
    $inWorkTotal = (int)($inWorkTotalStmt->fetchColumn() ?: 0);

    $degreeStmt = $pdo->query("SELECT COALESCE(NULLIF(TRIM(degree_pursuing), ''), '') AS degree_label, COUNT(*) AS total
      FROM members
      WHERE status = 'in_work'
      GROUP BY degree_label
      ORDER BY degree_label");
    $inWorkByDegree = [];
    while ($row = $degreeStmt->fetch()) {
        $degreeKey = (string)($row['degree_label'] ?? '');
        $inWorkByDegree[$degreeKey] = (int)($row['total'] ?? 0);
    }
    arsort($inWorkByDegree);
}

$memberExtraValues = [];
if (!empty($members)) {
    $memberIds = array_column($members, 'id');
    $memberExtraValues = getMemberExtraValues($pdo, $memberIds);
}

$summaryCounts = [];
foreach($members as $m){
    $degree = trim((string)($m['degree_pursuing'] ?? ''));
    $year = trim((string)($m['year_of_join'] ?? ''));
    if($degree === '' && $year === ''){
        $key = '__unknown__';
    } else {
        $key = $degree . '||' . $year;
    }
    if(!isset($summaryCounts[$key])){
        $summaryCounts[$key] = 0;
    }
    $summaryCounts[$key]++;
}
$summaryItems = [];
foreach($summaryCounts as $key => $count){
    if($key === '__unknown__'){
        $summaryItems[] = [
            'degree' => '',
            'year' => '',
            'count' => $count
        ];
        continue;
    }
    $parts = explode('||', $key);
    $summaryItems[] = [
        'degree' => trim($parts[0] ?? ''),
        'year' => trim($parts[1] ?? ''),
        'count' => $count
    ];
}
include 'header.php';
?>
<style>
  .summary-stat-title { white-space: nowrap; letter-spacing: .08em; }
  .summary-scroll-wrapper { overflow-x: auto; }
  .summary-scroll-inner { display: flex; flex-wrap: nowrap; align-items: center; gap: 0.75rem; min-height: 2.5rem; }
  .summary-scroll-inner::-webkit-scrollbar { height: 6px; }
  .summary-scroll-inner::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); border-radius: 3px; }
  .summary-label { flex-shrink: 0; font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; color: #6c757d; white-space: nowrap; }
  .summary-pill { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.75rem; border-radius: 999px; border: 1px solid rgba(13,110,253,0.35); background-color: rgba(13,110,253,0.08); color: #0d6efd; font-weight: 600; white-space: nowrap; }
  .summary-pill-count { font-size: 1rem; color: #0b5ed7; }
  .members-table th,
  .members-table td { white-space: nowrap; }
</style>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="members.title">团队成员</h2>
  <?php if($isManager): ?>
  <div>
    <button type="button" class="btn btn-success" id="addMemberBtn" data-i18n="members.add">新增成员</button>
    <a class="btn btn-secondary" href="members_import.php" data-i18n="members.import">从表格导入</a>
    <a class="btn btn-secondary" href="members_export.php" id="exportMembers" data-i18n="members.export">导出至表格</a>
    <button type="button" class="btn btn-warning qr-btn" data-url="member_self_update.php" data-i18n="members.request_update">请求信息更新</button>
    <button type="button" class="btn btn-outline-primary" id="editExtraAttributesBtn" data-i18n="members.extra.edit">编辑额外属性</button>
  </div>
  <?php endif; ?>
</div>
<?php if($isManager): ?>
<div class="mb-3">
  <a class="btn btn-sm <?= $statusFilter==='all'? 'btn-primary':'btn-outline-primary'; ?>" href="?status=all&amp;sort=<?= $sort; ?>&amp;dir=<?= strtolower($dir); ?>" data-i18n="members.filter.all">全部</a>
  <a class="btn btn-sm <?= $statusFilter==='in_work'? 'btn-primary':'btn-outline-primary'; ?>" href="?status=in_work&amp;sort=<?= $sort; ?>&amp;dir=<?= strtolower($dir); ?>" data-i18n="members.filter.in_work">在岗</a>
  <a class="btn btn-sm <?= $statusFilter==='exited'? 'btn-primary':'btn-outline-primary'; ?>" href="?status=exited&amp;sort=<?= $sort; ?>&amp;dir=<?= strtolower($dir); ?>" data-i18n="members.filter.exited">已离退</a>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleColor" data-i18n="members.toggle_color">Toggle Colors</button>
</div>
<div class="mb-3">
  <div class="card shadow-sm">
    <div class="card-body d-flex flex-column flex-lg-row gap-4 align-items-start align-items-lg-center">
      <div>
        <div class="text-uppercase text-muted small summary-stat-title" data-i18n="members.summary.in_work_total">Current Active Members</div>
        <div class="display-6 fw-bold text-primary mb-0"><?= $inWorkTotal; ?></div>
      </div>
      <div class="vr d-none d-lg-block"></div>
      <div class="flex-grow-1 w-100">
        <div class="text-uppercase text-muted small summary-stat-title" data-i18n="members.summary.by_degree">Active Members by Current Degree</div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <?php if(!empty($inWorkByDegree)): ?>
            <?php foreach($inWorkByDegree as $degree => $count): ?>
              <span class="badge bg-info text-dark fs-6 px-3 py-2">
                <?php if(trim($degree) === ''): ?>
                  <span data-i18n="members.summary.degree.unknown">Unspecified</span>
                <?php else: ?>
                  <?= htmlspecialchars($degree); ?>
                <?php endif; ?>
                <span class="ms-2 fw-semibold"><?= $count; ?></span>
              </span>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="text-muted" data-i18n="members.summary.none">No active members currently.</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card shadow-sm mb-3">
  <div class="card-body py-3">
    <div class="summary-scroll-wrapper">
      <div class="summary-scroll-inner">
        <span class="summary-label" data-i18n="members.summary.title">Summary</span>
        <?php if(!empty($summaryItems)): ?>
          <?php foreach($summaryItems as $item): ?>
            <span class="summary-pill">
              <?php if($item['degree'] === '' && $item['year'] === ''): ?>
                <span data-i18n="members.summary.degree.unknown">Unspecified</span>
              <?php else: ?>
                <?php if($item['degree'] !== ''): ?>
                  <?= htmlspecialchars($item['degree']); ?>
                <?php endif; ?>
                <?php if($item['year'] !== ''): ?>
                  <?php if($item['degree'] !== ''): ?>
                    <span class="text-muted">·</span>
                  <?php endif; ?>
                  <?= htmlspecialchars($item['year']); ?>
                <?php endif; ?>
              <?php endif; ?>
              <span class="summary-pill-count"><?= (int)$item['count']; ?></span>
            </span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted" data-i18n="members.summary.none">No active members currently.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover members-table">
  <thead>
  <tr>
    <th></th>
    <?php foreach($columns as $col => $info):
        $label = $info['label'];
        $key = $info['key'];
        $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
        if($isManager):
    ?>
      <th><a href="?sort=<?= $col; ?>&amp;dir=<?= $newDir; ?>&amp;status=<?= $statusFilter; ?>" data-i18n="<?= $key; ?>"><?= htmlspecialchars($label); ?></a></th>
    <?php else: ?>
      <th data-i18n="<?= $key; ?>"><?= htmlspecialchars($label); ?></th>
    <?php endif; endforeach; ?>
    <?php foreach ($extraAttributes as $attr):
        $nameZh = trim((string)($attr['name_zh'] ?? ''));
        $nameEn = trim((string)($attr['name_en'] ?? ''));
        $attrId = (int)($attr['id'] ?? 0);
        $display = $nameZh !== '' ? $nameZh : ($nameEn !== '' ? $nameEn : ('Attr ' . $attrId));
    ?>
      <th data-extra-name-zh="<?= htmlspecialchars($nameZh, ENT_QUOTES); ?>" data-extra-name-en="<?= htmlspecialchars($nameEn, ENT_QUOTES); ?>"><?= htmlspecialchars($display); ?></th>
    <?php endforeach; ?>
    <th data-i18n="members.table.actions">操作</th>
  </tr>
  </thead>
  <tbody id="memberList">
  <?php foreach($members as $m): ?>
  <tr data-id="<?= $m['id']; ?>" data-year="<?= htmlspecialchars($m['year_of_join']); ?>" data-degree="<?= htmlspecialchars($m['degree_pursuing']); ?>">
    <?php if($isManager): ?>
    <td class="drag-handle">&#9776;</td>
    <?php else: ?>
    <td></td>
    <?php endif; ?>
    <td><?= htmlspecialchars($m['campus_id']); ?></td>
    <td><?= htmlspecialchars($m['name']); ?></td>
    <td><span data-i18n="<?= $m['status']==='in_work' ? 'members.status.in_work' : 'members.status.exited'; ?>"><?= $m['status']==='in_work' ? '在岗' : '已离退'; ?></span></td>
    <td><?= htmlspecialchars($m['email']); ?></td>
    <td><?= htmlspecialchars($m['identity_number']); ?></td>
    <td><?= htmlspecialchars($m['year_of_join']); ?></td>
    <td><?= htmlspecialchars($m['current_degree']); ?></td>
    <td><?= htmlspecialchars($m['degree_pursuing']); ?></td>
    <td><?= htmlspecialchars($m['phone']); ?></td>
    <td><?= htmlspecialchars($m['wechat']); ?></td>
    <td><?= htmlspecialchars($m['department']); ?></td>
    <td><?= htmlspecialchars($m['workplace']); ?></td>
    <td><?= htmlspecialchars($m['homeplace']); ?></td>
    <?php
      $memberId = (int)($m['id'] ?? 0);
      $rowExtraValues = [];
      foreach ($extraAttributes as $attr) {
        $attrId = (int)($attr['id'] ?? 0);
        $attrType = $attr['attribute_type'] ?? 'text';
        $fallback = $attrType === 'text' ? (string)($attr['default_value'] ?? '') : '';
        $rowExtraValues[$attrId] = (string)($memberExtraValues[$memberId][$attrId] ?? $fallback);
      }
      $rowExtraJson = htmlspecialchars(json_encode($rowExtraValues, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    ?>
    <?php foreach ($extraAttributes as $attr):
      $attrId = (int)($attr['id'] ?? 0);
      $value = $rowExtraValues[$attrId] ?? '';
    ?>
    <td><?= htmlspecialchars($value); ?></td>
    <?php endforeach; ?>
    <td>
      <button type="button"
              class="btn btn-sm btn-primary member-edit-btn"
              data-id="<?= $m['id']; ?>"
              data-campus-id="<?= htmlspecialchars((string)($m['campus_id'] ?? ''), ENT_QUOTES); ?>"
              data-name="<?= htmlspecialchars((string)($m['name'] ?? ''), ENT_QUOTES); ?>"
              data-email="<?= htmlspecialchars((string)($m['email'] ?? ''), ENT_QUOTES); ?>"
              data-identity-number="<?= htmlspecialchars((string)($m['identity_number'] ?? ''), ENT_QUOTES); ?>"
              data-year-of-join="<?= htmlspecialchars((string)($m['year_of_join'] ?? ''), ENT_QUOTES); ?>"
              data-current-degree="<?= htmlspecialchars((string)($m['current_degree'] ?? ''), ENT_QUOTES); ?>"
              data-degree-pursuing="<?= htmlspecialchars((string)($m['degree_pursuing'] ?? ''), ENT_QUOTES); ?>"
              data-phone="<?= htmlspecialchars((string)($m['phone'] ?? ''), ENT_QUOTES); ?>"
              data-wechat="<?= htmlspecialchars((string)($m['wechat'] ?? ''), ENT_QUOTES); ?>"
              data-department="<?= htmlspecialchars((string)($m['department'] ?? ''), ENT_QUOTES); ?>"
              data-workplace="<?= htmlspecialchars((string)($m['workplace'] ?? ''), ENT_QUOTES); ?>"
              data-homeplace="<?= htmlspecialchars((string)($m['homeplace'] ?? ''), ENT_QUOTES); ?>"
              data-extra='<?= $rowExtraJson; ?>'
              data-status="<?= htmlspecialchars((string)($m['status'] ?? 'in_work'), ENT_QUOTES); ?>"
              data-i18n="members.action.edit">编辑</button>
      <?php if($isManager): ?>
      <a class="btn btn-sm btn-danger" href="member_delete.php?id=<?= $m['id']; ?>" onclick="return doubleConfirm(translations[document.documentElement.lang]['members.confirm.remove']);" data-i18n="members.action.remove">移除</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
  </div>
  <div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form id="memberForm" method="post" enctype="multipart/form-data">
          <input type="hidden" name="member_action" value="save">
          <input type="hidden" name="member_id" value="">
          <div class="modal-header">
            <h5 class="modal-title" id="memberModalTitle" data-i18n="member_edit.title_add">Add Member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.campus_id">Campus ID</label>
                <input type="text" name="campus_id" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.name">Name</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.email">Email</label>
                <input type="email" name="email" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.identity_number">Identity Number</label>
                <input type="text" name="identity_number" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.year_of_join">Year of Join</label>
                <input type="number" name="year_of_join" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.current_degree">Current Degree</label>
                <input type="text" name="current_degree" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.degree_pursuing">Degree Pursuing</label>
                <input type="text" name="degree_pursuing" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.phone">Phone</label>
                <input type="text" name="phone" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.wechat">WeChat</label>
                <input type="text" name="wechat" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.department">Department</label>
                <input type="text" name="department" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.workplace">Workplace</label>
                <input type="text" name="workplace" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.homeplace">Homeplace</label>
                <input type="text" name="homeplace" class="form-control">
              </div>
              <?php if (!empty($extraAttributes)): ?>
              <div class="col-12">
                <hr class="my-2">
                <h6 class="text-muted" data-i18n="members.extra.section_title">额外属性</h6>
              </div>
              <?php foreach ($extraAttributes as $attr):
                $attrId = (int)($attr['id'] ?? 0);
                $nameZh = (string)($attr['name_zh'] ?? '');
                $nameEn = (string)($attr['name_en'] ?? '');
                $attrType = (string)($attr['attribute_type'] ?? 'text');
                $defaultValue = $attrType === 'text' ? (string)($attr['default_value'] ?? '') : '';
                $displayName = $nameZh !== '' ? $nameZh : ($nameEn !== '' ? $nameEn : ('Attr ' . $attrId));
              ?>
              <div class="col-md-6" data-extra-wrapper>
                <label class="form-label" data-extra-name-zh="<?= htmlspecialchars($nameZh, ENT_QUOTES); ?>" data-extra-name-en="<?= htmlspecialchars($nameEn, ENT_QUOTES); ?>"><?= htmlspecialchars($displayName); ?></label>
                <?php if ($attrType === 'media'): ?>
                <input type="file" name="extra_attrs[<?= $attrId; ?>]" class="form-control" data-extra-field data-attribute-id="<?= $attrId; ?>" data-default-value="" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>" accept="image/*,.zip,.rar,.7z,.tar,.gz,.7zip,.7Z">
                <div class="form-text" data-i18n="members.extra.helper.media_input">可上传图片、压缩包等文件。</div>
                <div class="small text-muted d-none" data-extra-current-file data-i18n="members.extra.no_file"></div>
                <?php else: ?>
                <input type="text" name="extra_attrs[<?= $attrId; ?>]" class="form-control" value="<?= htmlspecialchars($defaultValue, ENT_QUOTES); ?>" data-extra-field data-attribute-id="<?= $attrId; ?>" data-default-value="<?= htmlspecialchars($defaultValue, ENT_QUOTES); ?>" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
              <?php if($isManager): ?>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.status">Status</label>
                <select name="status" class="form-select">
                  <option value="in_work" data-i18n="members.status.in_work" selected>In Work</option>
                  <option value="exited" data-i18n="members.status.exited">Exited</option>
                </select>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="member_edit.cancel">Cancel</button>
            <button type="submit" class="btn btn-primary" data-i18n="member_edit.save">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php if($isManager): ?>
  <div class="modal fade" id="extraAttributesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="extraAttributesForm">
          <div class="modal-header">
            <h5 class="modal-title" data-i18n="members.extra.modal_title">编辑额外属性</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted" data-i18n="members.extra.description">额外属性会显示在成员列表中，并同步到新增、编辑以及信息更新页面。</p>
            <div id="extraAttributesList" class="mt-3"></div>
            <button type="button" class="btn btn-outline-secondary mt-3" id="addExtraAttribute" data-i18n="members.extra.add">新增属性</button>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="members.extra.cancel">取消</button>
            <button type="submit" class="btn btn-primary" data-i18n="members.extra.save">保存</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
    window.memberExtraAttributes = <?= json_encode($extraAttributes, JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
  <?php endif; ?>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    const exportLink = document.getElementById('exportMembers');
    if(exportLink){
      exportLink.href = `members_export.php?lang=${document.documentElement.lang||'zh'}`;
    }
    <?php if($isManager): ?>
    if(typeof Sortable !== 'undefined' && document.getElementById('memberList')){
      Sortable.create(document.getElementById('memberList'), {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function(){
          const order = Array.from(document.querySelectorAll('#memberList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
          fetch('member_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order: order})
          });
        }
      });
    }
    const toggleBtn = document.getElementById('toggleColor');
    if(toggleBtn){
      let colored=false;
      const colorMap={};
      function getColor(key){
        if(!colorMap[key]){
          const hue=Object.keys(colorMap).length*60%360;
          colorMap[key]='hsl('+hue+',70%,80%)';
        }
        return colorMap[key];
      }
      function applyColors(){
        document.querySelectorAll('#memberList tr').forEach(row=>{
          const key=row.dataset.year+'-'+row.dataset.degree;
          row.style.backgroundColor=getColor(key);
        });
      }
      function clearColors(){
        document.querySelectorAll('#memberList tr').forEach(row=>{row.style.backgroundColor='';});
      }
      toggleBtn.addEventListener('click',()=>{
        colored=!colored;
        if(colored){applyColors();toggleBtn.classList.add('btn-primary');}
        else{clearColors();toggleBtn.classList.remove('btn-primary');}
      });
    }
    <?php endif; ?>
    const memberModalElement = document.getElementById('memberModal');
    const memberForm = document.getElementById('memberForm');
    const addMemberBtn = document.getElementById('addMemberBtn');
    const modalTitle = document.getElementById('memberModalTitle');
    const isManager = <?= $isManager ? 'true' : 'false'; ?>;
    if(memberModalElement && memberForm){
      if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        console.warn('Bootstrap modal is not available.');
        return;
      }
      const memberModal = new bootstrap.Modal(memberModalElement);
      const fieldNames = ['campus_id','name','email','identity_number','year_of_join','current_degree','degree_pursuing','phone','wechat','department','workplace','homeplace'];
      const extraInputs = Array.from(memberForm.querySelectorAll('[data-extra-field]'));
      function translateWithFallback(key, fallback){
        const lang = document.documentElement.lang || 'zh';
        return (translations?.[lang] && translations[lang][key]) || fallback;
      }
      function translate(key){
        return translateWithFallback(key, key);
      }
      function updateMediaInfo(input, value, isSelection){
        const wrapper = input.closest('[data-extra-wrapper]');
        const info = wrapper ? wrapper.querySelector('[data-extra-current-file]') : null;
        if(!info){
          return;
        }
        const currentLabel = translateWithFallback('members.extra.current_file', '当前文件');
        const noneLabel = translateWithFallback('members.extra.no_file', '暂无上传的文件');
        const selectedLabel = translateWithFallback('members.extra.selected_file', '已选择文件');
        if(value){
          const label = isSelection ? selectedLabel : currentLabel;
          const safeValue = String(value);
          if(isSelection){
            info.textContent = `${label}: ${safeValue}`;
          } else {
            const link = encodeURI(safeValue);
            info.innerHTML = `${label}: <a href="${link}" target="_blank" rel="noopener">${safeValue}</a>`;
          }
        } else {
          info.textContent = noneLabel;
        }
        info.classList.remove('d-none');
      }
      function setModalTitle(key){
        if(!modalTitle){
          return;
        }
        modalTitle.setAttribute('data-i18n', key);
        modalTitle.textContent = translate(key);
      }
      function resetExtraFields(){
        extraInputs.forEach(function(input){
          const attrType = input.dataset.attributeType || 'text';
          if(attrType === 'media'){
            input.value = '';
            updateMediaInfo(input, '', false);
          } else {
            const defaultValue = input.dataset.defaultValue ?? '';
            input.value = defaultValue;
          }
        });
      }
      function resetForm(){
        memberForm.reset();
        memberForm.elements['member_id'].value = '';
        if(memberForm.elements['status']){
          memberForm.elements['status'].value = 'in_work';
        }
        resetExtraFields();
      }
      if(isManager && addMemberBtn){
        addMemberBtn.addEventListener('click', function(){
          resetForm();
          setModalTitle('member_edit.title_add');
          memberModal.show();
        });
      }
      document.querySelectorAll('.member-edit-btn').forEach(function(btn){
        btn.addEventListener('click', function(event){
          event.preventDefault();
          const data = btn.dataset;
          resetForm();
          memberForm.elements['member_id'].value = data.id || '';
          fieldNames.forEach(function(name){
            const datasetKey = name.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
            if(memberForm.elements[name]){
              memberForm.elements[name].value = data[datasetKey] || '';
            }
          });
          let extraData = {};
          if (data.extra) {
            try {
              extraData = JSON.parse(data.extra);
            } catch (error) {
              extraData = {};
            }
          }
          extraInputs.forEach(function(input){
            const attrId = input.dataset.attributeId;
            const attrType = input.dataset.attributeType || 'text';
            const defaultValue = attrType === 'text' ? (input.dataset.defaultValue ?? '') : '';
            let value = defaultValue;
            if (attrId && Object.prototype.hasOwnProperty.call(extraData, attrId)) {
              value = extraData[attrId] ?? defaultValue;
            }
            if(attrType === 'media'){
              input.value = '';
              updateMediaInfo(input, value, false);
            } else {
              input.value = value;
            }
          });
          if(memberForm.elements['status']){
            memberForm.elements['status'].value = data.status || 'in_work';
          }
          setModalTitle(data.id ? 'member_edit.title_edit' : 'member_edit.title_add');
          memberModal.show();
        });
      });
      memberForm.addEventListener('change', function(event){
        const target = event.target;
        if(!(target instanceof HTMLInputElement)){
          return;
        }
        if(target.dataset.attributeType === 'media'){
          const name = target.files && target.files.length ? target.files[0].name : '';
          updateMediaInfo(target, name, true);
        }
      });
    }
    <?php if($isManager): ?>
    const editExtraBtn = document.getElementById('editExtraAttributesBtn');
    const extraAttributesModalEl = document.getElementById('extraAttributesModal');
    const extraAttributesForm = document.getElementById('extraAttributesForm');
    const extraAttributesList = document.getElementById('extraAttributesList');
    const addExtraAttributeBtn = document.getElementById('addExtraAttribute');
    if(editExtraBtn && extraAttributesModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal){
      const extraModal = new bootstrap.Modal(extraAttributesModalEl);
      const cloneAttributes = (list) => Array.isArray(list) ? list.map(attr => ({
        id: attr.id ?? null,
        name_zh: attr.name_zh ?? '',
        name_en: attr.name_en ?? '',
        attribute_type: attr.attribute_type ?? 'text',
        default_value: attr.default_value ?? ''
      })) : [];
      let workingAttributes = cloneAttributes(window.memberExtraAttributes || []);
      const getLang = () => document.documentElement.lang || 'zh';
      const translationsFor = (key, fallback) => {
        const lang = getLang();
        return translations?.[lang]?.[key] ?? fallback;
      };
      const validationMessage = () => translationsFor('members.extra.validation', '请为每个属性提供中文或英文名称。');
      const saveErrorMessage = () => translationsFor('members.extra.save_error', '保存失败，请稍后重试。');
      const emptyMessage = () => translationsFor('members.extra.empty', '暂无额外属性。');
      function escapeHtml(text){
        return String(text ?? '').replace(/[&<>"']/g, function(ch){
          switch(ch){
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            case "'": return '&#39;';
            default: return ch;
          }
        });
      }
      function renderExtraAttributes(){
        if(!extraAttributesList){
          return;
        }
        extraAttributesList.innerHTML='';
        if(!workingAttributes.length){
          const emptyDiv=document.createElement('div');
          emptyDiv.className='text-muted';
          emptyDiv.setAttribute('data-i18n','members.extra.empty');
          emptyDiv.textContent = emptyMessage();
          extraAttributesList.appendChild(emptyDiv);
          if(typeof applyTranslations==='function'){
            applyTranslations();
          }
          return;
        }
        workingAttributes.forEach(function(attr,index){
          const wrapper=document.createElement('div');
          wrapper.className='border rounded p-3 mb-3';
          wrapper.dataset.index=String(index);
          const isMedia = String(attr.attribute_type ?? 'text') === 'media';
          wrapper.innerHTML=`<div class="row g-3 align-items-end">
  <div class="col-md-3">
    <label class="form-label" data-i18n="members.extra.field.name_zh">中文名称</label>
    <input type="text" class="form-control" data-field="name_zh" value="${escapeHtml(attr.name_zh)}">
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="members.extra.field.name_en">英文名称</label>
    <input type="text" class="form-control" data-field="name_en" value="${escapeHtml(attr.name_en)}">
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="members.extra.field.type">属性类型</label>
    <select class="form-select" data-field="attribute_type">
      <option value="text" ${isMedia ? '' : 'selected'} data-i18n="members.extra.type.text">文本</option>
      <option value="media" ${isMedia ? 'selected' : ''} data-i18n="members.extra.type.media">多媒体</option>
    </select>
  </div>
  <div data-default-wrapper class="col-md-3${isMedia ? ' d-none' : ''}">
    <label class="form-label" data-i18n="members.extra.field.default_value">默认值</label>
    <input type="text" class="form-control" data-field="default_value" value="${escapeHtml(isMedia ? '' : attr.default_value)}">
  </div>
  <div class="col-12 d-flex justify-content-end mt-2">
    <button type="button" class="btn btn-sm btn-outline-danger extra-attr-delete" data-index="${index}" data-i18n="members.extra.delete">删除</button>
  </div>
</div>`;
          extraAttributesList.appendChild(wrapper);
        });
        if(typeof applyTranslations==='function'){
          applyTranslations();
        }
      }
      editExtraBtn.addEventListener('click', function(){
        workingAttributes = cloneAttributes(window.memberExtraAttributes || []);
        renderExtraAttributes();
        extraModal.show();
      });
      addExtraAttributeBtn?.addEventListener('click', function(){
        workingAttributes.push({id:null,name_zh:'',name_en:'',attribute_type:'text',default_value:''});
        renderExtraAttributes();
      });
      extraAttributesList?.addEventListener('input', function(event){
        const target = event.target;
        if(!(target instanceof HTMLInputElement)){
          return;
        }
        const row = target.closest('[data-index]');
        if(!row){
          return;
        }
        const index = Number(row.dataset.index);
        if(Number.isNaN(index) || !workingAttributes[index]){
          return;
        }
        const field = target.getAttribute('data-field');
        if(!field){
          return;
        }
        workingAttributes[index][field] = target.value;
      });
      extraAttributesList?.addEventListener('change', function(event){
        const target = event.target;
        if(!(target instanceof HTMLSelectElement)){
          return;
        }
        const row = target.closest('[data-index]');
        if(!row){
          return;
        }
        const index = Number(row.dataset.index);
        if(Number.isNaN(index) || !workingAttributes[index]){
          return;
        }
        const field = target.getAttribute('data-field');
        if(!field){
          return;
        }
        workingAttributes[index][field] = target.value;
        if(field === 'attribute_type'){
          workingAttributes[index].default_value = target.value === 'media' ? '' : (workingAttributes[index].default_value ?? '');
          const defaultWrapper = row.querySelector('[data-default-wrapper]');
          if(defaultWrapper){
            if(target.value === 'media'){
              defaultWrapper.classList.add('d-none');
            } else {
              defaultWrapper.classList.remove('d-none');
            }
          }
          const defaultInput = row.querySelector('[data-field="default_value"]');
          if(defaultInput instanceof HTMLInputElement && target.value === 'media'){
            defaultInput.value = '';
          }
        }
      });
      extraAttributesList?.addEventListener('click', function(event){
        const deleteBtn = event.target.closest('.extra-attr-delete');
        if(!deleteBtn){
          return;
        }
        const index = Number(deleteBtn.dataset.index);
        if(Number.isNaN(index)){
          return;
        }
        workingAttributes.splice(index, 1);
        renderExtraAttributes();
      });
      extraAttributesForm?.addEventListener('submit', function(event){
        event.preventDefault();
        const payload = workingAttributes.map(function(attr){
          return {
            id: attr.id ?? null,
            name_zh: String(attr.name_zh ?? '').trim(),
            name_en: String(attr.name_en ?? '').trim(),
            attribute_type: attr.attribute_type ?? 'text',
            default_value: String(attr.default_value ?? '')
          };
        });
        const validCount = payload.filter(item => item.name_zh !== '' || item.name_en !== '').length;
        if(payload.length !== validCount){
          alert(validationMessage());
          return;
        }
        fetch('member_extra_attributes.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({attributes: payload})
        }).then(response => response.json()).then(data => {
          if(data?.success){
            window.location.reload();
          } else {
            alert(data?.error || saveErrorMessage());
          }
        }).catch(() => {
          alert(saveErrorMessage());
        });
      });
    }
    <?php endif; ?>
  });
  </script>
  <?php include 'footer.php'; ?>
