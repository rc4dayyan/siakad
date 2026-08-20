<style>
    .professional-dashboard .dashboard-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
        overflow: hidden;
        padding: 30px;
        border-radius: 18px;
        color: #fff;
        background: radial-gradient(circle at 86% 18%, rgba(17, 122, 101, .38), transparent 31%), linear-gradient(125deg, var(--dash-navy), var(--dash-navy-soft));
        box-shadow: 0 18px 40px rgba(11, 33, 53, .16);
    }
    .professional-dashboard .dashboard-hero::after {
        position: absolute;
        top: -90px;
        right: -55px;
        width: 270px;
        height: 270px;
        border: 44px solid rgba(255, 255, 255, .06);
        border-radius: 50%;
        content: '';
    }
    .dashboard-hero__content, .dashboard-hero__aside { position: relative; z-index: 1; }
    .dashboard-hero__content { max-width: 680px; }
    .dashboard-hero__eyebrow { display: block; margin-bottom: 8px; color: #bfe3d9; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .dashboard-hero h2 { margin-bottom: 8px; color: #fff; font-size: clamp(24px, 3vw, 34px); }
    .dashboard-hero p { max-width: 600px; margin: 0; color: rgba(255, 255, 255, .78); }
    .dashboard-hero .btn { border-radius: 9px; font-weight: 700; }
    .dashboard-hero__aside { display: flex; min-width: 245px; flex-direction: column; padding: 18px 20px; border: 1px solid rgba(255, 255, 255, .16); border-radius: 14px; background: rgba(255, 255, 255, .1); backdrop-filter: blur(8px); }
    .dashboard-hero__aside small { color: rgba(255, 255, 255, .65); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .dashboard-hero__aside strong { margin: 5px 0 2px; color: #fff; font-size: 17px; }
    .dashboard-hero__aside span { color: rgba(255, 255, 255, .76); font-size: 12px; }

    .dashboard-metric {
        display: flex;
        min-height: 118px;
        height: 100%;
        align-items: center;
        gap: 14px;
        padding: 20px;
        border: 1px solid var(--dash-line);
        border-radius: 14px;
        background: var(--dash-surface);
        box-shadow: 0 7px 20px rgba(12, 44, 55, .05);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    a.dashboard-metric:hover { border-color: rgba(17, 122, 101, .3); transform: translateY(-2px); box-shadow: 0 12px 28px rgba(11, 66, 68, .1); }
    .dashboard-metric__icon { display: grid; width: 48px; height: 48px; flex: 0 0 48px; place-items: center; border-radius: 12px; color: var(--dash-green); background: var(--dash-green-soft); font-size: 20px; }
    .dashboard-metric__icon.is-gold { color: #9a6b12; background: #fff3d7; }
    .dashboard-metric__icon.is-red { color: #bd4d58; background: #faecee; }
    .dashboard-metric__content { min-width: 0; }
    .dashboard-metric small, .dashboard-metric em { display: block; overflow: hidden; color: var(--dash-muted); font-size: 11px; font-style: normal; text-overflow: ellipsis; white-space: nowrap; }
    .dashboard-metric strong { display: block; margin: 3px 0; color: var(--dash-navy); font-size: 24px; line-height: 1.15; }

    .dashboard-panel { height: 100%; margin-bottom: 0; border: 1px solid var(--dash-line); border-radius: 15px; box-shadow: 0 7px 20px rgba(12, 44, 55, .05); }
    .dashboard-panel .card-header { padding: 21px 22px 12px; background: transparent; }
    .dashboard-panel .card-body { padding: 16px 22px 22px; }
    .dashboard-list-item { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 14px 2px; border-bottom: 1px solid var(--dash-line); color: var(--dash-ink); }
    .dashboard-list-item:last-child { border-bottom: 0; }
    .dashboard-list-item__icon { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 10px; color: var(--dash-green); background: var(--dash-green-soft); }
    .dashboard-list-item__icon.is-gold { color: #9a6b12; background: #fff3d7; }
    .dashboard-list-item__icon.is-red { color: #bd4d58; background: #faecee; }
    .dashboard-list-item strong, .dashboard-list-item small { display: block; }
    .dashboard-list-item strong { overflow: hidden; font-size: 13px; text-overflow: ellipsis; white-space: nowrap; }
    .dashboard-list-item small { margin-top: 2px; color: var(--dash-muted); font-size: 11px; }
    .dashboard-list-item > i { color: #a0a8b1; font-size: 11px; }
    .dashboard-empty { padding: 26px 12px; color: var(--dash-muted); text-align: center; }
    .dashboard-empty i { display: block; margin-bottom: 10px; color: #aab8b4; font-size: 26px; }

    .dashboard-action-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .dashboard-action-grid.is-six { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .dashboard-action-grid a { display: flex; min-height: 92px; flex-direction: column; align-items: center; justify-content: center; gap: 9px; padding: 14px; border: 1px solid var(--dash-line); border-radius: 12px; color: var(--dash-muted); text-align: center; transition: color .18s ease, border-color .18s ease, background .18s ease; }
    .dashboard-action-grid a:hover { color: var(--dash-green-dark); border-color: rgba(17, 122, 101, .3); background: var(--dash-green-soft); }
    .dashboard-action-grid i { color: var(--dash-green); font-size: 20px; }
    .dashboard-action-grid span { font-size: 12px; font-weight: 700; }
    .dashboard-status { display: flex; align-items: center; gap: 8px; color: var(--dash-muted); font-size: 12px; }
    .dashboard-status__dot { width: 8px; height: 8px; border-radius: 50%; background: var(--dash-green); }

    @media (max-width: 991.98px) {
        .professional-dashboard .dashboard-hero { align-items: stretch; flex-direction: column; }
        .dashboard-hero__aside { width: 100%; min-width: 0; }
        .dashboard-action-grid.is-six { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .dashboard-action-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dashboard-action-grid.is-six { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .professional-dashboard .dashboard-hero { padding: 24px 20px; }
        .dashboard-panel .card-header, .dashboard-panel .card-body { padding-right: 16px; padding-left: 16px; }
        .dashboard-metric { min-height: 105px; padding: 16px; }
    }
</style>
