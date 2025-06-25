<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        $users = [
            [
                'name'     => 'Administrator',
                'email'    => 'admin@example.com',
                'password' => 'password',
                'role'     => 'admin',
            ],
            [
                'name'     => 'John Doe',
                'email'    => 'john.doe@example.com',
                'password' => 'password',
                'role'     => 'user',
            ],
        ];

        foreach ($users as $userData) {
            $userEntity = new \App\Entities\User($userData);
            $userModel->save($userEntity);
        }
    }
}
