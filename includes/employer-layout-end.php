<?php
declare(strict_types=1);
?>
      </main>

      <style>
        .gig-post-modal {
          max-width: 860px !important;
          border-radius: 18px;
          overflow: hidden;
          box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
        }
        .gig-post-modal .modal-header {
          border-bottom: 1px solid #e2e8f0;
          padding: 18px 22px 14px;
          background: #ffffff;
        }
        .gig-post-modal .modal-body {
          max-height: 72vh;
          overflow-y: auto;
          padding: 16px 18px;
          background: #f8fafc;
        }
        .gig-post-modal .modal-footer {
          padding: 14px 18px;
          border-top: 1px solid #e2e8f0;
          background: #ffffff;
          position: sticky;
          bottom: 0;
          z-index: 4;
        }
        .gig-post-modal .modal-close-btn {
          width: 34px;
          height: 34px;
          border-radius: 999px;
          border: 1px solid #e2e8f0;
          background: #ffffff;
          color: #64748b;
          font-size: 1.25rem;
          line-height: 1;
        }
        .gig-post-modal .modal-close-btn:hover {
          background: #f8fafc;
          color: #0f172a;
        }
        .gig-post-head-title {
          font-size: 1.35rem;
          font-weight: 800;
          color: #0f172a;
          margin-bottom: 2px;
        }
        .gig-post-head-sub {
          font-size: 0.86rem;
          color: #64748b;
        }
        .gig-stepper {
          margin-top: 12px;
          display: flex;
          align-items: center;
          gap: 8px;
          flex-wrap: wrap;
        }
        .gig-step-item {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          font-size: 0.76rem;
          color: #64748b;
          font-weight: 700;
        }
        .gig-step-dot {
          width: 18px;
          height: 18px;
          border-radius: 999px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-size: 0.68rem;
          font-weight: 800;
          background: #e2e8f0;
          color: #334155;
        }
        .gig-step-item.active .gig-step-dot {
          background: #0ea5e9;
          color: #ffffff;
        }
        .gig-step-sep {
          width: 28px;
          height: 1px;
          background: #cbd5e1;
        }
        .gig-section-card {
          border: 1px solid #e2e8f0;
          border-radius: 14px;
          background: #ffffff;
          padding: 14px 14px 14px;
          margin-bottom: 13px;
          box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .gig-section-head {
          display: flex;
          align-items: flex-start;
          gap: 10px;
          margin-bottom: 12px;
          padding-bottom: 8px;
          border-bottom: 1px solid #f1f5f9;
        }
        .gig-section-icon {
          width: 28px;
          height: 28px;
          border-radius: 8px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          background: #ecfeff;
          color: #0891b2;
          font-size: 0.85rem;
          font-weight: 800;
          flex-shrink: 0;
        }
        .gig-section-title {
          font-size: 0.98rem;
          font-weight: 800;
          color: #0f172a;
          margin-bottom: 2px;
        }
        .gig-section-sub {
          font-size: 0.75rem;
          color: #64748b;
        }
        .gig-grid-2 {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 12px;
        }
        .gig-post-modal .form-row {
          display: flex;
          flex-direction: column;
          gap: 6px;
          margin-bottom: 12px;
        }
        .gig-post-modal .form-row:last-child {
          margin-bottom: 0;
        }
        .gig-post-modal .form-row label {
          font-size: 0.84rem;
          color: #1e293b;
          font-weight: 700;
        }
        .gig-post-modal input[type="text"],
        .gig-post-modal input[type="number"],
        .gig-post-modal input[type="date"],
        .gig-post-modal select,
        .gig-post-modal textarea {
          width: 100%;
          border: 1px solid #cbd5e1;
          border-radius: 12px;
          background: #ffffff;
          color: #0f172a;
          font-size: 0.9rem;
          padding: 10px 12px;
          transition: border-color .16s ease, box-shadow .16s ease;
        }
        .gig-post-modal textarea {
          resize: vertical;
          min-height: 84px;
        }
        .gig-post-modal input:focus,
        .gig-post-modal select:focus,
        .gig-post-modal textarea:focus {
          outline: none;
          border-color: #38bdf8;
          box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
        }
        .gig-duration-split {
          display: grid;
          grid-template-columns: 1fr 1.25fr;
          gap: 8px;
        }
        .gig-inline-radio {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 12px;
          margin-top: 6px;
        }
        .gig-inline-radio label {
          display: flex;
          align-items: flex-start;
          justify-content: flex-start;
          gap: 12px;
          font-size: 0.85rem;
          color: #0f172a;
          font-weight: 700;
          cursor: pointer;
          border: 1px solid #cbd5e1;
          background: #ffffff;
          padding: 12px 14px;
          border-radius: 12px;
          line-height: 1.35;
          min-height: 60px;
          text-align: left;
          transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease;
        }
        .gig-inline-radio label:hover {
          border-color: #0ea5e9;
          background: #f0f9ff;
        }
        .gig-inline-radio label:has(input[type="radio"]:checked) {
          border-color: #0ea5e9;
          background: #f0f9ff;
          box-shadow: 0 0 0 1px #0ea5e9;
        }
        .gig-inline-radio input[type="radio"] {
          margin-top: 2px;
          width: 16px;
          height: 16px;
          flex-shrink: 0;
          accent-color: #0ea5e9;
          cursor: pointer;
        }
        .gig-inline-radio span {
          display: block;
          text-align: left;
          flex: 1;
        }
        .gig-inline-radio input[type="radio"]:checked + span {
          color: #0369a1;
        }
        .gig-inline-radio small {
          display: block;
          margin-top: 2px;
          color: #64748b;
          font-size: 0.76rem;
          font-weight: 500;
          line-height: 1.35;
        }
        .gig-toggle-row {
          margin: 4px 0 2px;
          padding: 10px 12px;
          border: 1px solid #e2e8f0;
          border-radius: 10px;
          background: #f8fafc;
        }
        .gig-checkbox-label {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 700;
          font-size: 0.84rem;
          color: #1e293b;
          cursor: pointer;
          margin: 0;
        }
        .gig-checkbox-label input[type="checkbox"] {
          width: 16px;
          height: 16px;
          accent-color: #0ea5e9;
          flex-shrink: 0;
        }
        .gig-checkbox-note {
          margin-top: 4px;
          margin-left: 24px;
          font-size: 0.74rem;
          color: #64748b;
          line-height: 1.4;
        }
        .gig-form-callout {
          font-size: 0.8rem;
          color: #1e40af;
          background: #eff6ff;
          border: 1px solid #bfdbfe;
          border-radius: 10px;
          padding: 10px 12px;
          margin-bottom: 14px;
        }
        .gig-note {
          font-size: 0.74rem;
          color: #64748b;
          margin-top: 4px;
          line-height: 1.45;
        }
        @media (max-width: 760px) {
          .gig-grid-2 {
            grid-template-columns: 1fr;
          }
          .gig-inline-radio {
            grid-template-columns: 1fr;
          }
          .gig-duration-split {
            grid-template-columns: 1fr;
          }
        }
      </style>

      <div class="modal-backdrop" id="postProjectModal" onclick="handleBackdropClick(event)">
        <div class="modal-window gig-post-modal">
          <div class="modal-header">
            <div>
              <div class="gig-post-head-title">Tambah Lowongan Proyek</div>
              <div class="gig-post-head-sub">Lengkapi form untuk mempublikasikan proyek dan merekrut 1 Gig Workers.</div>
              <div class="gig-stepper">
                <span class="gig-step-item active"><span class="gig-step-dot">1</span>Informasi Proyek</span>
                <span class="gig-step-sep"></span>
                <span class="gig-step-item"><span class="gig-step-dot">2</span>Kriteria Gig Workers</span>
                <span class="gig-step-sep"></span>
                <span class="gig-step-item"><span class="gig-step-dot">3</span>Tambahan</span>
              </div>
            </div>
            <button class="modal-close-btn" type="button" onclick="closePostProjectModal()">&times;</button>
          </div>
          <form id="newProjectForm" onsubmit="handleCreateProject(event)">
            <div class="modal-body">

              <div class="gig-form-callout">
                &#9432; Lowongan akan diverifikasi Admin terlebih dahulu sebelum tayang ke Gig Workers.
              </div>

              <section class="gig-section-card">
                <div class="gig-section-head">
                  <span class="gig-section-icon">1</span>
                  <div>
                    <div class="gig-section-title">Informasi Proyek</div>
                    <div class="gig-section-sub">Judul, deskripsi, durasi, deliverable, dan lokasi proyek.</div>
                  </div>
                </div>

                <div class="form-row">
                  <label for="proj_title">Judul Lowongan Proyek *</label>
                  <input type="text" id="proj_title" required placeholder="Contoh: Redesign Dashboard Monitoring Penjualan" />
                </div>

                <div class="form-row">
                  <label for="proj_desc">Deskripsi Proyek *</label>
                  <textarea id="proj_desc" rows="4" required placeholder="Jelaskan masalah, ruang lingkup, dan hasil yang diharapkan dari Gig Worker..."></textarea>
                </div>

                <div class="gig-grid-2">
                  <div class="form-row">
                    <label for="proj_kbji">Jabatan Sesuai KBJI *</label>
                    <input type="text" id="proj_kbji" required placeholder="Contoh: 2512 - Pengembang Perangkat Lunak" />
                  </div>
                  <div class="form-row">
                    <label for="proj_category">Bidang *</label>
                    <select id="proj_category" required>
                      <option value="">-- Pilih bidang --</option>
                      <option value="IT &amp; Pemrograman">IT &amp; Pemrograman</option>
                      <option value="Desain &amp; Kreatif">Desain &amp; Kreatif</option>
                      <option value="Pemasaran &amp; Konten">Pemasaran &amp; Konten</option>
                      <option value="Penulisan &amp; Terjemahan">Penulisan &amp; Terjemahan</option>
                      <option value="Akuntansi &amp; Keuangan">Akuntansi &amp; Keuangan</option>
                      <option value="Manajemen Proyek">Manajemen Proyek</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>
                </div>

                <div class="gig-grid-2">
                  <div class="form-row">
                    <label for="proj_duration">Durasi Proyek *</label>
                    <div class="gig-duration-split">
                      <input type="number" id="proj_duration_value" min="1" max="36" value="1" required />
                      <select id="proj_duration_unit" required>
                        <option value="Hari">Hari</option>
                        <option value="Minggu" selected>Minggu</option>
                        <option value="Bulan">Bulan</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <label for="proj_target">Target / Deliverable Proyek *</label>
                  <textarea id="proj_target" rows="3" required placeholder="Contoh: 12 layar high-fidelity + prototype interaktif + style guide komponen"></textarea>
                </div>

                <div class="gig-grid-2">
                  <div class="form-row">
                    <label for="proj_province">Lokasi (Provinsi) *</label>
                    <input type="text" id="proj_province" required placeholder="Contoh: DKI Jakarta" />
                  </div>
                  <div class="form-row">
                    <label for="proj_city">Lokasi (Kota/Kabupaten) *</label>
                    <input type="text" id="proj_city" required placeholder="Contoh: Jakarta Selatan" />
                  </div>
                </div>
              </section>

              <section class="gig-section-card">
                <div class="gig-section-head">
                  <span class="gig-section-icon">2</span>
                  <div>
                    <div class="gig-section-title">Kriteria Gig Worker</div>
                    <div class="gig-section-sub">Atur skill wajib dan persyaratan kandidat.</div>
                  </div>
                </div>

                <div class="form-row">
                  <label for="proj_skills">Skill Wajib *</label>
                  <input type="text" id="proj_skills" required placeholder="Contoh: Figma, UI Audit, Design System (pisahkan dengan koma)" />
                </div>

                <div class="form-row">
                  <label for="proj_kualifikasi">Persyaratan Tambahan</label>
                  <textarea id="proj_kualifikasi" rows="3" placeholder="Contoh: Pernah mengerjakan dashboard analytics, paham handoff ke developer, terbiasa kerja sprint."></textarea>
                </div>
              </section>

              <section class="gig-section-card">
                <div class="gig-section-head">
                  <span class="gig-section-icon">3</span>
                  <div>
                    <div class="gig-section-title">Tambahan &amp; Publikasi</div>
                    <div class="gig-section-sub">Opsi remote, nominal gaji, dan periode tayang lowongan.</div>
                  </div>
                </div>

                <div class="form-row">
                  <label>Opsi Pengerjaan Remote</label>
                  <div class="gig-inline-radio">
                    <label>
                      <input type="radio" name="proj_lokasi_type" value="luring" checked>
                      <span>Tidak Remote<small>Pengerjaan on-site atau hybrid.</small></span>
                    </label>
                    <label>
                      <input type="radio" name="proj_lokasi_type" value="remote">
                      <span>Ya, project remote<small>Pengerjaan jarak jauh dari lokasi manapun.</small></span>
                    </label>
                  </div>
                  <div class="gig-note">Lokasi provinsi dan kota tetap wajib diisi pada bagian Informasi Proyek.</div>
                </div>

                <div class="form-row">
                  <label for="proj_salary">Gaji (Rp) *</label>
                  <input type="text" id="proj_salary" required placeholder="Contoh: 7500000" />
                </div>

                <div class="gig-toggle-row">
                  <label for="proj_show_salary" class="gig-checkbox-label">
                    <input type="checkbox" id="proj_show_salary" checked />
                    <span>Tampilkan nominal gaji di postingan lowongan</span>
                  </label>
                </div>

                <div class="gig-grid-2">
                  <div class="form-row">
                    <label for="proj_deadline">Batas Waktu Lamaran *</label>
                    <input type="date" id="proj_deadline" required value="2026-09-30" />
                  </div>
                  <div class="form-row">
                    <label for="proj_visibility">Visibilitas Lowongan</label>
                    <select id="proj_visibility">
                      <option value="public" selected>Publik (terlihat di Bursa Gig Worker)</option>
                      <option value="limited">Terbatas (hanya kandidat tertentu)</option>
                    </select>
                  </div>
                </div>

                <div class="gig-note">Setiap lowongan secara otomatis hanya dapat merekrut <strong>1 Gig Worker</strong>.</div>
              </section>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" onclick="closePostProjectModal()">Batal</button>
              <button type="submit" class="btn-primary">Ajukan untuk Verifikasi &#8594;</button>
            </div>
          </form>
        </div>
      </div>

      <div class="toast-container" id="toastContainer"></div>
    </div>
  </div>
</div>
<script>
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.profile-menu')) {
      document.querySelectorAll('.profile-dropdown').forEach(function (drop) {
        drop.classList.remove('open');
      });
    }
  });

  function openPostProjectModal() {
    document.getElementById('postProjectModal').classList.add('open');
  }
  function closePostProjectModal() {
    document.getElementById('postProjectModal').classList.remove('open');
  }
  function handleBackdropClick(e) {
    if (e.target.id === 'postProjectModal') closePostProjectModal();
  }
  function handleCreateProject(e) {
    e.preventDefault();
    const title = document.getElementById('proj_title').value;
    const locRadio = document.querySelector('input[name="proj_lokasi_type"]:checked');
    const durationValue = Number(document.getElementById('proj_duration_value').value || 0);
    const durationUnit = document.getElementById('proj_duration_unit').value || 'Minggu';
    if (!durationValue || durationValue < 1) {
      showToast('Durasi proyek harus diisi minimal 1.');
      return;
    }
    const durationLabel = String(durationValue) + ' ' + String(durationUnit);
    const province = (document.getElementById('proj_province').value || '').trim();
    const city = (document.getElementById('proj_city').value || '').trim();
    if (!province || !city) {
      showToast('Lokasi proyek wajib diisi: provinsi dan kota/kabupaten.');
      return;
    }
    const salaryRaw = document.getElementById('proj_salary').value || '';
    const salaryVal = Number((salaryRaw + '').replace(/[^\d]/g, ''));
    if (!salaryVal || salaryVal < 10000) {
      showToast('Gaji proyek belum valid. Masukkan nominal gaji yang sesuai.');
      return;
    }

    const payload = {
      title: title,
      category: document.getElementById('proj_category').value,
      kbji: document.getElementById('proj_kbji').value,
      duration: durationLabel,
      desc: document.getElementById('proj_desc').value,
      target: document.getElementById('proj_target').value,
      qualifications: document.getElementById('proj_kualifikasi').value,
      visibility: document.getElementById('proj_visibility').value,
      skills: document.getElementById('proj_skills').value,
      budget_min: salaryVal,
      budget_max: salaryVal,
      deadline: document.getElementById('proj_deadline').value,
      budget: 'Rp ' + salaryVal.toLocaleString('id-ID'),
      show_salary: document.getElementById('proj_show_salary').checked,
      location_type: locRadio ? locRadio.value : 'luring',
      location_detail: city + ', ' + province,
      location_city: city,
      location_province: province
    };
    fetch('vacancy-submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); }).then(function (data) {
      closePostProjectModal();
      document.getElementById('newProjectForm').reset();
      if (data && data.ok) {
        showToast('Lowongan "' + title + '" berhasil diajukan. Menunggu verifikasi Admin KarirHub.');
        setTimeout(function () { window.location.href = 'employer-lowongan.php'; }, 700);
      } else {
        showToast((data && data.error) ? data.error : 'Gagal mengajukan lowongan.');
      }
    }).catch(function () {
      showToast('Gagal mengajukan lowongan. Periksa koneksi server.');
    });
  }
  function copyContact(name, phone, email) {
    const textToCopy = 'Nama: ' + name + '\nWhatsApp: ' + phone + '\nEmail: ' + email;
    if (navigator.clipboard) navigator.clipboard.writeText(textToCopy);
    showToast('Kontak ' + name + ' disalin. WA: ' + phone);
  }
  function hireApplicant(name) {
    showToast('Gig Worker ' + name + ' resmi direkrut. Kontak terbuka dan proyek aktif.');
  }
  function showToast(message) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'toast-card';
    toast.innerHTML = '<span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(function () { toast.remove(); }, 300);
    }, 3500);
  }
  function filterApplicants(category, btn) {
    document.querySelectorAll('.toolbar-filter .filter-btn-pill').forEach(function (b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');
    const cards = document.querySelectorAll('.applicant-card');
    let count = 0;
    cards.forEach(function (card) {
      const cat = card.getAttribute('data-category');
      const show = category === 'all' || cat === category;
      card.style.display = show ? 'grid' : 'none';
      if (show) count++;
    });
    const el = document.getElementById('applicants-visible-count');
    if (el) el.innerText = String(count);
  }
  function searchApplicants(query) {
    const q = (query || '').toLowerCase().trim();
    const terms = q.split(/\s+/).filter(Boolean);
    const cards = document.querySelectorAll('.applicant-card');
    let count = 0;
    cards.forEach(function (card) {
      const corpus = (card.getAttribute('data-search-corpus') || card.innerText || '').toLowerCase();
      let match = true;
      if (terms.length > 0) {
        for (let i = 0; i < terms.length; i++) {
          if (corpus.indexOf(terms[i]) === -1) {
            match = false;
            break;
          }
        }
      }
      card.style.display = match ? 'grid' : 'none';
      if (match) count++;
    });
    const el = document.getElementById('applicants-visible-count');
    if (el) el.innerText = String(count);
  }
  function filterVacancyRows(status, btn) {
    document.querySelectorAll('.toolbar-filter .filter-btn-pill').forEach(function (b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');
    document.querySelectorAll('#vacancy-table-body tr').forEach(function (row) {
      const rowStatus = row.getAttribute('data-status');
      row.style.display = (status === 'all' || rowStatus === status) ? '' : 'none';
    });
  }
  function searchVacancies(query) {
    const q = (query || '').toLowerCase();
    document.querySelectorAll('#vacancy-table-body tr').forEach(function (row) {
      row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  }
</script>
</body>
</html>
