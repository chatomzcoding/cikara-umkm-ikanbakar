<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Component;
use Modules\Superadmin\Models\DataPokok;

class Lineone extends Component
{
    /**
     * Create a new component instance.
     */
    public string $menu;

    public bool $menuButton;

    public bool $dashboard;

    public bool $sidebar;

    public function __construct($menu = 'dashboard', $menuButton = true, $dashboard = false, $sidebar = false)
    {
        $this->menu = $menu;
        $this->menuButton = $menuButton;
        $this->dashboard = $dashboard;
        $this->sidebar = $sidebar;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $user = Auth::user();
        $notifications = $user?->notifications()->latest()->limit(15)->get() ?? collect();
        $notificationGroups = [
            'all' => $notifications,
            'alert' => $notifications->where('data.category', 'alert'),
            'event' => $notifications->where('data.category', 'event'),
            'activity' => $notifications->where('data.category', 'activity'),
        ];
        $unreadNotificationCount = $user?->unreadNotifications()->count() ?? 0;
        $subMenuDefault = Schema::hasTable('data_pokok')
            ? (DataPokok::query()->value('sub_menu_default') ?? 'hide')
            : 'hide';

        return view('components.lineone', compact(
            'user',
            'notificationGroups',
            'unreadNotificationCount',
            'subMenuDefault',
        ));
    }
}
