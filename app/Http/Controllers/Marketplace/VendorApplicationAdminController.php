<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\MarketplaceNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorApplicationAdminController extends Controller
{
    public function show(Request $request, Vendor $vendor): View
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);
        abort_unless($vendor->application_submitted_at, 404);
        $vendor->load(['users' => fn ($query) => $query->orderBy('id')]);

        $correctionNotice = null;
        if ($vendor->application_status === 'correction_requested' && filled($vendor->correction_request)) {
            $correctionRequestLogId = AuditLog::query()
                ->where('action', 'vendor.application_correction_requested')
                ->where('auditable_type', $vendor->getMorphClass())
                ->where('auditable_id', $vendor->getKey())
                ->latest('id')
                ->value('id');

            if ($correctionRequestLogId !== null) {
                $noticeWasRead = AuditLog::query()
                    ->where('user_id', $request->user()->id)
                    ->where('action', 'vendor.application_correction_notice_read')
                    ->where('auditable_type', $vendor->getMorphClass())
                    ->where('auditable_id', $vendor->getKey())
                    ->where('meta->correction_request_log_id', $correctionRequestLogId)
                    ->exists();

                if (! $noticeWasRead) {
                    $correctionNotice = $vendor->correction_request;
                    AuditLogger::log(
                        $request->user(),
                        'vendor.application_correction_notice_read',
                        $vendor,
                        meta: ['correction_request_log_id' => $correctionRequestLogId],
                    );
                }
            }
        }

        return view('marketplace.vendor-applications.show', compact('vendor', 'correctionNotice'));
    }

    public function certificate(Request $request, Vendor $vendor): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);
        $disk = Storage::disk('local');
        abort_unless($vendor->registration_certificate_path, 404);
        abort_unless($disk->exists($vendor->registration_certificate_path), 404);

        AuditLogger::log($request->user(), 'vendor.application_certificate_downloaded', $vendor);

        $mimeType = $disk->mimeType($vendor->registration_certificate_path) ?: 'application/octet-stream';
        if ($request->boolean('view') && in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return $disk->response($vendor->registration_certificate_path, basename($vendor->registration_certificate_path), [
                'Content-Type' => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
            ], 'inline');
        }

        return $disk->download($vendor->registration_certificate_path);
    }

    public function approve(Request $request, Vendor $vendor, MarketplaceNotifier $notifier): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $reviewedVendor = DB::transaction(function () use ($request, $vendor): Vendor {
            $lockedVendor = $this->lockReviewableApplication($vendor);
            $vendorAccount = $lockedVendor->users()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'vendor'))
                ->orderBy('id')
                ->first();
            $profileAttributes = [];

            if (blank($lockedVendor->legal_name) && filled($lockedVendor->business_name)) {
                $profileAttributes['legal_name'] = $lockedVendor->business_name;
            }
            if (blank($lockedVendor->contact_person) && filled($vendorAccount?->name)) {
                $profileAttributes['contact_person'] = $vendorAccount->name;
            }
            if (filled($vendorAccount?->email)
                && (blank($lockedVendor->email) || $lockedVendor->email === $lockedVendor->business_email)) {
                $profileAttributes['email'] = $vendorAccount->email;
            }
            if (blank($lockedVendor->address_notes) && filled($lockedVendor->business_address)) {
                $profileAttributes['address_notes'] = $lockedVendor->business_address;
            }

            $lockedVendor->update($profileAttributes + [
                'application_status' => 'approved',
                'status' => 'active',
                'application_reviewed_by' => $request->user()->id,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            AuditLogger::log($request->user(), 'vendor.application_approved', $lockedVendor);

            return $lockedVendor;
        });

        $notifier->vendorApplicationDecision($reviewedVendor, 'approved');

        return redirect()->route('marketplace.vendor-applications.show', $reviewedVendor)
            ->with('success', 'Vendor application approved. Vendor access is now active.');
    }

    public function reject(Request $request, Vendor $vendor, MarketplaceNotifier $notifier): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:5000'],
        ]);

        $reviewedVendor = DB::transaction(function () use ($request, $vendor, $data): Vendor {
            $lockedVendor = $this->lockReviewableApplication($vendor);
            $lockedVendor->update([
                'application_status' => 'rejected',
                'status' => 'inactive',
                'application_reviewed_by' => $request->user()->id,
                'rejection_reason' => $data['rejection_reason'],
            ]);

            AuditLogger::log($request->user(), 'vendor.application_rejected', $lockedVendor);

            return $lockedVendor;
        });

        $notifier->vendorApplicationDecision($reviewedVendor, 'rejected', $data['rejection_reason']);

        return redirect()->route('marketplace.vendor-applications.show', $reviewedVendor)
            ->with('success', 'Vendor application rejected and the applicant has been notified.');
    }

    public function requestCorrection(Request $request, Vendor $vendor, MarketplaceNotifier $notifier): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);
        $data = $request->validate([
            'correction_request' => ['required', 'string', 'max:5000'],
        ]);

        $reviewedVendor = DB::transaction(function () use ($request, $vendor, $data): Vendor {
            $lockedVendor = $this->lockReviewableApplication($vendor);
            $lockedVendor->update([
                'application_status' => 'correction_requested',
                'status' => 'pending',
                'application_reviewed_by' => $request->user()->id,
                'correction_request' => $data['correction_request'],
                'correction_requested_at' => now(),
                'rejection_reason' => null,
            ]);

            AuditLogger::log($request->user(), 'vendor.application_correction_requested', $lockedVendor);

            return $lockedVendor;
        });

        $notifier->vendorApplicationDecision($reviewedVendor, 'correction_requested', $data['correction_request']);

        return redirect()->route('marketplace.vendor-applications.show', $reviewedVendor)
            ->with('success', 'Correction requested and the applicant has been notified.');
    }

    private function lockReviewableApplication(Vendor $vendor): Vendor
    {
        $lockedVendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);
        abort_unless(
            in_array($lockedVendor->application_status, ['pending', 'correction_requested'], true),
            409,
            'This vendor application has already received a final decision.'
        );

        return $lockedVendor;
    }
}
