<?php
namespace Domain\Entity;

class User {
    private string $username;
    private string $passwordHash;
    private bool $isActive;
    private bool $isAdmin;
    
    private ?string $email;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $ip;
    private ?string $lastAction;
    private ?string $lastActionDate;

    public function __construct(
        string $username,
        string $passwordHash,
        bool $isActive = true,
        bool $isAdmin = false,
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $ip = null,
        ?string $lastAction = null,
        ?string $lastActionDate = null
    ) {
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->isActive = $isActive;
        $this->isAdmin = $isAdmin;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->ip = $ip;
        $this->lastAction = $lastAction;
        $this->lastActionDate = $lastActionDate;
    }

    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function isActive(): bool { return $this->isActive; }
    public function isAdmin(): bool { return $this->isAdmin; }
    
    public function getEmail(): ?string { return $this->email; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getIp(): ?string { return $this->ip; }
    public function getLastAction(): ?string { return $this->lastAction; }
    public function getLastActionDate(): ?string { return $this->lastActionDate; }

    public function setActive(bool $active): void { $this->isActive = $active; }
    public function setAdmin(bool $admin): void { $this->isAdmin = $admin; }
    public function recordAction(string $action, string $date, ?string $ip = null): void {
        $this->lastAction = $action;
        $this->lastActionDate = $date;
        if ($ip !== null) $this->ip = $ip;
    }
}
