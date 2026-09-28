<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Visitor\PageController;

Route::get('media/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    $file = storage_path('app/public/'.$path);
    abort_unless(is_file($file), 404);
    return response()->file($file);
})->where('path', '.*');

Route::get('/', [PageController::class, 'home'])->name('visitor.home');

Route::get('/sitemap', function () {
    return view('sitemap');
});

require __DIR__.'/admin.php';

require __DIR__.'/member.php';

require __DIR__.'/visitor.php';
