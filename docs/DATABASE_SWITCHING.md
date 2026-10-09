# Database Switching System

This system allows Laravel to dynamically switch between different databases based on encrypted database names passed in request headers.

## Overview

After a user logs in, they receive encrypted database names for their assigned branches. Every subsequent API request must include the encrypted database name in the `X-Database-Name` header, which will automatically switch the application to use that specific database for the request.

## How It Works

### 1. Login Response
When a user logs in, they receive a response like this:

```json
{
    "status": 200,
    "error": false,
    "message": "Login successful",
    "data": {
        "token": "60|GN12gvNOTmA0ynRPKr1MdkSnpVN16b4gzarCUJWMc6e7487e",
        "user": { ... },
        "branches": [
            {
                "branchId": "6",
                "branchName": "Abudhabi Branch",
                "dbNameEncrypted": "eyJpdiI6IjZBTkxmck95Y0p6VTFsZGdVMU5RNFE9PSIsInZhbHVlIjoidUtuTGVCNkMvUzBFbzF2UktpY3l1QzIrWWRvOEFZYzg0VEJZNEdCSmQ4bz0iLCJtYWMiOiIzYTI4YWZkM2EwMGNlN2E2NGZmNjJhZTI5M2U2OWUyMGNkZDNmYmEzYTZhN2FmOWY2OGZmZWUzZjIxMDQ5YjdhIiwidGFnIjoiIn0="
            }
        ]
    }
}
```

### 2. Making API Requests
For every API request after login, include the encrypted database name in the header:

```http
GET /api/users
Authorization: Bearer 60|GN12gvNOTmA0ynRPKr1MdkSnpVN16b4gzarCUJWMc6e7487e
X-Database-Name: eyJpdiI6IjZBTkxmck95Y0p6VTFsZGdVMU5RNFE9PSIsInZhbHVlIjoidUtuTGVCNkMvUzBFbzF2UktpY3l1QzIrWWRvOEFZYzg0VEJZNEdCSmQ4bz0iLCJtYWMiOiIzYTI4YWZkM2EwMGNlN2E2NGZmNjJhZTI5M2U2OWUyMGNkZDNmYmEzYTZhN2FmOWY2OGZmZWUzZjIxMDQ5YjdhIiwidGFnIjoiIn0=
```

### 3. Automatic Database Switching
The middleware automatically:
- Decrypts the database name
- Creates a new database connection
- Switches the application to use that database
- All subsequent database operations use the selected database

## Components

### 1. DatabaseSwitcher Middleware
Located at `app/Http/Middleware/DatabaseSwitcher.php`

This middleware:
- Extracts the `X-Database-Name` header
- Uses the DatabaseService to switch databases
- Stores database context in the request

### 2. DatabaseService
Located at `app/Services/DatabaseService.php`

This service handles:
- Decrypting database names
- Creating dynamic database connections
- Managing database switching logic

### 3. DatabaseContext Trait
Located at `app/Traits/DatabaseContext.php`

This trait provides methods for controllers to:
- Get current database context
- Check if database context is available
- Access database information

### 4. DatabaseAwareController
Located at `app/Http/Controllers/DatabaseAwareController.php`

A base controller that includes the DatabaseContext trait and provides helper methods.

## Usage in Controllers

### Option 1: Use the Trait
```php
use App\Traits\DatabaseContext;

class UserController extends Controller
{
    use DatabaseContext;
    
    public function index(Request $request)
    {
        $dbContext = $this->getDatabaseContext($request);
        // Use $dbContext to get database information
    }
}
```

### Option 2: Extend DatabaseAwareController
```php
class UserController extends DatabaseAwareController
{
    public function index(Request $request)
    {
        $dbContext = $this->getCurrentDatabaseContext();
        // Use $dbContext to get database information
    }
}
```

## API Endpoints

### Get Database Context
```
GET /api/database-context
Authorization: Bearer {token}
X-Database-Name: {encrypted_db_name}
```

Response:
```json
{
    "status": 200,
    "error": false,
    "message": "Current database context",
    "data": {
        "connection": "dynamic_database_name",
        "database": "database_name",
        "has_context": true
    }
}
```

## Error Handling

### Missing Database Header
```json
{
    "status": 400,
    "error": true,
    "message": "Database name header is required (X-Database-Name)"
}
```

### Invalid Database Name
```json
{
    "status": 400,
    "error": true,
    "message": "Invalid database name encryption"
}
```

## Security

- Database names are encrypted using Laravel's built-in encryption
- Each request must include the encrypted database name
- The system automatically validates and decrypts the database name
- Database connections are created dynamically per request

## Configuration

The middleware is registered in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'database.switcher' => \App\Http\Middleware\DatabaseSwitcher::class,
    ]);
})
```

## Routes

All routes that need database access should use the `database.switcher` middleware:

```php
Route::middleware(['auth:sanctum', 'database.switcher'])->group(function () {
    // Your API routes here
});
```

## Example Client Implementation

```javascript
// After login, store the encrypted database name
const dbNameEncrypted = response.data.branches[0].dbNameEncrypted;

// For all subsequent requests, include the header
const response = await fetch('/api/users', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'X-Database-Name': dbNameEncrypted,
        'Content-Type': 'application/json'
    }
});
```

## Notes

- The system automatically handles database switching for each request
- All Eloquent models will use the selected database
- Database connections are created dynamically and cached per request
- The system is designed to work with multiple database types (MySQL, PostgreSQL, etc.)
