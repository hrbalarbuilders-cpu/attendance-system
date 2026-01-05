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
					<select id="leadStatusFilter" class="form-select form-select-sm" style="max-width:150px;">
					  <option value="">All Status</option>
					  <option value="hot">Hot</option>
					  <option value="warm">Warm</option>
					  <option value="cold">Cold</option>
					</select>
					<select id="leadSalesFilter" class="form-select form-select-sm" style="max-width:200px;"></select>
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
	try{ if (leadModalEl) leadModal = new bootstrap.Modal(leadModalEl); }catch(e){}

	let currentLeadsPage = 1;
	let currentLeadsPerPage = 10;
	function getLeadsPerPage(){ return currentLeadsPerPage; }

	function loadLeads(page = 1){
		currentLeadsPage = page || 1;
		var per = getLeadsPerPage();
		var q = document.getElementById('leadSearch') ? document.getElementById('leadSearch').value.trim() : '';
		var status = document.getElementById('leadStatusFilter') ? document.getElementById('leadStatusFilter').value : '';
		var sales = document.getElementById('leadSalesFilter') ? document.getElementById('leadSalesFilter').value : '';
		var url = 'leads_list_fragment.php?page='+encodeURIComponent(currentLeadsPage)+'&per_page='+encodeURIComponent(per);
		if (q) url += '&q='+encodeURIComponent(q);
		if (status) url += '&lead_status='+encodeURIComponent(status);
		if (sales) url += '&sales_person='+encodeURIComponent(sales);
		fetch(url).then(r=>r.text()).then(html=>{
			var m = html.match(/<!--LEADS_TOTAL:(\d+)-->/);
			if (m && m[1]){ var el = document.getElementById('leadsCount'); if (el) el.innerText = m[1]; }
			document.getElementById('leadsList').innerHTML = html.replace(/<!--LEADS_TOTAL:\d+-->/, '');
				// sync footer per-page select with current value and wire change handler
				var footerSel = document.getElementById('leadPerPageFooter'); if (footerSel) { footerSel.value = currentLeadsPerPage; footerSel.onchange = function(){ currentLeadsPerPage = parseInt(this.value,10)||10; loadLeads(1); }; }
				initLeadHandlers(); attachPaginationHandlers();
		});
	}

	function attachPaginationHandlers(){
		document.querySelectorAll('.leads-page-link').forEach(function(a){
			a.addEventListener('click', function(e){ e.preventDefault(); var p = parseInt(this.dataset.page,10)||1; loadLeads(p); });
		});
	}



	function initLeadHandlers(){
		// helper to open edit modal for a given lead id
		function openEditLead(id){
			var idStr = (id === undefined || id === null) ? '' : String(id).trim();
			var idNum = parseInt(idStr, 10) || 0;
			if (!idStr || idNum <= 0){ return; }
			fetch('get_lead.php?id='+encodeURIComponent(idNum)).then(r=>r.json()).then(j=>{
						if (!j.success){ alert(j.message||'Failed to load'); return; }
						const d = j.data || {};
						const setIf = (id, val)=>{ const el = document.getElementById(id); if (!el) return; el.value = val === undefined || val === null ? '' : val; };
						fetch('get_sources.php').then(r=>r.json()).then(sdata=>{
										try{
															if (sdata && Array.isArray(sdata.sources)) populateLeadSelects({ sources: sdata.sources });
															// if lead has no source assigned but there is exactly one active source, auto-select it
															if ((d.lead_source_id === 0 || d.lead_source_id === null || d.lead_source_id === '' || d.lead_source_id === undefined) && Array.isArray(sdata.sources) && sdata.sources.length === 1){
																d.lead_source_id = sdata.sources[0].id;
																d.lead_source_name = sdata.sources[0].name || d.lead_source_name;
															}
										}catch(e){ }
								// populate fields
								setIf('leadId', d.id ?? '');
								setIf('leadName', d.name ?? '');
								setIf('leadContact', d.contact_number ?? '');
								setIf('leadEmail', d.email ?? '');
								setIf('leadLookingForId', d.looking_for_id ?? '');
								var lfEl = document.getElementById('leadLookingForId'); if (lfEl) lfEl.dispatchEvent(new Event('change'));
								// set saved LF type and subtype ids (if present)
								setIf('leadLookingForTypeId', d.looking_for_type_id ?? '');
								var hiddenSub = document.getElementById('leadLookingForSubtypeIds'); if (hiddenSub) hiddenSub.value = d.looking_for_subtypes ?? '';
								(function(){
									var srcSel = document.getElementById('leadSourceId');
									var srcVal = d.lead_source_id ?? '';
									srcVal = srcVal === null || srcVal === undefined ? '' : String(srcVal);
                                    
									var attempts = 0;
									function trySetSource(){
										attempts++;
										if (!srcSel) srcSel = document.getElementById('leadSourceId');
										if (srcSel){
											var opt = srcSel.querySelector('option[value="'+srcVal+'"]');
											if (opt){
												srcSel.value = srcVal;
												srcSel.dispatchEvent(new Event('change'));
												return true;
											}
											// try match by option text (name) in case value types or ids differ
											var match = Array.from(srcSel.options).find(function(o){ return (o.text||'').toString().trim().toLowerCase() === (d.lead_source_name||'').toString().trim().toLowerCase(); });
											if (match){
												srcSel.value = match.value;
												srcSel.dispatchEvent(new Event('change'));
												return true;
											}
											if (srcVal && d.lead_source_name){
												var o = document.createElement('option'); o.value = srcVal; o.text = d.lead_source_name; o.selected = true; srcSel.appendChild(o);
												srcSel.dispatchEvent(new Event('change'));
												return true;
											}
											if (srcVal === ''){
												srcSel.value = '';
												srcSel.dispatchEvent(new Event('change'));
												return true;
											}
										}
										if (attempts < 6){
											setTimeout(trySetSource, 120);
										} else {
										}
									}
									trySetSource();
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
									var stEl = document.getElementById('leadStatus');
									if (stEl){ stEl.value = st; stEl.dispatchEvent(new Event('change')); }
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
				}).catch(err=>{ alert('Failed to load lead data'); });
		}

		// attach click handlers (existing buttons)
		document.querySelectorAll('.btn-edit-lead').forEach(btn=> btn.addEventListener('click', function(){ const tr = this.closest('tr'); const id = tr && tr.dataset && tr.dataset.id ? tr.dataset.id : (this.dataset && this.dataset.leadId ? this.dataset.leadId : null); if (id) openEditLead(id); }));

		// delegated handler for dynamically added rows or if direct handlers fail
		var leadsListEl = document.getElementById('leadsList');
		if (leadsListEl){
			leadsListEl.addEventListener('click', function(e){
				var btn = e.target.closest && e.target.closest('.btn-edit-lead');
				if (btn){ var tr = btn.closest('tr'); var id = tr && tr.dataset && tr.dataset.id ? tr.dataset.id : (btn.dataset && btn.dataset.leadId ? btn.dataset.leadId : null); if (id) openEditLead(id); }
			});
		}

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
	// no inline sales filter — moved to navbar
  
	// wire search and filters to server-side load
	var leadSearchEl = document.getElementById('leadSearch');
	if (leadSearchEl){
		var debounce;
		leadSearchEl.addEventListener('input', function(){ clearTimeout(debounce); debounce = setTimeout(function(){ loadLeads(1); }, 300); });
	}
	var leadStatusFilterEl = document.getElementById('leadStatusFilter'); if (leadStatusFilterEl) leadStatusFilterEl.addEventListener('change', function(){ loadLeads(1); });
	var leadSalesFilterEl = document.getElementById('leadSalesFilter'); if (leadSalesFilterEl) leadSalesFilterEl.addEventListener('change', function(){ loadLeads(1); });
	// header per-page removed; footer-only control is handled in loadLeads

	// populate sales person filter with all sales persons (first page large)
	(function(){ fetch('get_sales_persons.php?page=1&per_page=1000').then(r=>r.json()).then(j=>{ if (j && Array.isArray(j.sales)){ var sel = document.getElementById('leadSalesFilter'); if (!sel) return; var opt = document.createElement('option'); opt.value=''; opt.text='All Sales'; sel.appendChild(opt); j.sales.forEach(function(s){ var o = document.createElement('option'); o.value = s.name || s.employee_id || s.id; o.text = s.name || ('#'+s.employee_id); sel.appendChild(o); }); } }); })();

	// header per-page removed; footer-only control is used

	// initial load
	document.addEventListener('DOMContentLoaded', ()=>{ initLeadHandlers(); loadLeads(1); });
</script>

</body>
</html>
