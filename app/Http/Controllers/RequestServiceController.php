<?php

namespace App\Http\Controllers;

use App\Models\Servicerequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestServiceController extends Controller
{
    // ─── SHOW FORM (Public) ───────────────────────────────────
    public function create(Request $request)
    {
        return view('front.pages.request-service', [
            'defaultAddress' => Auth::guard('customers')->user()?->defaultAddress(),
        ]);
    }

    // ─── STORE (Public) ───────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'service_category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'budget' => ['nullable', 'string', 'max:255'],
            'timeline' => ['nullable', 'string', 'max:255'],
            'files.*' => ['nullable', 'file', 'max:5120',
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xlsx'],
        ]);

        // Handle file uploads
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $dir = public_path('assets/service-requests');
            if (! file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            foreach ($request->file('files') as $file) {
                $filename = uniqid().'_'.time().'.'.$file->getClientOriginalExtension();
                $file->move($dir, $filename);
                $uploadedFiles[] = 'assets/service-requests/'.$filename;
            }
        }

        ServiceRequest::create([
            'customer_id' => Auth::guard('customers')->id(),
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'service_category' => $request->service_category,
            'description' => $request->description,
            'budget' => $request->budget,
            'timeline' => $request->timeline,
            'files' => $uploadedFiles ?: null,
            'status' => 'pending',
        ]);

        return redirect()->route('request-service')
            ->with('success', true);
    }

    // ─── INDEX (Admin) ────────────────────────────────────────
    public function index(Request $request)
    {
        $query = ServiceRequest::latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $serviceRequests = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => ServiceRequest::count(),
            'pending' => ServiceRequest::pending()->count(),
            'reviewed' => ServiceRequest::reviewed()->count(),
            'completed' => ServiceRequest::completed()->count(),
        ];

        return view('service-requests.index', compact('serviceRequests', 'counts'));
    }

    // ─── UPDATE STATUS (Admin) ────────────────────────────────
    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'status' => ['required', 'in:pending,reviewed,completed'],
        ]);

        $serviceRequest->update(['status' => $request->status]);

        return back()->with('success', 'Status updated successfully!');
    }

    // ─── DESTROY (Admin) ──────────────────────────────────────
    public function destroy(ServiceRequest $serviceRequest)
    {
        // Delete uploaded files
        foreach ($serviceRequest->files ?? [] as $file) {
            $path = public_path($file);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $serviceRequest->delete();

        return redirect()->route('service-requests.index')
            ->with('success', 'Service request deleted successfully!');
    }
}
