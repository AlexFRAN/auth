# Pixxel Authentication

### Note: This is just for testing purposes, DO NOT use this in production!

### Alright, that out of the way, let's begin:

I wrote this library as a simple auth library, which should be extendable easily.
By default, php sessions are used for perstistency and a mysql/mariadb database for user storage.
However, these functions are implemented as objects with an interface, which allows you to swap those out by writing another implementation (for instance to use jwt token instead of sessions, oracle db or webservice instead of a mysql database and so on).
At the moment it is in an alpha state, i did not implement dependency injection for loading the corresponding libraries, but in the future that may be an option.
Also i did not do any advanced security checks or even an audit from someone else, so be careful.

### Installation

Install it via composer:

    composer require pixxel/auth

Then you can use it like this:

    require_once(dirname(__FILE__).'/vendor/autoload.php');

    $secret = 'mysupersecretkey';                               // The key is used to generate a hmac verification for the data saved in the session
    $dbal = new Pixxel\DBAL('dbuser', 'dbpass', 'dbname');      // By default the lib uses our dbal library for user storage
    $userStorage = new Pixxel\Auth\UserStorage\Database([       // Create a user storage with the $dbal instance as db handler, the Database class contains all the methods to register / login / verify users in the db
        'dbal' => $dbal
    ]);
    $sessionHandler = new Josantonius\Session\Session();        // We use the Session library from Josantonius by default
    $persistence = new Pixxel\Auth\Persistence\Session(['handler' => $sessionHandler, 'secret' => $secret]);    // And pass that to our session-handler, for that you could write jwt-handlers or other implementations
    $auth = new Pixxel\Auth($userStorage, $persistence);        // Finally, create an auth instance and pass the user- and session-storage to it

    // Now we are set and can for instance try to login a user:
    if($auth->login('myusername', 'mypassword'))
    {
        echo 'Success, you are now logged in!';
    }
    else
    {
        echo 'Username or password wrong';
    }


These are the default configuration values, you can personalize them however:

#### The user storage

For the database implementation, you have the following customization possibilities:

    dbal: Pass on the dbal instance
    usersTable (String): The name of the table, used to load the users, defaults to "users"
    usernameField (String): The name of the username field, often the field "email" is used, defaults to: "username"
    conditions (Array): Further conditions, saved as Key / Value pair, for instance, sometimes you want to check a field like "active" or similar to be true, so that a user can login, in that case: ['active' => 1]
    hashAlgorithm (String): The hashing algorithm used to hash passwords, supported values: "argon2i", "bcrypt", "argon2id". Defaults to "argon2i"

Further options for the session handler is in the works (session duration and so on)

The Auth library can do the following things:

#### 1.) Adding new users

You can add new users, it throws an exception if the user exists already, so it works like this:

    try
    {
        $auth->register($username, $password, ['otherfield' => 'valueforthisfield', 'anotherfieldintheusertable' => 'valueforthat']);
    }
    catch(\Exception $e)
    {
        echo 'Something went wrong while registering a user: '.$e->getMessage();
    }


#### 2.) Login a user

As shown above, you can login a user:

    if($auth->login($username, $password))
    {
        echo 'Logged in';
    }

An example with a user table where active has to be 1:

    if($auth->login($username, $password, ['active' => 1]))
    {
        echo 'Logged in';
    }

#### 3.) Check if a user is currently logged in

    if($auth->isLoggedIn())
    {
        echo 'Yes, someone is logged in';
    }

#### 4.) Get the currently logged in user details

    $user = $auth->getUser();   // Will return an array with the users fields apart the password or, if no user is logged in, simply false

#### 5.) Logout a user

    $auth->logout();

Thats all for now