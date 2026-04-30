<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @param  array{status?: string, message?: string|null, warning?: string|null}  $dispatchResult
     */
    protected function withPosKantinDispatchNotice(RedirectResponse $response, array $dispatchResult): RedirectResponse
    {
        $status = $dispatchResult['status'] ?? null;
        $message = $dispatchResult['message'] ?? $dispatchResult['warning'] ?? null;

        if (! is_string($status) || ! in_array($status, ['applied', 'queued', 'unsupported', 'failed'], true)) {
            return $response;
        }

        if (! is_string($message) || $message === '') {
            return $response;
        }

        return $response->with([
            'sync_notice_status' => $status,
            'sync_notice_message' => $message,
        ]);
    }
}
