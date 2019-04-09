<?php

namespace Model;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    public $timestamps = false;

    /**
     * @param $formData
     * @param $path
     * @return $this|bool
     */
    public  function add($formData, $path)
    {
        $this->uuid = \Lib\UUID::v4();
        $this->first_name = $formData['first_name'];
        $this->last_name = $formData['last_name'];
        $this->gender = $formData['gender'];
        $this->email = $formData['email'];
        $this->phone = $formData['phone'];
        $this->role = $formData['role'];
        $this->department = $formData['department'];
        $this->password = password_hash($formData['password'], PASSWORD_BCRYPT);
        $this->profile_pic = str_replace('public', '',$path);
        $this->date_of_birth = date($formData['birth_date']);
        $this->joined_date = date($formData['join_date']);
        $this->website = $formData['website'];

        if ($this->save()) {
            return $this;
        } else {
            return false;
        }

    }

    public function update($formData, $uuid)
    {
        $userDetails = \Lib\App::getUserByUuid($uuid);
        $user = $this->find($userDetails->id);
        $user->first_name = $formData['first_name'];
        $user->last_name = $formData['last_name'];
        $user->gender = $formData['gender'];
        $user->phone = $formData['phone'];
        $user->role = $formData['role'];
        $user->department = $formData['department'];
        $user->status = $formData['status'];
        $user->date_of_birth = date($formData['birth_date']);
        $user->joined_date = date($formData['join_date']);
        $user->website = $formData['website'];

        if ($user->save()) {
            return $user;
        } else {
            return false;
        }
    }

    public function list($queryData, $page, $limit)
    {
        $query = $this;

        if (!empty($queryData['first_name'])) {
            $query = $query->Where('first_name', 'LIKE',"%".$queryData['first_name']."%");
        }

        if (!empty($queryData['last_name'])) {
            $query = $query->Where('last_name', 'LIKE',"%".$queryData['last_name']."%");
        }

        if (!empty($queryData['email'])) {
            $query = $query->Where('email', $queryData['email']);
        }

        if (!empty($queryData['gender'])) {
            $query = $query->Where('gender', $queryData['gender']);
        }

        if (!empty($queryData['birth_date'])) {
            $query = $query->Where('date_of_birth', $queryData['birth_date']);
        }

        if (!empty($queryData['role'])) {
            $query = $query->Where('role', $queryData['role']);
        }
        if (!empty($queryData['department'])) {
            $query = $query->Where('department', $queryData['department']);
        }

        if (!empty($queryData['join_date'])) {
            $query = $query->Where('joined_date', $queryData['join_date']);
        }

        if (!empty($queryData['website'])) {
            $query = $query->Where('website', $queryData['website']);
        }

        if (!empty($queryData['status'])) {
            $query = $query->Where('status', $queryData['status']);
        }

        $query =  $query->skip($page * $limit)
            ->take($limit)
            ->orderBy('id', 'desc')
            ->get();
        return $query;
    }

    public function attendanceRecord($date = null)
    {
        $query =  $this->hasMany(AttendanceRecord::class, 'user_id' , 'id');

        if($date != null){
            $query =  $query->whereDate('date', $date);
        }
        return $query;

    }

    public function attendance($date = null)
    {
        $query =  $this->hasMany(Attendance::class, 'user_id' , 'id');

        if($date != null){
            $query =  $query->whereDate('date', $date);
        }
        return $query;
    }
}