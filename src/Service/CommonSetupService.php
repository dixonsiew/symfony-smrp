<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use App\Entity\CommonSetup;

class CommonSetupService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findById(int $id, string $table): ?CommonSetup
    {
        $sql = "SELECT id, code, \"desc\", ref, created_by, created_date, modified_by, modified_date, deleted, deleted_by, deleted_date 
            FROM $table where id = :id limit 1";
        $result = $this->connection->executeQuery($sql, ['id' => $id]);
        $data = $result->fetchAssociative();

        if (!$data) {
            return null;
        }

        return CommonSetup::fromRs($data);
    }

    public function findByDesc(string $desc, string $table): ?CommonSetup
    {
        $sql = "SELECT t.id, t.code, t.desc, t.ref, t.created_by, t.created_date, t.modified_by, t.modified_date, t.deleted, t.deleted_by, t.deleted_date 
            FROM $table t WHERE lower(t.desc) = lower(:desc) LIMIT 1";
        $result = $this->connection->executeQuery($sql, ['desc' => $desc]);
        $data = $result->fetchAssociative();

        if (!$data) {
            return null;
        }

        return CommonSetup::fromRs($data);
    }

    public function findAll(string $table, int $offset, int $limit, string $sortBy, string $sortDir): array
    {
        $sql = '';
        $prm = [];
        if ($limit > 0) {
            $sql = "SELECT t.id, t.code, t.desc, t.ref, t.created_by, t.created_date, t.modified_by, t.modified_date, t.deleted, t.deleted_by, t.deleted_date 
                FROM $table t WHERE t.deleted is not true ORDER BY $sortBy $sortDir OFFSET :offset LIMIT :limit";
            $prm = ['offset' => $offset, 'limit' => $limit];
        } else {
            $sql = "SELECT t.id, t.code, t.desc, t.ref, t.created_by, t.created_date, t.modified_by, t.modified_date, t.deleted, t.deleted_by, t.deleted_date 
                FROM $table t WHERE t.desc <> '' AND t.deleted is not true ORDER BY t.code";
        }

        $result = $this->connection->executeQuery($sql, $prm);
        $data = $result->fetchAllAssociative();
        $lx = [];

        foreach ($data as $row) {
            $lx[] = CommonSetup::fromRs($row);
        }

        return $lx;
    }

    public function count(string $table): int
    {
        $sql = "SELECT COUNT(id) FROM $table t WHERE t.deleted is not true";
        $result = $this->connection->executeQuery($sql);
        return (int) $result->fetchOne();
    }

    public function findByKeyword(string $keyword, int $offset, int $limit, string $sortBy, string $sortDir, string $table): array
    {
        $sql = "SELECT t.id, t.code, t.desc, t.ref, t.created_by, t.created_date, t.modified_by, t.modified_date, t.deleted, t.deleted_by, t.deleted_date 
            FROM $table t WHERE (t.code ILIKE :keyword OR t.desc ilike :keyword OR t.ref ILIKE :keyword) AND t.deleted is not true ORDER BY 
            \"$sortBy\" $sortDir offset :offset limit :limit";
        $result = $this->connection->executeQuery($sql, [
            'keyword' => $keyword,
            'offset' => $offset,
            'limit' => $limit
        ]);
        $data = $result->fetchAllAssociative();
        $lx = [];

        foreach ($data as $row) {
            $lx[] = CommonSetup::fromRs($row);
        }

        return $lx;
    }

    public function countByKeyword(string $keyword, string $table): int
    {
        $sql = 'SELECT COUNT(id) FROM $table t WHERE (t.code ILIKE :keyword or t.desc ILIKE :keyword OR t.ref ILIKE :keyword) AND t.deleted is not true';
        $result = $this->connection->executeQuery($sql, ['keyword' => $keyword]);
        return (int) $result->fetchOne();
    }

    public function save(CommonSetup $o, string $table): void
    {
        $sql = "INSERT INTO $table (id, code, \"desc\", ref, created_by, created_date) VALUES(nextval('{$table}_id_seq'),:code,:desc,:ref,:created_by,now())";
        $this->connection->executeQuery($sql, [
            'code' => $o->code,
            'desc' => $o->desc,
            'ref' => $o->ref,
            'created_by' => $o->created_by
        ]);
    }

    public function update(CommonSetup $o, string $table): void
    {
        $sql = "UPDATE $table SET code = :code, \"desc\" = :desc, ref = :ref, modified_by = :modified_by, modified_date = now() WHERE id = :id";
        $this->connection->executeQuery($sql, [
            'code' => $o->code,
            'desc' => $o->desc,
            'ref' => $o->ref,
            'modified_by' => $o->modified_by,
            'id' => $o->id
        ]);
    }

    public function deleteById(int $id, int $user_id, string $table): void
    {
        $sql = "UPDATE $table SET deleted = true, deleted_by = :user_id, deleted_date = now() WHERE id = :id";
        $this->connection->executeQuery($sql, [
            'user_id' => $user_id,
            'id' => $id
        ]);
    }
}