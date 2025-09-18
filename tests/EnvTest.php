<?php

namespace NALDotenvTests;

use NAL\Dotenv\Env;
use NAL\Dotenv\Exceptions\InvalidEnvKeyFormat;
use NAL\Dotenv\Exceptions\InvalidEnvLine;
use NAL\Dotenv\Exceptions\InvalidJson;
use NAL\Dotenv\Exceptions\MissingLoader;
use NAL\Dotenv\Exceptions\MissingParser;
use NAL\Dotenv\Exceptions\UnableToOpenFileException;
use NAL\Dotenv\Exceptions\UnsupportedFileTypeException;
use NAL\Dotenv\Loader\DotenvLoader;
use NAL\Dotenv\Loader\EnvLoader;
use NAL\Dotenv\Loader\JsonLoader;
use NAL\Dotenv\Loader\LoaderRegistry;
use NAL\Dotenv\PathResolver;
use PHPUnit\Framework\TestCase;
use Tests\Unit\_data\CustomLoader;

class EnvTest extends TestCase
{
    private string $dir = __DIR__ . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR;

    protected function setUp(): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }

    private function createEnvFile(string $name, string $content = "")
    {
        $file = $this->dir . $name;

        file_put_contents($file, $content);

        return $file;
    }

    private function dropEnvFile(string $name)
    {
        if (file_exists($name)) {
            unlink($name);
        } else if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . $name)) {
            unlink(__DIR__ . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . $name);
        }
    }

    public function testEnvLoadCorrectly()
    {
        $file = $this->createEnvFile('.env', "FOO=bar\n");

        $env = Env::create(new DotenvLoader($file));

        $this->assertSame('bar', $env->get('FOO'));

        $this->dropEnvFile($file);
    }

    public function testEnvLoadJsonFileCorrectly()
    {
        $file = $this->createEnvFile('env.json', "{\n" . '"APP":{' . "\n" . '"ENV":"testing"' . "\n" . '}' . "\n}\n");

        $env = Env::create(new JsonLoader($file));

        $this->assertSame('testing', $env->get('APP_ENV'));

        $this->dropEnvFile($file);
    }

    public function testThrowExceptionIfLoadJsonFileWithDotenvLoader()
    {
        $file = $this->createEnvFile('env.json', "{\n" . '"APP":{' . "\n" . '"ENV":"testing"' . "\n" . '}' . "\n}\n");

        $this->expectException(UnsupportedFileTypeException::class);

        try {
            $env = Env::create(new DotenvLoader($file));

            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testThrowExceptionIfLoadNotJsonFileWithJsonLoader()
    {
        $file = $this->createEnvFile('.env', "FOO=bar\n");

        $this->expectException(UnsupportedFileTypeException::class);

        try {
            $env = Env::create(new JsonLoader($file));

            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testEnvLoadWithGuessLoaderCorrectly()
    {
        $file = $this->createEnvFile('.env', "FOO=bar\n");

        $env = Env::create(name: $file);

        $this->assertSame('bar', $env->get('FOO'));

        $this->dropEnvFile($file);
    }

    public function testEnvLoadWithGuessLoaderCorrectlyForJsonFile()
    {
        $file = $this->createEnvFile('env.json', "{\n" . '"APP":{' . "\n" . '"ENV":"testing"' . "\n" . '},' . "\n" . '"SECRET":"abcd"' ."\n}\n");

        $env = Env::create(name: $file);

        $this->assertSame('testing', $env->get('APP_ENV'));

        $this->dropEnvFile($file);
    }

    public function testEnvLoadMultipleFile()
    {
        $file1 = $this->createEnvFile('.env', "FOO=bar\n");
        $file2 = $this->createEnvFile('env.json', "{\n" . '"APP":{' . "\n" . '"ENV":"testing"' . "\n" . '}' . "\n}\n");

        $env = Env::create(name: [$file1, $file2]);

        $this->assertSame('bar', $env->get('FOO'));
        $this->assertSame('testing', $env->get('APP_ENV'));

        $this->dropEnvFile($file1);
        $this->dropEnvFile($file2);
    }

    public function testThrowExceptionIfEnvFileNotFound()
    {
        $file = 'env.unknown';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to locate $file");

        $env = Env::create(name: $file);

        $env->load();
    }

    public function testEnvFileLoadWithCustomPath()
    {
        $file = $this->createEnvFile('.env', "FOO=bar\n");

        $env = Env::create(loader: new DotenvLoader('.env', resolver: new PathResolver($this->dir)));

        $this->assertSame('bar', $env->get('FOO'));
    }

    public function testThrowExceptionEnvLoadWithGuessLoaderForUnregisteredFileType()
    {
        $file = $this->createEnvFile('env.txt', "APP_TEST=true");

        $this->expectException(MissingLoader::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testThrowExceptionEnvLoadWithInvalidEnvLine()
    {
        $file = $this->createEnvFile('.env', "APP_ENV=testing\nAPP_INVALID");

        $this->expectException(InvalidEnvLine::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testThrowExceptionEnvLoadWithInvalidEnvKey()
    {
        $file = $this->createEnvFile('.env', "APP_ENV=testing\n123=abc");

        $this->expectException(InvalidEnvKeyFormat::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testThrowExceptionEnvLoadWithInvalidEnvKeyInJsonFile()
    {
        $file = $this->createEnvFile('env.json', "{\n" . '"APP":{' . "\n" . '"ENV":"testing"' . "\n" . '},' . "\n" . '"123":"abcd"' ."\n}\n");

        $this->expectException(InvalidEnvKeyFormat::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testThrowExceptionEnvLoadWithInvalidJsonData()
    {
        $invalidJson = <<<JSON
{
    "APP": {
        "ENV": "testing"
    // missing closing brace here
JSON;

        $file = $this->createEnvFile('env.json', $invalidJson);

        $this->expectException(InvalidJson::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testEnvLoadNotOverridenEnvVarByDotenvParser()
    {
        $file = $this->createEnvFile('.env', "SECRET=ABC\nSECRET=123");

        $env = Env::create(name: $file);

        $this->assertSame('ABC', $env->get('SECRET'));
    }

    public function testEnvLoadOverrideEnvVarByDotenvParser()
    {
        $file = $this->createEnvFile('.env', "SECRET=ABC\nSECRET=123");

        $env = Env::create(name: $file, override: true);

        $this->assertSame('123', $env->get('SECRET'));
    }

    public function testSafeLoading()
    {
        $file = $this->createEnvFile('env.txt', "APP_TEST=true"); //unregistered file type

        $env = Env::create(name: $file);

        $this->assertIsArray($env->safeLoad());

        $this->dropEnvFile($file);
    }

// Uncomment this if you want to test default .env file load, to get 100% coverage
//    public function testEnvLoadWithGuessLoaderWithDefaultEnvFileCorrectly()
//    {
//        $original = getcwd();
//        $loadRegistry = new LoaderRegistry();
//
//        $loadRegistry->register('dotenv', fn(...$args) => new DotenvLoader(...$args));
//        $resolver = new PathResolver(dirname(__DIR__, 2));
//
//        try {
//            chdir(__DIR__);
//            $env = Env::create(new EnvLoader($loadRegistry ,resolver: $resolver));
//            $this->assertSame('foo', $env->get('BAR'));
//        } finally {
//            chdir($original);
//        }
//    }

    public function testEnvLoadGroupEnvVarCorrectly()
    {
        $file = $this->createEnvFile('.env', "APP_NAME=nubo\nAPP.ENV=testing\n");

        $env = Env::create(name: $file);

        $this->assertContains('nubo', $env->group('APP')); // APP_NAME
        $this->assertContains('testing', $env->group('APP')); // APP.ENV

        $this->dropEnvFile($file);
    }

    public function testSyncWithServer()
    {
        $file = $this->createEnvFile('.env', "APP_NAME=nubo\nAPP.ENV=testing\n");

        $env = Env::create(name: $file);

        $this->assertContains('nubo', $env->group('APP'));

        $this->createEnvFile('.env', "APP.ENV=testing\n");

        $this->assertNotContains('nubo', $env->group('APP'));

        $this->dropEnvFile($file);
    }

    public function testGetEnvWithStaticMethod()
    {
        $file = $this->createEnvFile('.env', "APP_NAME=nubo\nAPP.ENV=testing\n");

        $env = Env::create(name: $file);

        $this->assertSame('testing', $env::get('APP.ENV'));

        $this->dropEnvFile($file);
    }

    public function testGetGroupEnvWithStaticMethod()
    {
        $file = $this->createEnvFile('.env', "APP_NAME=nubo\nAPP.ENV=testing\n");

        $env = Env::create(name: $file);

        $this->assertContains('testing', $env::group('APP'));

        $this->dropEnvFile($file);
    }

    public function testDump()
    {
        $file = $this->createEnvFile('.env', "APP_NAME=nubo\nAPP.ENV=testing\n");

        $env = Env::create(name: $file, cache: true);

        $dump = $env->dump();

        $this->assertIsArray($dump);
        $this->assertArrayHasKey('envs', $dump);
        $this->assertArrayHasKey('groups', $dump);
        $this->assertArrayHasKey('process', $dump);

        $this->dropEnvFile($file);
    }

    public function testThrowExceptionOnUnexistParser()
    {
        $file = $this->createEnvFile('env.custom');

        $this->expectException(MissingParser::class);

        try {
            $registry = new LoaderRegistry();

            $registry->register('custom', fn(...$args) => new CustomLoader(...$args));

            $env = Env::create(name: $file,registry: $registry);

            $env->load();
        } finally {
            $this->dropEnvFile($file);
        }
    }

    public function testInvalidFileSkipsGracefullyOnDotenvFile()
    {
        $file = $this->dir . '.env.error';
        mkdir($file);

        $this->expectException(UnableToOpenFileException::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            rmdir($file);
        }
    }

    public function testInvalidFileSkipsGracefullyOnJsonFile()
    {
        $file = $this->dir . '.env.error.json';
        mkdir($file);

        $this->expectException(UnableToOpenFileException::class);

        try {
            $env = Env::create(name: $file);
            $env->load();
        } finally {
            rmdir($file);
        }
    }

    public function testCallUnknownMagicMethod()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage("Call to undefined method: unknown()");

        $env = Env::create();

        $env->unknown();
    }

    public function testCallUnknownMagicStaticMethod()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage("Call to undefined method: unknown()");

        Env::unknown();
    }
}
