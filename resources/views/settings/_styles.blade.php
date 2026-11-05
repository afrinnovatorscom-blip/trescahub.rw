<style>
    .settings-page {
        max-width: 960px;
        margin: 0 auto;
        color: #0f172a;
    }
    .settings-header {
        margin-bottom: 24px;
    }
    .settings-title {
        font-size: 1.55rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
        letter-spacing: -0.02em;
    }
    .settings-subtitle {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 0;
    }
    .settings-back-link {
        color: #64748b;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 16px;
        transition: color 0.15s ease;
    }
    .settings-back-link:hover {
        color: #2563eb;
    }
    .settings-group-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 8px;
        padding-left: 2px;
    }
    .settings-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .settings-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        text-decoration: none;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .settings-row:last-child {
        border-bottom: none;
    }
    .settings-row:hover {
        background-color: #f8fafc;
        color: #0f172a;
    }
    .settings-row:focus-visible {
        background-color: #f8fafc;
        outline: 2px solid #2563eb;
        outline-offset: -2px;
    }
    .settings-row-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .settings-row-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.25;
    }
    .settings-row-desc {
        font-size: 0.82rem;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.35;
    }
    .settings-row-chevron {
        color: #94a3b8;
        font-size: 0.9rem;
        flex-shrink: 0;
        margin-left: 16px;
        transition: transform 0.15s ease, color 0.15s ease;
    }
    .settings-row:hover .settings-row-chevron {
        color: #2563eb;
        transform: translateX(2px);
    }
    .settings-form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        margin-bottom: 24px;
    }
    .settings-card-header {
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .settings-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .settings-card-desc {
        font-size: 0.84rem;
        color: #64748b;
        margin-top: 2px;
        margin-bottom: 0;
    }
    .settings-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }
    .settings-info-row:last-child {
        border-bottom: none;
    }
    .settings-info-label {
        color: #64748b;
        font-weight: 500;
    }
    .settings-info-val {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
    }
    .settings-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    @media (max-width: 575.98px) {
        .settings-row {
            padding: 13px 14px;
            gap: 12px;
        }
        .settings-form-card {
            padding: 18px 16px;
        }
        .settings-info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .settings-info-val {
            text-align: left;
        }
    }
</style>

