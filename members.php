<?php
require_once 'member_attribute_helpers.php';
include 'header.php';

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

$customAttributes = fetch_member_attributes($pdo);
$memberIds = array_column($members, 'id');
$memberAttributeMap = $memberIds ? fetch_member_attribute_map($pdo, $memberIds) : [];

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
    <button type="button" class="btn btn-outline-primary" id="manageAttributesBtn" data-i18n="members.attributes.manage">编辑属性</button>
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
    <?php foreach($customAttributes as $attr): ?>
      <th>
        <div><?= htmlspecialchars($attr['label_zh']); ?></div>
        <div class="text-muted small"><?= htmlspecialchars($attr['label_en']); ?></div>
      </th>
    <?php endforeach; ?>
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
    <?php foreach($customAttributes as $attr): ?>
      <td><?= htmlspecialchars((string)($memberAttributeMap[$m['id']][$attr['id']] ?? $attr['default_value'])); ?></td>
    <?php endforeach; ?>
    <td>
      <button type="button" class="btn btn-sm btn-primary btn-edit-member" data-id="<?= $m['id']; ?>" data-i18n="members.action.edit">编辑</button>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="memberModalTitle" data-i18n="member_edit.title_add">新增成员</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" id="memberFormError"></div>
          <form id="memberForm" class="row g-3">
            <input type="hidden" name="id" id="memberIdField">
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.campus_id">一卡通号</label>
              <input type="text" name="campus_id" class="form-control" id="memberCampusId" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.name">姓名</label>
              <input type="text" name="name" class="form-control" id="memberName" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.email">正式邮箱</label>
              <input type="email" name="email" class="form-control" id="memberEmail">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.identity_number">身份证号</label>
              <input type="text" name="identity_number" class="form-control" id="memberIdentity">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.year_of_join">入学年份</label>
              <input type="number" name="year_of_join" class="form-control" id="memberJoinYear">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.current_degree">已获学位</label>
              <input type="text" name="current_degree" class="form-control" id="memberCurrentDegree">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.degree_pursuing">当前学历</label>
              <input type="text" name="degree_pursuing" class="form-control" id="memberDegreePursuing">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.phone">手机号</label>
              <input type="text" name="phone" class="form-control" id="memberPhone">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.wechat">微信号</label>
              <input type="text" name="wechat" class="form-control" id="memberWechat">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.department">所处学院/单位</label>
              <input type="text" name="department" class="form-control" id="memberDepartment">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.workplace">工作地点</label>
              <input type="text" name="workplace" class="form-control" id="memberWorkplace">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.homeplace">家庭住址</label>
              <input type="text" name="homeplace" class="form-control" id="memberHomeplace">
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="members.table.status">状态</label>
              <select name="status" class="form-select" id="memberStatus">
                <option value="in_work" data-i18n="members.status.in_work">在岗</option>
                <option value="exited" data-i18n="members.status.exited">已离退</option>
              </select>
            </div>
            <div class="col-12">
              <hr>
              <h6 class="mb-3" data-i18n="members.attributes.section_title">成员自定义属性</h6>
              <div id="memberAttributesContainer" class="row g-3"></div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="member_edit.cancel">取消</button>
          <button type="submit" class="btn btn-primary" form="memberForm" data-i18n="member_edit.save">保存</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="attributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="members.attributes.manage">编辑属性</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" id="attributeError"></div>
          <form id="attributeForm" class="border rounded p-3 mb-3 bg-light">
            <input type="hidden" id="attributeId">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.attributes.label_zh">中文名称</label>
                <input type="text" class="form-control" id="attributeLabelZh" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="members.attributes.label_en">英文名称</label>
                <input type="text" class="form-control" id="attributeLabelEn" required>
              </div>
              <div class="col-12">
                <label class="form-label" data-i18n="members.attributes.default">默认值</label>
                <input type="text" class="form-control" id="attributeDefault">
              </div>
            </div>
            <div class="form-check form-switch my-3">
              <input class="form-check-input" type="checkbox" id="attributeApplyDefault">
              <label class="form-check-label" for="attributeApplyDefault" data-i18n="members.attributes.apply_default">将默认值同步到所有成员</label>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary" id="attributeFormReset" data-i18n="members.attributes.reset">重置</button>
              <button type="submit" class="btn btn-primary" data-i18n="members.attributes.save">保存</button>
            </div>
          </form>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0" data-i18n="members.attributes.current">已配置属性</h6>
            <button type="button" class="btn btn-sm btn-success" id="attributeAddBtn" data-i18n="members.attributes.add">新增属性</button>
          </div>
          <ul class="list-group" id="attributeList"></ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="members.attributes.close">关闭</button>
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

    const memberModalEl = document.getElementById('memberModal');
    const memberModal = memberModalEl ? new bootstrap.Modal(memberModalEl) : null;
    const memberForm = document.getElementById('memberForm');
    const memberFormError = document.getElementById('memberFormError');
    const memberModalTitle = document.getElementById('memberModalTitle');
    const memberIdField = document.getElementById('memberIdField');
    const memberAttributesContainer = document.getElementById('memberAttributesContainer');
    const fieldRefs = {
      campus_id: document.getElementById('memberCampusId'),
      name: document.getElementById('memberName'),
      email: document.getElementById('memberEmail'),
      identity_number: document.getElementById('memberIdentity'),
      year_of_join: document.getElementById('memberJoinYear'),
      current_degree: document.getElementById('memberCurrentDegree'),
      degree_pursuing: document.getElementById('memberDegreePursuing'),
      phone: document.getElementById('memberPhone'),
      wechat: document.getElementById('memberWechat'),
      department: document.getElementById('memberDepartment'),
      workplace: document.getElementById('memberWorkplace'),
      homeplace: document.getElementById('memberHomeplace'),
      status: document.getElementById('memberStatus')
    };

    function resetMemberForm(){
      if(!memberForm) return;
      memberForm.reset();
      memberIdField.value='';
      memberAttributesContainer.innerHTML='';
      if(memberFormError){
        memberFormError.classList.add('d-none');
        memberFormError.textContent='';
      }
    }

    function setMemberModalTitle(key){
      if(!memberModalTitle) return;
      memberModalTitle.setAttribute('data-i18n', key);
      window.applyTranslations?.();
    }

    function renderAttributeInputs(attributes, values){
      if(!memberAttributesContainer) return;
      memberAttributesContainer.innerHTML='';
      if(!attributes || attributes.length===0){
        const empty=document.createElement('div');
        empty.className='text-muted';
        empty.setAttribute('data-i18n','members.attributes.none');
        empty.textContent=t('members.attributes.none','暂无自定义属性');
        memberAttributesContainer.appendChild(empty);
        window.applyTranslations?.();
        return;
      }
      attributes.forEach(attr=>{
        const col=document.createElement('div');
        col.className='col-md-6';
        const wrapper=document.createElement('div');
        wrapper.className='mb-3';
        const label=document.createElement('label');
        label.className='form-label';
        label.innerHTML=`${attr.label_zh}<br><small class="text-muted">${attr.label_en}</small>`;
        const input=document.createElement('input');
        input.type='text';
        input.className='form-control';
        input.value=values?.[attr.id] ?? attr.default_value ?? '';
        input.dataset.attributeId=attr.id;
        wrapper.appendChild(label);
        wrapper.appendChild(input);
        col.appendChild(wrapper);
        memberAttributesContainer.appendChild(col);
      });
    }

    function openMemberModal(id=null){
      if(!memberModal) return;
      resetMemberForm();
      setMemberModalTitle(id ? 'member_edit.title_edit' : 'member_edit.title_add');
      const url=id?`member_edit.php?id=${id}`:'member_edit.php';
      fetch(url, {headers:{'Accept':'application/json'}})
        .then(resp=>resp.json())
        .then(data=>{
          if(!data.success){
            throw new Error('load');
          }
          const info=data.member||{};
          memberIdField.value=info.id||'';
          Object.entries(fieldRefs).forEach(([key, input])=>{
            if(!input) return;
            if(key==='status'){
              input.value=info[key]||'in_work';
            } else {
              input.value=info[key] ?? '';
            }
          });
          renderAttributeInputs(data.attributes||[], data.attribute_values||{});
          memberModal.show();
          window.applyTranslations?.();
        })
        .catch(()=>{
          if(memberFormError){
            memberFormError.textContent=t('member_edit.load_failed','成员信息加载失败');
            memberFormError.classList.remove('d-none');
          }
          memberModal.show();
        });
    }

    const addMemberBtn=document.getElementById('addMemberBtn');
    if(addMemberBtn){
      addMemberBtn.addEventListener('click',()=>openMemberModal());
    }
    document.querySelectorAll('.btn-edit-member').forEach(btn=>{
      btn.addEventListener('click',()=>openMemberModal(btn.dataset.id));
    });

    if(memberForm){
      memberForm.addEventListener('submit',function(ev){
        ev.preventDefault();
        if(memberFormError){
          memberFormError.classList.add('d-none');
          memberFormError.textContent='';
        }
        const formData=new FormData(memberForm);
        const payload={};
        formData.forEach((value,key)=>{payload[key]=value;});
        const attrValues={};
        memberAttributesContainer?.querySelectorAll('[data-attribute-id]').forEach(input=>{
          attrValues[input.dataset.attributeId]=input.value;
        });
        payload.attributes=attrValues;
        fetch('member_edit.php',{
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body:JSON.stringify(payload)
        }).then(resp=>resp.json()).then(data=>{
          if(!data.success){
            throw new Error(data.message||'save');
          }
          memberModal?.hide();
          window.location.reload();
        }).catch(err=>{
          if(memberFormError){
            memberFormError.textContent=err.message==='save'?t('member_edit.error_generic','保存失败，请重试'):err.message;
            memberFormError.classList.remove('d-none');
          }
        });
      });
    }

    const attributeModalEl=document.getElementById('attributeModal');
    const attributeModal=attributeModalEl?new bootstrap.Modal(attributeModalEl):null;
    const attributeError=document.getElementById('attributeError');
    const attributeForm=document.getElementById('attributeForm');
    const attributeIdField=document.getElementById('attributeId');
    const attributeLabelZh=document.getElementById('attributeLabelZh');
    const attributeLabelEn=document.getElementById('attributeLabelEn');
    const attributeDefault=document.getElementById('attributeDefault');
    const attributeApplyDefault=document.getElementById('attributeApplyDefault');
    const attributeFormReset=document.getElementById('attributeFormReset');
    const attributeAddBtn=document.getElementById('attributeAddBtn');
    const attributeList=document.getElementById('attributeList');
    let attributeCache = <?php echo json_encode($customAttributes, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];

    function resetAttributeForm(isNew=true){
      if(!attributeForm) return;
      attributeIdField.value='';
      attributeLabelZh.value='';
      attributeLabelEn.value='';
      attributeDefault.value='';
      attributeApplyDefault.checked=isNew;
      attributeApplyDefault.disabled=isNew;
      if(attributeError){
        attributeError.classList.add('d-none');
        attributeError.textContent='';
      }
      window.applyTranslations?.();
    }

    function fillAttributeForm(attr){
      attributeIdField.value=attr.id;
      attributeLabelZh.value=attr.label_zh;
      attributeLabelEn.value=attr.label_en;
      attributeDefault.value=attr.default_value ?? '';
      attributeApplyDefault.checked=false;
      attributeApplyDefault.disabled=false;
    }

    function renderAttributeList(){
      if(!attributeList) return;
      attributeList.innerHTML='';
      if(!attributeCache || attributeCache.length===0){
        const empty=document.createElement('li');
        empty.className='list-group-item text-muted';
        empty.setAttribute('data-i18n','members.attributes.none');
        empty.textContent=t('members.attributes.none','暂无自定义属性');
        attributeList.appendChild(empty);
        window.applyTranslations?.();
        return;
      }
      attributeCache.forEach(attr=>{
        const li=document.createElement('li');
        li.className='list-group-item d-flex justify-content-between align-items-center';
        li.dataset.id=attr.id;
        const left=document.createElement('div');
        left.className='flex-grow-1';
        const title=document.createElement('div');
        title.className='fw-semibold';
        title.textContent=`${attr.label_zh}`;
        const subtitle=document.createElement('div');
        subtitle.className='text-muted small';
        subtitle.textContent=attr.label_en;
        const defaultLine=document.createElement('div');
        defaultLine.className='text-muted small';
        defaultLine.innerHTML=`${t('members.attributes.default','默认值')}: ${attr.default_value ?? ''}`;
        const handle=document.createElement('span');
        handle.className='attribute-handle text-muted me-3';
        handle.innerHTML='&#9776;';
        left.appendChild(title);
        left.appendChild(subtitle);
        left.appendChild(defaultLine);
        li.appendChild(handle);
        li.appendChild(left);
        const btnGroup=document.createElement('div');
        btnGroup.className='btn-group btn-group-sm';
        const editBtn=document.createElement('button');
        editBtn.type='button';
        editBtn.className='btn btn-outline-primary';
        editBtn.setAttribute('data-i18n','members.attributes.edit');
        editBtn.textContent=t('members.attributes.edit','编辑');
        editBtn.addEventListener('click',()=>{
          fillAttributeForm(attr);
        });
        const deleteBtn=document.createElement('button');
        deleteBtn.type='button';
        deleteBtn.className='btn btn-outline-danger';
        deleteBtn.setAttribute('data-i18n','members.attributes.delete');
        deleteBtn.textContent=t('members.attributes.delete','删除');
        deleteBtn.addEventListener('click',()=>{
          if(!confirm(t('members.attributes.confirm_delete','确认删除该属性？'))){
            return;
          }
          fetch('member_attribute_api.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'delete', id:attr.id})
          }).then(resp=>resp.json()).then(data=>{
            if(!data.success){throw new Error(data.message||'delete');}
            window.location.reload();
          }).catch(err=>{
            if(attributeError){
              attributeError.textContent=err.message;
              attributeError.classList.remove('d-none');
            }
          });
        });
        btnGroup.appendChild(editBtn);
        btnGroup.appendChild(deleteBtn);
        li.appendChild(btnGroup);
        attributeList.appendChild(li);
      });
      window.applyTranslations?.();
      Sortable.create(attributeList, {
        handle: '.attribute-handle',
        animation: 150,
        onEnd: function(){
          const order=Array.from(attributeList.querySelectorAll('li[data-id]')).map(li=>li.dataset.id);
          fetch('member_attribute_api.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'reorder', order:order})
          }).then(resp=>resp.json()).then(data=>{
            if(!data.success){throw new Error(data.message||'reorder');}
            window.location.reload();
          }).catch(err=>{
            if(attributeError){
              attributeError.textContent=err.message;
              attributeError.classList.remove('d-none');
            }
          });
        }
      });
    }

    if(attributeModalEl){
      renderAttributeList();
      resetAttributeForm(true);
    }

    const manageAttributesBtn=document.getElementById('manageAttributesBtn');
    if(manageAttributesBtn && attributeModal){
      manageAttributesBtn.addEventListener('click',()=>{
        resetAttributeForm(true);
        attributeModal.show();
        window.applyTranslations?.();
      });
    }

    attributeAddBtn?.addEventListener('click',()=>{
      resetAttributeForm(true);
    });

    attributeFormReset?.addEventListener('click',()=>{
      if(attributeIdField.value){
        const attr=attributeCache.find(item=>String(item.id)===String(attributeIdField.value));
        if(attr){
          fillAttributeForm(attr);
          attributeApplyDefault.checked=false;
          attributeApplyDefault.disabled=false;
          return;
        }
      }
      resetAttributeForm(true);
    });

    attributeForm?.addEventListener('submit',ev=>{
      ev.preventDefault();
      if(attributeError){
        attributeError.classList.add('d-none');
        attributeError.textContent='';
      }
      const payload={
        action: attributeIdField.value ? 'update' : 'create',
        id: attributeIdField.value,
        label_zh: attributeLabelZh.value.trim(),
        label_en: attributeLabelEn.value.trim(),
        default_value: attributeDefault.value,
        apply_default: attributeApplyDefault.checked
      };
      fetch('member_attribute_api.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
      }).then(resp=>resp.json()).then(data=>{
        if(!data.success){throw new Error(data.message||'attribute');}
        window.location.reload();
      }).catch(err=>{
        if(attributeError){
          attributeError.textContent=err.message;
          attributeError.classList.remove('d-none');
        }
      });
    });
  });
  </script>
  <?php endif; ?>
  <?php include 'footer.php'; ?>
