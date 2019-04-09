<?php
namespace Model;

use Illuminate\Database\Eloquent\Model;
use Lib\App;
use Illuminate\Database\Query\Expression as raw;

class Attendance extends Model
{
    protected $table = 'attendance';

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    /**
     * @param $queryData
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function list($queryData, $page, $limit)
    {
        $query = $this;

        if (!empty($queryData['user_id'])) {
            $query = $query->where('user_id', $queryData['user_id']);
        }

        if (!empty($queryData['date'])) {
            $date = date("Y-m-d", strtotime($queryData['date']));
            $query = $query->where(new raw('DATE_FORMAT(date,\'%Y-%m-%d\')'), $date);
        }

        if (!empty($queryData['start_date']) or !empty($queryData['end_date'])) {
            $startDate = date("Y-m-d", strtotime($queryData['start_date']));
            $endDate = date("Y-m-d", strtotime($queryData['end_date']));
            $query = $query->WhereBetween('date', array($startDate, $endDate));
        }

        if (!empty($queryData['check_in'])) {
            $time = date("H:i", strtotime($queryData['check_in']));
            $query = $query->where(new raw('DATE_FORMAT(check_in,\'%H:%i\')'), $time);
        }

        if (!empty($queryData['check_out'])) {
            $time = date("H:i", strtotime($queryData['check_out']));
            $query = $query->where(new raw('DATE_FORMAT(check_out,\'%H:%i\')'), $time);
        }

        if (!empty($queryData['status'])) {
            $query = $query->where('status', $queryData['status']);
        }

        if (!empty($queryData['department'])) {
            $query = $query->join('users', function ($join) use ($queryData) {
                $join->on('users.id', '=', 'attendance.user_id');
                $join->where('users.department', $queryData['department']);
            });
        }

        $userAttendance = $query->skip($page * $limit)->take($limit);

        if (App::getUserRole() == 'employee') {
            $userAttendance = $userAttendance->where('user_id', App::getUserId());
        }

        $attendance = $userAttendance->orderBy('date', 'desc')->get();

        return $attendance;

    }

    /**
     * Manage Employee Attandance
     * @param $date
     * @param $userId
     * @return bool
     */
    public  function manageEmployeeAttendance($date, $userId)
    {
        $finalCheckOut = AttendanceRecord::finalCheckInCheckOut($userId, $date, 'check_out');
        $finalCheckIn = AttendanceRecord::finalCheckInCheckOut($userId, $date, 'check_in');
        $totalHour = \Lib\Utilities::timeDiff($finalCheckOut->check_out, $finalCheckIn->check_in);
        $totalWorkingHour = (new AttendanceRecord())->totalWorkingHour($date, $userId);
        $totalLeisureTime = \Lib\Utilities::timeDiff($totalHour, $totalWorkingHour->working_hour);

        $query = $this;
        $attendanceReportCount = $query->where('user_id', $userId)
            ->whereDate('date', $date)
            ->count();
        $officeTime = App::getMetaValue('office_starting_time');

        if ($attendanceReportCount <= 0) {
            $query->date = $date;
            $query->check_in = $finalCheckIn->check_in;
            $query->check_out = $finalCheckOut->check_out;
            $query->user_id = $userId;
            $query->working_hour = $totalWorkingHour->working_hour;
            $query->total_hour = $totalHour;
            $query->leisure_time = $totalLeisureTime;
            $query->office_time = $officeTime;

            if ($finalCheckIn->check_in == $officeTime) {
                $query->status = 1;
            }

            if ($finalCheckIn->check_in < $officeTime) {
                $query->status = 1;
            }

            if ($officeTime < $finalCheckIn->check_in) {
                $query->status = 2;
            }

            $query->save();

        } elseif ($attendanceReportCount == 1) {
            $query = $query->where('user_id', $userId)
                ->whereDate('date', $date)
                ->first();
            $query->check_in = $finalCheckIn->check_in;
            $query->check_out = $finalCheckOut->check_out;
            $query->working_hour = $totalWorkingHour->working_hour;
            $query->total_hour = $totalHour;
            $query->leisure_time = $totalLeisureTime;

            if ($query->working_hour > $query->total_hour) {
                return false;
            }

            if ($finalCheckIn->check_in == $officeTime) {
                $query->status = 1;
            }

            if ($finalCheckIn->check_in < $officeTime) {
                $query->status = 1;
            }
            if ($officeTime < $finalCheckIn->check_in) {
                $query->status = 2;
            }
            $query->save();

        } else {
            return false;
        }
    }

    public static function attendanceInfo()
    {
        return self::select('user_id' , new raw('count(id) as totalCheckIn'))
            ->whereNotNull('check_in')
            ->where(new raw('year(date)') , date('Y'))
            ->where(new raw('month(date)') , date('m'))
            ->groupBy(new raw('month(date)'))
            ->groupBy(new raw('year(date)'))
            ->groupBy(new raw('user_id'))
            ->get();
    }

    public function absence()
    {
        $checkUserLeave = \Lib\Utilities::checkUserLeaveToday();
        $user = Users::select('id')->whereNotIn('id', $checkUserLeave)->get();
        $users = $user->toArray();
        $leaveUser = Users::select('id')->whereIn('id', $checkUserLeave)->get();
        $leaveUsers = $leaveUser->toArray();
        $attendance = $this->whereDate('date', date('Y-m-d'))->select('user_id')->get();
        $attendUsers = $attendance->toArray();

        foreach ($users as $user) {
            $hasUser = App::searchArrayByKeyValue($attendUsers, 'user_id', $user['id']);

            if (sizeof($hasUser) < 1) {
                $userId = $user['id'];
                if ($userId) {
                    $query = new $this;
                    $officeTime = App::getMetaValue('office_starting_time');
                    $query->date = date('Y-m-d');
                    $query->check_in = null;
                    $query->check_out = null;
                    $query->user_id = $user['id'];
                    $query->working_hour = '00:00:00';
                    $query->total_hour = '00:00:00';
                    $query->leisure_time = '00:00:00';
                    $query->office_time = $officeTime;
                    $query->status = 3;
                    $query->save();
                }
            }
        }

        foreach ($leaveUsers as $user) {
            $hasUser = App::searchArrayByKeyValue($attendUsers, 'user_id', $user['id']);

            if (sizeof($hasUser) < 1) {
                $userId = $user['id'];
                if ($userId) {
                    $query = new $this;
                    $officeTime = App::getMetaValue('office_starting_time');
                    $query->date = date('Y-m-d');
                    $query->check_in = null;
                    $query->check_out = null;
                    $query->user_id = $user['id'];
                    $query->working_hour = '00:00:00';
                    $query->total_hour = '00:00:00';
                    $query->leisure_time = '00:00:00';
                    $query->office_time = $officeTime;
                    $query->status = 4;
                    $query->save();
                }
            }
        }
    }
}