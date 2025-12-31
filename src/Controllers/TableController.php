<?php

namespace Rafid\DbClient\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rafid\DbClient\Services\SqlGuard;
use Rafid\DbClient\Services\TableService;

class TableController extends Controller
{
  public function databaseInfo()
  {
    $dbInfo = TableService::getDatabaseInfo();
    return view('dbclient::database-info', compact('dbInfo'));
  }

  public function index(Request $request)
  {
    $tables = TableService::getAllTables();
    $tableInfo = [];

    foreach ($tables as $table) {
      $tableInfo[] = [
        'name' => $table,
        'rows' => TableService::getTableRowCount($table),
        'columns' => count(TableService::getTableColumns($table))
      ];
    }

    // Pagination
    $perPage = $request->get('per_page', 20);
    $page = $request->get('page', 1);
    $total = count($tableInfo);
    $lastPage = ceil($total / $perPage);

    $offset = ($page - 1) * $perPage;
    $paginatedTables = array_slice($tableInfo, $offset, $perPage);

    return view('dbclient::tables', [
      'tableInfo' => $paginatedTables,
      'current_page' => (int)$page,
      'last_page' => $lastPage,
      'per_page' => $perPage,
      'total' => $total,
      'from' => $total > 0 ? $offset + 1 : 0,
      'to' => min($offset + $perPage, $total),
    ]);
  }

  public function show(Request $request, $table)
  {
    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      abort(404, 'Table not found');
    }

    $columns = TableService::getTableColumns($table);
    $structure = TableService::getTableStructure($table);
    $rowCount = TableService::getTableRowCount($table);
    $primaryKey = TableService::getPrimaryKey($table) ?? 'id';

    return view('dbclient::table-view', compact('table', 'columns', 'structure', 'rowCount', 'primaryKey'));
  }

  public function rows(Request $request, $table)
  {
    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    $perPage = $request->get('per_page', 20);
    $page = $request->get('page', 1);
    $search = $request->get('search', '');

    $query = DB::table($table);

    // Apply search if provided
    if (!empty($search)) {
      $columns = Schema::getColumnListing($table);
      $query->where(function ($q) use ($columns, $search) {
        foreach ($columns as $column) {
          $q->orWhere($column, 'LIKE', "%{$search}%");
        }
      });
    }

    $total = $query->count();
    $rows = $query->skip(($page - 1) * $perPage)
      ->take($perPage)
      ->get();

    $lastPage = ceil($total / $perPage);

    return response()->json([
      'data' => $rows,
      'current_page' => (int)$page,
      'last_page' => $lastPage,
      'per_page' => $perPage,
      'total' => $total,
      'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
      'to' => min($page * $perPage, $total),
    ]);
  }

  public function insert(Request $request, $table)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    try {
      $data = $request->except(['_token', '_method']);
      DB::table($table)->insert($data);

      return response()->json([
        'success' => true,
        'message' => 'Row inserted successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function update(Request $request, $table, $id)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    try {
      $primaryKey = TableService::getPrimaryKey($table) ?? 'id';
      $data = $request->except(['_token', '_method']);
      $updated = DB::table($table)->where($primaryKey, $id)->update($data);

      if ($updated) {
        return response()->json([
          'success' => true,
          'message' => 'Row updated successfully'
        ]);
      }

      return response()->json([
        'success' => false,
        'error' => 'Row not found or no changes made'
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function delete(Request $request, $table, $id)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    try {
      $primaryKey = TableService::getPrimaryKey($table) ?? 'id';
      $deleted = DB::table($table)->where($primaryKey, $id)->delete();

      if ($deleted) {
        return response()->json([
          'success' => true,
          'message' => 'Row deleted successfully'
        ]);
      }

      return response()->json([
        'success' => false,
        'error' => 'Row not found'
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function getRow(Request $request, $table, $id)
  {
    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    $primaryKey = TableService::getPrimaryKey($table) ?? 'id';
    $row = DB::table($table)->where($primaryKey, $id)->first();

    if (!$row) {
      return response()->json(['error' => 'Row not found'], 404);
    }

    return response()->json(['success' => true, 'data' => $row]);
  }

  public function addColumn(Request $request, $table)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    try {
      $columnName = SqlGuard::sanitizeTableName($request->input('column_name'));
      $columnType = $request->input('column_type');
      $nullable = $request->input('nullable', 'NO') === 'YES';
      $default = $request->input('default_value');
      $after = $request->input('after_column');

      if (empty($columnName) || empty($columnType)) {
        return response()->json(['error' => 'Column name and type are required'], 400);
      }

      // Check if column already exists
      if (Schema::hasColumn($table, $columnName)) {
        return response()->json(['error' => 'Column already exists'], 400);
      }

      // Build ALTER TABLE statement
      $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$columnName}` {$columnType}";

      if (!$nullable) {
        $sql .= " NOT NULL";
      }

      if ($default !== null && $default !== '') {
        // Handle special default values
        $defaultUpper = strtoupper(trim($default));
        if (in_array($defaultUpper, ['CURRENT_TIMESTAMP', 'NOW()', 'NULL'])) {
          $sql .= " DEFAULT {$defaultUpper}";
        } else {
          $defaultEscaped = addslashes($default);
          $sql .= " DEFAULT '{$defaultEscaped}'";
        }
      } elseif (!$nullable) {
        // If NOT NULL and no default, we might want to add a default
        // But for now, we'll let MySQL handle it
      }

      if ($after && Schema::hasColumn($table, $after)) {
        $sql .= " AFTER `{$after}`";
      }

      DB::statement($sql);

      return response()->json([
        'success' => true,
        'message' => 'Column added successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function updateColumn(Request $request, $table, $column)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);
    $column = SqlGuard::sanitizeTableName($column);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    if (!Schema::hasColumn($table, $column)) {
      return response()->json(['error' => 'Column not found'], 404);
    }

    try {
      $columnType = $request->input('column_type');
      $nullable = $request->input('nullable', 'NO') === 'YES';
      $default = $request->input('default_value');

      if (empty($columnType)) {
        return response()->json(['error' => 'Column type is required'], 400);
      }

      // Build ALTER TABLE MODIFY statement
      $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$columnType}";

      if (!$nullable) {
        $sql .= " NOT NULL";
      }

      if ($default !== null && $default !== '') {
        // Handle special default values
        $defaultUpper = strtoupper(trim($default));
        if (in_array($defaultUpper, ['CURRENT_TIMESTAMP', 'NOW()', 'NULL'])) {
          $sql .= " DEFAULT {$defaultUpper}";
        } else {
          $defaultEscaped = addslashes($default);
          $sql .= " DEFAULT '{$defaultEscaped}'";
        }
      } else {
        $sql .= " DEFAULT NULL";
      }

      DB::statement($sql);

      return response()->json([
        'success' => true,
        'message' => 'Column updated successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function deleteColumn(Request $request, $table, $column)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);
    $column = SqlGuard::sanitizeTableName($column);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    if (!Schema::hasColumn($table, $column)) {
      return response()->json(['error' => 'Column not found'], 404);
    }

    try {
      $sql = "ALTER TABLE `{$table}` DROP COLUMN `{$column}`";
      DB::statement($sql);

      return response()->json([
        'success' => true,
        'message' => 'Column deleted successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function rename(Request $request, $table)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);
    $newName = SqlGuard::sanitizeTableName($request->input('new_name'));

    if (!$newName) {
      return response()->json(['error' => 'New table name is required'], 400);
    }

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    if (Schema::hasTable($newName)) {
      return response()->json(['error' => "Table '{$newName}' already exists"], 409);
    }

    try {
      DB::statement("RENAME TABLE `{$table}` TO `{$newName}`");
      return response()->json(['success' => true, 'message' => 'Table renamed successfully', 'new_name' => $newName]);
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  public function drop(Request $request, $table)
  {
    if (config('dbclient.read_only', false)) {
      return response()->json(['error' => 'Database is in read-only mode'], 403);
    }

    $table = SqlGuard::sanitizeTableName($table);

    if (!Schema::hasTable($table)) {
      return response()->json(['error' => 'Table not found'], 404);
    }

    try {
      Schema::dropIfExists($table);
      return response()->json(['success' => true, 'message' => 'Table dropped successfully']);
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}
