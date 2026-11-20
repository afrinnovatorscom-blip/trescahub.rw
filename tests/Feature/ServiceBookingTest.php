<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use App\Services\ServiceBookingReferenceGenerator;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::query()->create([
            'title' => 'Digital Services',
            'slug' => 'digital-services',
        ]);

        $this->service = Service::query()->create([
            'title' => 'Website Development',
            'slug' => 'website-development',
            'price' => 100000,
            'classification_name' => $category->id,
            'is_in_stock' => true,
        ]);
    }

    public function test_service_page_shows_booking_action_and_form(): void
    {
        $this->get(route('service.show', $this->service->slug))
            ->assertOk()
            ->assertSee('Order via WhatsApp')
            ->assertSee('Order Now')
            ->assertSee('Book This Service')
            ->assertSee('Preferred Date')
            ->assertDontSee('Preferred Time')
            ->assertDontSee('booking-preferred-time')
            ->assertSee('Submit Booking');
    }

    public function test_guest_order_now_opens_existing_sign_in_modal_for_the_selected_service(): void
    {
        $signInUrl = route('service.show', [
            'service' => $this->service->slug,
            'auth_required' => 1,
            'booking_after_login' => 1,
        ]);

        $this->get(route('service.show', $this->service->slug))
            ->assertOk()
            ->assertSee('Order Now')
            ->assertSee('href="'.str_replace('&', '&amp;', $signInUrl).'"', false)
            ->assertDontSee('data-bs-toggle="modal" data-bs-target="#serviceBookingModal"', false);

        $this->get($signInUrl)
            ->assertOk()
            ->assertSee('Please sign in first to continue with your order.')
            ->assertSee('open: true', false)
            ->assertSee('redirectAfterLoginUrl', false)
            ->assertSee('auto_book=1');
    }

    public function test_signed_in_customer_opens_booking_modal_directly_with_account_details_prefilled(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Booking Account Customer',
            'email' => 'booking-account@example.com',
            'phone' => '+250782222222',
            'password' => 'customer-password',
        ]);

        $this->actingAs($customer, 'customers')
            ->get(route('service.show', $this->service->slug))
            ->assertOk()
            ->assertSee('data-bs-toggle="modal" data-bs-target="#serviceBookingModal"', false)
            ->assertSee('value="Booking Account Customer"', false)
            ->assertSee('value="+250782222222"', false)
            ->assertSee('value="booking-account@example.com"', false);
    }

    public function test_guest_booking_is_saved_as_pending_and_duplicate_submission_is_idempotent(): void
    {
        $submissionKey = (string) Str::uuid();
        $bookingDetails = [
            'submission_key' => $submissionKey,
            'customer_name' => 'Guest Customer',
            'customer_phone' => '+250780000000',
            'customer_email' => 'guest@example.com',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'requirements' => 'A responsive company website.',
        ];

        $response = $this->from(route('service.show', $this->service->slug))
            ->post(route('service-bookings.store', $this->service->slug), $bookingDetails)
            ->assertRedirect(route('service.show', $this->service->slug));

        $booking = ServiceBooking::query()->sole();
        $this->assertSame(ServiceBooking::PENDING, $booking->status);
        $this->assertSame(ServiceBooking::NOT_PAID, $booking->payment_status);
        $this->assertNull($booking->customer_id);
        $this->assertSame($this->service->id, $booking->service_id);
        $this->assertSame($bookingDetails['preferred_date'], $booking->preferred_date->toDateString());
        $this->assertSame('flexible', $booking->preferred_time);
        $this->assertNull($booking->appointment_date);
        $this->assertNull($booking->appointment_time);
        $this->assertMatchesRegularExpression('/^THW-[A-Z2-9]{4}$/', $booking->reference);
        $response->assertSessionHas('booking_reference', $booking->reference);

        $this->post(route('service-bookings.store', $this->service->slug), $bookingDetails)
            ->assertSessionHas('booking_reference', $booking->reference);
        $this->assertDatabaseCount('service_bookings', 1);
    }

    public function test_booking_links_to_logged_in_customer_and_is_listed_in_pending_admin_tab(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Account Customer',
            'email' => 'account-customer@example.com',
            'phone' => '+250781111111',
            'password' => 'customer-password',
        ]);

        $this->actingAs($customer, 'customers')
            ->post(route('service-bookings.store', $this->service->slug), [
                'submission_key' => (string) Str::uuid(),
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'preferred_date' => now()->addDays(5)->toDateString(),
                'preferred_time' => 'flexible',
            ])
            ->assertRedirect(route('service.show', $this->service->slug));

        $booking = ServiceBooking::query()->sole();
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertTrue($customer->serviceBookings()->whereKey($booking->id)->exists());
        $this->assertTrue($this->service->bookings()->whereKey($booking->id)->exists());

        PermissionCatalog::sync();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('administrator');

        $this->actingAs($admin, 'web')
            ->get(route('service-bookings.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee($customer->name)
            ->assertSee($this->service->title)
            ->assertSee('Preferred Date')
            ->assertDontSee('Preferred Time')
            ->assertSee('View Booking')
            ->assertSee('Not Paid');
    }

    public function test_admin_can_save_partial_payment_and_confirm_booking_without_changing_preferred_date_or_payment_status(): void
    {
        $booking = $this->createBooking();
        $admin = $this->createAdmin();
        $appointmentDate = now()->addDays(8)->toDateString();

        $this->actingAs($admin, 'web')
            ->get(route('service-bookings.show', $booking))
            ->assertOk()
            ->assertSee('Booking information')
            ->assertSee('Appointment scheduling')
            ->assertSee('Payment recording')
            ->assertSee('Has the customer paid?')
            ->assertSee('Save Payment')
            ->assertDontSee('Preferred Time');

        $this->patchJson(route('service-bookings.payment', $booking), [
            'has_paid' => 'yes',
            'payment_type' => 'partial',
            'amount_received' => '25000',
        ])->assertOk()
            ->assertJsonPath('payment_status', ServiceBooking::PARTIALLY_PAID)
            ->assertJsonPath('amount_received', '25000.00')
            ->assertJsonPath('total_amount', '100000.00')
            ->assertJsonPath('balance', '75000.00')
            ->assertJsonPath('payment_recorded', true);

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => '25000.00',
            'payment_recorded' => true,
            'status' => ServiceBooking::PENDING,
        ]);
        $this->assertDatabaseCount('service_bookings', 1);

        $this->get(route('service-bookings.show', $booking))
            ->assertOk()
            ->assertSee('Edit Payment')
            ->assertSee('Update Payment')
            ->assertSee('id="cancel-payment-edit-button"', false)
            ->assertSee('id="save-payment-button"', false)
            ->assertSee('disabled', false);

        $this->patchJson(route('service-bookings.payment', $booking), [
            'has_paid' => 'yes',
            'payment_type' => 'partial',
            'amount_received' => '30000',
        ])->assertOk()
            ->assertJsonPath('payment_status', ServiceBooking::PARTIALLY_PAID)
            ->assertJsonPath('amount_received', '30000.00')
            ->assertJsonPath('balance', '70000.00');
        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => '30000.00',
            'payment_recorded' => true,
        ]);
        $this->assertDatabaseCount('service_bookings', 1);

        $this->patch(route('service-bookings.appointment', $booking), [
            'appointment_date' => $appointmentDate,
        ])->assertRedirect();

        $this->post(route('service-bookings.confirm', $booking), [
            'appointment_date' => $appointmentDate,
        ])->assertRedirect(route('service-bookings.index', ['tab' => 'confirmed']))
            ->assertSessionHas('success', 'Service booking confirmed.');

        $booking->refresh();
        $this->assertSame(ServiceBooking::CONFIRMED, $booking->status);
        $this->assertSame(ServiceBooking::PARTIALLY_PAID, $booking->payment_status);
        $this->assertSame('30000.00', $booking->payment_amount_received);
        $this->assertSame($appointmentDate, $booking->appointment_date->toDateString());
        $this->assertNotSame($booking->preferred_date->toDateString(), $booking->appointment_date->toDateString());
    }

    public function test_partial_payment_equal_to_total_is_saved_as_paid(): void
    {
        $booking = $this->createBooking();

        $this->actingAs($this->createAdmin(), 'web')
            ->patchJson(route('service-bookings.payment', $booking), [
                'has_paid' => 'yes',
                'payment_type' => 'partial',
                'amount_received' => '100000',
            ])->assertOk()
            ->assertJsonPath('payment_status', ServiceBooking::PAID)
            ->assertJsonPath('amount_received', '100000.00')
            ->assertJsonPath('balance', '0.00');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'payment_status' => ServiceBooking::PAID,
            'payment_amount_received' => '100000.00',
        ]);
    }

    public function test_payment_validation_and_confirmation_requirements_are_enforced(): void
    {
        $booking = $this->createBooking();
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'web')
            ->patchJson(route('service-bookings.payment', $booking), [
                'has_paid' => 'yes',
                'payment_type' => 'partial',
                'amount_received' => '100001',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('amount_received');

        $this->post(route('service-bookings.confirm', $booking), [
            'appointment_date' => now()->addDays(4)->toDateString(),
        ])->assertSessionHasErrors('payment');

        $booking->update(['payment_status' => ServiceBooking::PAID]);
        $this->post(route('service-bookings.confirm', $booking), [
            'appointment_date' => now()->addDays(4)->toDateString(),
        ])->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::PENDING,
            'payment_status' => ServiceBooking::PAID,
        ]);
    }

    public function test_confirmed_booking_can_record_an_additional_payment_and_be_marked_completed(): void
    {
        $this->service->update(['price' => 250000]);
        $appointmentDate = now()->addDays(8)->toDateString();
        $booking = $this->createBooking();
        $booking->update([
            'status' => ServiceBooking::CONFIRMED,
            'appointment_date' => $appointmentDate,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => 100000,
            'payment_recorded' => true,
        ]);
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('service-bookings.index', ['tab' => 'confirmed']))
            ->assertOk()
            ->assertSee('Open')
            ->assertDontSee('View Booking');

        $this->get(route('service-bookings.confirmed.show', $booking))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee($booking->customer_name)
            ->assertSee($booking->customer_phone)
            ->assertSee($this->service->title)
            ->assertSee(now()->addDays(8)->format('M j, Y'))
            ->assertSee('RWF 250,000')
            ->assertSee('RWF 100,000')
            ->assertSee('RWF 150,000')
            ->assertSee('Edit Payment')
            ->assertSee('Mark as Completed');

        $this->post(route('service-bookings.additional-payment', $booking), [
            'amount_received' => '150000',
        ])->assertRedirect(route('service-bookings.confirmed.show', $booking))
            ->assertSessionHas('success', 'Payment information updated.');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::CONFIRMED,
            'payment_status' => ServiceBooking::PAID,
            'payment_amount_received' => '250000.00',
            'payment_recorded' => true,
        ]);
        $this->assertSame($appointmentDate, $booking->fresh()->appointment_date->toDateString());
        $this->assertDatabaseCount('service_bookings', 1);

        $this->get(route('service-bookings.confirmed.show', $booking))
            ->assertOk()
            ->assertSee('RWF 250,000')
            ->assertSee('RWF 0')
            ->assertSee('Payment information updated.');

        $this->get(route('service-bookings.completed.show', $booking))
            ->assertNotFound();

        $this->post(route('service-bookings.complete', $booking))
            ->assertRedirect(route('service-bookings.index', ['tab' => 'completed']))
            ->assertSessionHas('completed_success', 'Service booking marked as completed.');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PAID,
            'payment_amount_received' => '250000.00',
        ]);
        $completedBooking = $booking->fresh();
        $this->assertSame($appointmentDate, $completedBooking->appointment_date->toDateString());
        $this->assertNotNull($completedBooking->completed_at);
        $this->assertDatabaseCount('service_bookings', 1);
        $this->get(route('service-bookings.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('View')
            ->assertDontSee('Mark as Completed')
            ->assertSee('Service booking marked as completed.');

        $this->get(route('service-bookings.completed.show', $booking))
            ->assertOk()
            ->assertSee('Booking and completion details are read-only')
            ->assertSee('Booking information')
            ->assertSee('Appointment information')
            ->assertSee('Payment summary')
            ->assertSee('Payment history')
            ->assertSee('Completion information')
            ->assertSee($completedBooking->completed_at->format('M j, Y'))
            ->assertSee('Individual payment transactions and their dates are not stored')
            ->assertSee('No completion notes are recorded')
            ->assertSee('Back to Service Bookings')
            ->assertSee('Edit Payment')
            ->assertSee(route('service-bookings.additional-payment', $booking), false)
            ->assertDontSee('Update Payment')
            ->assertDontSee('Mark as Completed')
            ->assertDontSee(route('service-bookings.payment', $booking), false)
            ->assertDontSee(route('service-bookings.confirm', $booking), false)
            ->assertDontSee('action="'.route('service-bookings.complete', $booking).'"', false);
    }

    public function test_completed_booking_can_receive_an_additional_payment_without_changing_its_status(): void
    {
        $booking = $this->createBooking();
        $completedAt = now()->subDay()->startOfMinute();
        $appointmentDate = now()->addDays(8)->toDateString();
        $booking->update([
            'status' => ServiceBooking::COMPLETED,
            'appointment_date' => $appointmentDate,
            'completed_at' => $completedAt,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => 65000,
            'payment_recorded' => true,
        ]);
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('service-bookings.completed.show', $booking))
            ->assertOk()
            ->assertSee('Edit Payment')
            ->assertSee('RWF 100,000')
            ->assertSee('RWF 65,000')
            ->assertSee('RWF 35,000')
            ->assertSee('Additional amount received')
            ->assertSee(route('service-bookings.additional-payment', $booking), false)
            ->assertDontSee(route('service-bookings.complete', $booking), false)
            ->assertDontSee(route('service-bookings.confirm', $booking), false);

        $this->from(route('service-bookings.completed.show', $booking))
            ->post(route('service-bookings.additional-payment', $booking), [
                'amount_received' => '35000',
            ])
            ->assertRedirect(route('service-bookings.completed.show', $booking))
            ->assertSessionHas('success', 'Payment information updated.');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PAID,
            'payment_amount_received' => '100000.00',
            'payment_recorded' => true,
        ]);
        $this->assertSame($completedAt->toDateTimeString(), $booking->fresh()->completed_at->toDateTimeString());
        $this->assertSame($appointmentDate, $booking->fresh()->appointment_date->toDateString());
        $this->assertDatabaseCount('service_bookings', 1);

        $this->get(route('service-bookings.completed.show', $booking))
            ->assertOk()
            ->assertSee('RWF 100,000')
            ->assertSee('RWF 0')
            ->assertSee('Paid')
            ->assertSee('data-completed-toast-message="Payment information updated."', false);
    }

    public function test_completed_booking_additional_payment_cannot_exceed_its_balance(): void
    {
        $booking = $this->createBooking();
        $booking->update([
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => 90000,
            'payment_recorded' => true,
        ]);

        $this->actingAs($this->createAdmin(), 'web')
            ->from(route('service-bookings.completed.show', $booking))
            ->post(route('service-bookings.additional-payment', $booking), [
                'amount_received' => '10001',
            ])
            ->assertRedirect(route('service-bookings.completed.show', $booking))
            ->assertSessionHasErrors('amount_received');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => '90000.00',
        ]);
        $this->assertDatabaseCount('service_bookings', 1);
    }

    public function test_completed_paid_booking_payment_can_be_corrected_without_changing_booking_status(): void
    {
        $booking = $this->createBooking();
        $booking->update([
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PAID,
            'payment_amount_received' => 100000,
            'payment_recorded' => true,
        ]);

        $this->actingAs($this->createAdmin(), 'web')
            ->get(route('service-bookings.completed.show', $booking))
            ->assertOk()
            ->assertSee('id="edit-completed-payment"', false)
            ->assertDontSee('id="edit-completed-payment" disabled', false)
            ->assertSee('Total amount received')
            ->assertSee(route('service-bookings.additional-payment', $booking), false)
            ->assertSee('name="payment_mode" value="replace"', false)
            ->assertSee('value="100000.00"', false);

        $this->from(route('service-bookings.completed.show', $booking))
            ->post(route('service-bookings.additional-payment', $booking), [
                'amount_received' => '85000',
                'payment_mode' => 'replace',
            ])
            ->assertRedirect(route('service-bookings.completed.show', $booking))
            ->assertSessionHas('success', 'Payment information updated.');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => '85000.00',
        ]);
        $this->assertDatabaseCount('service_bookings', 1);
        $this->get(route('service-bookings.completed.show', $booking))
            ->assertOk()
            ->assertSee('RWF 15,000')
            ->assertSee('Partially Paid');

        $this->post(route('service-bookings.additional-payment', $booking), [
            'amount_received' => '100000',
            'payment_mode' => 'replace',
        ])->assertRedirect(route('service-bookings.completed.show', $booking));

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PAID,
            'payment_amount_received' => '100000.00',
        ]);
    }

    public function test_completed_booking_displays_status_derived_from_amounts(): void
    {
        $booking = $this->createBooking();
        $booking->update([
            'status' => ServiceBooking::COMPLETED,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => 100000,
            'payment_recorded' => true,
        ]);

        $this->actingAs($this->createAdmin(), 'web')
            ->get(route('service-bookings.completed.show', $booking))
            ->assertOk()
            ->assertSee('class="completed-payment-badge paid">Paid', false)
            ->assertDontSee('class="completed-payment-badge partially_paid"', false);
    }

    public function test_confirmed_payment_update_cannot_exceed_the_remaining_balance(): void
    {
        $booking = $this->createBooking();
        $booking->update([
            'status' => ServiceBooking::CONFIRMED,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => 50000,
            'payment_recorded' => true,
        ]);

        $this->actingAs($this->createAdmin(), 'web')
            ->from(route('service-bookings.confirmed.show', $booking))
            ->post(route('service-bookings.additional-payment', $booking), [
                'amount_received' => '50001',
            ])
            ->assertRedirect(route('service-bookings.confirmed.show', $booking))
            ->assertSessionHasErrors('amount_received');

        $this->assertDatabaseHas('service_bookings', [
            'id' => $booking->id,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => '50000.00',
            'status' => ServiceBooking::CONFIRMED,
        ]);
    }

    public function test_full_payment_records_service_total_and_unavailable_price_has_no_balance_calculation(): void
    {
        $booking = $this->createBooking();
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'web')
            ->patchJson(route('service-bookings.payment', $booking), [
                'has_paid' => 'yes',
                'payment_type' => 'full',
            ])->assertOk()
            ->assertJsonPath('payment_status', ServiceBooking::PAID)
            ->assertJsonPath('amount_received', '100000.00')
            ->assertJsonPath('balance', '0.00');

        $booking->service->update(['price' => 0]);
        $this->patchJson(route('service-bookings.payment', $booking), [
            'has_paid' => 'yes',
            'payment_type' => 'partial',
            'amount_received' => '12500',
        ])->assertOk()
            ->assertJsonPath('payment_status', ServiceBooking::PARTIALLY_PAID)
            ->assertJsonPath('amount_received', '12500.00')
            ->assertJsonPath('total_amount', null)
            ->assertJsonPath('balance', null)
            ->assertJsonPath('has_total', false);
    }

    public function test_each_service_gets_a_distinct_code_and_booking_view_keeps_preferred_and_confirmed_times_separate(): void
    {
        $webDesign = Service::query()->create([
            'title' => 'Web Design',
            'slug' => 'web-design',
            'price' => 100000,
            'classification_name' => $this->service->classification_name,
            'is_in_stock' => true,
        ]);
        $preferredDate = now()->addDays(3)->toDateString();
        $appointmentDate = now()->addDays(8)->toDateString();

        $this->post(route('service-bookings.store', $this->service->slug), [
            'submission_key' => (string) Str::uuid(),
            'customer_name' => 'Website Customer',
            'customer_phone' => '+250780000000',
            'preferred_date' => $preferredDate,
        ])->assertRedirect();

        $this->post(route('service-bookings.store', $webDesign->slug), [
            'submission_key' => (string) Str::uuid(),
            'customer_name' => 'Design Customer',
            'customer_phone' => '+250780000001',
            'preferred_date' => $preferredDate,
        ])->assertRedirect();

        $this->post(route('service-bookings.store', $this->service->slug), [
            'submission_key' => (string) Str::uuid(),
            'customer_name' => 'Second Website Customer',
            'customer_phone' => '+250780000002',
            'preferred_date' => $preferredDate,
        ])->assertRedirect();

        $bookings = ServiceBooking::query()->orderBy('id')->get();
        $websiteBooking = $bookings[0];
        $webDesignBooking = $bookings[1];
        $secondWebsiteBooking = $bookings[2];

        $this->assertMatchesRegularExpression('/^THW-[A-Z2-9]{4}$/', $websiteBooking->reference);
        $this->assertMatchesRegularExpression('/^TH[A-Z0-9]-[A-Z2-9]{4}$/', $webDesignBooking->reference);
        $this->assertNotSame(substr($websiteBooking->reference, 2, 1), substr($webDesignBooking->reference, 2, 1));
        $this->assertNotSame($websiteBooking->reference, $secondWebsiteBooking->reference);
        $this->assertSame(substr($websiteBooking->reference, 2, 1), substr($secondWebsiteBooking->reference, 2, 1));

        $websiteBooking->update([
            'appointment_date' => $appointmentDate,
            'appointment_time' => 'afternoon',
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
            'payment_amount_received' => 25000,
            'payment_recorded' => true,
        ]);

        PermissionCatalog::sync();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('administrator');

        $this->actingAs($admin, 'web')
            ->get(route('service-bookings.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee($websiteBooking->preferred_date->format('M j, Y'))
            ->assertDontSee('Preferred Time')
            ->assertSee('Partially Paid')
            ->assertDontSee($appointmentDate);

        $this->get(route('service-bookings.show', $websiteBooking))
            ->assertOk()
            ->assertSee($websiteBooking->reference)
            ->assertSee($websiteBooking->preferred_date->format('M j, Y'))
            ->assertDontSee('Preferred Time')
            ->assertSee($websiteBooking->appointment_date->format('M j, Y'))
            ->assertSee('Afternoon')
            ->assertSee('Partially Paid');

        $this->assertSame(ServiceBooking::PENDING, $websiteBooking->fresh()->status);
        $this->assertSame(ServiceBooking::PARTIALLY_PAID, $websiteBooking->fresh()->payment_status);
    }

    public function test_existing_long_booking_references_are_replaced_without_changing_booking_data(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Legacy Customer',
            'email' => 'legacy-customer@example.com',
            'phone' => '+250780000003',
            'password' => 'customer-password',
        ]);
        $booking = ServiceBooking::query()->create([
            'reference' => 'SB-'.str_repeat('A', 36),
            'service_id' => $this->service->id,
            'customer_id' => $customer->id,
            'submission_key' => (string) Str::uuid(),
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => 'morning',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => 'afternoon',
            'requirements' => 'Keep original booking information.',
            'status' => ServiceBooking::CONFIRMED,
            'payment_status' => ServiceBooking::PARTIALLY_PAID,
        ]);
        $existingShortReference = ServiceBooking::query()->create([
            'reference' => 'THW-AB23',
            'service_id' => $this->service->id,
            'submission_key' => (string) Str::uuid(),
            'customer_name' => 'Existing Short Reference Customer',
            'customer_phone' => '+250780000004',
            'preferred_date' => now()->addDays(4)->toDateString(),
            'preferred_time' => 'flexible',
            'status' => ServiceBooking::PENDING,
            'payment_status' => ServiceBooking::NOT_PAID,
        ]);
        $originalId = $booking->id;

        app(ServiceBookingReferenceGenerator::class)->refreshExistingReferences();

        $booking->refresh();
        $existingShortReference->refresh();
        $this->assertSame($originalId, $booking->id);
        $this->assertMatchesRegularExpression('/^THW-[A-Z2-9]{4}$/', $booking->reference);
        $this->assertSame('THW-AB23', $existingShortReference->reference);
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertSame($this->service->id, $booking->service_id);
        $this->assertSame('afternoon', $booking->appointment_time);
        $this->assertSame(ServiceBooking::CONFIRMED, $booking->status);
        $this->assertSame(ServiceBooking::PARTIALLY_PAID, $booking->payment_status);
        $this->assertSame('Keep original booking information.', $booking->requirements);

        $this->service->update(['title' => 'Renamed Website Service']);
        $nextReference = app(ServiceBookingReferenceGenerator::class)->generateFor($this->service->fresh());
        $this->assertStringStartsWith('THW-', $nextReference);
    }

    public function test_invalid_or_past_booking_dates_are_rejected(): void
    {
        $this->from(route('service.show', $this->service->slug))
            ->post(route('service-bookings.store', $this->service->slug), [
                'submission_key' => (string) Str::uuid(),
                'customer_name' => 'Guest Customer',
                'customer_phone' => '+250780000000',
                'preferred_date' => now()->subDay()->toDateString(),
                'preferred_time' => 'afternoon',
            ])
            ->assertSessionHasErrors('preferred_date');

        $this->assertDatabaseCount('service_bookings', 0);
    }

    private function createBooking(): ServiceBooking
    {
        return ServiceBooking::query()->create([
            'reference' => app(ServiceBookingReferenceGenerator::class)->generateFor($this->service),
            'service_id' => $this->service->id,
            'submission_key' => (string) Str::uuid(),
            'customer_name' => 'Payment Test Customer',
            'customer_phone' => '+250780000000',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => 'flexible',
            'status' => ServiceBooking::PENDING,
            'payment_status' => ServiceBooking::NOT_PAID,
        ]);
    }

    private function createAdmin(): User
    {
        PermissionCatalog::sync();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('administrator');

        return $admin;
    }
}
