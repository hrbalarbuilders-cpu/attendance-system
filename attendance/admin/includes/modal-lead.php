<?php
// Reusable Lead Modal Include
?>
<style>
/* Backdrop blur for modern browsers */
.modal-backdrop.show {
  background-color: rgba(255,255,255,0.06) !important;
  backdrop-filter: blur(40px) saturate(1.08);
  -webkit-backdrop-filter: blur(40px) saturate(1.08);
  position: fixed !important;
  inset: 0 !important;
  z-index: 3990 !important;
}
.modal {
  z-index: 4000 !important;
  display: none !important;
  position: fixed !important;
  inset: 0 !important;
}
.modal.show { display: block !important; }
.modal-content.lead-modal {
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.modal-dialog { margin-top: 8vh; }
.lead-modal .form-label { font-weight: 600; }
.input-add-btn { cursor:pointer; font-size:1.05rem; }
.lead-modal .modal-body { overflow: auto; -webkit-overflow-scrolling: touch; flex: 1 1 auto; }

/* Ensure Select2 dropdown appears above Bootstrap modal/backdrop */
.select2-container--open {
  z-index: 5005 !important;
}
</style>

<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
    <form id="leadForm" class="modal-content lead-modal p-0">
      <div class="modal-header border-0">
        <h5 class="modal-title">Add / Edit Lead</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="leadId">
        <div class="container-fluid">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label">Name</label>
              <input name="name" id="leadName" class="form-control" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Contact Number</label>
              <input name="contact_number" id="leadContact" class="form-control">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input name="email" id="leadEmail" type="email" class="form-control">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label d-flex justify-content-between align-items-center">Lead Source
                <small class="text-muted">(select)</small>
              </label>
              <select name="lead_source_id" id="leadSourceId" class="form-select">
                <option value="">-- Select source --</option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label d-flex justify-content-between align-items-center">Looking For
              </label>
              <div class="input-group">
                <select name="looking_for_id" id="leadLookingForId" class="form-select">
                  <option value="">-- Select --</option>
                </select>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Looking For Type</label>
              <select name="looking_for_type_id" id="leadLookingForTypeId" class="form-select">
                <option value="">-- Select type --</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Looking For Subtypes</label>
              <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="leadLookingForSubtypeBtn" data-bs-toggle="dropdown" aria-expanded="false">Select subtypes</button>
                <div class="dropdown-menu p-2" id="leadLookingForSubtypeMenu" style="max-height:220px; overflow:auto; min-width:250px;">
                  <!-- checkboxes inserted here -->
                </div>
                <input type="hidden" name="looking_for_subtype_ids" id="leadLookingForSubtypeIds" value="">
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Sales Person</label>
              <select name="sales_person" id="leadSalesPerson" class="form-select">
                <option value="">-- Select sales person --</option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Profile</label>
              <select name="profile" id="leadProfile" class="form-select">
                <option value="">-- Select profile --</option>
                <option value="business">Business</option>
                <option value="salaried">Salaried</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Purpose</label>
              <input name="purpose" id="leadPurpose" class="form-control">
            </div>

            <div class="col-12 col-md-3">
              <label class="form-label">Pincode</label>
              <input name="pincode" id="leadPincode" class="form-control">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">City</label>
              <input name="city" id="leadCity" class="form-control">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">State</label>
              <input name="state" id="leadState" class="form-control">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Country</label>
              <input name="country" id="leadCountry" class="form-control">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Status</label>
              <select name="lead_status" id="leadStatus" class="form-select">
                <option value="">-- Select status --</option>
                <option value="hot">Hot</option>
                <option value="cold">Cold</option>
                <option value="warm">Warm</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" id="leadNotes" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Lead</button>
      </div>
    </form>
  </div>
</div>

<script>
<?php
// Build an admin-root relative path to the leads endpoint so the modal works
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$adminPos = strpos($script, '/admin');
$adminBase = $adminPos !== false ? substr($script, 0, $adminPos + 6) : '';
$adminBase = rtrim($adminBase, '/');
// Ensure URLs are absolute from the web root; SCRIPT_NAME can be missing a leading slash on some setups.
if ($adminBase !== '' && $adminBase[0] !== '/') {
  $adminBase = '/' . $adminBase;
}
$getBundleUrl = $adminBase . '/leads/get_lead_form_payload.php';
?>
// Initialize modal behaviors and small helpers (reusable)
(function(){
  // Expose resolved endpoint URLs for debugging + consistent usage.
  window.__leadApiUrls = window.__leadApiUrls || {
    bundle: '<?php echo $getBundleUrl; ?>'
  };

  // Fallback: if PHP-derived URLs are missing the app subfolder, derive base from browser location.
  (function ensureLeadApiUrls(){
    function computeAdminBaseFromLocation(){
      try{
        var p = (window.location && window.location.pathname) ? String(window.location.pathname) : '';
        var idx = p.indexOf('/admin/');
        if (idx === -1) return '';
        return p.slice(0, idx + 6); // include '/admin'
      }catch(e){ return ''; }
    }
    function resolveLeadUrl(file){
      var adminBase = computeAdminBaseFromLocation();
      if (adminBase) return adminBase + '/leads/' + file;
      return '../leads/' + file;
    }
    function looksSuspicious(url){
      url = (url === undefined || url === null) ? '' : String(url);
      // If it doesn't contain '/admin/' it's very likely missing the app base path.
      return url.indexOf('/admin/') === -1;
    }
    try{
      var u = window.__leadApiUrls || {};
      if (looksSuspicious(u.bundle)) u.bundle = resolveLeadUrl('get_lead_form_payload.php');
      window.__leadApiUrls = u;
    }catch(e){}
  })();

  // In-memory cache for bundled responses (reduces duplicate network calls).
  window.__leadBundleCache = window.__leadBundleCache || {
    lastLoadedAt: 0,
    typesByLooking: {},
    subtypesByType: {}
  };

  window.storeLeadBundlePayload = function(payload){
    try{
      if (!payload) return;
      var cache = window.__leadBundleCache || (window.__leadBundleCache = { lastLoadedAt: 0, typesByLooking: {}, subtypesByType: {} });
      cache.lastLoadedAt = Date.now();

      // If the payload is for edit mode, cache dependent lists for that lead.
      if (payload.lead){
        var lfId = payload.lead.looking_for_id ? String(payload.lead.looking_for_id) : '';
        var typeId = payload.lead.looking_for_type_id ? String(payload.lead.looking_for_type_id) : '';
        if (lfId && Array.isArray(payload.types)) cache.typesByLooking[lfId] = payload.types;
        if (typeId && Array.isArray(payload.subtypes)) cache.subtypesByType[typeId] = payload.subtypes;
      }

      // If the payload is for dependent lookup requests, cache by explicit ids.
      if (payload.types_for_looking_for_id && Array.isArray(payload.types)){
        cache.typesByLooking[String(payload.types_for_looking_for_id)] = payload.types;
      }
      if (payload.subtypes_for_type_id && Array.isArray(payload.subtypes)){
        cache.subtypesByType[String(payload.subtypes_for_type_id)] = payload.subtypes;
      }
    }catch(e){}
  };

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
      .then(async function(r){
        var text = await r.text();
        var json;
        try{
          json = JSON.parse(text);
        }catch(e){
          showLeadApiError('Invalid server response');
          throw e;
        }
        if (json && json.success === false){
          showLeadApiError(json.message || 'Request failed');
        }
        return json;
      });
  }
  // Removed click handler for addLookingForBtn

  var leadModalEl = document.getElementById('leadModal');
    if (leadModalEl){
        leadModalEl.addEventListener('shown.bs.modal', function(){
      try{ if (typeof window.wireLeadDependentSelects === 'function') window.wireLeadDependentSelects(); }catch(e){}
      var c = document.querySelector('.main-content-scroll'); if (c) c.classList.add('lead-modal-blur');
      try{
        var scrollY = window.scrollY || window.pageYOffset || 0; leadModalEl.dataset._prevScrollY = scrollY;
        leadModalEl.dataset._prevBodyPosition = document.body.style.position || '';
        leadModalEl.dataset._prevBodyTop = document.body.style.top || '';
        leadModalEl.dataset._prevBodyLeft = document.body.style.left || '';
        leadModalEl.dataset._prevBodyRight = document.body.style.right || '';
        leadModalEl.dataset._prevBodyWidth = document.body.style.width || '';
        document.body.style.position = 'fixed'; document.body.style.top = '-' + scrollY + 'px';
        document.body.style.left = '0'; document.body.style.right = '0'; document.body.style.width = '100%';
        var sbw = window.innerWidth - document.documentElement.clientWidth; if (sbw > 0){ leadModalEl.dataset._prevBodyPr = document.body.style.paddingRight || ''; var prevPr = parseFloat(getComputedStyle(document.body).paddingRight) || 0; document.body.style.paddingRight = (prevPr + sbw) + 'px'; }
        var scs = document.querySelectorAll('.main-content-scroll'); scs.forEach(function(el){ el.dataset._prevOverflow = el.style.overflow || ''; el.style.overflow = 'hidden'; });
      }catch(e){}
      var nm = document.getElementById('leadName'); if (nm) nm.focus();
      // Fetch dropdown lookups (prefer single bundled call).
      try{
        (function(){
          var srcEl = document.getElementById('leadSourceId');
          var lfEl = document.getElementById('leadLookingForId');
          var spEl = document.getElementById('leadSalesPerson');

          var needFetch = (!srcEl || !srcEl.options || srcEl.options.length <= 1) ||
                          (!lfEl || !lfEl.options || lfEl.options.length <= 1) ||
                          (!spEl || !spEl.options || spEl.options.length <= 1);

          // Avoid refetching constantly if we just loaded lookups recently.
          var cache = window.__leadBundleCache || { lastLoadedAt: 0 };
          var recent = cache.lastLoadedAt && (Date.now() - cache.lastLoadedAt) < 60000;
          if (!needFetch && recent) return;

          var bundleUrl = (window.__leadApiUrls && window.__leadApiUrls.bundle) ? window.__leadApiUrls.bundle : '';
          if (!bundleUrl) return;
          fetchJson(bundleUrl + '?per_page=1000')
            .then(function(j){
              if (!j || j.success === false) return;
              try{ if (typeof window.storeLeadBundlePayload === 'function') window.storeLeadBundlePayload(j); }catch(e){}
              if (Array.isArray(j.sources)) populateLeadSelects({ sources: j.sources });
              if (Array.isArray(j.sales)) populateLeadSelects({ sales: j.sales });
              if (Array.isArray(j.lookings)) populateLeadSelects({ lookings: j.lookings });
            })
            .catch(function(){});
        })();
      }catch(e){}
      var c2 = document.querySelector('.main-content-scroll'); if (c2) c2.classList.remove('lead-modal-blur');
      try{
        if (leadModalEl.dataset._prevScrollY !== undefined){ var prevY = parseInt(leadModalEl.dataset._prevScrollY,10) || 0; document.body.style.position = leadModalEl.dataset._prevBodyPosition || ''; document.body.style.top = leadModalEl.dataset._prevBodyTop || ''; document.body.style.left = leadModalEl.dataset._prevBodyLeft || ''; document.body.style.right = leadModalEl.dataset._prevBodyRight || ''; document.body.style.width = leadModalEl.dataset._prevBodyWidth || ''; window.scrollTo(0, prevY); delete leadModalEl.dataset._prevScrollY; delete leadModalEl.dataset._prevBodyPosition; delete leadModalEl.dataset._prevBodyTop; delete leadModalEl.dataset._prevBodyLeft; delete leadModalEl.dataset._prevBodyRight; delete leadModalEl.dataset._prevBodyWidth; }
        if (leadModalEl.dataset._prevBodyPr !== undefined){ document.body.style.paddingRight = leadModalEl.dataset._prevBodyPr || ''; delete leadModalEl.dataset._prevBodyPr; }
        var scs = document.querySelectorAll('.main-content-scroll'); scs.forEach(function(el){ if (el.dataset._prevOverflow !== undefined){ el.style.overflow = el.dataset._prevOverflow || ''; delete el.dataset._prevOverflow; } });
      }catch(e){}
      try{
        // Defensive fallback: after a short delay, re-apply snapshot prefill values
        setTimeout(function(){
          try{
            var pref = window._pendingLeadPrefillSnapshot || window.pendingLeadPrefill;
            if (!pref) return;
            // apply type if present
            if (pref.looking_for_type_id){
              var tEl = document.getElementById('leadLookingForTypeId');
              if (tEl){ var want = String(pref.looking_for_type_id); var opt = tEl.querySelector('option[value="'+want+'"]'); if (opt){ tEl.value = want; tEl.dispatchEvent(new Event('change')); } }
            }
            // apply subtypes if present
            if (pref.looking_for_subtypes){ var hidden = document.getElementById('leadLookingForSubtypeIds'); if (hidden) hidden.value = String(pref.looking_for_subtypes); try{ if (typeof updateSubtypeHidden === 'function') updateSubtypeHidden(); }catch(e){} }
            // apply sales person if present
            if (pref.sales_person){ var sp = document.getElementById('leadSalesPerson'); if (sp){ var pv = String(pref.sales_person); var opt2 = sp.querySelector('option[value="'+pv+'"]'); if (opt2){ sp.value = pv; sp.dispatchEvent(new Event('change')); } } }
          }catch(e){}
        }, 220);
      }catch(e){}
    });

    // Clear any pending prefill only when the modal is fully closed.
    leadModalEl.addEventListener('hidden.bs.modal', function(){
      try{ delete window.pendingLeadPrefill; delete window._pendingLeadPrefillSnapshot; }catch(e){}
    });
  }

  // hide/hidden debug tracing removed

  window.populateLeadSelects = function(data){
    var pref = window.pendingLeadPrefill || window._pendingLeadPrefillSnapshot || null;
    if (data && data.sources){
      var s = document.getElementById('leadSourceId');
      if (s){
        s.innerHTML = '<option value="">-- Select source --</option>';
        data.sources.forEach(function(r){
          var o = document.createElement('option');
          o.value = String(r.id);
          var name = (r.name || '').toString();
          var status = (r.status || '').toString().toLowerCase();
          if (status === '0' || status === 'inactive' || status === 'false' || status === 'off'){
            o.text = name + ' (inactive)';
            o.disabled = true;
          } else {
            o.text = name;
          }
          s.appendChild(o);
        });
        s.dispatchEvent(new Event('change'));
      }
      // If a lead prefill object exists on window, apply its values now that selects are populated
      if (pref){
        try{
          var d = pref;
          // helper to set select by value or text
          function setSelectById(id, val){
            var el = document.getElementById(id); if (!el) return false; var v = val === undefined || val === null ? '' : String(val);
            var opt = el.querySelector('option[value="'+v+'"]'); if (opt){ el.value = v; el.dispatchEvent(new Event('change')); return true; }
            var match = Array.from(el.options).find(function(o){ return (o.text||'').toString().trim().toLowerCase() === (v||'').toString().trim().toLowerCase(); });
            if (match){ el.value = match.value; el.dispatchEvent(new Event('change')); return true; }
            return false;
          }
          // set Looking For and trigger type fetch
          if (d.looking_for_id !== undefined){ setSelectById('leadLookingForId', d.looking_for_id); }
          // try set type after a short wait (types are fetched on change handler)
          (function waitType(attempts){ attempts = attempts||0; var tEl = document.getElementById('leadLookingForTypeId'); if (tEl && tEl.querySelector('option[value="'+String(d.looking_for_type_id || '')+'"]')){ setSelectById('leadLookingForTypeId', d.looking_for_type_id); var hiddenSub = document.getElementById('leadLookingForSubtypeIds'); if (hiddenSub) hiddenSub.value = d.looking_for_subtypes || ''; try{ if (typeof updateSubtypeHidden === 'function') updateSubtypeHidden(); }catch(e){} return; } if (attempts < 20) setTimeout(function(){ waitType(attempts+1); }, 120); })(0);
            // set Sales Person when options ready
            (function waitSales(attempts){ attempts = attempts||0; var sp = document.getElementById('leadSalesPerson'); if (sp && sp.options && sp.options.length>0){ setSelectById('leadSalesPerson', d.sales_person); return; } if (attempts < 20) setTimeout(function(){ waitSales(attempts+1); }, 100); })(0);
          // set source if provided
          if (d.lead_source_id !== undefined){ setSelectById('leadSourceId', d.lead_source_id || d.lead_source_name || ''); }
          // set other fields directly
          var map = ['leadId','leadName','leadContact','leadEmail','leadProfile','leadPincode','leadCity','leadState','leadCountry','leadPurpose','leadNotes'];
          map.forEach(function(id){ var el = document.getElementById(id); if (!el) return; var key = id.replace(/^lead/,'').charAt(0).toLowerCase() + id.replace(/^lead/,'').slice(1); if (d[key]!==undefined) try{ el.value = d[key]; }catch(e){} });
        }catch(e){}
        // do NOT clear pendingLeadPrefill here — other populate calls (lookings/sales) may arrive later and
        // should also apply pending values. It will be cleared later by the modal consumer.
      }
    }
    if (data && data.lookings){
      var l = document.getElementById('leadLookingForId');
      if (l){
        // Preserve current selection to avoid async refresh clearing user choice.
        var existingVal = '';
        try{ existingVal = String(l.value || ''); }catch(e){}
        l.innerHTML = '<option value="">-- Select --</option>';
        data.lookings.forEach(function(r){ var o = document.createElement('option'); o.value = String(r.id); o.text = r.name; l.appendChild(o); });
        // Prefer pending prefill; otherwise preserve existing selected value if still available.
        var chosen = '';
        if (pref && pref.looking_for_id !== undefined && pref.looking_for_id !== null && String(pref.looking_for_id) !== ''){
          chosen = String(pref.looking_for_id);
        } else if (existingVal){
          chosen = existingVal;
        }
        if (chosen){
          var opt = l.querySelector('option[value="'+chosen+'"]');
          if (opt){ l.value = chosen; }
        }
        // Only dispatch change if we actually have a selection; avoid firing with empty value.
        if (l.value){ l.dispatchEvent(new Event('change')); }
      }
    }
    if (data && data.sales){
      var sp = document.getElementById('leadSalesPerson');
      if (sp){
        // Preserve current selection to avoid async refresh clearing user choice.
        var existingSp = '';
        try{ existingSp = String(sp.value || ''); }catch(e){}
        sp.innerHTML = '<option value="">-- Select sales person --</option>';
        data.sales.forEach(function(r){ var o = document.createElement('option'); o.value = (r.name||'').toString(); o.text = (r.name||'').toString(); sp.appendChild(o); });
        // Prefer pending prefill; otherwise preserve existing selected value if still available.
        var chosenSp = '';
        if (pref && pref.sales_person){
          chosenSp = String(pref.sales_person || '');
        } else if (existingSp){
          chosenSp = existingSp;
        }
        if (chosenSp){
          var opt = sp.querySelector('option[value="'+chosenSp+'"]');
          if (opt){ sp.value = chosenSp; }
        }
        sp.dispatchEvent(new Event('change'));
      }
    }
  };

  // lightweight guards to prevent duplicate concurrent lookup fetches
  window._leadLookup = window._leadLookup || { fetchingTypesFor: null, fetchingSubtypesFor: null, lastTypesFor: null, lastTypesAt: 0, lastSubtypesFor: null, lastSubtypesAt: 0 };

  // fetch types for a given looking_for id and populate the Type select
  function fetchTypesForLooking(lookingId){
    var tEl = document.getElementById('leadLookingForTypeId');
    if (!tEl) return;
    // Preserve current type selection (editLead may set it before options are loaded).
    var existingTypeVal = '';
    try{ existingTypeVal = String(tEl.value || ''); }catch(e){}
    // If we have types cached from a bundled payload, use them.
    try{
      var cache = window.__leadBundleCache;
      var lidCache = String(lookingId || '');
      if (cache && cache.typesByLooking && lidCache && Array.isArray(cache.typesByLooking[lidCache])){
        tEl.innerHTML = '<option value="">-- Select type --</option>';
        cache.typesByLooking[lidCache].forEach(function(t){
          var o = document.createElement('option');
          o.value = String(t.id);
          o.text = t.name;
          tEl.appendChild(o);
        });
        try{
          var _pref = window.pendingLeadPrefill || window._pendingLeadPrefillSnapshot || null;
          var want = (_pref && _pref.looking_for_type_id) ? String(_pref.looking_for_type_id || '') : '';
          if (!want && existingTypeVal) want = existingTypeVal;
          if (want){
            var opt = tEl.querySelector('option[value="'+want+'"]');
            if (opt){ tEl.value = want; }
          }
        }catch(e){}
        tEl.dispatchEvent(new Event('change'));
        return;
      }
    }catch(e){}

    // avoid duplicate concurrent fetches for the same lookingId
    try{
      var key = String(lookingId || '');
      if (window._leadLookup.fetchingTypesFor === key) { return; }
      window._leadLookup.fetchingTypesFor = key;
    }catch(e){}
    tEl.innerHTML = '<option value="">-- Select type --</option>';
    if (!lookingId) { try{ window._leadLookup.fetchingTypesFor = null; }catch(e){}; return; }
    // Only numeric looking_for_id is valid for the types endpoint.
    var lid = String(lookingId);
    if (!/^\d+$/.test(lid)) { try{ window._leadLookup.fetchingTypesFor = null; }catch(e){}; return; }

    // Prefer the bundle endpoint for dependent lookups.
    var bundleUrl = (window.__leadApiUrls && window.__leadApiUrls.bundle) ? window.__leadApiUrls.bundle : '';
    if (bundleUrl){
      fetchJson(bundleUrl + '?looking_for_id=' + encodeURIComponent(lid) + '&per_page=1000').then(function(j){
        if (!j || j.success === false) return;
        try{ if (typeof window.storeLeadBundlePayload === 'function') window.storeLeadBundlePayload(j); }catch(e){}

        var arr = Array.isArray(j.types) ? j.types : [];
        arr.forEach(function(t){ var o = document.createElement('option'); o.value = String(t.id); o.text = t.name; tEl.appendChild(o); });

        // Prefer pending prefill desired type; otherwise preserve existing selection if still available.
        try{
          var _pref = window.pendingLeadPrefill || window._pendingLeadPrefillSnapshot || null;
          var want = (_pref && _pref.looking_for_type_id) ? String(_pref.looking_for_type_id || '') : '';
          if (!want && existingTypeVal) want = existingTypeVal;
          if (want){
            var opt = tEl.querySelector('option[value="'+want+'"]');
            if (opt){ tEl.value = want; }
          }
        }catch(e){}
        tEl.dispatchEvent(new Event('change'));
      }).catch(function(){}).finally(function(){ try{ window._leadLookup.fetchingTypesFor = null; window._leadLookup.lastTypesFor = String(lookingId||''); window._leadLookup.lastTypesAt = Date.now(); }catch(e){} });
      return;
    }

    // Bundle-only mode: no legacy fallback.
    try{ window._leadLookup.fetchingTypesFor = null; }catch(e){}
  }

  // fetch subtypes for a given type id and populate multiselect
  function fetchSubtypesForType(typeId){
    var menu = document.getElementById('leadLookingForSubtypeMenu');
    var hidden = document.getElementById('leadLookingForSubtypeIds');
    var btn = document.getElementById('leadLookingForSubtypeBtn');
    if (!menu || !hidden || !btn) return;
    // capture previous hidden value or pending prefill BEFORE we clear the menu
    var prevStr = (hidden && hidden.value) ? String(hidden.value) : ((window.pendingLeadPrefill && window.pendingLeadPrefill.looking_for_subtypes) ? String(window.pendingLeadPrefill.looking_for_subtypes) : ((window._pendingLeadPrefillSnapshot && window._pendingLeadPrefillSnapshot.looking_for_subtypes) ? String(window._pendingLeadPrefillSnapshot.looking_for_subtypes) : ''));
    var prevArr = prevStr.split(',').filter(Boolean);
    menu.innerHTML = '';
    if (!typeId) { hidden.value = ''; btn.innerText = 'Select subtypes'; return; }
    var tid = String(typeId);
    if (!/^\d+$/.test(tid)) { return; }

    // If we have subtypes cached from a bundled payload, use them.
    try{
      var cache = window.__leadBundleCache;
      if (cache && cache.subtypesByType && Array.isArray(cache.subtypesByType[tid])){
        cache.subtypesByType[tid].forEach(function(s){
          var id = String(s.id);
          var label = document.createElement('label');
          label.className = 'd-block mb-1';
          var cb = document.createElement('input');
          cb.type = 'checkbox'; cb.value = id; cb.className = 'form-check-input me-2 subtype-checkbox';
          if (prevArr.indexOf(id) !== -1) cb.checked = true;
          cb.addEventListener('change', function(){ updateSubtypeHidden(); });
          label.appendChild(cb);
          var span = document.createElement('span'); span.innerText = s.name || '';
          label.appendChild(span);
          menu.appendChild(label);
        });
        updateSubtypeHidden();
        return;
      }
    }catch(e){}

    try{
      var key = String(typeId||'');
      if (window._leadLookup.fetchingSubtypesFor === key){ return; }
      window._leadLookup.fetchingSubtypesFor = key;
    }catch(e){}

    // Prefer the bundle endpoint for dependent lookups.
    var bundleUrl = (window.__leadApiUrls && window.__leadApiUrls.bundle) ? window.__leadApiUrls.bundle : '';
    if (bundleUrl){
      fetchJson(bundleUrl + '?type_id=' + encodeURIComponent(tid) + '&per_page=1000').then(function(j){
        if (!j || j.success === false) return;
        try{ if (typeof window.storeLeadBundlePayload === 'function') window.storeLeadBundlePayload(j); }catch(e){}
        var arr = Array.isArray(j.subtypes) ? j.subtypes : [];
        arr.forEach(function(s){
          var id = String(s.id);
          var label = document.createElement('label');
          label.className = 'd-block mb-1';
          var cb = document.createElement('input');
          cb.type = 'checkbox'; cb.value = id; cb.className = 'form-check-input me-2 subtype-checkbox';
          if (prevArr.indexOf(id) !== -1) cb.checked = true;
          cb.addEventListener('change', function(){ updateSubtypeHidden(); });
          label.appendChild(cb);
          var span = document.createElement('span'); span.innerText = s.name || '';
          label.appendChild(span);
          menu.appendChild(label);
        });
        updateSubtypeHidden();
      }).catch(function(){}).finally(function(){ try{ window._leadLookup.fetchingSubtypesFor = null; window._leadLookup.lastSubtypesFor = String(typeId||''); window._leadLookup.lastSubtypesAt = Date.now(); }catch(e){} });
      return;
    }

    // Bundle-only mode: no legacy fallback.
    try{ window._leadLookup.fetchingSubtypesFor = null; }catch(e){}
  }

  function updateSubtypeHidden(){
    var menu = document.getElementById('leadLookingForSubtypeMenu');
    var hidden = document.getElementById('leadLookingForSubtypeIds');
    var btn = document.getElementById('leadLookingForSubtypeBtn');
    if (!menu || !hidden || !btn) return;
    var prevHidden = (hidden && hidden.value) ? String(hidden.value) : ((window.pendingLeadPrefill && window.pendingLeadPrefill.looking_for_subtypes) ? String(window.pendingLeadPrefill.looking_for_subtypes) : ((window._pendingLeadPrefillSnapshot && window._pendingLeadPrefillSnapshot.looking_for_subtypes) ? String(window._pendingLeadPrefillSnapshot.looking_for_subtypes) : ''));
    var checked = Array.from(menu.querySelectorAll('input.subtype-checkbox:checked')).map(function(cb){ return cb.value; });
    // deduplicate
    var uniq = Array.from(new Set(checked));
    // If the computed selection is empty but we have a previous value (from hidden or pending snapshot),
    // avoid overwriting it with an empty string — this prevents async races from clearing selections.
    if (uniq.length === 0 && prevHidden){
      // set button label based on previous value
      var prevArr = prevHidden.split(',').filter(Boolean);
      if (prevArr.length === 0){ btn.innerText = 'Select subtypes'; }
      else if (prevArr.length === 1){ btn.innerText = menu.querySelector('input.subtype-checkbox[value="'+prevArr[0]+'"]') ? menu.querySelector('input.subtype-checkbox[value="'+prevArr[0]+'"]').nextSibling.textContent.trim() : (prevArr[0] || '1 selected'); }
      else { btn.innerText = prevArr.length + ' selected'; }
      return;
    }
    hidden.value = uniq.join(',');
    if (uniq.length === 0){ btn.innerText = 'Select subtypes'; }
    else if (uniq.length === 1){ btn.innerText = menu.querySelector('input.subtype-checkbox:checked').nextSibling.textContent.trim(); }
    else { btn.innerText = uniq.length + ' selected'; }
  }

  // Wire dependent change handlers (Looking For -> Type -> Subtypes).
  // This must work even if DOMContentLoaded already fired (e.g. modal injected late).
  window.wireLeadDependentSelects = function(){
    var lookingEl = document.getElementById('leadLookingForId');
    var typeEl = document.getElementById('leadLookingForTypeId');
    if (lookingEl && lookingEl.dataset._wiredLeadLooking !== '1'){
      lookingEl.dataset._wiredLeadLooking = '1';
      var _lfDeb;
      lookingEl.addEventListener('change', function(){
        clearTimeout(_lfDeb);
        var val = this.value || '';
        // small debounce to prevent rapid duplicate fetches that clear subtypes
        _lfDeb = setTimeout(function(){ fetchTypesForLooking(val); }, 120);
      });
    }
    if (typeEl && typeEl.dataset._wiredLeadType !== '1'){
      typeEl.dataset._wiredLeadType = '1';
      typeEl.addEventListener('change', function(){
        var v = this.value || '';
        fetchSubtypesForType(v);
      });
    }
  };

  // Expose for other scripts (and Select2 hooks).
  window.leadFetchTypesForLooking = function(lookingId){ return fetchTypesForLooking(lookingId); };
  window.leadFetchSubtypesForType = function(typeId){ return fetchSubtypesForType(typeId); };

  // Select2 uses a separate DOM and won't fire native events in some interactions.
  // Hook into Select2 events when available.
  window.wireLeadSelect2Hooks = function(){
    try{
      if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
      var $ = window.jQuery;
      var $looking = $('#leadLookingForId');
      var $type = $('#leadLookingForTypeId');
      if ($looking.length && !$looking.data('_leadSelect2Wired')){
        $looking.data('_leadSelect2Wired', true);
        $looking.on('select2:select select2:clear', function(){
          var v = String($looking.val() || '');
          if (v) window.leadFetchTypesForLooking(v);
        });
      }
      if ($type.length && !$type.data('_leadSelect2Wired')){
        $type.data('_leadSelect2Wired', true);
        // When opening Type dropdown, ensure types are loaded for the current Looking For.
        $type.on('select2:opening', function(){
          try{
            var native = document.getElementById('leadLookingForTypeId');
            var need = !native || !native.options || native.options.length <= 1;
            if (need){
              var lf = String($('#leadLookingForId').val() || '');
              if (lf) window.leadFetchTypesForLooking(lf);
            }
          }catch(e){}
        });

        // When a type is selected/cleared via Select2, load/reset subtypes.
        $type.on('select2:select select2:clear', function(){
          var tv = String($type.val() || '');
          if (tv) window.leadFetchSubtypesForType(tv);
          else window.leadFetchSubtypesForType('');
        });
      }
    }catch(e){}
  };

  // Initial wire-up: run now if possible, else on DOMContentLoaded.
  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){ try{ window.wireLeadDependentSelects(); }catch(e){} });
  } else {
    try{ window.wireLeadDependentSelects(); }catch(e){}
  }

  // Try to wire Select2 hooks after jQuery/Select2 are loaded.
  (function waitSelect2Hooks(attempts){
    attempts = attempts || 0;
    try{ if (typeof window.wireLeadSelect2Hooks === 'function') window.wireLeadSelect2Hooks(); }catch(e){}
    if (attempts < 25 && !(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2)){
      setTimeout(function(){ waitSelect2Hooks(attempts + 1); }, 200);
    }
  })();

  window.resetLeadForm = function(){ var f = document.getElementById('leadForm'); if (f) f.reset(); var id = document.getElementById('leadId'); if (id) id.value = ''; };

})();
</script>

<script>
// Auto-fill city/state/country when a 6-digit Indian pincode is entered
(function(){
  const pincodeEl = document.getElementById('leadPincode');
  const cityEl = document.getElementById('leadCity');
  const stateEl = document.getElementById('leadState');
  const countryEl = document.getElementById('leadCountry');
  if (!pincodeEl) return;
  let debounceTimer = null;
  pincodeEl.addEventListener('input', function(){ clearTimeout(debounceTimer); const digits = this.value.replace(/\D/g,''); if (digits.length !== 6) return; debounceTimer = setTimeout(()=> lookupPincode(digits), 400); });
  pincodeEl.addEventListener('blur', function(){ const digits = this.value.replace(/\D/g,''); if (digits.length === 6) lookupPincode(digits); });
  function lookupPincode(pin){ if (!pin || pin.length !== 6) return; fetch('https://api.postalpincode.in/pincode/' + encodeURIComponent(pin)).then(r=>r.json()).then(j=>{ if (!Array.isArray(j) || j.length===0) return; const res = j[0]; if (!res || res.Status!=='Success' || !Array.isArray(res.PostOffice) || res.PostOffice.length===0) return; const po = res.PostOffice[0]||{}; if (cityEl) cityEl.value = po.District || po.Name || ''; if (stateEl) stateEl.value = po.State || ''; if (countryEl) countryEl.value = po.Country || 'India'; }).catch(()=>{}); }
})();
</script>
