<?php
declare(strict_types=1);
?>
      </main>

      <style>
        .gig-post-modal {
          max-width: 860px !important;
          max-height: 90vh;
          border-radius: 18px;
          overflow: hidden;
          display: flex;
          flex-direction: column;
          box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
        }
        .gig-post-modal form {
          display: flex;
          flex-direction: column;
          flex: 1 1 auto;
          min-height: 0;
          overflow: hidden;
        }
        .gig-post-modal .modal-header {
          flex-shrink: 0;
          border-bottom: 1px solid #e2e8f0;
          padding: 18px 22px 14px;
          background: #ffffff;
        }
        .gig-post-modal .modal-body {
          flex: 1 1 auto;
          min-height: 0;
          max-height: none;
          overflow-y: auto;
          padding: 16px 18px 20px;
          background: #f8fafc;
        }
        .gig-post-modal .modal-footer {
          flex-shrink: 0;
          padding: 14px 18px;
          border-top: 1px solid #e2e8f0;
          background: #ffffff;
          position: static;
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
        .gig-step-item.active {
          color: #0284c7;
        }
        .gig-step-item.active .gig-step-dot {
          background: #0ea5e9;
          color: #ffffff;
        }
        .gig-step-item.completed {
          color: #0f172a;
          cursor: pointer;
        }
        .gig-step-item.completed .gig-step-dot {
          background: #10b981;
          color: #ffffff;
        }
        .gig-step-panel {
          display: none;
        }
        .gig-step-panel.active {
          display: block;
          animation: gigStepFadeIn .18s ease;
        }
        @keyframes gigStepFadeIn {
          from { opacity: 0; transform: translateY(4px); }
          to { opacity: 1; transform: translateY(0); }
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
        /* Optional Feature Switch Card */
        .gig-optional-card {
          border: 1.5px dashed #cbd5e1;
          border-radius: 12px;
          background: #f8fafc;
          padding: 14px 16px;
          margin: 14px 0 12px;
          transition: all 0.2s ease;
        }
        .gig-optional-card.active {
          border-style: solid;
          border-color: #38bdf8;
          background: #f0f9ff;
          box-shadow: 0 2px 10px rgba(14, 165, 233, 0.08);
        }
        .gig-optional-head {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 14px;
        }
        .gig-optional-info {
          display: flex;
          align-items: flex-start;
          gap: 12px;
          flex: 1;
        }
        .gig-optional-icon {
          width: 36px;
          height: 36px;
          border-radius: 10px;
          background: #e2e8f0;
          color: #64748b;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.15rem;
          flex-shrink: 0;
          transition: all 0.2s ease;
        }
        .gig-optional-card.active .gig-optional-icon {
          background: #0ea5e9;
          color: #ffffff;
        }
        .gig-optional-title-row {
          display: flex;
          align-items: center;
          gap: 8px;
          flex-wrap: wrap;
        }
        .gig-optional-title {
          font-size: 0.92rem;
          font-weight: 700;
          color: #0f172a;
          line-height: 1.3;
        }
        .gig-optional-badge {
          display: inline-block;
          font-size: 0.68rem;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          padding: 2px 8px;
          border-radius: 9999px;
          background: #e2e8f0;
          color: #64748b;
          border: 1px solid #cbd5e1;
        }
        .gig-optional-card.active .gig-optional-badge {
          background: #e0f2fe;
          color: #0284c7;
          border-color: #bae6fd;
        }
        .gig-optional-desc {
          font-size: 0.77rem;
          color: #64748b;
          margin-top: 3px;
          line-height: 1.4;
        }
        .gig-switch-wrap {
          display: flex;
          align-items: center;
          gap: 8px;
          flex-shrink: 0;
        }
        .gig-switch-status {
          font-size: 0.76rem;
          font-weight: 700;
          color: #94a3b8;
          min-width: 58px;
          text-align: right;
          transition: color 0.2s ease;
        }
        .gig-optional-card.active .gig-switch-status {
          color: #0284c7;
        }
        .gig-switch {
          position: relative;
          display: inline-block;
          width: 44px;
          height: 24px;
          flex-shrink: 0;
          cursor: pointer;
        }
        .gig-switch input {
          opacity: 0;
          width: 0;
          height: 0;
          position: absolute;
        }
        .gig-slider {
          position: absolute;
          cursor: pointer;
          top: 0; left: 0; right: 0; bottom: 0;
          background-color: #cbd5e1;
          transition: .25s ease;
          border-radius: 24px;
        }
        .gig-slider:before {
          position: absolute;
          content: "";
          height: 18px;
          width: 18px;
          left: 3px;
          bottom: 3px;
          background-color: white;
          transition: .25s ease;
          border-radius: 50%;
          box-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .gig-switch input:checked + .gig-slider {
          background-color: #0ea5e9;
        }
        .gig-switch input:checked + .gig-slider:before {
          transform: translateX(20px);
        }
        .gig-optional-body {
          display: none;
          margin-top: 14px;
          padding-top: 14px;
          border-top: 1px solid #e2e8f0;
          animation: gigFadeDown 0.2s ease-out;
        }
        @keyframes gigFadeDown {
          from { opacity: 0; transform: translateY(-4px); }
          to { opacity: 1; transform: translateY(0); }
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
              <div class="gig-post-head-title">Tambah Lowongan</div>
              <div class="gig-post-head-sub">Lengkapi form untuk mempublikasikan lowongan dan merekrut 1 kandidat.</div>
              <div class="gig-stepper">
                <span class="gig-step-item active" data-step="1" onclick="goToProjectStep(1)"><span class="gig-step-dot">1</span>Informasi Lowongan</span>
                <span class="gig-step-sep"></span>
                <span class="gig-step-item" data-step="2" onclick="goToProjectStep(2)"><span class="gig-step-dot">2</span>Kriteria Pelamar</span>
                <span class="gig-step-sep"></span>
                <span class="gig-step-item" data-step="3" onclick="goToProjectStep(3)"><span class="gig-step-dot">3</span>Tambahan</span>
              </div>
            </div>
            <button class="modal-close-btn" type="button" onclick="closePostProjectModal()">&times;</button>
          </div>
          <form id="newProjectForm" novalidate onsubmit="handleCreateProject(event)">
            <div class="modal-body" id="postProjectModalBody">

              <div class="gig-form-callout">
                &#9432; Lowongan akan diverifikasi Admin terlebih dahulu sebelum dipublikasikan ke pelamar.
              </div>

              <section class="gig-section-card gig-step-panel active" data-step="1">
                <div class="gig-section-head">
                  <span class="gig-section-icon">1</span>
                  <div>
                    <div class="gig-section-title">Informasi Lowongan</div>
                    <div class="gig-section-sub">Judul, deskripsi, durasi, nominal imbalan, batas waktu lamaran, deliverable, dan lokasi.</div>
                  </div>
                </div>

                <div class="form-row">
                  <label for="proj_title">Judul Lowongan *</label>
                  <input type="text" id="proj_title" required placeholder="Contoh: Redesign Dashboard Monitoring Penjualan" />
                </div>

                <div class="form-row">
                  <label for="proj_kbji">Jabatan Sesuai KBJI *</label>
                  <input type="text" id="proj_kbji" required placeholder="Contoh: 2512 - Pengembang Perangkat Lunak" />
                </div>

                <div class="gig-grid-2">
                  <div class="form-row">
                    <label for="proj_category">Bidang *</label>
                    <select id="proj_category" required>
                      <option value="">-- Pilih bidang --</option>
                      <option value="IT &amp; Pemrograman">IT &amp; Pemrograman</option>
                      <option value="Desain &amp; Kreatif">Desain &amp; Kreatif</option>
                      <option value="Pemasaran &amp; Konten">Pemasaran &amp; Konten</option>
                      <option value="Penulisan &amp; Terjemahan">Penulisan &amp; Terjemahan</option>
                      <option value="Akuntansi &amp; Keuangan">Akuntansi &amp; Keuangan</option>
                      <option value="Manajemen &amp; Operasional">Manajemen &amp; Operasional</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>
                  <div class="form-row">
                    <label for="proj_duration">Durasi Pengerjaan *</label>
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

                <div class="form-row">
                  <label for="proj_salary">Nominal Imbalan (Rp) *</label>
                  <input type="text" id="proj_salary" required placeholder="Contoh: 7500000" />
                </div>

                <div class="gig-toggle-row" style="margin-bottom: 12px;">
                  <label for="proj_show_salary" class="gig-checkbox-label">
                    <input type="checkbox" id="proj_show_salary" checked />
                    <span>Tampilkan nominal imbalan di postingan lowongan</span>
                  </label>
                </div>

                <div class="form-row">
                  <label for="proj_desc">Deskripsi Pekerjaan *</label>
                  <textarea id="proj_desc" rows="4" required placeholder="Jelaskan masalah, ruang lingkup, dan hasil yang diharapkan dari pelamar..."></textarea>
                </div>

                <div class="form-row">
                  <label for="proj_target">Target / Deliverables *</label>
                  <textarea id="proj_target" rows="3" required placeholder="Contoh: 12 layar high-fidelity + prototype interaktif + style guide komponen"></textarea>
                </div>

                <?php
                  $idMonths = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                  $deadlineOptions = [];
                  foreach ([1, 2, 3] as $m) {
                      $ts = strtotime('+' . $m . ' month');
                      $labelDate = date('j', $ts) . ' ' . ($idMonths[(int)date('n', $ts)] ?? date('M', $ts)) . ' ' . date('Y', $ts);
                      $deadlineOptions[] = [
                          'value' => date('Y-m-d', $ts),
                          'label' => $m . ' Bulan (' . $labelDate . ')',
                      ];
                  }
                ?>
                <div class="form-row">
                  <label for="proj_deadline">Batas Waktu Lamaran *</label>
                  <select id="proj_deadline" required>
                    <option value="">Pilih batas waktu lamaran</option>
                    <?php foreach ($deadlineOptions as $idx => $opt): ?>
                      <option value="<?php echo htmlspecialchars($opt['value'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $idx === 0 ? ' selected' : ''; ?>><?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </section>

              <section class="gig-section-card gig-step-panel" data-step="2">
                <div class="gig-section-head">
                  <span class="gig-section-icon">2</span>
                  <div>
                    <div class="gig-section-title">Kriteria Pelamar</div>
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

              <section class="gig-section-card gig-step-panel" data-step="3">
                <div class="gig-section-head">
                  <span class="gig-section-icon">3</span>
                  <div>
                    <div class="gig-section-title">Tambahan &amp; Publikasi</div>
                    <div class="gig-section-sub">Opsi remote dan visibilitas lowongan.</div>
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
                      <span>Ya, kerja remote<small>Pengerjaan jarak jauh dari lokasi manapun.</small></span>
                    </label>
                  </div>
                  <div class="gig-note">Lokasi provinsi dan kota tetap wajib diisi pada bagian Informasi Lowongan.</div>
                </div>

                <div class="form-row">
                  <label for="proj_visibility">Visibilitas Lowongan</label>
                  <select id="proj_visibility">
                    <option value="public" selected>Publik</option>
                    <option value="limited">Terbatas (hanya kandidat tertentu)</option>
                  </select>
                </div>

                <?php
                  require_once __DIR__ . '/vacancy-store.php';
                  $specialReqTypes = gig_special_requirement_types();
                ?>
                <div class="gig-optional-card" id="projSpecialReqCard">
                  <div class="gig-optional-head" onclick="handleOptionalHeadClick(event)" style="cursor: pointer;">
                    <div class="gig-optional-info">
                      <div class="gig-optional-icon">&#127894;</div>
                      <div>
                        <div class="gig-optional-title-row">
                          <span class="gig-optional-title">Wajibkan Kualifikasi Khusus</span>
                          <span class="gig-optional-badge">Opsional</span>
                        </div>
                        <div class="gig-optional-desc">Secara default pelamar cukup melampirkan lamaran standar. Aktifkan jika posisi ini mewajibkan sertifikasi, lisensi resmi, atau portofolio khusus.</div>
                      </div>
                    </div>
                    <div class="gig-switch-wrap">
                      <span class="gig-switch-status" id="projSpecialReqSwitchStatus">Tidak Aktif</span>
                      <label class="gig-switch" title="Aktifkan syarat kualifikasi khusus (opsional)">
                        <input type="checkbox" id="proj_special_req_toggle" onchange="toggleSpecialReq(this.checked)" />
                        <span class="gig-slider"></span>
                      </label>
                    </div>
                  </div>

                  <div class="gig-optional-body" id="projSpecialReqBody">
                    <div style="font-size:0.78rem; color:#64748b; margin-bottom:12px;">
                      Anda dapat menambahkan lebih dari satu kualifikasi khusus sesuai kebutuhan proyek.
                    </div>

                    <!-- Container for multiple qualification items -->
                    <div id="projSpecialReqItemsList"></div>

                    <!-- Add More Button -->
                    <button type="button" id="btnAddSpecialReqItem" onclick="addSpecialReqItem()" style="width:100%; padding:11px 16px; border:1.5px dashed #3b82f6; background:#eff6ff; color:#2563eb; border-radius:10px; font-size:0.86rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.15s ease;" onmouseover="this.style.background='#dbeafe';" onmouseout="this.style.background='#eff6ff';">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                      Tambah Kualifikasi Lainnya
                    </button>
                  </div>
                </div>

                <div class="gig-note" style="margin-top: 10px;">Setiap lowongan secara otomatis hanya dapat merekrut <strong>1 orang pelamar</strong>.</div>
              </section>

            </div>
            <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
              <button type="button" class="btn-secondary" onclick="closePostProjectModal()">Batal</button>
              <div style="display:flex;align-items:center;gap:10px;">
                <button type="button" class="btn-secondary" id="projPrevBtn" onclick="prevProjectStep()" style="display:none;">&#8592; Sebelumnya</button>
                <button type="button" class="btn-primary" id="projNextBtn" onclick="nextProjectStep()">Selanjutnya &#8594;</button>
                <button type="submit" class="btn-primary" id="projSubmitBtn" style="display:none;">Ajukan untuk Verifikasi &#8594;</button>
              </div>
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

  var currentProjectStep = 1;

  function setProjectStep(step) {
    currentProjectStep = step;
    document.querySelectorAll('#newProjectForm .gig-step-panel').forEach(function (panel) {
      var panelStep = Number(panel.getAttribute('data-step'));
      panel.classList.toggle('active', panelStep === step);
    });
    document.querySelectorAll('#postProjectModal .gig-step-item').forEach(function (item) {
      var itemStep = Number(item.getAttribute('data-step'));
      var dot = item.querySelector('.gig-step-dot');
      item.classList.remove('active', 'completed');
      if (itemStep < step) {
        item.classList.add('completed');
        if (dot) dot.textContent = '✓';
      } else if (itemStep === step) {
        item.classList.add('active');
        if (dot) dot.textContent = String(itemStep);
      } else {
        if (dot) dot.textContent = String(itemStep);
      }
    });
    var prevBtn = document.getElementById('projPrevBtn');
    var nextBtn = document.getElementById('projNextBtn');
    var submitBtn = document.getElementById('projSubmitBtn');
    if (prevBtn) prevBtn.style.display = (step > 1) ? 'inline-flex' : 'none';
    if (nextBtn) nextBtn.style.display = (step < 3) ? 'inline-flex' : 'none';
    if (submitBtn) submitBtn.style.display = (step === 3) ? 'inline-flex' : 'none';

    var body = document.getElementById('postProjectModalBody');
    if (body) body.scrollTop = 0;
  }

  function validateProjectStep(step) {
    var panel = document.querySelector('#newProjectForm .gig-step-panel[data-step="' + step + '"]');
    if (!panel) return true;

    var requiredControls = panel.querySelectorAll('[required]');
    for (var i = 0; i < requiredControls.length; i++) {
      var ctrl = requiredControls[i];
      ctrl.setCustomValidity('');
      if (ctrl.type !== 'checkbox' && ctrl.type !== 'radio' && String(ctrl.value || '').trim() === '') {
        ctrl.setCustomValidity('Bagian ini wajib diisi.');
        ctrl.addEventListener('input', function clearValidity() {
          this.setCustomValidity('');
          this.removeEventListener('input', clearValidity);
        });
        ctrl.reportValidity();
        ctrl.focus();
        showToast('Lengkapi semua kolom wajib (*) pada bagian ini sebelum melanjutkan.');
        return false;
      }
      if (!ctrl.checkValidity()) {
        ctrl.reportValidity();
        ctrl.focus();
        return false;
      }
    }

    if (step === 1) {
      var durationInput = document.getElementById('proj_duration_value');
      var durationValue = Number(durationInput ? durationInput.value : 0);
      if (!durationValue || durationValue < 1) {
        if (durationInput) durationInput.focus();
        showToast('Durasi pengerjaan harus diisi minimal 1.');
        return false;
      }
      var deadlineInput = document.getElementById('proj_deadline');
      if (!deadlineInput || !deadlineInput.value) {
        if (deadlineInput) deadlineInput.focus();
        showToast('Pilih batas waktu lamaran terlebih dahulu.');
        return false;
      }
      var salaryInput = document.getElementById('proj_salary');
      var salaryRaw = salaryInput ? salaryInput.value : '';
      var salaryVal = Number((salaryRaw + '').replace(/[^\d]/g, ''));
      if (!salaryVal || salaryVal < 10000) {
        if (salaryInput) salaryInput.focus();
        showToast('Nominal imbalan belum valid. Masukkan nominal minimal Rp 10.000.');
        return false;
      }
      var province = (document.getElementById('proj_province').value || '').trim();
      var city = (document.getElementById('proj_city').value || '').trim();
      if (!province || !city) {
        showToast('Lokasi wajib diisi: provinsi dan kota/kabupaten.');
        return false;
      }
    } else if (step === 2) {
      var skillsInput = document.getElementById('proj_skills');
      if (!skillsInput || !skillsInput.value.trim()) {
        if (skillsInput) skillsInput.focus();
        showToast('Skill wajib harus diisi terlebih dahulu.');
        return false;
      }
    } else if (step === 3) {
      var toggle = document.getElementById('proj_special_req_toggle');
      if (toggle && toggle.checked) {
        var items = document.querySelectorAll('.proj-special-req-item');
        if (items.length === 0) {
          showToast('Tambahkan minimal 1 kualifikasi khusus.');
          return false;
        }
        for (var i = 0; i < items.length; i++) {
          var item = items[i];
          var reqTypeSelect = item.querySelector('.item-req-type');
          var customTypeInput = item.querySelector('.item-req-custom-type');
          var reqLabelInput = item.querySelector('.item-req-label');
          var itemNum = i + 1;
          if (reqTypeSelect && reqTypeSelect.value === 'other' && customTypeInput && !customTypeInput.value.trim()) {
            customTypeInput.focus();
            showToast('Tuliskan nama jenis kualifikasi khusus (lainnya) pada Kualifikasi #' + itemNum + '.');
            return false;
          }
          if (reqLabelInput && !reqLabelInput.value.trim()) {
            reqLabelInput.focus();
            showToast('Tuliskan detail / nama kualifikasi khusus pada Kualifikasi #' + itemNum + '.');
            return false;
          }
        }
      }
    }
    return true;
  }

  window.GIG_SPECIAL_REQ_TYPES = <?php echo json_encode($specialReqTypes, JSON_UNESCAPED_UNICODE); ?>;

  function updateSpecialReqNumbers() {
    var items = document.querySelectorAll('.proj-special-req-item');
    items.forEach(function(item, idx) {
      var badge = item.querySelector('.req-idx-badge');
      var numText = item.querySelector('.req-idx-num');
      var removeBtn = item.querySelector('.btn-remove-req');
      if (badge) badge.textContent = (idx + 1);
      if (numText) numText.textContent = '#' + (idx + 1);
      if (removeBtn) {
        removeBtn.style.display = (items.length > 1) ? 'inline-flex' : 'none';
      }
    });
  }

  function addSpecialReqItem(data) {
    var list = document.getElementById('projSpecialReqItemsList');
    if (!list) return;
    var index = list.children.length;

    var types = window.GIG_SPECIAL_REQ_TYPES || {};
    var typeOptionsHtml = '';
    for (var k in types) {
      if (types.hasOwnProperty(k)) {
        var m = types[k];
        typeOptionsHtml += '<option value="' + escapeHtml(k) + '" data-format="' + escapeHtml(m.format_hint || '') + '" data-placeholder="' + escapeHtml(m.placeholder || '') + '" data-input-mode="' + escapeHtml(m.input_mode || 'file') + '">' + escapeHtml(m.label || k) + '</option>';
      }
    }

    var div = document.createElement('div');
    div.className = 'proj-special-req-item';
    div.style.cssText = 'background:#ffffff; border:1px solid #cbd5e1; border-radius:12px; padding:16px; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,0.03);';
    div.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
        <span style="font-size:0.84rem; font-weight:800; color:#2563eb; display:flex; align-items:center; gap:6px;">
          <span class="req-idx-badge" style="width:20px; height:20px; border-radius:50%; background:#eff6ff; color:#2563eb; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:800;">${index + 1}</span>
          Kualifikasi Khusus <span class="req-idx-num">#${index + 1}</span>
        </span>
        <button type="button" class="btn-remove-req" onclick="removeSpecialReqItem(this)" style="background:none; border:none; color:#ef4444; font-size:0.78rem; font-weight:700; cursor:pointer; display:${index > 0 ? 'inline-flex' : 'none'}; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; transition:background 0.15s;" onmouseover="this.style.background='#fef2f2';" onmouseout="this.style.background='none';" title="Hapus syarat ini">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Hapus
        </button>
      </div>

      <div class="form-row" style="margin-bottom: 12px;">
        <label>Jenis Kualifikasi Khusus *</label>
        <select class="item-req-type" onchange="syncSpecialReqItemUI(this)">
          ${typeOptionsHtml}
        </select>
      </div>

      <div class="form-row item-custom-type-row" style="display: none; margin-bottom: 12px;">
        <label>Nama Jenis Kualifikasi (Lainnya) *</label>
        <input type="text" class="item-req-custom-type" placeholder="Contoh: Surat Izin Mengemudi (SIM B2) / SKCK / Sertifikat TOEFL" />
        <div class="gig-note" style="margin-top: 3px;">Tuliskan nama kategori atau jenis kualifikasi yang belum tertera pada pilihan di atas.</div>
      </div>

      <div class="form-row" style="margin-bottom: 12px;">
        <label>Detail / Nama Kualifikasi yang Diminta *</label>
        <input type="text" class="item-req-label" placeholder="Contoh: Sertifikat AWS Cloud Practitioner / BNSP" />
      </div>

      <div class="form-row" style="margin-bottom: 12px;">
        <label>Tipe Input / Format Berkas untuk Pelamar *</label>
        <select class="item-req-input-type" onchange="syncSpecialReqItemInputType(this)">
          <option value="document" data-mode="file" data-accept=".pdf,.doc,.docx" data-hint="Dokumen PDF, DOC, DOCX (maks. 2 MB)">Dokumen (PDF, DOC, DOCX)</option>
          <option value="image" data-mode="file" data-accept=".jpg,.jpeg,.png" data-hint="Gambar JPG, JPEG, PNG (maks. 2 MB)">Gambar (JPG, JPEG, PNG)</option>
          <option value="archive" data-mode="file" data-accept=".zip,.rar,.7z" data-hint="Berkas Kompresi ZIP, RAR, 7Z (maks. 2 MB)">Berkas Kompresi (ZIP, RAR, 7Z)</option>
          <option value="url" data-mode="url" data-accept="" data-hint="Link / URL">Link / URL</option>
          <option value="text" data-mode="text" data-accept="" data-hint="Text">Text</option>
        </select>
        <div class="gig-note" style="margin-top: 3px;">Pilih format berkas atau tipe input yang wajib disediakan oleh pelamar saat melamar lowongan ini.</div>
      </div>

      <div class="item-format-preview-bar" style="font-size: 0.78rem; color: #0369a1; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 8px 10px; display: flex; align-items: center; gap: 6px; margin-bottom: 12px;">
        <span>&#128206;</span>
        <span>Format input untuk Pelamar: <strong class="item-format-preview-text">Dokumen PDF, DOC, DOCX (maks. 2 MB)</strong></span>
      </div>

      <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; margin: 0;">
          <input type="checkbox" class="item-req-is-public" checked style="margin-top: 2px; width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer;" />
          <span style="font-size: 0.83rem; line-height: 1.45; color: #1e293b;">
            <strong style="font-weight: 700;">Tampilkan kualifikasi khusus</strong>
            <span style="color: #64748b; margin-left: 4px;">(Kualifikasi khusus ini akan ditampilkan secara publik pada halaman rincian lowongan agar calon pelamar dapat mengetahuinya sebelum melamar.)</span>
          </span>
        </label>
      </div>
    `;

    list.appendChild(div);
    syncSpecialReqItemUI(div.querySelector('.item-req-type'));
    updateSpecialReqNumbers();
  }

  function removeSpecialReqItem(btn) {
    var item = btn.closest('.proj-special-req-item');
    if (item) {
      item.remove();
      updateSpecialReqNumbers();
    }
  }

  function syncSpecialReqItemInputType(sel) {
    var item = sel.closest('.proj-special-req-item');
    if (!item) return;
    var preview = item.querySelector('.item-format-preview-text');
    var opt = sel.options[sel.selectedIndex];
    if (preview && opt) {
      preview.textContent = opt.getAttribute('data-hint') || 'Upload File';
    }
  }

  function syncSpecialReqItemUI(sel) {
    var item = sel.closest('.proj-special-req-item');
    if (!item) return;
    var customRow = item.querySelector('.item-custom-type-row');
    var labelInput = item.querySelector('.item-req-label');
    var inputTypeSel = item.querySelector('.item-req-input-type');
    if (customRow) {
      customRow.style.display = (sel.value === 'other') ? 'block' : 'none';
    }
    var opt = sel.options[sel.selectedIndex];
    if (!opt) return;
    var placeholder = opt.getAttribute('data-placeholder') || '';
    var defaultInputMode = opt.getAttribute('data-input-mode') || 'file';

    if (inputTypeSel) {
      if (defaultInputMode === 'url') {
        inputTypeSel.value = 'url';
      } else if (defaultInputMode === 'textarea' || defaultInputMode === 'text') {
        inputTypeSel.value = 'text';
      } else {
        if (!inputTypeSel.value || !['document', 'image', 'archive', 'pdf', 'doc', 'jpg', 'png'].includes(inputTypeSel.value)) {
          inputTypeSel.value = 'document';
        }
      }
    }

    if (labelInput && placeholder && !labelInput.value) {
      labelInput.placeholder = placeholder;
    }
    syncSpecialReqItemInputType(inputTypeSel);
  }

  function handleOptionalHeadClick(e) {
    if (e.target.closest('.gig-switch')) return;
    var toggle = document.getElementById('proj_special_req_toggle');
    if (toggle) {
      toggle.checked = !toggle.checked;
      toggleSpecialReq(toggle.checked);
    }
  }

  function toggleSpecialReq(enabled) {
    var card = document.getElementById('projSpecialReqCard');
    var body = document.getElementById('projSpecialReqBody');
    var statusText = document.getElementById('projSpecialReqSwitchStatus');
    var toggle = document.getElementById('proj_special_req_toggle');
    var list = document.getElementById('projSpecialReqItemsList');
    if (toggle) toggle.checked = !!enabled;
    if (card) card.classList.toggle('active', !!enabled);
    if (statusText) statusText.textContent = enabled ? 'Aktif' : 'Tidak Aktif';
    if (body) body.style.display = enabled ? 'block' : 'none';
    if (enabled && list && list.children.length === 0) {
      addSpecialReqItem();
    }
  }

  function nextProjectStep() {
    if (!validateProjectStep(currentProjectStep)) return;
    if (currentProjectStep < 3) {
      setProjectStep(currentProjectStep + 1);
    }
  }

  function prevProjectStep() {
    if (currentProjectStep > 1) {
      setProjectStep(currentProjectStep - 1);
    }
  }

  function goToProjectStep(targetStep) {
    if (targetStep === currentProjectStep) return;
    if (targetStep < currentProjectStep) {
      setProjectStep(targetStep);
      return;
    }
    for (var s = currentProjectStep; s < targetStep; s++) {
      if (!validateProjectStep(s)) {
        if (s !== currentProjectStep) setProjectStep(s);
        return;
      }
    }
    setProjectStep(targetStep);
  }

  function openPostProjectModal() {
    setProjectStep(1);
    var list = document.getElementById('projSpecialReqItemsList');
    if (list) list.innerHTML = '';
    toggleSpecialReq(false);
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
    if (currentProjectStep < 3) {
      nextProjectStep();
      return;
    }
    for (var s = 1; s <= 3; s++) {
      if (!validateProjectStep(s)) {
        if (s !== currentProjectStep) {
          setProjectStep(s);
          validateProjectStep(s);
        }
        return;
      }
    }
    const title = document.getElementById('proj_title').value;
    const locRadio = document.querySelector('input[name="proj_lokasi_type"]:checked');
    const durationValue = Number(document.getElementById('proj_duration_value').value || 0);
    const durationUnit = document.getElementById('proj_duration_unit').value || 'Minggu';
    const durationLabel = String(durationValue) + ' ' + String(durationUnit);
    const province = (document.getElementById('proj_province').value || '').trim();
    const city = (document.getElementById('proj_city').value || '').trim();
    const salaryRaw = document.getElementById('proj_salary').value || '';
    const salaryVal = Number((salaryRaw + '').replace(/[^\d]/g, ''));

    var toggleEl = document.getElementById('proj_special_req_toggle');
    var specialRequirements = [];
    if (toggleEl && toggleEl.checked) {
      var items = document.querySelectorAll('.proj-special-req-item');
      items.forEach(function(item) {
        var reqTypeEl = item.querySelector('.item-req-type');
        var customTypeEl = item.querySelector('.item-req-custom-type');
        var reqLabelEl = item.querySelector('.item-req-label');
        var reqInputTypeEl = item.querySelector('.item-req-input-type');
        var isPublicEl = item.querySelector('.item-req-is-public');
        if (reqTypeEl && reqLabelEl && reqLabelEl.value.trim() !== '') {
          var chosenOpt = reqInputTypeEl ? reqInputTypeEl.options[reqInputTypeEl.selectedIndex] : null;
          var chosenMode = chosenOpt ? chosenOpt.getAttribute('data-mode') : 'file';
          var chosenAccept = chosenOpt ? chosenOpt.getAttribute('data-accept') : '.pdf';
          var chosenHint = chosenOpt ? chosenOpt.getAttribute('data-hint') : (chosenOpt ? chosenOpt.text : 'PDF');
          var chosenVal = reqInputTypeEl ? reqInputTypeEl.value : 'document';
          var isPublic = isPublicEl ? isPublicEl.checked : true;

          specialRequirements.push({
            type: reqTypeEl.value,
            custom_type: (reqTypeEl.value === 'other' && customTypeEl) ? customTypeEl.value.trim() : '',
            label: reqLabelEl.value.trim(),
            input_type: chosenVal,
            input_mode: chosenMode,
            accept: chosenAccept,
            format_hint: chosenHint,
            is_public: isPublic
          });
        }
      });
    }

    var specialRequirement = specialRequirements.length > 0 ? specialRequirements[0] : null;

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
      location_province: province,
      special_requirement: specialRequirement,
      special_requirements: specialRequirements.length > 0 ? specialRequirements : null
    };
    fetch('vacancy-submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); }).then(function (data) {
      closePostProjectModal();
      document.getElementById('newProjectForm').reset();
      toggleSpecialReq(false);
      setProjectStep(1);
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
