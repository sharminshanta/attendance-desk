<?php
namespace Model;


use Illuminate\Database\Eloquent\Model;
use Lib\App;
use Illuminate\Database\Query\Expression as raw;

class Leave extends Model
{
    protected $table = 'leaves';

    public function typeYear()
    {
        return $this->belongsTo(TypeYear::class);
    }

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function leaveApprovedBy()
    {
        return $this->belongsTo(Users::class, 'approved_by');
    }

    public function typeCount($typeID)
    {
        return $this->where('type_id', $typeID)->count();
    }

    public static function lists(
        $userId = null,
        $status,
        $role = null,
        $option,
        $year = null,
        $page = null,
        $limit = null
    ) {
        if ($year == null) {

            $year = date('Y');
        }

        if ($page != null && $limit != null) {
            $query = self::skip($page * $limit)->take($limit);
            $query = $query->select('*', new raw('datediff(request_end_date, request_start_date) as leaveduration'))
                ->where(new raw('year(created_at)'), $year);

        } else {
            $query = self::select('*', new raw('datediff(request_end_date, request_start_date) as leaveduration'))
                ->where(new raw('year(created_at)'), $year);

        }


        if ($role == 'employee') {
            $query = $query->where('user_id', $userId);
        }

        if (!empty($option['user_id'])) {
            $query = $query->where('user_id', $option['user_id']);
        }

        if (!empty($option['date'])) {
            $date = date("Y-m-d", strtotime($option['date']));
            $query = $query->where(new raw('DATE_FORMAT(request_start_date,\'%Y-%m-%d\')'), $date);
        }

        if (!empty($option['request_start_date']) or !empty($option['request_end_date'])) {
            $startDate = date("Y-m-d", strtotime($option['request_start_date']));
            $endDate = date("Y-m-d", strtotime($option['request_end_date']));
            $query = $query->WhereBetween('request_start_date', array($startDate, $endDate));
        }

        $query = $query->where('status', $status)->orderBy('created_at', 'desc')->get();

        return $query;
    }

    public function add($formData)
    {
        $this->uuid = \Lib\UUID::v4();
        $this->type_year_id = $formData['type_year_id'];
        $this->user_id = App::getUserId();
        $this->request_start_date = date("Y-m-d h:i:s", strtotime($formData['request_start_date']));
        $this->request_end_date = date("Y-m-d h:i:s", strtotime($formData['request_end_date']));
        $this->created_at = date("Y-m-d h:i:s");
        $this->description = $formData['description'];

        if ($this->save()) {
            return $this;
        } else {
            return false;
        }

    }

    public function getLeaveDayByYear($userId, $typeYearID = null)
    {
        if ($typeYearID) {
            $query = $this->select('leaves.*');
        } else {
            $query = $this->select(
                'user_id',
                'type_year.id as type_year_id',
                new raw('sum(datediff(confirm_end_date, confirm_start_date)) as total_days'),
                'leave_type.name',

                new raw('type_year.days - sum(datediff(confirm_end_date, confirm_start_date)) as remaining_days'),
                'type_year.days as leave_official_days'
            );

        }

        $query = $query->join('type_year', function ($join) {
            $join->on('leaves.type_year_id', '=', 'type_year.id');

        })
            ->join('leave_type', function ($join2) {
                $join2->on('leave_type.id', '=', 'type_year.leave_type_id');
            })
            ->where('leaves.status', 2);

        if ($typeYearID) {
            $query = $query->where('type_year.id', $typeYearID)->where('user_id', $userId);

        } else {
            $query = $query->groupBy('type_year.id')
                ->groupBy('user_id')
                ->where('user_id', $userId);

        }
        $query = $query->get();

        return $query;
    }

    public function leaveReports($year, $totalOfficeLeave, $userID = false)
    {
        $query = $this->select('user_id', new raw('sum(datediff(confirm_end_date, confirm_start_date)) as taken_days'),
            new raw($totalOfficeLeave . '- sum(datediff(confirm_end_date, confirm_start_date)) as remaining_days'))
            ->with(['user'])
            ->join('type_year', function ($join) use ($year) {
                $join->on('leaves.type_year_id', '=', 'type_year.id');
                $join->where('year', $year);
            })
            ->where('status', 2);
        $query = $query->groupBy('user_id');

        if ($userID) {
            $query = $query->where('user_id', $userID);
            $query = $query->first();

        } else {
            $query = $query->get();

        }

        return $query;
    }

    public function leaveStatCount($year, $userID)
    {
        $query = $this->select(new raw('       SUM(CASE 
             WHEN status = 1 THEN 1
             ELSE 0
           END) AS pending,        SUM(CASE 
             WHEN status = 2 THEN 1
             ELSE 0
           END) AS accepted ,         SUM(CASE 
             WHEN status = 3 THEN 1
             ELSE 0
           END) AS denied'))
            ->join('type_year', function ($join) use ($year) {
                $join->on('leaves.type_year_id', '=', 'type_year.id');
                $join->where('year', $year);
            });
        $query = $query->where('user_id', $userID);
        $query = $query->first();

        return $query;
    }
}