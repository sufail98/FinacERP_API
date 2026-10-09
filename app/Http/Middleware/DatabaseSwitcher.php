<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DatabaseService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;



class DatabaseSwitcher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->is('api/get-organization') ||
            $request->is('api/organizations') ||
            $request->is('api/app-version-update') ||
            $request->is('api/save-default-branchdb-data/*') ||
            $request->is('api/zatca/pending-vouchers') ||
            $request->is('api/zatca/update-fields') ||
            $request->is('api/tax-masters-print') ||
            $request->is('api/get-sales-byId-print/*')
        ) {
            return $next($request);
        }
        // Get the encrypted database name from the request header
        $encryptedDbName = $request->header('X-Database-Name');

        if (!$encryptedDbName) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Database name header is required (X-Database-Name)',
            ], 400);
        }

        DB::purge(); // purge default connection safely

        // Use the database service to switch databases
        $result = DatabaseService::switchToDatabase($encryptedDbName);

        if (!$result['success']) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => $result['message'],
            ], 400);
        }

        // Set new default connection
        Config::set('database.default', $result['connection_name']);

        // Purge again to ensure clean state
        DB::purge($result['connection_name']);

        // Optional: reconnect explicitly
        DB::reconnect($result['connection_name']);

        // Store the connection information in the request for later use
        $request->attributes->set('current_database_connection', $result['connection_name']);
        $request->attributes->set('current_database_name', $result['database_name']);

        return $next($request);
    }

    

    /**
     * Handle tasks after the response has been sent to the browser.
     * Laravel automatically calls this if the method exists — no interface needed.
     */
    public function terminate(Request $request, Response $response): void
    {
        $connectionName = $request->attributes->get('current_database_connection');

        if ($connectionName) {
            DB::disconnect($connectionName);
        }
    }
}
