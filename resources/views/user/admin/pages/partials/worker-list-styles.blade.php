<style>
    .worker-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .worker-summary__item { display: flex; align-items: center; gap: 14px; min-height: 92px; padding: 18px; border: 1px solid #e8ecef; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(37, 50, 55, .05); }
    .worker-summary__icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; flex: 0 0 46px; border-radius: 12px; font-size: 18px; }
    .worker-summary__icon--total { background: #eef2ff; color: #435ebe; }
    .worker-summary__icon--active { background: #eaf8f2; color: #198754; }
    .worker-summary__icon--inactive { background: #fff0f1; color: #dc3545; }
    .worker-summary__label { display: block; margin-bottom: 2px; color: #7b8794; font-size: 12px; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
    .worker-summary__value { margin: 0; color: #263238; font-size: 24px; font-weight: 700; line-height: 1.2; }
    .worker-filter { margin-bottom: 20px; padding: 20px; border: 1px solid #dfe7e4; border-radius: 14px; background: linear-gradient(135deg, #f5faf8 0%, #fff 72%); }
    .worker-filter__header { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
    .worker-filter__title { margin: 0 0 3px; color: #263d36; font-size: 15px; font-weight: 700; }
    .worker-filter__description { margin: 0; color: #71837d; font-size: 12px; }
    .worker-filter__result { display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border-radius: 999px; background: #e8f5f0; color: #176b55; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .worker-filter .form-label { margin-bottom: 6px; color: #46534f; font-size: 12px; font-weight: 700; }
    .worker-filter .form-control, .worker-filter .form-select { min-height: 42px; border-color: #dce4e1; border-radius: 9px; }
    .worker-filter .input-group-text { border-color: #dce4e1; border-radius: 9px 0 0 9px; background: #fff; color: #87938f; }
    .worker-filter__buttons { display: flex; gap: 8px; }
    .worker-filter__buttons .btn { min-height: 42px; border-radius: 9px; white-space: nowrap; }
    .worker-card { overflow: hidden; border: 0; border-radius: 14px; box-shadow: 0 7px 24px rgba(37, 50, 55, .07); }
    .worker-card .card-header { padding: 20px 22px; border-bottom: 1px solid #edf0f2; background: #fff; }
    .worker-card__title { margin: 0 0 4px; color: #263238; font-size: 18px; font-weight: 700; }
    .worker-card__description { margin: 0; color: #7b8794; font-size: 13px; }
    .worker-card .card-body { padding: 8px 22px 22px; }
    .worker-table th { padding-top: 15px; padding-bottom: 15px; border-bottom-width: 1px !important; color: #66727d; font-size: 11px; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
    .worker-table td { padding-top: 14px; padding-bottom: 14px; vertical-align: middle; }
    .worker-profile { display: flex; align-items: center; gap: 12px; min-width: 205px; }
    .worker-profile__avatar { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; background: linear-gradient(135deg, #435ebe, #6f85db); color: #fff; font-size: 15px; font-weight: 700; }
    .worker-profile__name { display: block; margin-bottom: 2px; color: #263238; font-size: 14px; font-weight: 700; }
    .worker-profile__username, .worker-contact { color: #7b8794; font-size: 12px; }
    .worker-contact span { display: block; max-width: 210px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .worker-contact i { width: 16px; color: #9aa4ad; text-align: center; }
    .worker-status { display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .worker-status::before { width: 7px; height: 7px; border-radius: 50%; background: currentColor; content: ''; }
    .worker-status--active { background: #eaf8f2; color: #198754; }
    .worker-status--inactive { background: #fff0f1; color: #dc3545; }
    .worker-action-dropdown .dropdown-toggle { min-width: 88px; border-radius: 9px; font-weight: 600; }
    .worker-action-dropdown .dropdown-menu { min-width: 185px; padding: 7px; border: 1px solid #e6ece9; border-radius: 10px; box-shadow: 0 10px 28px rgba(38, 61, 54, .14); }
    .worker-action-dropdown .dropdown-item { display: flex; align-items: center; gap: 9px; padding: 9px 11px; border: 0; border-radius: 7px; background: transparent; font-size: 13px; }
    .worker-action-dropdown .dropdown-item:hover { background: #f4f7f6; }
    .worker-action-dropdown .dropdown-item i { width: 16px; text-align: center; }
    .worker-contact-box { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid #e8ecef; border-radius: 11px; background: #fafbfc; }
    .worker-contact-box__icon { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 10px; background: #eef2ff; color: #435ebe; }
    .worker-contact-box__content { min-width: 0; flex: 1; }
    .worker-contact-box__content small, .worker-contact-box__content strong { display: block; }
    .worker-contact-box__content small { color: #7b8794; }
    .worker-contact-box__content strong { overflow: hidden; color: #263238; font-size: 14px; text-overflow: ellipsis; white-space: nowrap; }
    @media (max-width: 767.98px) {
        .worker-summary { grid-template-columns: 1fr; gap: 10px; }
        .worker-summary__item { min-height: 76px; padding: 14px; }
        .worker-filter__header { align-items: flex-start; flex-direction: column; }
        .worker-filter__buttons, .worker-filter__buttons .btn { width: 100%; }
        .worker-card .card-header { align-items: flex-start !important; flex-direction: column; }
        .worker-card__actions { width: 100%; }
        .worker-card__actions .btn { flex: 1; }
        .worker-card .card-body { padding-right: 14px; padding-left: 14px; }
    }
</style>
