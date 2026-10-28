@once
@push('scripts')
<script>
(() => {
    const showMessage = (message, type) => {
        const container = document.querySelector('[data-live-delivery-feedback]') || document.querySelector('.delivery-page');
        if (!container) return;
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        alert.innerHTML = `<span></span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        alert.querySelector('span').textContent = message;
        container.prepend(alert);
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-delivery-complete-form]');
        if (!form || form.dataset.submitting === 'true') return;

        event.preventDefault();
        form.dataset.submitting = 'true';
        const button = form.querySelector('button[type="submit"]');
        const originalLabel = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = 'Updating...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Delivery could not be updated.');
            }

            const row = form.closest('tr');
            row?.remove();
            const inTransit = document.querySelector('[data-delivery-status-count="in_transit"]');
            const delivered = document.querySelector('[data-delivery-status-count="delivered"]');
            if (inTransit) inTransit.textContent = Math.max(0, Number(inTransit.textContent) - 1);
            if (delivered) delivered.textContent = Number(delivered.textContent) + 1;
            showMessage(payload.message || 'Delivery marked as Delivered successfully.', 'success');
        } catch (error) {
            form.dataset.submitting = 'false';
            if (button) {
                button.disabled = false;
                button.textContent = originalLabel;
            }
            showMessage(error.message, 'danger');
        }
    });
})();
</script>
@endpush
@endonce
