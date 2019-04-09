<?php


namespace Lib;


use Model\Users;

class Auth
{
    /**
     * @param $email
     * @param $pwd
     * @return bool
     *
     * Authorizing user credentials. And then storing authorized user infomation
     * into SESSION['auth'] and return true. Otherwise return false.
     */
    public function login($email, $pwd)
    {
        $user = new Users();
        $result = $user->where('email', $email)->first();

        // Authorizing user credential
        if(isset($result->password) && $result->password)
        {
            // If authorized then matching password encrption.
            if(password_verify($pwd, $result->password) == true)
            {
                // If password encryption matched, then return true.
                $_SESSION['auth'] = $result;
                return true;
            }
        }

        // Otherwise return false.
        $_SESSION['auth'] = null;
        return false;
    }
}