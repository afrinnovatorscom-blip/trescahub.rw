<style>
    .partner-admin {
        --partner-primary: #2563EB;
        --partner-border: #E2E8F0;
        --partner-heading: #0F172A;
        --partner-body: #475569;
        color: var(--partner-body);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        max-width: 1180px;
    }

    .partner-back-link {
        align-items: center;
        color: #64748B;
        display: inline-flex;
        font-size: 13px;
        font-weight: 500;
        gap: 8px;
        text-decoration: none;
    }

    .partner-back-link:hover { color: #2563EB; }
    .partner-page-title { color: var(--partner-heading); font-size: 26px; font-weight: 700; }
    .partner-page-description { color: #64748B; font-size: 14px; }

    .partner-form {
        background: #fff;
        border: 1px solid var(--partner-border);
        border-radius: 12px;
        max-width: 860px;
        overflow: hidden;
    }

    .partner-form-section { padding: 22px 24px; }
    .partner-form-section + .partner-form-section { border-top: 1px solid var(--partner-border); }

    .partner-section-heading {
        align-items: center;
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
    }

    .partner-section-icon {
        align-items: center;
        background: #EFF6FF;
        border-radius: 9px;
        color: #2563EB;
        display: flex;
        flex: 0 0 36px;
        height: 36px;
        justify-content: center;
    }

    .partner-section-heading h2 { color: var(--partner-heading); font-size: 16px; font-weight: 600; margin: 0 0 3px; }
    .partner-section-heading p { color: #64748B; font-size: 12px; margin: 0; }
    .partner-form .form-label { color: var(--partner-heading); font-size: 13px; font-weight: 600; margin-bottom: 6px; }

    .partner-control {
        border-color: var(--partner-border);
        border-radius: 8px;
        color: var(--partner-heading);
        font-size: 13px;
        min-height: 42px;
    }

    .partner-control:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .13);
    }

    textarea.partner-control { min-height: 88px; }
    .partner-help { color: #64748B; font-size: 12px; margin-top: 6px; }

    .partner-logo-field { align-items: center; display: flex; gap: 16px; }
    .partner-logo-preview {
        align-items: center;
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 10px;
        color: #94A3B8;
        display: flex;
        flex: 0 0 76px;
        height: 76px;
        justify-content: center;
        overflow: hidden;
    }

    .partner-logo-preview img { height: 100%; object-fit: contain; padding: 8px; width: 100%; }
    .partner-logo-preview span { font-size: 24px; }
    .partner-logo-field .partner-control { background: #fff; }
    .partner-visibility-control { align-items: center; display: flex; justify-content: space-between; min-height: 56px; }
    .partner-visibility-control .form-check-input { cursor: pointer; height: 20px; margin: 0; width: 38px; }
    .partner-visibility-control .form-check-input:checked { background-color: #2563EB; border-color: #2563EB; }

    .partner-form-actions {
        align-items: center;
        background: #F8FAFC;
        border-top: 1px solid var(--partner-border);
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 16px 24px;
    }

    .partner-btn-primary, .partner-btn-secondary {
        align-items: center;
        border-radius: 8px;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        gap: 7px;
        justify-content: center;
        min-height: 40px;
        padding: 0 16px;
    }

    .partner-btn-primary { background: #2563EB; border-color: #2563EB; color: #fff; }
    .partner-btn-primary:hover, .partner-btn-primary:focus-visible { background: #1D4ED8; border-color: #1D4ED8; color: #fff; }
    .partner-btn-secondary { background: #fff; border: 1px solid #CBD5E1; color: #475569; }
    .partner-btn-secondary:hover, .partner-btn-secondary:focus-visible { background: #F1F5F9; color: #0F172A; }

    @media (max-width: 575.98px) {
        .partner-form-section { padding: 18px 16px; }
        .partner-form-actions { padding: 14px 16px; }
        .partner-logo-field { align-items: flex-start; }
        .partner-logo-preview { flex-basis: 60px; height: 60px; }
        .partner-form-actions .btn { flex: 1; }
    }
</style>
