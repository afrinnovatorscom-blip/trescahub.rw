<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->routeIs(
                'checkout',
                'checkout.place',
                'product-requests.create',
                'product-requests.store',
            )) {
                $intended = $request->isMethodSafe()
                    ? $request->fullUrl()
                    : url()->previous(route('shop'));

                $intendedParts = parse_url($intended);
                $expectedPort = $request->getPort();
                $intendedPort = is_array($intendedParts) ? ($intendedParts['port'] ?? null) : null;

                if (
                    ! is_array($intendedParts)
                    || ($intendedParts['host'] ?? null) !== $request->getHost()
                    || (int) ($intendedPort ?? $expectedPort) !== (int) $expectedPort
                    || in_array($intendedParts['scheme'] ?? null, ['http', 'https'], true) === false
                ) {
                    $intended = route('shop');
                    $intendedParts = parse_url($intended);
                }

                session(['customer.auth_redirect' => $intended]);

                $modalDestination = match (true) {
                    $request->routeIs('checkout') => route('cart'),
                    $request->routeIs('checkout.place') => url()->previous(route('cart')),
                    $request->routeIs('product-requests.create') => route('shop'),
                    default => url()->previous(route('shop')),
                };

                $modalParts = parse_url($modalDestination);
                parse_str(is_array($modalParts) && isset($modalParts['query']) ? $modalParts['query'] : '', $parameters);
                $parameters['auth_required'] = 1;

                $redirect = ($modalParts['scheme'] ?? '').'://'.($modalParts['host'] ?? '');
                $redirect .= isset($modalParts['port']) ? ':'.$modalParts['port'] : '';
                $redirect .= $modalParts['path'] ?? '/';
                $redirect .= '?'.http_build_query($parameters);
                if (isset($modalParts['fragment'])) {
                    $redirect .= '#'.$modalParts['fragment'];
                }

                return $redirect;
            }

            return $request->is('account/*') || $request->is('customer/*')
                ? route('home')
                : route('login');
        });

        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'vendor.active' => \App\Http\Middleware\EnsureVendorIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
