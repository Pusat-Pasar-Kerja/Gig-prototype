<?php
declare(strict_types=1);
?>
<!-- ADMIN VERIFICATION DECISION MODAL (MATCHING IMAGES 2, 3, 4 DESIGN) -->
<div id="decision-modal-backdrop" class="decision-modal-backdrop" onclick="handleModalCloseAttempt(event)">
  <div class="decision-modal" onclick="event.stopPropagation();">
    <div class="decision-modal-head">
      <div>
        <div class="decision-modal-title" id="modal-title-text">Ambil Keputusan Verifikasi</div>
        <div class="decision-modal-sub" id="modal-sub-text">Pilih keputusan untuk profil Gig Worker ini. Pastikan Anda telah memeriksa seluruh data profil Gig Worker dengan seksama.</div>
      </div>
      <button type="button" class="decision-close" onclick="handleModalCloseAttempt(event)" aria-label="Tutup">&times;</button>
    </div>

    <form method="post" id="decision-form" action="" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
      <input type="hidden" name="action" id="modal-form-action" value="worker_take_decision" />
      <input type="hidden" name="tab" id="modal-form-tab" value="" />
      <input type="hidden" name="id" id="modal-form-id" value="" />
      <input type="hidden" name="vacancy_id" id="modal-form-vacancy-id" value="" />
      <input type="hidden" name="username" id="modal-form-username" value="" />
      <input type="hidden" name="edit_id" id="modal-form-edit-id" value="" />
      <input type="hidden" name="decision" id="modal-form-decision" value="approve" />

      <div class="decision-modal-body">
        <!-- SECTION 1: KETIDAKPATUHAN -->
        <div class="decision-section" id="compliance-section">
          <div class="decision-sec-header" id="compliance-sec-header">
            <div class="decision-sec-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="6" x2="20" y2="6"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="4" y1="18" x2="20" y2="18"></line>
                <circle cx="9" cy="6" r="2" fill="currentColor"></circle>
                <circle cx="15" cy="12" r="2" fill="currentColor"></circle>
                <circle cx="11" cy="18" r="2" fill="currentColor"></circle>
              </svg>
            </div>
            <div>
              <div class="decision-sec-title">Ketidakpatuhan</div>
              <div class="decision-sec-sub" id="compliance-sec-sub">Aktifkan salah satu item di bawah ini apabila terdapat ketidakpatuhan pada profil Gig Worker ini</div>
            </div>
          </div>

          <div class="compliance-list">
            <div class="compliance-card" onclick="toggleComplianceSwitch(this)">
              <span class="compliance-label">Data tidak lengkap</span>
              <div class="switch-track">
                <div class="switch-thumb"></div>
              </div>
              <input type="checkbox" name="compliance_reasons[]" value="Data tidak lengkap" style="display:none;" onchange="onComplianceToggleChange()" />
            </div>

            <div class="compliance-card" onclick="toggleComplianceSwitch(this)">
              <span class="compliance-label">Tidak sesuai substansi</span>
              <div class="switch-track">
                <div class="switch-thumb"></div>
              </div>
              <input type="checkbox" name="compliance_reasons[]" value="Tidak sesuai substansi" style="display:none;" onchange="onComplianceToggleChange()" />
            </div>

            <div class="compliance-card" onclick="toggleComplianceSwitch(this)">
              <span class="compliance-label">Tidak sesuai dengan aturan</span>
              <div class="switch-track">
                <div class="switch-thumb"></div>
              </div>
              <input type="checkbox" name="compliance_reasons[]" value="Tidak sesuai dengan aturan" style="display:none;" onchange="onComplianceToggleChange()" />
            </div>

            <div class="compliance-card" onclick="toggleComplianceSwitch(this)">
              <span class="compliance-label">Tidak sesuai dengan aturan anti diskriminasi</span>
              <div class="switch-track">
                <div class="switch-thumb"></div>
              </div>
              <input type="checkbox" name="compliance_reasons[]" value="Tidak sesuai dengan aturan anti diskriminasi" style="display:none;" onchange="onComplianceToggleChange()" />
            </div>
          </div>
        </div>

        <!-- SECTION 2: KEPUTUSAN -->
        <div class="decision-section">
          <div class="decision-sec-header">
            <div class="decision-sec-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m14 13 5 5m-4-2 3 3m-7-7 2.5-2.5a2.121 2.121 0 0 1 3 3L14 13Zm-5 5 1.5 1.5M3 21l3-3m0 0L3.5 15.5l5.5-5.5 2.5 2.5L6 18Z"></path>
              </svg>
            </div>
            <div>
              <div class="decision-sec-title">Keputusan</div>
              <div class="decision-sec-sub" id="decision-sec-sub">Tentukan keputusan sebelum memverifikasi profil Gig Worker ini</div>
            </div>
          </div>

          <div class="decision-radio-list">
            <!-- OPTION 1: SETUJUI -->
            <div class="decision-radio-card" data-opt="approve" onclick="selectDecisionRadio('approve')">
              <div class="custom-radio"></div>
              <div class="decision-opt-icon approve">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="16 10 11 15 8 12"></polyline></svg>
              </div>
              <span class="decision-opt-label">Setujui</span>
              <input type="radio" name="decision_option" value="approve" style="display:none;" />
            </div>

            <!-- OPTION 2: TOLAK -->
            <div class="decision-radio-card" data-opt="reject" onclick="selectDecisionRadio('reject')">
              <div class="custom-radio"></div>
              <div class="decision-opt-icon reject">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
              </div>
              <span class="decision-opt-label">Tolak</span>
              <input type="radio" name="decision_option" value="reject" style="display:none;" />
            </div>

            <!-- OPTION 3: REVISI -->
            <div class="decision-radio-card" data-opt="revision" onclick="selectDecisionRadio('revision')">
              <div class="custom-radio"></div>
              <div class="decision-opt-icon revision">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
              </div>
              <span class="decision-opt-label">Revisi</span>
              <input type="radio" name="decision_option" value="revision" style="display:none;" />
            </div>
          </div>
        </div>

        <!-- DYNAMIC NOTES FIELD (FOR REVISION AND REJECTION) -->
        <div id="decision-note-box" class="decision-note-wrap" style="display:none;">
          <label class="decision-note-label">Catatan <span style="color:#ef4444;">*</span></label>
          <textarea class="decision-note-textarea" name="admin_note" id="modal-note-input" placeholder="Masukkan catatan..."></textarea>
        </div>

        <!-- DYNAMIC CALLOUT BANNERS -->
        <div id="callout-banner-approve" class="decision-callout approve" style="display:flex;">
          <span class="callout-pill approve">Persetujuan</span>
          <span class="callout-msg" id="approve-msg-text">Dengan menyetujui, akun Gig Worker akan aktif dan dapat langsung melamar serta menerima proyek gig.</span>
        </div>

        <div id="callout-banner-reject" class="decision-callout reject" style="display:none;">
          <span class="callout-pill reject">Peringatan</span>
          <span class="callout-msg" id="reject-msg-text">Tindakan ini akan menolak profil Gig Worker secara permanen. Gig Worker harus memperbarui data dan mengajukan ulang.</span>
        </div>
      </div>

      <div class="decision-modal-foot">
        <button type="button" class="btn-decision-cancel" onclick="handleModalCloseAttempt(event)">Batalkan</button>
        <button type="submit" id="decision-submit-btn" class="btn-decision-submit approve">Setujui Gig Worker</button>
      </div>
    </form>
  </div>
</div>

<!-- EXIT CONFIRMATION DIALOG -->
<div id="decision-exit-confirm-modal" class="exit-modal-backdrop" onclick="if(event.target===this){closeExitConfirmModal();}">
  <div class="exit-modal-card" onclick="event.stopPropagation();">
    <div class="exit-modal-icon">⚠️</div>
    <div class="exit-modal-title">Batalkan Keputusan Verifikasi?</div>
    <div class="exit-modal-desc">Apakah Anda yakin ingin keluar? Seluruh pilihan atau catatan yang telah Anda masukkan tidak akan disimpan.</div>
    <div class="exit-modal-actions">
      <button type="button" class="btn-decision-cancel" onclick="closeExitConfirmModal()">Batal</button>
      <button type="button" class="btn-exit-confirm" onclick="confirmCloseDecisionModal()">Ya, Keluar</button>
    </div>
  </div>
</div>

<script>
let currentEntityName = 'profil Gig Worker';
let currentEntityType = 'worker';
let isFormDirty = false;

function openAdminDecisionModal(config = {}) {
  const modalBackdrop = document.getElementById('decision-modal-backdrop');
  if (!modalBackdrop) return;

  const entityType = config.entityType || 'worker';
  const entityName = config.entityName || (entityType === 'worker' ? 'profil Gig Worker' : (entityType === 'employer' ? 'pemberi kerja' : 'lowongan'));
  const id = config.id || '';
  const action = config.action || (entityType === 'worker' ? 'worker_take_decision' : (entityType === 'employer' ? 'employer_decision' : 'vacancy_decision'));
  const tab = config.tab || '';

  currentEntityType = entityType;
  currentEntityName = entityName;
  isFormDirty = false;

  document.getElementById('modal-form-action').value = action;
  document.getElementById('modal-form-tab').value = tab;
  document.getElementById('modal-form-id').value = id;
  document.getElementById('modal-form-vacancy-id').value = config.vacancyId || id;
  document.getElementById('modal-form-username').value = config.username || '';
  document.getElementById('modal-form-edit-id').value = config.editId || '';

  document.getElementById('modal-title-text').innerText = 'Ambil Keputusan Verifikasi';
  document.getElementById('modal-sub-text').innerText = `Pilih keputusan untuk ${entityName} ini. Pastikan Anda telah memeriksa seluruh data ${entityName} dengan seksama.`;
  document.getElementById('compliance-sec-sub').innerText = `Aktifkan salah satu item di bawah ini apabila terdapat ketidakpatuhan pada ${entityName} ini`;
  document.getElementById('decision-sec-sub').innerText = `Tentukan keputusan sebelum memverifikasi ${entityName} ini`;

  // Reset checkboxes
  const checkBoxes = document.querySelectorAll('.compliance-card input[type="checkbox"]');
  checkBoxes.forEach(cb => {
    cb.checked = false;
    cb.closest('.compliance-card').classList.remove('is-active');
  });

  // Clear notes
  const noteInput = document.getElementById('modal-note-input');
  noteInput.value = '';
  noteInput.required = false;

  const compHeader = document.getElementById('compliance-sec-header');
  const bannerApprove = document.getElementById('callout-banner-approve');
  const bannerReject = document.getElementById('callout-banner-reject');
  const noteBox = document.getElementById('decision-note-box');
  const submitBtn = document.getElementById('decision-submit-btn');

  if (entityType === 'vacancy') {
    if (compHeader) compHeader.style.display = 'none';
    if (bannerApprove) bannerApprove.style.display = 'none';
    if (bannerReject) bannerReject.style.display = 'none';
    if (noteBox) noteBox.style.display = 'none';
    submitBtn.innerText = 'Konfirmasi';
    submitBtn.className = 'btn-decision-submit primary-cyan';

    // In Image 2, radios start unselected
    document.getElementById('modal-form-decision').value = '';
    document.querySelectorAll('.decision-radio-card').forEach(card => {
      card.classList.remove('is-selected');
      const rad = card.querySelector('input[type="radio"]');
      if (rad) rad.checked = false;
    });
  } else {
    if (compHeader) compHeader.style.display = 'flex';
    selectDecisionRadio('approve');
  }

  modalBackdrop.style.display = 'flex';
  requestAnimationFrame(() => {
    modalBackdrop.classList.add('show');
  });
}

function handleModalCloseAttempt(e) {
  if (e) e.stopPropagation();
  if (!isFormDirty) {
    confirmCloseDecisionModal();
    return;
  }
  openExitConfirmModal();
}

function openExitConfirmModal() {
  const exitModal = document.getElementById('decision-exit-confirm-modal');
  if (exitModal) {
    exitModal.style.display = 'flex';
  }
}

function closeExitConfirmModal() {
  const exitModal = document.getElementById('decision-exit-confirm-modal');
  if (exitModal) {
    exitModal.style.display = 'none';
  }
}

function confirmCloseDecisionModal() {
  closeExitConfirmModal();
  const modalBackdrop = document.getElementById('decision-modal-backdrop');
  if (!modalBackdrop) return;
  modalBackdrop.classList.remove('show');
  setTimeout(() => {
    modalBackdrop.style.display = 'none';
    isFormDirty = false;
  }, 250);
}

function toggleComplianceSwitch(cardEl) {
  isFormDirty = true;
  const cb = cardEl.querySelector('input[type="checkbox"]');
  if (!cb) return;
  cb.checked = !cb.checked;
  if (cb.checked) {
    cardEl.classList.add('is-active');
  } else {
    cardEl.classList.remove('is-active');
  }
  onComplianceToggleChange();
}

function onComplianceToggleChange() {
  const activeToggles = Array.from(document.querySelectorAll('.compliance-card input[type="checkbox"]:checked'));
  if (activeToggles.length > 0) {
    // Auto select revision radio option
    selectDecisionRadio('revision');

    // Auto-populate active reasons into notes field if empty or update
    const noteInput = document.getElementById('modal-note-input');
    const reasons = activeToggles.map(cb => cb.value).join(', ');
    if (noteInput && (noteInput.value.trim() === '' || noteInput.value.startsWith('Ketidakpatuhan:'))) {
      noteInput.value = 'Ketidakpatuhan: ' + reasons + '.';
    }
  }
}

function selectDecisionRadio(optValue) {
  isFormDirty = true;
  if (optValue === 'approve') {
    const checkBoxes = document.querySelectorAll('.compliance-card input[type="checkbox"]');
    checkBoxes.forEach(cb => {
      cb.checked = false;
      cb.closest('.compliance-card').classList.remove('is-active');
    });
  }

  const radioCards = document.querySelectorAll('.decision-radio-card');
  radioCards.forEach(card => {
    if (card.getAttribute('data-opt') === optValue) {
      card.classList.add('is-selected');
      const rad = card.querySelector('input[type="radio"]');
      if (rad) rad.checked = true;
    } else {
      card.classList.remove('is-selected');
      const rad = card.querySelector('input[type="radio"]');
      if (rad) rad.checked = false;
    }
  });

  updateDecisionState(optValue);
}

function updateDecisionState(optValue) {
  const submitBtn = document.getElementById('decision-submit-btn');
  const decisionHidden = document.getElementById('modal-form-decision');
  const noteBox = document.getElementById('decision-note-box');
  const noteInput = document.getElementById('modal-note-input');
  const bannerApprove = document.getElementById('callout-banner-approve');
  const bannerReject = document.getElementById('callout-banner-reject');
  const approveMsg = document.getElementById('approve-msg-text');
  const rejectMsg = document.getElementById('reject-msg-text');

  decisionHidden.value = optValue;

  let entityLabel = 'Gig Worker';
  if (currentEntityType === 'employer') {
    entityLabel = 'Pemberi Kerja';
  } else if (currentEntityType === 'vacancy') {
    entityLabel = 'Lowongan';
  }

  if (currentEntityType === 'vacancy') {
    submitBtn.innerText = 'Konfirmasi';
    submitBtn.className = 'btn-decision-submit primary-cyan';
    if (bannerApprove) bannerApprove.style.display = 'none';
    if (bannerReject) bannerReject.style.display = 'none';

    if (optValue === 'approve') {
      noteBox.style.display = 'none';
      noteInput.required = false;
    } else if (optValue === 'reject') {
      noteBox.style.display = 'flex';
      noteInput.required = true;
      noteInput.placeholder = 'Masukkan alasan penolakan lowongan...';
    } else if (optValue === 'revision') {
      noteBox.style.display = 'flex';
      noteInput.required = true;
      noteInput.placeholder = 'Masukkan catatan revisi untuk lowongan...';
    }
    return;
  }

  if (optValue === 'approve') {
    submitBtn.innerText = `Setujui ${entityLabel}`;
    submitBtn.className = 'btn-decision-submit approve';
    noteBox.style.display = 'none';
    noteInput.required = false;

    bannerApprove.style.display = 'flex';
    bannerReject.style.display = 'none';

    if (currentEntityType === 'worker') {
      approveMsg.innerText = 'Dengan menyetujui, akun Gig Worker akan aktif dan dapat langsung melamar serta menerima proyek gig.';
    } else if (currentEntityType === 'employer') {
      approveMsg.innerText = 'Dengan menyetujui, akun pemberi kerja akan terverifikasi dan dapat memasang lowongan proyek gig.';
    } else {
      approveMsg.innerText = 'Dengan menyetujui, lowongan proyek akan langsung dipublikasi dan dapat dilamar oleh pencari kerja.';
    }
  } else if (optValue === 'reject') {
    submitBtn.innerText = `Tolak ${entityLabel}`;
    submitBtn.className = 'btn-decision-submit reject';
    noteBox.style.display = 'flex';
    noteInput.required = true;
    noteInput.placeholder = `Masukkan alasan penolakan ${entityLabel.toLowerCase()}...`;

    bannerApprove.style.display = 'none';
    bannerReject.style.display = 'flex';

    if (currentEntityType === 'worker') {
      rejectMsg.innerText = 'Tindakan ini akan menolak profil Gig Worker secara permanen. Gig Worker harus memperbarui data dan mengajukan ulang.';
    } else if (currentEntityType === 'employer') {
      rejectMsg.innerText = 'Tindakan ini akan menolak pendaftaran pemberi kerja secara permanen. Perusahaan harus mengajukan ulang pendaftaran.';
    } else {
      rejectMsg.innerText = 'Tindakan ini akan menolak lowongan secara permanen. Pemberi kerja harus mengajukan ulang lowongan baru.';
    }
  } else if (optValue === 'revision') {
    submitBtn.innerText = 'Kirim Revisi';
    submitBtn.className = 'btn-decision-submit revision';
    noteBox.style.display = 'flex';
    noteInput.required = true;
    noteInput.placeholder = `Masukkan catatan revisi untuk ${entityLabel.toLowerCase()}...`;

    bannerApprove.style.display = 'none';
    bannerReject.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('decision-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      const decisionVal = document.getElementById('modal-form-decision').value;
      if (!decisionVal) {
        e.preventDefault();
        alert('Silakan pilih salah satu keputusan (Setujui, Tolak, atau Revisi) sebelum konfirmasi.');
        return false;
      }
      const noteInput = document.getElementById('modal-note-input');
      if ((decisionVal === 'reject' || decisionVal === 'revision') && noteInput && noteInput.required && !noteInput.value.trim()) {
        e.preventDefault();
        alert('Harap isi catatan untuk keputusan ' + (decisionVal === 'reject' ? 'Tolak' : 'Revisi') + '.');
        noteInput.focus();
        return false;
      }
    });
  }
});
</script>
