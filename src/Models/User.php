<?php
declare(strict_types=1);

namespace DTZ\Models;

class User {
    public string $id;
    public string $email;
    public ?string $name;
    public string $level;
    public string $role;
    public bool $isActive;
    
    public function __construct(array $data) {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->name = $data['name'] ?? null;
        $this->level = $data['level'] ?? 'A2';
        $this->role = $data['role'] ?? 'user';
        $this->isActive = $data['is_active'] ?? true;
    }
    
    public function isAdmin(): bool {
        return $this->role === 'admin';
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'level' => $this->level,
            'role' => $this->role
        ];
    }
}
