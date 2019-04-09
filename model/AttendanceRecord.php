<?php
namespace Model;


use Illuminate\Database\Eloquent\Model;
use Lib\App;
use Illuminate\Database\Query\Expression as raw;


class AttendanceRecord extends Model
{
    protected $table = 'attendance_record';

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    /**
     * @param $date
     * @param $checkinTime
     * @param $userId
     * @param null $checkoutTime
     * @param null $workingHour
     * @return bool|AttendanceRecord
     */
    public static function saveAttendanceRecord( $date, $checkinTime, $userId, $checkoutTime = null, $workingHour = null)
    {
        $attendance = new \Model\AttendanceRecord();
        $attendance->date = $date;
        $attendance->check_in = $checkinTime;
        $attendance->user_id = $userId;
        $attendance->check_out = $checkoutTime;
        $attendance->working_hour = $workingHour;

        if ($attendance->save()) {
            return $attendance;
        } else {
            return false;
        }
    }

    /**
     * @param $userID
     * @param $date
     * @param $checkType
     * @return mixed
     */
    public static function finalCheckInCheckOut($userID, $date, $checkType)
    {
        if ($checkType == 'check_in') {
            $order = 'asc';
        }
        elseif($checkType == 'check_out' ){
            $order = 'desc';
        }

        $finalCheckOut = AttendanceRecord::where('user_id', $userID)
            ->whereDate('date', $date)->orderBy('created_at', $order)->first();

        return $finalCheckOut;

    }

    public static  function totalWorkingHour($date, $userId) {
        $query = self::where('user_id', $userId)
            ->whereDate('date', $date)
            ->select(new raw('SEC_TO_TIME( SUM( TIME_TO_SEC( `working_hour`))) as working_hour'))
            ->groupBy('user_id')->first();

        return $query;
    }

    public static  function userAttendanceRecord()
    {
        return self::where('user_id', App::getUserId())
            ->whereDate('date', date('Y-m-d'))
            ->orderBy('created_at', 'desc')
            ->first();
    }
}