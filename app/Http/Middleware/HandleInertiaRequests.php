<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\SystemSetting;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->only(
                    'id', 'first_name', 'last_name', 'role', 'user_photo'
                ) : null,
                // FIX: Restored the 'data' wrapper and 'created_at' so the Vue template doesn't crash
                'notifications' => $request->user() ? $request->user()->unreadNotifications->map(fn ($n) => [
                    'id' => $n->id,
                    'created_at' => $n->created_at, 
                    'data' => [
                        'title' => $n->data['title'] ?? 'Notification',
                        'message' => $n->data['message'] ?? '',
                        'url' => $n->data['url'] ?? '#',
                    ],
                ]) : [],
            ],
            // This shares the settings globally to $page.props.settings in Vue
            'settings' => SystemSetting::select(
                'theme_color', 'lgu_name', 'lgu_logo_path', 'office_name', 'office_logo_path'
            )->first() ?? new SystemSetting(),
            'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
        ],
        ];
    }
}