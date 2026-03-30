<?php

declare(strict_types=1);

final class LegacyApiUsageAnalyzer
{
    private const DEFAULT_FORMAT = 'json';

    private const ENDPOINT_PATTERNS = [
        '/soap.php' => '#\s/(?:[^"\s]*?/)?soap\.php(?:[?\s]|$)#i',
        '/service/v4/rest.php' => '#\s/(?:[^"\s]*?/)?service/v4/rest\.php(?:[?\s]|$)#i',
        '/service/v4_1/rest.php' => '#\s/(?:[^"\s]*?/)?service/v4_1/rest\.php(?:[?\s]|$)#i',
        '/service/*/soap.php' => '#\s/(?:[^"\s]*?/)?service/v\d+(?:_\d+)?/soap\.php(?:[?\s]|$)#i',
    ];

    public function run(array $argv): int
    {
        $args = $this->parseArgs($argv);
        $logs = $args['logs'];
        $format = $args['format'];
        $since = $args['since'];
        $until = $args['until'];

        $aggregates = [];
        $totalMatches = 0;

        foreach ($logs as $logPath) {
            $handle = @fopen($logPath, 'rb');
            if ($handle === false) {
                fwrite(STDERR, "Failed to open log: {$logPath}\n");
                return 2;
            }

            while (($line = fgets($handle)) !== false) {
                $parsed = $this->parseApacheCombinedLine($line);
                if ($parsed === null) {
                    continue;
                }

                $ts = $parsed['timestamp'];
                if ($since !== null && $ts < $since) {
                    continue;
                }
                if ($until !== null && $ts > $until) {
                    continue;
                }

                $match = $this->matchLegacyEndpoint($parsed['request']);
                if ($match === null) {
                    continue;
                }

                $totalMatches++;
                $key = $parsed['ip'] . "\n" . $parsed['user_agent'] . "\n" . $match;

                if (!isset($aggregates[$key])) {
                    $aggregates[$key] = [
                        'caller_ip' => $parsed['ip'],
                        'user_agent' => $parsed['user_agent'],
                        'endpoint' => $match,
                        'count' => 0,
                        'first_seen' => $ts->format(DATE_ATOM),
                        'last_seen' => $ts->format(DATE_ATOM),
                    ];
                }

                $aggregates[$key]['count']++;
                if ($ts->format('U') < strtotime($aggregates[$key]['first_seen'])) {
                    $aggregates[$key]['first_seen'] = $ts->format(DATE_ATOM);
                }
                if ($ts->format('U') > strtotime($aggregates[$key]['last_seen'])) {
                    $aggregates[$key]['last_seen'] = $ts->format(DATE_ATOM);
                }
            }

            fclose($handle);
        }

        $rows = array_values($aggregates);
        usort($rows, static function (array $a, array $b): int {
            return $b['count'] <=> $a['count'];
        });

        return $this->emit($format, [
            'generated_at' => gmdate(DATE_ATOM),
            'total_matches' => $totalMatches,
            'callers' => $rows,
        ]);
    }

    private function parseArgs(array $argv): array
    {
        $logs = [];
        $format = self::DEFAULT_FORMAT;
        $since = null;
        $until = null;

        $i = 1;
        while ($i < count($argv)) {
            $arg = $argv[$i];

            if ($arg === '--log') {
                $i++;
                if ($i >= count($argv)) {
                    $this->usageAndExit(2);
                }
                $logs[] = $argv[$i];
                $i++;
                continue;
            }

            if ($arg === '--format') {
                $i++;
                if ($i >= count($argv)) {
                    $this->usageAndExit(2);
                }
                $format = strtolower(trim((string) $argv[$i]));
                $i++;
                continue;
            }

            if ($arg === '--since') {
                $i++;
                if ($i >= count($argv)) {
                    $this->usageAndExit(2);
                }
                $since = $this->parseIso8601((string) $argv[$i]);
                $i++;
                continue;
            }

            if ($arg === '--until') {
                $i++;
                if ($i >= count($argv)) {
                    $this->usageAndExit(2);
                }
                $until = $this->parseIso8601((string) $argv[$i]);
                $i++;
                continue;
            }

            if ($arg === '--help' || $arg === '-h') {
                $this->usageAndExit(0);
            }

            $this->usageAndExit(2);
        }

        if ($logs === []) {
            $this->usageAndExit(2);
        }

        if (!in_array($format, ['json', 'csv'], true)) {
            fwrite(STDERR, "Unsupported format: {$format}\n");
            $this->usageAndExit(2);
        }

        if ($since !== null && $until !== null && $since > $until) {
            fwrite(STDERR, "--since must be <= --until\n");
            return [];
        }

        return [
            'logs' => $logs,
            'format' => $format,
            'since' => $since,
            'until' => $until,
        ];
    }

    private function usageAndExit(int $code): void
    {
        $msg = <<<TXT
Usage:
  php build/api-audit/legacy_api_usage.php --log <path> [--log <path> ...] [--format json|csv] [--since <ISO8601>] [--until <ISO8601>]

Examples:
  php build/api-audit/legacy_api_usage.php --log /var/log/apache2/access.log --format json
  php build/api-audit/legacy_api_usage.php --log access.log --since 2026-01-01T00:00:00Z --until 2026-02-01T00:00:00Z

TXT;
        fwrite($code === 0 ? STDOUT : STDERR, $msg);
        exit($code);
    }

    private function parseIso8601(string $value): ?DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
        if ($dt === false) {
            $dt = new DateTimeImmutable($value);
        }
        return $dt;
    }

    private function parseApacheCombinedLine(string $line): ?array
    {
        $pattern = '#^(?<ip>\S+) \S+ \S+ \[(?<ts>[^\]]+)\] "(?<req>[^"]*)" (?<status>\d{3}) (?<bytes>\S+) "(?<ref>[^"]*)" "(?<ua>[^"]*)"#';
        if (!preg_match($pattern, $line, $m)) {
            return null;
        }

        $tsRaw = (string) $m['ts'];
        $ts = DateTimeImmutable::createFromFormat('d/M/Y:H:i:s O', $tsRaw);
        if ($ts === false) {
            return null;
        }

        return [
            'ip' => (string) $m['ip'],
            'timestamp' => $ts,
            'request' => (string) $m['req'],
            'status' => (int) $m['status'],
            'user_agent' => (string) $m['ua'],
        ];
    }

    private function matchLegacyEndpoint(string $requestLine): ?string
    {
        foreach (self::ENDPOINT_PATTERNS as $canonical => $pattern) {
            if (preg_match($pattern, $requestLine) === 1) {
                return $canonical;
            }
        }
        return null;
    }

    private function emit(string $format, array $payload): int
    {
        if ($format === 'json') {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                fwrite(STDERR, "Failed to encode JSON output\n");
                return 2;
            }
            fwrite(STDOUT, $json . "\n");
            return 0;
        }

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            fwrite(STDERR, "Failed to open stdout\n");
            return 2;
        }

        fputcsv($out, ['caller_ip', 'user_agent', 'endpoint', 'count', 'first_seen', 'last_seen']);
        foreach ($payload['callers'] as $row) {
            fputcsv($out, [
                $row['caller_ip'],
                $row['user_agent'],
                $row['endpoint'],
                $row['count'],
                $row['first_seen'],
                $row['last_seen'],
            ]);
        }
        fclose($out);
        return 0;
    }
}

$analyzer = new LegacyApiUsageAnalyzer();
exit($analyzer->run($argv));

