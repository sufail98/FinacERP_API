<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Exception;

class DatabaseService
{
    /**
     * Switch to a specific database using encrypted database name
     */
    public static function switchToDatabase(string $encryptedDbName): array
    {
        try {
            // Decrypt the database name
            $dbName = Crypt::decryptString($encryptedDbName);
            
            // Get the current database configuration
            $currentConfig = Config::get('database.connections.' . Config::get('database.default'));
            
            // Create a new connection configuration with the decrypted database name
            $newConfig = array_merge($currentConfig, [
                'database' => $dbName,
            ]);
            
            // Add the new connection to the database configuration
            $connectionName = 'dynamic_' . $dbName;
            Config::set("database.connections.{$connectionName}", $newConfig);
            
            // Set this as the default connection for the request
            Config::set('database.default', $connectionName);
            
            return [
                'success' => true,
                'connection_name' => $connectionName,
                'database_name' => $dbName,
                'message' => "Switched to database: {$dbName}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to switch database'
            ];
        }
    }

    /**
     * Get current database connection info
     */
    public static function getCurrentConnectionInfo(): array
    {
        $defaultConnection = Config::get('database.default');
        $connectionConfig = Config::get("database.connections.{$defaultConnection}");
        
        return [
            'connection_name' => $defaultConnection,
            'database_name' => $connectionConfig['database'] ?? null,
            'host' => $connectionConfig['host'] ?? null,
            'port' => $connectionConfig['port'] ?? null,
        ];
    }

    /**
     * Test database connection
     */
    public static function testConnection(string $connectionName = null): array
    {
        try {
            $connection = $connectionName ?: Config::get('database.default');
            DB::connection($connection)->getPdo();
            
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'connection' => $connection
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Database connection failed'
            ];
        }
    }

    /**
     * Get all available database connections
     */
    public static function getAvailableConnections(): array
    {
        return array_keys(Config::get('database.connections'));
    }
}
