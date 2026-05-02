'use strict';

/* ─── State ─────────────────────────────── */
let currentStep = 1;
const tags = [];
const uploadedFiles = [];

const subcategories = {
  climatique:    ['Sécheresse', 'Grêle', 'Gel / gelée tardive', 'Inondation / crue', 'Tempête / vent violent', 'Autre aléa climatique'],
  foncier:       ['Empiétement / accaparement', 'Contestation de bornage', 'Problème de titre foncier', 'Litige successoral', 'Occupation illicite'],
  subvention:    ['Aide FDA non reçue', 'Crédit Tayssir bloqué', 'Subvention semences/engrais', 'Prime Assurance agricole', 'Autre aide gouvernementale'],
  eau:           ['Coupure d\'irrigation', 'Tarification abusive', 'Dégradation de canal', 'Accès puits / forage refusé', 'Mauvaise qualité de l\'eau'],
  intrants:      ['Semences non conformes', 'Engrais contrefait', 'Pesticide défectueux', 'Prix excessif intrants', 'Rupture approvisionnement'],
  equipement:    ['Matériel agricole défectueux', 'Refus de mécanisation', 'Stockage / entrepôt frigorifique', 'Semoir / tracteur en panne'],
  ravageur:      ['Invasion acridienne', 'Maladie cryptogamique', 'Ravageurs insectes', 'Maladie virale cultures', 'Nuisibles élevage'],
  marche:        ['Effondrement des prix', 'Intermédiaires abusifs', 'Accès marché refusé', 'Non-respect de contrat', 'Concurrence déloyale'],
  infrastructure:['Piste agricole dégradée', 'Manque d\'électricité rurale', 'Absence de souk rural', 'Problème réseau GSM', 'Infrastructure d\'irrigation'],
  autre:         ['Signalement environnemental', 'Conflit de voisinage', 'Problème administratif', 'Autre (préciser dans description)'],
};

/* ─── Navigation ─────────────────────────── */
function goToStep(n) {
  if (n > currentStep && !validateStep(currentStep)) return;

  document.getElementById(`step-${currentStep}`).classList.remove('active');
  const ind = document.getElementById(`ind-${currentStep}`);
  ind.classList.remove('active');
  ind.classList.add('done');

  const lines = document.querySelectorAll('.step-line');
  if (currentStep - 1 < lines.length) lines[currentStep - 1].classList.add('done');

  if (n < currentStep) {
    for (let i = n; i <= currentStep; i++) {
      const el = document.getElementById(`ind-${i}`);
      el.classList.remove('done', 'active');
    }
    document.getElementById(`ind-${n}`).classList.add('active');
    for (let i = n - 1; i < lines.length; i++) lines[i].classList.remove('done');
  }

  currentStep = n;
  document.getElementById(`step-${n}`).classList.add('active');
  document.getElementById(`ind-${n}`).classList.remove('done');
  document.getElementById(`ind-${n}`).classList.add('active');

  if (n === 4) buildRecap();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ─── Validation ─────────────────────────── */
function validateStep(step) {
  const fields = document.querySelectorAll(`#step-${step} [required]`);
  let valid = true;
  fields.forEach(f => {
    clearError(f);
    if (!f.value.trim()) {
      showError(f, 'Ce champ est obligatoire');
      valid = false;
    }
    if (f.type === 'email' && f.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value)) {
      showError(f, 'Adresse e-mail invalide');
      valid = false;
    }
  });

  if (step === 2) {
    const cat = document.getElementById('categorie');
    if (!cat.value) {
      document.getElementById('category-grid').style.outline = '2px solid var(--red-alert)';
      document.getElementById('category-grid').style.borderRadius = '8px';
      valid = false;
    }
  }
  return valid;
}

function showError(el, msg) {
  el.classList.add('error');
  const existing = el.parentElement.querySelector('.error-msg');
  if (!existing) {
    const span = document.createElement('span');
    span.className = 'error-msg';
    span.textContent = msg;
    el.parentElement.appendChild(span);
  }
}

function clearError(el) {
  el.classList.remove('error');
  const e = el.parentElement.querySelector('.error-msg');
  if (e) e.remove();
}

/* ─── Category selection ──────────────────── */
function selectCategory(card) {
  document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  const cat = card.dataset.cat;
  document.getElementById('categorie').value = cat;
  document.getElementById('category-grid').style.outline = '';

  const group = document.getElementById('subcategory-group');
  const sel = document.getElementById('sous_categorie');
  sel.innerHTML = '<option value="">-- Préciser --</option>';
  (subcategories[cat] || []).forEach(s => {
    const opt = document.createElement('option');
    opt.value = s;
    opt.textContent = s;
    sel.appendChild(opt);
  });
  group.style.display = 'block';
}

/* ─── Tag input ───────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('culture-input');
  if (!input) return;

  input.addEventListener('keydown', e => {
    if ((e.key === 'Enter' || e.key === ',') && input.value.trim()) {
      e.preventDefault();
      addTag(input.value.trim().replace(/,+$/, ''));
      input.value = '';
    }
    if (e.key === 'Backspace' && !input.value && tags.length) {
      removeTag(tags.length - 1);
    }
  });

  document.getElementById('tag-input-wrapper')?.addEventListener('click', () => input.focus());

  const desc = document.getElementById('description');
  if (desc) {
    desc.addEventListener('input', () => {
      document.getElementById('char-count').textContent = desc.value.length;
    });
  }

  document.querySelectorAll('input[name="demarches_anterieures"]').forEach(r => {
    r.addEventListener('change', () => {
      document.getElementById('demarches-detail').style.display =
        r.value === 'oui' && r.checked ? 'flex' : 'none';
    });
  });

  setupUpload();

  const form = document.getElementById('complaint-form');
  if (form) form.addEventListener('submit', handleSubmit);
});

function addTag(text) {
  if (!text || tags.includes(text)) return;
  tags.push(text);
  renderTags();
  document.getElementById('cultures_touchees').value = tags.join(',');
}

function removeTag(idx) {
  tags.splice(idx, 1);
  renderTags();
  document.getElementById('cultures_touchees').value = tags.join(',');
}

function renderTags() {
  const container = document.getElementById('tag-container');
  container.innerHTML = '';
  tags.forEach((t, i) => {
    const span = document.createElement('span');
    span.className = 'tag';
    span.innerHTML = `${t}<span class="tag-remove" onclick="removeTag(${i})">×</span>`;
    container.appendChild(span);
  });
}

/* ─── File upload ─────────────────────────── */
function setupUpload() {
  const zone = document.getElementById('upload-zone');
  const input = document.getElementById('file-input');
  if (!zone || !input) return;

  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    handleFiles(e.dataTransfer.files);
  });
  input.addEventListener('change', () => handleFiles(input.files));
}

function handleFiles(fileList) {
  const maxFiles = 5;
  const maxSize = 10 * 1024 * 1024;
  Array.from(fileList).forEach(file => {
    if (uploadedFiles.length >= maxFiles) return;
    if (file.size > maxSize) {
      alert(`${file.name} dépasse 10 Mo.`);
      return;
    }
    uploadedFiles.push(file);
  });
  renderFileList();
}

function removeFile(idx) {
  uploadedFiles.splice(idx, 1);
  renderFileList();
}

function renderFileList() {
  const list = document.getElementById('file-list');
  if (!list) return;
  if (!uploadedFiles.length) { list.style.display = 'none'; return; }
  list.style.display = 'flex';
  list.innerHTML = uploadedFiles.map((f, i) => `
    <div class="file-item">
      <span class="file-icon">${fileIcon(f.name)}</span>
      <span class="file-name">${f.name}</span>
      <span class="file-size">${formatSize(f.size)}</span>
      <span class="file-remove" onclick="removeFile(${i})">✕</span>
    </div>`).join('');
}

function fileIcon(name) {
  const ext = name.split('.').pop().toLowerCase();
  const map = { pdf: '📄', jpg: '🖼️', jpeg: '🖼️', png: '🖼️', mp4: '🎥' };
  return map[ext] || '📎';
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
  return (bytes / 1048576).toFixed(1) + ' Mo';
}

/* ─── Recap builder ───────────────────────── */
function buildRecap() {
  const g = id => (document.getElementById(id) || {}).value || '—';
  const radio = name => {
    const el = document.querySelector(`input[name="${name}"]:checked`);
    return el ? el.value : '—';
  };
  const catCard = document.querySelector('.cat-card.selected');
  const catLabel = catCard ? catCard.querySelector('strong').textContent : '—';
  const urgenceEl = document.getElementById('urgence');
  const urgenceLabel = urgenceEl && urgenceEl.selectedIndex > 0
    ? urgenceEl.options[urgenceEl.selectedIndex].text : '—';

  const recap = document.getElementById('recap-content');
  recap.innerHTML = `
    <div class="recap-section">
      <h4>👤 Agriculteur</h4>
      <div class="recap-grid">
        <div class="recap-item"><div class="rlabel">Nom complet</div><div class="rvalue">${g('prenom')} ${g('nom')}</div></div>
        <div class="recap-item"><div class="rlabel">CIN / Carte</div><div class="rvalue">${g('cin')}</div></div>
        <div class="recap-item"><div class="rlabel">Téléphone</div><div class="rvalue">${g('tel')}</div></div>
        <div class="recap-item"><div class="rlabel">E-mail</div><div class="rvalue">${g('email')}</div></div>
      </div>
    </div>
    <div class="recap-section">
      <h4>📍 Exploitation</h4>
      <div class="recap-grid">
        <div class="recap-item"><div class="rlabel">Région</div><div class="rvalue">${g('region')}</div></div>
        <div class="recap-item"><div class="rlabel">Province</div><div class="rvalue">${g('province')}</div></div>
        <div class="recap-item"><div class="rlabel">Commune</div><div class="rvalue">${g('commune')}</div></div>
        <div class="recap-item"><div class="rlabel">Superficie</div><div class="rvalue">${g('superficie') !== '—' ? g('superficie') + ' ha' : '—'}</div></div>
      </div>
    </div>
    <div class="recap-section">
      <h4>📝 Réclamation</h4>
      <div class="recap-grid">
        <div class="recap-item"><div class="rlabel">Catégorie</div><div class="rvalue">${catLabel}</div></div>
        <div class="recap-item"><div class="rlabel">Urgence</div><div class="rvalue">${urgenceLabel}</div></div>
        <div class="recap-item"><div class="rlabel">Date incident</div><div class="rvalue">${g('date_incident')}</div></div>
        <div class="recap-item"><div class="rlabel">Surface touchée</div><div class="rvalue">${g('surface_touchee') !== '—' ? g('surface_touchee') + ' ha' : '—'}</div></div>
        <div class="recap-item"><div class="rlabel">Perte estimée</div><div class="rvalue">${g('perte_estimee') !== '—' ? Number(g('perte_estimee')).toLocaleString('fr-MA') + ' MAD' : '—'}</div></div>
        <div class="recap-item"><div class="rlabel">Cultures</div><div class="rvalue">${tags.length ? tags.join(', ') : '—'}</div></div>
      </div>
      <div style="margin-top:.75rem;">
        <div class="rlabel" style="font-size:.78rem;color:var(--text-muted);">Description</div>
        <div style="font-size:.85rem;color:var(--text-primary);margin-top:.25rem;line-height:1.5;">${g('description')}</div>
      </div>
    </div>
    <div class="recap-section" style="margin-bottom:0;">
      <h4>📎 Documents</h4>
      <div class="recap-item"><div class="rvalue">${uploadedFiles.length ? uploadedFiles.map(f => f.name).join(', ') : 'Aucun document joint'}</div></div>
    </div>`;
}

/* ─── Form submission ─────────────────────── */
function handleSubmit(e) {
  e.preventDefault();
  if (!document.getElementById('consent').checked) {
    alert('Veuillez accepter les conditions avant de soumettre.');
    return;
  }
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span>⏳ Envoi en cours…</span>';

  setTimeout(() => {
    const ref = 'AGRI-2025-' + Math.floor(10000 + Math.random() * 90000);
    document.getElementById('ref-number').textContent = ref;
    document.getElementById('complaint-form').style.display = 'none';
    document.querySelector('.progress-bar-wrapper').style.display = 'none';
    document.getElementById('success-state').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, 1800);
}

/* ─── Utilities ───────────────────────────── */
function copyRef() {
  const ref = document.getElementById('ref-number').textContent;
  navigator.clipboard.writeText(ref).then(() => alert('Référence copiée : ' + ref));
}

function resetForm() {
  location.reload();
}
