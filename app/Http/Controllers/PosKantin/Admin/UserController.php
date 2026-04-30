<?php

namespace App\Http\Controllers\PosKantin\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosKantin\Admin\StoreUserRequest;
use App\Http\Requests\PosKantin\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\PosKantin\PosKantinMutationDispatcher;
use App\Services\PosKantin\UserSyncPayloadFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pos-kantin.admin.users.index', [
            'filters' => $request->only(['role', 'active']),
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('pos-kantin.admin.users.create');
    }

    public function store(
        StoreUserRequest $request,
        PosKantinMutationDispatcher $dispatcher,
        UserSyncPayloadFactory $userSyncPayloadFactory,
    ): RedirectResponse {
        $validated = $request->validated();
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'active' => $validated['active'] ?? false,
            'status' => ($validated['active'] ?? false) ? User::STATUS_ACTIVE : User::STATUS_INACTIVE,
        ]);

        $dispatchResult = $dispatcher->dispatch('saveUser', [$userSyncPayloadFactory->make($user, $validated['password'])], [
            'entity' => 'user',
            'id' => $user->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('pos-kantin.admin.users.index')
                ->with('status', 'Pengguna berhasil ditambahkan.'),
            $dispatchResult,
        );
    }

    public function edit(User $user): View
    {
        return view('pos-kantin.admin.users.edit', [
            'userModel' => $user,
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        PosKantinMutationDispatcher $dispatcher,
        UserSyncPayloadFactory $userSyncPayloadFactory,
    ): RedirectResponse {
        $validated = $request->validated();
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'active' => $validated['active'] ?? false,
            'status' => ($validated['active'] ?? false) ? User::STATUS_ACTIVE : User::STATUS_INACTIVE,
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        $dispatchResult = $dispatcher->dispatch('saveUser', [$userSyncPayloadFactory->make($user, $validated['password'] ?? null)], [
            'entity' => 'user',
            'id' => $user->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('pos-kantin.admin.users.index')
                ->with('status', 'Pengguna berhasil diperbarui.'),
            $dispatchResult,
        );
    }

    public function destroy(
        User $user,
        PosKantinMutationDispatcher $dispatcher,
        UserSyncPayloadFactory $userSyncPayloadFactory,
    ): RedirectResponse {
        if ($user->isAdmin() && $user->active && User::query()->active()->admin()->count() <= 1) {
            return back()->with('error', 'Admin aktif terakhir tidak boleh dinonaktifkan.');
        }

        $user->fill([
            'active' => false,
            'status' => User::STATUS_INACTIVE,
        ])->save();

        $dispatchResult = $dispatcher->dispatch('saveUser', [$userSyncPayloadFactory->make($user)], [
            'entity' => 'user',
            'id' => $user->getKey(),
        ]);

        return $this->withPosKantinDispatchNotice(
            redirect()
                ->route('pos-kantin.admin.users.index')
                ->with('status', 'Pengguna berhasil dinonaktifkan.'),
            $dispatchResult,
        );
    }
}
