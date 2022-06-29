<?php
namespace Pixxel\Auth;

/**
 * Every user-storage class has to implement this interface to standartize access to login and so on methods
 */
interface UserStorageInterface
{
    public function __construct($configuration);

    public function getByUsername($username): bool|object;
    public function verifyUser(string $username, string $password): bool|object;    // Use this method to check a users login data
    public function hashPassword($password): string;
    public function verifyPassword($password, $hash): bool;
    public function register(string $username, string $password, array $data);
}