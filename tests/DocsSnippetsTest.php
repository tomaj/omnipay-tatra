<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every example of the documentation site is a script in docs/snippets/ that the pages import. Each one runs here,
 * with assertions enabled, so the documented code works with the current version.
 */
final class DocsSnippetsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function snippetProvider(): array
    {
        $snippets = [];
        $files = glob(__DIR__ . '/../docs/snippets/*.php');
        foreach ($files === false ? [] : $files as $file) {
            $snippets[basename($file)] = [$file];
        }

        return $snippets;
    }

    #[DataProvider('snippetProvider')]
    public function testSnippetRuns(string $file): void
    {
        $command = [PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1', '-d', 'display_errors=stderr', '-d', 'log_errors=0', '-d', 'error_reporting=-1', $file];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame('', $this->withoutDependencyDeprecations($stderr), $stderr);
        self::assertSame(0, $exitCode, $stdout);
        self::assertNotSame('', $stdout);
    }

    /**
     * Deprecations raised inside dependencies are not ours to fix, like ignoreIndirectDeprecations in phpunit.xml:
     * omnipay/common calls is_integer(), deprecated in PHP 8.6 (https://github.com/tomaj/omnipay-tatra/issues/35), and
     * the lowest supported dependencies predate PHP 8.4. A deprecation in this package or any other output still fails.
     */
    private function withoutDependencyDeprecations(string $stderr): string
    {
        $lines = preg_split('~\R~', $stderr);
        $lines = array_filter(
            $lines === false ? [$stderr] : $lines,
            static fn(string $line): bool => trim($line) !== '' && preg_match('~^(PHP )?Deprecated:\s.* in \S+/vendor/\S+ on line \d+$~', $line) !== 1,
        );

        return implode("\n", $lines);
    }
}
