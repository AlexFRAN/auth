<?php

namespace Pixxel\Auth\Persistence;

/**
 * Class that handles user persistence via cookies, uses the session library by josantonius
 */
class Cookie implements \Pixxel\Auth\PersistenceInterface
{
    private $handler;
    private $secret;
    private $duration;      // Duration in days
    private $prefix;        // Prefix the cookies to avoid collisions
    private bool $updateOnRefresh = true;


    public function __construct($data = [])
    {
        if (empty($data['handler']) || !$data['handler'] instanceof \Pixxel\Cookie) {
            throw new \Exception("Cookie handler not specified or not instance of \\Pixxel\\Cookie.");
        }

        if (empty($data['secret'])) {
            throw new \Exception("Secret key for cookie signing not provided (param: secret)");
        }

        if (empty($data['duration'])) {
            $data['duration'] = 14;     // 14 days by default
        }

        if (empty($data['prefix'])) {
            $data['prefix'] = 'pixx_auth_';     // 14 days by default
        }

        $this->handler = $data['handler'];
        $this->secret = $data['secret'];
        $this->duration = $data['duration'];
        $this->prefix = $data['prefix'];
        
        $this->handler->setPrefix($this->prefix);
    }

    /**
     * Sets if the persistence object should be updated on login and refresh
     * @param bool $update
     */
    public function setUpdateOnRefresh(bool $update)
    {
        $this->updateOnRefresh = $update;
    }

    /**
     * Checks if the object should be updated on login and refresh
     * @return bool
     */
    public function getUpdateOnRefresh(): bool
    {
        return $this->updateOnRefresh;
    }

    /**
     * Login a specific user
     * @param array $data   The user array
     */
    public function login($data = [])
    {
        $data['expiration'] = time() + (86400 * $this->duration);

        $verification = hash_hmac('sha256', json_encode($data), $this->secret);

        $this->handler->set('user', json_encode($data), $this->duration);
        $this->handler->set('verification', $verification, $this->duration);
    }

    /**
     * Verifies if a user is logged in
     * @return bool
     */
    public function isLoggedIn()
    {
        $user = $this->handler->get('user');
        
        if (empty($user)) {
            return false;
        }

        $verification = hash_hmac('sha256', $user, $this->secret);
        
        if ($verification != $this->handler->get('verification')) {
            $this->logout();

            return false;
        }

        $user = json_decode($user, true);

        if (time() > $user['expiration']) {
            $this->logout();

            return false;
        }

        return true;
    }

    /**
     * Refresh the user data
     * @param array $user
     */
    public function refresh(array $user)
    {
        $user['expiration'] = time() + (86400 * $this->duration);
        $this->handler->set('user', json_encode($user), $this->duration);
        $verification = hash_hmac('sha256', json_encode($user), $this->secret);
        $this->handler->set('verification', $verification, $this->duration);
    }

    /**
     * Get the current user saved in the session, false if none set
     * @return array|bool
     */
    public function getUser()
    {
        $user = $this->handler->get('user');
        $verification = hash_hmac('sha256', $user, $this->secret);

        if ($verification != $this->handler->get('verification')) {
            return false;
        }

        return $user ? json_decode($user, true) : false;
    }

    /**
     * Logs out the current user by destroying the session and regenerating the session-id
     */
    public function logout()
    {
        $this->handler->destroy('user');
        $this->handler->destroy('verification');

        return true;
    }
}
