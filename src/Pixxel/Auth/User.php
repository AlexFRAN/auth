<?php
namespace Pixxel\Auth;

/**
 * The main user-class which holds all the users data
 */
class User
{
    private $usernameField;
    private $data;

    /**
     * @param array $data               The data-array with all the users fields
     * @param string $usernameField     The name the username-field has (sometimes we rather user the email field)
     */
    public function __construct(array $data, $usernameField = 'username')
    {
        $this->data = $data;
        $this->usernameField = $usernameField;
    }

    /**
     * Get the username only
     * @return string|bool
     */
    public function getUsername(): string|bool
    {
        return isset($this->data[$this->usernameField]) ? $this->data[$this->usernameField] : false;
    }

    /**
     * Removes the password from the data array
     */
    public function removePassword()
    {
        if(!empty($this->data['password']))
        {
            unset($this->data['password']);
        }
    }

    /**
     * Get a specific field if set, if field is not set, return everything
     * @param string|bool $field
     * @return mixed
     */
    public function get($field = false)
    {
        if(!$field)
        {
            return $this->data;
        }

        if(!isset($this->data[$field]))
        {
            throw new \Exception("Field: ".$field." not found in user array.");
        }

        return $this->data[$field];
    }

    /**
     * Set a specific field to a new value
     * @param string $field
     * @param mixed $value
     */
    public function set($field, $value)
    {
        if(!$field)
        {
            return;
        }

        $this->data[$field] = $value;
    }
}