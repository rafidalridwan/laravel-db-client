<?php

namespace Rafid\DbClient\Services;

class SqlGuard
{
  /**
   * Dangerous SQL keywords that should be restricted
   */
  private static array $dangerousKeywords = [
    'DROP',
    'TRUNCATE',
    'DELETE',
    'ALTER',
    'CREATE',
    'GRANT',
    'REVOKE',
    'EXEC',
    'EXECUTE',
    'INSERT',
    'UPDATE',
    'REPLACE'
  ];

  /**
   * Check if SQL query is safe to execute
   */
  public static function isSafe(string $query, bool $readOnly = false): bool
  {
    $query = strtoupper(trim($query));

    // If read-only mode, only allow SELECT
    if ($readOnly && !str_starts_with($query, 'SELECT')) {
      return false;
    }

    // Check for dangerous keywords
    foreach (self::$dangerousKeywords as $keyword) {
      if (str_contains($query, $keyword)) {
        // Allow if it's part of a comment or string, but for simplicity, we'll be strict
        // In production, you might want more sophisticated parsing
        if ($readOnly) {
          return false;
        }
        // For write mode, we still want to be careful
        // Allow DELETE, UPDATE, INSERT but block DROP, TRUNCATE, ALTER, CREATE
        if (in_array($keyword, ['DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'GRANT', 'REVOKE'])) {
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Sanitize table name
   */
  public static function sanitizeTableName(string $table): string
  {
    // Only allow alphanumeric, underscore, and dash
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $table);
  }
}
