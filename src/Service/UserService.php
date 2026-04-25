<?php

namespace App\Service;

use function strlen;

use Doctrine\DBAL\Connection;
use App\Entity\User;

class UserService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findById(int $id): ?User
    {
        $sql = 'SELECT * FROM app_user WHERE id = :id limit 1';
        $result = $this->connection->executeQuery($sql, ['id' => $id]);
        $data = $result->fetchAssociative();

        if (!$data) {
            return null;
        }

        $user = new User();
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->last_login = $data['last_login'];

        $user->setRoles($this->connection);
        return $user;
    }

    public function findByUsername(string $username): ?User
    {
        $sql = 'SELECT * FROM app_user WHERE username = :username LIMIT 1';
        $result = $this->connection->executeQuery($sql, ['username' => $username]);
        $data = $result->fetchAssociative();

        if (!$data) {
            return null;
        }

        $user = new User();
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->last_login = $data['last_login'];

        $user->setRoles($this->connection);
        return $user;
    }

    public function findAll(int $offset, int $limit, string $sortBy, string $sortDir): array
    {
        $sql = "SELECT t.id, t.username, t.first_name, t.last_name, t.password, t.last_login FROM app_user t ORDER BY $sortBy $sortDir OFFSET :offset LIMIT :limit";
        $result = $this->connection->executeQuery($sql, ['offset' => $offset, 'limit' => $limit]);
        $data = $result->fetchAllAssociative();
        $users = [];

        foreach ($data as $row) {
            $user = new User();
            $user->id = $row['id'];
            $user->username = $row['username'];
            $user->first_name = $row['first_name'];
            $user->last_name = $row['last_name'];
            $user->last_login = $row['last_login'];
            $user->setRoles($this->connection);
            $users[] = $user;
        }

        return $users;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(id) FROM app_user';
        $result = $this->connection->executeQuery($sql);
        return (int) $result->fetchOne();
    }

    public function existsByOtherUsername(string $username, int $id): bool
    {
        $sql = 'SELECT EXISTS (SELECT 1 FROM app_user t WHERE t.username = :username AND t.id <> :id)';
        $result = $this->connection->executeQuery($sql, ['username' => $username, 'id' => $id]);
        return (bool) $result->fetchOne();
    }

    public function existsByUsername(string $username): bool
    {
        $sql = 'SELECT EXISTS (SELECT 1 FROM app_user t WHERE t.username = :username)';
        $result = $this->connection->executeQuery($sql, ['username' => $username]);
        return (bool) $result->fetchOne();
    }

    public function findByKeyword(string $keyword, int $offset, int $limit, string $sortBy, string $sortDir): array
    {
        $sql = "SELECT t.id, t.username, t.first_name, t.last_name, t.password, t.last_login 
            FROM app_user t WHERE (t.username ILIKE :keyword OR t.first_name ILIKE :keyword OR t.last_name ILIKE :keyword) 
            ORDER BY $sortBy $sortDir OFFSET :offset LIMIT :limit";
        $result = $this->connection->executeQuery($sql, [
            'keyword' => $keyword,
            'offset' => $offset,
            'limit' => $limit
        ]);
        $data = $result->fetchAllAssociative();
        $users = [];

        foreach ($data as $row) {
            $user = new User();
            $user->id = $row['id'];
            $user->username = $row['username'];
            $user->first_name = $row['first_name'];
            $user->last_name = $row['last_name'];
            $user->last_login = $row['last_login'];
            $user->setRoles($this->connection);
            $users[] = $user;
        }

        return $users;
    }

    public function countByKeyword(string $keyword): int
    {
        $sql = 'SELECT COUNT(id) FROM app_user t WHERE (t.username ILIKE :keyword OR t.first_name ILIKE :keyword OR t.last_name ILIKE :keyword)';
        $result = $this->connection->executeQuery($sql, ['keyword' => $keyword]);
        return (int) $result->fetchOne();
    }

    public function save(User $o): void
    {
        $pw = password_hash($o->getPassword(), PASSWORD_BCRYPT);
        $this->connection->beginTransaction();
        try {
            $sql = "INSERT INTO app_user (id, username, password, first_name, last_name, active) 
                VALUES(nextval('app_user_id_seq'),:username,:password,:first_name,:last_name,:active) 
                returning id AS app_user_id";
            $result = $this->connection->executeQuery($sql, [
                'username' => $o->username,
                'password' => $pw,
                'first_name' => $o->first_name,
                'last_name' => $o->last_name,
                'active' => true
            ]);
            $id = $result->fetchOne();

            foreach ($o->roles as $role) {
                $q = 'INSERT INTO app_user_roles (app_user_id, roles_id) VALUES(:app_user_id, :roles_id)';
                $this->connection->executeQuery($q, [
                    'app_user_id' => $id,
                    'roles_id' => $role->id
                ]);
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function update(User $o): void
    {
        $this->connection->beginTransaction();
        try {
            if (strlen($o->getPassword()) > 0) {
                $pw = password_hash($o->getPassword(), PASSWORD_BCRYPT);
                $sql = 'UPDATE app_user SET PASSWORD = :password, first_name = :first_name, last_name = :last_name WHERE id = :id';
                $this->connection->executeQuery($sql, [
                    'password' => $pw,
                    'first_name' => $o->first_name,
                    'last_name' => $o->last_name,
                    'id' => $o->id
                ]);
            } else {
                $sql = 'UPDATE app_user SET first_name = :first_name, last_name = :last_name WHERE id = :id';
                $this->connection->executeQuery($sql, [
                    'first_name' => $o->first_name,
                    'last_name' => $o->last_name,
                    'id' => $o->id
                ]);
            }

            $q = 'DELETE FROM app_user_roles WHERE app_user_id = :app_user_id';
            $this->connection->executeQuery($q, ['app_user_id' => $o->id]);

            foreach ($o->roles as $role) {
                $q = 'INSERT INTO app_user_roles (app_user_id, roles_id) VALUES(:app_user_id, :roles_id)';
                $this->connection->executeQuery($q, [
                    'app_user_id' => $o->id,
                    'roles_id' => $role->id
                ]);
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function deleteById(int $id): void
    {
        $this->connection->beginTransaction();
        try {
            $q = 'DELETE FROM app_user_roles WHERE app_user_id = :app_user_id';
            $this->connection->executeQuery($q, ['app_user_id' => $id]);

            $q = 'DELETE FROM app_user WHERE id = :id';
            $this->connection->executeQuery($q, ['id' => $id]);

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function updateLastLogin(int $id): void
    {
        $sql = 'UPDATE app_user SET last_login = now() WHERE id = :id';
        $this->connection->executeQuery($sql, ['id' => $id]);
    }

    public function updatePassword(User $o): void
    {
        $pw = password_hash($o->getPassword(), PASSWORD_BCRYPT);
        $sql = 'UPDATE app_user SET password = :password WHERE id = :id';
        $this->connection->executeQuery($sql, [
            'password' => $pw,
            'id' => $o->id
        ]);
    }

    public function validateCredentials(User $user, string $password): bool
    {
        return password_verify($password, $user->getPassword());
    }
}