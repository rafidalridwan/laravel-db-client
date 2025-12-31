<?php

namespace Rafid\DbClient\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableService
{
  /**
   * Get all tables from the database
   */
  public static function getAllTables(): array
  {
    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $key = "Tables_in_{$dbName}";

    return array_map(function ($table) use ($key) {
      return $table->$key;
    }, $tables);
  }

  /**
   * Get table columns with details
   */
  public static function getTableColumns(string $table): array
  {
    $columns = DB::select("DESCRIBE `{$table}`");
    $details = [];

    foreach ($columns as $column) {
      $details[$column->Field] = $column->Type;
    }

    return $details;
  }

  /**
   * Get table row count
   */
  public static function getTableRowCount(string $table): int
  {
    return DB::table($table)->count();
  }

  /**
   * Get table structure information
   */
  public static function getTableStructure(string $table): array
  {
    $columns = DB::select("DESCRIBE `{$table}`");
    $structure = [];

    foreach ($columns as $column) {
      $structure[] = [
        'field' => $column->Field,
        'type' => $column->Type,
        'null' => $column->Null,
        'key' => $column->Key,
        'default' => $column->Default,
        'extra' => $column->Extra,
      ];
    }

    return $structure;
  }

  /**
   * Get primary key column name for a table
   */
  public static function getPrimaryKey(string $table): ?string
  {
    $databaseName = DB::getDatabaseName();

    $result = DB::select("
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = ?
      AND TABLE_NAME = ?
      AND COLUMN_KEY = 'PRI'
      LIMIT 1
    ", [$databaseName, $table]);

    if (!empty($result)) {
      return $result[0]->COLUMN_NAME;
    }

    // Fallback: check if 'id' column exists
    if (Schema::hasColumn($table, 'id')) {
      return 'id';
    }

    return null;
  }

  /**
   * Get database information and statistics
   */
  public static function getDatabaseInfo(): array
  {
    $dbName = DB::getDatabaseName();
    $connection = DB::connection();

    // Get database size
    $sizeResult = DB::select("
      SELECT 
        ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
        ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024 / 1024, 2) AS size_gb
      FROM information_schema.TABLES 
      WHERE TABLE_SCHEMA = ?
    ", [$dbName]);

    $sizeMb = $sizeResult[0]->size_mb ?? $sizeResult[0]->SIZE_MB ?? 0;
    $sizeGb = $sizeResult[0]->size_gb ?? $sizeResult[0]->SIZE_GB ?? 0;

    // Get tables count
    $tables = self::getAllTables();
    $tablesCount = count($tables);

    // Get total rows count
    $totalRows = 0;
    foreach ($tables as $table) {
      try {
        $totalRows += self::getTableRowCount($table);
      } catch (\Exception $e) {
        // Skip if table can't be accessed
      }
    }

    // Get database charset and collation
    $dbInfo = DB::select("
      SELECT 
        DEFAULT_CHARACTER_SET_NAME as charset,
        DEFAULT_COLLATION_NAME as collation
      FROM information_schema.SCHEMATA 
      WHERE SCHEMA_NAME = ?
    ", [$dbName]);

    $charset = $dbInfo[0]->charset ?? 'Unknown';
    $collation = $dbInfo[0]->collation ?? 'Unknown';

    // Get MySQL version
    $versionResult = DB::select("SELECT VERSION() as version");
    $version = $versionResult[0]->version ?? 'Unknown';

    // Get connection info
    $config = $connection->getConfig();
    $host = $config['host'] ?? 'Unknown';
    $port = $config['port'] ?? 'Unknown';
    $driver = $config['driver'] ?? 'Unknown';

    // Get table sizes breakdown
    $tableSizes = DB::select("
      SELECT 
        TABLE_NAME as table_name,
        ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
        TABLE_ROWS as table_rows
      FROM information_schema.TABLES 
      WHERE TABLE_SCHEMA = ?
      ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
      LIMIT 10
    ", [$dbName]);

    // Normalize column names - convert object to array first, then back to object with lowercase keys
    $tableSizesArray = [];
    foreach ($tableSizes as $item) {
      // Convert object to array to handle case-insensitive access
      $itemArray = (array) $item;

      // Create new object with normalized lowercase keys
      $result = new \stdClass();
      $result->table_name = $itemArray['table_name'] ??
        $itemArray['TABLE_NAME'] ??
        (isset($item->table_name) ? $item->table_name : '');
      $result->size_mb = (float)($itemArray['size_mb'] ??
        $itemArray['SIZE_MB'] ??
        (isset($item->size_mb) ? $item->size_mb : 0));
      $result->table_rows = (int)($itemArray['table_rows'] ??
        $itemArray['TABLE_ROWS'] ??
        (isset($item->table_rows) ? $item->table_rows : 0));
      $tableSizesArray[] = $result;
    }

    return [
      'database_name' => $dbName,
      'host' => $host,
      'port' => $port,
      'driver' => $driver,
      'version' => $version,
      'charset' => $charset,
      'collation' => $collation,
      'tables_count' => $tablesCount,
      'total_rows' => $totalRows,
      'size_mb' => $sizeMb,
      'size_gb' => $sizeGb,
      'largest_tables' => $tableSizesArray,
    ];
  }
}
