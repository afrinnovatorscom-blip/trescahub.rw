@extends('front.layouts.guest')

@section('content')
@php
    $cartSubtotal = $lines->sum('line');
@endphp

<style>
    .cart-page {
        min-height: 60vh;
        background: #f8fafc;
    }

    .cart-page__title {
        color: #111827;
        font-family: 'Space Grotesk', sans-serif;
        letter-spacing: -0.035em;
    }

    .cart-card,
    .cart-summary {
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 8px 24px rgba(17, 24, 39, 0.045);
    }

    .cart-line {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        padding: 1.25rem;
    }

    .cart-line + .cart-line {
        border-top: 1px solid #e5e7eb;
    }

    .cart-product {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 1rem;
    }

    .cart-product__image {
        display: flex;
        width: 88px;
        height: 88px;
        flex: 0 0 88px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 0.75rem;
        background: #f3f4f6;
        color: #9ca3af;
    }

    .cart-product__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cart-product__name {
        overflow-wrap: anywhere;
        color: #111827;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
    }

    .cart-product__name:hover {
        color: #15803d;
    }

    .cart-line__controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 0.75rem;
    }

    .cart-quantity {
        width: 48px;
        min-height: 44px;
        padding: 0.35rem 0.2rem;
        border-radius: 0;
        text-align: center;
        appearance: textfield;
    }

    .cart-quantity::-webkit-inner-spin-button,
    .cart-quantity::-webkit-outer-spin-button {
        margin: 0;
        appearance: none;
    }

    .cart-quantity-stepper {
        display: inline-flex;
        align-items: center;
        overflow: hidden;
        border: 1px solid #d1d5db;
        border-radius: 0.65rem;
        background: #fff;
    }

    .cart-quantity-step {
        display: inline-flex;
        width: 42px;
        min-height: 44px;
        align-items: center;
        justify-content: center;
        border: 0;
        background: #fff;
        color: #15803d;
        font-size: 1.15rem;
        font-weight: 600;
    }

    .cart-quantity-step:hover:not(:disabled) {
        background: #f0fdf4;
    }

    .cart-quantity-step:focus-visible {
        position: relative;
        z-index: 1;
        outline: 2px solid #16a34a;
        outline-offset: -2px;
    }

    .cart-quantity-step:disabled {
        color: #9ca3af;
    }

    .cart-line__end {
        display: flex;
        min-width: 145px;
        flex-direction: column;
        align-items: flex-end;
        justify-content: center;
        gap: 0.75rem;
        text-align: right;
    }

    .cart-summary {
        padding: 1.5rem;
    }

    .cart-checkout {
        min-height: 48px;
        padding: 0.7rem 1.15rem;
        border-color: #16a34a;
        background: #16a34a;
        white-space: nowrap;
    }

    .cart-checkout:hover,
    .cart-checkout:focus-visible {
        border-color: #15803d;
        background: #15803d;
    }

    .cart-empty {
        padding: clamp(2rem, 6vw, 4rem) 1.5rem;
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .cart-line {
            grid-template-columns: minmax(0, 1fr);
            gap: 0.85rem;
            padding: 1rem;
        }

        .cart-product {
            align-items: flex-start;
            gap: 0.75rem;
        }

        .cart-product__image {
            width: 68px;
            height: 68px;
            flex-basis: 68px;
        }

        .cart-line__end {
            min-width: 0;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding-left: 4.25rem;
            text-align: left;
        }

        .cart-line__controls {
            flex-wrap: wrap;
        }

        .cart-summary {
            padding: 1.25rem;
        }
    }
</style>

<main class="cart-page py-4 py-md-5">
    <div class="container-xl">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mb-1 text-success fw-semibold small text-uppercase">Shopping</p>
                <h1 class="cart-page__title h2 mb-1">Your cart</h1>
                <p class="mb-0 text-muted">Review your items before continuing to checkout.</p>
            </div>
            @if($lines->isNotEmpty())
                <a href="{{ route('shop') }}" class="btn btn-outline-secondary rounded-pill px-3" data-cart-continue>
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Continue shopping
                </a>
            @endif
        </div>

        @include('marketplace.partials.flash')

        @if($lines->isEmpty())
            <section class="cart-card cart-empty" aria-labelledby="empty-cart-title">
                <div class="mb-3 text-success" aria-hidden="true">
                    <i class="bi bi-bag fs-1"></i>
                </div>
                <h2 id="empty-cart-title" class="h5 fw-semibold">Your cart is empty</h2>
                <p class="mb-4 text-muted">Browse the shop and add something you need.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary rounded-pill px-4">Continue shopping</a>
            </section>
        @else
            <template id="cart-empty-template">
                <section class="cart-card cart-empty" aria-labelledby="empty-cart-title">
                    <div class="mb-3 text-success" aria-hidden="true">
                        <i class="bi bi-bag fs-1"></i>
                    </div>
                    <h2 id="empty-cart-title" class="h5 fw-semibold">Your cart is empty</h2>
                    <p class="mb-4 text-muted">Browse the shop and add something you need.</p>
                    <a href="{{ route('shop') }}" class="btn btn-primary rounded-pill px-4">Continue shopping</a>
                </section>
            </template>
            <p id="cart-update-status" class="visually-hidden" role="status" aria-live="polite"></p>
            <div data-cart-shell>
            <form id="cart-update-form" method="POST" action="{{ route('cart.update') }}">
                @csrf
                @method('PATCH')

                <div class="row g-4 align-items-start">
                    <div class="col-12 col-lg-8">
                        <section class="cart-card overflow-hidden" aria-label="Cart items">
                            @foreach($lines as $line)
                                @php
                                    $product = $line['product'];
                                    $imagePath = $product->thumbnail
                                        ? asset('assets/' . ltrim(str_replace('public/assets/', '', $product->thumbnail), '/'))
                                        : null;
                                @endphp
                                <article class="cart-line">
                                    <div class="cart-product">
                                        <div class="cart-product__image" aria-hidden="{{ $imagePath ? 'true' : 'false' }}">
                                            @if($imagePath)
                                                <img src="{{ $imagePath }}" alt="">
                                            @else
                                                <i class="bi bi-box-seam fs-3" aria-hidden="true"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-grow-1">
                                            <a href="{{ route('shop-products.show', $product) }}" class="cart-product__name">
                                                {{ $product->product_name }}
                                            </a>
                                            @if($line['variation'])
                                                <div class="mt-1 text-muted small">{{ $line['variation']->feature_label }}: {{ $line['variation']->variation_value }}</div>
                                            @endif
                                            <div class="mt-1 text-muted small">RWF {{ number_format($line['unit_price'], 0, '.', ',') }} each</div>
                                            <div class="cart-line__controls">
                                                <div class="d-flex align-items-center gap-2 text-muted small">
                                                    Qty
                                                    <div class="cart-quantity-stepper">
                                                        <button
                                                            type="button"
                                                            class="cart-quantity-step"
                                                            data-cart-step="-1"
                                                            aria-label="Decrease quantity of {{ $product->product_name }}"
                                                        >−</button>
                                                        <input
                                                            id="quantity-{{ $line['key'] }}"
                                                            class="form-control cart-quantity"
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            inputmode="numeric"
                                                            name="quantities[{{ $line['key'] }}]"
                                                            value="{{ $line['quantity'] }}"
                                                            data-cart-quantity
                                                            data-unit-price="{{ $line['unit_price'] }}"
                                                            aria-label="Quantity of {{ $product->product_name }}"
                                                        >
                                                        <button
                                                            type="button"
                                                            class="cart-quantity-step"
                                                            data-cart-step="1"
                                                            aria-label="Increase quantity of {{ $product->product_name }}"
                                                        >+</button>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="btn btn-link link-danger text-decoration-none p-0 small"
                                                    data-cart-remove="{{ $line['key'] }}"
                                                >
                                                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="cart-line__end">
                                        <span class="text-muted small">Item total</span>
                                        <strong class="text-nowrap" data-cart-line-total>RWF {{ number_format($line['line'], 0, '.', ',') }}</strong>
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    </div>

                    <aside class="col-12 col-lg-4" aria-labelledby="cart-summary-title">
                        <div class="cart-summary">
                            <h2 id="cart-summary-title" class="h5 fw-semibold mb-4">Order summary</h2>
                            <div class="d-flex justify-content-between gap-3 mb-3 text-muted">
                                <span>Subtotal</span>
                                <span class="text-nowrap" data-cart-subtotal>RWF {{ number_format($cartSubtotal, 0, '.', ',') }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-baseline gap-3 border-top pt-3 mb-4">
                                <span class="fw-semibold">Total</span>
                                <span class="fs-5 fw-bold text-nowrap" data-cart-total>RWF {{ number_format($cartSubtotal, 0, '.', ',') }}</span>
                            </div>
                            <a href="{{ route('checkout', Auth::guard('customers')->check() ? [] : ['auth_required' => 1]) }}" class="cart-checkout btn btn-success d-inline-flex align-items-center justify-content-center gap-2 rounded-pill" data-cart-checkout>
                                Proceed to Checkout <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                            <p id="cart-save-feedback" class="small mt-3 mb-0" role="status" aria-live="polite"></p>
                            <p class="small text-muted mt-3 mb-0">Delivery and payment details are confirmed at checkout.</p>
                        </div>
                    </aside>
                </div>
            </form>
            </div>

            <script>
                (() => {
                    const form = document.getElementById('cart-update-form');
                    const status = document.getElementById('cart-save-feedback');
                    const updateStatus = document.getElementById('cart-update-status');
                    const continueLink = document.querySelector('[data-cart-continue]');
                    const money = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
                    let saveTimer;
                    let saveInProgress = false;
                    let lastSavedQuantities = getQuantities();
                    let pendingCheckout = null;

                    function getQuantities() {
                        return Object.fromEntries(
                            Array.from(form.querySelectorAll('[data-cart-quantity]'))
                                .map((input) => [input.name.match(/\[([^\]]+)\]/)[1], Number.parseInt(input.value, 10) || 0])
                        );
                    }

                    function currentQuantitySnapshot() {
                        return JSON.stringify(getQuantities());
                    }

                    function formatRwf(amount) {
                        return `RWF ${money.format(amount)}`;
                    }

                    function showStatus(message, isError = false) {
                        status.textContent = message;
                        status.classList.toggle('text-danger', isError);
                        status.classList.toggle('text-muted', !isError);
                        updateStatus.textContent = message;
                    }

                    function updateTotals() {
                        let subtotal = 0;

                        form.querySelectorAll('[data-cart-quantity]').forEach((input) => {
                            const quantity = Math.min(100, Math.max(0, Number.parseInt(input.value, 10) || 0));
                            const stepper = input.closest('.cart-quantity-stepper');
                            stepper.querySelector('[data-cart-step="-1"]').disabled = quantity === 0;
                            stepper.querySelector('[data-cart-step="1"]').disabled = quantity === 100;
                            const lineTotal = Number(input.dataset.unitPrice) * quantity;
                            const line = input.closest('.cart-line');
                            line.querySelector('[data-cart-line-total]').textContent = formatRwf(lineTotal);
                            subtotal += lineTotal;
                        });

                        form.querySelector('[data-cart-subtotal]').textContent = formatRwf(subtotal);
                        form.querySelector('[data-cart-total]').textContent = formatRwf(subtotal);
                    }

                    function updateHeaderCartCount(count) {
                        const cartLink = document.querySelector('a[aria-label^="Shopping cart"]');
                        if (!cartLink) {
                            return;
                        }

                        cartLink.setAttribute('aria-label', count > 0 ? `Shopping cart, ${count} ${count === 1 ? 'product' : 'products'}` : 'Shopping cart');
                        let badge = cartLink.querySelector('span[aria-hidden="true"]');

                        if (count === 0) {
                            badge?.remove();
                            return;
                        }

                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'position-absolute d-inline-flex align-items-center justify-content-center rounded-circle';
                            badge.style.cssText = 'top:2px;right:1px;min-width:18px;height:18px;padding:0 3px;background:#16a34a;color:#fff;font-size:0.65rem;font-weight:700;line-height:1;';
                            badge.setAttribute('aria-hidden', 'true');
                            cartLink.append(badge);
                        }

                        badge.textContent = count;
                    }

                    function restoreSavedQuantities() {
                        form.querySelectorAll('[data-cart-quantity]').forEach((input) => {
                            const slug = input.name.match(/\[([^\]]+)\]/)[1];
                            input.value = lastSavedQuantities[slug] ?? '0';
                        });
                        updateTotals();
                    }

                    function scheduleSave(delay = 500) {
                        clearTimeout(saveTimer);
                        saveTimer = setTimeout(() => {
                            saveTimer = null;
                            saveCart();
                        }, delay);
                    }

                    async function saveCart() {
                        if (saveInProgress) {
                            return;
                        }

                        saveTimer = null;
                        saveInProgress = true;
                        const submittedSnapshot = currentQuantitySnapshot();
                        showStatus('Saving cart...');

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: new FormData(form),
                            });
                            const result = await response.json();

                            if (!response.ok) {
                                throw new Error(result.message || 'Cart update failed. Please try again.');
                            }

                            lastSavedQuantities = result.quantities;
                            updateHeaderCartCount(result.item_count);

                            if (currentQuantitySnapshot() === submittedSnapshot) {
                                Object.entries(result.lines).forEach(([slug, lineTotal]) => {
                                    const input = form.querySelector(`[name="quantities[${CSS.escape(slug)}]"]`);
                                    const line = input?.closest('.cart-line');
                                    if (line) {
                                        line.querySelector('[data-cart-line-total]').textContent = formatRwf(lineTotal);
                                    }
                                });
                                form.querySelector('[data-cart-subtotal]').textContent = formatRwf(result.subtotal);
                                form.querySelector('[data-cart-total]').textContent = formatRwf(result.subtotal);

                                form.querySelectorAll('.cart-line').forEach((line) => {
                                    const input = line.querySelector('[data-cart-quantity]');
                                    if (Number(input.value) === 0) {
                                        line.remove();
                                    }
                                });
                                showStatus(result.message);

                                if (result.item_count === 0) {
                                    document.querySelector('[data-cart-shell]').replaceWith(
                                        document.getElementById('cart-empty-template').content.cloneNode(true)
                                    );
                                    continueLink?.remove();
                                    showStatus('Your cart is empty.');
                                }

                                if (pendingCheckout) {
                                    const checkoutUrl = pendingCheckout;
                                    pendingCheckout = null;
                                    if (result.item_count > 0) {
                                        window.location.assign(checkoutUrl);
                                    }
                                    return;
                                }
                            }
                        } catch (error) {
                            showStatus(error.message, true);
                            restoreSavedQuantities();
                            pendingCheckout = null;
                        } finally {
                            saveInProgress = false;

                            if (currentQuantitySnapshot() !== JSON.stringify(lastSavedQuantities)) {
                                clearTimeout(saveTimer);
                                saveTimer = setTimeout(saveCart, 300);
                            }
                        }
                    }

                    form.addEventListener('input', (event) => {
                        if (!event.target.matches('[data-cart-quantity]')) {
                            return;
                        }

                        updateTotals();
                        showStatus('');
                        scheduleSave();
                    });

                    form.querySelectorAll('[data-cart-step]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const input = button.closest('.cart-quantity-stepper').querySelector('[data-cart-quantity]');
                            const current = Number.parseInt(input.value, 10) || 0;
                            const step = Number(button.dataset.cartStep);

                            input.value = String(Math.min(100, Math.max(0, current + step)));
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    });

                    form.querySelectorAll('[data-cart-remove]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const quantity = form.querySelector(`#quantity-${CSS.escape(button.dataset.cartRemove)}`);
                            if (!quantity) {
                                return;
                            }

                            quantity.value = '0';
                            quantity.dispatchEvent(new Event('input', { bubbles: true }));
                            scheduleSave(150);
                        });
                    });

                    document.querySelector('[data-cart-checkout]').addEventListener('click', (event) => {
                        if (!saveInProgress && !saveTimer) {
                            return;
                        }

                        event.preventDefault();
                        pendingCheckout = event.currentTarget.href;
                        clearTimeout(saveTimer);
                        saveTimer = null;

                        if (!saveInProgress) {
                            saveCart();
                        }
                    });

                    form.addEventListener('submit', (event) => event.preventDefault());
                    updateTotals();
                })();
            </script>
        @endif
    </div>
</main>
@endsection
