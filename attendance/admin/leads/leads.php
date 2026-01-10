<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Leads</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
		<!-- jQuery + Select2 for improved selects in modal -->
		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
			/* No internal table scrollbars: wrap content instead of scrolling */
			#leadsList .table-responsive { overflow: visible !important; }
			#leadsList #leadsTable { table-layout: fixed; width: 100%; }
			#leadsList #leadsTable th:not(.text-nowrap),
			#leadsList #leadsTable td:not(.text-nowrap) { white-space: normal; word-break: break-word; }
			/* Keep dropdown menu above the table/footer */
			#leadsList .dropdown-menu { z-index: 5005; }
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
<!-- jQuery + Select2 (used by editLead helper) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function showLeadApiError(message){
	try{
		var msg = (message === undefined || message === null) ? '' : String(message);
		msg = msg.trim();
		if (!msg) msg = 'Request failed';
		var now = Date.now();
		if (window._leadApiLastAlert && (now - window._leadApiLastAlert) < 1500) return;
		window._leadApiLastAlert = now;
		alert(msg);
	}catch(e){}
}

function fetchJson(url){
	return fetch(url, { credentials: 'same-origin' })
		.then(function(r){ return r.text(); })
		.then(function(text){
			var json;
			try{ json = JSON.parse(text); }catch(e){ showLeadApiError('Invalid server response'); throw e; }
			if (json && json.success === false){ showLeadApiError(json.message || 'Request failed'); }
			return json;
		});
}

function loadLeadFormLookups(){
	var bundleUrl = (window.__leadApiUrls && window.__leadApiUrls.bundle) ? window.__leadApiUrls.bundle : 'get_lead_form_payload.php';
	return fetchJson(bundleUrl + '?per_page=1000').then(function(j){
		if (!j || j.success === false) return null;
		try{ if (typeof window.storeLeadBundlePayload === 'function') window.storeLeadBundlePayload(j); }catch(e){}
		if (typeof window.populateLeadSelects === 'function'){
			window.populateLeadSelects({
				sources: Array.isArray(j.sources) ? j.sources : [],
				sales: Array.isArray(j.sales) ? j.sales : [],
				lookings: Array.isArray(j.lookings) ? j.lookings : []
			});
		}
		return j;
	}).catch(function(){ return null; });
}

// jQuery helper to open edit modal and prefill values using AJAX
function editLead(id){
	if (!id) return;
	var bundleUrl = (window.__leadApiUrls && window.__leadApiUrls.bundle) ? window.__leadApiUrls.bundle : 'get_lead_form_payload.php';
	fetchJson(bundleUrl + '?id=' + encodeURIComponent(id) + '&per_page=1000').then(function(resp){
		if (!resp || resp.success === false){ alert(resp && resp.message? resp.message : 'Failed to load lead'); return; }
		var d = resp.lead || {};
		// Set both snapshot and prefill for modal compatibility (must be set before populating selects)
		window._pendingLeadPrefillSnapshot = JSON.parse(JSON.stringify(d));
		window.pendingLeadPrefill = d;
		try{ if (typeof window.storeLeadBundlePayload === 'function') window.storeLeadBundlePayload(resp); }catch(e){}
		// Populate selects from bundled lookups
		if (typeof window.populateLeadSelects === 'function'){
			window.populateLeadSelects({
				sources: Array.isArray(resp.sources) ? resp.sources : [],
				sales: Array.isArray(resp.sales) ? resp.sales : [],
				lookings: Array.isArray(resp.lookings) ? resp.lookings : []
			});
		}
		// Set both snapshot and prefill for modal compatibility
		window._pendingLeadPrefillSnapshot = JSON.parse(JSON.stringify(d));
		window.pendingLeadPrefill = d;

		// simple inputs
		$('#leadId').val(d.id || '');
		$('#leadName').val(d.name || '');
		$('#leadContact').val(d.contact_number || '');
		$('#leadEmail').val(d.email || '');
		$('#leadProfile').val(d.profile || '').trigger('change');
		$('#leadPincode').val(d.pincode || '');
		$('#leadCity').val(d.city || '');
		$('#leadState').val(d.state || '');
		$('#leadCountry').val(d.country || '');
		$('#leadPurpose').val(d.purpose || '');
		$('#leadNotes').val(d.notes || '');

		// selects: use .val(...).trigger('change') so Select2 or other listeners update
		try{ $('#leadSourceId').val(String(d.lead_source_id || '')).trigger('change'); }catch(e){} // numeric id
		try{ $('#leadLookingForId').val(String(d.looking_for_id || '')).trigger('change'); }catch(e){}
		// Type options are loaded asynchronously based on Looking For. Store desired value and let modal logic apply it when options arrive.
		try{ $('#leadLookingForTypeId').val(''); }catch(e){}

		// subtypes: stored as CSV; populate hidden. DO NOT call updateSubtypeHidden() now —
		// the subtype select will be populated later and will read the hidden value to preselect options.
		try{ var subs = []; if (d.looking_for_subtypes) subs = String(d.looking_for_subtypes).split(',').filter(Boolean); $('#leadLookingForSubtypeIds').val(subs.join(',')); }catch(e){} 

		// sales person: options are strings (name). Ensure option exists then set
		try{
			var spVal = d.sales_person || '';
			var spSel = $('#leadSalesPerson');
			if (spVal && spSel.find('option[value="'+spVal+'"]').length === 0){ spSel.append($('<option>').val(spVal).text(spVal)); }
			spSel.val(spVal).trigger('change');
		}catch(e){} 

		// status
		try{ var st = (d.lead_status||'').toString().toLowerCase(); if (st==='h') st='hot'; if (st==='c') st='cold'; if (st==='w') st='warm'; $('#leadStatus').val(st).trigger('change'); }catch(e){}

		// finally show modal
		try{ var modalEl = document.getElementById('leadModal'); var m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl); m.show(); }catch(e){} 
	}).catch(function(){ alert('Failed to load lead'); });}
// delegate clicks on .btn-edit-lead
$(document).on('click', '.btn-edit-lead', function(e){ e.preventDefault(); var id = $(this).data('lead-id') || $(this).attr('data-lead-id'); editLead(id); });

// initialize select2 for better UX (safe to ignore if not available)
// Use dropdownParent inside the Bootstrap modal so the dropdown isn't hidden behind the modal/backdrop.
$(function(){
	try{
		var $modal = $('#leadModal');
		$('#leadSalesPerson').select2({ width:'100%', placeholder: '-- Select sales person --', allowClear:true, dropdownParent: $modal });
			$('#leadSourceId').select2({ width:'100%', placeholder: '-- Select source --', allowClear:true, dropdownParent: $modal });
			$('#leadLookingForId').select2({ width:'100%', placeholder: '-- Select --', allowClear:true, dropdownParent: $modal });
			$('#leadLookingForTypeId').select2({ width:'100%', placeholder: '-- Select type --', allowClear:true, dropdownParent: $modal });
	}catch(e){}
});
	const leadModalEl = document.getElementById('leadModal');
	let leadModal = null;
	try{ if (leadModalEl) leadModal = new bootstrap.Modal(leadModalEl); }catch(e){}
	// wrap hide removed (debug-only)

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
		// Delete handlers only (edit is handled by jQuery delegate above)
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
		// Clear any pending prefill so modal fetches lookups for a fresh add
		try{ delete window.pendingLeadPrefill; delete window._pendingLeadPrefillSnapshot; }catch(e){}
		// Preload lookups so the modal is ready immediately (modal itself also has a fallback).
		loadLeadFormLookups().finally(function(){ leadModal.show(); });
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

	function populateSalesFilterFromSales(sales){
		var sel = document.getElementById('leadSalesFilter');
		if (!sel) return;
		sel.innerHTML = '';
		var opt = document.createElement('option');
		opt.value = '';
		opt.text = 'All Sales';
		sel.appendChild(opt);
		(sales || []).forEach(function(s){
			var o = document.createElement('option');
			o.value = s.name || s.employee_id || s.id;
			o.text = s.name || ('#'+s.employee_id);
			sel.appendChild(o);
		});
	}

	// populate sales person filter using the bundled lookups (single request)
	(function(){
		loadLeadFormLookups().then(function(j){
			if (j && Array.isArray(j.sales)) populateSalesFilterFromSales(j.sales);
		});
	})();

	// header per-page removed; footer-only control is used

	// initial load
	document.addEventListener('DOMContentLoaded', ()=>{ initLeadHandlers(); loadLeads(1); });
</script>

</body>
</html>
