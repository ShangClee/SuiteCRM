<?php

namespace SuiteCRM\Utility;

#[\AllowDynamicProperties]
class NPlusOneDetector
{
    public function scanFile(string $filePath): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        $findings = [];
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $loop = $this->detectLoop($lines[$i]);
            if (!$loop) {
                continue;
            }

            $window = [];
            for ($j = $i; $j < min($count, $i + 60); $j++) {
                $window[] = $lines[$j];
            }
            $block = implode("\n", $window);

            foreach ($this->detectCalls($block) as $call) {
                $findings[] = [
                    'file' => $filePath,
                    'line' => $i + 1,
                    'loop' => $loop,
                    'call' => $call,
                ];
            }
        }

        return $findings;
    }

    protected function detectLoop(string $line): ?string
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

    protected function detectCalls(string $code): array
    {
        $calls = [];
        $patterns = [
            'BeanFactory::getBean' => '/BeanFactory::getBean\\s*\\(/',
            'BeanFactory::newBean' => '/BeanFactory::newBean\\s*\\(/',
            'get_linked_beans' => '/->get_linked_beans\\s*\\(/',
            'Link2::getBeans' => '/->getBeans\\s*\\(/',
            'SugarBean::retrieve' => '/->retrieve\\s*\\(/',
        ];

        foreach ($patterns as $name => $re) {
            if (preg_match($re, $code)) {
                $calls[] = $name;
            }
        }

        return $calls;
    }
}

