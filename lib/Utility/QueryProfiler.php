<?php

namespace SuiteCRM\Utility;

use DBManager;

#[\AllowDynamicProperties]
class QueryProfiler
{
    public function measure(callable $fn): array
    {
        DBManager::resetQueryCount();

        $start = microtime(true);
        $result = $fn();
        $elapsed = microtime(true) - $start;

        $queries = DBManager::getQueryCount();
        DBManager::resetQueryCount();

        return [
            'result' => $result,
            'queries' => $queries,
            'seconds' => $elapsed,
        ];
    }
}
