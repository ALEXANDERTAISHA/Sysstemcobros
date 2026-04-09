<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'systemLogoUrl' => AppSetting::systemLogoUrl(),
            'hasSystemLogo' => (bool) AppSetting::systemLogoPath(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);
        unset($data['avatar'], $data['remove_avatar']);

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.required' => 'Debes ingresar tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Debes ingresar una nueva contraseña.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Contraseña cambiada correctamente.');
    }

    public function updateSystemLogo(Request $request)
    {
        $data = $request->validate([
            'system_logo' => ['nullable', 'image', 'max:2048'],
            'remove_system_logo' => ['nullable', 'boolean'],
        ]);

        $currentPath = AppSetting::systemLogoPath();

        if (($data['remove_system_logo'] ?? false) && $currentPath) {
            Storage::disk('public')->delete($currentPath);
            AppSetting::setValue('system_logo_path', null);
            $currentPath = null;
        }

        if ($request->hasFile('system_logo')) {
            if ($currentPath) {
                Storage::disk('public')->delete($currentPath);
            }

            $storedPath = $request->file('system_logo')->store('system-brand', 'public');
            AppSetting::setValue('system_logo_path', $storedPath);
        }

        return redirect()->route('profile.edit')->with('success', 'Logo del sistema actualizado correctamente.');
    }
}
