<?php

namespace App\Http\Controllers\Blade;

use App\Http\Controllers\Controller;
use App\Models\ArtisanCommandLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArtisanConsoleController extends Controller
{
    /**
     * Allowed commands with metadata.
     * risky=true triggers a confirmation modal before execution.
     */
    private const COMMANDS = [
        'about' => ['description' => 'Display basic information about the application', 'risky' => false],
        'inspire' => ['description' => 'Display an inspiring quote', 'risky' => false],
        'migrate:status' => ['description' => 'Show the status of each migration', 'risky' => false],
        'route:list' => ['description' => 'List all registered routes', 'risky' => false],
        'storage:link' => ['description' => 'Create the symbolic links configured for the application', 'risky' => false],
        'cache:clear' => ['description' => 'Flush the application cache', 'risky' => true],
        'config:cache' => ['description' => 'Create a cache file for faster configuration loading', 'risky' => false],
        'config:clear' => ['description' => 'Remove the configuration cache file', 'risky' => true],
        'event:cache' => ['description' => 'Discover and cache the application\'s events and listeners', 'risky' => false],
        'event:clear' => ['description' => 'Clear all cached events and listeners', 'risky' => false],
        'route:cache' => ['description' => 'Create a route cache file for faster route registration', 'risky' => false],
        'route:clear' => ['description' => 'Remove the route cache file', 'risky' => false],
        'view:cache' => ['description' => 'Compile all of the application\'s Blade templates', 'risky' => false],
        'view:clear' => ['description' => 'Clear all compiled view files', 'risky' => false],
        'queue:restart' => ['description' => 'Restart queue worker daemons after their current job', 'risky' => true],
        'optimize' => ['description' => 'Cache framework bootstrap files', 'risky' => false],
        'optimize:clear' => ['description' => 'Remove the cached bootstrap files', 'risky' => false],
        'migrate' => ['description' => 'Run migrations', 'risky' => true],
        'migrate:fresh' => ['description' => 'Drop all tables and re-run all migrations', 'risky' => true],
        'migrate:refresh' => ['description' => 'Drop all tables and re-run all migrations', 'risky' => true],
        'migrate:reset' => ['description' => 'Drop all tables and re-run all migrations', 'risky' => true],
        'migrate:rollback' => ['description' => 'Drop all tables and re-run all migrations', 'risky' => true],
    ];

    private function guardSuperAdmin(): void
    {
        if (! auth()->user()->hasRole('Super Admin')) {
            abort(403);
        }
    }

    public function index(): \Illuminate\View\View
    {
        $this->guardSuperAdmin();

        $commands = self::COMMANDS;
        $logs = ArtisanCommandLog::with('user:id,name,email')
            ->latest()
            ->limit(20)
            ->get();

        return view('pages.artisan.index', compact('commands', 'logs'));
    }

    public function run(Request $request): JsonResponse
    {
        $this->guardSuperAdmin();

        $request->validate([
            'command'   => ['required', 'string'],
            'arguments' => ['nullable', 'string', 'max:500', 'regex:/^[\w\s\-=.,\/]*$/'],
        ]);

        $command = $request->input('command');

        if (! array_key_exists($command, self::COMMANDS)) {
            return response()->json(['error' => 'Command not allowed.'], 422);
        }

        $parameters = $this->parseArguments($request->input('arguments') ?? '');

        $startTime = microtime(true);
        $status    = 'success';
        $output    = '';

        try {
            Artisan::call($command, $parameters);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            $status = 'error';
            $output = $e->getMessage();
        }

        $executionTime = round(microtime(true) - $startTime, 3);

        ArtisanCommandLog::create([
            'user_id'        => auth()->id(),
            'command'        => $command,
            'parameters'     => $parameters ?: null,
            'status'         => $status,
            'output'         => $output,
            'execution_time' => $executionTime,
            'ip_address'     => $request->ip(),
        ]);

        return response()->json([
            'status'         => $status,
            'output'         => $output ?: '(no output)',
            'execution_time' => $executionTime,
        ]);
    }

    public function commands(): JsonResponse
    {
        $this->guardSuperAdmin();

        return response()->json(self::COMMANDS);
    }

    private function parseArguments(string $raw): array
    {
        $parameters = [];

        if (trim($raw) === '') {
            return $parameters;
        }

        // Extract --key=value and --flag patterns only
        preg_match_all('/--([a-zA-Z][\w-]*)(?:=([^\s]*))?/', $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = '--' . $match[1];
            $parameters[$key] = isset($match[2]) && $match[2] !== '' ? $match[2] : true;
        }

        return $parameters;
    }
}
