<?php
require_once 'config.php';
include_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['member_action'] ?? '') === 'save') {
    if (($_SESSION['role'] ?? '') !== 'manager') {
        header('Location: members.php');
        exit();
    }

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
    }

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

if($_SESSION['role'] === 'member') {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id=?');
    $stmt->execute([$_SESSION['member_id']]);
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
</style>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="members.title">团队成员</h2>
  <?php if($_SESSION['role'] === 'manager'): ?>
  <div>
    <button type="button" class="btn btn-success" id="addMemberBtn" data-i18n="members.add">新增成员</button>
    <a class="btn btn-secondary" href="members_import.php" data-i18n="members.import">从表格导入</a>
    <a class="btn btn-secondary" href="members_export.php" id="exportMembers" data-i18n="members.export">导出至表格</a>
    <button type="button" class="btn btn-warning qr-btn" data-url="member_self_update.php" data-i18n="members.request_update">请求信息更新</button>
  </div>
  <?php endif; ?>
</div>
<?php if($_SESSION['role'] === 'manager'): ?>
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
<table class="table table-bordered table-striped table-hover">
  <thead>
  <tr>
    <th></th>
    <?php foreach($columns as $col => $info):
        $label = $info['label'];
        $key = $info['key'];
        $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
        if($_SESSION['role'] === 'manager'):
    ?>
      <th><a href="?sort=<?= $col; ?>&amp;dir=<?= $newDir; ?>&amp;status=<?= $statusFilter; ?>" data-i18n="<?= $key; ?>"><?= htmlspecialchars($label); ?></a></th>
    <?php else: ?>
      <th data-i18n="<?= $key; ?>"><?= htmlspecialchars($label); ?></th>
    <?php endif; endforeach; ?>
    <th data-i18n="members.table.actions">操作</th>
  </tr>
  </thead>
  <tbody id="memberList">
  <?php foreach($members as $m): ?>
  <tr data-id="<?= $m['id']; ?>" data-year="<?= htmlspecialchars($m['year_of_join']); ?>" data-degree="<?= htmlspecialchars($m['degree_pursuing']); ?>">
    <?php if($_SESSION['role'] === 'manager'): ?>
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
              data-status="<?= htmlspecialchars((string)($m['status'] ?? 'in_work'), ENT_QUOTES); ?>"
              data-i18n="members.action.edit">编辑</button>
      <?php if($_SESSION['role'] === 'manager'): ?>
      <a class="btn btn-sm btn-danger" href="member_delete.php?id=<?= $m['id']; ?>" onclick="return doubleConfirm(translations[document.documentElement.lang]['members.confirm.remove']);" data-i18n="members.action.remove">移除</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
  </div>
  <?php if($_SESSION['role'] === 'manager'): ?>
  <div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form id="memberForm" method="post">
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
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.table.status">Status</label>
                <select name="status" class="form-select">
                  <option value="in_work" data-i18n="members.status.in_work" selected>In Work</option>
                  <option value="exited" data-i18n="members.status.exited">Exited</option>
                </select>
              </div>
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
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
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
    const exportLink=document.getElementById('exportMembers');
    if(exportLink){
      exportLink.href=`members_export.php?lang=${document.documentElement.lang||'zh'}`;
    }
    const toggleBtn=document.getElementById('toggleColor');
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
    const memberModalElement = document.getElementById('memberModal');
    const memberForm = document.getElementById('memberForm');
    const addMemberBtn = document.getElementById('addMemberBtn');
    const modalTitle = document.getElementById('memberModalTitle');
    if(memberModalElement && memberForm){
      if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        console.warn('Bootstrap modal is not available.');
        return;
      }
      const memberModal = new bootstrap.Modal(memberModalElement);
      const fieldNames = ['campus_id','name','email','identity_number','year_of_join','current_degree','degree_pursuing','phone','wechat','department','workplace','homeplace'];
      function translate(key){
        const lang = document.documentElement.lang || 'zh';
        return (translations?.[lang] && translations[lang][key]) || key;
      }
      function setModalTitle(key){
        if(!modalTitle){
          return;
        }
        modalTitle.setAttribute('data-i18n', key);
        modalTitle.textContent = translate(key);
      }
      function resetForm(){
        memberForm.reset();
        memberForm.elements['member_id'].value = '';
        memberForm.elements['status'].value = 'in_work';
      }
      addMemberBtn?.addEventListener('click', function(){
        resetForm();
        setModalTitle('member_edit.title_add');
        memberModal.show();
      });
      document.querySelectorAll('.member-edit-btn').forEach(function(btn){
        btn.addEventListener('click', function(event){
          event.preventDefault();
          const data = btn.dataset;
          resetForm();
          memberForm.elements['member_id'].value = data.id || '';
          fieldNames.forEach(function(name){
            const datasetKey = name.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
            memberForm.elements[name].value = data[datasetKey] || '';
          });
          memberForm.elements['status'].value = data.status || 'in_work';
          setModalTitle('member_edit.title_edit');
          memberModal.show();
        });
      });
    }
  });
  </script>
  <?php endif; ?>
  <?php include 'footer.php'; ?>
