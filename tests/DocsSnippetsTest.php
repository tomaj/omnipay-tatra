<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The documented code runs with the current version:
 *
 * - every script in docs/snippets/ (the pages import regions of them) runs with assertions enabled;
 * - every ```php block of README.md, UPGRADE-5.0.md and docs/ runs in its own process, with the variables of
 *   docs/snippets/_inline-prelude.php ($gateway, $order, $hmacKey, ...) already defined. A block directly after
 *   `<!-- snippet: skip -->` is not run: code that shows 4.x or elides arguments with `...`.
 *
 * The complete recipes in examples/ run in the Examples workflow instead, against payment-simulator.
 */
final class DocsSnippetsTest extends TestCase
{
    private const string SKIP = '<!-- snippet: skip -->';

    /**
     * @return array<string, array{string}>
     */
    public static function snippetProvider(): array
    {
        $snippets = [];
        $files = glob(__DIR__ . '/../docs/snippets/*.php');
        foreach ($files === false ? [] : $files as $file) {
            if (!str_starts_with(basename($file), '_')) {
                $snippets[basename($file)] = [$file];
            }
        }

        return $snippets;
    }

    #[DataProvider('snippetProvider')]
    public function testSnippetRuns(string $file): void
    {
        [$exitCode, $stdout, $stderr] = $this->runPhp([$file]);

        self::assertSame('', $this->withoutDependencyDeprecations($stderr), $stderr);
        self::assertSame(0, $exitCode, $stdout);
        self::assertNotSame('', $stdout);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function inlineBlockProvider(): iterable
    {
        $root = dirname(__DIR__);
        $files = [$root . '/README.md', $root . '/UPGRADE-5.0.md'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/docs', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            $path = $file->getPathname();
            if ($file->getExtension() === 'md' && !str_contains($path, '/node_modules/') && !str_contains($path, '/.vitepress/')) {
                $files[] = $path;
            }
        }
        sort($files);

        foreach ($files as $path) {
            foreach (self::phpBlocks((string) file_get_contents($path)) as $line => $code) {
                yield substr($path, strlen($root) + 1) . ':' . $line => [$code];
            }
        }
    }

    public function testDocumentationHasInlineBlocks(): void
    {
        self::assertGreaterThanOrEqual(6, iterator_count(self::inlineBlockProvider()));
    }

    #[DataProvider('inlineBlockProvider')]
    public function testInlineBlockRuns(string $code): void
    {
        $script = tempnam(sys_get_temp_dir(), 'snippet');
        self::assertIsString($script);
        try {
            file_put_contents($script, "<?php\n\ndeclare(strict_types=1);\n\n" . $code);
            [$exitCode, $stdout, $stderr] = $this->runPhp([$script], __DIR__ . '/../docs/snippets/_inline-prelude.php');
        } finally {
            unlink($script);
        }

        self::assertSame('', $this->withoutDependencyDeprecations($stderr), "The block wrote to stderr:\n{$code}\n{$stderr}");
        self::assertSame(0, $exitCode, "The block failed:\n{$code}\n{$stdout}");
    }

    /**
     * @return array<int, string> line number => code of each ```php block not preceded by the skip marker
     */
    private static function phpBlocks(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $blocks = [];
        $previous = '';
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            if (preg_match('/^```(\w+)/', $lines[$i], $match) !== 1) {
                if (trim($lines[$i]) !== '') {
                    $previous = trim($lines[$i]);
                }
                continue;
            }
            $start = $i + 1;
            $body = [];
            for ($i++; $i < $count && !str_starts_with($lines[$i], '```'); $i++) {
                $body[] = $lines[$i];
            }
            if ($match[1] === 'php' && $previous !== self::SKIP) {
                $blocks[$start] = (string) preg_replace('/^<\?php\s*/', '', implode("\n", $body) . "\n");
            }
            $previous = '```';
        }

        return $blocks;
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string, string} exit code, stdout, stderr
     */
    private function runPhp(array $arguments, ?string $prepend = null): array
    {
        $command = [PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1', '-d', 'display_errors=stderr', '-d', 'log_errors=0', '-d', 'error_reporting=-1'];
        if ($prepend !== null) {
            $command = [...$command, '-d', 'auto_prepend_file=' . $prepend];
        }
        $process = proc_open([...$command, ...$arguments], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
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
