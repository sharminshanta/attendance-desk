<?php
namespace Model;


use Illuminate\Database\Eloquent\Model;
use Lib\App;
use Illuminate\Database\Query\Expression as raw;

class TypeYear extends Model
{
    protected $table = 'type_year';

    public function type()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function getLeaveDayByYear($year){
        return $this->belongsTo(LeaveType::class, 'leave_type_id')->where('type_year.year', $year)->get();
    }

    public function getLeaveDayByYear2($year){
        return $this->join('leave_type', function ($join) {
            $join->on('type_year.leave_type_id', '=', 'leave_type.id');
        })->where('year', $year)->get();
    }

    public function getLeaveDayByYear1($year){
        return $this->with(['type'])->where('year', $year)->get();
    }

    public static function officeLeave()
    {
        return self::select(new raw('sum(days) as totalOfficeLeave'))
            ->where('year', date('Y'))
            ->first();
    }

    public static function typeYear() {
        return self::select('year', new raw('sum(days) as totaldays'))
            ->groupBy('year')->orderBy('year', 'desc')->get();
    }
}