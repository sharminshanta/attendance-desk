<?php
namespace Lib;

use Model\Attendance;
use Model\AttendanceRecord;
use Model\Settings;
use Model\Users;
use Illuminate\Database\Query\Expression as raw;

class App
{
    /**
     * @var
     */
    private static $_instance;

    /**
     * @var string
     */
    public static $appsCompanyName = 'Sharmin Shanta';

    /**
     * @var string
     */
    public static $noReplyEmail = 'no-reply@previewtechs.com';


    public static function getInstance()
    {
        if (!is_null(self::$_instance)) {
            return self::$_instance;
        }
        self::$_instance = new self;
        return self::$_instance;
    }

    public static function getUserRole()
    {
        if ($_SESSION['auth']->role == 1) {
            return 'admin';
        } elseif ($_SESSION['auth']->role == 2) {
            return 'employee';
        }
    }

    /**
     * @return null
     */
    public static function getUserId()
    {
        $user = self::getUserProfile();
        return $user ? $user->id : null;
    }

    /**
     * @return null
     */
    public static function getUserProfile()
    {
        if (isset($_SESSION['auth']) && $_SESSION['auth']) {
            $user = new Users();
            $result = $user->where('id', $_SESSION['auth']->id)->first();
            return $result;
        }
        return null;
    }

    /**
     * @return null
     */
    public static function getUserByEmail($email)
    {
        $user = new Users();
        $result = $user->where('email', $email)->first();
        return $result;
    }

    /**
     * @return null
     */
    public static function getUserByUuid($uuid)
    {
        $user = new Users();
        $result = $user->where('uuid', $uuid)->first();
        return $result;
    }

    /**
     * @return null
     */
    public static function getUserByPwdToken($token)
    {
        $user = new Users();
        $result = $user->where('pwd_reset_token', $token)->first();
        return $result;
    }

    /**
     * @return null
     */
    public static function getUserById($id)
    {
        $user = new Users();
        $result = $user->where('id', $id)->first();
        return $result;
    }

    /**
     * returns the remote ip (client ip) of the user
     * @return string
     */
    public static function getRemoteAddress()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return array_pop($ip_list);
        }
        if (isset($_SERVER['REMOTE_IP'])) {
            $ip_list = explode(',', $_SERVER['REMOTE_IP']);
            return array_pop($ip_list);
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_list = explode(',', $_SERVER['REMOTE_ADDR']);
            return array_pop($ip_list);
        }
        return '';
    }

    /**
     * for setting key and value of meta table
     */
    public static function registerMetaData($data)
    {

        $i = 0;
        $return = [];
        foreach ($data as $key => $value) {
            $i = $i + 1;
            $count = Settings::where('key', $key)->count();
            if ($count == 0) {
                $newSetting = new Settings();
                $newSetting->key = $key;
                $newSetting->value = $value;
                $submit = $newSetting->save();
            } else {
                $oldKey = Settings::where('key', $key)->first();


                $flight = Settings::find($oldKey->id);
                $flight->value = $value;
                $submit = $flight->save();
            }

        }
        return $submit;

    }

    /**
     * @param $key returns the value
     * @return bool
     */
    public static function getMetaValue($key)
    {
        $data = Settings::where('key', $key)->first();
        if ($data) {
            return $data->value;
        }
        return false;
    }

    /**
     * @param $checkIn
     * @param $checkOut
     * @return bool
     */
    public static function timeCheck($checkIn, $checkOut)
    {
        if ($checkIn > $checkOut) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * returns the array, key and value
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    public static function searchArrayByKeyValue($array, $key, $value)
    {
        $results = array();
        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subArray) {
                $results = array_merge($results, self::searchArrayByKeyValue($subArray, $key, $value));
            }
        }
        return $results;
    }
}