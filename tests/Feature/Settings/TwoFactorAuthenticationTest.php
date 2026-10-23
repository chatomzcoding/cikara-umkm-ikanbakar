<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('halaman 2FA dapat dibuka setelah konfirmasi password', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertSee('Autentikasi Dua Langkah')
        ->assertSee('Nonaktif');
});

test('halaman 2FA meminta konfirmasi password terlebih dahulu', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('two-factor.show'))
        ->assertRedirect(route('password.confirm'));
});

test('halaman 2FA ditolak jika fitur dinonaktifkan', function () {
    config(['fortify.features' => []]);

    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'))
        ->assertForbidden();
});

test('setup 2FA yang ditinggalkan sebelum dikonfirmasi dinonaktifkan', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    Livewire::actingAs($user)
        ->test('settings.two-factor')
        ->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('2FA dapat diaktifkan dan dikonfirmasi dengan kode yang valid', function () {
    $user = User::factory()->create();
    session(['auth.password_confirmed_at' => time()]);

    $component = Livewire::actingAs($user)
        ->test('settings.two-factor')
        ->call('enable')
        ->assertSet('showVerificationStep', true);

    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));

    $component->set('code', $code)
        ->call('confirm')
        ->assertHasNoErrors()
        ->assertSet('twoFactorEnabled', true);

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull()
        ->and($component->get('recoveryCodes'))->toHaveCount(8);
});

test('kode 2FA yang salah ditolak', function () {
    $user = User::factory()->create();
    session(['auth.password_confirmed_at' => time()]);

    Livewire::actingAs($user)
        ->test('settings.two-factor')
        ->call('enable')
        ->set('code', '000000')
        ->call('confirm')
        ->assertHasErrors(['code'])
        ->assertSet('twoFactorEnabled', false);

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('aksi 2FA diarahkan ke konfirmasi password jika konfirmasi kedaluwarsa', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('settings.two-factor')
        ->call('enable')
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->two_factor_secret)->toBeNull();
});
