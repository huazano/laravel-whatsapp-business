<?php

use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\WhatsappUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // WhatsApp Users Management
    Route::prefix('admin/whatsapp-users')->name('admin.whatsapp-users.')->group(function () {
        Route::get('/', [WhatsappUserController::class, 'index'])->name('index');
        Route::get('/{whatsappUser}', [WhatsappUserController::class, 'show'])->name('show');
        Route::put('/{whatsappUser}/role', [WhatsappUserController::class, 'updateRole'])->name('update-role');
        Route::put('/{whatsappUser}/toggle-active', [WhatsappUserController::class, 'toggleActive'])->name('toggle-active');
    });

    // Conversations & Messages
    Route::prefix('admin/conversations')->name('admin.conversations.')->group(function () {
        Route::get('/{conversation}/messages', [ConversationController::class, 'messages'])->name('messages');
        Route::post('/{conversation}/send', [ConversationController::class, 'sendMessage'])->name('send-message');
        Route::post('/whatsapp-users/{whatsappUser}/get-or-create', [ConversationController::class, 'getOrCreate'])->name('get-or-create');
        Route::put('/{conversation}/close', [ConversationController::class, 'close'])->name('close');
    });
});

require __DIR__.'/settings.php';
