<?php
include 'auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: offices.php');
    exit();
}
$officeStmt = $pdo->prepare('SELECT * FROM offices WHERE id = ?');
$officeStmt->execute([$id]);
$office = $officeStmt->fetch();
if (!$office) {
    header('Location: offices.php');
    exit();
}
$seatStmt = $pdo->prepare('SELECT s.id, s.label, s.pos_x, s.pos_y, s.member_id, m.name AS member_name FROM office_seats s LEFT JOIN members m ON s.member_id = m.id WHERE s.office_id = ? ORDER BY s.id');
$seatStmt->execute([$id]);
$seats = $seatStmt->fetchAll();
$seatCount = count($seats);
$availableSeats = 0;
foreach ($seats as $seat) {
    if (empty($seat['member_id'])) {
        $availableSeats++;
    }
}
$membersList = [];
if ($_SESSION['role'] === 'manager') {
    $membersList = $pdo->query("SELECT id, name FROM members WHERE status != 'exited' ORDER BY sort_order, name")->fetchAll();
}
$currentMemberId = $_SESSION['member_id'] ?? null;

include 'header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="bold-target">
    <span data-i18n="office_view.title">Office Layout</span>
    <span class="text-muted">- <?= htmlspecialchars($office['name']); ?></span>
  </h2>
  <a href="offices.php" class="btn btn-outline-secondary" data-i18n="direction_members.back">Back</a>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div id="layoutContainer" class="border rounded position-relative bg-light" style="min-height:360px; overflow:hidden;">
      <?php if(!empty($office['layout_image'])): ?>
        <img src="<?= htmlspecialchars($office['layout_image']); ?>" id="officeLayout" class="img-fluid" alt="Layout">
        <div id="viewSeatMarkers" class="position-absolute top-0 start-0 w-100 h-100"></div>
      <?php else: ?>
        <div class="p-5 text-center text-muted" data-i18n="office_edit.no_image">Please upload a layout image before placing seats.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title" data-i18n="offices.table.name">Office Name</h5>
        <p class="card-text fw-semibold"><?= htmlspecialchars($office['name']); ?></p>
        <ul class="list-unstyled small mb-3">
          <li><strong data-i18n="office_view.info.location">Location</strong>: <?= htmlspecialchars($office['location_description'] ?? ''); ?></li>
          <li><strong data-i18n="office_view.info.region">Region</strong>: <?= htmlspecialchars($office['region'] ?? ''); ?></li>
          <li><strong data-i18n="office_view.info.total">Total Seats</strong>: <span id="totalCount"><?= $seatCount; ?></span></li>
          <li><strong data-i18n="office_view.info.available">Remaining Seats</strong>: <span id="availableCount"><?= $availableSeats; ?></span></li>
        </ul>
        <?php if($_SESSION['role'] === 'manager'): ?>
          <div class="alert alert-info" data-i18n="office_view.instructions.manager">Choose a member and click a seat to assign it. Select Clear Seat to free a seat.</div>
          <select class="form-select form-select-sm mb-3" id="memberSelect">
            <option value="" data-i18n="office_view.select.member">Select member</option>
            <option value="clear" data-i18n="office_view.select.clear">Clear Seat</option>
            <?php foreach($membersList as $member): ?>
              <option value="<?= (int)$member['id']; ?>"><?= htmlspecialchars($member['name']); ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <div class="alert alert-info" data-i18n="office_view.instructions.member">Click an available seat to claim it, or click your seat to release it.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header" data-i18n="offices.table.members">Members in Office</div>
      <div class="card-body p-0">
        <div class="table-responsive mb-0">
          <table class="table table-sm mb-0" id="seatStatusTable">
            <thead class="table-light">
              <tr>
                <th data-i18n="office_view.table.seat">Seat</th>
                <th data-i18n="office_view.table.status">Status</th>
                <th data-i18n="office_view.table.member">Member</th>
              </tr>
            </thead>
            <tbody>
              <?php if($seats): ?>
                <?php foreach($seats as $seat): ?>
                  <tr data-seat-id="<?= (int)$seat['id']; ?>">
                    <td class="fw-semibold"><?= htmlspecialchars($seat['label']); ?></td>
                    <td class="seat-status">
                      <?php if($seat['member_id']): ?>
                        <span class="badge bg-danger" data-i18n="office_view.status.occupied">Occupied</span>
                      <?php else: ?>
                        <span class="badge bg-success" data-i18n="office_view.status.available">Available</span>
                      <?php endif; ?>
                    </td>
                    <td class="seat-member">
                      <?php if($seat['member_id']): ?>
                        <?= htmlspecialchars($seat['member_name'] ?? ('#' . $seat['member_id'])); ?>
                      <?php else: ?>
                        <span class="text-muted" data-i18n="office_view.member.empty">Unassigned</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="text-center text-muted" data-i18n="office_edit.seats_empty">No seats defined yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const seatData = new Map();
  const initialSeats = <?= json_encode($seats, JSON_UNESCAPED_UNICODE); ?>;
  initialSeats.forEach(seat => {
    seat.id = parseInt(seat.id, 10);
    seat.member_id = seat.member_id ? parseInt(seat.member_id, 10) : null;
    seat.member_name = seat.member_name || null;
    seatData.set(seat.id, seat);
  });

  const layoutImage = document.getElementById('officeLayout');
  const markersContainer = document.getElementById('viewSeatMarkers');
  const seatRows = new Map();
  document.querySelectorAll('#seatStatusTable tbody tr[data-seat-id]').forEach(row => {
    const seatId = parseInt(row.dataset.seatId, 10);
    const statusEl = row.querySelector('.seat-status');
    const memberEl = row.querySelector('.seat-member');
    seatRows.set(seatId, {row, statusEl, memberEl});
  });
  const totalCountEl = document.getElementById('totalCount');
  const availableCountEl = document.getElementById('availableCount');
  const memberSelect = document.getElementById('memberSelect');
  const isManager = <?= $_SESSION['role'] === 'manager' ? 'true' : 'false'; ?>;
  const currentMemberId = <?= $currentMemberId ? (int)$currentMemberId : 'null'; ?>;

  function t(key) {
    const lang = document.documentElement.lang || 'zh';
    if (window.translations && translations[lang] && translations[lang][key]) {
      return translations[lang][key];
    }
    return key;
  }

  function safeApplyTranslations() {
    if (typeof applyTranslations === 'function') {
      applyTranslations();
    }
  }

  function updateCounts() {
    const total = seatData.size;
    let available = 0;
    seatData.forEach(seat => {
      if (!seat.member_id) {
        available += 1;
      }
    });
    if (totalCountEl) totalCountEl.textContent = total;
    if (availableCountEl) availableCountEl.textContent = available;
  }

  function updateRow(seat) {
    const refs = seatRows.get(seat.id);
    if (!refs) {
      return;
    }
    const { statusEl, memberEl } = refs;
    if (statusEl) {
      statusEl.innerHTML = '';
      const badge = document.createElement('span');
      if (seat.member_id) {
        badge.className = 'badge bg-danger';
        badge.dataset.i18n = 'office_view.status.occupied';
        badge.textContent = 'Occupied';
      } else {
        badge.className = 'badge bg-success';
        badge.dataset.i18n = 'office_view.status.available';
        badge.textContent = 'Available';
      }
      statusEl.appendChild(badge);
    }
    if (memberEl) {
      if (seat.member_id) {
        memberEl.textContent = seat.member_name || ('#' + seat.member_id);
        delete memberEl.dataset.i18n;
      } else {
        memberEl.textContent = '';
        memberEl.dataset.i18n = 'office_view.member.empty';
      }
    }
    safeApplyTranslations();
  }

  function updateMarkerState(marker, seat) {
    marker.classList.toggle('occupied', !!seat.member_id);
    marker.classList.toggle('available', !seat.member_id);
    marker.classList.toggle('mine', !!seat.member_id && currentMemberId && seat.member_id === currentMemberId);
    const occupantEl = marker.querySelector('.seat-marker-occupant');
    if (occupantEl) {
      if (seat.member_name) {
        occupantEl.textContent = seat.member_name;
        occupantEl.classList.remove('d-none');
      } else {
        occupantEl.textContent = '';
        occupantEl.classList.add('d-none');
      }
    }
    const parts = [seat.label];
    if (seat.member_name) {
      parts.push(seat.member_name);
    }
    marker.title = parts.join(' - ');
  }

  function positionMarker(marker, seat) {
    if (!layoutImage) {
      return;
    }
    const width = layoutImage.clientWidth;
    const height = layoutImage.clientHeight;
    if (!width || !height) {
      return;
    }
    const left = seat.pos_x * width;
    const top = seat.pos_y * height;
    marker.style.transform = `translate(-50%, -50%) translate(${left}px, ${top}px)`;
  }

  function createMarker(seat) {
    const marker = document.createElement('div');
    marker.className = 'seat-marker-view';
    marker.dataset.seatId = seat.id;
    const label = document.createElement('span');
    label.className = 'seat-marker-label';
    label.textContent = seat.label;
    marker.appendChild(label);
    const occupant = document.createElement('span');
    occupant.className = 'seat-marker-occupant d-none';
    marker.appendChild(occupant);
    updateMarkerState(marker, seat);
    marker.addEventListener('click', () => handleSeatClick(seat.id));
    return marker;
  }

  function renderMarkers() {
    if (!layoutImage || !markersContainer) {
      return;
    }
    markersContainer.innerHTML = '';
    seatData.forEach(seat => {
      const marker = createMarker(seat);
      markersContainer.appendChild(marker);
      positionMarker(marker, seat);
    });
  }

  function repositionMarkers() {
    if (!layoutImage || !markersContainer) {
      return;
    }
    seatData.forEach(seat => {
      const marker = markersContainer.querySelector(`[data-seat-id="${seat.id}"]`);
      if (marker) {
        updateMarkerState(marker, seat);
        positionMarker(marker, seat);
      }
    });
  }

  function showMessage(key) {
    alert(t(key));
  }

  function handleSeatClick(seatId) {
    const seat = seatData.get(seatId);
    if (!seat) {
      return;
    }
    if (isManager) {
      if (!memberSelect) {
        return;
      }
      const value = memberSelect.value;
      if (!value) {
        showMessage('office_view.message.select_member');
        return;
      }
      if (value === 'clear') {
        updateSeat(seatId, 'release');
        return;
      }
      updateSeat(seatId, 'assign', parseInt(value, 10));
      return;
    }
    if (!currentMemberId) {
      return;
    }
    if (!seat.member_id) {
      updateSeat(seatId, 'assign', currentMemberId);
    } else if (seat.member_id === currentMemberId) {
      updateSeat(seatId, 'release');
    } else {
      showMessage('office_view.message.unavailable');
    }
  }

  function updateSeat(seatId, action, memberId) {
    const params = new URLSearchParams();
    params.append('seat_id', seatId);
    params.append('action', action);
    if (memberId) {
      params.append('member_id', memberId);
    }
    fetch('office_seat_update.php', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: params
    }).then(resp => resp.json())
      .then(data => {
        if (!data || !data.success) {
          showMessage(data && data.message ? data.message : 'office_view.message.error');
          return;
        }
        const seat = seatData.get(seatId);
        if (!seat) {
          return;
        }
        seat.member_id = data.seat.member_id ? parseInt(data.seat.member_id, 10) : null;
        seat.member_name = data.seat.member_name || null;
        updateRow(seat);
        const marker = markersContainer ? markersContainer.querySelector(`[data-seat-id="${seat.id}"]`) : null;
        if (marker) {
          updateMarkerState(marker, seat);
          positionMarker(marker, seat);
        }
        updateCounts();
      })
      .catch(() => {
        showMessage('office_view.message.error');
      });
  }

  if (layoutImage && layoutImage.complete) {
    renderMarkers();
  } else if (layoutImage) {
    layoutImage.addEventListener('load', renderMarkers);
  }

  window.addEventListener('resize', repositionMarkers);
  updateCounts();
})();
</script>
<style>
  #layoutContainer {
    background-color: #f8f9fa;
  }
  .seat-marker-view {
    position: absolute;
    padding: 0.35rem 0.6rem;
    border-radius: 999px;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    transition: transform 0.15s ease;
  }
  .seat-marker-view .seat-marker-label {
    pointer-events: none;
  }
  .seat-marker-view .seat-marker-occupant {
    pointer-events: none;
    display: block;
    font-size: 0.7rem;
    line-height: 1.2;
    margin-top: 0.15rem;
    font-weight: 500;
  }
  .seat-marker-view.available {
    background-color: rgba(25, 135, 84, 0.85);
  }
  .seat-marker-view.occupied {
    background-color: rgba(220, 53, 69, 0.85);
  }
  .seat-marker-view.mine {
    background-color: rgba(13, 110, 253, 0.9);
  }
  .seat-marker-view:hover {
    transform: translate(-50%, -50%) scale(1.05);
  }
</style>
<?php include 'footer.php'; ?>
