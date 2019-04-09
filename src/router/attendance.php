<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Lib\App;
use Model\AttendanceRecord;
use Model\Attendance;
use Model\Users;
use Illuminate\Database\Query\Expression as raw;


$app->group('/attendance', function () use ($app, $adminMiddleware) {

    $app->get('', function ($request, $response) {
        $queryData = $request->getQueryParams();
        $attendance = new Attendance();
        $users = Users::get();

        $page = 0;
        $limit = App::getMetaValue('attendance_list_perPage');

        if($limit == null || $limit == 0) {
            $limit = 10;
        }

        if (isset($queryData['page']) && $queryData['page']) {
            $page = (int)$queryData['page'];
            $page = $page - 1;
        }

        $userAttendance = $attendance->list($queryData, $page, $limit);
        $count = $attendance->count();
        $totalPage = (int)($count / $limit);

        if ($count % $limit > 0) {
            $totalPage = $totalPage + 1;
        }

        return $this->view->render($response, '/attendance/list.twig', [
            'attendances' => $userAttendance,
            'totalPage' => $totalPage,
            'count' => $count,
            'users' => $users
        ]);
    });

    $app->get('/', function ($request, $response) {
        return $this->view->render($response, '/layouts/application.twig', [
        ]);
    });

    $app->get('/checkin', function ($request, $response) {
        $attendanceModel = new AttendanceRecord();
        $attendance = $attendanceModel->saveAttendanceRecord(date('Y-m-d'), date('H:i'), App::getUserId());

        if ($attendance) {
            (new Attendance())->manageEmployeeAttendance(date('Y-m-d'), App::getUserId());
        } else {
            return false;
        }

        $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
        return $response->withStatus(302)->withHeader('Location', $refererLink);
    });

    $app->get('/checkout', function ($request, $response) {

        $userAttendanceRecord = AttendanceRecord::userAttendanceRecord();

        if ($userAttendanceRecord->check_out == NULL) {
            $userAttendanceRecord->check_out = date('H:i');
            $workingHour = \Lib\Utilities::timeDiff($userAttendanceRecord->check_out, $userAttendanceRecord->check_in);
            $userAttendanceRecord->working_hour = $workingHour;
            $userAttendanceRecord->save();
        }
        (new Attendance())->manageEmployeeAttendance(date('Y-m-d'), App::getUserId());
        $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
        return $response->withStatus(302)->withHeader('Location', $refererLink);

    });

    $app->get('/details/{uuid}/{date}', function ($request, $response) {
        $uuid = $request->getAttribute('uuid');
        $date = $request->getAttribute('date');
        $user = Users::where('uuid', $uuid)->first();
        $attendanceRecord = $user->attendanceRecord($date)->orderBy('id', 'desc')->get();
        $attendance = $user->attendance($date)->first();
        $workingHour = date('H:i', strtotime($attendance['working_hour']));
        $totalHour = date('H:i', strtotime($attendance['total_hour']));
        $leisureTime = date('H:i', strtotime($attendance['leisure_time']));
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/attendance/details.twig', [
            'userAttendance' => $attendanceRecord,
            'attendance' => $attendance,
            'working_hour' => $workingHour,
            'total_hour' => $totalHour,
            'leisure_time' => $leisureTime,
            'message' => $msg,
        ]);
    });

    $app->get('/add', function ($request, $response) {
        $users = Users::get();
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/attendance/add.twig', [
            'users' => $users,
            'message' => $msg
        ]);
    })->add($adminMiddleware);

    $app->post('/add', function ($request, $response) {
        $formData = $request->getParsedBody();
        $users = Users::get();
        $validation = new Valitron\Validator($_POST);
        $validation->rule('required', 'user_id')->message('Username is required');
        $validation->rule('required', 'date')->message('Date is required');
        $validation->rule('required', 'check_in')->message('Checkin time is required');
        $validation->rule('required', 'check_out')->message('Checkout time is required');

        if ($formData['date'] > date("Y-m-d")) {
            $validation->addInstanceRule('checkDate', function () {
                return false;
            });
            $validation->rule('checkDate', 'date')->message('Date is invalid');
        }

        if ($validation->validate()) {
            if ($formData['check_in'] > $formData['check_out']) {
                $this->flash->addMessage('error', 'Checkout time must be greater than checkin time');
                return $response->withStatus(302)->withHeader('Location', '/attendance/add');
            } else {
                $date = $formData['date'];
                $checkIn = $formData['check_in'];
                $checkOut = $formData['check_out'];
                $userId = $formData['user_id'];
                $workingHour = \Lib\Utilities::timeDiff($checkOut, $checkIn);
                $attendance = AttendanceRecord::saveAttendanceRecord($date, $checkIn, $userId, $checkOut, $workingHour);

                if ($attendance) {
                    (new Attendance())->manageEmployeeAttendance($date, $userId);
                } else {
                    return false;
                }
            }
        } else {
            $errors = $validation->errors();
            $oldValue = $validation->data();
            return $this->view->render($response, '/attendance/add.twig', [
                'users' => $users,
                'errors' => $errors,
                'oldValue' => $oldValue]);
        }
        return $response->withStatus(302)->withHeader('Location', '/attendance');
    });

    $app->get('/update/{uuid}/{date}/{id}', function ($request, $response) {
        $userUid = $request->getAttribute('uuid');
        $date = $request->getAttribute('date');
        $attendanceRecordId = $request->getAttribute('id');
        $users = Users::get();
        $userDetail = Users::where('uuid', $userUid)->first();
        $attendanceRecord = AttendanceRecord::where('user_id', $userDetail->id)
            ->whereDate('date', $date)->where('id', $attendanceRecordId)->first();
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/attendance/update.twig', [
            'users' => $users,
            'attendanceRecord' => $attendanceRecord,
            'message' => $msg
        ]);
    })->add($adminMiddleware);

    $app->post('/update/{uuid}/{date}', function ($request, $response) {
        $formData = $request->getParsedBody();
        $userUid = $request->getAttribute('uuid');
        $date = $request->getAttribute('date');
        $userDetails = \Lib\App::getUserByUuid($userUid);
        $userID = $userDetails->id;

        if ($formData['check_in'] > $formData['check_out']) {
            $this->flash->addMessage('error', 'Checkout time must be greater than checkin time');
        } else {
            $attendanceRecord = AttendanceRecord::where('id', $formData['id'])->first();
            $finalCheckOut = AttendanceRecord::finalCheckInCheckOut($userID, $date, 'check_out');
            $finalCheckIn = AttendanceRecord::finalCheckInCheckOut($userID, $date, 'check_in');
            $totalHour = \Lib\Utilities::timeDiff($finalCheckOut->check_out, $finalCheckIn->check_in);
            $totalWorkingHour = AttendanceRecord::totalWorkingHour($date, $userID);

            if ($totalWorkingHour->working_hour > $totalHour) {
                $this->flash->addMessage('error', 'Working hour must not greater then total hour');
            } else {
                $attendanceRecord->check_in = $formData['check_in'];
                $attendanceRecord->check_out = $formData['check_out'];
                $attendanceRecord->user_id = $formData['user_id'];
                $attendanceRecord->working_hour = \Lib\Utilities::timeDiff($formData['check_out'], $formData['check_in']);

                if ($attendanceRecord->save()) {
                    (new Attendance())->manageEmployeeAttendance($attendanceRecord->date, $formData['user_id']);
                    $this->flash->addMessage('success', 'Attendance has been successfully updated');
                    return $response->withStatus(302)->withHeader('Location', '/attendance/details/' . $userUid . '/' . $date);
                }
            }
        }
        $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
        return $response->withStatus(302)->withHeader('Location', $refererLink);
    })->add($adminMiddleware);
})->add($authMiddleware);


$app->get('/attendance/absence', function ($request, $response) {
    $attendance = new Attendance();
    $attendance->absence();
});













