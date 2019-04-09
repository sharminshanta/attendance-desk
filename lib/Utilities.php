<?php


namespace Lib;

use Illuminate\Support\Facades\DB;
use Model\Leave;
use Model\LeaveType;
use Illuminate\Database\Query\Expression as raw;

class Utilities
{
    /**
     * @param $tmp
     * @param $name
     * @param $path
     * @param null $customName
     * @return array|bool
     */
    public static function uploadImages($tmp, $name, $path, $customName = null)
    {
        $ext = end(explode(".", $name));
        $uploadFile = $path . $customName . '.' . $ext;
        if ($ext && $ext != '') {
            if (move_uploaded_file($tmp, $uploadFile)) {
                $name = explode('/', $uploadFile);
                $name = end($name);
                $data = array(
                    'name' => $name,
                    'path' => $uploadFile,
                );
                return $data;
            }
            return false;
        }
    }

    /**
     * @param $file
     * @param $path
     * @param null $customName
     * @return bool
     */
    public static function uploadDocument($file, $path, $customName = null)
    {
        $uploadFile = $path . '/' . $file['name'];
        $ext = end(explode(".", $file['name']));
        if ($customName) {
            $uploadFile = $path . '/' . $customName . '.' . $ext;
        }
        if ($ext && $ext != '') {
            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                return $path;
            }
            return false;
        }
    }

    /**
     * @param $request
     * @return mixed|string
     *
     */
    public static function referer($request)
    {
        if (is_array($request) && isset($request[0])) {
            return $request[0];
        }
        return '/';
    }

    /**
     * @param $endTime , $startTime
     * @return difference between $endTime and $startTime
     *
     */
    public static function timeDiff($endTime, $startTime)
    {
        $endTime = date_create($endTime);
        $startTime = date_create($startTime);
        $difference = date_diff($endTime, $startTime);

        if ($difference) {
            return $difference->format("%H:%I:%S");
        } else {
            return false;
        }

    }

    public static function uuidDecode($uuid)
    {
        $leaveType = LeaveType::where('uuid', $uuid)->first();

        return $leaveType->id;

    }

    public static function checkUserLeaveToday()
    {

        $checkUsers = Leave::select('user_id')
            ->where(new raw('date(confirm_start_date)'), '<=', date('Y-m-d'))
            ->where(new raw('date(confirm_end_date)'), '>=', date('Y-m-d'))
            /*->join('leaves', function ($join) {
                $join->on('leave_request.id', '=', 'leave_confirm.leave_request_id');
            })*/->get();
        $user_id = array();
        foreach ($checkUsers as $checkUser) {
            $user_id [] = $checkUser['user_id'];
        }
        return $user_id;
    }
}