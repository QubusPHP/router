<?php

declare(strict_types=1);

namespace Qubus\Routing\Route;

use RuntimeException;

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
        if (file_exists($this->path)) {
            $data = require $this->path; // 👈 this will return what the file returns

            if (! is_array($data)) {
                throw new RuntimeException(
                    "Route cache file [{$this->path}] did not return an array."
                );
            }

            return $data;
        }

        $data = $loader();

        if (! is_array($data)) {
            throw new RuntimeException(
                'Route cache loader must return an array; got ' . get_debug_type($data)
            );
        }

        $this->writeCache($data);

        return $data;
    }

    /**
     * @param array $data
     */
    private function writeCache(array $data): void
    {
        $dir = dirname($this->path);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $php = '<?php' . PHP_EOL . PHP_EOL
        . 'declare(strict_types=1);' . PHP_EOL . PHP_EOL
        . 'return ' . var_export($data, true) . ';' . PHP_EOL;

        file_put_contents($this->path, $php);
    }

    public function clear(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function path(): string
    {
        return $this->path;
    }
}
