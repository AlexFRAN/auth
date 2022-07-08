<?php

namespace Pixxel;

/**
 * A simplistic authentication library
 * Note that this is only a quick test, so do not use in production yet!
 * @TODO: Security verification & refactoring, use Dependency injection and so on..
 */
class Auth
{
    private $storage;       // In here the object with which you can search for users and details are stored, it uses the driver pattern, so the methods to access the data is the same on each
    private $persistence = [];   // Object that handles the user persistence, either through a session or some kind of token
    private $user;          // In here the current user-details are stored


    /**
     * At the moment i was too lazy to implement a DI-library, so the userstorage and user object have to be passed into the constructor -.-
     * @param object $storage
     */
    public function __construct(object $storage, bool|array|object $persistence = false)
    {
        $this->storage = $storage;

        if(is_object($persistence))
        {
            if($persistence instanceof \Pixxel\Auth\PersistenceInterface)
            {
                $this->persistence[$this->getClassName($persistence)] = $persistence;
            }
        }
        elseif(is_array($persistence))
        {
            foreach($persistence as $persistenceObject)
            {
                if(is_object($persistenceObject) && $persistenceObject instanceof \Pixxel\Auth\PersistenceInterface)
                {
                    $this->persistence[$this->getClassName($persistenceObject)] = $persistenceObject;
                }
            }
        }
    }
    
    /**
     * Gets the classname only from an object (without the namespace)
     * TODO: Maybe move this method to another, more generic class, since it's not tied to Authentication
     * @param object $object
     * @return string|bool
     */
    public function getClassName(object $object): string|bool
    {
        $class = get_class($object);
        $pos = strrpos($class, '\\');

        if($pos)
        {
            return substr($class, $pos + 1);
        }

        return false;
    }

    /**
     * Add a new persistence object
     * @param object $persistenceObject     Has to be a class that implements the PersistenceInterface
     * @return bool
     */
    public function addPersistence(object $persistenceObject): bool
    {
        if ($persistenceObject instanceof \Pixxel\Auth\PersistenceInterface) {
            $this->persistence[$this->getClassName($persistenceObject)] = $persistenceObject;

            return true;
        }

        return false;
    }

    /**
     * Get a single persistence object
     * @param string $classname
     * @return object|bool
     */
    public function getPersistence(string $classname): object|bool
    {
        return isset($this->persistence[$classname]) ? $this->persistence[$classname] : false;
    }

    /**
     * Try to login a user and return true or false
     * @param string $username
     * @param string $password
     * @param array $conditions     Other conditions, such as active => 1
     * @return bool
     */
    public function login($username, $password, $conditions = []): bool
    {
        if ($this->isLoggedIn()) {
            $this->logout();
        }

        $user = $this->storage->verifyUser($username, $password, $conditions);

        if (!$user) {
            return false;
        }

        $data = $user->get();
        unset($data['password']);   // Do not save the hashed password in the session

        foreach ($this->persistence as $persistenceObject) {
            $persistenceObject->login($data);
        }

        return true;
    }

    /**
     * Checked wheter a user is logged in or not
     * @return bool
     */
    public function isLoggedIn()
    {
        $loggedIn = false;

        foreach ($this->persistence as $persistenceObject) {
            if ($persistenceObject->isLoggedIn() === true) {
                $loggedIn = true;
            }
        }
        
        return $loggedIn;
    }

    /**
     * Refresh the user session to prevent timeout after login when there is user activity
     * @param ?array $user      You can manually refresh a user, otherwise it loops through the persistence objects to find it
     * @return bool
     */
    public function refresh(array|bool $user = false)
    {
        $loggedIn = false;
        $negativeObjects = [];

        if(!$user)
        {
            foreach ($this->persistence as $persistenceObject) {
                if ($persistenceObject->isLoggedIn() === true) {
                    $loggedIn = true;
                    $user = $persistenceObject->getUser();
                }
                else {
                    $negativeObjects[] = $persistenceObject;
                }
            }

            if ($loggedIn == true) {
                foreach ($negativeObjects as $object) {
                    if ($object->getUpdateOnRefresh() === true) {
                        $object->refresh($user);
                    }
                }
            }
        }
        else
        {
            foreach($this->persistence as $persistenceObject)
            {
                $persistenceObject->refresh($user);
            }
        }
        
        return $loggedIn;
    }

    /**
     * Get the current user or false if not logged in
     * @return bool|array
     */
    public function getUser(): bool|array
    {
        foreach ($this->persistence as $persistenceObject) {
            $user = $persistenceObject->getUser();

            if (is_array($user) && !empty($user)) {
                return $user;
            }
        }
        
        return false;
    }

    /**
     * Try to register a new user
     * @param string $username
     * @param string $password
     * @param array $data           Associative array with fieldname / value pairs of data that will be saved with the user
     * @return bool                 Returns true if successful, false if not
     */
    public function register($username, $password, $data = [])
    {
        return $this->storage->register($username, $password, $data);
    }

    /**
     * Logs a user out
     */
    public function logout()
    {
        $return = true;

        foreach ($this->persistence as $persistenceObject) {
            if (!$persistenceObject->logout()) {
                $return = false;
            }
        }
        
        return $return;
    }
}
