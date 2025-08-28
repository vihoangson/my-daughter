<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\KidRequest;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function systemInfo()
    {
        // Thông tin về users
        $totalUsers = User::count();
        $parentUsers = User::where('type', 'parent')->count();
        $childUsers = User::where('type', 'child')->count();

        // Thông tin về relationships
        $parentChildRelations = DB::table('child_parent')->count();

        // Thông tin về requests
        $totalRequests = KidRequest::count();
        $pendingRequests = KidRequest::where('status', 'pending')->count();
        $acceptedRequests = KidRequest::where('status', 'accepted')->count();
        $deniedRequests = KidRequest::where('status', 'denied')->count();

        // Lấy thông tin chi tiết về relationships
        $relationships = DB::table('child_parent')
            ->join('users as children', 'child_parent.child_id', '=', 'children.id')
            ->join('users as parents', 'child_parent.parent_id', '=', 'parents.id')
            ->select('children.name as child_name', 'parents.name as parent_name', 'child_parent.created_at')
            ->get();

        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'parents' => $parentUsers,
                'children' => $childUsers
            ],
            'relationships' => [
                'total' => $parentChildRelations,
                'details' => $relationships
            ],
            'requests' => [
                'total' => $totalRequests,
                'pending' => $pendingRequests,
                'accepted' => $acceptedRequests,
                'denied' => $deniedRequests
            ]
        ]);
    }

    public function checkSystemStatus()
    {
        $status = [];

        try {
            // Kiểm tra database connection
            DB::connection()->getPdo();
            $status['database'] = [
                'status' => 'OK',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $status['database'] = [
                'status' => 'ERROR',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        try {
            // Kiểm tra tables tồn tại
            $tables = ['users', 'child_parent', 'kid_requests', 'reward_punishments'];
            $missingTables = [];

            foreach ($tables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            if (empty($missingTables)) {
                $status['tables'] = [
                    'status' => 'OK',
                    'message' => 'All required tables exist'
                ];
            } else {
                $status['tables'] = [
                    'status' => 'WARNING',
                    'message' => 'Missing tables: ' . implode(', ', $missingTables)
                ];
            }
        } catch (\Exception $e) {
            $status['tables'] = [
                'status' => 'ERROR',
                'message' => 'Error checking tables: ' . $e->getMessage()
            ];
        }

        try {
            // Kiểm tra data integrity
            $orphanedChildren = DB::table('users')
                ->where('type', 'child')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('child_parent')
                          ->whereRaw('child_parent.child_id = users.id');
                })
                ->count();

            $orphanedRequests = DB::table('kid_requests')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->whereRaw('users.id = kid_requests.kid_id');
                })
                ->count();

            if ($orphanedChildren === 0 && $orphanedRequests === 0) {
                $status['data_integrity'] = [
                    'status' => 'OK',
                    'message' => 'Data integrity check passed'
                ];
            } else {
                $status['data_integrity'] = [
                    'status' => 'WARNING',
                    'message' => "Found {$orphanedChildren} orphaned children and {$orphanedRequests} orphaned requests"
                ];
            }
        } catch (\Exception $e) {
            $status['data_integrity'] = [
                'status' => 'ERROR',
                'message' => 'Error checking data integrity: ' . $e->getMessage()
            ];
        }

        return response()->json([
            'overall_status' => collect($status)->every(function ($check) {
                return $check['status'] === 'OK';
            }) ? 'HEALTHY' : 'ISSUES_DETECTED',
            'checks' => $status,
            'timestamp' => now()->toISOString()
        ]);
    }
}
