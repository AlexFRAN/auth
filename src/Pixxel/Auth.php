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
    private $authSession;   // Object that handles the user persistence, either through a session or some kind of token
    private $user;          // In here the current user-details are stored

    /**
     * At the moment i was too lazy to implement a DI-library, so the userstorage and user object have to be passed into the constructor -.-
     * @param object $storage
     */
    public function __construct(object $storage, object $authSession)
    {
        $this->storage = $storage;
        $this->authSession = $authSession;
    }

    /**
     * Try to login a user and return the user object if successful
     * @param string $username
     * @param string $password
     * @return bool|object
     */
    public function login($username, $password)
    {
        if($this->authSession->isLoggedIn())
        {
            $this->authSession->logout();
        }

        $user = $this->storage->verifyUser($username, $password);

        if(!$user)
        {
            return false;
        }

        $data = $user->get();
        unset($data['password']);   // Do not save the hashed password in the session

        $this->authSession->login($data);

        return true;
    }

    /**
     * Checked wheter a user is logged in or not
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->authSession->isLoggedIn();
    }

    /**
     * Get the current user or false if not logged in
     * @return bool|array
     */
    public function getUser()
    {
        return $this->authSession->getUser();
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
        return $this->authSession->logout();
    }
}