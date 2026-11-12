<?php
/**
 * FRONTEND POP-UP WIDGET: Progressive Profiling (Profile Completion Nudge)
 * Memanggil endpoint `get_nudge.php` dan `save_nudge.php` menggunakan Fetch API.
 */
declare(strict_types=1);
?>
<style>
  .pp-nudge-fab {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 998;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #09b8a7 0%, #0ea5e9 100%);
    color: #ffffff;
    border: none;
    border-radius: 999px;
    padding: 11px 18px;
    font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
    font-size: 0.85rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(14, 165, 233, 0.35);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .pp-nudge-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(14, 165, 233, 0.45);
  }
  .pp-nudge-fab-pct {
    background: rgba(255, 255, 255, 0.22);
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
  }

  .pp-nudge-overlay {
    position: fixed;
    right: 24px;
    bottom: 80px;
    z-index: 999;
    width: min(410px, calc(100vw - 32px));
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18);
    font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
    color: #0f172a;
    overflow: hidden;
    display: none;
    animation: ppSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .pp-nudge-overlay.is-open {
    display: block;
  }
  @keyframes ppSlideUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .pp-nudge-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: #ffffff;
    padding: 16px 18px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  .pp-nudge-title {
    font-size: 0.92rem;
    font-weight: 800;
    line-height: 1.3;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .pp-nudge-sub {
    font-size: 0.75rem;
    color: #cbd5e1;
    margin-top: 3px;
  }
  .pp-nudge-close {
    background: rgba(255, 255, 255, 0.14);
    border: none;
    color: #ffffff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .pp-nudge-close:hover {
    background: rgba(255, 255, 255, 0.26);
  }

  .pp-progress-wrap {
    padding: 12px 18px 0;
  }
  .pp-progress-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.74rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 5px;
  }
  .pp-progress-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
  }
  .pp-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #09b8a7, #0ea5e9);
    width: 50%;
    transition: width 0.35s ease;
  }

  .pp-nudge-body {
    padding: 16px 18px 18px;
  }
  .pp-badges-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
  }
  .pp-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.02em;
  }
  .pp-badge-category {
    background: #e0f2fe;
    color: #0369a1;
  }
  .pp-badge-new {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
  }
  .pp-badge-verify {
    background: #ccfbf1;
    color: #0f766e;
    border: 1px solid #99f6e4;
  }

  .pp-question-text {
    font-size: 0.93rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.45;
    margin-bottom: 12px;
  }

  .pp-current-val-box {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 9px 12px;
    font-size: 0.82rem;
    color: #334155;
    margin-bottom: 12px;
  }
  .pp-current-val-box strong {
    color: #0f172a;
  }

  .pp-input-wrap {
    margin-bottom: 14px;
  }
  .pp-input, .pp-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font: inherit;
    font-size: 0.86rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
  }
  .pp-input:focus, .pp-select:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
  }

  .pp-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .pp-btn {
    border: none;
    border-radius: 10px;
    padding: 9px 14px;
    font: inherit;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.15s ease;
  }
  .pp-btn-primary {
    background: #0ea5e9;
    color: #ffffff;
    flex: 1;
  }
  .pp-btn-primary:hover { background: #0284c7; }
  .pp-btn-verify {
    background: #09b8a7;
    color: #ffffff;
    flex: 1;
  }
  .pp-btn-verify:hover { background: #079b8c; }
  .pp-btn-outline {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #cbd5e1;
  }
  .pp-btn-outline:hover { background: #f1f5f9; color: #0f172a; }

  .pp-feedback {
    display: none;
    margin-top: 10px;
    padding: 9px 12px;
    border-radius: 9px;
    font-size: 0.8rem;
    font-weight: 700;
  }
  .pp-feedback.success {
    display: block;
    background: #dcfce7;
    color: #166534;
  }
  .pp-feedback.error {
    display: block;
    background: #fee2f2;
    color: #991b1b;
  }

  .pp-nudge-footer {
    border-top: 1px solid #f1f5f9;
    padding: 10px 18px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.74rem;
    color: #64748b;
  }
  .pp-nudge-footer a {
    color: #0284c7;
    font-weight: 700;
    text-decoration: none;
  }
  .pp-nudge-footer a:hover { text-decoration: underline; }

  @keyframes ppSectionHighlight {
    0% { box-shadow: 0 0 0 4px rgba(9, 184, 167, 0.55); background-color: #f0fdfa; }
    100% { box-shadow: 0 0 0 0 rgba(9, 184, 167, 0); }
  }
  .pp-updated-highlight {
    animation: ppSectionHighlight 2.2s ease-out;
    border-radius: 8px;
  }
</style>

<!-- Tombol Floating untuk membuka Pop-up Progressive Profiling -->
<button type="button" class="pp-nudge-fab" id="ppNudgeFab" style="display:none;" title="Lengkapi & Verifikasi Profil SIAPKerja">
  <span>✨ Lengkapi Profil</span>
  <span class="pp-nudge-fab-pct" id="ppFabPercent">...</span>
</button>

<!-- Pop-up Card Progressive Profiling -->
<aside class="pp-nudge-overlay" id="ppNudgeModal" role="dialog" aria-label="Melengkapi Profil SIAPKerja">
  <div class="pp-nudge-header">
    <div>
      <div class="pp-nudge-title">
        <span>🎯</span>
        <span>Progressive Profiling SIAPKerja</span>
      </div>
      <div class="pp-nudge-sub">Bantu kami melengkapi &amp; memverifikasi keakuratan profil Anda</div>
    </div>
    <button type="button" class="pp-nudge-close" id="ppNudgeCloseBtn" aria-label="Tutup">&times;</button>
  </div>

  <div class="pp-progress-wrap">
    <div class="pp-progress-meta">
      <span id="ppProgressLabel">Kelengkapan Profil</span>
      <span id="ppProgressPct">0%</span>
    </div>
    <div class="pp-progress-bar">
      <div class="pp-progress-fill" id="ppProgressFill"></div>
    </div>
  </div>

  <div class="pp-nudge-body">
    <div class="pp-badges-row">
      <span class="pp-badge pp-badge-category" id="ppCategoryBadge">Kategori</span>
      <span class="pp-badge pp-badge-new" id="ppModeBadge">Pertanyaan Baru</span>
    </div>

    <div class="pp-question-text" id="ppQuestionText">Memuat pertanyaan...</div>

    <div class="pp-current-val-box" id="ppCurrentValBox" style="display:none;">
      Data tercatat saat ini: <strong id="ppCurrentValText"></strong>
    </div>

    <div class="pp-input-wrap" id="ppInputContainer"></div>

    <div class="pp-actions" id="ppActionsContainer"></div>

    <div class="pp-feedback" id="ppFeedbackBox"></div>
  </div>

  <div class="pp-nudge-footer">
    <button type="button" id="ppNextQuestionBtn" style="background:none;border:none;color:#475569;font:inherit;font-weight:700;cursor:pointer;padding:0;">
      ↻ Pertanyaan Acak Lainnya
    </button>
    <a href="dashboard-admin.php?tab=profiling">⚙ Admin Pertanyaan</a>
  </div>
</aside>

<script>
(function () {
  var fab = document.getElementById('ppNudgeFab');
  var modal = document.getElementById('ppNudgeModal');
  var closeBtn = document.getElementById('ppNudgeCloseBtn');
  var nextBtn = document.getElementById('ppNextQuestionBtn');

  var fabPercent = document.getElementById('ppFabPercent');
  var progressLabel = document.getElementById('ppProgressLabel');
  var progressPct = document.getElementById('ppProgressPct');
  var progressFill = document.getElementById('ppProgressFill');

  var catBadge = document.getElementById('ppCategoryBadge');
  var modeBadge = document.getElementById('ppModeBadge');
  var questionText = document.getElementById('ppQuestionText');
  var currentValBox = document.getElementById('ppCurrentValBox');
  var currentValText = document.getElementById('ppCurrentValText');
  var inputContainer = document.getElementById('ppInputContainer');
  var actionsContainer = document.getElementById('ppActionsContainer');
  var feedbackBox = document.getElementById('ppFeedbackBox');

  var currentQuestion = null;
  var currentSchedule = null;
  var answeredInCurrentOpen = 0;

  function showFeedback(msg, type) {
    feedbackBox.textContent = msg;
    feedbackBox.className = 'pp-feedback ' + type;
  }

  function clearFeedback() {
    feedbackBox.textContent = '';
    feedbackBox.className = 'pp-feedback';
  }

  function buildInputHtml(q, prefillValue) {
    var val = prefillValue != null ? prefillValue : '';
    if (q.input_type === 'select' && Array.isArray(q.options) && q.options.length > 0) {
      var optsHtml = '<option value="">-- Pilih Jawaban --</option>';
      q.options.forEach(function (opt) {
        var selected = (opt === val) ? ' selected' : '';
        var safeOpt = String(opt).replace(/"/g, '&quot;');
        optsHtml += '<option value="' + safeOpt + '"' + selected + '>' + opt + '</option>';
      });
      return '<select id="ppAnswerField" class="pp-select">' + optsHtml + '</select>';
    }

    var inputType = (q.input_type === 'tel' || q.input_type === 'url') ? q.input_type : 'text';
    var safeVal = String(val).replace(/"/g, '&quot;');
    var placeholder = q.input_type === 'url'
      ? 'https://...'
      : (q.input_type === 'tel' ? 'Contoh: 0812-xxxx-xxxx' : 'Ketik jawaban Anda di sini...');
    return '<input type="' + inputType + '" id="ppAnswerField" class="pp-input" value="' + safeVal + '" placeholder="' + placeholder + '" />';
  }

  function renderNudge(payload) {
    clearFeedback();
    if (!payload || payload.status !== 'success' || !payload.data) {
      questionText.textContent = 'Seluruh pertanyaan profil telah lengkap!';
      inputContainer.innerHTML = '';
      actionsContainer.innerHTML = '';
      currentValBox.style.display = 'none';
      return;
    }

    var user = payload.user || {};
    var pct = typeof user.completion_percent === 'number' ? user.completion_percent : 50;
    fabPercent.textContent = pct + '%';
    progressPct.textContent = pct + '%';
    progressFill.style.width = pct + '%';
    if (user.filled_columns != null && user.total_columns != null) {
      var emptyCount = user.empty_columns_count != null ? user.empty_columns_count : (user.total_columns - user.filled_columns);
      if (emptyCount > 0) {
        progressLabel.textContent = 'Profil: ' + user.filled_columns + '/' + user.total_columns + ' terisi (' + emptyCount + ' field kosong)';
      } else {
        progressLabel.textContent = 'Profil Lengkap (' + user.filled_columns + '/' + user.total_columns + ' terisi)';
      }
    }

    var q = payload.data;
    currentQuestion = q;

    catBadge.textContent = '📁 ' + (q.category_name || 'Profil');
    if (q.mode === 'new') {
      modeBadge.textContent = '★ Lengkapi Field Kosong';
      modeBadge.className = 'pp-badge pp-badge-new';
    } else {
      modeBadge.textContent = '✓ Verifikasi Data Profil';
      modeBadge.className = 'pp-badge pp-badge-verify';
    }

    questionText.textContent = q.question_text;

    if (q.mode === 'verify') {
      currentValBox.style.display = 'block';
      currentValText.textContent = q.current_value || '-';
      inputContainer.style.display = 'none';
      inputContainer.innerHTML = buildInputHtml(q, q.current_value || '');

      actionsContainer.innerHTML =
        '<button type="button" class="pp-btn pp-btn-verify" id="ppBtnVerifyOk">✓ Ya, Masih Benar</button>' +
        '<button type="button" class="pp-btn pp-btn-outline" id="ppBtnShowEdit">✎ Perbarui Data</button>';

      document.getElementById('ppBtnVerifyOk').addEventListener('click', function () {
        submitAnswer('verify_ok', q.current_value || '');
      });

      document.getElementById('ppBtnShowEdit').addEventListener('click', function () {
        inputContainer.style.display = 'block';
        var field = document.getElementById('ppAnswerField');
        if (field) field.focus();
        actionsContainer.innerHTML =
          '<button type="button" class="pp-btn pp-btn-primary" id="ppBtnSaveEdit">Simpan Perubahan</button>' +
          '<button type="button" class="pp-btn pp-btn-outline" id="ppBtnCancelEdit">Batal</button>';

        document.getElementById('ppBtnSaveEdit').addEventListener('click', function () {
          var f = document.getElementById('ppAnswerField');
          submitAnswer('save', f ? f.value : '');
        });
        document.getElementById('ppBtnCancelEdit').addEventListener('click', function () {
          renderNudge(payload);
        });
      });
    } else {
      // Mode NEW (data masih kosong)
      currentValBox.style.display = 'none';
      inputContainer.style.display = 'block';
      inputContainer.innerHTML = buildInputHtml(q, '');

      actionsContainer.innerHTML =
        '<button type="button" class="pp-btn pp-btn-primary" id="ppBtnSaveNew">Simpan Jawaban</button>';

      document.getElementById('ppBtnSaveNew').addEventListener('click', function () {
        var f = document.getElementById('ppAnswerField');
        submitAnswer('save', f ? f.value : '');
      });
    }
  }

  function hideAllNudgeUi() {
    if (modal) {
      modal.classList.remove('is-open');
      modal.style.display = 'none';
    }
    if (fab) {
      fab.style.display = 'none';
    }
  }

  function fetchNudge(excludeId, triggerType) {
    var params = [];
    if (excludeId) {
      params.push('exclude_id=' + encodeURIComponent(excludeId));
    }
    if (triggerType) {
      params.push('trigger=' + encodeURIComponent(triggerType));
    }
    var url = 'get_nudge.php' + (params.length > 0 ? '?' + params.join('&') : '');

    return fetch(url, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || data.status === 'completed_today' || (data.schedule && data.schedule.quota_reached)) {
          hideAllNudgeUi();
          return data;
        }
        if (data.schedule) {
          currentSchedule = data.schedule;
          if (fab) {
            fab.style.display = (data.schedule.is_enabled && data.schedule.show_fab_button && !data.schedule.quota_reached)
              ? 'inline-flex'
              : 'none';
          }
          if (nextBtn) {
            nextBtn.style.display = (data.schedule.frequency_mode === 'always' || data.schedule.max_questions_per_show > 1)
              ? 'inline-block'
              : 'none';
          }
        }
        renderNudge(data);
        return data;
      })
      .catch(function () {
        hideAllNudgeUi();
        return null;
      });
  }

  function refreshProfilePageLive(targetColumn) {
    var shell = document.querySelector('.profile-shell');
    if (!shell) return Promise.resolve();

    var oldBackdrop = document.getElementById('biodataModalBackdrop');
    var wasBiodataOpen = oldBackdrop && oldBackdrop.classList.contains('open');

    return fetch(window.location.href, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (res) { return res.text(); })
      .then(function (html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newShell = doc.querySelector('.profile-shell');
        if (newShell) {
          shell.innerHTML = newShell.innerHTML;
          if (typeof window.initProfileInteractions === 'function') {
            window.initProfileInteractions();
          }
          var newBackdrop = document.getElementById('biodataModalBackdrop');
          if (wasBiodataOpen && newBackdrop) {
            newBackdrop.classList.add('open');
          }
          if (targetColumn) {
            var updatedEl = shell.querySelector('[data-profile-field~="' + targetColumn + '"]');
            if (updatedEl) {
              if (newBackdrop && newBackdrop.contains(updatedEl)) {
                newBackdrop.classList.add('open');
              }
              updatedEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
              updatedEl.classList.remove('pp-updated-highlight');
              void updatedEl.offsetWidth;
              updatedEl.classList.add('pp-updated-highlight');
            }
          }
        }
      })
      .catch(function () {});
  }

  function submitAnswer(actionType, value) {
    if (!currentQuestion) return;
    if (actionType === 'save' && (!value || !String(value).trim())) {
      showFeedback('Silakan isi atau pilih jawaban terlebih dahulu.', 'error');
      return;
    }

    var answeredCol = currentQuestion.target_column;
    var answeredId = currentQuestion.question_id;

    fetch('save_nudge.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        target_column: answeredCol,
        action_type: actionType,
        answer_value: String(value).trim()
      })
    })
      .then(function (res) { return res.json(); })
      .then(function (resp) {
        if (resp.status === 'success') {
          answeredInCurrentOpen++;
          var maxPerShow = (currentSchedule && currentSchedule.max_questions_per_show) ? currentSchedule.max_questions_per_show : 1;
          var isDoneForToday = Boolean(resp.quota_reached) || (answeredInCurrentOpen >= maxPerShow && (!currentSchedule || currentSchedule.frequency_mode !== 'always'));

          refreshProfilePageLive(actionType === 'save' ? answeredCol : null).then(function () {
            if (isDoneForToday) {
              // User sudah mengisi kuota pertanyaan hari ini:
              // Sembunyikan input & tombol, tampilkan konfirmasi singkat, lalu tutup pop-up & FAB sepenuhnya
              inputContainer.style.display = 'none';
              inputContainer.innerHTML = '';
              actionsContainer.innerHTML = '';
              currentValBox.style.display = 'none';
              questionText.textContent = 'Terima kasih! Jawaban Anda telah disimpan. Pertanyaan baru akan muncul esok hari.';
              showFeedback(resp.message || 'Tersimpan! Pertanyaan baru akan muncul esok hari.', 'success');
              if (fab) {
                fab.style.display = 'none';
              }
              setTimeout(function () {
                hideAllNudgeUi();
              }, 1100);
            } else {
              showFeedback(resp.message || 'Tersimpan!', 'success');
              setTimeout(function () {
                fetchNudge(answeredId || 0, 'manual');
              }, 700);
            }
          });
        } else {
          showFeedback(resp.message || 'Gagal menyimpan.', 'error');
        }
      })
      .catch(function () {
        showFeedback('Terjadi kesalahan jaringan saat menyimpan.', 'error');
      });
  }

  if (fab && modal) {
    fab.addEventListener('click', function () {
      var willOpen = !modal.classList.contains('is-open');
      if (willOpen) {
        modal.style.display = '';
      }
      modal.classList.toggle('is-open', willOpen);
    });
  }

  if (closeBtn && modal) {
    closeBtn.addEventListener('click', function () {
      modal.classList.remove('is-open');
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      fetchNudge(currentQuestion ? currentQuestion.question_id : 0, 'manual');
    });
  }

  // Muat pertanyaan pertama kali dengan trigger=auto (mengecek apakah user sudah mengisi hari ini)
  fetchNudge(0, 'auto').then(function (data) {
    if (!data || data.status === 'completed_today' || !data.schedule) return;
    if (data.schedule.is_enabled && data.schedule.should_auto_show && !data.schedule.quota_reached && modal) {
      setTimeout(function () {
        answeredInCurrentOpen = 0;
        modal.style.display = '';
        modal.classList.add('is-open');
      }, 400);
    }
  });
})();
</script>
