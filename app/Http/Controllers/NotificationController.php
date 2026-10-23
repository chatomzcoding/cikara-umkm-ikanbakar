<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $target = $notification->data['url'] ?? null;

        return $this->safeRedirect($target) ?? back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    private function safeRedirect(mixed $target): ?RedirectResponse
    {
        if (! is_string($target) || $target === '') {
            return null;
        }

        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return redirect()->to($target);
        }

        $appUrl = rtrim(url('/'), '/');

        if (str_starts_with($target, $appUrl.'/')) {
            return redirect()->to($target);
        }

        return null;
    }
}
