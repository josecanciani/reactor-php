<?php declare(strict_types=1);

namespace Reactor\Tests\Php;

use PHPUnit\Framework\TestCase;

final class StrictTypesTest extends TestCase {
    function testAllFilesHaveStrictMode(): void {
        $codePaths = [
            __DIR__ . '/../../src',
            __DIR__ . '/../../tests'
        ];
        foreach ($this->getPhpFiles(...$codePaths) as $phpFile) {
            $fileObject = new \SplFileObject($phpFile);
            $this->assertEquals('<?php declare(strict_types=1);' . PHP_EOL, $fileObject->current());
        }
    }

    private function getPhpFiles(string ...$baseDirs) {
        foreach ($baseDirs as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir)
            );
            foreach ($iterator as $file) {
                if (!$file->isDir() && $this->str_ends_with($file->getPathname(), '.php')) {
                    yield $file->getPathname();
                }
            }
        }
    }

    private function str_ends_with(string $haystack, string $needle): bool {
        if (function_exists('str_ends_with')) {
            return str_ends_with($haystack, $needle);
        }
        // PHP < 8 polyfill
        $needle_len = strlen($needle);
        return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, - $needle_len));
    }
}
