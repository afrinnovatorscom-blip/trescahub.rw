@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 760px;">
    <div id="account-order-tracking" data-tracking-url="{{ route('account.orders.tracking', $tracking['reference']) }}">
        <h1 class="h3">Order {{ $tracking['reference'] }}</h1>
        <p class="text-muted">Placed {{ $tracking['placed_at']?->format('M j, Y g:i A') }} · RWF {{ number_format((float) $tracking['total'], 0, '.', ',') }}</p>
        <p><strong>Delivery address:</strong> {{ $tracking['delivery_address'] }}</p>
    </div>
    @include('marketplace.partials.flash')
    @include('front.pages.partials.tracking-timeline')
    <p class="small text-muted mt-2" data-tracking-refresh-error hidden>Tracking could not be refreshed. Reload this page to try again.</p>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('account-order-tracking');
            const errorMessage = document.querySelector('[data-tracking-refresh-error]');

            if (!container || !errorMessage) return;

            const refreshTracking = async () => {
                if (document.hidden) return;

                try {
                    const response = await fetch(container.dataset.trackingUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) throw new Error('Tracking refresh failed.');

                    const payload = await response.json();
                    const tracking = payload.tracking;
                    const message = document.querySelector('[data-tracking-message]');
                    const payment = document.querySelector('[data-payment-status]');
                    const payments = document.querySelector('[data-tracking-payments]');
                    const steps = document.querySelector('[data-tracking-steps]');
                    const items = document.querySelector('[data-tracking-items]');
                    const deliveries = document.querySelector('[data-tracking-deliveries]');
                    const deliveriesHeading = document.querySelector('[data-deliveries-heading]');
                    const events = document.querySelector('[data-tracking-events]');

                    if (message) message.textContent = tracking.message;
                    if (payment) payment.textContent = tracking.payment_status.replaceAll('_', ' ');
                    if (payments) {
                        payments.hidden = tracking.payments.length === 0;
                        payments.replaceChildren(...tracking.payments.map((record) => {
                            const row = document.createElement('li');
                            const date = record.recorded_at ? ` — ${new Date(record.recorded_at).toLocaleString()}` : '';
                            row.textContent = `${record.type} payment of ${tracking.currency} ${Number(record.amount).toLocaleString()} via ${record.method.replaceAll('_', ' ')}${date}`;
                            return row;
                        }));
                    }
                    if (steps) {
                        steps.replaceChildren(...tracking.timeline.steps.map((step) => {
                            const row = document.createElement('li');
                            row.className = 'list-group-item d-flex justify-content-between';
                            const label = document.createElement('span');
                            label.textContent = step.label;
                            const state = document.createElement('span');
                            state.className = `badge bg-${step.state === 'current' ? 'primary' : (step.state === 'complete' ? 'success' : 'light text-dark')}`;
                            state.textContent = step.state;
                            row.append(label, state);
                            return row;
                        }));
                    }
                    if (items) {
                        items.replaceChildren(...tracking.items.map((item) => {
                            const row = document.createElement('li');
                            row.textContent = `${item.name} × ${item.quantity} — ${tracking.currency} ${Number(item.line_total).toLocaleString()} · ${item.fulfillment_label} · ${item.assignment_label}`;
                            return row;
                        }));
                    }
                    if (deliveries) {
                        deliveries.hidden = tracking.deliveries.length === 0;
                        deliveries.replaceChildren(...tracking.deliveries.map((delivery) => {
                            const row = document.createElement('li');
                            row.textContent = `${delivery.reference}: ${delivery.status_label}`;
                            return row;
                        }));
                    }
                    if (deliveriesHeading) deliveriesHeading.hidden = tracking.deliveries.length === 0;
                    if (events) {
                        events.replaceChildren(...tracking.events.map((event) => {
                            const row = document.createElement('li');
                            const date = event.occurred_at ? new Date(event.occurred_at).toLocaleString() : '';
                            row.textContent = date ? `${date} — ${event.label}` : event.label;
                            return row;
                        }));
                    }

                    errorMessage.hidden = true;
                } catch {
                    errorMessage.hidden = false;
                }
            };

            window.setInterval(refreshTracking, 15000);
        });
    </script>
    @if($canCancel)
        <form method="POST" action="{{ route('account.orders.cancel', $tracking['reference']) }}" class="mt-4">
            @csrf
            <label class="form-label" for="reason">Request cancellation</label>
            <input id="reason" name="reason" class="form-control mb-2" required>
            <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Request cancellation?')">Send request</button>
        </form>
    @endif
</div></main>
@endsection
