<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackdoorGuardTest extends TestCase
{
    #[Test]
    public function public_index_php_does_not_contain_backdoor_routes(): void
    {
        $index = file_get_contents(base_path('public/index.php'));

        $this->assertStringNotContainsString('pandaxcode', $index);
        $this->assertStringNotContainsString('some_random_long_secret_key', $index);
        $this->assertStringNotContainsString("REQUEST_URI === '/kill", $index);
        $this->assertStringNotContainsString("REQUEST_URI === '/revive", $index);
        $this->assertStringNotContainsString('x9/Handler', $index);
    }

    #[Test]
    public function gitignore_blocks_backdoor_paths(): void
    {
        $gi = file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('/bootstrap/cache/', $gi);
        $this->assertStringContainsString(".env\n", $gi."\n");
        $this->assertStringNotContainsString('!bootstrap/cache/vendor/assets/.bin/x9', $gi);
    }

    #[Test]
    public function backdoor_handler_file_does_not_exist(): void
    {
        $this->assertFileDoesNotExist(base_path('bootstrap/cache/vendor/assets/.bin/x9/Handler.php'));
    }

    #[Test]
    public function env_exfil_payload_does_not_exist(): void
    {
        $this->assertFileDoesNotExist(base_path('pandaxcode.php'));
    }
}
