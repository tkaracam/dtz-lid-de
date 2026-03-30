<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'email' => 'admin@dtz-lernen.de',
                'password_hash' => password_hash('Admin123!', PASSWORD_ARGON2ID),
                'display_name' => 'Administrator',
                'level' => 'B1',
                'subscription_status' => 'premium',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => 'test@example.com',
                'password_hash' => password_hash('Test123!', PASSWORD_ARGON2ID),
                'display_name' => 'Test User',
                'level' => 'A2',
                'subscription_status' => 'trialing',
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $users = $this->table('users');
        $users->insert($data)->saveData();
    }
}
