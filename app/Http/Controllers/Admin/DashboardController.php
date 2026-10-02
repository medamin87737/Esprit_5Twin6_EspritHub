<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $modules = collect(config('nutritrace.modules'))->map(function (array $module) {
            $module['entites'] = collect($module['entites'])->map(function (array $entite) {
                $entite['total'] = $this->count($entite['table']);

                return $entite;
            })->all();

            return $module;
        });

        $tablesPretes = $modules->flatMap(fn (array $m) => $m['entites'])
            ->filter(fn (array $e) => $e['total'] !== null)
            ->count();

        $roles = DB::table('users')
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('pages.admin.dashboard', [
            'modules' => $modules,
            'tablesPretes' => $tablesPretes,
            'totalEntites' => $modules->sum(fn (array $m) => count($m['entites'])),
            'utilisateurs' => $roles->sum(),
            'administrateurs' => (int) ($roles['admin'] ?? 0),
            'roles' => $roles,
        ]);
    }

    private function count(string $table): ?int
    {
        try {
            return Schema::hasTable($table) ? DB::table($table)->count() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
