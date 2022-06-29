<?php
namespace Pixxel\Auth\UserStorage;

/**
 * A database driver for finding and authenticating users, uses the pixxel/dbal library
 */
class Database implements \Pixxel\Auth\UserStorageInterface
{
    private $dbal;              // Pixxel DBAL instance
    private $usersTable;        // Name of the table the users are saved in
    private $usernameField;     // ..also the name of the username..
    private $conditions;        // Other conditions, such as for instance a field "active" should be 1, then pass it like this: ['active' => 1]
    private $hashAlgorithm;     // The password hash function, defaults to argon2i


    public function __construct($configuration)
    {
        if(empty($configuration['dbal']) || !$configuration['dbal'] instanceof \Pixxel\DBAL)
        {
            throw new \Exception('No valid dbal instance passed to the storage-configuration');
        }

        $this->dbal = $configuration['dbal'];
        $this->usersTable = !empty($configuration['usersTable']) ? $configuration['usersTable'] : 'users';
        $this->usernameField = !empty($configuration['usernameField']) ? $configuration['usernameField'] : 'username';
        $this->conditions = !empty($configuration['conditions']) && is_array($configuration['conditions']) ? $configuration['conditions'] : [];
        $this->hashAlgorithm = !empty($configuration['hashAlgorithm']) ? $configuration['hashAlgorithm'] : 'argon2i';
    }

    /**
     * Search a user by its username and return it if it exists, otherwise return false
     * @param string $username
     * @return bool|object
     */
    public function getByUsername($username): bool|object
    {
        $params = [];
        $query = "select * from `".$this->usersTable."` where `".$this->usernameField."` = :username";
        $params[':username'] = $username;

        if(!empty($this->conditions))
        {
            foreach($this->conditions as $field => $value)
            {
                $query .= " and `".$field."` = :".$field;   // Watch out that the user cannot control the field names
                $params[':'.$field] = $value;
            }
        }
        
        $result = $this->dbal->readSingle($query, $params);

        if(!$result)
        {
            return false;
        }

        $user = new \Pixxel\Auth\User((array)$result, $this->usernameField);

        return $user;
    }

    /**
     * Verify a user, search by its username and / or other conditions and use the predefined hash-function to check the password
     * @param string $username
     * @param string $password
     * @return bool|object
     */
    public function verifyUser(string $username, string $password): bool|object
    {
        $user = $this->getByUsername($username);

        if(!$user)
        {
            return false;
        }

        if($this->verifyPassword($password, $user->get('password')))
        {
            return $user;
        }

        return false;
    }

    /**
     * Hash a password with the specified algorithm
     * @param string $password
     * @return string
     */
    public function hashPassword($password): string
    {
        switch($this->hashAlgorithm)
        {
            case 'argon2i':
                $algorithm = PASSWORD_ARGON2I;
            break;

            case 'argon2id':
                $algorithm = PASSWORD_ARGON2ID;
            break;

            case 'bcrypt':
                $algorithm = PASSWORD_BCRYPT;
            break;
        }

        return password_hash($password, $algorithm);
    }

    /**
     * Verify an existing password
     * @param string $password              The password the user has inserted
     * @param string $hash      The password stored in the database, needed for the salt
     * @return bool
     */
    public function verifyPassword($password, $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Try to register a new user
     * @param string $username
     * @param string $password
     * @param array $data       Further data to save in the user table
     * @return bool
     * @throws Exception
     */
    public function register(string $username, string $password, array $data)
    {
        // Check if the user exists already
        $query = "select * from `".$this->usersTable."` where `".$this->usernameField."` = :username";
        $exists = $this->dbal->readSingle($query, [':username' => $username]);

        if(!empty($exists))
        {
            throw new \Exception('User with this username exists already.');
        }

        // Otherwise try to insert it
        $query = "insert into `".$this->usersTable."` (`".$this->usernameField."`, `password`";

        foreach($data as $field => $value)
        {
            $query .= ", `".$field."`";
        }

        $query .= ") values(:username, :password";

        foreach($data as $field => $value)
        {
            $query .= ", :".$field;
        }

        $query .= ")";
        $password = $this->hashPassword($password);
        $params = [':username' => $username, ':password' => $password];

        foreach($data as $field => $value)
        {
            $params[':'.$field] = $value;
        }

        return $this->dbal->save($query, $params);
    }
}