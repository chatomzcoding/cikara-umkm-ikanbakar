<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->superadminRole = Role::create(['name' => 'superadmin', 'display_name' => 'Super Admin']);
    $this->superadmin = User::factory()->create();
    $this->superadmin->addRole($this->superadminRole);
});

test('perubahan model dicatat dengan pelaku dan field sensitif dimasking', function () {
    $this->actingAs($this->superadmin);
    $target = User::factory()->create(['name' => 'Target Audit', 'password' => 'Rahasia-123']);
    $target->update(['name' => 'Target Diperbarui', 'password' => 'Rahasia-Baru']);

    $created = AuditLog::query()->where('auditable_type', User::class)->where('auditable_id', $target->id)->where('event', 'created')->sole();
    $updated = AuditLog::query()->where('auditable_type', User::class)->where('auditable_id', $target->id)->where('event', 'updated')->sole();

    expect($created->user_id)->toBe($this->superadmin->id)
        ->and($created->new_values['password'])->toBe('[REDACTED]')
        ->and($updated->old_values['name'])->toBe('Target Audit')
        ->and($updated->new_values['name'])->toBe('Target Diperbarui')
        ->and($updated->new_values['password'])->toBe('[REDACTED]');
});

test('bulk delete memiliki batch UUID yang sama', function () {
    $targets = User::factory()->count(2)->create();
    AuditLog::query()->delete();

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->set('selected', $targets->pluck('id')->all())
        ->call('bulkDelete');

    $logs = AuditLog::query()->where('event', 'soft_deleted')->whereIn('auditable_id', $targets->pluck('id'))->get();

    expect($logs)->toHaveCount(2)
        ->and($logs->pluck('batch_uuid')->filter()->unique())->toHaveCount(1);
});

test('login dan export dicatat sebagai aktivitas sistem', function () {
    Event::dispatch(new Login('web', $this->superadmin, false));

    Livewire::actingAs($this->superadmin)
        ->test('superadmin.sistem.user.index')
        ->call('exportExcel')
        ->assertFileDownloaded();

    expect(AuditLog::query()->where('event', 'login')->where('user_id', $this->superadmin->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'exported')->where('user_id', $this->superadmin->id)->whereJsonContains('metadata->format', 'xlsx')->exists())->toBeTrue();
});

test('halaman audit membutuhkan permission khusus', function () {
    $viewerRole = Role::create(['name' => 'audit-viewer', 'display_name' => 'Audit Viewer']);
    $permission = Permission::create(['name' => 'audit-logs.view', 'display_name' => 'Melihat Audit']);
    $viewerRole->givePermission($permission);
    $viewer = User::factory()->create();
    $viewer->addRole($viewerRole);

    $this->actingAs($viewer)
        ->get(route('superadmin.sistem.audit-log.index'))
        ->assertSuccessful();

    $this->actingAs(User::factory()->create())
        ->get(route('superadmin.sistem.audit-log.index'))
        ->assertForbidden();
});

test('record audit tidak dapat diubah atau dihapus melalui model', function () {
    $log = AuditLog::query()->firstOrFail();

    expect(fn () => $log->update(['description' => 'Diubah']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});
