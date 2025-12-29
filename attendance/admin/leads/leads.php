<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Leads</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Leads</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div style="padding-top:72px; max-width:1200px; margin: 0 auto;">
<?php include_once __DIR__ . '/../includes/navbar-sales.php'; ?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h4 class="section-title mb-0">Leads</h4>
		<button id="btnAddLead" class="btn btn-dark">+ Add Lead</button>
	</div>
	<div class="card card-main">
		<div class="card-header card-main-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
			<div class="d-flex align-items-center gap-3 w-100">
				<div class="w-100" style="max-width:360px;">
					<input type="search" id="leadSearch" class="form-control form-control-sm" placeholder="Search leads...">
				</div>
				<small class="text-muted ms-2">Total: <span id="leadsCount"></span></small>
			</div>
		</div>
		<div class="card-body p-0">
			<div id="leadsList">
				<?php include __DIR__ . '/leads_list_fragment.php'; ?>
			</div>
		</div>
	</div>
</div>

<?php include_once __DIR__ . '/../includes/modal-lead.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
	const leadModalEl = document.getElementById('leadModal');
	const leadModal = new bootstrap.Modal(leadModalEl);

	function loadLeads(){
		fetch('leads_list_fragment.php')
			.then(r=>r.text())
			.then(html=>{ document.getElementById('leadsList').innerHTML = html; initLeadHandlers(); if (typeof filterLeads === 'function') filterLeads(); });
	}

	function initLeadHandlers(){
		document.querySelectorAll('.btn-edit-lead').forEach(btn=>{
			btn.addEventListener('click', function(){
				const tr = this.closest('tr');
				const id = tr.dataset.id;
				if (!id) return;
				fetch('get_lead.php?id='+encodeURIComponent(id)).then(r=>r.json()).then(j=>{
					if (!j.success){ alert(j.message||'Failed to load'); return; }
					const d = j.data;
					document.getElementById('leadId').value = d.id || '';
					document.getElementById('leadName').value = d.name || '';
					document.getElementById('leadContact').value = d.contact_number || '';
					document.getElementById('leadEmail').value = d.email || '';
					document.getElementById('leadLookingForId').value = d.looking_for_id || '';
					document.getElementById('leadSourceId').value = d.lead_source_id || '';
					document.getElementById('leadSalesPerson').value = d.sales_person || '';
					document.getElementById('leadProfile').value = d.profile || '';
					document.getElementById('leadPincode').value = d.pincode || '';
					document.getElementById('leadCity').value = d.city || '';
					document.getElementById('leadState').value = d.state || '';
					document.getElementById('leadCountry').value = d.country || '';
					document.getElementById('leadReference').value = d.reference || '';
					document.getElementById('leadPurpose').value = d.purpose || '';
					document.getElementById('leadStatus').value = d.lead_status || '';
					document.getElementById('leadNotes').value = d.notes || '';
					leadModal.show();
				});
			});
		});

		document.querySelectorAll('.btn-delete-lead').forEach(btn=>{
			btn.addEventListener('click', function(){
				const tr = this.closest('tr');
				const id = tr.dataset.id;
				if (!id) return;
				if (!confirm('Delete this lead?')) return;
				fetch('delete_lead.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+encodeURIComponent(id)})
					.then(r=>r.json()).then(j=>{ alert(j.message || (j.success? 'Deleted':'Error')); if (j.success) loadLeads(); });
			});
		});
	}

	document.getElementById('btnAddLead').addEventListener('click', ()=>{
		document.getElementById('leadForm').reset();
		document.getElementById('leadId').value = '';
		leadModal.show();
	});

	document.getElementById('leadForm').addEventListener('submit', function(e){
		e.preventDefault();
		const id = document.getElementById('leadId').value;
		const url = id? 'update_lead.php' : 'create_lead.php';
		const fd = new FormData(this);
		fetch(url, {method:'POST', body:fd}).then(r=>r.json()).then(j=>{
			alert(j.message || (j.success? 'Saved':'Error'));
			if (j.success){ leadModal.hide(); loadLeads(); }
		});
	});

	// init on page load
	document.addEventListener('DOMContentLoaded', ()=>{ initLeadHandlers(); });
  
	// search filter for leads table
	function filterLeads(){
		var input = document.getElementById('leadSearch');
		var filter = input ? input.value.toLowerCase().trim() : '';
		var tbody = document.querySelector('#leadsTable tbody');
		if (!tbody) return;
		var rows = tbody.getElementsByTagName('tr');
		var any = 0;
		for (var i=0;i<rows.length;i++){
			var r = rows[i];
			var text = r.innerText.toLowerCase();
			if (filter === '' || text.indexOf(filter) !== -1){ r.style.display = ''; any++; }
			else { r.style.display = 'none'; }
		}
		document.getElementById('leadsCount').innerText = any;
	}
	var leadSearchEl = document.getElementById('leadSearch');
	if (leadSearchEl){ leadSearchEl.addEventListener('input', filterLeads); }
	// update count initially
	setTimeout(filterLeads, 200);
</script>

</body>
</html>
