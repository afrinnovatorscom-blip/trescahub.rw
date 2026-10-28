@push('scripts')
<script>
    (() => {
        const feedback = (message, type) => {
            const content = document.querySelector('#main-content .content-inner');
            if (!content) return;

            let alert = content.querySelector('[data-orders-live-feedback]');
            if (!alert) {
                alert = document.createElement('div');
                alert.dataset.ordersLiveFeedback = '';
                content.prepend(alert);
            }
            alert.className = `alert alert-${type} mb-3`;
            alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
            alert.setAttribute('aria-live', 'polite');
            alert.textContent = message;
        };

        const refreshPage = async (url, message, addHistory = true) => {
            const target = new URL(url, window.location.href);
            if (target.origin !== window.location.origin) {
                throw new Error('The updated page URL is not on this site.');
            }

            const response = await fetch(target.href, {
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error(`The updated page could not be loaded (${response.status}).`);
            }

            const documentText = await response.text();
            const nextDocument = new DOMParser().parseFromString(documentText, 'text/html');
            const currentContent = document.querySelector('#main-content .content-inner');
            const nextContent = nextDocument.querySelector('#main-content .content-inner');
            if (!currentContent || !nextContent) {
                throw new Error('The updated page content was not available.');
            }

            currentContent.replaceChildren(...Array.from(nextContent.childNodes));
            document.head.querySelectorAll('style').forEach((style) => style.remove());
            nextDocument.head.querySelectorAll('style').forEach((style) => {
                document.head.append(style.cloneNode(true));
            });
            document.title = nextDocument.title;
            const currentTitle = document.querySelector('#topbar .topbar-title');
            const nextTitle = nextDocument.querySelector('#topbar .topbar-title');
            if (currentTitle && nextTitle) currentTitle.textContent = nextTitle.textContent;
            document.querySelectorAll('.nav-item-link.active').forEach((link) => link.classList.remove('active'));
            nextDocument.querySelectorAll('.nav-item-link.active').forEach((nextLink) => {
                const currentLink = Array.from(document.querySelectorAll('.nav-item-link'))
                    .find((link) => link.getAttribute('href') === nextLink.getAttribute('href'));
                if (currentLink) currentLink.classList.add('active');
            });
            if (addHistory) window.history.pushState({}, '', target.href);
            updatePaymentFields();
            updateReviewDecisionFields();
            if (message) feedback(message, 'success');
        };

        const updateReviewDecisionFields = () => {
            const acceptForm = document.getElementById('accept-order-form');
            const cancelForm = document.getElementById('cancel-order-form');
            const acceptChoice = document.getElementById('choose-accept-order');
            const cancelChoice = document.getElementById('choose-cancel-order');
            if (!acceptForm || !cancelForm || !acceptChoice || !cancelChoice) return;

            const acceptMode = !cancelChoice.classList.contains('is-selected');
            acceptForm.hidden = !acceptMode;
            cancelForm.hidden = acceptMode;
            acceptChoice.setAttribute('aria-pressed', acceptMode ? 'true' : 'false');
            cancelChoice.setAttribute('aria-pressed', acceptMode ? 'false' : 'true');
            acceptChoice.classList.toggle('is-selected', acceptMode);
            cancelChoice.classList.toggle('is-selected', !acceptMode);

            document.getElementById('cancellation-reason').required = !acceptMode;
            const cancellationLabel = document.getElementById('cancellation-reason-label');
            if (cancellationLabel) cancellationLabel.textContent = acceptMode ? '' : 'Required reason';
            acceptForm.querySelectorAll('input[name="has_paid"], input[name="payment_type"], input[name="payment_method"], input[name="partial_amount"]')
                .forEach((field) => field.disabled = !acceptMode);
        };

        const updatePaymentFields = () => {
            const paidChoice = document.querySelector('input[name="has_paid"][value="yes"]');
            const paymentDetails = document.getElementById('payment-details');
            const paymentType = document.querySelector('input[name="payment_type"]:checked');
            const partialPanel = document.getElementById('partial-payment-amount');
            const partialAmount = document.getElementById('partial-amount');
            const method = document.getElementById('payment-method');
            if (!paidChoice || !paymentDetails || !partialPanel || !partialAmount || !method) return;

            const showPaymentDetails = paidChoice.checked;
            paymentDetails.hidden = !showPaymentDetails;
            document.querySelectorAll('input[name="payment_type"]').forEach((field) => field.disabled = !showPaymentDetails);
            method.required = showPaymentDetails;
            method.disabled = !showPaymentDetails;
            const isPartial = showPaymentDetails && paymentType?.value === 'partial';
            partialPanel.hidden = !isPartial;
            partialAmount.required = isPartial;
            partialAmount.disabled = !isPartial;

            const remaining = document.getElementById('remaining-balance');
            if (remaining) {
                const total = Number(remaining.dataset.total);
                const entered = isPartial ? Number(partialAmount.value || 0) : showPaymentDetails ? total : 0;
                const balance = Math.max(0, total - entered);
                remaining.textContent = `${balance.toLocaleString(undefined, { maximumFractionDigits: 2, minimumFractionDigits: 2 })} RWF`;
            }
        };

        document.addEventListener('change', (event) => {
            if (event.target.name === 'has_paid' || event.target.name === 'payment_type' || event.target.id === 'partial-amount') updatePaymentFields();
        });

        document.addEventListener('click', (event) => {
            if (event.target.closest('#choose-cancel-order')) {
                document.getElementById('choose-cancel-order').classList.add('is-selected');
                document.getElementById('choose-accept-order').classList.remove('is-selected');
                updateReviewDecisionFields();
                document.getElementById('cancellation-reason').focus();
                return;
            }

            if (event.target.closest('#choose-accept-order') || event.target.closest('#back-to-accept')) {
                document.getElementById('choose-accept-order').classList.add('is-selected');
                document.getElementById('choose-cancel-order').classList.remove('is-selected');
                updateReviewDecisionFields();
                return;
            }

            const toggle = event.target.closest('#show-rejection-form');
            if (toggle) {
                const panel = document.getElementById('rejection-panel');
                const reason = document.getElementById('rejection-reason');
                if (!panel || !reason) return;
                panel.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
                reason.focus();
                return;
            }

            if (event.target.closest('#cancel-rejection')) {
                const panel = document.getElementById('rejection-panel');
                const rejectionToggle = document.getElementById('show-rejection-form');
                if (!panel || !rejectionToggle) return;
                panel.hidden = true;
                rejectionToggle.setAttribute('aria-expanded', 'false');
                rejectionToggle.focus();
            }
        });

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('form[data-orders-live-form]');
            if (!form || form.dataset.submitting === 'true') return;

            event.preventDefault();
            const formData = new FormData(form);
            const buttons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
            const originalLabels = buttons.map((button) => button.textContent);
            form.dataset.submitting = 'true';
            buttons.forEach((button) => {
                button.disabled = true;
                if (button.tagName === 'BUTTON') button.textContent = 'Saving...';
            });

            let actionSucceeded = false;
            let successMessage = '';
            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });
                const result = await response.json();
                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    feedback(validationMessage || result.message || 'The action could not be completed.', 'danger');
                    return;
                }

                if (!result.redirect_url) {
                    throw new Error('The server did not provide the updated page location.');
                }
                actionSucceeded = true;
                successMessage = result.message || 'Changes saved.';
                await refreshPage(result.redirect_url, result.message || 'Changes saved.');
            } catch (error) {
                if (actionSucceeded) {
                    feedback(`${successMessage} The page could not refresh. Reload it to view the latest saved data.`, 'warning');
                } else {
                    feedback(error.message || 'The action could not be completed. Check your connection and try again.', 'danger');
                }
            } finally {
                delete form.dataset.submitting;
                buttons.forEach((button, index) => {
                    button.disabled = false;
                    if (button.tagName === 'BUTTON') button.textContent = originalLabels[index];
                });
            }
        });

        window.addEventListener('popstate', () => {
            refreshPage(window.location.href, '', false).catch((error) => {
                feedback(error.message || 'The page could not be restored. Reload it to view the latest data.', 'danger');
            });
        });

        updatePaymentFields();
        updateReviewDecisionFields();
    })();
</script>
@endpush
