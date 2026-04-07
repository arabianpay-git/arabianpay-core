    <?php

    use Illuminate\Foundation\Application;
    use Illuminate\Http\Request;

    define('LARAVEL_START', microtime(true));

    // Require Composer autoload
    require __DIR__ . '/../vendor/autoload.php';

    // Bootstrap the Laravel application (creates $app but does not boot providers yet)
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Instantiate HTTP kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    // [PHASE-0 2026-04-06] Removed malicious kill-switch (F-001).

    // Check for maintenance mode file and serve if exists
    if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
        require $maintenance;
    }

    // Now handle the incoming request via Laravel
    $request = Request::capture();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
