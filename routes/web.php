<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OtherIncomeController;
use App\Http\Controllers\DailyClosingController;
use App\Http\Controllers\CashBoxInitialController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/forgot-password', [LoginController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [LoginController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [LoginController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('password.update');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin,admin')->group(function () {
        // Empresas
        Route::resource('companies', CompanyController::class)->except(['show']);

        // Clientes
        Route::resource('clients', ClientController::class);
    });

    Route::middleware('role:super_admin')->group(function () {
        // Administración de usuarios y sucursales
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('branches', [AdminBranchController::class, 'index'])->name('branches.index');
            Route::post('branches', [AdminBranchController::class, 'store'])->name('branches.store');
            Route::put('branches/{branch}', [AdminBranchController::class, 'update'])->name('branches.update');
            Route::delete('branches/{branch}', [AdminBranchController::class, 'destroy'])->name('branches.destroy');

            Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        });
    });

    Route::middleware('role:super_admin,admin,operator')->group(function () {
        // Dinero inicial caja chica
        Route::get('cash-box-initial', [CashBoxInitialController::class, 'index'])->name('cash-box-initial.index');
        Route::post('cash-box-initial', [CashBoxInitialController::class, 'store'])->name('cash-box-initial.store');
        Route::put('cash-box-initial/{cashBoxInitial}', [CashBoxInitialController::class, 'update'])->name('cash-box-initial.update');
        Route::delete('cash-box-initial/{cashBoxInitial}', [CashBoxInitialController::class, 'destroy'])->name('cash-box-initial.destroy');

        Route::middleware('cash.box.initialized')->group(function () {
        // Giros / Transferencias
        Route::resource('transfers', TransferController::class)->except(['show']);
        Route::patch('transfers/{transfer}/mark-sent', [TransferController::class, 'markSent'])->name('transfers.mark-sent');
        Route::patch('transfers/{transfer}/resend', [TransferController::class, 'resend'])->name('transfers.resend');
        Route::post('transfers/{transfer}/notify-whatsapp', [TransferController::class, 'notifyWhatsApp'])->name('transfers.notify-whatsapp');

        // Gastos / Debitos
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('expenses/quick-client', [ExpenseController::class, 'quickStoreClient'])->name('expenses.quick-client');
        Route::post('expenses/quick-company', [ExpenseController::class, 'quickStoreCompany'])->name('expenses.quick-company');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('expenses/{credit}', [ExpenseController::class, 'show'])->name('expenses.show');
        Route::get('expenses/{credit}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('expenses/{credit}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::post('expenses/{credit}/payments', [ExpenseController::class, 'storePayment'])->name('expenses.payments.store');
        Route::post('expenses/{credit}/send-reminder', [ExpenseController::class, 'sendReminder'])->name('expenses.send-reminder');
        Route::delete('expenses/{credit}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // Compatibilidad con rutas antiguas de fiados / créditos
        Route::get('credits', fn(\Illuminate\Http\Request $request) => redirect()->route('expenses.index', $request->query()))->name('credits.index');
        Route::get('credits/create', fn(\Illuminate\Http\Request $request) => redirect()->route('expenses.create', $request->query()))->name('credits.create');
        Route::post('credits', [ExpenseController::class, 'store'])->name('credits.store');
        Route::get('credits/{credit}', fn(\App\Models\Credit $credit) => redirect()->route('expenses.show', $credit))->name('credits.show');
        Route::get('credits/{credit}/edit', fn(\App\Models\Credit $credit) => redirect()->route('expenses.edit', $credit))->name('credits.edit');
        Route::put('credits/{credit}', [ExpenseController::class, 'update'])->name('credits.update');
        Route::post('credits/{credit}/payments', [ExpenseController::class, 'storePayment'])->name('credits.payments.store');
        Route::delete('credits/{credit}', [ExpenseController::class, 'destroy'])->name('credits.destroy');

        // Otros ingresos
        Route::get('other-incomes', [OtherIncomeController::class, 'index'])->name('other-incomes.index');
        Route::post('other-incomes', [OtherIncomeController::class, 'store'])->name('other-incomes.store');
        Route::post('other-incomes/collect-debit', [OtherIncomeController::class, 'collectDebit'])->name('other-incomes.collect-debit');
        Route::post('other-incomes/collect-client-debts', [OtherIncomeController::class, 'collectClientDebts'])->name('other-incomes.collect-client-debts');
        Route::post('other-incomes/send-overdue-reminders', [OtherIncomeController::class, 'sendOverdueReminders'])->name('other-incomes.send-overdue-reminders');
        Route::put('other-incomes/{otherIncome}', [OtherIncomeController::class, 'update'])->name('other-incomes.update');
        Route::delete('other-incomes/{otherIncome}', [OtherIncomeController::class, 'destroy'])->name('other-incomes.destroy');

        // Cierre de caja
        Route::get('daily-closings', [DailyClosingController::class, 'index'])->name('daily-closings.index');
        Route::get('daily-closings/create', [DailyClosingController::class, 'create'])->name('daily-closings.create');
        Route::post('daily-closings', [DailyClosingController::class, 'store'])->name('daily-closings.store');
        Route::get('daily-closings/{dailyClosing}', [DailyClosingController::class, 'show'])->name('daily-closings.show');
        Route::delete('daily-closings/{dailyClosing}', [DailyClosingController::class, 'destroy'])->name('daily-closings.destroy');
        });
    });

    Route::middleware('role:super_admin,admin,operator,viewer')->group(function () {
        // Reportes
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    });

    // Perfil de usuario
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('profile/system-logo', [ProfileController::class, 'updateSystemLogo'])->name('profile.system-logo.update');
});
