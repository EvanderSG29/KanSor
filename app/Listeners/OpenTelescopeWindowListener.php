<?php

namespace App\Listeners;

use App\Events\OpenTelescopeWindow;
use Native\Desktop\Facades\Window;

class OpenTelescopeWindowListener
{
    private const WINDOW_ID = 'telescope-github';

    private const WINDOW_TITLE = 'Laravel Telescope';

    private const WINDOW_URL = 'https://github.com/laravel/telescope';

    public function handle(OpenTelescopeWindow $event): void
    {
        Window::open(self::WINDOW_ID)
            ->title(self::WINDOW_TITLE)
            ->url(self::WINDOW_URL)
            ->width(1280)
            ->height(900)
            ->minWidth(1024)
            ->minHeight(720)
            ->preventLeaveDomain()
            ->hideMenu();
    }
}
