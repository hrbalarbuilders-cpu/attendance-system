<?php
// Fragment of employees_list.php used for AJAX loads
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-title mb-0">Employee Management</h1>
    <a href="add_employee.php" class="btn btn-dark">+ Add Employee</a>
  </div>
  <div class="card card-main">
    <div class="card-header card-main-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div class="d-flex align-items-center gap-3">
        <div style="min-width:200px; max-width:300px;">
          <input type="search" id="employeeSearch" class="form-control form-control-sm" placeholder="Search employees..." value="<?php echo isset($q) ? htmlspecialchars($q) : ''; ?>">
        </div>
        <small class="text-muted">Total: <?php echo isset($totalCount) ? (int)$totalCount : ($result ? (int)$result->num_rows : 0); ?></small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-dark btn-sm d-flex align-items-center gap-2" id="importBtn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
            <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
          </svg>
          Import
        </button>
        <button type="button" class="btn btn-dark btn-sm d-flex align-items-center gap-2" id="exportBtn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
          </svg>
          Export
        </button>
        <label class="switch-icon mb-0">
          <input type="checkbox" id="archiveToggle">
          <span class="slider-icon">
            <svg class="icon-filter" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
            </svg>
            <svg class="icon-trash" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
              <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
            </svg>
          </span>
        </label>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead id="employeeTableHead">
          <tr class="text-nowrap">
            <th>#</th>
            <th>Emp Code</th>
            <th>Name</th>
            <th>Department</th>
            <th>Shift</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="employeeTableBody">
        <?php
        $i = 1;
        $offset = isset($offset) ? (int)$offset : 0;
        if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $department = $row["department_name"] ?? "-";
            $shiftName = "-";
            if (!empty($row['shift_name'])) {
              $startDisp = $row['start_time'] ? date('h:i A', strtotime($row['start_time'])) : "";
              $endDisp   = $row['end_time']   ? date('h:i A', strtotime($row['end_time']))   : "";
              $shiftName = $row['shift_name'] . (($startDisp && $endDisp) ? " ($startDisp – $endDisp)" : "");
            }
            $isActive  = (int)($row['status'] ?? 1) === 1;
            $updatedTs = $row['updated_at'] ?? $row['created_at'] ?? null;
            $updated   = $updatedTs ? date('d M Y, h:i A', strtotime($updatedTs)) : '-';
        ?>
          <tr>
            <td data-label="#"><?php echo $offset + $i++; ?></td>
            <td data-label="Emp Code"><?php echo htmlspecialchars($row['emp_code'] ?? ''); ?></td>
            <td data-label="Name">
              <div class="fw-semibold"><?php echo htmlspecialchars($row['name'] ?? ''); ?></div>
              <div class="small text-muted">Updated: <?php echo $updated; ?></div>
            </td>
            <td data-label="Department"><?php echo htmlspecialchars($department); ?></td>
            <td data-label="Shift"><?php echo htmlspecialchars($shiftName); ?></td>
            <td data-label="Status">
              <label class="switch mb-0" title="Enable/Disable">
                <input type="checkbox"
                       class="status-toggle"
                       data-id="<?php echo (int)$row['id']; ?>"
                       <?php echo $isActive ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </td>
            <td data-label="Action">
              <div class="dropdown">
                <button class="btn btn-sm btn-light border-0 px-2 py-1 d-flex align-items-center justify-content-center" type="button" id="actionMenu<?php echo $row['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow:none;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-three-dots-vertical text-secondary" viewBox="0 0 16 16">
                    <path d="M9.5 2a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                  </svg>
                </button>
                <ul class="dropdown-menu shadow-sm rounded-3 py-2" aria-labelledby="actionMenu<?php echo $row['id']; ?>" style="min-width:140px;">
                  <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="view_employee.php?id=<?php echo (int)$row['id']; ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill text-primary" viewBox="0 0 16 16">
                        <path d="M1 14s-1 0-1-1 1-4 7-4 7 3 7 4-1 1-1 1H1zm7-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm4.5 1a.5.5 0 0 1 0-1h2a.5.5 0 0 1 0 1h-2zm0 2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 0 1h-2zm0 2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 0 1h-2z"/>
                      </svg>
                      View
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="edit_employee.php?id=<?php echo (int)$row['id']; ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square text-warning" viewBox="0 0 16 16">
                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706l-1 1a.5.5 0 0 1-.708 0l-1-1a.5.5 0 0 1 0-.707l1-1a.5.5 0 0 1 .707 0l1 1zm-1.75 2.456-1-1L4 11.293V12.5a.5.5 0 0 0 .5.5h1.207l8.043-8.104z"/>
                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-7a.5.5 0 0 0-1 0v7a.5.5 0 0 1-1.5 1.5h-11A.5.5 0 0 1 1 13.5v-11A.5.5 0 0 1 2.5 1H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                      </svg>
                      Edit
                    </a>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        <?php
          }
        } else {
        ?>
        <tr>
          <td colspan="7" class="text-center py-4 text-muted">
            No employees found. Please add one.
          </td>
        </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>

    <?php
      $totalCountSafe = isset($totalCount) ? (int)$totalCount : 0;
      $pageSafe = isset($page) ? (int)$page : 1;
      $perSafe = isset($per_page) ? (int)$per_page : 10;
      $totalPages = max(1, (int)ceil(($totalCountSafe ?: 0) / max(1, $perSafe)));
      $startRec = $totalCountSafe > 0 ? ($offset + 1) : 0;
      $endRec = $totalCountSafe > 0 ? ($offset + ($result ? $result->num_rows : 0)) : 0;
    ?>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-3 px-2">
        <ul class="pagination mb-0">
          <?php
            $start = max(1, $pageSafe - 3);
            $end = min($totalPages, $pageSafe + 3);
            if ($pageSafe > 1) echo '<li class="page-item"><a href="#" class="page-link emp-page-link" data-page="'.($pageSafe-1).'">Previous</a></li>';
            if ($start > 1) echo '<li class="page-item"><a href="#" class="page-link emp-page-link" data-page="1">1</a></li>' . ($start>2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'' );
            for ($p = $start; $p <= $end; $p++){
              $cls = $p == $pageSafe ? ' page-item active' : ' page-item';
              echo '<li class="'.$cls.'"><a href="#" class="page-link emp-page-link" data-page="'.$p.'">'.$p.'</a></li>';
            }
            if ($end < $totalPages) echo ($end < $totalPages-1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>':'') . '<li class="page-item"><a href="#" class="page-link emp-page-link" data-page="'.$totalPages.'">'.$totalPages.'</a></li>';
            if ($pageSafe < $totalPages) echo '<li class="page-item"><a href="#" class="page-link emp-page-link" data-page="'.($pageSafe+1).'">Next</a></li>';
          ?>
        </ul>
      </nav>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mt-2 px-2 pb-2">
      <div class="small text-muted">Record <?php echo $startRec; ?>–<?php echo $endRec; ?> of <?php echo $totalCountSafe; ?></div>
      <div class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0">Rows:</label>
        <select id="employeePerPageFooter" class="form-select form-select-sm" style="width:80px;">
          <option value="10" <?php if($perSafe==10) echo 'selected'; ?>>10</option>
          <option value="25" <?php if($perSafe==25) echo 'selected'; ?>>25</option>
          <option value="50" <?php if($perSafe==50) echo 'selected'; ?>>50</option>
          <option value="100" <?php if($perSafe==100) echo 'selected'; ?>>100</option>
        </select>
      </div>
    </div>
  </div>
  


<script type="text/javascript" id="employeeSearchScriptSource">
// Employees list pagination + server-side search (executed via injection in employees.php)
(function(){
  var input = document.getElementById('employeeSearch');
  var perSel = document.getElementById('employeePerPageFooter');

  function getContentArea(){
    return document.getElementById('contentArea') || document.querySelector('[data-hr-content]');
  }

  function safeShowLoader(){
    try{ if (typeof showLoader === 'function') showLoader(); }catch(e){}
  }
  function safeHideLoader(){
    try{ if (typeof hideLoader === 'function') hideLoader(); }catch(e){}
  }

  function loadEmployees(page){
    page = parseInt(page, 10) || 1;
    var per = perSel ? (parseInt(perSel.value, 10) || 10) : 10;
    var q = input ? String(input.value || '').trim() : '';
    var url = 'employees_list.php?ajax=1&page=' + encodeURIComponent(page) + '&per_page=' + encodeURIComponent(per);
    if (q) url += '&q=' + encodeURIComponent(q);

    var contentArea = getContentArea();
    if (!contentArea) {
      // Fallback for standalone page
      window.location.assign(url.replace('?ajax=1&', '?'));
      return;
    }

    safeShowLoader();
    fetch(url, { credentials: 'same-origin' })
      .then(function(r){ return r.text(); })
      .then(function(html){
        contentArea.innerHTML = html;
        try{ if (typeof initEmployeesListEvents === 'function') initEmployeesListEvents(); }catch(e){}
      })
      .catch(function(){
        contentArea.innerHTML = "<div class='alert alert-danger m-3'>Failed to load employees.</div>";
      })
      .finally(function(){
        safeHideLoader();
      });
  }

  // Pagination clicks
  document.querySelectorAll('.emp-page-link').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      var p = parseInt(this.dataset.page, 10) || 1;
      loadEmployees(p);
    });
  });

  // Per-page change
  if (perSel) {
    perSel.addEventListener('change', function(){
      loadEmployees(1);
    });
  }

  // Search (debounced)
  var t = null;
  if (input) {
    input.addEventListener('input', function(){
      if (t) clearTimeout(t);
      t = setTimeout(function(){ loadEmployees(1); }, 250);
    });
  }
})();
</script>
