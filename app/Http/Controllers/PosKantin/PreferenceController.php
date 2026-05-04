<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\StorePreferenceRequest;
use App\Http\Requests\PosKantin\UpdatePreferenceRequest;
use App\Models\Preference;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PreferenceController extends Controller
{
    public function index(): View
    {
        $preferences = Preference::query()
            ->whereBelongsTo(auth()->user())
            ->pluck('value', 'key');

        return view('pos-kantin.preferences.index', [
            'preferences' => [
                'sync_interval' => $preferences['sync_interval'] ?? '60',
                'theme' => $preferences['theme'] ?? 'system',
                'rows_per_page' => $preferences['rows_per_page'] ?? '10',
                'offline_session_days' => $preferences['offline_session_days'] ?? (string) config('services.pos_kantin.offline_login_days', 30),
            ],
        ]);
    }

    public function store(StorePreferenceRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            Preference::query()->updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'key' => $key,
                ],
                [
                    'value' => (string) $value,
                ],
            );
        }

        return redirect()
            ->route('pos-kantin.preferences.index')
            ->with('status', 'Preferensi berhasil disimpan.');
    }

    public function update(UpdatePreferenceRequest $request, Preference $preference): RedirectResponse
    {
        abort_unless($preference->user_id === auth()->id(), 403);

        $preference->update([
            'value' => (string) ($request->validated()['value'] ?? ''),
        ]);

        return redirect()
            ->route('pos-kantin.preferences.index')
            ->with('status', 'Preferensi berhasil diperbarui.');
    }
}
