{{-- Shared Dashboard Widget Styles --}}
<style>
    .dash-widget {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        min-height: 220px;
    }
    .dash-widget:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,.1);
    }
    .dash-widget .widget-header {
        padding: 20px 22px 14px;
        position: relative;
        overflow: hidden;
    }
    .dash-widget .widget-header::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,.12);
    }
    .dash-widget .widget-header::after {
        content: '';
        position: absolute;
        bottom: -20px;
        right: 40px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
    }
    .dash-widget .widget-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        background: rgba(255,255,255,.22);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        backdrop-filter: blur(4px);
    }
    .dash-widget .widget-icon i {
        font-size: 22px;
        color: #fff;
    }
    .dash-widget .widget-number {
        font-size: 32px;
        font-weight: 800;
        color: #fff;
        line-height: 1;
        letter-spacing: -0.5px;
    }
    .dash-widget .widget-label {
        font-size: 12px;
        color: rgba(255,255,255,.85);
        font-weight: 500;
    }
    .dash-widget .widget-body {
        padding: 14px 22px 18px;
        background: #fff;
    }
    .dash-widget .breakdown-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    .dash-widget .breakdown-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12.5px;
        color: #334155;
    }
    .dash-widget .breakdown-row:last-child {
        border-bottom: none;
    }
    .dash-widget .breakdown-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        color: #fff;
    }
    .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }
    .section-header .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .section-header h6 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #475569;
        margin: 0;
    }
    .dash-widget .widget-filter {
        position: relative;
        z-index: 1;
    }
    .dash-widget .widget-filter select {
        background: rgba(255,255,255,.2);
        border: 1px solid rgba(255,255,255,.3);
        color: #fff;
        font-size: 11px;
        border-radius: 8px;
        padding: 4px 24px 4px 10px;
        backdrop-filter: blur(4px);
        cursor: pointer;
    }
    .dash-widget .widget-filter select option {
        color: #334155;
        background: #fff;
    }
    .dash-widget .status-pills {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .dash-widget .status-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        backdrop-filter: blur(4px);
    }
</style>
