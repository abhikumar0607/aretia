<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientRegistrationOtpTest extends TestCase
{
    use RefreshDatabase;

    private function registrationPayload(): array
    {
        return [
            'name' => 'Jane Client',
            'company' => 'Acme Corp',
            'email' => 'jane@acme.test',
            'phone' => '9876543210',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ];
    }

    public function test_register_sends_otp_and_redirects_without_creating_user(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registrationPayload())
            ->assertRedirect(route('register.verify'));

        $this->assertDatabaseMissing('users', ['email' => 'jane@acme.test']);
        $this->assertDatabaseMissing('companies', ['email' => 'jane@acme.test']);

        Notification::assertSentOnDemand(
            RegistrationOtpNotification::class,
            fn (RegistrationOtpNotification $notification, array $channels, object $notifiable) => ($notifiable->routes['mail'] ?? null) === 'jane@acme.test'
        );
    }

    public function test_wrong_otp_shows_error(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registrationPayload());
        $this->get(route('register.verify'))->assertOk();

        $this->post(route('register.verify.submit'), ['otp' => '000000'])
            ->assertRedirect(route('register.verify'))
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'OTP not match');

        $this->assertDatabaseMissing('users', ['email' => 'jane@acme.test']);
    }

    public function test_correct_otp_creates_account_and_redirects_to_onboarding(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registrationPayload());

        $otp = null;
        Notification::assertSentOnDemand(
            RegistrationOtpNotification::class,
            function (RegistrationOtpNotification $notification) use (&$otp) {
                $otp = $notification->otp;

                return true;
            }
        );

        $this->post(route('register.verify.submit'), ['otp' => $otp])
            ->assertRedirect(route('client.onboarding'));

        $this->assertDatabaseHas('users', [
            'email' => 'jane@acme.test',
            'name' => 'Jane Client',
        ]);
        $this->assertDatabaseHas('companies', [
            'email' => 'jane@acme.test',
            'name' => 'Acme Corp',
        ]);

        $this->assertAuthenticatedAs(User::query()->where('email', 'jane@acme.test')->first());
    }

    public function test_resend_otp_sends_new_code(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->registrationPayload());
        Notification::assertSentOnDemand(RegistrationOtpNotification::class, 1);

        $this->post(route('register.resend-otp'))
            ->assertRedirect(route('register.verify'));

        Notification::assertSentOnDemand(RegistrationOtpNotification::class, 2);
    }
}
