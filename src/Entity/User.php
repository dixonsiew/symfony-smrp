<?php

namespace App\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class User
{
    #[Groups(['user:read'])]
    public ?int $id;

    #[Groups(['user:read'])]
    public ?string $username;

    #[Groups(['user:read'])]
    public ?string $first_name;

    #[Groups(['user:read'])]
    public ?string $last_name;

    private string $password;

    #[Groups(['user:read'])]
    public ?string $last_login;

    /** @var Role[] */
    #[Groups(['user:read'])]
    public array $roles = [];

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setRoles(Connection $connection): void
    {
        $q = 'select aur.app_user_id, aur.roles_id, r.id, r.name from app_user_roles aur inner join role r on aur.roles_id = r.id where aur.app_user_id = :id';
        $result = $connection->executeQuery($q, ['id' => $this->id]);
        $roles = $result->fetchAllAssociative();
        $ls = [];

        foreach ($roles as $data) {
            $role = new Role();
            $role->id = $data['id'];
            $role->name = $data['name'];
            $ls[] = $role;
        }
        $this->roles = $ls;
    }

    public static function fromRs($data, Connection $connection): self
    {
        $o = new self();
        $o->id = $data['id'];
        $o->username = $data['username'];
        $o->first_name = $data['first_name'];
        $o->last_name = $data['last_name'];
        $o->last_login = $data['last_login'];
        $o->setPassword($data['password']);
        $o->setRoles($connection);
        return $o;
    }

    // public function toArray(): array
    // {
    //     return [
    //         'id'         => $this->id,
    //         'username'   => $this->username,
    //         'first_name' => $this->first_name,
    //         'last_name'  => $this->last_name,
    //         'last_login' => $this->last_login,
    //     ];
    // }

    // public function toJson(): string
    // {
    //     return json_encode($this->toArray());
    // }
}