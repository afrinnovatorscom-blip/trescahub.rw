<style>
    .partner-admin {
        --partner-primary: #2563EB;
        --partner-border: #E2E8F0;
        --partner-heading: #0F172A;
        --partner-body: #475569;
        color: var(--partner-body);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        max-width: 1440px;
    }

    .partner-page-title { color: var(--partner-heading); font-size: 26px; font-weight: 700; }
    .partner-page-description { color: #64748B; font-size: 14px; }

    .partner-btn-primary {
        align-items: center;
        background: #2563EB;
        border: 1px solid #2563EB;
        border-radius: 8px;
        color: #fff;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        gap: 7px;
        justify-content: center;
        min-height: 40px;
        padding: 0 16px;
        text-decoration: none;
    }

    .partner-btn-primary:hover, .partner-btn-primary:focus-visible { background: #1D4ED8; border-color: #1D4ED8; color: #fff; }
    .partner-alert { border-radius: 9px; font-size: 13px; }

    .partner-list-card { background: #fff; border: 1px solid var(--partner-border); border-radius: 11px; overflow: hidden; }
    .partner-list-meta { align-items: center; border-bottom: 1px solid var(--partner-border); color: #0F172A; display: flex; font-size: 14px; font-weight: 600; justify-content: space-between; padding: 16px 20px; }
    .partner-count { color: #64748B; font-size: 12px; font-weight: 500; }
    .partner-table { min-width: 760px; }
    .partner-table thead th { background: #F8FAFC; border-bottom: 1px solid var(--partner-border); color: #64748B; font-size: 11px; font-weight: 600; padding: 12px 16px; }
    .partner-table tbody td { border-bottom-color: #F1F5F9; color: #475569; font-size: 13px; padding: 13px 16px; }
    .partner-table tbody tr:last-child td { border-bottom: 0; }
    .partner-table tbody tr:hover { background: #FAFCFF; }
    .partner-list-logo { align-items: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 9px; display: inline-flex; flex: 0 0 44px; height: 44px; justify-content: center; object-fit: contain; padding: 4px; width: 44px; }
    .partner-list-logo-placeholder { color: #2563EB; font-size: 16px; font-weight: 700; }
    .partner-list-name { color: #0F172A; font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .partner-list-description { color: #64748B; font-size: 12px; margin-top: 3px; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .partner-website-link { color: #2563EB; font-size: 12px; text-decoration: none; }
    .partner-website-link:hover { color: #1D4ED8; text-decoration: underline; }
    .partner-muted { color: #94A3B8; }
    .partner-badge { align-items: center; border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 600; gap: 6px; padding: 5px 9px; white-space: nowrap; }
    .partner-badge-visible, .partner-badge-active { background: #F0FDF4; color: #15803D; }
    .partner-badge-hidden, .partner-badge-inactive { background: #F1F5F9; color: #64748B; }
    .partner-status-dot { background: currentColor; border-radius: 50%; height: 6px; width: 6px; }
    .partner-action-link { align-items: center; background: #fff; border: 1px solid #E2E8F0; border-radius: 7px; color: #475569; display: inline-flex; font-size: 12px; gap: 6px; padding: 6px 9px; text-decoration: none; }
    .partner-action-link:hover { background: #EFF6FF; border-color: #BFDBFE; color: #2563EB; }
    .partner-pagination { border-top: 1px solid var(--partner-border); padding: 14px 18px; }
    .partner-pagination nav { display: flex; justify-content: flex-end; }
    .partner-empty-state { align-items: center; background: #fff; border: 1px solid var(--partner-border); border-radius: 11px; display: flex; flex-direction: column; padding: 56px 20px; text-align: center; }
    .partner-empty-icon { align-items: center; background: #EFF6FF; border-radius: 14px; color: #2563EB; display: flex; font-size: 24px; height: 54px; justify-content: center; margin-bottom: 16px; width: 54px; }
    .partner-empty-state h2 { color: #0F172A; font-size: 17px; font-weight: 600; margin-bottom: 6px; }
    .partner-empty-state p { color: #64748B; font-size: 13px; margin-bottom: 18px; }

    @media (max-width: 575.98px) {
        .partner-list-meta { padding: 14px; }
        .partner-table tbody td, .partner-table thead th { padding-left: 12px; padding-right: 12px; }
    }
</style>
