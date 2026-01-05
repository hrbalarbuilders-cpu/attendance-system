<?php
include_once __DIR__ . '/../config/db.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Looking For</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> body{ background:#f3f5fb; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;} .page-wrap{max-width:1200px;margin:0 auto;padding-top:72px;} .section-title{font-size:1.6rem;font-weight:700;} </style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="page-wrap">
  <?php include_once __DIR__ . '/../includes/navbar-sales.php'; ?>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="section-title mb-0">Looking For</h4>
      <button id="btnAddLF" class="btn btn-dark">+ Add Item</button>
    </div>

    <div class="card">
      <div class="card-header d-flex gap-2 align-items-center">
        <input id="lfSearch" class="form-control form-control-sm" placeholder="Search..." style="max-width:360px;">
      </div>
      <div class="card-body p-0">
        <div id="lfList">
          <?php include __DIR__ . '/leads_looking_for_list_fragment.php'; ?>
        </div>
      </div>
    </div>
  </div>

  <?php include_once __DIR__ . '/../includes/modal-looking-for.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let currentLFPerPage = 10;
  let currentLFPage = 1;
  const lfModalEl = document.getElementById('lookingForModal');
  const lfModal = lfModalEl ? new bootstrap.Modal(lfModalEl) : null;

  function loadLF(page=1){
    currentLFPage = page||1;
    var q = document.getElementById('lfSearch') ? document.getElementById('lfSearch').value.trim() : '';
    var url = 'leads_looking_for_list_fragment.php?page='+encodeURIComponent(currentLFPage)+'&per_page='+encodeURIComponent(currentLFPerPage);
    if (q) url += '&q='+encodeURIComponent(q);
    fetch(url).then(r=>r.text()).then(html=>{ document.getElementById('lfList').innerHTML = html.replace(/<!--LF_TOTAL:\d+-->/,''); initLFHandlers(); attachLFPagination(); var foot = document.getElementById('lfPerPageFooter'); if (foot){ foot.value = currentLFPerPage; foot.onchange = function(){ currentLFPerPage = parseInt(this.value,10)||10; loadLF(1); }; } });
  }

  function initLFHandlers(){
    document.querySelectorAll('.btn-edit-lf').forEach(btn=> btn.addEventListener('click', function(){
      const tr=this.closest('tr'); const id = tr ? tr.dataset.id : null; if (!id) return;
      fetch('get_looking_for.php?id='+encodeURIComponent(id)).then(r=>r.json()).then(j=>{
        if (!j.success){ alert(j.message||'Failed'); return; }
        const d=j.data||{};
        document.getElementById('lookingForId').value = d.id ?? '';
        document.getElementById('lookingForName').value = d.name ?? '';
        document.getElementById('lookingForStatus').value = d.status ?? 'active';
        // project type removed
        // populate type groups
        var typesContainer = document.getElementById('lfTypes'); typesContainer.innerHTML = '';
        var typesArr = Array.isArray(d.types) ? d.types : [];
        if (typesArr.length === 0) addTypeGroup('','');
        typesArr.forEach(function(t){ addTypeGroup(t.name, Array.isArray(t.subtypes) ? t.subtypes : []); });
        if (lfModal) lfModal.show();
      });
    }));

    document.querySelectorAll('.btn-delete-lf').forEach(btn=> btn.addEventListener('click', function(){ const tr=this.closest('tr'); const id = tr ? tr.dataset.id : null; if (!id) return; if (!confirm('Delete?')) return; fetch('delete_looking_for.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+encodeURIComponent(id)}).then(r=>r.json()).then(j=>{ alert(j.message || (j.success ? 'Deleted' : 'Error')); if (j.success) loadLF(currentLFPage); }); }));
  }

  function attachLFPagination(){ document.querySelectorAll('.lf-page-link').forEach(a=> a.addEventListener('click', function(e){ e.preventDefault(); var p = parseInt(this.dataset.page,10)||1; loadLF(p); })); }

  document.getElementById('btnAddLF').addEventListener('click', function(){ document.getElementById('lookingForForm').reset(); document.getElementById('lookingForId').value=''; document.getElementById('lfTypes').innerHTML=''; addTypeGroup('',''); if (lfModal) lfModal.show(); });

  // Type group helpers
  function addTypeGroup(name, subtypes){
    var container = document.getElementById('lfTypes');
    var group = document.createElement('div'); group.className = 'lf-type-group border rounded p-3 mb-3';
    // header with type name and remove
    var header = document.createElement('div'); header.className = 'd-flex gap-2 mb-2 align-items-center';
    var nameInp = document.createElement('input'); nameInp.type='text'; nameInp.name='type_name[]'; nameInp.placeholder='Type name (e.g. Villa)'; nameInp.value = name || ''; nameInp.className='form-control';
    var remBtn = document.createElement('button'); remBtn.type='button'; remBtn.className='btn btn-sm btn-outline-danger'; remBtn.innerText='Remove Type'; remBtn.addEventListener('click', function(){ group.remove(); });
    header.appendChild(nameInp); header.appendChild(remBtn);
    group.appendChild(header);
    // subtypes container
    var subCont = document.createElement('div'); subCont.className = 'lf-subtypes row g-2';
    if (Array.isArray(subtypes) && subtypes.length){ subtypes.forEach(function(s){ addSubtypeRowTo(subCont, s); }); }
    else { addSubtypeRowTo(subCont, ''); }
    group.appendChild(subCont);
    // add subtype button
    var addSub = document.createElement('div'); addSub.className='mt-2'; var addBtn = document.createElement('button'); addBtn.type='button'; addBtn.className='btn btn-sm btn-outline-secondary'; addBtn.innerText='Add Sub Type'; addBtn.addEventListener('click', function(){ addSubtypeRowTo(subCont, ''); }); addSub.appendChild(addBtn); group.appendChild(addSub);
    container.appendChild(group);
  }
  function addSubtypeRowTo(container, value){ var wrapper = document.createElement('div'); wrapper.className='col-md-6'; var card = document.createElement('div'); card.className='card p-2'; var row = document.createElement('div'); row.className='d-flex gap-2 align-items-center'; var inp = document.createElement('input'); inp.type='text'; inp.className='form-control form-control-sm'; inp.name='subtype[]'; inp.value = value || ''; var del = document.createElement('button'); del.type='button'; del.className='btn btn-sm btn-outline-danger'; del.innerText='Remove'; del.addEventListener('click', function(){ wrapper.remove(); }); row.appendChild(inp); row.appendChild(del); card.appendChild(row); wrapper.appendChild(card); container.appendChild(wrapper); }

  document.getElementById('lookingForForm').addEventListener('submit', function(e){ e.preventDefault(); var id = document.getElementById('lookingForId').value; var url = id ? 'update_looking_for.php' : 'create_looking_for.php';
    // build structured types array
    var types = [];
    document.querySelectorAll('.lf-type-group').forEach(function(g){ var tnameEl = g.querySelector('input[name="type_name[]"]'); var tname = tnameEl ? tnameEl.value.trim() : ''; if (!tname) return; var subs=[]; g.querySelectorAll('.lf-subtypes input').forEach(function(si){ if (si.value && si.value.trim()) subs.push(si.value.trim()); }); types.push({name: tname, subtypes: subs}); });
    document.getElementById('typesJson').value = JSON.stringify(types);
    var fd = new FormData(this);
    fetch(url, {method:'POST', body:fd}).then(r=>r.json()).then(j=>{ alert(j.message || (j.success ? 'Saved' : 'Error')); if (j.success){ if (lfModal) lfModal.hide(); loadLF(1); } }); });

  document.addEventListener('DOMContentLoaded', function(){ initLFHandlers(); attachLFPagination(); var foot = document.getElementById('lfPerPageFooter'); if (foot){ foot.value = currentLFPerPage; foot.onchange = function(){ currentLFPerPage = parseInt(this.value,10)||10; loadLF(1); }; } loadLF(1); });

  // wire add type group button in modal
  document.addEventListener('DOMContentLoaded', function(){ var addTypeBtn = document.getElementById('btnAddLfType'); if (addTypeBtn){ addTypeBtn.addEventListener('click', function(){ addTypeGroup('',''); }); } });
</script>
</body>
</html>
