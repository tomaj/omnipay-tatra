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

        self::assertSame('', $this->withoutKnownDependencyDeprecations($stderr), $stderr);
        self::assertSame(0, $exitCode, $stdout);
        self::assertNotSame('', $stdout);
    }

    /**
     * omnipay/common calls is_integer(), deprecated in PHP 8.6, in AbstractRequest::getAmount(); only an upstream
     * release can fix it (https://github.com/tomaj/omnipay-tatra/issues/35). Any other output still fails the test.
     */
    private function withoutKnownDependencyDeprecations(string $stderr): string
    {
        return trim((string) preg_replace(
            '~^(PHP )?Deprecated:\s+Function is_integer\(\) is deprecated since 8\.6, use is_int\(\) instead in \S+/vendor/omnipay/common/src/Common/Message/AbstractRequest\.php on line \d+\R?~m',
            '',
            $stderr,
        ));
    }
}
