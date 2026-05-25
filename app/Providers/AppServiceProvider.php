<?php

namespace App\Providers;

use App\Models\CaseFile;
use App\Services\PublicUploadService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->ensureFrameworkWritable();

        Paginator::defaultView('vendor.pagination.aretia');
        Paginator::defaultSimpleView('vendor.pagination.aretia');

        Broadcast::routes(['middleware' => ['web', 'auth']]);

        app(PublicUploadService::class)->ensureRootDirs();

        Route::bind('case', fn (string $value) => CaseFile::findOrFail($value));
    }

    /** Avoid PHP 8.4 tempnam() using system /tmp when storage paths are missing on deploy. */
    private function ensureFrameworkWritable(): void
    {
        $dirs = [
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/tmp'),
            base_path('bootstrap/cache'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        $tmp = storage_path('framework/tmp');
        putenv('TMPDIR='.$tmp);
        putenv('TEMP='.$tmp);
        putenv('TMP='.$tmp);
    }
}
