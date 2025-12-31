<?php

namespace Rafid\DbClient\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rafid\DbClient\Services\SqlGuard;
use Rafid\DbClient\Services\TableService;

class QueryController extends Controller
{
  public function index()
  {
    $tables = TableService::getAllTables();
    $tablesWithColumns = [];

    foreach ($tables as $table) {
      try {
        $columns = Schema::getColumnListing($table);
        $tablesWithColumns[$table] = $columns;
      } catch (\Exception $e) {
        // Skip tables that can't be accessed
      }
    }

    return view('dbclient::query', compact('tablesWithColumns'));
  }

  public function run(Request $request)
  {
    $query = $request->input('query');
    $readOnly = config('dbclient.read_only', false);
    $page = $request->input('page', 1);
    $perPage = $request->input('per_page', 50);

    if (empty($query)) {
      return response()->json([
        'success' => false,
        'error' => 'Query is required'
      ], 400);
    }

    // Check if query is safe
    if (!SqlGuard::isSafe($query, $readOnly)) {
      return response()->json([
        'success' => false,
        'error' => 'Query contains dangerous operations and is not allowed'
      ], 403);
    }

    try {
      $startTime = microtime(true);

      // Check if query already has LIMIT clause
      $queryUpper = strtoupper(trim($query));
      $hasLimit = strpos($queryUpper, 'LIMIT') !== false;

      $total = 0;
      $lastPage = 1;

      if (!$hasLimit) {
        // Try to get total count (for pagination)
        try {
          $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
          $totalResult = DB::select($countQuery);
          $total = $totalResult[0]->total ?? 0;
          $lastPage = ceil($total / $perPage);
        } catch (\Exception $e) {
          // If count query fails, we'll just paginate without total count
          $hasLimit = true; // Treat as if it has limit to skip count
        }
      }

      // Apply pagination to query if it doesn't have LIMIT
      if (!$hasLimit) {
        $offset = ($page - 1) * $perPage;
        $paginatedQuery = "{$query} LIMIT {$perPage} OFFSET {$offset}";
      } else {
        $paginatedQuery = $query;
      }

      $results = DB::select($paginatedQuery);
      $executionTime = round((microtime(true) - $startTime) * 1000, 2);

      // Convert results to array for JSON response
      $data = array_map(function ($row) {
        return (array) $row;
      }, $results);

      // If we couldn't get total, use current results count
      if ($total === 0 && !$hasLimit) {
        $total = count($results);
        $lastPage = 1;
      }

      return response()->json([
        'success' => true,
        'data' => $data,
        'current_page' => (int)$page,
        'last_page' => $lastPage,
        'per_page' => $perPage,
        'total' => $total,
        'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
        'to' => min($page * $perPage, $total),
        'execution_time' => $executionTime . 'ms'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
