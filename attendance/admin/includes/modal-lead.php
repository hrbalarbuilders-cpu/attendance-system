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
                <span class="input-add-btn text-primary" id="addLookingForBtn" title="Add">➕</span>
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
// Initialize modal behaviors and small helpers (reusable)
(function(){
  document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'addLookingForBtn'){
      var v = prompt('Add new Looking For label'); if (!v) return;
      var sel = document.getElementById('leadLookingForId'); var opt = document.createElement('option');
      opt.value = 'new:' + Date.now(); opt.text = v; opt.selected = true; sel.appendChild(opt);
    }
  });

  var leadModalEl = document.getElementById('leadModal');
  if (leadModalEl){
      leadModalEl.addEventListener('shown.bs.modal', function(){
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
      // fetch active lead sources so dropdown stays up-to-date
      
      	  // fetch active lead sources only if select not already populated
      	  try{
        <?php
        // Build an admin-root relative path to the leads endpoint so the modal works
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $adminPos = strpos($script, '/admin');
        $adminBase = $adminPos !== false ? substr($script, 0, $adminPos + 6) : '';
        $getSourcesUrl = $adminBase . '/leads/get_sources.php';
          $getSalesUrl = $adminBase . '/leads/get_sales_persons.php';
          $getLookingsUrl = $adminBase . '/leads/get_looking_fors.php';
        ?>
        (function(){
          var srcEl = document.getElementById('leadSourceId');
          // if only default option present (or element missing), fetch; otherwise assume already populated and preserve selection
          var needFetch = !srcEl || !srcEl.options || srcEl.options.length <= 1;
          if (needFetch){
            fetch('<?php echo $getSourcesUrl; ?>').then(r=>r.json()).then(j=>{ if (j && Array.isArray(j.sources)) populateLeadSelects({ sources: j.sources }); }).catch(()=>{});
          }
            // always fetch latest sales persons so UI reflects recent additions
            fetch('<?php echo $getSalesUrl; ?>').then(r=>r.json()).then(j=>{ if (j && Array.isArray(j.sales)) populateLeadSelects({ sales: j.sales }); }).catch(()=>{});
            // fetch looking-for list
            fetch('<?php echo $getLookingsUrl; ?>').then(r=>r.json()).then(j=>{ if (j && Array.isArray(j.lookings)) populateLeadSelects({ lookings: j.lookings }); }).catch(()=>{});
        })();
      }catch(e){}
      var c = document.querySelector('.main-content-scroll'); if (c) c.classList.remove('lead-modal-blur');
      try{
        if (leadModalEl.dataset._prevScrollY !== undefined){ var prevY = parseInt(leadModalEl.dataset._prevScrollY,10) || 0; document.body.style.position = leadModalEl.dataset._prevBodyPosition || ''; document.body.style.top = leadModalEl.dataset._prevBodyTop || ''; document.body.style.left = leadModalEl.dataset._prevBodyLeft || ''; document.body.style.right = leadModalEl.dataset._prevBodyRight || ''; document.body.style.width = leadModalEl.dataset._prevBodyWidth || ''; window.scrollTo(0, prevY); delete leadModalEl.dataset._prevScrollY; delete leadModalEl.dataset._prevBodyPosition; delete leadModalEl.dataset._prevBodyTop; delete leadModalEl.dataset._prevBodyLeft; delete leadModalEl.dataset._prevBodyRight; delete leadModalEl.dataset._prevBodyWidth; }
        if (leadModalEl.dataset._prevBodyPr !== undefined){ document.body.style.paddingRight = leadModalEl.dataset._prevBodyPr || ''; delete leadModalEl.dataset._prevBodyPr; }
        var scs = document.querySelectorAll('.main-content-scroll'); scs.forEach(function(el){ if (el.dataset._prevOverflow !== undefined){ el.style.overflow = el.dataset._prevOverflow || ''; delete el.dataset._prevOverflow; } });
      }catch(e){}
    });
  }

  window.populateLeadSelects = function(data){
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
    }
    if (data && data.lookings){
      var l = document.getElementById('leadLookingForId');
      if (l){
        l.innerHTML = '<option value="">-- Select --</option>';
        data.lookings.forEach(function(r){ var o = document.createElement('option'); o.value = String(r.id); o.text = r.name; l.appendChild(o); });
        l.dispatchEvent(new Event('change'));
      }
    }
    if (data && data.sales){
      var sp = document.getElementById('leadSalesPerson');
      if (sp){
        sp.innerHTML = '<option value="">-- Select sales person --</option>';
        data.sales.forEach(function(r){ var o = document.createElement('option'); o.value = (r.name||'').toString(); o.text = (r.name||'').toString(); sp.appendChild(o); });
        sp.dispatchEvent(new Event('change'));
      }
    }
  };

  // fetch types for a given looking_for id and populate the Type select
  function fetchTypesForLooking(lookingId){
    var tEl = document.getElementById('leadLookingForTypeId');
    var subEl = document.getElementById('leadLookingForSubtypeIds');
    if (!tEl) return;
    tEl.innerHTML = '<option value="">-- Select type --</option>';
    if (subEl) subEl.innerHTML = '';
    if (!lookingId) return;
    fetch('get_looking_for_types.php?looking_for_id='+encodeURIComponent(lookingId)).then(r=>r.json()).then(j=>{
      if (!j || !Array.isArray(j.data)) return;
      j.data.forEach(function(t){ var o = document.createElement('option'); o.value = String(t.id); o.text = t.name; tEl.appendChild(o); });
      // if a saved type value exists in the select element's value, apply it before change
      try{ var saved = document.getElementById('leadLookingForTypeId') && document.getElementById('leadLookingForTypeId').value; if (saved) { tEl.value = saved; } }catch(e){}
      tEl.dispatchEvent(new Event('change'));
    }).catch(()=>{});
  }

  // fetch subtypes for a given type id and populate multiselect
  function fetchSubtypesForType(typeId){
    var menu = document.getElementById('leadLookingForSubtypeMenu');
    var hidden = document.getElementById('leadLookingForSubtypeIds');
    var btn = document.getElementById('leadLookingForSubtypeBtn');
    if (!menu || !hidden || !btn) return;
    menu.innerHTML = '';
    if (!typeId) { hidden.value = ''; btn.innerText = 'Select subtypes'; return; }
    fetch('get_type_subtypes.php?type_id='+encodeURIComponent(typeId)).then(r=>r.json()).then(j=>{
      if (!j || !Array.isArray(j.data)) return;
      // build list of checkboxes
      j.data.forEach(function(s){
        var id = String(s.id);
        var label = document.createElement('label');
        label.className = 'd-block mb-1';
        var cb = document.createElement('input');
        cb.type = 'checkbox'; cb.value = id; cb.className = 'form-check-input me-2 subtype-checkbox';
        // preserve previous selections if any
        var prev = (hidden.value||'').split(',').filter(Boolean);
        if (prev.indexOf(id) !== -1) cb.checked = true;
        cb.addEventListener('change', function(){ updateSubtypeHidden(); });
        label.appendChild(cb);
        var span = document.createElement('span'); span.innerText = s.name || '';
        label.appendChild(span);
        menu.appendChild(label);
      });
      // update label to reflect selected
      updateSubtypeHidden();
    }).catch(()=>{});
  }

  function updateSubtypeHidden(){
    var menu = document.getElementById('leadLookingForSubtypeMenu');
    var hidden = document.getElementById('leadLookingForSubtypeIds');
    var btn = document.getElementById('leadLookingForSubtypeBtn');
    if (!menu || !hidden || !btn) return;
    var checked = Array.from(menu.querySelectorAll('input.subtype-checkbox:checked')).map(function(cb){ return cb.value; });
    hidden.value = checked.join(',');
    if (checked.length === 0){ btn.innerText = 'Select subtypes'; }
    else if (checked.length === 1){ btn.innerText = menu.querySelector('input.subtype-checkbox:checked').nextSibling.textContent.trim(); }
    else { btn.innerText = checked.length + ' selected'; }
  }

  // wire change handlers
  document.addEventListener('DOMContentLoaded', function(){
    var lookingEl = document.getElementById('leadLookingForId');
    var typeEl = document.getElementById('leadLookingForTypeId');
    if (lookingEl){ lookingEl.addEventListener('change', function(){ fetchTypesForLooking(this.value || ''); }); }
    if (typeEl){ typeEl.addEventListener('change', function(){ fetchSubtypesForType(this.value || ''); }); }
  });

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
