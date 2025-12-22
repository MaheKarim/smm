<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $settings = $user->getOrCreateSettings();
        
        return view('settings.index', compact('user', 'settings'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);
        
        $user->update($validated);
        
        return redirect()->route('settings.index')
            ->with('success', 'Profile updated successfully.');
    }

    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark,system'],
            'timezone' => ['required', 'string', 'max:100'],
            'date_format' => ['required', 'string', 'max:20'],
            'email_notifications' => ['boolean'],
            'push_notifications' => ['boolean'],
        ]);
        
        $settings = $user->getOrCreateSettings();
        $settings->update([
            'theme' => $validated['theme'],
            'timezone' => $validated['timezone'],
            'date_format' => $validated['date_format'],
            'email_notifications' => $request->boolean('email_notifications'),
            'push_notifications' => $request->boolean('push_notifications'),
        ]);
        
        return redirect()->route('settings.index')
            ->with('success', 'Preferences updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        
        return redirect()->route('settings.index')
            ->with('success', 'Password updated successfully.');
    }
}

