<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LekarnickController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\API\ObjednavkyController;
use App\Http\Controllers\API\ZamestnanciController;
use App\Http\Controllers\API\ProduktyController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\HolidayController;
use App\Http\Controllers\API\ExternalApiController;
use App\Http\Controllers\API\SignatureController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\API\StatistikaController;


/*
|--------------------------------------------------------------------------
| Authentication Routes  (Public)
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/password/forgot',  [AuthController::class, 'showForgot'])->name('password.forgot');
Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

/*
|--------------------------------------------------------------------------
| Session helper (přihlášený uživatel)
|--------------------------------------------------------------------------
*/
Route::middleware(['custom.auth'])->group(function () {
    Route::get('/check-session',   [AuthController::class, 'checkSession']);
    Route::post('/extend-session', [AuthController::class, 'extendSession']);
});

/*
|--------------------------------------------------------------------------
| Chráněné routes
|--------------------------------------------------------------------------
*/
Route::middleware(['custom.auth'])->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    /* ===== OOPP ===== */
    Route::middleware(['permission:oopp.view'])->group(function () {
        Route::get('/prehled', [HomeController::class, 'prehlOrders'])->name('prehlobj');
        Route::get('/cards',   [HomeController::class, 'cardsEmploy'])->name('cards');
        Route::get('/alloders',       [ObjednavkyController::class, 'getAktivni']);
        Route::get('/objednavkyMenu', [ObjednavkyController::class, 'getAktivni']);
    });

    Route::middleware(['permission:oopp.create'])->group(function () {
        Route::get('/new_orders', [HomeController::class, 'menuOrd'])->name('menuorders');

        Route::get('/zamestnanci',       [ZamestnanciController::class, 'search']);
        Route::get('/zamestnanci/{zamestnanec_id}/objednavky-vydane',
            [ZamestnanciController::class, 'getObjednavkyVydane'])->where('zamestnanec_id', '[0-9]+');
        Route::get('/druhy',                [ProduktyController::class, 'getDruhy']);
        Route::get('/druhy/{id}/produkty',  [ProduktyController::class, 'getProduktyByDruh'])->where('id', '[0-9]+');
        Route::get('/produkty/{id}',        [ProduktyController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/last-info',            [ObjednavkyController::class, 'getLastInfo']);
        Route::post('/odeslat-objednavku',  [ObjednavkyController::class, 'store']);
    });

    Route::middleware(['permission:oopp.edit'])->group(function () {
        Route::post('/vydat',    [ObjednavkyController::class, 'vydat']);
        Route::post('/objednat', [ObjednavkyController::class, 'objednat']);
    });

    Route::middleware(['permission:oopp.delete'])->group(function () {
        Route::post('/delete', [ObjednavkyController::class, 'delete']);
    });

    Route::middleware(['permission:oopp.view'])
        ->get('/signatures/{filename}', [SignatureController::class, 'show'])
        ->where('filename', '[A-Za-z0-9._-]+\.png');

    /*
    |--------------------------------------------------------------------------
    | Lékárničky Modul - s oprávněními
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:lekarnicke.view'])->group(function () {
        Route::get('/lekarnicke', [LekarnickController::class, 'index'])->name('lekarnicke.index');
    });

    // API – view operace
    Route::middleware(['permission:lekarnicke.view'])->prefix('api/lekarnicke')->group(function () {
        Route::get('/dashboard',         [LekarnickController::class, 'dashboard']);
        Route::get('/stats',             [LekarnickController::class, 'stats']);
        Route::get('/available-owners',  [LekarnickController::class, 'getAvailableOwners']);
        Route::get('/{id}',              [LekarnickController::class, 'show'])
                ->where('id', '[0-9]+')->middleware(['lekarnick.access:view']);
    });

    // API – create
    Route::middleware(['permission:lekarnicke.create'])
        ->prefix('api/lekarnicke')->group(function () {
            Route::post('/', [LekarnickController::class, 'store']);
        });

    // API – edit / delete / kontrola / pozice v plánu
    Route::middleware(['permission:lekarnicke.edit'])
        ->prefix('api/lekarnicke')->group(function () {
            Route::put('/{id}', [LekarnickController::class, 'update'])
                ->where('id', '[0-9]+')->middleware(['lekarnick.access:edit']);
            Route::delete('/{id}', [LekarnickController::class, 'destroy'])
                ->where('id', '[0-9]+')->middleware(['lekarnick.access:admin']);
            Route::post('/{id}/kontrola', [LekarnickController::class, 'kontrola'])
                ->where('id', '[0-9]+')->middleware(['lekarnick.access:edit']);
            Route::post('/{id}/plan-position', [LekarnickController::class, 'updatePlanPosition'])  
                ->where('id', '[0-9]+')->middleware(['lekarnick.access:edit']);
        });

    // API – materiál
    Route::middleware(['permission:lekarnicke.material'])
        ->prefix('api/lekarnicke')->group(function () {
            Route::post('/{lekarnicky_id}/material', [LekarnickController::class, 'storeMaterial'])
                ->where('lekarnicky_id', '[0-9]+')->middleware(['lekarnick.access:edit']);
            Route::put('/material/{material_id}',    [LekarnickController::class, 'updateMaterial'])
                ->where('material_id', '[0-9]+');
            Route::delete('/material/{material_id}', [LekarnickController::class, 'destroyMaterial'])
                ->where('material_id', '[0-9]+');
        });

    // API – úrazy + výdej
    Route::middleware(['permission:lekarnicke.urazy'])
        ->prefix('api/lekarnicke')->group(function () {
            Route::get('/zamestnanci',     [LekarnickController::class, 'getZamestnanci']);
            Route::get('/urazy',           [LekarnickController::class, 'getUrazy']);
            Route::post('/urazy',          [LekarnickController::class, 'storeUraz']);
            Route::delete('/urazy/{id}',   [LekarnickController::class, 'destroyUraz'])->where('id', '[0-9]+');
            Route::post('/vydej',          [LekarnickController::class, 'vydejMaterial']);
        });

    // API – export
    Route::middleware(['permission:stats.export'])
        ->prefix('api/lekarnicke')->group(function () {
            Route::get('/export-vykaz', [LekarnickController::class, 'exportVykaz']);
        });

    /*
    |--------------------------------------------------------------------------
    | Notifikace - dostupné každému přihlášenému uživateli
    |--------------------------------------------------------------------------
    | Notifikace jsou per-user (každý vidí jen své vlastní přes
    | $user->notifications()). Permission gating zde nemá smysl - obsah
    | je řízen tím, kdo notifikaci posílá, ne tím, kdo má jaké oprávnění.
    */
    Route::get('/notifications',                [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read',     [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    /*
    |--------------------------------------------------------------------------
    | Statistiky - s oprávněními
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:stats.view'])->group(function () {
        Route::get('/statistiky',           [StatistikaController::class, 'data'])->name('statistiky.data');
        Route::get('/statistiky/vydaje',    [StatistikaController::class, 'vydajeZaRok'])->name('statistiky.vydaje');
        Route::get('/statistiky/souhrn',    [StatistikaController::class, 'souhrn'])->name('statistiky.souhrn');
        Route::get('/statistiky/strediska', [StatistikaController::class, 'podleStredisek'])->name('statistiky.strediska');
        Route::get('/statistiky/trend',     [StatistikaController::class, 'trendObjednavek'])->name('statistiky.trend');
    });

    /*
    |--------------------------------------------------------------------------
    | Administrace
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::get('/admin', [HomeController::class, 'admin']);

        // Uživatelé
        Route::middleware(['permission:admin.users'])->group(function () {
            Route::get('/users',         [HomeController::class, 'users']);
            Route::get('/user_aktivity', [HomeController::class, 'userAktivity']);
            Route::get('/adminUser',     [UserController::class, 'index']);
            Route::get('/api/users/available-roles', [UserController::class, 'availableRoles']);
            Route::post('/add_users',    [UserController::class, 'store']);
            Route::delete('/users/{id}', [UserController::class, 'destroy'])->where('id', '[0-9]+');
            Route::post('/send-login-email', [UserController::class, 'sendLoginEmail'])
                ->middleware('throttle:10,1')->name('send.login.email');
            Route::get('/userActivity',  [UserController::class, 'getUserActivity']);
        });

        // Zaměstnanci
        Route::middleware(['permission:admin.employees'])->group(function () {
            Route::get('/employee_list',   [HomeController::class, 'employeeList']);
            Route::get('/admin_employee',  [HomeController::class, 'adminEmployee']);
            Route::get('/employee',        [ZamestnanciController::class, 'index']);
            Route::post('/employeeAdd',    [ZamestnanciController::class, 'store']);
            Route::delete('/employee/{id}',[ZamestnanciController::class, 'destroy'])->where('id', '[0-9]+');
        });

        // Oprávnění
        Route::middleware(['permission:admin.permissions'])->group(function () {
            Route::get('/admin/permissions', [RolePermissionController::class, 'index']);
            Route::prefix('api/permissions')->group(function () {
                Route::get('/dashboard',                              [RolePermissionController::class, 'dashboard']);
                Route::post('/roles',                                 [RolePermissionController::class, 'storeRole']);
                Route::put('/roles/{id}',                             [RolePermissionController::class, 'updateRole'])->where('id', '[0-9]+');
                Route::post('/roles/{role_id}/permissions',           [RolePermissionController::class, 'assignPermissionsToRole'])->where('role_id', '[0-9]+');
                Route::post('/users/{user_id}/roles',                 [RolePermissionController::class, 'assignRolesToUser'])->where('user_id', '[0-9]+');
                Route::post('/users/{user_id}/lekarnicky-access',     [RolePermissionController::class, 'assignLekarnickAccess'])->where('user_id', '[0-9]+');
                Route::get('/users/{user_id}/permissions',            [RolePermissionController::class, 'getUserPermissions'])->where('user_id', '[0-9]+');
            });
        });

        // Kontakty
        Route::middleware(['permission:admin.settings'])->group(function () {
            Route::get('/email_contact', [HomeController::class, 'emailContact']);
            Route::get('/api/contacts',         [\App\Http\Controllers\API\ContactController::class, 'index']);
            Route::post('/api/contacts',        [\App\Http\Controllers\API\ContactController::class, 'store']);
            Route::get('/api/contacts/{id}',    [\App\Http\Controllers\API\ContactController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/api/contacts/{id}',    [\App\Http\Controllers\API\ContactController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/api/contacts/{id}', [\App\Http\Controllers\API\ContactController::class, 'destroy'])->where('id', '[0-9]+');
        });
    });

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/weather/current', [ExternalApiController::class, 'weather']);
        Route::get('/holidays',        [HolidayController::class, 'index']);
    });
});

/*
|--------------------------------------------------------------------------
| Fallback route - pro neexistující stránky
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    if (!session('user')) {
        return redirect('/login');
    }
    return abort(404);
});
