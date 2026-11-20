<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Services\ServiceBookingReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceBookingController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pending');
        $activeTab = in_array($tab, ['pending', 'confirmed', 'completed'], true)
            ? $tab
            : 'pending';
        $bookings = ServiceBooking::query()
            ->with(['service', 'customer'])
            ->where('status', $activeTab)
            ->latest()
            ->paginate(15)
            ->withQueryString();
        foreach ($bookings as $booking) {
            $serviceTotal = $this->serviceTotal($booking);
            $booking->setAttribute(
                'display_payment_status',
                $serviceTotal !== null
                    ? $this->paymentStatusForAmount($booking->payment_amount_received, $serviceTotal)
                    : $booking->payment_status,
            );
        }

        return view('service-bookings.index', compact('activeTab', 'bookings'));
    }

    public function show(ServiceBooking $serviceBooking): View
    {
        $serviceBooking->load(['service', 'customer']);
        $serviceTotal = $this->serviceTotal($serviceBooking);
        $paymentStatus = $serviceTotal !== null
            ? $this->paymentStatusForAmount($serviceBooking->payment_amount_received, $serviceTotal)
            : $serviceBooking->payment_status;

        return view('service-bookings.show', compact('serviceBooking', 'serviceTotal', 'paymentStatus'));
    }

    public function showConfirmed(ServiceBooking $serviceBooking): View
    {
        abort_unless($serviceBooking->status === ServiceBooking::CONFIRMED, 404);

        $serviceBooking->load(['service', 'customer']);
        $serviceTotal = $this->serviceTotal($serviceBooking);
        $amountReceived = $serviceBooking->payment_amount_received !== null
            ? number_format((float) $serviceBooking->payment_amount_received, 2, '.', '')
            : '0.00';
        $remainingBalance = $serviceTotal !== null
            ? number_format(max(0, (float) $serviceTotal - (float) $amountReceived), 2, '.', '')
            : null;
        $paymentStatus = $serviceTotal !== null
            ? $this->paymentStatusForAmount($amountReceived, $serviceTotal)
            : $serviceBooking->payment_status;

        return view('service-bookings.confirmed', compact(
            'serviceBooking',
            'serviceTotal',
            'amountReceived',
            'remainingBalance',
            'paymentStatus',
        ));
    }

    public function showCompleted(ServiceBooking $serviceBooking): View
    {
        abort_unless($serviceBooking->status === ServiceBooking::COMPLETED, 404);

        $serviceBooking->load(['service', 'customer']);
        $serviceTotal = $this->serviceTotal($serviceBooking);
        $amountReceived = $serviceBooking->payment_amount_received;
        $remainingBalance = $serviceTotal !== null
            ? number_format(max(0, (float) $serviceTotal - (float) ($amountReceived ?? 0)), 2, '.', '')
            : null;
        $paymentStatus = $serviceTotal !== null
            ? $this->paymentStatusForAmount($amountReceived, $serviceTotal)
            : $serviceBooking->payment_status;

        return view('service-bookings.completed', compact(
            'serviceBooking',
            'serviceTotal',
            'amountReceived',
            'remainingBalance',
            'paymentStatus',
        ));
    }

    public function recordPayment(Request $request, ServiceBooking $serviceBooking)
    {
        $validated = $request->validate([
            'has_paid' => ['required', 'in:yes,no'],
            'payment_type' => ['nullable', 'required_if:has_paid,yes', 'in:full,partial'],
            'amount_received' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0'],
        ]);

        $serviceTotal = $this->serviceTotal($serviceBooking);

        if ($validated['has_paid'] === 'yes') {
            if ($validated['payment_type'] === 'full') {
                if ($serviceTotal !== null) {
                    $amountReceived = $serviceTotal;
                } else {
                    $amountReceived = $this->validatedReceivedAmount($validated, $serviceTotal);
                }
            } else {
                $amountReceived = $this->validatedReceivedAmount($validated, $serviceTotal);
            }
        } else {
            $amountReceived = null;
        }
        $paymentStatus = $this->paymentStatusForAmount($amountReceived, $serviceTotal);

        $serviceBooking->forceFill([
            'payment_amount_received' => $amountReceived,
            'payment_status' => $paymentStatus,
            'payment_recorded' => true,
        ])->save();

        $serviceBooking->load('service');

        return response()->json([
            'saved' => true,
            'payment_status' => $serviceBooking->payment_status,
            'payment_status_label' => $this->paymentStatusLabel($serviceBooking->payment_status),
            'amount_received' => $serviceBooking->payment_amount_received,
            'payment_recorded' => $serviceBooking->payment_recorded,
            'total_amount' => $this->serviceTotal($serviceBooking),
            'balance' => $this->balanceFor($serviceBooking),
            'has_total' => $this->serviceTotal($serviceBooking) !== null,
        ]);
    }

    public function saveAppointment(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $serviceBooking->update([
            'appointment_date' => $validated['appointment_date'],
        ]);

        return back()->with('success', 'Appointment date saved.');
    }

    public function confirm(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        DB::transaction(function () use ($serviceBooking, $validated): void {
            $booking = ServiceBooking::query()->whereKey($serviceBooking->id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== ServiceBooking::PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending bookings can be confirmed.',
                ]);
            }

            if (
                ! in_array($booking->payment_status, [ServiceBooking::PAID, ServiceBooking::PARTIALLY_PAID], true)
                || $booking->payment_amount_received === null
                || (float) $booking->payment_amount_received <= 0
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Save a valid payment amount before confirming this booking.',
                ]);
            }

            $booking->update([
                'appointment_date' => $validated['appointment_date'],
                'status' => ServiceBooking::CONFIRMED,
            ]);
        });

        return redirect()
            ->route('service-bookings.index', ['tab' => 'confirmed'])
            ->with('success', 'Service booking confirmed.');
    }

    public function recordAdditionalPayment(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $replacePayment = $request->input('payment_mode') === 'replace';
        $validated = $request->validate([
            'amount_received' => ['required', 'numeric', 'decimal:0,2', $replacePayment ? 'gte:0' : 'gt:0'],
            'payment_mode' => ['nullable', 'in:replace'],
        ]);

        DB::transaction(function () use ($serviceBooking, $validated, $replacePayment): void {
            $booking = ServiceBooking::query()
                ->with('service')
                ->whereKey($serviceBooking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($booking->status, [ServiceBooking::CONFIRMED, ServiceBooking::COMPLETED], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Additional payments can only be recorded for confirmed or completed bookings.',
                ]);
            }

            if ($replacePayment && $booking->status !== ServiceBooking::COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Payment corrections can only be made for completed bookings.',
                ]);
            }

            $currentAmountCents = (int) round((float) ($booking->payment_amount_received ?? 0) * 100);
            $enteredAmountCents = (int) round((float) $validated['amount_received'] * 100);
            $newAmountCents = $replacePayment
                ? $enteredAmountCents
                : $currentAmountCents + $enteredAmountCents;
            $serviceTotal = $this->serviceTotal($booking);
            $totalCents = $serviceTotal === null
                ? null
                : (int) round((float) $serviceTotal * 100);

            if ($totalCents !== null && $newAmountCents > $totalCents) {
                throw ValidationException::withMessages([
                    'amount_received' => 'The total amount received cannot exceed the booking amount.',
                ]);
            }

            $amountReceived = $newAmountCents > 0
                ? number_format($newAmountCents / 100, 2, '.', '')
                : null;
            $booking->forceFill([
                'payment_amount_received' => $amountReceived,
                'payment_status' => $this->paymentStatusForAmount($amountReceived, $serviceTotal),
                'payment_recorded' => true,
            ])->save();
        });

        return back()->with('success', 'Payment information updated.');
    }

    public function complete(ServiceBooking $serviceBooking): RedirectResponse
    {
        DB::transaction(function () use ($serviceBooking): void {
            $booking = ServiceBooking::query()
                ->whereKey($serviceBooking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== ServiceBooking::CONFIRMED) {
                throw ValidationException::withMessages([
                    'status' => 'Only confirmed bookings can be marked as completed.',
                ]);
            }

            $booking->forceFill([
                'status' => ServiceBooking::COMPLETED,
                'completed_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('service-bookings.index', ['tab' => 'completed'])
            ->with('completed_success', 'Service booking marked as completed.');
    }

    public function store(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'submission_key' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'preferred_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer = $request->user('customers');
        $booking = DB::transaction(function () use ($service, $validated, $customer): ServiceBooking {
            Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();

            $existingBooking = ServiceBooking::query()
                ->where('service_id', $service->id)
                ->where('submission_key', $validated['submission_key'])
                ->first();

            if ($existingBooking) {
                return $existingBooking;
            }

            return ServiceBooking::query()->create([
                'reference' => app(ServiceBookingReferenceGenerator::class)->generateFor($service),
                'service_id' => $service->id,
                'submission_key' => $validated['submission_key'],
                'customer_id' => $customer?->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'preferred_date' => $validated['preferred_date'],
                'preferred_time' => 'flexible',
                'requirements' => $validated['requirements'] ?? null,
                'status' => ServiceBooking::PENDING,
                'payment_status' => ServiceBooking::NOT_PAID,
            ]);
        });

        return redirect()
            ->route('service.show', $service->slug)
            ->with('booking_reference', $booking->reference);
    }

    private function serviceTotal(ServiceBooking $booking): ?string
    {
        $price = $booking->service?->price;

        return $price !== null && (float) $price > 0
            ? number_format((float) $price, 2, '.', '')
            : null;
    }

    private function paymentStatusForAmount(?string $amountReceived, ?string $serviceTotal): string
    {
        if ($amountReceived === null || (float) $amountReceived <= 0) {
            return ServiceBooking::NOT_PAID;
        }

        if (
            $serviceTotal !== null
            && (int) round((float) $amountReceived * 100) >= (int) round((float) $serviceTotal * 100)
        ) {
            return ServiceBooking::PAID;
        }

        return ServiceBooking::PARTIALLY_PAID;
    }

    private function validatedReceivedAmount(array $validated, ?string $serviceTotal): string
    {
        $amount = $validated['amount_received'] ?? null;

        if ($amount === null || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'amount_received' => 'Enter an amount greater than zero.',
            ]);
        }

        if (
            $serviceTotal !== null
            && (int) round((float) $amount * 100) > (int) round((float) $serviceTotal * 100)
        ) {
            throw ValidationException::withMessages([
                'amount_received' => 'The amount received cannot exceed the booking total.',
            ]);
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function balanceFor(ServiceBooking $booking): ?string
    {
        $total = $this->serviceTotal($booking);
        if ($total === null || $booking->payment_amount_received === null) {
            return null;
        }

        return number_format(max(0, (float) $total - (float) $booking->payment_amount_received), 2, '.', '');
    }

    private function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            ServiceBooking::PAID => 'Paid',
            ServiceBooking::PARTIALLY_PAID => 'Partially Paid',
            default => 'Not Paid',
        };
    }
}
