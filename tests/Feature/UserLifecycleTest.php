<?php

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\UserLifecycleService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->superadminRole = Role::create([
        'name' => 'superadmin',
        'display_name' => 'Super Admin',
    ]);
    $this->admin = User::factory()->create();
    $this->admin->addRole($this->superadminRole);
});

test('admin dapat menangguhkan dan mengaktifkan kembali pengguna', function () {
    $target = User::factory()->create();

    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.user.index')
        ->call('suspendUser', $target->id, 'Pelanggaran kebijakan')
        ->assertDispatched('notify');

    $target->refresh();
    expect($target->status)->toBe(UserStatus::Suspended)
        ->and($target->suspension_reason)->toBe('Pelanggaran kebijakan')
        ->and($target->suspended_by)->toBe($this->admin->id)
        ->and($target->suspended_at)->not->toBeNull();

    expect(AuditLog::query()->where('event', 'user_suspended')->where('auditable_id', $target->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.user.index')
        ->call('activateUser', $target->id)
        ->assertDispatched('notify');

    $target->refresh();
    expect($target->status)->toBe(UserStatus::Active)
        ->and($target->suspension_reason)->toBeNull()
        ->and($target->suspended_at)->toBeNull();
});

test('admin dapat menonaktifkan pengguna dengan alasan', function () {
    $target = User::factory()->create();

    app(UserLifecycleService::class)->deactivate($target, $this->admin, 'Kontrak berakhir');

    $target->refresh();
    expect($target->status)->toBe(UserStatus::Inactive)
        ->and($target->deactivation_reason)->toBe('Kontrak berakhir')
        ->and($target->deactivated_by)->toBe($this->admin->id);
});

test('akun nonaktif dan ditangguhkan tidak dapat login', function (UserStatus $status) {
    $user = User::factory()->create(['status' => $status]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
})->with([UserStatus::Suspended, UserStatus::Inactive]);

test('sesi pengguna dihentikan setelah statusnya tidak aktif', function () {
    $user = User::factory()->create(['status' => UserStatus::Inactive]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('admin tidak dapat menangguhkan diri sendiri', function () {
    expect(fn () => app(UserLifecycleService::class)->suspend($this->admin, $this->admin, 'Alasan'))
        ->toThrow(ValidationException::class);

    expect($this->admin->fresh()->status)->toBe(UserStatus::Active);
});

test('super admin aktif terakhir tidak dapat dinonaktifkan', function () {
    $actor = User::factory()->create();

    expect(fn () => app(UserLifecycleService::class)->deactivate($this->admin, $actor, 'Alasan'))
        ->toThrow(ValidationException::class);

    expect($this->admin->fresh()->status)->toBe(UserStatus::Active);
});

test('filter status hanya menampilkan pengguna yang sesuai', function () {
    User::factory()->create(['name' => 'Pengguna Suspend', 'status' => UserStatus::Suspended]);
    User::factory()->create(['name' => 'Pengguna Aktif', 'status' => UserStatus::Active]);

    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.user.index')
        ->set('filter.status', UserStatus::Suspended->value)
        ->assertSee('Pengguna Suspend')
        ->assertDontSee('Pengguna Aktif');
});
