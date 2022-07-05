<?php

namespace Pixxel\Auth\Persistence;

/**
 * Class that handles user persistence via sessions, uses the session library by josantonius
 */
class Session
{
    private $handler;
    private $secret;


    public function __construct($data = [])
    {
        if (empty($data['handler']) || !$data['handler'] instanceof \josantonius\Session\Session) {
            throw new \Exception("Session handler not specified or not instance of \\josantonius\\Session\\Session.");
        }

        if (empty($data['secret'])) {
            throw new \Exception("Secret key for session signing not provided (param: secret)");
        }

        $this->handler = $data['handler'];
        $this->secret = $data['secret'];

        if (!$this->handler->isStarted()) {
            $this->handler->start();
        }
    }

    /**
     * Login a specific user
     * @param array $data   The user array
     */
    public function login($data = [])
    {
        $verification = hash_hmac('sha256', json_encode($data), $this->secret);

        $this->handler->set('user', $data);
        $this->handler->set('verification', $verification);
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

        $verification = hash_hmac('sha256', json_encode($user), $this->secret);

        if ($verification != $this->handler->get('verification')) {
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
        $this->handler->set('user', $user);
        $verification = hash_hmac('sha256', json_encode($user), $this->secret);
        $this->handler->set('verification', $verification);
    }

    /**
     * Get the current user saved in the session, false if none set
     * @return array|bool
     */
    public function getUser()
    {
        $user = $this->handler->get('user');

        return $user ? $user : false;
    }

    /**
     * Logs out the current user by destroying the session and regenerating the session-id
     */
    public function logout()
    {
        $this->handler->destroy();
        $this->handler->start();
    }
}
