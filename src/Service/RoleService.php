<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use App\Entity\Role;

class RoleService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(string $sortBy, string $sortDir): array
    {
        $sql = "SELECT id, name FROM role ORDER BY $sortBy $sortDir";
        $result = $this->connection->executeQuery($sql);
        $data = $result->fetchAllAssociative();
        $roles = [];

        foreach ($data as $row) {
            $role = new Role();
            $role->id = $row['id'];
            $role->name = $row['name'];
            $roles[] = $role;
        }

        return $roles;
    }

    public function findById(int $id): ?Role
    {
        $sql = 'SELECT id, name FROM role WHERE id = :id LIMIT 1';
        $result = $this->connection->executeQuery($sql, ['id' => $id]);
        $data = $result->fetchAssociative();

        if (!$data) {
            return null;
        }

        $role = new Role();
        $role->id = $data['id'];
        $role->name = $data['name'];

        return $role;
    }
}