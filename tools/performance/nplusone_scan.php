<?php

declare(strict_types=1);

final class NPlusOneScan
{
    private array $ignoreDirs = [
        '.git',
        'cache',
        'custom',
        'upload',
        'vendor',
        'node_modules',
        'themes',
        'Zend',
        'XTemplate',
    ];

    public function run(array $argv): int
    {
        $opts = $this->parseArgs($argv);
        $root = realpath(__DIR__ . '/../..');
        if (!$root) {
            fwrite(STDERR, "Cannot resolve project root\n");
            return 2;
        }

        $scanPath = $opts['path'] ? ($root . DIRECTORY_SEPARATOR . trim($opts['path'], '/')) : $root;
        $scanPathReal = realpath($scanPath);
        if (!$scanPathReal || !is_dir($scanPathReal)) {
            fwrite(STDERR, "Invalid --path: {$opts['path']}\n");
            return 2;
        }

        $findings = $this->scan($scanPathReal);
        usort($findings, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        $limit = $opts['limit'];
        $out = [];
        $i = 0;
        foreach ($findings as $f) {
            if (++$i > $limit) {
                break;
            }
            $out[] = [
                'score' => $f['score'],
                'file' => $this->relPath($root, $f['file']),
                'line' => $f['line'],
                'kind' => $f['kind'],
                'loop' => $f['loop'],
                'call' => $f['call'],
            ];
        }

        fwrite(STDOUT, json_encode([
            'scannedPath' => $this->relPath($root, $scanPathReal),
            'totalFindings' => count($findings),
            'returned' => count($out),
            'findings' => $out,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return 0;
    }

    private function parseArgs(array $argv): array
    {
        $path = null;
        $limit = 100;
        for ($i = 1; $i < count($argv); $i++) {
            $a = $argv[$i];
            if ($a === '--path' && isset($argv[$i + 1])) {
                $path = $argv[++$i];
                continue;
            }
            if ($a === '--limit' && isset($argv[$i + 1])) {
                $limit = max(1, (int) $argv[++$i]);
                continue;
            }
        }
        return ['path' => $path, 'limit' => $limit];
    }

    private function scan(string $baseDir): array
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
                function (SplFileInfo $current, $key, $iterator): bool {
                    if ($current->isDir()) {
                        return !in_array($current->getFilename(), $this->ignoreDirs, true);
                    }
                    if ($current->isFile()) {
                        $name = $current->getFilename();
                        if (str_ends_with($name, '.php')) {
                            return true;
                        }
                        return false;
                    }
                    return false;
                }
            )
        );

        $findings = [];
        foreach ($it as $fileInfo) {
            $file = (string) $fileInfo;
            $fileFindings = $this->scanFile($file);
            foreach ($fileFindings as $f) {
                $findings[] = $f;
            }
        }
        return $findings;
    }

    private function scanFile(string $file): array
    {
        $fh = @fopen($file, 'rb');
        if (!$fh) {
            return [];
        }

        $window = [];
        $maxWindow = 60;
        $lineNo = 0;
        $findings = [];
        while (($line = fgets($fh)) !== false) {
            $lineNo++;
            $window[] = ['n' => $lineNo, 's' => $line];
            if (count($window) > $maxWindow) {
                array_shift($window);
            }

            $loopType = $this->detectLoop($line);
            if (!$loopType) {
                continue;
            }

            $lookahead = [$line];
            $peekLines = 0;
            $pos = ftell($fh);
            while ($peekLines < 40 && ($next = fgets($fh)) !== false) {
                $peekLines++;
                $lookahead[] = $next;
                if (strpos($next, '{') !== false) {
                    break;
                }
                if (strpos($next, ';') !== false) {
                    break;
                }
            }
            fseek($fh, $pos);

            $block = implode('', $lookahead);
            foreach ($this->detectCalls($block) as $call) {
                $findings[] = [
                    'score' => $call['score'],
                    'file' => $file,
                    'line' => $lineNo,
                    'kind' => 'loop-call',
                    'loop' => $loopType,
                    'call' => $call['call'],
                ];
            }
        }

        fclose($fh);
        return $findings;
    }

    private function detectLoop(string $line): ?string
    {
        $l = ltrim($line);
        if (str_starts_with($l, 'foreach')) {
            return 'foreach';
        }
        if (str_starts_with($l, 'for')) {
            return 'for';
        }
        if (str_starts_with($l, 'while')) {
            return 'while';
        }
        return null;
    }

    private function detectCalls(string $code): array
    {
        $calls = [];
        $patterns = [
            ['re' => '/BeanFactory::(getBean|newBean)\\s*\\(/', 'call' => 'BeanFactory', 'score' => 5],
            ['re' => '/->get_linked_beans\\s*\\(/', 'call' => 'get_linked_beans', 'score' => 4],
            ['re' => '/->getBeans\\s*\\(/', 'call' => 'getBeans', 'score' => 3],
            ['re' => '/->retrieve\\s*\\(/', 'call' => 'retrieve', 'score' => 2],
        ];

        foreach ($patterns as $p) {
            if (preg_match($p['re'], $code)) {
                $calls[] = ['call' => $p['call'], 'score' => $p['score']];
            }
        }
        return $calls;
    }

    private function relPath(string $root, string $path): string
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }
        return $path;
    }
}

$scanner = new NPlusOneScan();
exit($scanner->run($argv));

