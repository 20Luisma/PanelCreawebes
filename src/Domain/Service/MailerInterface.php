<?php
namespace Domain\Service;

interface MailerInterface {
    public function sendCredentials(string $username, string $password, string $email, string $firstName, string $lastName): void;
}
