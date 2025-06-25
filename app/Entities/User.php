<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'   => 'integer',
        'role' => 'string'
    ];

    protected function setPassword(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    protected function verifyPassword(string $password): bool
    {
        if (empty($this->attributes['password'])) {
            return false;
        }
        return password_verify($password, $this->attributes['password']);
    }
}
