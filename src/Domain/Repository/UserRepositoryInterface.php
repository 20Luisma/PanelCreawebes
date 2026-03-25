<?php
namespace Domain\Repository;

use Domain\Entity\User;

interface UserRepositoryInterface {
    /**
     * @return User[]
     */
    public function findAll(): array;
    
    public function findByUsername(string $username): ?User;
    
    public function save(User $user): void;
    
    public function delete(string $username): void;
}
