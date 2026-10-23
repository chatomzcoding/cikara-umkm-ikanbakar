<?php

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\UserImpersonationService;
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

test('super admin dapat memulai dan menghentikan impersonasi', function () {
    $target = User::factory()->create(['name' => 'User Target']);

    $this->actingAs($this->admin);
    app(UserImpersonationService::class)->start($this->admin, $target);

    $this->assertAuthenticatedAs($target);
    expect(session('impersonation.impersonator_id'))->toBe($this->admin->id)
        ->and(session('impersonation.impersonator_name'))->toBe($this->admin->name)
        ->and(AuditLog::query()->where('event', 'impersonation_started')->where('auditable_id', $target->id)->exists())->toBeTrue();

    $this->post(route('impersonation.stop'))
        ->assertRedirect(route('superadmin.sistem.user.index'));

    $this->assertAuthenticatedAs($this->admin);
    expect(session()->has('impersonation'))->toBeFalse()
        ->and(AuditLog::query()->where('event', 'impersonation_ended')->where('auditable_id', $target->id)->exists())->toBeTrue();
});

test('tombol manajemen user dapat memulai impersonasi', function () {
    $target = User::factory()->create();

    Livewire::actingAs($this->admin)
        ->test('superadmin.sistem.user.index')
        ->call('impersonateUser', $target->id)
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($target);
});

test('pengguna tanpa permission tidak dapat melakukan impersonasi', function () {
    $viewPermission = Permission::create(['name' => 'users.view', 'display_name' => 'View']);
    $role = Role::create(['name' => 'viewer', 'display_name' => 'Viewer']);
    $role->givePermission($viewPermission);
    $actor = User::factory()->create();
    $actor->addRole($role);
    $target = User::factory()->create();

    Livewire::actingAs($actor)
        ->test('superadmin.sistem.user.index')
        ->call('impersonateUser', $target->id)
        ->assertForbidden();

    $this->assertAuthenticatedAs($actor);
});

test('permission impersonate tetap tidak dapat digunakan untuk mengambil akun super admin', function () {
    $permission = Permission::create(['name' => 'users.impersonate', 'display_name' => 'Impersonate']);
    $actor = User::factory()->create();
    $actor->givePermission($permission);
    $target = User::factory()->create();
    $target->addRole($this->superadminRole);

    $this->actingAs($actor);

    expect(fn () => app(UserImpersonationService::class)->start($actor, $target))
        ->toThrow(ValidationException::class);

    $this->assertAuthenticatedAs($actor);
});

test('akun nonaktif dan akun sendiri tidak dapat diimpersonasi', function () {
    $inactive = User::factory()->create(['status' => UserStatus::Inactive]);
    $service = app(UserImpersonationService::class);
    $this->actingAs($this->admin);

    expect(fn () => $service->start($this->admin, $inactive))->toThrow(ValidationException::class)
        ->and(fn () => $service->start($this->admin, $this->admin))->toThrow(ValidationException::class);

    $this->assertAuthenticatedAs($this->admin);
});

test('impersonasi bertingkat ditolak', function () {
    $firstTarget = User::factory()->create();
    $secondTarget = User::factory()->create();
    $service = app(UserImpersonationService::class);
    $this->actingAs($this->admin);
    $service->start($this->admin, $firstTarget);

    expect(fn () => $service->start($firstTarget, $secondTarget))
        ->toThrow(ValidationException::class);

    $this->assertAuthenticatedAs($firstTarget);
});
