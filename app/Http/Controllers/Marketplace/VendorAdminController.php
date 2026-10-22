<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VendorAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('vendors.view'), 403);

        $vendors = Vendor::query()
            ->withCount(['products', 'users'])
            ->when($request->q, fn ($query) => $query->where('business_name', 'like', '%'.$request->q.'%'))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('marketplace.vendors.index', compact('vendors'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('vendors.create'), 403);

        return view('marketplace.vendors.form', ['vendor' => new Vendor(['status' => 'pending'])]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('vendors.create'), 403);

        $vendor = Vendor::query()->create($this->validated($request));
        AuditLogger::log($request->user(), 'vendor.created', $vendor, ['business_name' => $vendor->business_name]);

        return redirect()->route('marketplace.vendors.show', $vendor)->with('success', 'Vendor registered.');
    }

    public function show(Vendor $vendor)
    {
        abort_unless(auth()->user()->hasPermission('vendors.view'), 403);

        $vendor->load(['users', 'products.category']);
        $requests = $vendor->requests()->latest()->limit(10)->get();
        $items = $vendor->orderItems()->with('order')->latest()->limit(10)->get();

        AuditLogger::log(auth()->user(), 'vendor.viewed', $vendor);

        return view('marketplace.vendors.show', compact('vendor', 'requests', 'items'));
    }

    public function edit(Vendor $vendor)
    {
        abort_unless(auth()->user()->hasPermission('vendors.manage'), 403);

        return view('marketplace.vendors.form', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $before = $vendor->status;
        $vendor->update($this->validated($request, $vendor));
        AuditLogger::log($request->user(), 'vendor.updated', $vendor, ['status_before' => $before, 'status_after' => $vendor->status]);

        return redirect()->route('marketplace.vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    public function status(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'active', 'suspended', 'inactive'])]]);
        $before = $vendor->status;
        $vendor->update(['status' => $data['status']]);
        AuditLogger::log($request->user(), 'vendor.status_changed', $vendor, ['from' => $before, 'to' => $vendor->status]);

        return back()->with('success', 'Vendor status updated.');
    }

    private function validated(Request $request, ?Vendor $vendor = null): array
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100', Rule::unique('vendors', 'reference_number')->ignore($vendor?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'inactive'])],
            'district' => ['nullable', 'string', 'max:255'],
            'operating_location' => ['nullable', 'string', 'max:255'],
            'address_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'public_branding_approved' => ['nullable', 'boolean'],
        ]) + [
            'public_branding_approved' => $request->boolean('public_branding_approved'),
        ];
    }
}
