<?php
include_once __DIR__ . '/../config/db.php';
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Sales Persons</title>
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

    .container {
      max-width: 1100px
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
    <?php include __DIR__ . '/../includes/navbar-sales.php'; ?>

    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="section-title mb-0">Sales Persons</h4>
        <div class="d-flex gap-2 align-items-center">
          <!-- search removed as requested -->
          <button id="btnAddSP" class="btn btn-dark">+ Add Sales Person</button>
        </div>
      </div>

      <div id="spList"></div>
    </div>
  </div>

  <?php // simple modal for adding sales person by selecting employee ?>
  <div class="modal fade" id="spModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="spForm">
        <div class="modal-header">
          <h5 class="modal-title">Add Sales Person</h5>
          <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label>Employee</label>
            <select id="spEmployee" class="form-select"></select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let currentSPPage = 1;
    let currentSPPerPage = 10;
    function loadSP(page = 1) {
      currentSPPage = page || 1;
      fetch('get_sales_persons.php?page=' + encodeURIComponent(currentSPPage) + '&per_page=' + encodeURIComponent(currentSPPerPage)).then(r => r.json()).then(j => {
        // Wrap table in a Bootstrap card + responsive container
        var out = '<div class="card"><div class="card-body p-2">';
        out += '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        if (j && Array.isArray(j.sales) && j.sales.length) {
          j.sales.forEach(function (s) {
            var statusText = '';
            var st = ('' + (s.status || '')).trim();
            if (st === '1' || st.toLowerCase() === 'active') statusText = 'Active';
            else if (st === '0' || st.toLowerCase() === 'inactive') statusText = 'Inactive';
            else statusText = s.status || '';
            out += '<tr><td>' + s.id + '</td><td>' + (s.name || '') + '</td><td>' + statusText + '</td>';
            out += '<td><div class="dropdown position-relative">';
            out += '<button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Actions" title="Actions">&#8942;</button>';
            out += '<ul class="dropdown-menu dropdown-menu-end">';
            out += '<li><button type="button" data-id="' + s.id + '" class="dropdown-item text-danger btn-del-sp">Delete</button></li>';
            out += '</ul></div></td></tr>';
          });
        } else { out += '<tr><td colspan="4">No sales persons found.</td></tr>'; }
        out += '</tbody></table></div>';
        // pagination
        var total = j && j.total ? parseInt(j.total, 10) : 0;
        if (total > currentSPPerPage) {
          var totalPages = Math.max(1, Math.ceil(total / currentSPPerPage));
          out += '<nav class="mt-2"><ul class="pagination mb-0">';
          if (currentSPPage > 1) out += '<li class="page-item"><a href="#" class="page-link sp-page-link" data-page="' + (currentSPPage - 1) + '">Previous</a></li>';
          var start = Math.max(1, currentSPPage - 3);
          var end = Math.min(totalPages, currentSPPage + 3);
          if (start > 1) out += '<li class="page-item"><a href="#" class="page-link sp-page-link" data-page="1">1</a></li>' + (start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
          for (var p = start; p <= end; p++) { out += '<li class="' + (p === currentSPPage ? 'page-item active' : 'page-item') + '"><a href="#" class="page-link sp-page-link" data-page="' + p + '">' + p + '</a></li>'; }
          if (end < totalPages) out += (end < totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') + '<li class="page-item"><a href="#" class="page-link sp-page-link" data-page="' + totalPages + '">' + totalPages + '</a></li>';
          if (currentSPPage < totalPages) out += '<li class="page-item"><a href="#" class="page-link sp-page-link" data-page="' + (currentSPPage + 1) + '">Next</a></li>';
          out += '</ul></nav>';
        }
        // footer with record range and per-page select
        var startRec = total > 0 ? ((currentSPPage - 1) * currentSPPerPage + 1) : 0;
        var endRec = total > 0 ? Math.min(total, (currentSPPage * currentSPPerPage)) : 0;
        out += '<div class="d-flex justify-content-between align-items-center mt-2 px-2">';
        out += '<div class="small text-muted">Record ' + startRec + '–' + endRec + ' of ' + total + '</div>';
        out += '<div class="d-flex align-items-center gap-2"><label class="small text-muted mb-0">Rows:</label>';
        out += '<select id="spPerPageFooter" class="form-select form-select-sm" style="width:80px;"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>';
        out += '</div></div>';
        out += '</div></div>';
        document.getElementById('spList').innerHTML = out;
        // sync footer select value and wire change
        var foot = document.getElementById('spPerPageFooter'); if (foot) { foot.value = currentSPPerPage; foot.onchange = function () { currentSPPerPage = parseInt(this.value, 10) || 10; loadSP(1); }; }
        document.querySelectorAll('.btn-del-sp').forEach(function (b) { b.addEventListener('click', function () { if (!confirm('Delete?')) return; fetch('delete_sales_person.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'id=' + encodeURIComponent(this.dataset.id) }).then(r => r.json()).then(j => { if (j.success) loadSP(currentSPPage); else alert(j.message || 'Error'); }); }); });
        document.querySelectorAll('.sp-page-link').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); var p = parseInt(this.dataset.page, 10) || 1; loadSP(p); }); });
        // header per-page removed; footer-only control handled above
      });
    }
    function loadEmployeesIntoSelect() {
      fetch('../hr/get_employees_json.php?active=1').then(r => r.json()).then(j => {
        var sel = document.getElementById('spEmployee'); sel.innerHTML = '';
        if (j && Array.isArray(j.employees)) {
          j.employees.forEach(function (e) { var o = document.createElement('option'); o.value = String(e.id); o.text = (e.name || '').toString(); sel.appendChild(o); });
        }
      }).catch(function (err) {
        console.error('Failed to load employees', err);
        // fallback: keep select empty
      });
    }
    document.getElementById('btnAddSP').addEventListener('click', function () { loadEmployeesIntoSelect(); var m = new bootstrap.Modal(document.getElementById('spModal')); m.show(); });
    document.getElementById('spForm').addEventListener('submit', function (e) { e.preventDefault(); var emp = document.getElementById('spEmployee').value; fetch('create_sales_person.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'user_id=' + encodeURIComponent(emp) }).then(r => r.json()).then(j => { if (j.success) { loadSP(currentSPPage); var m = bootstrap.Modal.getInstance(document.getElementById('spModal')); if (m) m.hide(); } else alert(j.message || 'Error'); }); });
    // search input removed; no search handler
    // header per-page removed; footer-only control is used
    // initial load
    document.addEventListener('DOMContentLoaded', function () { loadSP(1); });
  </script>
</body>

</html>