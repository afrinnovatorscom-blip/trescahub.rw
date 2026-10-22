<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $vendor = $user?->vendor;

        if (! $user || ! $user->hasRole('vendor') || ! $vendor || $vendor->status !== 'active') {
            abort(403, 'This account cannot access the vendor workspace.');
        }

        return $next($request);
    }
}
