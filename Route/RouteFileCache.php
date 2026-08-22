<?php

declare(strict_types=1);

namespace Qubus\Routing\Route;

use RuntimeException;
use Throwable;

use function chmod;
use function dirname;
use function file_put_contents;
use function get_debug_type;
use function is_array;
use function is_dir;
use function is_file;
use function mkdir;
use function rename;
use function strlen;
use function tempnam;
use function unlink;
use function var_export;

use const LOCK_EX;
use const PHP_EOL;

final readonly class RouteFileCache
{
    public function __construct(
        private string $path,
    ) {
    }

    /**
     * @param callable(): array $loader
     */
    public function get(callable $loader): array
    {
        if ($this->exists()) {
            return $this->read();
        }

        $data = $loader();

        if (! is_array($data)) {
            throw new RuntimeException(
                'Route cache loader must return an array; got ' . get_debug_type($data)
            );
        }

        $this->put($data);

        return $data;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function read(): array
    {
        if (! $this->exists()) {
            throw new RuntimeException("Route cache file [{$this->path}] does not exist.");
        }

        try {
            $data = (static function (string $path): mixed {
                return require $path;
            })($this->path);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to read route cache file [{$this->path}]: {$exception->getMessage()}",
                previous: $exception
            );
        }

        if (! is_array($data)) {
            throw new RuntimeException(
                "Route cache file [{$this->path}] did not return an array."
            );
        }

        return $data;
    }

    public function put(array $data): void
    {
        $dir = dirname($this->path);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Unable to create route cache directory [{$dir}].");
        }

        $php = '<?php' . PHP_EOL . PHP_EOL
        . 'declare(strict_types=1);' . PHP_EOL . PHP_EOL
        . 'return ' . var_export($data, true) . ';' . PHP_EOL;

        $temporaryPath = tempnam($dir, '.routes-');

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create a temporary route cache file in [{$dir}].");
        }

        try {
            $bytes = file_put_contents($temporaryPath, $php, LOCK_EX);

            if ($bytes === false || $bytes !== strlen($php)) {
                throw new RuntimeException("Unable to write route cache file [{$this->path}].");
            }

            if (! chmod($temporaryPath, 0644)) {
                throw new RuntimeException("Unable to secure route cache file [{$temporaryPath}].");
            }

            if (! rename($temporaryPath, $this->path)) {
                throw new RuntimeException("Unable to replace route cache file [{$this->path}].");
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function clear(): void
    {
        if ($this->exists() && ! unlink($this->path)) {
            throw new RuntimeException("Unable to clear route cache file [{$this->path}].");
        }
    }

    public function path(): string
    {
        return $this->path;
    }
}
