<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceBooking;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceBookingReferenceGenerator
{
    private const SERVICE_CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const REFERENCE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generateFor(Service $service): string
    {
        $serviceCode = $this->serviceCodeFor($service);

        do {
            $suffix = '';
            for ($index = 0; $index < 4; $index++) {
                $suffix .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }

            $reference = 'TH'.$serviceCode.'-'.$suffix;
        } while (ServiceBooking::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function refreshExistingReferences(): void
    {
        DB::transaction(function (): void {
            ServiceBooking::query()
                ->with('service')
                ->orderBy('id')
                ->chunkById(200, function ($bookings): void {
                    foreach ($bookings as $booking) {
                        if (
                            preg_match('/^TH([A-Z0-9])-[A-HJ-NP-Z2-9]{4}$/', $booking->reference, $matches) === 1
                            && $matches[1] === $this->serviceCodeFor($booking->service)
                        ) {
                            continue;
                        }

                        $reference = $this->generateFor($booking->service);

                        DB::table('service_bookings')
                            ->where('id', $booking->id)
                            ->update(['reference' => $reference]);
                    }
                });
        });
    }

    private function serviceCodeFor(Service $service): string
    {
        $assignedCode = DB::table('service_booking_service_codes')
            ->where('service_id', $service->id)
            ->value('service_code');

        if ($assignedCode !== null) {
            return $assignedCode;
        }

        $usedCodes = DB::table('service_booking_service_codes')
            ->pluck('service_code')
            ->all();
        $preferredCode = strtoupper(substr($service->title, 0, 1));

        if (
            preg_match('/^[A-Z0-9]$/', $preferredCode) === 1
            && ! in_array($preferredCode, $usedCodes, true)
        ) {
            $serviceCode = $preferredCode;
        } else {
            $serviceCode = null;
            foreach (str_split(self::SERVICE_CODE_ALPHABET) as $candidate) {
                if (! in_array($candidate, $usedCodes, true)) {
                    $serviceCode = $candidate;
                    break;
                }
            }
        }

        if ($serviceCode === null) {
            throw new RuntimeException('No unique service booking reference codes remain.');
        }

        DB::table('service_booking_service_codes')->insert([
            'service_id' => $service->id,
            'service_code' => $serviceCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $serviceCode;
    }
}
