<?php
include 'auth_manager.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$office = [
    'name' => '',
    'location_description' => '',
    'region' => '',
    'layout_image' => '',
    'open_for_selection' => 1
];
$existingSeats = [];
$selectedWhitelist = [];
$existingWhitelist = [];
$error = '';

$activeMembersStmt = $pdo->query("SELECT id, name, campus_id, degree_pursuing, year_of_join FROM members WHERE status = 'in_work' ORDER BY sort_order, name");
$activeMembers = $activeMembersStmt->fetchAll();
if ($activeMembers) {
    $selectedWhitelist = array_map('intval', array_column($activeMembers, 'id'));
}

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM offices WHERE id = ?');
    $stmt->execute([$id]);
    $officeData = $stmt->fetch();
    if (!$officeData) {
        header('Location: offices.php');
        exit();
    }
    $office = $officeData;
    $seatStmt = $pdo->prepare('SELECT id, label, pos_x, pos_y FROM office_seats WHERE office_id = ? ORDER BY id');
    $seatStmt->execute([$id]);
    $existingSeats = $seatStmt->fetchAll();
    $whitelistStmt = $pdo->prepare('SELECT member_id FROM office_selection_whitelist WHERE office_id = ?');
    $whitelistStmt->execute([$id]);
    $existingWhitelist = array_map('intval', $whitelistStmt->fetchAll(PDO::FETCH_COLUMN));
    if ($existingWhitelist) {
        $selectedWhitelist = $existingWhitelist;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $office['name'] = trim($_POST['name'] ?? '');
    $office['location_description'] = trim($_POST['location_description'] ?? '');
    $office['region'] = trim($_POST['region'] ?? '');
    $office['open_for_selection'] = isset($_POST['open_for_selection']) ? 1 : 0;
    if ($office['name'] === '') {
        $error = 'Office name is required (办公地点名称不能为空)。';
    }
    $selectedWhitelist = [];
    if (isset($_POST['whitelist']) && is_array($_POST['whitelist'])) {
        foreach ($_POST['whitelist'] as $memberId) {
            if (is_numeric($memberId)) {
                $selectedWhitelist[] = (int)$memberId;
            }
        }
        $selectedWhitelist = array_values(array_unique(array_filter($selectedWhitelist, fn($v) => $v > 0)));
    }
    $seatsJson = $_POST['seats_json'] ?? '[]';
    $seatsData = json_decode($seatsJson, true);
    if (!is_array($seatsData)) {
        $seatsData = [];
    }

    $normalizedSeats = [];
    foreach ($seatsData as $seat) {
        if (!isset($seat['label'], $seat['pos_x'], $seat['pos_y'])) {
            continue;
        }
        $label = trim((string)$seat['label']);
        if ($label === '') {
            continue;
        }
        $normalizedSeats[] = [
            'id' => isset($seat['id']) && is_numeric($seat['id']) ? (int)$seat['id'] : null,
            'label' => mb_substr($label, 0, 100),
            'pos_x' => max(0, min(1, round((float)$seat['pos_x'], 6))),
            'pos_y' => max(0, min(1, round((float)$seat['pos_y'], 6)))
        ];
    }
    $existingSeats = $normalizedSeats;

    $layoutImagePath = $office['layout_image'] ?? '';
    $uploaded = $_FILES['layout_image'] ?? null;
    if ($uploaded && $uploaded['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($uploaded['error'] !== UPLOAD_ERR_OK) {
            $error = 'Failed to upload layout image (上传布局图片失败)。';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $uploaded['tmp_name']) : null;
            if ($finfo) {
                finfo_close($finfo);
            }
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg'
            ];
            if (!$mime || !isset($allowed[$mime])) {
                $error = 'Please upload a valid image file (请上传有效的图片文件)。';
            } else {
                $filename = uniqid('office_', true) . '.' . $allowed[$mime];
                $targetDir = __DIR__ . '/office_layouts/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $targetPath = $targetDir . $filename;
                if (!move_uploaded_file($uploaded['tmp_name'], $targetPath)) {
                    $error = 'Failed to save layout image (保存布局图片失败)。';
                } else {
                    if ($id && !empty($office['layout_image'])) {
                        $oldPath = __DIR__ . '/' . $office['layout_image'];
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $layoutImagePath = 'office_layouts/' . $filename;
                    $office['layout_image'] = $layoutImagePath;
                }
            }
        }
    } elseif (!$id && empty($layoutImagePath)) {
        $error = 'Layout image is required for a new office (新建办公地点必须上传布局图片)。';
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();
            if ($id) {
                $update = $pdo->prepare('UPDATE offices SET name = ?, location_description = ?, region = ?, layout_image = ?, open_for_selection = ? WHERE id = ?');
                $update->execute([
                    $office['name'],
                    $office['location_description'],
                    $office['region'],
                    $layoutImagePath,
                    $office['open_for_selection'],
                    $id
                ]);
                $officeId = $id;
            } else {
                $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM offices');
                $nextOrder = (int)$orderStmt->fetchColumn();
                $insert = $pdo->prepare('INSERT INTO offices (name, location_description, region, layout_image, open_for_selection, sort_order) VALUES (?,?,?,?,?,?)');
                $insert->execute([
                    $office['name'],
                    $office['location_description'],
                    $office['region'],
                    $layoutImagePath,
                    $office['open_for_selection'],
                    $nextOrder
                ]);
                $officeId = (int)$pdo->lastInsertId();
                $office['layout_image'] = $layoutImagePath;
            }

            $currentSeatIds = [];
            if ($id) {
                $seatIdStmt = $pdo->prepare('SELECT id FROM office_seats WHERE office_id = ?');
                $seatIdStmt->execute([$officeId]);
                $currentSeatIds = array_map('intval', $seatIdStmt->fetchAll(PDO::FETCH_COLUMN));
            }
            $keptSeatIds = [];
            foreach ($normalizedSeats as $seat) {
                if ($seat['id'] && in_array($seat['id'], $currentSeatIds, true)) {
                    $updateSeat = $pdo->prepare('UPDATE office_seats SET label = ?, pos_x = ?, pos_y = ? WHERE id = ? AND office_id = ?');
                    $updateSeat->execute([$seat['label'], $seat['pos_x'], $seat['pos_y'], $seat['id'], $officeId]);
                    $keptSeatIds[] = $seat['id'];
                } else {
                    $insertSeat = $pdo->prepare('INSERT INTO office_seats (office_id, label, pos_x, pos_y) VALUES (?,?,?,?)');
                    $insertSeat->execute([$officeId, $seat['label'], $seat['pos_x'], $seat['pos_y']]);
                }
            }
            if ($currentSeatIds) {
                $idsToDelete = array_diff($currentSeatIds, $keptSeatIds);
                if ($idsToDelete) {
                    $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                    $delete = $pdo->prepare("DELETE FROM office_seats WHERE office_id = ? AND id IN ($placeholders)");
                    $delete->execute(array_merge([$officeId], array_values($idsToDelete)));
                }
            }

            $deleteWhitelist = $pdo->prepare('DELETE FROM office_selection_whitelist WHERE office_id = ?');
            $deleteWhitelist->execute([$officeId]);
            if (!empty($selectedWhitelist)) {
                $insertWhitelist = $pdo->prepare('INSERT INTO office_selection_whitelist (office_id, member_id) VALUES (?, ?)');
                foreach ($selectedWhitelist as $memberId) {
                    $insertWhitelist->execute([$officeId, $memberId]);
                }
            }

            $pdo->commit();
            header('Location: offices.php');
            exit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Failed to save office data (保存办公地点信息失败)。';
        }
    }
}

$emptySeatClass = $existingSeats ? 'text-muted d-none' : 'text-muted';

include 'header.php';
?>
<h2 class="mb-4" data-i18n="<?= $id ? 'office_edit.title_edit' : 'office_edit.title_add'; ?>">
  <?= $id ? 'Edit Office' : 'Add Office'; ?>
</h2>
<?php if($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" id="officeForm">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" data-i18n="office_edit.label_name">Office Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($office['name']); ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label" data-i18n="office_edit.label_location">Location Description</label>
      <input type="text" name="location_description" class="form-control" value="<?= htmlspecialchars($office['location_description']); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label" data-i18n="office_edit.label_region">Region</label>
      <input type="text" name="region" class="form-control" value="<?= htmlspecialchars($office['region']); ?>">
    </div>
  </div>
  <div class="form-check form-switch mt-3">
    <input class="form-check-input" type="checkbox" role="switch" id="openSelectionSwitch" name="open_for_selection" value="1" <?= $office['open_for_selection'] ? 'checked' : ''; ?>>
    <label class="form-check-label" for="openSelectionSwitch" data-i18n="office_edit.label_open_selection">Allow members to select seats</label>
    <div class="form-text" data-i18n="office_edit.open_selection_hint">When disabled, only managers can adjust seat assignments.</div>
  </div>
  <div class="mt-4">
    <label class="form-label fw-semibold" data-i18n="office_edit.whitelist.title">Seat Selection Whitelist</label>
    <div class="text-muted small mb-2" data-i18n="office_edit.whitelist.description">Only selected on-duty members can pick seats in this office.</div>
    <div class="row g-2 align-items-center mb-2">
      <div class="col-md-6">
        <input type="text" class="form-control" id="whitelistSearch" data-i18n-placeholder="office_edit.whitelist.search_placeholder" placeholder="Search members">
      </div>
      <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-sm btn-outline-primary me-2" id="whitelistSelectAll" data-i18n="office_edit.whitelist.select_all">Select All</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="whitelistClearAll" data-i18n="office_edit.whitelist.clear">Clear</button>
      </div>
    </div>
    <div id="whitelistContainer" class="whitelist-container border rounded p-3 bg-light">
      <?php if($activeMembers): ?>
        <div class="row row-cols-1 row-cols-md-2 g-2">
          <?php foreach($activeMembers as $member):
            $memberId = (int)$member['id'];
            $isChecked = in_array($memberId, $selectedWhitelist, true);
            $searchParts = array_filter([
              $member['name'] ?? '',
              $member['campus_id'] ?? '',
              $member['degree_pursuing'] ?? '',
              $member['year_of_join'] ?? ''
            ]);
            $searchTextRaw = trim(implode(' ', $searchParts));
            $searchText = function_exists('mb_strtolower') ? mb_strtolower($searchTextRaw) : strtolower($searchTextRaw);
            $inputId = 'whitelist_' . $memberId;
          ?>
          <div class="col whitelist-item" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES); ?>">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="whitelist[]" value="<?= $memberId; ?>" id="<?= htmlspecialchars($inputId); ?>" <?= $isChecked ? 'checked' : ''; ?>>
              <label class="form-check-label" for="<?= htmlspecialchars($inputId); ?>">
                <span class="fw-semibold"><?= htmlspecialchars($member['name']); ?></span>
                <?php if(!empty($member['campus_id'])): ?>
                  <span class="text-muted ms-1">(<?= htmlspecialchars($member['campus_id']); ?>)</span>
                <?php endif; ?>
                <?php if(!empty($member['degree_pursuing']) || !empty($member['year_of_join'])): ?>
                  <small class="d-block text-muted">
                    <?php if(!empty($member['degree_pursuing'])): ?><?= htmlspecialchars($member['degree_pursuing']); ?><?php endif; ?>
                    <?php if(!empty($member['degree_pursuing']) && !empty($member['year_of_join'])): ?> · <?php endif; ?>
                    <?php if(!empty($member['year_of_join'])): ?><?= htmlspecialchars($member['year_of_join']); ?><?php endif; ?>
                  </small>
                <?php endif; ?>
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-muted" data-i18n="office_edit.whitelist.empty">No active members available.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="mb-3 mt-4">
    <label class="form-label" data-i18n="office_edit.label_image">Layout Image</label>
    <input type="file" name="layout_image" accept="image/*" class="form-control" <?= $id ? '' : 'required'; ?>>
    <?php if(!empty($office['layout_image'])): ?>
      <small class="text-muted d-block mt-1"><span data-i18n="office_edit.current_image">Current layout</span>: <?= htmlspecialchars($office['layout_image']); ?></small>
    <?php endif; ?>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="office_edit.label_seats">Seat Layout</label>
    <div class="alert alert-info py-2" id="layoutNotice">
      <div data-i18n="office_edit.instructions">Click the layout to add seats. Drag markers to fine-tune positions.</div>
      <div data-i18n="office_edit.instructions_remove">Use the list below to rename or remove seats.</div>
    </div>
    <div id="layoutWrapper" class="border rounded position-relative" style="min-height:320px; overflow:hidden;">
      <div class="position-absolute top-50 start-50 translate-middle text-muted" id="layoutPlaceholder" data-i18n="office_edit.no_image">Please upload a layout image before placing seats.</div>
      <?php if(!empty($office['layout_image'])): ?>
        <img src="<?= htmlspecialchars($office['layout_image']); ?>" id="layoutImage" class="img-fluid" alt="Layout">
      <?php else: ?>
        <img src="" id="layoutImage" class="img-fluid d-none" alt="Layout">
      <?php endif; ?>
      <div id="seatMarkers" class="position-absolute top-0 start-0 w-100 h-100"></div>
    </div>
  </div>
  <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <h5 data-i18n="office_edit.label_seats">Seat Layout</h5>
      <span id="emptySeatHint" class="<?= $emptySeatClass; ?>" data-i18n="office_edit.seats_empty">No seats defined yet.</span>
    </div>
    <div class="table-responsive mt-2">
      <table class="table table-sm" id="seatTable">
        <thead class="table-light">
          <tr>
            <th data-i18n="office_edit.table.label">Seat Label</th>
            <th class="text-center" data-i18n="office_edit.table.actions">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
  <input type="hidden" name="seats_json" id="seatsJson">
  <div class="mt-4">
    <button type="submit" class="btn btn-primary" data-i18n="office_edit.save">Save</button>
    <a href="offices.php" class="btn btn-secondary" data-i18n="office_edit.cancel">Cancel</a>
  </div>
</form>
<span id="defaultSeatLabel" class="d-none" data-i18n="office_edit.default_label">Seat</span>
<script>
(function(){
  const layoutWrapper = document.getElementById('layoutWrapper');
  const layoutImage = document.getElementById('layoutImage');
  const seatMarkers = document.getElementById('seatMarkers');
  const seatTableBody = document.querySelector('#seatTable tbody');
  const seatsJsonInput = document.getElementById('seatsJson');
  const emptySeatHint = document.getElementById('emptySeatHint');
  const layoutPlaceholder = document.getElementById('layoutPlaceholder');
  const defaultLabelSpan = document.getElementById('defaultSeatLabel');
  const whitelistContainer = document.getElementById('whitelistContainer');
  const whitelistSearch = document.getElementById('whitelistSearch');
  const whitelistSelectAllBtn = document.getElementById('whitelistSelectAll');
  const whitelistClearAllBtn = document.getElementById('whitelistClearAll');
  let seatCounter = 0;
  let seatData = <?= json_encode($existingSeats, JSON_UNESCAPED_UNICODE); ?> || [];
  seatCounter = seatData.length;

  const fileInput = document.querySelector('input[name="layout_image"]');
  if (fileInput) {
    fileInput.addEventListener('change', handleFileChange);
  }

  function repositionAllMarkers() {
    seatData.forEach(seat => {
      const marker = seatMarkers.querySelector(`[data-seat-id="${seat.id ?? ''}"]`);
      if (marker) {
        positionMarker(marker, seat);
      }
    });
  }

  if (window.ResizeObserver && layoutWrapper) {
    const observer = new ResizeObserver(repositionAllMarkers);
    observer.observe(layoutWrapper);
  } else {
    window.addEventListener('resize', repositionAllMarkers);
  }

  function safeApplyTranslations() {
    if (typeof applyTranslations === 'function') {
      applyTranslations();
    }
  }

  function getFilterTerm() {
    if (!whitelistSearch) {
      return '';
    }
    const value = whitelistSearch.value || '';
    return value.toLowerCase().trim();
  }

  function filterWhitelist() {
    if (!whitelistContainer) {
      return;
    }
    const term = getFilterTerm();
    const items = whitelistContainer.querySelectorAll('.whitelist-item');
    items.forEach(item => {
      const searchValue = (item.getAttribute('data-search') || '').toLowerCase();
      if (!term || searchValue.includes(term)) {
        item.classList.remove('d-none');
      } else {
        item.classList.add('d-none');
      }
    });
  }

  if (whitelistSearch) {
    whitelistSearch.addEventListener('input', filterWhitelist);
  }

  if (whitelistSelectAllBtn) {
    whitelistSelectAllBtn.addEventListener('click', () => {
      if (!whitelistContainer) {
        return;
      }
      whitelistContainer.querySelectorAll('.whitelist-item:not(.d-none) input[type="checkbox"]').forEach(cb => {
        cb.checked = true;
      });
    });
  }

  if (whitelistClearAllBtn) {
    whitelistClearAllBtn.addEventListener('click', () => {
      if (!whitelistContainer) {
        return;
      }
      whitelistContainer.querySelectorAll('.whitelist-item:not(.d-none) input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
      });
    });
  }

  filterWhitelist();

  function handleFileChange(event) {
    const file = event.target.files[0];
    if (!file) {
      return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
      layoutImage.src = e.target.result;
      layoutImage.classList.remove('d-none');
      layoutPlaceholder.classList.add('d-none');
      layoutImage.addEventListener('load', () => {
        renderAllMarkers();
      }, { once: true });
    };
    reader.readAsDataURL(file);
  }

  if (layoutImage && layoutImage.getAttribute('src')) {
    if (!layoutImage.complete) {
      layoutImage.addEventListener('load', renderAllMarkers);
    } else {
      renderAllMarkers();
    }
    layoutPlaceholder.classList.add('d-none');
  }

  layoutWrapper.addEventListener('click', event => {
    if (!layoutImage || layoutImage.classList.contains('d-none')) {
      return;
    }
    if (event.target.closest('.seat-marker')) {
      return;
    }
    const rect = layoutImage.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width;
    const y = (event.clientY - rect.top) / rect.height;
    if (x < 0 || y < 0 || x > 1 || y > 1) {
      return;
    }
    const baseLabel = defaultLabelSpan.textContent || 'Seat';
    seatCounter += 1;
    const newSeat = {
      id: `new-${Date.now()}-${seatCounter}`,
      label: `${baseLabel} ${seatCounter}`,
      pos_x: x,
      pos_y: y
    };
    seatData.push(newSeat);
    addSeatRow(newSeat);
    createMarker(newSeat);
    updateEmptyHint();
  });

  function createMarker(seat) {
    const marker = document.createElement('div');
    marker.className = 'seat-marker';
    marker.dataset.seatId = seat.id ?? '';
    marker.innerHTML = `<span class="seat-marker-label"></span><button type="button" class="btn-close btn-close-white seat-remove" aria-label="Remove"></button>`;
    seatMarkers.appendChild(marker);
    updateMarkerLabel(marker, seat.label);
    positionMarker(marker, seat);
    marker.querySelector('.seat-remove').addEventListener('click', e => {
      e.stopPropagation();
      removeSeat(seat.id);
    });
    enableDrag(marker, seat);
  }

  function updateMarkerLabel(marker, label) {
    const labelEl = marker.querySelector('.seat-marker-label');
    if (labelEl) {
      labelEl.textContent = label;
    }
  }

  function positionMarker(marker, seat) {
    if (!layoutImage || !layoutImage.width) {
      return;
    }
    const width = layoutImage.clientWidth;
    const height = layoutImage.clientHeight;
    const left = seat.pos_x * width;
    const top = seat.pos_y * height;
    marker.style.transform = `translate(-50%, -50%) translate(${left}px, ${top}px)`;
  }

  function addSeatRow(seat) {
    const row = document.createElement('tr');
    row.dataset.seatId = seat.id ?? '';

    const labelCell = document.createElement('td');
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm';
    input.value = seat.label;
    input.addEventListener('input', e => {
      seat.label = e.target.value;
      const marker = seatMarkers.querySelector(`[data-seat-id="${seat.id ?? ''}"]`);
      if (marker) {
        updateMarkerLabel(marker, seat.label);
      }
    });
    labelCell.appendChild(input);

    const actionCell = document.createElement('td');
    actionCell.className = 'text-center';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-outline-danger';
    btn.dataset.i18n = 'office_edit.remove';
    btn.textContent = 'Remove';
    btn.addEventListener('click', () => removeSeat(seat.id));
    actionCell.appendChild(btn);

    row.appendChild(labelCell);
    row.appendChild(actionCell);
    seatTableBody.appendChild(row);
    safeApplyTranslations();
  }

  function removeSeat(seatId) {
    seatData = seatData.filter(seat => {
      const match = String(seat.id ?? '') === String(seatId ?? '');
      if (match) {
        const marker = seatMarkers.querySelector(`[data-seat-id="${seat.id ?? ''}"]`);
        if (marker) {
          marker.remove();
        }
        const row = seatTableBody.querySelector(`[data-seat-id="${seat.id ?? ''}"]`);
        if (row) {
          row.remove();
        }
      }
      return !match;
    });
    updateEmptyHint();
  }

  function renderAllMarkers() {
    seatMarkers.innerHTML = '';
    seatTableBody.innerHTML = '';
    seatData.forEach(seat => {
      if (!seat.id) {
        seat.id = `new-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
      }
      createMarker(seat);
      addSeatRow(seat);
    });
    updateEmptyHint();
    safeApplyTranslations();
  }

  function enableDrag(marker, seat) {
    let dragging = false;
    marker.addEventListener('pointerdown', e => {
      if (e.target && e.target.closest('.seat-remove')) {
        return;
      }
      dragging = true;
      marker.setPointerCapture(e.pointerId);
    });
    marker.addEventListener('pointermove', e => {
      if (!dragging) {
        return;
      }
      const rect = layoutImage.getBoundingClientRect();
      if (!rect.width || !rect.height) {
        return;
      }
      const x = (e.clientX - rect.left) / rect.width;
      const y = (e.clientY - rect.top) / rect.height;
      seat.pos_x = Math.max(0, Math.min(1, x));
      seat.pos_y = Math.max(0, Math.min(1, y));
      positionMarker(marker, seat);
    });
    marker.addEventListener('pointerup', () => {
      dragging = false;
    });
    marker.addEventListener('pointercancel', () => {
      dragging = false;
    });
  }

  function updateEmptyHint() {
    if (!emptySeatHint) {
      return;
    }
    if (seatData.length === 0) {
      emptySeatHint.classList.remove('d-none');
    } else {
      emptySeatHint.classList.add('d-none');
    }
  }

  document.getElementById('officeForm').addEventListener('submit', () => {
    const payload = seatData.map(seat => ({
      id: seat.id && /^\d+$/.test(String(seat.id)) ? parseInt(seat.id, 10) : seat.id,
      label: seat.label,
      pos_x: seat.pos_x,
      pos_y: seat.pos_y
    }));
    seatsJsonInput.value = JSON.stringify(payload);
  });
})();
</script>
<style>
  #layoutWrapper {
    background-color: #f8f9fa;
  }
  .seat-marker {
    position: absolute;
    background-color: rgba(13, 110, 253, 0.85);
    color: #fff;
    padding: 0.25rem 0.5rem;
    border-radius: 999px;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    cursor: grab;
    user-select: none;
    font-size: 0.85rem;
  }
  .seat-marker .seat-remove {
    opacity: 0.7;
    width: 0.8rem;
    height: 0.8rem;
  }
  .seat-marker .seat-remove:hover {
    opacity: 1;
  }
  .seat-marker:active {
    cursor: grabbing;
  }
  .whitelist-container {
    max-height: 320px;
    overflow-y: auto;
    background-color: #f8f9fa;
  }
  .whitelist-item .form-check {
    background-color: #fff;
    border-radius: 0.75rem;
    padding: 0.45rem 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
  }
  .whitelist-item .form-check:hover {
    box-shadow: 0 0.35rem 0.75rem rgba(0, 0, 0, 0.08);
    border-color: rgba(13, 110, 253, 0.4);
  }
</style>
<?php include 'footer.php'; ?>
