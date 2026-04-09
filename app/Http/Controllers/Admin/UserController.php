<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branch')->orderBy('name')->paginate(20);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:super_admin,admin,operator,viewer',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ], [
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        User::create($data);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:super_admin,admin,operator,viewer',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $passwordChanged = !empty($data['password']);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $currentUserId = (int) $request->user()->getKey();

        if ((int) $user->getKey() === $currentUserId && !$data['is_active']) {
            return back()->withErrors(['is_active' => 'No puedes desactivar tu propio usuario.']);
        }

        if ((int) $user->getKey() === $currentUserId && $request->user()->isAdmin() && !in_array($data['role'], ['super_admin', 'admin'], true)) {
            return back()->withErrors(['role' => 'No puedes cambiar tu propio rol a un perfil sin acceso administrativo.']);
        }

        $user->update($data);

        $message = 'Usuario actualizado correctamente.';
        if ($passwordChanged) {
            $message .= ' La contraseña fue cambiada.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request, User $user)
    {
        if ((int) $user->getKey() === (int) $request->user()->getKey()) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propio usuario.']);
        }

        if ($user->role === 'super_admin') {
            $remainingSuperAdmins = User::where('role', 'super_admin')
                ->whereKeyNot($user->getKey())
                ->count();

            if ($remainingSuperAdmins === 0) {
                return back()->withErrors(['user' => 'No se puede eliminar el último super administrador del sistema.']);
            }
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}
