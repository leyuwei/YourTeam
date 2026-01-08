<?php
require_once 'config.php';
include_once 'auth.php';
require_once 'publish_helpers.php';

$role = $_SESSION['role'] ?? '';
$isManager = $role === 'manager';
$sessionMemberId = (int)($_SESSION['member_id'] ?? 0);
$attributes = getPublishAttributes($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['publish_action'] ?? '') === 'delete') {
    $entryId = isset($_POST['entry_id']) && $_POST['entry_id'] !== ''
        ? (int)$_POST['entry_id']
        : 0;

    if ($entryId > 0) {
        if ($isManager) {
            $deleteStmt = $pdo->prepare('DELETE FROM publish_entries WHERE id = ?');
            $deleteStmt->execute([$entryId]);
        } else {
            $deleteStmt = $pdo->prepare('DELETE FROM publish_entries WHERE id = ? AND member_id = ?');
            $deleteStmt->execute([$entryId, $sessionMemberId]);
        }
    }

    header('Location: publish.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['publish_action'] ?? '') === 'save') {
    $entryId = isset($_POST['entry_id']) && $_POST['entry_id'] !== ''
        ? (int)$_POST['entry_id']
        : null;

    $memberId = $isManager
        ? (int)($_POST['member_id'] ?? 0)
        : $sessionMemberId;

    if ($memberId <= 0) {
        header('Location: publish.php');
        exit();
    }

    $existingValues = [];
    if ($entryId) {
        $stmt = $pdo->prepare('SELECT id, member_id FROM publish_entries WHERE id = ?');
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry) {
            header('Location: publish.php');
            exit();
        }
        if (!$isManager && (int)$entry['member_id'] !== $sessionMemberId) {
            header('Location: publish.php');
            exit();
        }
        if ($isManager && $memberId !== (int)$entry['member_id']) {
            $updateStmt = $pdo->prepare('UPDATE publish_entries SET member_id = ? WHERE id = ?');
            $updateStmt->execute([$memberId, $entryId]);
        }
        $existingValues = getPublishValues($pdo, [$entryId]);
        $existingValues = $existingValues[$entryId] ?? [];
    } else {
        $insertStmt = $pdo->prepare('INSERT INTO publish_entries (member_id) VALUES (?)');
        $insertStmt->execute([$memberId]);
        $entryId = (int)$pdo->lastInsertId();
        $existingValues = [];
    }

    $postedValues = isset($_POST['publish_attrs']) && is_array($_POST['publish_attrs']) ? $_POST['publish_attrs'] : [];
    $uploadedValues = $_FILES['publish_attrs'] ?? null;
    $rawClearFlags = isset($_POST['publish_clear']) && is_array($_POST['publish_clear']) ? $_POST['publish_clear'] : [];
    $clearFlags = [];
    foreach ($rawClearFlags as $clearId => $flag) {
        if ($flag === '1' || $flag === 1 || $flag === true || $flag === 'true') {
            $clearFlags[(int)$clearId] = true;
        }
    }

    $preparedValues = preparePublishValues((int)$entryId, $attributes, $postedValues, $uploadedValues, $existingValues, $clearFlags);
    ensurePublishValues($pdo, (int)$entryId, $preparedValues, $attributes);

    header('Location: publish.php');
    exit();
}

if ($isManager) {
    $stmt = $pdo->query('SELECT e.*, m.name AS member_name FROM publish_entries e JOIN members m ON e.member_id = m.id ORDER BY e.updated_at DESC, e.id DESC');
    $entries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $memberStmt = $pdo->query('SELECT id, name FROM members ORDER BY sort_order, id');
    $members = $memberStmt ? $memberStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} else {
    $stmt = $pdo->prepare('SELECT e.* FROM publish_entries e WHERE e.member_id = ? ORDER BY e.updated_at DESC, e.id DESC');
    $stmt->execute([$sessionMemberId]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $members = [];
}

$entryIds = array_column($entries, 'id');
$valuesMap = getPublishValues($pdo, $entryIds);

include 'header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h2 class="bold-target mb-1" data-i18n="publish.title">Publish</h2>
    <p class="text-muted mb-0" data-i18n="publish.subtitle">Manage achievements and upload supporting materials.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if ($isManager): ?>
      <button type="button" class="btn btn-outline-secondary" id="downloadPublishBtn" data-download-url="publish_download.php" data-i18n="publish.download_all">Download All Files</button>
      <a class="btn btn-outline-secondary" href="publish_export.php" data-i18n="publish.export">Export to Excel</a>
      <button type="button" class="btn btn-outline-primary" id="editPublishAttributesBtn" data-i18n="publish.attributes.edit">Edit Attributes</button>
    <?php endif; ?>
    <button type="button" class="btn btn-success" id="addPublishBtn" data-i18n="publish.add">Add Achievement</button>
  </div>
</div>

<style>
  .publish-sortable {
    cursor: pointer;
    user-select: none;
  }
  .publish-sort-indicator {
    font-size: 0.75rem;
    margin-left: 0.25rem;
  }
</style>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead>
      <tr>
        <?php if ($isManager): ?>
          <th data-i18n="publish.table.member">Member</th>
        <?php endif; ?>
        <?php foreach ($attributes as $attr):
          $nameEn = trim((string)$attr['name_en']);
          $nameZh = trim((string)$attr['name_zh']);
          $display = $nameZh !== '' ? $nameZh : ($nameEn !== '' ? $nameEn : '');
        ?>
          <th class="publish-sortable" role="button" tabindex="0" data-attr-id="<?= (int)$attr['id']; ?>" data-publish-name-zh="<?= htmlspecialchars($nameZh, ENT_QUOTES); ?>" data-publish-name-en="<?= htmlspecialchars($nameEn, ENT_QUOTES); ?>"><?= htmlspecialchars($display); ?><span class="publish-sort-indicator" aria-hidden="true"></span></th>
        <?php endforeach; ?>
        <th data-i18n="publish.table.updated">Updated</th>
        <th data-i18n="publish.table.actions">Actions</th>
      </tr>
      <tr class="publish-filter-row">
        <?php if ($isManager): ?>
          <th></th>
        <?php endif; ?>
        <?php foreach ($attributes as $attr):
          $attrId = (int)$attr['id'];
          $attrType = $attr['attribute_type'] ?? 'text';
          $optionsRaw = (string)($attr['options'] ?? '');
          $optionsList = array_values(array_filter(array_map('trim', explode(',', $optionsRaw))));
        ?>
          <th>
            <?php if ($attrType === 'select'): ?>
              <select class="form-select form-select-sm" data-publish-filter data-filter-id="<?= $attrId; ?>" data-filter-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
                <option value="" data-i18n="publish.filter.all">All</option>
                <?php foreach ($optionsList as $optionValue): ?>
                  <option value="<?= htmlspecialchars($optionValue, ENT_QUOTES); ?>"><?= htmlspecialchars($optionValue, ENT_QUOTES); ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($attrType === 'date'): ?>
              <input type="date" class="form-control form-control-sm" data-publish-filter data-filter-id="<?= $attrId; ?>" data-filter-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
            <?php elseif ($attrType === 'file'): ?>
              <select class="form-select form-select-sm" data-publish-filter data-filter-id="<?= $attrId; ?>" data-filter-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
                <option value="" data-i18n="publish.filter.all">All</option>
                <option value="has" data-i18n="publish.filter.has_file">Has file</option>
                <option value="empty" data-i18n="publish.filter.no_file">No file</option>
              </select>
            <?php else: ?>
              <input type="text" class="form-control form-control-sm" data-publish-filter data-filter-id="<?= $attrId; ?>" data-filter-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>" data-i18n-placeholder="publish.filter.placeholder" placeholder="Filter">
            <?php endif; ?>
          </th>
        <?php endforeach; ?>
        <th></th>
        <th>
          <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="publishClearFilters" data-i18n="publish.filter.clear">Clear Filters</button>
        </th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($entries)): ?>
        <tr>
          <td colspan="<?= count($attributes) + ($isManager ? 3 : 2); ?>" class="text-center text-muted" data-i18n="publish.empty">No achievements yet.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($entries as $entry):
          $entryId = (int)($entry['id'] ?? 0);
          $rowValues = $valuesMap[$entryId] ?? [];
          $displayValues = [];
          foreach ($attributes as $attr) {
            $attrId = (int)$attr['id'];
            $attrType = $attr['attribute_type'] ?? 'text';
            $displayValues[$attrId] = (string)($rowValues[$attrId] ?? ($attrType === 'file' ? '' : (string)($attr['default_value'] ?? '')));
          }
          $rowValueJson = htmlspecialchars(json_encode($rowValues, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
          $rowDisplayJson = htmlspecialchars(json_encode($displayValues, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        ?>
          <tr data-publish-values="<?= $rowDisplayJson; ?>">
            <?php if ($isManager): ?>
              <td><?= htmlspecialchars($entry['member_name'] ?? '', ENT_QUOTES); ?></td>
            <?php endif; ?>
            <?php foreach ($attributes as $attr):
              $attrId = (int)$attr['id'];
              $attrType = $attr['attribute_type'] ?? 'text';
              $value = (string)($displayValues[$attrId] ?? '');
            ?>
              <td>
                <?php if ($attrType === 'file'): ?>
                  <?php if ($value === ''): ?>
                    <span class="text-muted" data-i18n="publish.no_file">No file</span>
                  <?php else: ?>
                    <a href="<?= htmlspecialchars($value, ENT_QUOTES); ?>" target="_blank" rel="noopener" class="text-decoration-none" data-i18n="publish.view_file">View file</a>
                  <?php endif; ?>
                <?php else: ?>
                  <?= htmlspecialchars($value, ENT_QUOTES); ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td><?= htmlspecialchars($entry['updated_at'] ?? '', ENT_QUOTES); ?></td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-primary publish-edit-btn"
                      data-id="<?= $entryId; ?>"
                      data-member-id="<?= (int)($entry['member_id'] ?? 0); ?>"
                      data-values="<?= $rowValueJson; ?>"
                      data-i18n="publish.edit">Edit</button>
              <form method="post" class="d-inline" onsubmit="return doubleConfirm(translations[document.documentElement.lang||'zh']['publish.confirm_delete']);">
                <input type="hidden" name="publish_action" value="delete">
                <input type="hidden" name="entry_id" value="<?= $entryId; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-i18n="publish.delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr id="publishFilterEmpty" class="d-none">
          <td colspan="<?= count($attributes) + ($isManager ? 3 : 2); ?>" class="text-center text-muted" data-i18n="publish.filtered_empty">No matching achievements.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="publishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="publishForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="publishModalTitle" data-i18n="publish.modal.add">Add Achievement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="publish_action" value="save">
          <input type="hidden" name="entry_id" value="">
          <?php if ($isManager): ?>
            <div class="mb-3">
              <label class="form-label" for="publishMemberSelect" data-i18n="publish.form.member">Member</label>
              <select class="form-select" id="publishMemberSelect" name="member_id">
                <option value="" data-i18n="publish.form.member_placeholder">Select a member</option>
                <?php foreach ($members as $member): ?>
                  <option value="<?= (int)$member['id']; ?>"><?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else: ?>
            <input type="hidden" name="member_id" value="<?= $sessionMemberId; ?>">
          <?php endif; ?>
          <div class="row g-3">
            <?php foreach ($attributes as $attr):
              $attrId = (int)$attr['id'];
              $attrType = $attr['attribute_type'] ?? 'text';
              $nameEn = trim((string)$attr['name_en']);
              $nameZh = trim((string)$attr['name_zh']);
              $display = $nameZh !== '' ? $nameZh : ($nameEn !== '' ? $nameEn : '');
              $defaultValue = $attrType === 'file' ? '' : (string)($attr['default_value'] ?? '');
              $optionsRaw = (string)($attr['options'] ?? '');
              $optionsList = array_values(array_filter(array_map('trim', explode(',', $optionsRaw))));
            ?>
              <div class="col-md-6" data-publish-wrapper>
                <label class="form-label" data-publish-name-zh="<?= htmlspecialchars($nameZh, ENT_QUOTES); ?>" data-publish-name-en="<?= htmlspecialchars($nameEn, ENT_QUOTES); ?>"><?= htmlspecialchars($display); ?></label>
                <?php if ($attrType === 'textarea'): ?>
                  <textarea class="form-control" name="publish_attrs[<?= $attrId; ?>]" rows="3" data-publish-field data-attribute-id="<?= $attrId; ?>" data-default-value="<?= htmlspecialchars($defaultValue, ENT_QUOTES); ?>" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>"></textarea>
                <?php elseif ($attrType === 'date'): ?>
                  <input type="date" class="form-control" name="publish_attrs[<?= $attrId; ?>]" value="" data-publish-field data-attribute-id="<?= $attrId; ?>" data-default-value="<?= htmlspecialchars($defaultValue, ENT_QUOTES); ?>" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
                <?php elseif ($attrType === 'select'): ?>
                  <select class="form-select" name="publish_attrs[<?= $attrId; ?>]" data-publish-field data-attribute-id="<?= $attrId; ?>" data-default-value="<?= htmlspecialchars($defaultValue, ENT_QUOTES); ?>" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
                    <?php foreach ($optionsList as $optionValue): ?>
                      <option value="<?= htmlspecialchars($optionValue, ENT_QUOTES); ?>"><?= htmlspecialchars($optionValue, ENT_QUOTES); ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif ($attrType === 'file'): ?>
                  <input type="file" class="form-control" name="publish_attrs[<?= $attrId; ?>]" data-publish-field data-attribute-id="<?= $attrId; ?>" data-default-value="" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.7z,.tar,.gz">
                  <div class="form-text" data-i18n="publish.form.file_hint">Upload supporting files or screenshots.</div>
                  <div class="small text-muted d-none" data-publish-current-file data-i18n="publish.no_file"></div>
                  <input type="hidden" name="publish_clear[<?= $attrId; ?>]" value="0" data-publish-clear-flag data-attribute-id="<?= $attrId; ?>">
                  <button type="button" class="btn btn-sm btn-outline-danger mt-2" data-publish-clear-btn data-attribute-id="<?= $attrId; ?>" data-i18n="publish.form.clear_file">Clear File</button>
                <?php else: ?>
                  <input type="text" class="form-control" name="publish_attrs[<?= $attrId; ?>]" value="" data-publish-field data-attribute-id="<?= $attrId; ?>" data-default-value="<?= htmlspecialchars($defaultValue, ENT_QUOTES); ?>" data-attribute-type="<?= htmlspecialchars($attrType, ENT_QUOTES); ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="publish.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" data-i18n="publish.save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($isManager): ?>
<div class="modal fade" id="publishAttributesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="publishAttributesForm">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="publish.attributes.modal_title">Edit Attributes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted" data-i18n="publish.attributes.description">Define the fields required for each achievement.</p>
          <div id="publishAttributesList" class="mt-3"></div>
          <button type="button" class="btn btn-outline-secondary mt-3" id="addPublishAttribute" data-i18n="publish.attributes.add">Add Attribute</button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="publish.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" data-i18n="publish.save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="publishDownloadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="publish.download.empty_title">No files available</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0" data-i18n="publish.download.empty_body">No achievement files are available to download yet.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-i18n="publish.download.empty_confirm">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.publishAttributes = <?= json_encode($attributes, JSON_UNESCAPED_UNICODE); ?>;
  document.addEventListener('DOMContentLoaded', function(){
    const publishModalElement = document.getElementById('publishModal');
    const publishForm = document.getElementById('publishForm');
    const addPublishBtn = document.getElementById('addPublishBtn');
    const modalTitle = document.getElementById('publishModalTitle');
    const isManager = <?= $isManager ? 'true' : 'false'; ?>;
    function translateWithFallback(key, fallback){
      const lang = document.documentElement.lang || 'zh';
      return (translations?.[lang] && translations[lang][key]) || fallback;
    }
    function translate(key){
      return translateWithFallback(key, key);
    }
    const canUseBootstrap = typeof bootstrap !== 'undefined' && bootstrap.Modal;
    if(!canUseBootstrap){
      console.warn('Bootstrap modal is not available.');
    }
    if(publishModalElement && publishForm && canUseBootstrap){
      const publishModal = new bootstrap.Modal(publishModalElement);
      const fieldInputs = Array.from(publishForm.querySelectorAll('[data-publish-field]'));
      const memberSelect = publishForm.querySelector('#publishMemberSelect');
      function setClearFlag(wrapper, enabled){
        const clearField = wrapper ? wrapper.querySelector('[data-publish-clear-flag]') : null;
        if (clearField) {
          clearField.value = enabled ? '1' : '0';
        }
      }
      function updateFileInfo(input, value, isSelection, isClearing){
        const wrapper = input.closest('[data-publish-wrapper]');
        const info = wrapper ? wrapper.querySelector('[data-publish-current-file]') : null;
        if(!info){
          return;
        }
        const currentLabel = translateWithFallback('publish.form.current_file', '当前文件');
        const noneLabel = translateWithFallback('publish.no_file', '暂无文件');
        const selectedLabel = translateWithFallback('publish.form.selected_file', '已选择文件');
        const clearingLabel = translateWithFallback('publish.form.will_clear', '将删除当前文件');
        if(isClearing){
          info.textContent = clearingLabel;
        } else if(value){
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
      function resetFields(){
        publishForm.reset();
        publishForm.elements['entry_id'].value = '';
        fieldInputs.forEach(function(input){
          const attrType = input.dataset.attributeType || 'text';
          const wrapper = input.closest('[data-publish-wrapper]');
          if(attrType === 'file'){
            input.value = '';
            setClearFlag(wrapper, false);
            updateFileInfo(input, '', false, false);
          } else {
            const defaultValue = input.dataset.defaultValue ?? '';
            if(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement || input instanceof HTMLSelectElement){
              input.value = defaultValue;
            }
          }
        });
        if(memberSelect){
          memberSelect.value = '';
        }
      }
      if(addPublishBtn){
        addPublishBtn.addEventListener('click', function(){
          resetFields();
          setModalTitle('publish.modal.add');
          publishModal.show();
        });
      }
      document.querySelectorAll('.publish-edit-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
          const data = btn.dataset;
          resetFields();
          publishForm.elements['entry_id'].value = data.id || '';
          if(memberSelect){
            memberSelect.value = data.memberId || '';
          }
          let values = {};
          if(data.values){
            try {
              values = JSON.parse(data.values);
            } catch (err) {
              values = {};
            }
          }
          fieldInputs.forEach(function(input){
            const attrId = input.dataset.attributeId;
            const attrType = input.dataset.attributeType || 'text';
            const defaultValue = input.dataset.defaultValue ?? '';
            const wrapper = input.closest('[data-publish-wrapper]');
            const value = Object.prototype.hasOwnProperty.call(values, attrId) ? values[attrId] : defaultValue;
            if(attrType === 'file'){
              input.value = '';
              setClearFlag(wrapper, false);
              updateFileInfo(input, value, false, false);
            } else if(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement || input instanceof HTMLSelectElement){
              input.value = value ?? '';
            }
          });
          setModalTitle('publish.modal.edit');
          publishModal.show();
        });
      });
      fieldInputs.forEach(function(input){
        if(input.getAttribute('type') === 'file'){
          input.addEventListener('change', function(){
            const wrapper = input.closest('[data-publish-wrapper]');
            const fileName = input.files && input.files[0] ? input.files[0].name : '';
            setClearFlag(wrapper, false);
            updateFileInfo(input, fileName, true, false);
          });
        }
      });
      document.querySelectorAll('[data-publish-clear-btn]').forEach(function(btn){
        btn.addEventListener('click', function(){
          const wrapper = btn.closest('[data-publish-wrapper]');
          const input = wrapper ? wrapper.querySelector('input[type="file"][data-publish-field]') : null;
          if(!input){
            return;
          }
          input.value = '';
          setClearFlag(wrapper, true);
          updateFileInfo(input, '', false, true);
        });
      });
    }
    const publishTable = document.querySelector('.table');
    const publishTbody = publishTable?.querySelector('tbody') || null;
    const publishRows = publishTbody ? Array.from(publishTbody.querySelectorAll('tr[data-publish-values]')) : [];
    const publishFilterInputs = Array.from(document.querySelectorAll('[data-publish-filter]'));
    const publishClearFiltersBtn = document.getElementById('publishClearFilters');
    const publishEmptyRow = document.getElementById('publishFilterEmpty');
    const publishRowValuesCache = new WeakMap();
    const attributeTypeMap = new Map();
    (window.publishAttributes || []).forEach(function(attr){
      const id = String(attr.id ?? '');
      if(id){
        attributeTypeMap.set(id, String(attr.attribute_type ?? 'text'));
      }
    });
    function getRowValues(row){
      if(publishRowValuesCache.has(row)){
        return publishRowValuesCache.get(row);
      }
      let values = {};
      if(row?.dataset?.publishValues){
        try {
          values = JSON.parse(row.dataset.publishValues);
        } catch (err) {
          values = {};
        }
      }
      publishRowValuesCache.set(row, values);
      return values;
    }
    function applyPublishFilters(){
      if(!publishTbody || !publishRows.length){
        return;
      }
      let visibleCount = 0;
      publishRows.forEach(function(row){
        const values = getRowValues(row);
        let matches = true;
        for(const input of publishFilterInputs){
          const attrId = input.dataset.filterId;
          if(!attrId){
            continue;
          }
          const filterValue = String(input.value ?? '').trim();
          if(filterValue === ''){
            continue;
          }
          const filterType = input.dataset.filterType || attributeTypeMap.get(String(attrId)) || 'text';
          const value = String(values?.[attrId] ?? '');
          if(filterType === 'file'){
            if(filterValue === 'has' && value === ''){
              matches = false;
              break;
            }
            if(filterValue === 'empty' && value !== ''){
              matches = false;
              break;
            }
          } else if(filterType === 'select' || filterType === 'date'){
            if(value !== filterValue){
              matches = false;
              break;
            }
          } else if(!value.toLowerCase().includes(filterValue.toLowerCase())){
            matches = false;
            break;
          }
        }
        row.style.display = matches ? '' : 'none';
        if(matches){
          visibleCount += 1;
        }
      });
      if(publishEmptyRow){
        publishEmptyRow.classList.toggle('d-none', visibleCount !== 0);
      }
    }
    function buildFilterParams(){
      const params = new URLSearchParams();
      publishFilterInputs.forEach(function(input){
        const attrId = input.dataset.filterId;
        if(!attrId){
          return;
        }
        const filterValue = String(input.value ?? '').trim();
        if(filterValue === ''){
          return;
        }
        params.append(`filters[${attrId}]`, filterValue);
      });
      return params;
    }
    publishFilterInputs.forEach(function(input){
      input.addEventListener('input', applyPublishFilters);
      input.addEventListener('change', applyPublishFilters);
    });
    publishClearFiltersBtn?.addEventListener('click', function(){
      publishFilterInputs.forEach(function(input){
        if(input instanceof HTMLSelectElement || input instanceof HTMLInputElement){
          input.value = '';
        }
      });
      applyPublishFilters();
    });
    const publishSortHeaders = Array.from(document.querySelectorAll('th[data-attr-id]'));
    const publishSortState = { attrId: null, direction: 'asc' };
    const sortIndicatorText = (direction) => direction === 'asc' ? '▲' : '▼';
    function updateSortIndicators(){
      publishSortHeaders.forEach(function(header){
        const indicator = header.querySelector('.publish-sort-indicator');
        if(!indicator){
          return;
        }
        if(header.dataset.attrId === publishSortState.attrId){
          indicator.textContent = sortIndicatorText(publishSortState.direction);
        } else {
          indicator.textContent = '';
        }
      });
    }
    function compareValues(aValue, bValue, type){
      const aText = String(aValue ?? '');
      const bText = String(bValue ?? '');
      if(type === 'date'){
        const aTime = Date.parse(aText);
        const bTime = Date.parse(bText);
        if(!Number.isNaN(aTime) && !Number.isNaN(bTime)){
          return aTime - bTime;
        }
      }
      return aText.localeCompare(bText, undefined, { numeric: true, sensitivity: 'base' });
    }
    function sortPublishRows(attrId){
      if(!publishTbody || !publishRows.length){
        return;
      }
      if(publishSortState.attrId === attrId){
        publishSortState.direction = publishSortState.direction === 'asc' ? 'desc' : 'asc';
      } else {
        publishSortState.attrId = attrId;
        publishSortState.direction = 'asc';
      }
      const type = attributeTypeMap.get(String(attrId)) || 'text';
      const direction = publishSortState.direction === 'asc' ? 1 : -1;
      publishRows.sort(function(a, b){
        const valuesA = getRowValues(a);
        const valuesB = getRowValues(b);
        const result = compareValues(valuesA?.[attrId] ?? '', valuesB?.[attrId] ?? '', type);
        return result * direction;
      });
      publishRows.forEach(function(row){
        publishTbody.appendChild(row);
      });
      if(publishEmptyRow){
        publishTbody.appendChild(publishEmptyRow);
      }
      updateSortIndicators();
      applyPublishFilters();
    }
    publishSortHeaders.forEach(function(header){
      const attrId = header.dataset.attrId;
      if(!attrId){
        return;
      }
      header.addEventListener('click', function(){
        sortPublishRows(attrId);
      });
      header.addEventListener('keydown', function(event){
        if(event.key === 'Enter' || event.key === ' '){
          event.preventDefault();
          sortPublishRows(attrId);
        }
      });
    });
    applyPublishFilters();
    <?php if ($isManager): ?>
    const editAttrBtn = document.getElementById('editPublishAttributesBtn');
    const attrModalEl = document.getElementById('publishAttributesModal');
    const attrForm = document.getElementById('publishAttributesForm');
    const attrList = document.getElementById('publishAttributesList');
    const addAttrBtn = document.getElementById('addPublishAttribute');
    if(editAttrBtn && attrModalEl && canUseBootstrap){
      const attrModal = new bootstrap.Modal(attrModalEl);
      const cloneAttributes = (list) => Array.isArray(list) ? list.map(attr => ({
        id: attr.id ?? null,
        name_zh: attr.name_zh ?? '',
        name_en: attr.name_en ?? '',
        attribute_type: attr.attribute_type ?? 'text',
        default_value: attr.default_value ?? '',
        options: attr.options ?? ''
      })) : [];
      let workingAttributes = cloneAttributes(window.publishAttributes || []);
      const getLang = () => document.documentElement.lang || 'zh';
      const translationsFor = (key, fallback) => {
        const lang = getLang();
        return translations?.[lang]?.[key] ?? fallback;
      };
      const validationMessage = () => translationsFor('publish.attributes.validation', '请为每个属性提供中文或英文名称。');
      const saveErrorMessage = () => translationsFor('publish.attributes.save_error', '保存失败，请稍后重试。');
      const emptyMessage = () => translationsFor('publish.attributes.empty', '暂无成果属性。');
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
      function renderAttributes(){
        if(!attrList){
          return;
        }
        attrList.innerHTML='';
        if(!workingAttributes.length){
          const emptyDiv=document.createElement('div');
          emptyDiv.className='text-muted';
          emptyDiv.setAttribute('data-i18n','publish.attributes.empty');
          emptyDiv.textContent = emptyMessage();
          attrList.appendChild(emptyDiv);
          if(typeof applyTranslations==='function'){
            applyTranslations();
          }
          return;
        }
        workingAttributes.forEach(function(attr,index){
          const wrapper=document.createElement('div');
          wrapper.className='border rounded p-3 mb-3';
          wrapper.dataset.index=String(index);
          const type = String(attr.attribute_type ?? 'text');
          const hideDefault = type === 'file';
          const showOptions = type === 'select';
          wrapper.innerHTML=`<div class="row g-3 align-items-end">
  <div class="col-md-3">
    <label class="form-label" data-i18n="publish.attributes.field.name_zh">中文名称</label>
    <input type="text" class="form-control" data-field="name_zh" value="${escapeHtml(attr.name_zh)}">
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="publish.attributes.field.name_en">英文名称</label>
    <input type="text" class="form-control" data-field="name_en" value="${escapeHtml(attr.name_en)}">
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="publish.attributes.field.type">属性类型</label>
    <select class="form-select" data-field="attribute_type">
      <option value="text" ${type === 'text' ? 'selected' : ''} data-i18n="publish.attributes.type.text">文本</option>
      <option value="textarea" ${type === 'textarea' ? 'selected' : ''} data-i18n="publish.attributes.type.textarea">多行文本</option>
      <option value="date" ${type === 'date' ? 'selected' : ''} data-i18n="publish.attributes.type.date">日期</option>
      <option value="select" ${type === 'select' ? 'selected' : ''} data-i18n="publish.attributes.type.select">下拉选项</option>
      <option value="file" ${type === 'file' ? 'selected' : ''} data-i18n="publish.attributes.type.file">文件</option>
    </select>
  </div>
  <div data-default-wrapper class="col-md-3${hideDefault ? ' d-none' : ''}">
    <label class="form-label" data-i18n="publish.attributes.field.default_value">默认值</label>
    <input type="text" class="form-control" data-field="default_value" value="${escapeHtml(hideDefault ? '' : attr.default_value)}">
  </div>
  <div data-options-wrapper class="col-md-6${showOptions ? '' : ' d-none'}">
    <label class="form-label" data-i18n="publish.attributes.field.options">可选项</label>
    <input type="text" class="form-control" data-field="options" value="${escapeHtml(attr.options)}" data-i18n-placeholder="publish.attributes.field.options_placeholder" placeholder="Option A, Option B">
    <div class="form-text" data-i18n="publish.attributes.field.options_hint">用逗号分隔多个可选项。</div>
  </div>
  <div class="col-12 d-flex justify-content-end mt-2">
    <button type="button" class="btn btn-sm btn-outline-danger publish-attr-delete" data-index="${index}" data-i18n="publish.attributes.delete">删除</button>
  </div>
</div>`;
          attrList.appendChild(wrapper);
        });
        if(typeof applyTranslations==='function'){
          applyTranslations();
        }
      }
      editAttrBtn.addEventListener('click', function(){
        workingAttributes = cloneAttributes(window.publishAttributes || []);
        renderAttributes();
        attrModal.show();
      });
      addAttrBtn?.addEventListener('click', function(){
        workingAttributes.push({id:null,name_zh:'',name_en:'',attribute_type:'text',default_value:''});
        renderAttributes();
      });
      attrList?.addEventListener('input', function(event){
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
      attrList?.addEventListener('change', function(event){
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
          workingAttributes[index].default_value = target.value === 'file' ? '' : (workingAttributes[index].default_value ?? '');
          workingAttributes[index].options = target.value === 'select' ? (workingAttributes[index].options ?? '') : '';
          const defaultWrapper = row.querySelector('[data-default-wrapper]');
          if(defaultWrapper){
            if(target.value === 'file'){
              defaultWrapper.classList.add('d-none');
            } else {
              defaultWrapper.classList.remove('d-none');
            }
          }
          const optionsWrapper = row.querySelector('[data-options-wrapper]');
          if(optionsWrapper){
            if(target.value === 'select'){
              optionsWrapper.classList.remove('d-none');
            } else {
              optionsWrapper.classList.add('d-none');
            }
          }
          const defaultInput = row.querySelector('[data-field="default_value"]');
          if(defaultInput instanceof HTMLInputElement && target.value === 'file'){
            defaultInput.value = '';
          }
          const optionsInput = row.querySelector('[data-field="options"]');
          if(optionsInput instanceof HTMLInputElement && target.value !== 'select'){
            optionsInput.value = '';
          }
        }
      });
      attrList?.addEventListener('click', function(event){
        const deleteBtn = event.target.closest('.publish-attr-delete');
        if(!deleteBtn){
          return;
        }
        const index = Number(deleteBtn.dataset.index);
        if(Number.isNaN(index)){
          return;
        }
        workingAttributes.splice(index, 1);
        renderAttributes();
      });
      attrForm?.addEventListener('submit', function(event){
        event.preventDefault();
        const payload = workingAttributes.map(function(attr){
          return {
            id: attr.id ?? null,
            name_zh: String(attr.name_zh ?? '').trim(),
            name_en: String(attr.name_en ?? '').trim(),
            attribute_type: attr.attribute_type ?? 'text',
            default_value: String(attr.default_value ?? ''),
            options: String(attr.options ?? '')
          };
        });
        const validCount = payload.filter(item => item.name_zh !== '' || item.name_en !== '').length;
        if(payload.length !== validCount){
          alert(validationMessage());
          return;
        }
        fetch('publish_attributes.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({attributes: payload})
        }).then(response => response.json()).then(data => {
          if(data?.success){
            window.location.reload();
          } else {
            alert(saveErrorMessage());
          }
        }).catch(function(){
          alert(saveErrorMessage());
        });
      });
    }
    const downloadBtn = document.getElementById('downloadPublishBtn');
    const downloadModalEl = document.getElementById('publishDownloadModal');
    const downloadModal = (downloadModalEl && canUseBootstrap)
      ? new bootstrap.Modal(downloadModalEl)
      : null;
    const downloadErrorMessage = () => translateWithFallback('publish.download.error', '下载失败，请稍后重试。');
    if(downloadBtn){
      downloadBtn.addEventListener('click', function(){
        const baseUrl = downloadBtn.getAttribute('data-download-url') || 'publish_download.php';
        const url = new URL(baseUrl, window.location.href);
        const params = buildFilterParams();
        params.forEach(function(value, key){
          url.searchParams.append(key, value);
        });
        fetch(url.toString()).then(response => {
          if(response.ok){
            return response.blob().then(blob => {
              const contentDisposition = response.headers.get('Content-Disposition') || '';
              const matches = contentDisposition.match(/filename=\"?([^\";]+)\"?/i);
              const filename = matches ? matches[1] : 'publish_files.zip';
              const link = document.createElement('a');
              link.href = URL.createObjectURL(blob);
              link.download = filename;
              document.body.appendChild(link);
              link.click();
              link.remove();
              URL.revokeObjectURL(link.href);
            });
          }
          if(response.status === 404 && downloadModal){
            downloadModal.show();
            return null;
          }
          throw new Error('download_failed');
        }).catch(() => {
          if(downloadModal){
            alert(downloadErrorMessage());
          } else {
            alert(downloadErrorMessage());
          }
        });
      });
    }
    <?php endif; ?>
  });
</script>

<?php include 'footer.php'; ?>
