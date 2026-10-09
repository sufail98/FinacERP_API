<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Traits\DatabaseContext;

class DatabaseAwareController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, DatabaseContext;

    /**
     * Get the current database context for the request
     */
    protected function getCurrentDatabaseContext()
    {
        return $this->getDatabaseContext(request());
    }

    /**
     * Check if the request has a valid database context
     */
    protected function hasValidDatabaseContext()
    {
        return $this->hasDatabaseContext(request());
    }

    /**
     * Get the current database name
     */
    protected function getCurrentDatabaseName()
    {
        return $this->getCurrentDatabaseName(request());
    }
}
