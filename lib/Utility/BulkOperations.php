<?php

namespace SuiteCRM\Utility;

use BeanFactory;
use DBManagerFactory;
use Link2;
use RuntimeException;

#[\AllowDynamicProperties]
class BulkOperations
{
    protected $validator;

    public function __construct(?object $validator = null)
    {
        $this->validator = $validator ?: new SuiteValidator();
    }

    public function filterValidIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $id = is_string($id) ? trim($id) : (is_numeric($id) ? (string) $id : '');
            if ($this->validator->isValidId($id)) {
                $out[$id] = true;
            }
        }
        return array_keys($out);
    }

    public function chunkIds(array $ids, int $chunkSize = 500): array
    {
        $chunkSize = max(1, $chunkSize);
        return array_chunk($ids, $chunkSize);
    }

    public function bulkSelectByIds(string $module, array $ids, array $fields = ['id'], int $chunkSize = 500, bool $includeDeleted = false): array
    {
        $ids = $this->filterValidIds($ids);
        if (empty($ids)) {
            return [];
        }

        $seed = BeanFactory::newBean($module);
        if (!$seed) {
            throw new RuntimeException("Unknown module: {$module}");
        }

        $table = $seed->table_name;
        $fields = array_values(array_unique(array_filter($fields, static function ($f): bool {
            return is_string($f) && $f !== '';
        })));
        if (empty($fields)) {
            $fields = ['id'];
        }

        $db = DBManagerFactory::getInstance();
        $hasDeleted = isset($seed->field_defs['deleted']);

        $rows = [];
        foreach ($this->chunkIds($ids, $chunkSize) as $chunk) {
            $in = implode(',', array_map([$db, 'quoted'], $chunk));
            $select = implode(',', array_map(static function (string $f) use ($table): string {
                return "{$table}.{$f}";
            }, $fields));
            $where = "{$table}.id IN ({$in})";
            if ($hasDeleted && !$includeDeleted) {
                $where .= " AND {$table}.deleted = 0";
            }
            $sql = "SELECT {$select} FROM {$table} WHERE {$where}";

            $res = $db->query($sql);
            while ($row = $db->fetchByAssoc($res, false)) {
                if (!empty($row['id'])) {
                    $rows[$row['id']] = $row;
                }
            }
        }

        return $rows;
    }

    public function bulkUpdateByIds(string $module, array $ids, array $fieldValues, int $chunkSize = 500, bool $includeDeleted = false, bool $touchDateModified = true): int
    {
        $ids = $this->filterValidIds($ids);
        if (empty($ids)) {
            return 0;
        }

        $seed = BeanFactory::newBean($module);
        if (!$seed) {
            throw new RuntimeException("Unknown module: {$module}");
        }

        $db = DBManagerFactory::getInstance();
        $table = $seed->table_name;
        $hasDeleted = isset($seed->field_defs['deleted']);
        $hasDateModified = isset($seed->field_defs['date_modified']);

        $sets = [];
        foreach ($fieldValues as $field => $value) {
            if (!is_string($field) || $field === '') {
                continue;
            }
            if ($value === null) {
                $sets[] = "{$field}=NULL";
            } elseif (is_bool($value)) {
                $sets[] = "{$field}=" . ($value ? '1' : '0');
            } elseif (is_numeric($value)) {
                $sets[] = "{$field}=" . $db->quote((string) $value);
            } else {
                $sets[] = "{$field}=" . $db->quoted((string) $value);
            }
        }

        if ($touchDateModified && $hasDateModified) {
            $sets[] = "date_modified=" . $db->now();
        }

        if (empty($sets)) {
            return 0;
        }

        $affected = 0;
        $setSql = implode(',', $sets);
        foreach ($this->chunkIds($ids, $chunkSize) as $chunk) {
            $in = implode(',', array_map([$db, 'quoted'], $chunk));
            $where = "id IN ({$in})";
            if ($hasDeleted && !$includeDeleted) {
                $where .= " AND deleted = 0";
            }
            $sql = "UPDATE {$table} SET {$setSql} WHERE {$where}";
            $res = $db->query($sql);
            $affected += (int) $db->getAffectedRowCount($res);
        }

        return $affected;
    }

    public function bulkAddRelatedIds(Link2 $link, array $relatedIds, array $additionalFields = [], int $chunkSize = 500, bool $preserveBehavior = false): array
    {
        $focus = $link->getFocus();
        if (empty($focus->id)) {
            throw new RuntimeException('Link focus id is required');
        }

        $relatedIds = $this->filterValidIds($relatedIds);
        if (empty($relatedIds)) {
            return ['inserted' => 0, 'reactivated' => 0, 'skipped' => 0];
        }

        if ($preserveBehavior) {
            $ok = 0;
            foreach ($relatedIds as $id) {
                if ($link->add($id, $additionalFields)) {
                    $ok++;
                }
            }
            return ['inserted' => 0, 'reactivated' => 0, 'skipped' => count($relatedIds) - $ok];
        }

        $relationship = $link->getRelationshipObject();
        $table = $relationship->getRelationshipTable();
        $lhsKey = $relationship->getJoinKeyLHS();
        $rhsKey = $relationship->getJoinKeyRHS();
        if (empty($table) || empty($lhsKey) || empty($rhsKey)) {
            throw new RuntimeException('Relationship does not support join-table bulk operations');
        }

        $db = DBManagerFactory::getInstance();
        $roleWhere = $relationship->getRoleWhereClause($table);
        $roleCol = $relationship->getRelationshipRoleColumn();
        $roleVal = $relationship->getRelationshipRoleColumnValue();

        $focusKey = $link->getSide() === REL_LHS ? $lhsKey : $rhsKey;
        $relatedKey = $link->getSide() === REL_LHS ? $rhsKey : $lhsKey;

        $inserted = 0;
        $reactivated = 0;

        $additionalFields = $this->normalizeAdditionalFields($additionalFields);
        $nowDb = \TimeDate::getInstance()->nowDb();

        foreach ($this->chunkIds($relatedIds, $chunkSize) as $chunk) {
            $in = implode(',', array_map([$db, 'quoted'], $chunk));
            $focusId = $db->quote($focus->id);

            $existing = [];
            $sql = "SELECT {$relatedKey} AS rid, deleted FROM {$table} WHERE {$focusKey}='{$focusId}' AND {$relatedKey} IN ({$in}){$roleWhere}";
            $res = $db->query($sql);
            while ($row = $db->fetchByAssoc($res, false)) {
                if (!empty($row['rid'])) {
                    $existing[$row['rid']] = (int) ($row['deleted'] ?? 0);
                }
            }

            $toInsert = [];
            $toReactivate = [];
            foreach ($chunk as $rid) {
                if (!isset($existing[$rid])) {
                    $toInsert[] = $rid;
                    continue;
                }
                if ($existing[$rid] === 1) {
                    $toReactivate[] = $rid;
                }
            }

            if (!empty($toReactivate)) {
                $inReactivate = implode(',', array_map([$db, 'quoted'], $toReactivate));
                $sets = ["deleted=0", "date_modified=" . $db->quoted($nowDb)];
                foreach ($additionalFields as $k => $v) {
                    $sets[] = "{$k}={$v}";
                }
                $setSql = implode(',', $sets);
                $upd = "UPDATE {$table} SET {$setSql} WHERE {$focusKey}='{$focusId}' AND {$relatedKey} IN ({$inReactivate}) AND deleted=1{$roleWhere}";
                $r = $db->query($upd);
                $reactivated += (int) $db->getAffectedRowCount($r);
            }

            if (!empty($toInsert)) {
                $columns = ['id', $focusKey, $relatedKey, 'date_modified', 'deleted'];
                $valuesTemplate = [];
                if (!empty($roleCol) && !empty($roleVal)) {
                    $columns[] = $roleCol;
                }
                foreach (array_keys($additionalFields) as $k) {
                    if (!in_array($k, $columns, true)) {
                        $columns[] = $k;
                    }
                }

                $colSql = implode(',', $columns);
                foreach ($toInsert as $rid) {
                    $row = [];
                    $row[] = $db->quoted(create_guid());
                    $row[] = $db->quoted($focus->id);
                    $row[] = $db->quoted($rid);
                    $row[] = $db->quoted($nowDb);
                    $row[] = '0';
                    if (!empty($roleCol) && !empty($roleVal)) {
                        $row[] = $db->quoted($roleVal);
                    }
                    foreach (array_keys($additionalFields) as $k) {
                        $row[] = $additionalFields[$k];
                    }
                    $valuesTemplate[] = '(' . implode(',', $row) . ')';

                    if ($relationship->isSelfReferencingRelationship() && $rid !== $focus->id) {
                        $row2 = [];
                        $row2[] = $db->quoted(create_guid());
                        $row2[] = $db->quoted($rid);
                        $row2[] = $db->quoted($focus->id);
                        $row2[] = $db->quoted($nowDb);
                        $row2[] = '0';
                        if (!empty($roleCol) && !empty($roleVal)) {
                            $row2[] = $db->quoted($roleVal);
                        }
                        foreach (array_keys($additionalFields) as $k) {
                            $row2[] = $additionalFields[$k];
                        }
                        $valuesTemplate[] = '(' . implode(',', $row2) . ')';
                    }
                }

                if (!empty($valuesTemplate)) {
                    $ins = "INSERT INTO {$table} ({$colSql}) VALUES " . implode(',', $valuesTemplate);
                    $db->query($ins);
                    $inserted += count($toInsert);
                }
            }
        }

        return ['inserted' => $inserted, 'reactivated' => $reactivated, 'skipped' => 0];
    }

    public function bulkRemoveRelatedIds(Link2 $link, array $relatedIds, int $chunkSize = 500, bool $preserveBehavior = false): array
    {
        $focus = $link->getFocus();
        if (empty($focus->id)) {
            throw new RuntimeException('Link focus id is required');
        }

        $relatedIds = $this->filterValidIds($relatedIds);
        if (empty($relatedIds)) {
            return ['removed' => 0, 'skipped' => 0];
        }

        if ($preserveBehavior) {
            $ok = 0;
            foreach ($relatedIds as $id) {
                if ($link->delete($focus->id, $id)) {
                    $ok++;
                }
            }
            return ['removed' => $ok, 'skipped' => count($relatedIds) - $ok];
        }

        $relationship = $link->getRelationshipObject();
        $table = $relationship->getRelationshipTable();
        $lhsKey = $relationship->getJoinKeyLHS();
        $rhsKey = $relationship->getJoinKeyRHS();
        if (empty($table) || empty($lhsKey) || empty($rhsKey)) {
            throw new RuntimeException('Relationship does not support join-table bulk operations');
        }

        $db = DBManagerFactory::getInstance();
        $roleWhere = $relationship->getRoleWhereClause($table);
        $focusKey = $link->getSide() === REL_LHS ? $lhsKey : $rhsKey;
        $relatedKey = $link->getSide() === REL_LHS ? $rhsKey : $lhsKey;
        $nowDb = \TimeDate::getInstance()->nowDb();

        $removed = 0;
        foreach ($this->chunkIds($relatedIds, $chunkSize) as $chunk) {
            $in = implode(',', array_map([$db, 'quoted'], $chunk));
            $focusId = $db->quote($focus->id);
            $sql = "UPDATE {$table} SET deleted=1, date_modified=" . $db->quoted($nowDb) . " WHERE {$focusKey}='{$focusId}' AND {$relatedKey} IN ({$in}) AND deleted=0{$roleWhere}";
            $res = $db->query($sql);
            $removed += (int) $db->getAffectedRowCount($res);
        }

        return ['removed' => $removed, 'skipped' => 0];
    }

    /**
     * Bulk fetch related IDs for multiple focus beans.
     *
     * @param \SugarBean[] $focusBeans An array of initialized beans (of the same type)
     * @param string $linkName The relationship link name to fetch
     * @param int $chunkSize
     * @return array Map of focus bean ID => array of related IDs
     */
    public function bulkGetRelatedIds(array $focusBeans, string $linkName, int $chunkSize = 500): array
    {
        $result = [];
        $validBeans = [];
        
        foreach ($focusBeans as $bean) {
            if (!empty($bean->id)) {
                $validBeans[$bean->id] = $bean;
                $result[$bean->id] = [];
            }
        }
        
        if (empty($validBeans)) {
            return $result;
        }
        
        // Grab relationship definition from the first valid bean
        $firstBean = reset($validBeans);
        if (!$firstBean->load_relationship($linkName)) {
            return $result;
        }
        
        /** @var \Link2 $link */
        $link = $firstBean->$linkName;
        $relationship = $link->getRelationshipObject();
        $table = $relationship->getRelationshipTable();
        $lhsKey = $relationship->getJoinKeyLHS();
        $rhsKey = $relationship->getJoinKeyRHS();
        
        if (empty($table) || empty($lhsKey) || empty($rhsKey)) {
            // Fallback for non-join-table relationships (e.g. 1-to-M using parent_id)
            foreach ($validBeans as $id => $bean) {
                if ($bean->load_relationship($linkName)) {
                    $result[$id] = $bean->$linkName->get();
                }
            }
            return $result;
        }
        
        $db = DBManagerFactory::getInstance();
        $roleWhere = $relationship->getRoleWhereClause($table);
        $focusKey = $link->getSide() === REL_LHS ? $lhsKey : $rhsKey;
        $relatedKey = $link->getSide() === REL_LHS ? $rhsKey : $lhsKey;
        
        $focusIds = array_keys($validBeans);
        foreach ($this->chunkIds($focusIds, $chunkSize) as $chunk) {
            $in = implode(',', array_map([$db, 'quoted'], $chunk));
            $sql = "SELECT {$focusKey}, {$relatedKey} FROM {$table} WHERE {$focusKey} IN ({$in}) AND deleted=0{$roleWhere}";
            $res = $db->query($sql);
            while ($row = $db->fetchByAssoc($res, false)) {
                $result[$row[$focusKey]][] = $row[$relatedKey];
            }
        }
        
        // Ensure every valid focus bean has at least an empty array
        foreach ($validBeans as $id => $bean) {
            if (!isset($result[$id])) {
                $result[$id] = [];
            }
        }
        
        return $result;
    }

    protected function normalizeAdditionalFields(array $fields): array
    {
        $db = DBManagerFactory::getInstance();
        $out = [];
        foreach ($fields as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }
            if ($v === null) {
                $out[$k] = 'NULL';
            } elseif (is_bool($v)) {
                $out[$k] = $v ? '1' : '0';
            } elseif (is_numeric($v)) {
                $out[$k] = $db->quote((string) $v);
            } else {
                $out[$k] = $db->quoted((string) $v);
            }
        }
        return $out;
    }
}
