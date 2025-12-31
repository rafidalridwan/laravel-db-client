<?php

namespace Rafid\DbClient\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ArtisanController extends Controller
{
    public function index()
    {
        $allowedCommands = config('dbclient.allowed_commands', []);
        return view('dbclient::artisan', compact('allowedCommands'));
    }

    public function run(Request $request)
    {
        abort_if(app()->isProduction(), 403);

        $command = $request->input('command');
        $allowedCommands = config('dbclient.allowed_commands', []);

        if (!in_array($command, $allowedCommands)) {
            return response()->json([
                'success' => false,
                'error' => 'Command not allowed'
            ], 403);
        }

        try {
            Artisan::call($command, $request->except(['command', '_token']));
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
