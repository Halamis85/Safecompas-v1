<?php
// routes/web.php - KOMPLETNĚ OPRAVENO

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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\API\StatistikaController;

/*
|--------------------------------------------------------------------------
| Authentication Routes (NEZCHRÁNĚNÉ)
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['custom.auth'])->group(function () {
    Route::get('/check-session', [AuthController::class, 'checkSession']);
    Route::post('/extend-session', [AuthController::class, 'extendSession']);
});

/*
|--------------------------------------------------------------------------
| Chráněné Routes - VYŽADUJÍ PŘIHLÁŠENÍ
|--------------------------------------------------------------------------
*/
Route::middleware(['custom.auth'])->group(function () {

    // Homepage - dostupná všem přihlášeným
    Route::get('/', [HomeController::class, 'index'])->name('home');

    /*
    |--------------------------------------------------------------------------
    | OOPP Modul - s oprávněními
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:oopp.view'])->group(function () {
        Route::get('/prehled', [HomeController::class, 'prehlOders'])->name('prehlobj');
        Route::get('/cards', [HomeController::class, 'cardsEmploy'])->name('cards');

        // API pro OOPP přehled
        Route::get('/alloders', [ObjednavkyController::class, 'getAktivni']);
        Route::get('/objednavkyMenu', [ObjednavkyController::class, 'getAktivni']);
    });

    Route::middleware(['permission:oopp.create'])->group(function () {
        Route::get('/new_orders', [HomeController::class, 'menuOrd'])->name('menuorders');

        // API pro vytváření objednávek
        Route::get('/zamestnanci', [ZamestnanciController::class, 'search']);
        Route::get('/zamestnanci/{zamestnanec_id}/objednavky-vydane', [ZamestnanciController::class, 'getObjednavkyVydane']);
        Route::get('/druhy', [ProduktyController::class, 'getDruhy']);
        Route::get('/druhy/{id}/produkty', [ProduktyController::class, 'getProduktyByDruh']);
        Route::get('/produkty/{id}', [ProduktyController::class, 'show']);
        Route::get('/last-info', [ObjednavkyController::class, 'getLastInfo']);
        Route::post('/odeslat-objednavku', [ObjednavkyController::class, 'store']);
    });

    Route::middleware(['permission:oopp.edit'])->group(function () {
        Route::post('/delete', [ObjednavkyController::class, 'delete']);
        Route::post('/vydat', [ObjednavkyController::class, 'vydat']);
        Route::post('/objednat', [ObjednavkyController::class, 'objednat']);
    });

    /*
    |--------------------------------------------------------------------------
    | Lékárničky Modul - s oprávněními
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:lekarnicke.view'])->group(function () {
        Route::get('/lekarnicke', [LekarnickController::class, 'index'])->name('lekarnicke.index');

        Route::prefix('api/lekarnicke')->group(function () {
    Route::get('/dashboard', [LekarnickController::class, 'dashboard']);

    Route::get('/{id}', [LekarnickController::class, 'show'])->where('id', '[0-9]+')->middleware(['lekarnick.access:view']);

        Route::middleware(['permission:lekarnicke.create'])->group(function () {
            Route::post('/', [LekarnickController::class, 'store']);
        });

        Route::middleware(['permission:lekarnicke.edit'])->group(function () {
            Route::put('/{id}', [LekarnickController::class, 'update'])->where('id', '[0-9]+')->middleware(['lekarnick.access:edit']);
            Route::delete('/{id}', [LekarnickController::class, 'destroy'])->where('id', '[0-9]+')->middleware(['lekarnick.access:admin']);
            Route::post('/{id}/kontrola', [LekarnickController::class, 'kontrola'])->where('id', '[0-9]+')->middleware(['lekarnick.access:edit']);
        });

        Route::middleware(['permission:lekarnicke.material'])->group(function () {
            Route::post('/{lekarnicky_id}/material', [LekarnickController::class, 'storeMaterial'])->where('lekarnicky_id', '[0-9]+')->middleware(['lekarnick.access:edit']);
            Route::put('/material/{material_id}', [LekarnickController::class, 'updateMaterial'])->where('material_id', '[0-9]+');
            Route::delete('/material/{material_id}', [LekarnickController::class, 'destroyMaterial'])->where('material_id', '[0-9]+');
        });

        Route::middleware(['permission:lekarnicke.urazy'])->group(function () {
            Route::get('/zamestnanci', [LekarnickController::class, 'getZamestnanci']);
            Route::post('/urazy', [LekarnickController::class, 'storeUraz']);
            Route::post('/vydej', [LekarnickController::class, 'vydejMaterial']);
        });

        Route::middleware(['permission:stats.export'])->group(function () {
        Route::get('/export-vykaz', [LekarnickController::class, 'exportVykaz']);
    });
    });

    });

    /*
    |--------------------------------------------------------------------------
    | Notifikace - s oprávněními
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:notifications.view'])->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

    /*
    |--------------------------------------------------------------------------
    | Statistiky - s oprávněními
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:stats.view'])->group(function () {
        Route::get('/statistiky', [StatistikaController::class, 'data'])->name('statistiky.data');
        Route::get('/statistiky/vydaje', [StatistikaController::class, 'vydajeZaRok'])->name('statistiky.vydaje');
        Route::get('/statistiky/souhrn', [StatistikaController::class, 'souhrn'])->name('statistiky.souhrn');
        Route::get('/statistiky/strediska',[StatistikaController::class, 'podleStredisek'])->name('statistiky.strediska');
        Route::get('/statistiky/trend', [StatistikaController::class, 'trendObjednavek'])->name('statistiky.trend');
    });

    /*
    |--------------------------------------------------------------------------
    | Administrace - pouze pro adminy
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::get('/admin', [HomeController::class, 'admin']);

        // Správa uživatelů
        Route::middleware(['permission:admin.users'])->group(function () {
            Route::get('/users', [HomeController::class, 'users']);
            Route::get('/user_aktivity', [HomeController::class, 'userAktivity']);
            Route::get('/adminUser', [UserController::class, 'index']);
            Route::post('/add_users', [UserController::class, 'store']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/send-login-email', [UserController::class, 'sendLoginEmail'])->name('send.login.email');
            Route::get('/userActivity', [UserController::class, 'getUserActivity']);
        });

        // Správa zaměstnanců
        Route::middleware(['permission:admin.employees'])->group(function () {
            Route::get('/employee_list', [HomeController::class, 'employeeList']);
            Route::get('/admin_employee', [HomeController::class, 'adminEmployee']);
            Route::get('/employee', [ZamestnanciController::class, 'index']);
            Route::post('/employeeAdd', [ZamestnanciController::class, 'store']);
            Route::delete('/employee/{id}', [ZamestnanciController::class, 'destroy']);
        });

        // Správa oprávnění - pouze super admin
        Route::middleware(['permission:admin.permissions'])->group(function () {
            Route::get('/admin/permissions', [RolePermissionController::class, 'index']);
            Route::prefix('api/permissions')->group(function () {
                Route::get('/dashboard', [RolePermissionController::class, 'dashboard']);
                Route::post('/roles', [RolePermissionController::class, 'storeRole']);
                Route::put('/roles/{id}', [RolePermissionController::class, 'updateRole']);
                Route::post('/roles/{role_id}/permissions', [RolePermissionController::class, 'assignPermissionsToRole']);
                Route::post('/users/{user_id}/roles', [RolePermissionController::class, 'assignRolesToUser']);
                Route::post('/users/{user_id}/lekarnicky-access', [RolePermissionController::class, 'assignLekarnickAccess']);
                Route::get('/users/{user_id}/permissions', [RolePermissionController::class, 'getUserPermissions']);
            });
        });

        // Nastavení systému
        Route::middleware(['permission:admin.settings'])->group(function () {
            Route::get('/email_contact', [HomeController::class, 'emailContact']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Obecné API routes - dostupné všem přihlášeným
    |--------------------------------------------------------------------------
    */
    Route::get('/weather/current', [ExternalApiController::class, 'weather']);
    Route::get('/holidays', [HolidayController::class, 'index']);
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



