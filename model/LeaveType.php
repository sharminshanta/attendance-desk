<?php
namespace Model;


use Illuminate\Database\Eloquent\Model;
use Lib\App;
use Illuminate\Database\Query\Expression as raw;

class LeaveType extends Model
{
    protected $table = 'leave_type';

    public function typeYear()
    {
        return $this->belongsTo(TypeYear::class);
    }
}