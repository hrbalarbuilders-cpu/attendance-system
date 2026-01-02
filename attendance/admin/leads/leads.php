<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Leads</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
		<style>
			body {
				background: #f3f5fb;
				font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			}
			.section-title {
				font-size: 1.8rem;
				font-weight: 700;
				letter-spacing: 0.02em;
			}
			.card-main { border-radius: 8px; box-shadow: 0 4px 14px rgba(15,23,42,0.06); overflow: hidden; }
			.card-main-header { background: #ffffff; }
		</style>
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
	let leadModal = null;
	try{ if (leadModalEl) leadModal = new bootstrap.Modal(leadModalEl); }catch(e){ console.error('Modal init failed', e); }

	function loadLeads(){
		fetch('leads_list_fragment.php')
			.then(r=>r.text())
			.then(html=>{ document.getElementById('leadsList').innerHTML = html; initLeadHandlers(); if (typeof filterLeads === 'function') filterLeads(); });
	}

	function initLeadHandlers(){
		// helper to open edit modal for a given lead id
		function openEditLead(id){
				if (!id) return;
				fetch('get_lead.php?id='+encodeURIComponent(id)).then(r=>r.json()).then(j=>{
						if (!j.success){ alert(j.message||'Failed to load'); return; }
						const d = j.data || {};
						const setIf = (id, val)=>{ const el = document.getElementById(id); if (!el) return; el.value = val === undefined || val === null ? '' : val; };
						fetch('get_sources.php').then(r=>r.json()).then(sdata=>{
								try{ if (sdata && Array.isArray(sdata.sources)) populateLeadSelects({ sources: sdata.sources }); }catch(e){}
								// populate fields
								setIf('leadId', d.id ?? '');
								setIf('leadName', d.name ?? '');
								setIf('leadContact', d.contact_number ?? '');
								setIf('leadEmail', d.email ?? '');
								setIf('leadLookingForId', d.looking_for_id ?? '');
								(function(){
									var srcSel = document.getElementById('leadSourceId');
									var srcVal = d.lead_source_id ?? '';
									if (srcSel){
										var opt = srcSel.querySelector('option[value="'+srcVal+'"]');
										if (opt){ srcSel.value = srcVal; }
										else if (srcVal && d.lead_source_name){
											var o = document.createElement('option'); o.value = srcVal; o.text = d.lead_source_name; o.selected = true; srcSel.appendChild(o);
										} else { srcSel.value = srcVal || ''; }
										srcSel.dispatchEvent(new Event('change'));
									}
								})();
								setIf('leadSalesPerson', d.sales_person ?? '');
								setIf('leadProfile', d.profile ?? '');
								setIf('leadPincode', d.pincode ?? '');
								setIf('leadCity', d.city ?? '');
								setIf('leadState', d.state ?? '');
								setIf('leadCountry', d.country ?? '');
								setIf('leadReference', d.reference ?? '');
								setIf('leadPurpose', d.purpose ?? '');
								(function(){
									var st = (d.lead_status || '').toString().toLowerCase();
									if (st === 'h' || st === 'hot') st = 'hot';
									else if (st === 'c' || st === 'cold') st = 'cold';
									else if (st === 'w' || st === 'warm' || st === 'warn') st = 'warm';
									var stEl = document.getElementById('leadStatus'); if (stEl){ stEl.value = st; stEl.dispatchEvent(new Event('change')); }
								})();
								setIf('leadNotes', d.notes ?? '');
								if (leadModal) leadModal.show();
						}).catch(()=>{
								// fallback: populate anyway
								setIf('leadId', d.id ?? '');
								setIf('leadName', d.name ?? '');
								setIf('leadContact', d.contact_number ?? '');
								setIf('leadEmail', d.email ?? '');
								setIf('leadLookingForId', d.looking_for_id ?? '');
								setIf('leadSourceId', d.lead_source_id ?? '');
								setIf('leadSalesPerson', d.sales_person ?? '');
								setIf('leadProfile', d.profile ?? '');
								setIf('leadPincode', d.pincode ?? '');
								setIf('leadCity', d.city ?? '');
								setIf('leadState', d.state ?? '');
								setIf('leadCountry', d.country ?? '');
								setIf('leadReference', d.reference ?? '');
								setIf('leadPurpose', d.purpose ?? '');
								setIf('leadStatus', d.lead_status ?? '');
								setIf('leadNotes', d.notes ?? '');
								if (leadModal) leadModal.show();
						});
				}).catch(err=>{ console.error('Failed to fetch lead', err); alert('Failed to load lead data'); });
		}

		// attach click handlers (existing buttons)
		document.querySelectorAll('.btn-edit-lead').forEach(btn=> btn.addEventListener('click', function(){ const tr = this.closest('tr'); const id = tr ? tr.dataset.id : (this.dataset && this.dataset.leadId ? this.dataset.leadId : null); if (id) openEditLead(id); }));

		// delegated handler for dynamically added rows or if direct handlers fail
		var leadsListEl = document.getElementById('leadsList');
		if (leadsListEl){
			leadsListEl.addEventListener('click', function(e){
				var btn = e.target.closest && e.target.closest('.btn-edit-lead');
				if (btn){ var tr = btn.closest('tr'); var id = tr ? tr.dataset.id : (btn.dataset && btn.dataset.leadId ? btn.dataset.leadId : null); if (id) openEditLead(id); }
			});
		}
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
