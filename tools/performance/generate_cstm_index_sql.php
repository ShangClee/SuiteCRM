<?php

declare(strict_types=1);

final class GenerateCstmIndexSql
{
    public function run(array $argv): int
    {
        $opts = $this->parseArgs($argv);
        if (empty($opts['table']) || empty($opts['fields'])) {
            fwrite(STDERR, "Usage: php tools/performance/generate_cstm_index_sql.php --table <table> --fields <field1,field2>\n");
            return 2;
        }

        $table = $opts['table'];
        $fields = array_values(array_filter(array_map('trim', explode(',', $opts['fields']))));
        $cols = array_merge(['id_c'], $fields);
        $cols = array_values(array_unique($cols));

        $name = $this->indexName($table, $cols);

        $create = "CREATE INDEX {$name} ON {$table} (" . implode(',', $cols) . ");";
        $drop = "DROP INDEX {$name} ON {$table};";

        fwrite(STDOUT, json_encode([
            'table' => $table,
            'columns' => $cols,
            'index' => $name,
            'create' => $create,
            'drop' => $drop,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return 0;
    }

    private function parseArgs(array $argv): array
    {
        $table = null;
        $fields = null;
        for ($i = 1; $i < count($argv); $i++) {
            $a = $argv[$i];
            if ($a === '--table' && isset($argv[$i + 1])) {
                $table = $argv[++$i];
                continue;
            }
            if ($a === '--fields' && isset($argv[$i + 1])) {
                $fields = $argv[++$i];
                continue;
            }
        }
        return ['table' => $table, 'fields' => $fields];
    }

    private function indexName(string $table, array $cols): string
    {
        $hash = substr(sha1($table . '|' . implode(',', $cols)), 0, 12);
        $base = 'idx_' . preg_replace('/[^a-z0-9_]+/i', '_', $table) . '_' . $hash;
        return substr($base, 0, 63);
    }
}

exit((new GenerateCstmIndexSql())->run($argv));

