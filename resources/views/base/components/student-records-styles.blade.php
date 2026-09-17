<style>
    .student-records .record-metric { display: flex; min-height: 108px; height: 100%; align-items: center; gap: 14px; padding: 18px; border: 1px solid var(--dash-line); border-radius: 14px; background: #fff; box-shadow: 0 7px 20px rgba(12, 44, 55, .05); }
    .student-records .record-metric__icon { display: grid; width: 46px; height: 46px; flex: 0 0 46px; place-items: center; border-radius: 12px; color: var(--dash-green); background: var(--dash-green-soft); font-size: 19px; }
    .student-records .record-metric__icon.is-gold { color: #9a6b12; background: #fff3d7; }
    .student-records .record-metric__icon.is-red { color: #bd4d58; background: #faecee; }
    .student-records .record-metric__icon.is-blue { color: #3867a8; background: #edf4ff; }
    .student-records .record-metric > div { min-width: 0; }
    .student-records .record-metric > div > small, .student-records .record-metric > div > span { display: block; overflow: hidden; color: var(--dash-muted); font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
    .student-records .record-metric > div > strong { display: block; margin: 3px 0; color: var(--dash-navy); font-size: 22px; line-height: 1.15; }
    .student-records .record-panel { overflow: hidden; border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 8px 24px rgba(12, 44, 55, .05); }
    .student-records .record-panel > .card-header { padding: 20px 22px 14px; background: #fff; }
    .student-records .record-panel > .card-body { padding: 18px 22px 22px; }
    .student-records .record-filter { margin-bottom: 20px; padding: 17px 18px; border: 1px solid #dbe8e3; border-radius: 13px; background: linear-gradient(135deg, #f7fbfa, #fff); }
    .student-records .record-filter__title { margin: 0 0 3px; color: var(--dash-navy); font-size: 13px; font-weight: 800; }
    .student-records .record-filter__description { margin: 0 0 14px; color: var(--dash-muted); font-size: 11px; }
    .student-records .record-filter .form-label { margin-bottom: 5px; color: var(--dash-navy); font-size: 11px; font-weight: 700; }
    .student-records .record-filter .form-control, .student-records .record-filter .form-select, .student-records .record-filter .input-group-text { min-height: 40px; border-color: #d5e2de; font-size: 12px; }
    .student-records .record-filter .input-group-text { color: var(--dash-green); background: #fff; }
    .student-records .record-filter .input-group .form-control { border-left: 0; }
    .student-records .record-table { margin-bottom: 0; }
    .student-records .record-table thead th { padding: 12px 14px; border-bottom-width: 1px; color: #64726f; background: #f7f9f8; font-size: 10px; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .student-records .record-table tbody td { padding: 15px 14px; vertical-align: middle; color: var(--dash-ink); font-size: 12px; }
    .student-records .record-title { display: block; color: var(--dash-navy); font-size: 13px; font-weight: 750; }
    .student-records .record-subtitle { display: block; margin-top: 3px; color: var(--dash-muted); font-size: 10px; }
    .student-records .record-badge { display: inline-flex; align-items: center; gap: 5px; padding: 6px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; white-space: nowrap; }
    .student-records .record-badge.is-success { color: #13715e; background: #e8f6f1; }
    .student-records .record-badge.is-warning { color: #8d6312; background: #fff3d7; }
    .student-records .record-badge.is-danger { color: #ad3f4b; background: #faecee; }
    .student-records .record-badge.is-info { color: #3867a8; background: #edf4ff; }
    .student-records .record-badge.is-muted { color: #687470; background: #eef2f1; }
    .student-records .record-empty { padding: 44px 18px; color: var(--dash-muted); text-align: center; }
    .student-records .record-empty i { display: block; margin-bottom: 10px; color: #aab8b4; font-size: 28px; }
    .student-records .record-empty strong { display: block; margin-bottom: 4px; color: var(--dash-navy); font-size: 13px; }
    .student-records .record-actions .btn { border-radius: 8px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    @media (max-width: 767.98px) {
        .student-records .record-panel > .card-header, .student-records .record-panel > .card-body { padding-right: 15px; padding-left: 15px; }
        .student-records .record-table thead { display: none; }
        .student-records .record-table, .student-records .record-table tbody, .student-records .record-table tr, .student-records .record-table td { display: block; width: 100%; }
        .student-records .record-table tr { padding: 13px 14px; border-bottom: 1px solid var(--dash-line); }
        .student-records .record-table tr:last-child { border-bottom: 0; }
        .student-records .record-table td { display: flex; justify-content: space-between; gap: 18px; padding: 7px 0; border: 0; text-align: right; }
        .student-records .record-table td::before { flex: 0 0 34%; color: var(--dash-muted); content: attr(data-label); font-size: 10px; font-weight: 700; text-align: left; text-transform: uppercase; }
        .student-records .record-table td.record-primary { display: block; padding-bottom: 10px; text-align: left; }
        .student-records .record-table td.record-primary::before, .student-records .record-table td.record-empty-cell::before { display: none; }
        .student-records .record-actions .btn { width: 100%; }
    }
</style>
