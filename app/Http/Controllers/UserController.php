<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|in:ADMINISTRADOR,OPERADOR',
            'status' => 'nullable|in:activo,desactivado',
        ]);
        $status = $filters['status'] ?? 'activo';
        $users = match ($status) {
            'desactivado' => User::onlyTrashed(),
            default => User::query(),
        };

        $users = $users
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%")->orWhere('dni', $search);
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        $activeCounts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $inactiveCount = User::onlyTrashed()->count();

        return view('users.index', compact('users', 'filters', 'status', 'activeCounts', 'inactiveCount'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'dni' => ['sometimes', 'nullable', 'string', 'regex:/\A[0-9]{8}\z/', Rule::unique('users', 'dni')],
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMINISTRADOR,OPERADOR',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $actor = User::lockForUpdate()->find($request->user()->id);
            abort_unless($actor?->can('administrar-almacen'), 403);

            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->dni = $validated['dni'] ?? null;
            $user->role = $validated['role'];
            $user->save();
        }, 3);

        return to_route('users.index')->with('success', 'Cuenta creada.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'dni' => ['sometimes', 'nullable', 'string', 'regex:/\A[0-9]{8}\z/', Rule::unique('users', 'dni')->ignore($user)],
            'role' => 'required|in:ADMINISTRADOR,OPERADOR',
        ]);

        DB::transaction(function () use ($request, $user, $validated) {
            $actorId = $request->user()->id;
            $account = $this->lockManagedAccount($actorId, $user->id);

            if ($account->id === $actorId && $account->role !== $validated['role']) {
                throw ValidationException::withMessages(['role' => 'Tu propio rol debe permanecer como administrador.']);
            }

            $account->fill(['name' => $validated['name'], 'email' => $validated['email']]);
            if (array_key_exists('dni', $validated)) {
                $account->dni = $validated['dni'];
            }
            $account->role = $validated['role'];
            if ($account->isDirty('email')) {
                $account->email_verified_at = null;
            }
            $account->save();
        }, 3);

        return to_route('users.index')->with('success', 'Cuenta actualizada.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['cuenta' => 'Tu propia cuenta debe permanecer activa.']);
        }

        DB::transaction(function () use ($request, $user) {
            $this->lockManagedAccount($request->user()->id, $user->id)->delete();
        }, 3);

        return to_route('users.index', ['status' => 'activo'])
            ->with('success', 'Se desactivó la cuenta de '.$user->name.' ('.$user->email.'). Ahora estás viendo las cuentas activas.');
    }

    private function lockManagedAccount(int $actorId, int $targetId): User
    {
        $accounts = User::whereKey([$actorId, $targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        abort_unless($accounts->get($actorId)?->can('administrar-almacen'), 403);
        abort_unless($accounts->has($targetId), 404);

        return $accounts->get($targetId);
    }

    public function restore(Request $request, User $user)
    {
        DB::transaction(function () use ($request,$user) {
            $accounts=User::withTrashed()->whereKey([$request->user()->id,$user->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $actor=$accounts->get($request->user()->id);
            abort_unless($actor && !$actor->trashed() && $actor->can('administrar-almacen'),403);
            $account=$accounts->get($user->id);
            abort_unless($account,404);
            if(!$account->trashed()) throw ValidationException::withMessages(['cuenta'=>'Esta cuenta ya está activa.']);
            $account->restore();
        },3);
        return to_route('users.index')->with('success','Cuenta reactivada. Conserva su historial y sus credenciales anteriores.');
    }
}
