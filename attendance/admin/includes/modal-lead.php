<?php
// Reusable Lead Modal Include
?>
<style>
/* Backdrop blur for modern browsers */
.modal-backdrop.show {
  /* stronger backdrop: ~80% opacity with heavier blur */
  /* visible but strongly blurred backdrop */
  background-color: rgba(255,255,255,0.06) !important;
  backdrop-filter: blur(40px) saturate(1.08);
  -webkit-backdrop-filter: blur(40px) saturate(1.08);
  position: fixed !important;
  inset: 0 !important;
  z-index: 3990 !important;
}
.modal {
  z-index: 4000 !important;
}
.modal-content.lead-modal {
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  display: flex;
  flex-direction: column;
  overflow: hidden; /* ensure internal scrolling happens inside .modal-body */
}
.modal-dialog {
  /* small top offset so fixed header doesn't visually cut rounded corners on some browsers */
  margin-top: 8vh;
}
.lead-modal .form-label { font-weight: 600; }
.input-add-btn { cursor:pointer; font-size:1.05rem; }
/* Fallback blur for browsers that don't support backdrop-filter */
.main-content-scroll.lead-modal-blur {
  /* fallback: blur main content strongly rather than hide it */
  -webkit-filter: blur(40px) brightness(.88) contrast(.96);
  filter: blur(40px) brightness(.88) contrast(.96);
  transition: filter .18s ease, opacity .18s ease;
  opacity: 1;
}

/* When modal open, hide page scrollbars and prevent layout shift */
html.lead-disable-scroll,
body.lead-disable-scroll {
  overflow: hidden !important;
  height: 100% !important;
}
</style>

<style>
/* Make modal body scrollable internally so scrollbar appears inside modal */
.lead-modal .modal-body {
  overflow: auto;
  -webkit-overflow-scrolling: touch;
  flex: 1 1 auto;
}
.lead-modal .modal-header,
.lead-modal .modal-footer {
  flex: 0 0 auto;
}
</style>
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
              <label class="form-label">Sales Person</label>
              <input name="sales_person" id="leadSalesPerson" class="form-control">
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
  // When Add Looking For clicked, prompt and append option
  document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'addLookingForBtn'){
      var v = prompt('Add new Looking For label');
      if (!v) return;
      var sel = document.getElementById('leadLookingForId');
      var opt = document.createElement('option');
      opt.value = 'new:' + Date.now(); // temporary client id; server must map real id
      opt.text = v;
      opt.selected = true;
      sel.appendChild(opt);
    }
  });

  // Make sure modal is focus-friendly on small screens (Bootstrap handles most)
  var leadModalEl = document.getElementById('leadModal');
  if (leadModalEl){
    leadModalEl.addEventListener('shown.bs.modal', function(){
      var c = document.querySelector('.main-content-scroll'); if (c) c.classList.add('lead-modal-blur');
      // lock scroll by fixing body position and preserving scroll position (works in Chrome)
      try{
        var scrollY = window.scrollY || window.pageYOffset || 0;
        leadModalEl.dataset._prevScrollY = scrollY;
        // save previous inline styles to restore later
        leadModalEl.dataset._prevBodyPosition = document.body.style.position || '';
        leadModalEl.dataset._prevBodyTop = document.body.style.top || '';
        leadModalEl.dataset._prevBodyLeft = document.body.style.left || '';
        leadModalEl.dataset._prevBodyRight = document.body.style.right || '';
        leadModalEl.dataset._prevBodyWidth = document.body.style.width || '';

        document.body.style.position = 'fixed';
        document.body.style.top = '-' + scrollY + 'px';
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';

        // compute scrollbar width and add compensation padding to avoid layout shift
        var sbw = window.innerWidth - document.documentElement.clientWidth;
        if (sbw > 0){
          leadModalEl.dataset._prevBodyPr = document.body.style.paddingRight || '';
          var prevPr = parseFloat(getComputedStyle(document.body).paddingRight) || 0;
          document.body.style.paddingRight = (prevPr + sbw) + 'px';
        }

        // hide overflow on any .main-content-scroll containers and save previous
        var scs = document.querySelectorAll('.main-content-scroll');
        scs.forEach(function(el){ el.dataset._prevOverflow = el.style.overflow || ''; el.style.overflow = 'hidden'; });
      }catch(e){}
      var nm = document.getElementById('leadName'); if (nm) nm.focus();
    });
    leadModalEl.addEventListener('hidden.bs.modal', function(){
      var c = document.querySelector('.main-content-scroll'); if (c) c.classList.remove('lead-modal-blur');
      try{
        // restore body position and scroll
        if (leadModalEl.dataset._prevScrollY !== undefined){
          var prevY = parseInt(leadModalEl.dataset._prevScrollY,10) || 0;
          document.body.style.position = leadModalEl.dataset._prevBodyPosition || '';
          document.body.style.top = leadModalEl.dataset._prevBodyTop || '';
          document.body.style.left = leadModalEl.dataset._prevBodyLeft || '';
          document.body.style.right = leadModalEl.dataset._prevBodyRight || '';
          document.body.style.width = leadModalEl.dataset._prevBodyWidth || '';
          window.scrollTo(0, prevY);
          delete leadModalEl.dataset._prevScrollY;
          delete leadModalEl.dataset._prevBodyPosition; delete leadModalEl.dataset._prevBodyTop; delete leadModalEl.dataset._prevBodyLeft; delete leadModalEl.dataset._prevBodyRight; delete leadModalEl.dataset._prevBodyWidth;
        }
        // restore padding if we added compensation
        if (leadModalEl.dataset._prevBodyPr !== undefined){ document.body.style.paddingRight = leadModalEl.dataset._prevBodyPr || ''; delete leadModalEl.dataset._prevBodyPr; }
        // restore any .main-content-scroll containers
        var scs = document.querySelectorAll('.main-content-scroll');
        scs.forEach(function(el){ if (el.dataset._prevOverflow !== undefined){ el.style.overflow = el.dataset._prevOverflow || ''; delete el.dataset._prevOverflow; } });
      }catch(e){}
    });
  }

  // Optional: function to populate selects (callable from page if desired)
  window.populateLeadSelects = function(data){
    // data: { sources: [{id,name}], lookings: [{id,name}] }
    if (data && data.sources){
      var s = document.getElementById('leadSourceId'); if (s){ s.innerHTML = '<option value="">-- Select source --</option>'; data.sources.forEach(function(r){ var o = document.createElement('option'); o.value = r.id; o.text = r.name; s.appendChild(o); }); }
    }
    if (data && data.lookings){
      var l = document.getElementById('leadLookingForId'); if (l){ l.innerHTML = '<option value="">-- Select --</option>'; data.lookings.forEach(function(r){ var o = document.createElement('option'); o.value = r.id; o.text = r.name; l.appendChild(o); }); }
    }
  };

  // Optional helper to reset the form (used by pages)
  window.resetLeadForm = function(){
    var f = document.getElementById('leadForm'); if (f) f.reset();
    var id = document.getElementById('leadId'); if (id) id.value = '';
  };

})();
</script>
