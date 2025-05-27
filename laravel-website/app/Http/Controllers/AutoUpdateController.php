<?php

namespace App\Http\Controllers;

use App\Models\UpdateHistory;
use App\Models\UpdateLog;
use App\Models\UpdateSetting;
use Illuminate\Http\Request;

class AutoUpdateController extends Controller
{
    public function index()
    {
        $currentInterval = UpdateSetting::first()->interval ?? 'daily';
        return view('autoUpdateLog', compact('currentInterval'));
    }

    public function fetchUpdateHistory()
    {
        $updateHistory = UpdateHistory::with('updateLogs')
            ->orderBy('update_history_id', 'desc')
            ->get();

        return response()->json($updateHistory);
    }

    public function cancel(Request $request)
    {
        $historyUpdateId = $request->input('history_update_id');

        $update = UpdateHistory::find($historyUpdateId);

        if ($update) {
            $update->status = 3;
            $update->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Update not found']);
    }

    public function getDetailHistory($id)
    {
        $history = UpdateHistory::with('updateLogs')->findOrFail($id);
        return response()->json($history);
    }
}
