<?php

namespace App\Http\Controllers;

use App\Models\UpdateSetting;
use Illuminate\Http\Request;

class UpdateSettingsController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'interval' => 'required|in:daily,weekly,monthly,quarterly,yearly',
        ]);

        $setting = UpdateSetting::firstOrCreate([]);
        $setting->interval = $request->interval;
        $setting->save();

        return redirect()->back()->with('success', 'Pengaturan interval update berhasil disimpan.');
    }
}
