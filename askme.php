<?php
include 'header.php';

$isManager = ($_SESSION['role'] ?? '') === 'manager';
$saveMessage = '';
$saveError = '';
$searchError = '';

function normalizeKeywords(array $rawKeywords): array {
    $keywords = [];
    foreach ($rawKeywords as $keyword) {
        $trimmed = trim($keyword);
        if ($trimmed !== '') {
            $keywords[] = $trimmed;
        }
    }
    return array_values(array_unique($keywords));
}

function buildSnippet(string $text, string $query, int $radius = 50): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    if ($query === '') {
        return mb_substr($text, 0, $radius * 2) . (mb_strlen($text) > $radius * 2 ? '…' : '');
    }
    $position = mb_stripos($text, $query);
    if ($position === false) {
        return mb_substr($text, 0, $radius * 2) . (mb_strlen($text) > $radius * 2 ? '…' : '');
    }
    $start = max(0, $position - $radius);
    $end = min(mb_strlen($text), $position + mb_strlen($query) + $radius);
    $snippet = mb_substr($text, $start, $end - $start);
    if ($start > 0) {
        $snippet = '…' . $snippet;
    }
    if ($end < mb_strlen($text)) {
        $snippet .= '…';
    }
    return $snippet;
}

function renderHighlightedSnippet(string $text, string $query): string {
    if ($text === '') {
        return '';
    }
    if ($query === '') {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }
    $pattern = '/' . preg_quote($query, '/') . '/iu';
    $marked = preg_replace_callback($pattern, function ($matches) {
        return '[[HIGHLIGHT]]' . $matches[0] . '[[ENDH]]';
    }, $text);
    $escaped = nl2br(htmlspecialchars($marked, ENT_QUOTES, 'UTF-8'));
    return str_replace(['[[HIGHLIGHT]]', '[[ENDH]]'], ['<mark>', '</mark>'], $escaped);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isManager) {
    $entryId = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;
    $contentZh = trim($_POST['content_zh'] ?? '');
    $contentEn = trim($_POST['content_en'] ?? '');
    $keywordsZh = normalizeKeywords(explode(',', $_POST['keywords_zh'] ?? ''));
    $keywordsEn = normalizeKeywords(explode(',', $_POST['keywords_en'] ?? ''));

    if ($contentZh === '' && $contentEn === '') {
        $saveError = '请至少填写中文或英文的知识内容。/ Please provide knowledge content in Chinese or English.';
    } else {
        try {
            if ($entryId > 0) {
                $updateStmt = $pdo->prepare('UPDATE askme_entries SET content_zh = ?, content_en = ?, updated_at = NOW() WHERE id = ?');
                $updateStmt->execute([$contentZh, $contentEn, $entryId]);
                $pdo->prepare('DELETE FROM askme_keywords WHERE entry_id = ?')->execute([$entryId]);
            } else {
                $insertStmt = $pdo->prepare('INSERT INTO askme_entries (content_zh, content_en, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
                $insertStmt->execute([$contentZh, $contentEn]);
                $entryId = (int)$pdo->lastInsertId();
            }

            $keywordInsert = $pdo->prepare('INSERT INTO askme_keywords (entry_id, keyword, locale) VALUES (?, ?, ?)');
            foreach ($keywordsZh as $keyword) {
                $keywordInsert->execute([$entryId, $keyword, 'zh']);
            }
            foreach ($keywordsEn as $keyword) {
                $keywordInsert->execute([$entryId, $keyword, 'en']);
            }
            $saveMessage = '知识库已更新。/ Knowledge base updated.';
        } catch (\PDOException $e) {
            $saveError = '保存知识库时出错，请联系管理员或运行 update_db.sql。/ Failed to save knowledge base. Please contact admin or run update_db.sql.';
        }
    }
}

$searchQuery = trim($_GET['q'] ?? '');
$lang = ($_GET['lang'] ?? '') === 'en' ? 'en' : 'zh';
$results = [
    'knowledge' => [],
    'offices' => [],
    'assets' => []
];

if ($searchQuery !== '') {
    try {
        $like = '%' . implode('%', array_filter(preg_split('/\s+/', $searchQuery))) . '%';
        $knowledgeStmt = $pdo->prepare("SELECT DISTINCT e.id, e.content_zh, e.content_en FROM askme_entries e LEFT JOIN askme_keywords k ON e.id = k.entry_id WHERE ((:lang = 'en' AND e.content_en LIKE :pattern_en) OR (:lang != 'en' AND e.content_zh LIKE :pattern_zh) OR k.keyword LIKE :pattern_kw) ORDER BY e.updated_at DESC, e.id DESC LIMIT 50");
        $knowledgeStmt->execute([
            ':lang' => $lang,
            ':pattern_en' => $like,
            ':pattern_zh' => $like,
            ':pattern_kw' => $like,
        ]);
        $results['knowledge'] = $knowledgeStmt->fetchAll();

        $officeStmt = $pdo->prepare("SELECT id, name, region, location_description FROM offices WHERE name LIKE :pattern_name OR region LIKE :pattern_region OR location_description LIKE :pattern_loc ORDER BY sort_order, name LIMIT 20");
        $officeStmt->execute([
            ':pattern_name' => $like,
            ':pattern_region' => $like,
            ':pattern_loc' => $like,
        ]);
        $results['offices'] = $officeStmt->fetchAll();

        $assetStmt = $pdo->prepare("SELECT id, asset_code, category, model, organization, remarks FROM assets WHERE asset_code LIKE :pattern_code OR category LIKE :pattern_category OR model LIKE :pattern_model OR organization LIKE :pattern_org OR remarks LIKE :pattern_remarks ORDER BY updated_at DESC LIMIT 20");
        $assetStmt->execute([
            ':pattern_code' => $like,
            ':pattern_category' => $like,
            ':pattern_model' => $like,
            ':pattern_org' => $like,
            ':pattern_remarks' => $like,
        ]);
        $results['assets'] = $assetStmt->fetchAll();
    } catch (\PDOException $e) {
        $searchError = '搜索时出错，请联系管理员或运行 update_db.sql。/ Search failed. Please contact admin or run update_db.sql.';
    }
}

$askmeEntries = [];
if ($isManager) {
    try {
        $entriesStmt = $pdo->query("SELECT e.*, GROUP_CONCAT(CASE WHEN k.locale = 'zh' THEN k.keyword END SEPARATOR ', ') AS keywords_zh, GROUP_CONCAT(CASE WHEN k.locale = 'en' THEN k.keyword END SEPARATOR ', ') AS keywords_en FROM askme_entries e LEFT JOIN askme_keywords k ON e.id = k.entry_id GROUP BY e.id ORDER BY e.updated_at DESC, e.id DESC");
        $askmeEntries = $entriesStmt->fetchAll();
    } catch (\PDOException $e) {
        $askmeEntries = [];
        if ($saveError === '') {
            $saveError = '无法读取知识库，请联系管理员或运行 update_db.sql。/ Unable to load knowledge base. Please contact admin or run update_db.sql.';
        }
    }
}
?>
<style>
  .askme-search-card { background: var(--app-surface-bg); border: 1px solid var(--app-surface-border); box-shadow: var(--app-card-shadow); }
  .askme-source-label { font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #6c757d; }
  .askme-snippet mark { background-color: var(--app-highlight-bg); color: var(--app-highlight-text); }
  .askme-section-title { display: flex; align-items: center; gap: .5rem; }
  .askme-hint { color: var(--app-muted-text); }
  .askme-result-card { border-left: 4px solid #0d6efd; }
  .askme-result-card.offices { border-color: #20c997; }
  .askme-result-card.assets { border-color: #fd7e14; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0" data-i18n="askme.title">不懂问我</h2>
    <p class="askme-hint mb-0" data-i18n="askme.subtitle">搜索办公地点、固定资产和AskMe知识库。</p>
  </div>
  <?php if ($isManager): ?>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#askmeAdminModal" data-i18n="askme.admin.manage_button">管理知识库</button>
  <?php endif; ?>
</div>
<div class="card askme-search-card mb-4">
  <div class="card-body">
    <form class="row g-3 align-items-center" method="GET" id="askmeSearchForm">
      <div class="col-lg-10 col-12">
        <label for="askmeQuery" class="form-label" data-i18n="askme.search_label">你想了解什么？</label>
        <input type="text" class="form-control form-control-lg" id="askmeQuery" name="q" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES); ?>" data-i18n-placeholder="askme.search_placeholder" placeholder="搜索办公地点、资产或知识库" required>
        <input type="hidden" name="lang" id="askmeLangField" value="<?= htmlspecialchars($lang, ENT_QUOTES); ?>">
      </div>
      <div class="col-lg-2 col-12 d-grid">
        <button type="submit" class="btn btn-primary btn-lg" data-i18n="askme.search_button">搜索</button>
      </div>
    </form>
  </div>
</div>
<?php if ($saveMessage): ?>
  <div class="alert alert-success" role="alert"><?= htmlspecialchars($saveMessage, ENT_QUOTES); ?></div>
<?php endif; ?>
<?php if ($saveError): ?>
  <div class="alert alert-danger" role="alert"><?= htmlspecialchars($saveError, ENT_QUOTES); ?></div>
<?php endif; ?>
<?php if ($searchError): ?>
  <div class="alert alert-danger" role="alert"><?= htmlspecialchars($searchError, ENT_QUOTES); ?></div>
<?php endif; ?>
<?php if ($searchQuery === ''): ?>
  <div class="alert alert-info" data-i18n="askme.empty_state">输入关键词开始搜索，支持模糊匹配。</div>
<?php else: ?>
  <div class="mb-4">
    <div class="askme-section-title mb-2">
      <span class="askme-source-label" data-i18n="askme.section.knowledge">AskMe 知识库</span>
      <span class="badge bg-secondary"><?= count($results['knowledge']); ?></span>
    </div>
    <?php if (empty($results['knowledge'])): ?>
      <p class="text-muted" data-i18n="askme.no_results">没有匹配的结果。</p>
    <?php else: ?>
      <div class="list-group mb-3">
        <?php foreach ($results['knowledge'] as $entry): ?>
          <?php $content = $lang === 'en' ? ($entry['content_en'] ?: $entry['content_zh']) : ($entry['content_zh'] ?: $entry['content_en']); ?>
          <?php $snippet = buildSnippet($content ?? '', $searchQuery); ?>
          <div class="list-group-item askme-snippet askme-result-card">
            <?= renderHighlightedSnippet($snippet, $searchQuery); ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="mb-4">
    <div class="askme-section-title mb-2">
      <span class="askme-source-label" data-i18n="askme.section.offices">办公地点</span>
      <span class="badge bg-secondary"><?= count($results['offices']); ?></span>
    </div>
    <?php if (empty($results['offices'])): ?>
      <p class="text-muted" data-i18n="askme.no_results">没有匹配的结果。</p>
    <?php else: ?>
      <div class="list-group mb-3">
        <?php foreach ($results['offices'] as $office): ?>
          <?php
            $officeFields = [$office['name'] ?? '', $office['region'] ?? '', $office['location_description'] ?? ''];
            $officeText = implode(' - ', array_filter($officeFields));
            $snippet = buildSnippet($officeText, $searchQuery);
          ?>
          <div class="list-group-item askme-snippet askme-result-card offices">
            <div class="fw-bold"><?= htmlspecialchars($office['name'] ?? '', ENT_QUOTES); ?></div>
            <div class="small text-muted"><?= htmlspecialchars($office['region'] ?? '', ENT_QUOTES); ?> <?= htmlspecialchars($office['location_description'] ?? '', ENT_QUOTES); ?></div>
            <div><?= renderHighlightedSnippet($snippet, $searchQuery); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="mb-4">
    <div class="askme-section-title mb-2">
      <span class="askme-source-label" data-i18n="askme.section.assets">固定资产</span>
      <span class="badge bg-secondary"><?= count($results['assets']); ?></span>
    </div>
    <?php if (empty($results['assets'])): ?>
      <p class="text-muted" data-i18n="askme.no_results">没有匹配的结果。</p>
    <?php else: ?>
      <div class="list-group mb-3">
        <?php foreach ($results['assets'] as $asset): ?>
          <?php
            $assetFields = [
                $asset['asset_code'] ?? '',
                $asset['category'] ?? '',
                $asset['model'] ?? '',
                $asset['organization'] ?? '',
                $asset['remarks'] ?? ''
            ];
            $assetText = implode(' - ', array_filter($assetFields));
            $snippet = buildSnippet($assetText, $searchQuery);
          ?>
          <div class="list-group-item askme-snippet askme-result-card assets">
            <div class="fw-bold"><?= htmlspecialchars($asset['asset_code'] ?? '', ENT_QUOTES); ?></div>
            <div class="small text-muted"><?= htmlspecialchars($asset['model'] ?? '', ENT_QUOTES); ?> <?= htmlspecialchars($asset['category'] ?? '', ENT_QUOTES); ?></div>
            <div><?= renderHighlightedSnippet($snippet, $searchQuery); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($isManager): ?>
<div class="modal fade" id="askmeAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="askme.admin.modal_title">编辑AskMe知识库</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <h6 data-i18n="askme.admin.new_entry">新增知识条目</h6>
          <form method="POST" class="border rounded p-3 bg-light">
            <input type="hidden" name="entry_id" value="0">
            <div class="mb-3">
              <label class="form-label" data-i18n="askme.admin.content_zh">中文内容</label>
              <textarea class="form-control" name="content_zh" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label" data-i18n="askme.admin.content_en">英文内容</label>
              <textarea class="form-control" name="content_en" rows="3"></textarea>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" data-i18n="askme.admin.keywords_zh">中文关键词（逗号分隔）</label>
                <input type="text" class="form-control" name="keywords_zh" data-i18n-placeholder="askme.admin.keywords_placeholder" placeholder="例如：出差, 报销, 办公室">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="askme.admin.keywords_en">English Keywords (comma separated)</label>
                <input type="text" class="form-control" name="keywords_en" placeholder="e.g. travel, reimbursement, office">
              </div>
            </div>
            <div class="mt-3 text-end">
              <button type="submit" class="btn btn-primary" data-i18n="askme.admin.save">保存</button>
            </div>
          </form>
        </div>
        <hr>
        <h6 class="mb-3" data-i18n="askme.admin.existing_entries">已有条目</h6>
        <?php if (empty($askmeEntries)): ?>
          <p class="text-muted" data-i18n="askme.admin.empty">暂无知识条目。</p>
        <?php else: ?>
          <div class="accordion" id="askmeEntriesAccordion">
            <?php foreach ($askmeEntries as $index => $entry): ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="askmeHeading<?= $entry['id']; ?>">
                  <button class="accordion-button <?= $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#askmeCollapse<?= $entry['id']; ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false'; ?>" aria-controls="askmeCollapse<?= $entry['id']; ?>">
                    <span class="me-2 text-muted">#<?= $entry['id']; ?></span>
                    <span class="text-truncate" style="max-width: 400px;">
                      <?= htmlspecialchars(mb_substr($entry['content_zh'] ?: $entry['content_en'], 0, 60), ENT_QUOTES); ?>
                    </span>
                  </button>
                </h2>
                <div id="askmeCollapse<?= $entry['id']; ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : ''; ?>" aria-labelledby="askmeHeading<?= $entry['id']; ?>" data-bs-parent="#askmeEntriesAccordion">
                  <div class="accordion-body">
                    <form method="POST" class="askme-edit-form">
                      <input type="hidden" name="entry_id" value="<?= $entry['id']; ?>">
                      <div class="mb-3">
                        <label class="form-label" data-i18n="askme.admin.content_zh">中文内容</label>
                        <textarea class="form-control" name="content_zh" rows="3"><?= htmlspecialchars($entry['content_zh'] ?? '', ENT_QUOTES); ?></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label" data-i18n="askme.admin.content_en">英文内容</label>
                        <textarea class="form-control" name="content_en" rows="3"><?= htmlspecialchars($entry['content_en'] ?? '', ENT_QUOTES); ?></textarea>
                      </div>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label" data-i18n="askme.admin.keywords_zh">中文关键词（逗号分隔）</label>
                          <input type="text" class="form-control" name="keywords_zh" value="<?= htmlspecialchars($entry['keywords_zh'] ?? '', ENT_QUOTES); ?>" data-i18n-placeholder="askme.admin.keywords_placeholder" placeholder="例如：出差, 报销, 办公室">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" data-i18n="askme.admin.keywords_en">English Keywords (comma separated)</label>
                          <input type="text" class="form-control" name="keywords_en" value="<?= htmlspecialchars($entry['keywords_en'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. travel, reimbursement, office">
                        </div>
                      </div>
                      <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary" data-i18n="askme.admin.save">保存</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const langField = document.getElementById('askmeLangField');
    const currentLang = localStorage.getItem('lang') || (langField ? langField.value : 'zh');
    if (langField) {
      langField.value = currentLang;
    }
    const searchForm = document.getElementById('askmeSearchForm');
    if (searchForm) {
      searchForm.addEventListener('submit', () => {
        const latestLang = localStorage.getItem('lang') || 'zh';
        if (langField) {
          langField.value = latestLang;
        }
      });
    }
  });
</script>
<?php include 'footer.php'; ?>
