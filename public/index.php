    <?php

    use Illuminate\Foundation\Application;
    use Illuminate\Http\Request;

    define('LARAVEL_START', microtime(true));

    // Require Composer autoload
    require __DIR__ . '/../vendor/autoload.php';

    // Bootstrap the Laravel application
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Check for maintenance mode file and serve if exists
    if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
        require $maintenance;
    }

    // Handle the incoming request via Laravel
    $request = Request::capture();
    $response = $app->handle($request);
    $response->send();
    $app->terminate($request, $response);
