<?php
include '../includes/auth_check.php';
// Lead Sources management page
include_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Source of Leads</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f3f5fb;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .page-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding-top: 72px;
    }

    .section-title {
      font-size: 1.6rem;
      font-weight: 700;
    }

    /* Keep dropdown menus visible within responsive tables */
    .table-responsive {
      overflow: visible !important;
    }

    .dropdown-menu {
      z-index: 5005;
    }
  </style>
</head>

<body>

  <?php include_once __DIR__ . '/../includes/header.php'; ?>

  <div class="page-wrap">
    <?php include_once __DIR__ . '/../includes/navbar-sales.php'; ?>

    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="section-title mb-0">Source of Leads</h4>
        <button id="btnAddSource" class="btn btn-dark">+ Add Source</button>
      </div>

      <div class="card">
        <div class="card-header d-flex gap-2 align-items-center">
          <input id="sourceSearch" class="form-control form-control-sm" placeholder="Search sources..."
            style="max-width:360px;">
        </div>
        <div class="card-body p-0">
          <div id="sourcesList">
            <?php include __DIR__ . '/leads_source_list_fragment.php'; ?>
          </div>
        </div>
      </div>
    </div>

    <?php include_once __DIR__ . '/../includes/modal-source.php'; ?>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sourceModalEl = document.getElementById('sourceModal');
    const sourceModal = sourceModalEl ? new bootstrap.Modal(sourceModalEl) : null;

    let currentSourcesPerPage = 10;
    function getSourcesPerPage() { return currentSourcesPerPage; }
    function loadSources(page = 1) {
      var per = getSourcesPerPage();
      var q = document.getElementById('sourceSearch') ? document.getElementById('sourceSearch').value.trim() : '';
      var url = 'leads_source_list_fragment.php?page=' + encodeURIComponent(page) + '&per_page=' + encodeURIComponent(per);
      if (q) url += '&q=' + encodeURIComponent(q);
      fetch(url).then(r => r.text()).then(html => {
        document.getElementById('sourcesList').innerHTML = html.replace(/<!--SOURCES_TOTAL:\d+-->/, ''); initSourceHandlers(); attachSourcePaginationHandlers();
      // sync footer per-page and wire change
      var foot = document.getElementById('sourcePerPageFooter'); if (foot) { foot.value = currentSourcesPerPage; foot.onchange = function () { currentSourcesPerPage = parseInt(this.value, 10) || 10; loadSources(1); }; }
      });
    }

    function initSourceHandlers() {
      document.querySelectorAll('.btn-edit-source').forEach(btn => btn.addEventListener('click', function () {
        const tr = this.closest('tr'); const id = tr ? tr.dataset.id : null; if (!id) return;
        fetch('get_source.php?id=' + encodeURIComponent(id)).then(r => r.json()).then(j => {
          if (!j.success) { alert(j.message || 'Failed to load'); return; }
          const d = j.data || {};
          document.getElementById('sourceId').value = d.id ?? '';
          document.getElementById('sourceName').value = d.name ?? '';
          document.getElementById('sourceDescription').value = d.description ?? '';
          // normalize status: accept numeric 1/0 or string 'active'/'inactive'
          let s = d.status;
          if (s === 1 || s === '1') s = 'active';
          else if (s === 0 || s === '0') s = 'inactive';
          document.getElementById('sourceStatus').value = s ?? '';
          sourceModal.show();
        });
      }));

      document.querySelectorAll('.btn-delete-source').forEach(btn => btn.addEventListener('click', function () {
        const tr = this.closest('tr'); const id = tr ? tr.dataset.id : null; if (!id) return; if (!confirm('Delete this source?')) return;
        fetch('delete_source.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'id=' + encodeURIComponent(id) })
          .then(r => r.json()).then(j => { alert(j.message || (j.success ? 'Deleted' : 'Error')); if (j.success) loadSources(); });
      }));
    }

    document.getElementById('btnAddSource').addEventListener('click', () => { document.getElementById('sourceForm').reset(); document.getElementById('sourceId').value = ''; sourceModal.show(); });

    document.getElementById('sourceForm').addEventListener('submit', function (e) {
      e.preventDefault(); const id = document.getElementById('sourceId').value; const url = id ? 'update_source.php' : 'create_source.php'; const fd = new FormData(this);
      fetch(url, { method: 'POST', body: fd }).then(r => r.json()).then(j => { alert(j.message || (j.success ? 'Saved' : 'Error')); if (j.success) { sourceModal.hide(); loadSources(); } });
    });

    document.addEventListener('DOMContentLoaded', () => {
      initSourceHandlers(); attachSourcePaginationHandlers();
      // If the server rendered the fragment, handlers above will wire it; also load via JS to ensure footer wiring and fresh data
      var foot = document.getElementById('sourcePerPageFooter'); if (foot) { foot.value = currentSourcesPerPage; foot.onchange = function () { currentSourcesPerPage = parseInt(this.value, 10) || 10; loadSources(1); }; }
      loadSources(1);
    });

    function attachSourcePaginationHandlers() {
      document.querySelectorAll('.sources-page-link').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); var p = parseInt(this.dataset.page, 10) || 1; loadSources(p); }); });
    }

    // header per-page removed; footer-only control is used
  </script>

</body>

</html>