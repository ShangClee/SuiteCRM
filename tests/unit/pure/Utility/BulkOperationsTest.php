<?php

declare(strict_types=1);

namespace {
    const REL_LHS = 'LHS';
    const REL_RHS = 'RHS';

    function create_guid(): string
    {
        static $i = 0;
        $i++;
        return 'guid-' . $i;
    }

    final class TimeDate
    {
        public static function getInstance(): self
        {
            return new self();
        }

        public function nowDb(): string
        {
            return '2026-03-30 00:00:00';
        }
    }

    final class DBManagerFactory
    {
        public static FakeDb $db;

        public static function getInstance()
        {
            return self::$db;
        }
    }

    final class BeanFactory
    {
        public static function newBean($module)
        {
            $bean = new \stdClass();
            $bean->table_name = strtolower((string) $module);
            $bean->field_defs = ['deleted' => true, 'date_modified' => true];
            return $bean;
        }
    }

    final class FakeResult
    {
        private int $i = 0;
        public function __construct(private array $rows)
        {
        }

        public function next(): ?array
        {
            if ($this->i >= count($this->rows)) {
                return null;
            }
            return $this->rows[$this->i++];
        }
    }

    final class FakeDb
    {
        public int $queries = 0;
        public array $joinRows = [];
        public array $results = [];
        private int $affected = 0;

        public function quote(string $s): string
        {
            return str_replace("'", "''", $s);
        }

        public function quoted(string $s): string
        {
            return "'" . $this->quote($s) . "'";
        }

        public function now(): string
        {
            return "'2026-03-30 00:00:00'";
        }

        public function getAffectedRowCount($res): int
        {
            return $this->affected;
        }

        public function query(string $sql, bool $dieOnError = false, string $msg = '', bool $suppress = false)
        {
            $this->queries++;
            $this->affected = 0;

            if (str_starts_with($sql, 'SELECT')) {
                return $this->select($sql);
            }
            if (str_starts_with($sql, 'UPDATE')) {
                $this->update($sql);
                return true;
            }
            if (str_starts_with($sql, 'INSERT')) {
                $this->insert($sql);
                return true;
            }

            return true;
        }

        public function fetchByAssoc($res, $encode = true)
        {
            if ($res instanceof FakeResult) {
                $row = $res->next();
                return $row ?? false;
            }
            return false;
        }

        private function select(string $sql): FakeResult
        {
            if (!empty($this->results)) {
                return new FakeResult(array_shift($this->results));
            }

            if (!preg_match("/FROM\\s+(\\w+)\\s+WHERE\\s+(\\w+)='([^']+)'\\s+AND\\s+(\\w+)\\s+IN\\s+\\(([^\\)]+)\\)/", $sql, $m)) {
                return new FakeResult([]);
            }
            $table = $m[1];
            $focusKey = $m[2];
            $focusId = $m[3];
            $relatedKey = $m[4];
            $in = $m[5];
            $ids = array_map(static function ($v): string {
                return trim($v, " \t\n\r\0\x0B'");
            }, explode(',', $in));

            $rows = [];
            foreach ($ids as $rid) {
                $k = "{$table}|{$focusKey}|{$relatedKey}|{$focusId}|{$rid}";
                if (isset($this->joinRows[$k])) {
                    $rows[] = ['rid' => $rid, 'deleted' => $this->joinRows[$k]];
                }
            }
            return new FakeResult($rows);
        }

        private function update(string $sql): void
        {
            if (!preg_match("/UPDATE\\s+(\\w+)\\s+SET\\s+.+\\s+WHERE\\s+(\\w+)='([^']+)'\\s+AND\\s+(\\w+)\\s+IN\\s+\\(([^\\)]+)\\)\\s+AND\\s+deleted=(\\d)/", $sql, $m)) {
                return;
            }
            $table = $m[1];
            $focusKey = $m[2];
            $focusId = $m[3];
            $relatedKey = $m[4];
            $in = $m[5];
            $fromDeleted = (int) $m[6];
            $ids = array_map(static function ($v): string {
                return trim($v, " \t\n\r\0\x0B'");
            }, explode(',', $in));

            foreach ($ids as $rid) {
                $k = "{$table}|{$focusKey}|{$relatedKey}|{$focusId}|{$rid}";
                if (isset($this->joinRows[$k]) && $this->joinRows[$k] === $fromDeleted) {
                    $this->joinRows[$k] = $fromDeleted === 1 ? 0 : 1;
                    $this->affected++;
                }
            }
        }

        private function insert(string $sql): void
        {
            if (!preg_match("/INSERT\\s+INTO\\s+(\\w+)\\s*\\(([^\\)]+)\\)\\s+VALUES\\s+(.+)$/", $sql, $m)) {
                return;
            }
            $table = $m[1];
            $cols = array_map('trim', explode(',', $m[2]));
            $values = $m[3];
            $rows = preg_split("/\\)\\s*,\\s*\\(/", trim($values, " \t\n\r\0\x0B()"));
            foreach ($rows as $row) {
                $vals = array_map(static function ($v): string {
                    return trim($v, " \t\n\r\0\x0B'");
                }, explode(',', $row));
                $map = [];
                foreach ($cols as $idx => $col) {
                    $map[$col] = $vals[$idx] ?? null;
                }
                if ($map['id'] === null || $map['deleted'] === null) {
                    continue;
                }
                $focusKey = null;
                $relatedKey = null;
                foreach ($cols as $c) {
                    if (str_ends_with($c, '_ida') || str_ends_with($c, '_idb')) {
                        if ($focusKey === null) {
                            $focusKey = $c;
                        } elseif ($relatedKey === null) {
                            $relatedKey = $c;
                        }
                    }
                }
                if ($focusKey === null || $relatedKey === null) {
                    $focusKey = $cols[1] ?? null;
                    $relatedKey = $cols[2] ?? null;
                }
                if ($focusKey === null || $relatedKey === null) {
                    continue;
                }
                $k = "{$table}|{$focusKey}|{$relatedKey}|{$map[$focusKey]}|{$map[$relatedKey]}";
                $this->joinRows[$k] = (int) $map['deleted'];
                $this->affected++;
            }
        }
    }

    final class FakeRelationship
    {
        public function __construct(
            private string $table,
            private string $lhsKey,
            private string $rhsKey
        ) {
        }

        public function getRelationshipTable(): string
        {
            return $this->table;
        }

        public function getJoinKeyLHS(): string
        {
            return $this->lhsKey;
        }

        public function getJoinKeyRHS(): string
        {
            return $this->rhsKey;
        }

        public function getRoleWhereClause(string $table = ''): string
        {
            return '';
        }

        public function getRelationshipRoleColumn(): ?string
        {
            return null;
        }

        public function getRelationshipRoleColumnValue(): ?string
        {
            return null;
        }

        public function isSelfReferencingRelationship(): bool
        {
            return false;
        }
    }

    class Link2
    {
        public function __construct(
            private object $focus,
            private FakeRelationship $relationship,
            private string $side = REL_LHS,
            private FakeDb $db = new FakeDb()
        ) {
        }

        public function getFocus(): object
        {
            return $this->focus;
        }

        public function getSide(): string
        {
            return $this->side;
        }

        public function getRelationshipObject(): FakeRelationship
        {
            return $this->relationship;
        }

        public function add(string $id, array $additionalFields = []): bool
        {
            $this->db->query("INSERT INTO {$this->relationship->getRelationshipTable()} (id,{$this->relationship->getJoinKeyLHS()},{$this->relationship->getJoinKeyRHS()},date_modified,deleted) VALUES ('x','{$this->focus->id}','{$id}','x',0)");
            return true;
        }

        public function delete(string $id, string $relatedId = ''): bool
        {
            $this->db->query("UPDATE {$this->relationship->getRelationshipTable()} SET deleted=1 WHERE {$this->relationship->getJoinKeyLHS()}='{$id}' AND {$this->relationship->getJoinKeyRHS()} IN ('{$relatedId}') AND deleted=0");
            return true;
        }
    }
}

namespace SuiteCRM\Tests\Unit\Pure\Utility {

use PHPUnit\Framework\TestCase;
use SuiteCRM\Utility\BulkOperations;

final class BulkOperationsTest extends TestCase
{
    public function test_bulk_add_remove_and_reactivate_join_rows(): void
    {
        $db = new \FakeDb();
        \DBManagerFactory::$db = $db;

        $validator = new class {
            public function isValidId(?string $id): bool
            {
                return !empty($id);
            }
        };

        $bulk = new BulkOperations($validator);

        $focus = (object) ['id' => 'focus-1'];
        $relationship = new \FakeRelationship('rel_table', 'rel_table_ida', 'rel_table_idb');
        $link = new \Link2($focus, $relationship, \REL_LHS, $db);

        $add = $bulk->bulkAddRelatedIds($link, ['r1', 'r2']);
        $this->assertSame(2, $add['inserted']);
        $this->assertSame(0, $add['reactivated']);

        $remove = $bulk->bulkRemoveRelatedIds($link, ['r1', 'r2']);
        $this->assertSame(2, $remove['removed']);

        $add2 = $bulk->bulkAddRelatedIds($link, ['r1', 'r2']);
        $this->assertSame(0, $add2['inserted']);
        $this->assertSame(2, $add2['reactivated']);
    }

    public function test_bulk_get_related_ids(): void
    {
        $db = new \FakeDb();
        \DBManagerFactory::$db = $db;

        // Seed some fake data in the mock DB
        $db->results = [
            [
                ['rel_table_ida' => 'focus-1', 'rel_table_idb' => 'r1'],
                ['rel_table_ida' => 'focus-1', 'rel_table_idb' => 'r2'],
                ['rel_table_ida' => 'focus-2', 'rel_table_idb' => 'r3'],
            ]
        ];

        $validator = new class {
            public function isValidId(?string $id): bool { return !empty($id); }
        };

        $bulk = new BulkOperations($validator);

        $focus1 = new class {
            public $id = 'focus-1';
            public $test_link;
            public function load_relationship(string $name): bool { return true; }
        };
        $focus2 = new class {
            public $id = 'focus-2';
            public $test_link;
            public function load_relationship(string $name): bool { return true; }
        };

        $relationship = new \FakeRelationship('rel_table', 'rel_table_ida', 'rel_table_idb');
        $focus1->test_link = new \Link2($focus1, $relationship, \REL_LHS, $db);
        $focus2->test_link = new \Link2($focus2, $relationship, \REL_LHS, $db);

        $res = $bulk->bulkGetRelatedIds([$focus1, $focus2], 'test_link');
        $this->assertCount(2, $res);
        $this->assertEquals(['r1', 'r2'], $res['focus-1'] ?? []);
        $this->assertEquals(['r3'], $res['focus-2'] ?? []);
    }

    public function test_bulk_add_uses_fewer_queries_than_preserve_behavior_mode(): void
    {
        $db = new \FakeDb();
        \DBManagerFactory::$db = $db;

        $validator = new class {
            public function isValidId(?string $id): bool
            {
                return !empty($id);
            }
        };

        $bulk = new BulkOperations($validator);
        $focus = (object) ['id' => 'focus-2'];
        $relationship = new \FakeRelationship('rel_table2', 'rel_table2_ida', 'rel_table2_idb');
        $link = new \Link2($focus, $relationship, \REL_LHS, $db);

        $ids = [];
        for ($i = 0; $i < 15; $i++) {
            $ids[] = 'r' . $i;
        }

        $db->queries = 0;
        $bulk->bulkAddRelatedIds($link, $ids, [], 500, true);
        $preserveQueries = $db->queries;

        $db->queries = 0;
        $bulk->bulkAddRelatedIds($link, $ids, [], 500, false);
        $bulkQueries = $db->queries;

        $this->assertTrue($bulkQueries < $preserveQueries);
    }
}
}
