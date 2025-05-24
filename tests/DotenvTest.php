<?php

namespace NALDotenvTests;

use NAL\Dotenv\Dotenv;
use NAL\Dotenv\Exception\Missing;
use NAL\Dotenv\Exception\UnMatch;
use PHPUnit\Framework\TestCase;

class DotenvTest extends TestCase
{
    private string $dir;
    private string $file;

    private string $secondFile;

    private string $rootLevelEnvFile;

    protected function setUp(): void
    {
        $this->dir = __DIR__ . '/_data';

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }

        $this->file = $this->dir . '/.env';
        $this->secondFile = $this->dir . '/second.env';
        $this->rootLevelEnvFile = dirname(__DIR__, 4) . '/.nal.env.test';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }

        if (file_exists($this->secondFile)) {
            unlink($this->secondFile);
        }

        if (file_exists($this->rootLevelEnvFile)) {
            unlink($this->rootLevelEnvFile);
        }

        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }

        $_SERVER = [];
        $_ENV = [];
    }

    public function testLoadSingleEnv()
    {
        file_put_contents($this->file, "APP_ENV=development\nAPP_NAME=DotenvTest");

        $dotenv = new Dotenv($this->file);
        $dotenv->load();

        $this->assertEquals('development', $dotenv->get('APP_ENV'));
        $this->assertEquals('DotenvTest', $dotenv->get('APP_NAME'));
        $this->assertTrue($dotenv->has('APP_ENV'));
    }

    public function testLoadSingleEnvWithSkippableLines()
    {
        file_put_contents($this->file, "APP_ENV=development\nAPP_NAME=DotenvTest\n#APP_SKIP=Skippable");

        $dotenv = new Dotenv($this->file);
        $dotenv->load();

        $this->assertEquals('development', $dotenv->get('APP_ENV'));
        $this->assertEquals('DotenvTest', $dotenv->get('APP_NAME'));
        $this->assertFalse($dotenv->has('APP_SKIP'));
    }

    public function testInvalidFileSkipsGracefully(): void
    {
        $file = __DIR__ . '/not_readable.env';
        file_put_contents($file, 'KEY=value');
        chmod($file, 0000); // remove read permission

        try {
            $dotenv = new Dotenv([$file]);
            $dotenv->load();

            $this->assertTrue(true); // just to pass
        } finally {
            chmod($file, 0644); // restore permissions
            unlink($file);
        }
    }

    public function testGetEnvWithoutManuallyLoad()
    {
        file_put_contents($this->file, "APP_ENV=development\nAPP_NAME=DotenvTest");

        $dotenv = new Dotenv($this->file);

        $this->assertEquals('development', $dotenv->get('APP_ENV'));
        $this->assertEquals('DotenvTest', $dotenv->get('APP_NAME'));
    }

    public function testGroupedEnvs()
    {
        file_put_contents($this->file, "DB_HOST=localhost\nDB_USER=root");

        $dotenv = new Dotenv($this->file);
        $dotenv->load();

        $group = $dotenv->group('DB');
        $this->assertEquals(['DB_HOST' => 'localhost', 'DB_USER' => 'root'], $group);
    }

    public function testGroupedEnvsWithoutManuallyLoad()
    {
        file_put_contents($this->file, "DB_HOST=localhost\nDB_USER=root");

        $dotenv = new Dotenv($this->file);

        $group = $dotenv->group('DB');
        $this->assertEquals(['DB_HOST' => 'localhost', 'DB_USER' => 'root'], $group);
    }

    public function testNoOverwriteIfDisabled()
    {
        file_put_contents($this->file, "APP_ENV=dev");
        file_put_contents($this->secondFile, "APP_ENV=prod");

        $dotenv = new Dotenv([$this->file, $this->secondFile], overwrite: false);
        $dotenv->load();

        $this->assertEquals('dev', $dotenv->get('APP_ENV'));
    }

    public function testReload()
    {
        file_put_contents($this->file, "APP_ENV=dev");
        $dotenv = new Dotenv($this->file);
        $dotenv->load();

        file_put_contents($this->file, "APP_ENV=prod");
        $dotenv->reload();

        $this->assertEquals('prod', $dotenv->get('APP_ENV'));
    }

    public function testMissingEqualThrows()
    {
        $this->expectException(\RuntimeException::class);
        file_put_contents($this->file, "INVALID_LINE");

        $dotenv = new Dotenv($this->file);
        $dotenv->load();
    }

    public function testInvalidKeyFormatThrows()
    {
        $this->expectException(UnMatch::class);
        file_put_contents($this->file, "123=bad");

        $dotenv = new Dotenv($this->file);
        $dotenv->load();
    }

    public function testGetAllReturnsAllVariables()
    {
        file_put_contents($this->file, "FOO=bar\nBAZ=qux");

        $dotenv = new Dotenv($this->file);
        $dotenv->load();

        $all = $dotenv->get();

        $this->assertArrayHasKey('FOO', $all);
        $this->assertArrayHasKey('BAZ', $all);
    }

    public function testIsAppInProductionDetection()
    {
        file_put_contents($this->file, "ENV=production");

        $dotenv = new Dotenv($this->file, envKey: 'ENV');
        $dotenv->load();

        $ref = new \ReflectionClass($dotenv);
        $method = $ref->getMethod('isAppInProduction');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($dotenv));
    }

    public function testLoadEnvInAppProduction()
    {
        file_put_contents($this->file, "App_Env=production");

        $dotenv = new Dotenv($this->file);

        $this->assertEquals('production', $dotenv->get('App_Env'));
        $dotenv->load();
        $this->assertTrue($dotenv->has('App_Env'));
    }

    public function testLoadEnvFileWithNoBaseDir()
    {
        file_put_contents($this->rootLevelEnvFile, "APP_ENV=testing");

        $dotenv = new Dotenv('.nal.env.test');
        $dotenv->load();

        $this->assertEquals('testing', $dotenv->get('APP_ENV'));
    }

    public function testLoadEnvFileWithNoBaseDirAndInvalidFile()
    {
        $this->expectException(Missing::class);

        $dotenv = new Dotenv('.nal.env.invalid');
        $dotenv->load();
    }

    public function testCreateDotenvWithoutCustomFile()
    {
        $dotenv = new Dotenv();

        $this->assertInstanceOf(Dotenv::class, $dotenv->load());
    }

    public function testLoadEnvWithUpdateEnv()
    {
        file_put_contents($this->file, "APP_ENV=testing\nAPP_EXTRA=EXTRA_ENV");

        $dotenv = new Dotenv($this->file);

        $dotenv->load();
        $this->assertTrue($dotenv->has('APP_EXTRA'));

        file_put_contents($this->file, 'APP_ENV=testing');

        usleep(100000);

        $dotenv->load();

        $this->assertEquals('testing', $dotenv->get('APP_ENV'));
        $this->assertFalse($dotenv->has('APP_EXTRA'));
    }
}
