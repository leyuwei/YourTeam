<?php
include 'auth.php';

$is_manager = ($_SESSION['role'] ?? '') === 'manager';
?>
<?php include 'header.php'; ?>
  <div class="row mb-4 align-items-center">
    <div class="col-md-8">
      <h2 class="mb-2" data-i18n="askme.title">AskMe</h2>
      <p class="text-muted mb-0" data-i18n="askme.subtitle">Find answers across policies, offices, assets, and the AskMe knowledge base.</p>
    </div>
    <?php if($is_manager): ?>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <button class="btn btn-warning shadow-sm" id="manageKnowledge" data-i18n="askme.manage">Manage Knowledge Base</button>
    </div>
    <?php endif; ?>
  </div>
  <div class="card mb-4">
    <div class="card-body">
      <form id="askmeForm" class="row g-3 align-items-center">
        <div class="col-sm-9">
          <label class="visually-hidden" for="askmeQuery" data-i18n="askme.search_label">Search</label>
          <input type="text" id="askmeQuery" name="q" class="form-control form-control-lg" placeholder="Ask anything..." data-i18n-placeholder="askme.search_placeholder" required>
        </div>
        <div class="col-sm-3 d-grid">
          <button type="submit" class="btn btn-gradient btn-lg" id="askmeSearch" data-i18n="askme.search_btn">Search</button>
        </div>
      </form>
      <div class="text-muted mt-2" data-i18n="askme.hint">Tip: try keywords like "travel", "office", or "device".</div>
    </div>
  </div>
  <div id="askmeResults" class="row g-3"></div>

<?php if($is_manager): ?>
<div class="modal fade" id="knowledgeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="askme.manage_title">Manage AskMe Knowledge</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="knowledgeForm" class="mb-3">
          <input type="hidden" id="knowledgeId" value="">
          <div class="mb-3">
            <label for="knowledgeContent" class="form-label" data-i18n="askme.content_label">Knowledge Content</label>
            <textarea id="knowledgeContent" class="form-control" rows="5" required></textarea>
            <div class="form-text" data-i18n="askme.content_hint">One language is enough here—we will serve it to every user.</div>
          </div>
          <div class="mb-3">
            <label for="knowledgeKeywords" class="form-label" data-i18n="askme.keywords_label">Keywords (comma separated)</label>
            <input type="text" id="knowledgeKeywords" class="form-control" placeholder="travel, leave, office">
            <div class="form-text" data-i18n="askme.keywords_hint">Use multiple candidate keywords to improve fuzzy search.</div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" data-i18n="askme.save">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="askme.cancel">Cancel</button>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width:55%" data-i18n="askme.table.content">Content</th>
                <th style="width:25%" data-i18n="askme.table.keywords">Keywords</th>
                <th style="width:20%" data-i18n="askme.table.actions">Actions</th>
              </tr>
            </thead>
            <tbody id="knowledgeList"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
  .btn-gradient {
    background: linear-gradient(135deg, #ffb347 0%, #ffcc33 100%);
    border: none;
    color: #1f1f1f;
    font-weight: 700;
    letter-spacing: 0.3px;
    box-shadow: 0 10px 20px rgba(255, 193, 7, 0.25);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-position 0.2s ease;
    background-size: 120% 120%;
  }
  .btn-gradient:hover {
    transform: translateY(-1px);
    box-shadow: 0 15px 25px rgba(255, 193, 7, 0.35);
    background-position: 100% 0;
  }
  .askme-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    background: rgba(255, 221, 87, 0.18);
    color: var(--app-text-color);
    border: 1px solid rgba(255, 193, 7, 0.45);
  }
  .askme-snippet mark {
    padding: 0;
    background: rgba(255, 221, 87, 0.6);
  }
</style>

<script>
const askmeForm = document.getElementById('askmeForm');
const resultsContainer = document.getElementById('askmeResults');
const queryInput = document.getElementById('askmeQuery');
let knowledgeModal;

function renderResults(items) {
  resultsContainer.innerHTML = '';
  if (!items.length) {
    const empty = document.createElement('div');
    empty.className = 'col-12 text-muted';
    empty.dataset.i18n = 'askme.no_results';
    empty.textContent = getTranslation('askme.no_results');
    resultsContainer.appendChild(empty);
    applyI18n();
    return;
  }
  items.forEach(item => {
    const col = document.createElement('div');
    col.className = 'col-12';
    const card = document.createElement('div');
    card.className = 'card shadow-sm';
    const body = document.createElement('div');
    body.className = 'card-body';

    const header = document.createElement('div');
    header.className = 'd-flex align-items-center gap-2 mb-2';
    const source = document.createElement('span');
    source.className = 'askme-chip';
    source.textContent = item.source_label || item.source;
    header.appendChild(source);

    if (item.title) {
      const title = document.createElement('strong');
      title.textContent = item.title;
      header.appendChild(title);
    }

    const snippet = document.createElement('div');
    snippet.className = 'askme-snippet';
    snippet.innerHTML = item.snippet;

    body.appendChild(header);
    body.appendChild(snippet);
    card.appendChild(body);
    col.appendChild(card);
    resultsContainer.appendChild(col);
  });
}

function getTranslation(key) {
  const lang = document.documentElement.lang === 'en' ? 'en' : 'zh';
  return (translations[lang] && translations[lang][key]) ? translations[lang][key] : key;
}

askmeForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const q = queryInput.value.trim();
  if (!q) return;
  const btn = document.getElementById('askmeSearch');
  btn.disabled = true;
  btn.classList.add('disabled');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + getTranslation('askme.searching');
  try {
    const res = await fetch('askme_search.php?q=' + encodeURIComponent(q));
    const data = await res.json();
    renderResults(data.results || []);
  } catch (err) {
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.classList.remove('disabled');
    btn.dataset.i18n = 'askme.search_btn';
    btn.textContent = getTranslation('askme.search_btn');
    applyI18n();
  }
});

<?php if($is_manager): ?>
const knowledgeList = document.getElementById('knowledgeList');
const knowledgeForm = document.getElementById('knowledgeForm');
const knowledgeId = document.getElementById('knowledgeId');
const knowledgeContent = document.getElementById('knowledgeContent');
const knowledgeKeywords = document.getElementById('knowledgeKeywords');

knowledgeModal = new bootstrap.Modal(document.getElementById('knowledgeModal'));

document.getElementById('manageKnowledge').addEventListener('click', () => {
  knowledgeModal.show();
  loadKnowledge();
});

knowledgeForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const payload = {
    action: 'save',
    id: knowledgeId.value || null,
    content: knowledgeContent.value.trim(),
    keywords: knowledgeKeywords.value.trim(),
  };
  if (!payload.content) return;
  await fetch('askme_knowledge.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  knowledgeId.value = '';
  knowledgeContent.value = '';
  knowledgeKeywords.value = '';
  await loadKnowledge();
});

async function loadKnowledge() {
  const res = await fetch('askme_knowledge.php');
  const data = await res.json();
  const rows = data.entries || [];
  knowledgeList.innerHTML = '';
  if (!rows.length) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = 3;
    td.className = 'text-muted';
    td.dataset.i18n = 'askme.no_entries';
    td.textContent = getTranslation('askme.no_entries');
    tr.appendChild(td);
    knowledgeList.appendChild(tr);
    applyI18n();
    return;
  }
  rows.forEach(row => {
    const tr = document.createElement('tr');
    const tdContent = document.createElement('td');
    tdContent.textContent = row.content.length > 120 ? row.content.substring(0, 120) + '…' : row.content;
    const tdKeywords = document.createElement('td');
    tdKeywords.textContent = row.keywords;
    const tdActions = document.createElement('td');
    const editBtn = document.createElement('button');
    editBtn.className = 'btn btn-sm btn-outline-primary me-2';
    editBtn.dataset.i18n = 'askme.edit';
    editBtn.textContent = getTranslation('askme.edit');
    editBtn.addEventListener('click', () => {
      knowledgeId.value = row.id;
      knowledgeContent.value = row.content;
      knowledgeKeywords.value = row.keywords;
      knowledgeContent.focus();
    });
    const delBtn = document.createElement('button');
    delBtn.className = 'btn btn-sm btn-outline-danger';
    delBtn.dataset.i18n = 'askme.delete';
    delBtn.textContent = getTranslation('askme.delete');
    delBtn.addEventListener('click', async () => {
      await fetch('askme_knowledge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: row.id })
      });
      await loadKnowledge();
    });
    tdActions.appendChild(editBtn);
    tdActions.appendChild(delBtn);
    tr.appendChild(tdContent);
    tr.appendChild(tdKeywords);
    tr.appendChild(tdActions);
    knowledgeList.appendChild(tr);
  });
  applyI18n();
}
<?php endif; ?>
</script>
<?php include 'footer.php'; ?>
