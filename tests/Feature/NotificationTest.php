<?php

use App\Models\User;
use App\Services\NotificationService;

test('notification service stores every supported category', function () {
    $user = User::factory()->create();
    $service = app(NotificationService::class);

    $service->alert($user, 'Data belum lengkap', 'Lengkapi data pokok.', [
        'severity' => 'error',
        'url' => '/dashboard',
    ]);
    $service->event($user, 'Rapat besok', 'Rapat dimulai pukul 09.00.');
    $service->log($user, 'Data diperbarui', 'Profil berhasil diperbarui.');

    expect($user->notifications()->count())->toBe(3)
        ->and($user->notifications()->pluck('data')->pluck('category')->all())
        ->toContain('alert', 'event', 'activity')
        ->and($user->unreadNotifications()->count())->toBe(3);
});

test('user can mark their notification as read and follow its internal url', function () {
    $user = User::factory()->create();
    app(NotificationService::class)->alert($user, 'Perlu tindakan', 'Periksa data.', [
        'url' => '/dashboard',
    ]);
    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->patch(route('notifications.read', $notification))
        ->assertRedirect('/dashboard');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user cannot mark another users notification as read', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    app(NotificationService::class)->event($owner, 'Agenda', 'Agenda pribadi.');
    $notification = $owner->notifications()->first();

    $this->actingAs($otherUser)
        ->patch(route('notifications.read', $notification))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('notification dropdown displays database notifications', function () {
    $user = User::factory()->create();
    app(NotificationService::class)->activity($user, 'Profil diperbarui', 'Foto profil berhasil diganti.');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profil diperbarui')
        ->assertSee('Foto profil berhasil diganti.')
        ->assertSee('Aktivitas');
});
