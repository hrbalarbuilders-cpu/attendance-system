<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Management</title>
  <link rel="icon" href="data:,">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php
include 'db.php';
$sql = "
SELECT e.*, d.department_name, s.shift_name, s.start_time, s.end_time
FROM employees e
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN shifts s ON s.id = e.shift_id
ORDER BY e.id DESC
";
$result = $con->query($sql);
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-title mb-0">Employee Management</h1>
    <a href="add_employee.php" class="btn btn-dark">+ Add Employee</a>
  </div>
  <div class="card card-main">
    <div class="card-header card-main-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div class="d-flex align-items-center gap-3 w-100">
        <div class="w-100" style="max-width:300px;">
          <input type="search" id="employeeSearch" class="form-control form-control-sm" placeholder="Search employees...">
        </div>
        <small class="text-muted ms-2">Total: <?php echo $result ? $result->num_rows : 0; ?></small>
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
          <tr class="text-nowrap">
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['emp_code'] ?? ''); ?></td>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($row['name'] ?? ''); ?></div>
              <div class="small text-muted">Updated: <?php echo $updated; ?></div>
            </td>
            <td><?php echo htmlspecialchars($department); ?></td>
            <td><?php echo htmlspecialchars($shiftName); ?></td>
            <td>
              <span class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?>">
                <?php echo $isActive ? 'Active' : 'Inactive'; ?>
              </span>
            </td>
            <td>
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
  </div>
  

<script type="text/javascript" id="employeeSearchScriptSource">
// Employee search/filter logic for AJAX injection
function filterEmployeeTable() {
  var input = document.getElementById('employeeSearch');
  var filter = input ? input.value.toLowerCase().trim() : '';
  var tbody = document.getElementById('employeeTableBody');
  var rows = tbody ? tbody.getElementsByTagName('tr') : [];
  var anyVisible = false;
  var noDataRow = null;
  var matchedNames = [];
  for (var i = 0; i < rows.length; i++) {
    var row = rows[i];
    // Identify the 'no employees found' row by its unique text
    if (row.innerText && row.innerText.trim().toLowerCase().includes('no employees found')) {
      noDataRow = row;
      continue;
    }
    // Only filter rows with actual employee data
    // Get relevant columns: Emp Code, Name, Department, Shift
    var tds = row.getElementsByTagName('td');
    var searchText = '';
    var nameText = '';
    if (tds.length > 4) {
      searchText = [tds[1], tds[2], tds[3], tds[4]]
        .map(function(td) { return (td.innerText || td.textContent || '').toLowerCase().trim(); })
        .join(' ');
      // Name column (tds[2])
      nameText = (tds[2].innerText || tds[2].textContent || '').trim();
    }
    // Use includes for partial match
    if (filter === '' || searchText.includes(filter)) {
      row.style.display = '';
      anyVisible = true;
      if (filter !== '' && nameText) {
        matchedNames.push(nameText);
      }
    } else {
      row.style.display = 'none';
    }
  }
  if (noDataRow) {
    noDataRow.style.display = anyVisible ? 'none' : '';
  }
  // Remove matched names display; only show 'no matching employees' if needed
  var resultsDiv = document.getElementById('searchResults');
  if (resultsDiv) {
    if (filter !== '' && matchedNames.length === 0) {
      resultsDiv.innerHTML = '<span class="text-danger">No matching employees found.</span>';
    } else {
      resultsDiv.innerHTML = '';
    }
  }
}
var searchInput = document.getElementById('employeeSearch');
if (searchInput) {
  searchInput.addEventListener('input', filterEmployeeTable);
  // Run once on load to ensure correct state
  filterEmployeeTable();
}
</script>
</body>
<script>
// Ensure script runs after all HTML is loaded
var scriptStatus = document.getElementById('scriptStatus');
if (scriptStatus) {
  scriptStatus.style.display = 'block';
  scriptStatus.textContent = 'Employee search script is running.';
  scriptStatus.classList.remove('alert-danger');
  scriptStatus.classList.add('alert-info');
}
var testDiv = document.getElementById('testDiv');
if (testDiv) {
  testDiv.textContent = 'TEST DIV: JS IS RUNNING!';
  testDiv.style.background = 'lime';
  testDiv.style.color = 'black';
}
function filterEmployeeTable() {
  var input = document.getElementById('employeeSearch');
  if (scriptStatus && input) {
    scriptStatus.textContent = 'Employee search script is running. Current filter: "' + input.value + '"';
  }
  var filter = input ? input.value.toLowerCase().trim() : '';
  var tbody = document.getElementById('employeeTableBody');
  var rows = tbody ? tbody.getElementsByTagName('tr') : [];
  var anyVisible = false;
  var noDataRow = null;
  var matchedNames = [];
  for (var i = 0; i < rows.length; i++) {
    var row = rows[i];
    // Identify the 'no employees found' row by its unique text
    if (row.innerText && row.innerText.trim().toLowerCase().includes('no employees found')) {
      noDataRow = row;
      continue;
    }
    // Only filter rows with actual employee data
    // Get relevant columns: Emp Code, Name, Department, Shift
    var tds = row.getElementsByTagName('td');
    var searchText = '';
    var nameText = '';
    if (tds.length > 4) {
      searchText = [tds[1], tds[2], tds[3], tds[4]]
        .map(function(td) { return (td.innerText || td.textContent || '').toLowerCase().trim(); })
        .join(' ');
      // Name column (tds[2])
      nameText = (tds[2].innerText || tds[2].textContent || '').trim();
    }
    // Use includes for partial match
    if (filter === '' || searchText.includes(filter)) {
      row.style.display = '';
      anyVisible = true;
      if (filter !== '' && nameText) {
        matchedNames.push(nameText);
      }
      //console.log('Row', i, 'VISIBLE:', nameText, '|', searchText);
    } else {
      row.style.display = 'none';
      //console.log('Row', i, 'HIDDEN:', nameText, '|', searchText);
    }
  }
  if (noDataRow) {
    noDataRow.style.display = anyVisible ? 'none' : '';
  }
  // Show matched names under the table
  var resultsDiv = document.getElementById('searchResults');
  if (resultsDiv) {
    if (filter !== '' && matchedNames.length > 0) {
      resultsDiv.innerHTML = '<strong>Matching Employees:</strong><ul class="list-group list-group-flush">' +
        matchedNames.map(function(name) { return '<li class="list-group-item py-1">' + name + '</li>'; }).join('') + '</ul>';
    } else if (filter !== '') {
      resultsDiv.innerHTML = '<span class="text-danger">No matching employees found.</span>';
    } else {
      resultsDiv.innerHTML = '';
    }
  }
}
var searchInput = document.getElementById('employeeSearch');
if (searchInput) {
  searchInput.addEventListener('input', filterEmployeeTable);
  // Run once on load to ensure correct state
  filterEmployeeTable();
}
</script>
