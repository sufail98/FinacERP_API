<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

trait DatabaseContext
{
    /**
     * Get the current database connection name
     */
    protected function getCurrentDatabaseConnection(Request $request): ?string
    {
        return $request->attributes->get('current_database_connection');
    }

    /**
     * Get the current database name
     */
    protected function getCurrentDatabaseName(Request $request): ?string
    {
        return $request->attributes->get('current_database_name');
    }

    /**
     * Check if database context is available
     */
    protected function hasDatabaseContext(Request $request): bool
    {
        return $request->attributes->has('current_database_connection');
    }

    /**
     * Get database context information
     */
    protected function getDatabaseContext(Request $request): array
    {
        return [
            'connection' => $this->getCurrentDatabaseConnection($request),
            'database' => $this->getCurrentDatabaseName($request),
            'has_context' => $this->hasDatabaseContext($request),
        ];
    }
}
