<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Query\Expression as raw;
use Model\Attendance;

$app->get('/dashboard', function (Request $request, Response $response) {
    if ($this->app->getUserRole() == 'admin') {
        $attendance = new \Model\Attendance();
        $userAttendance = $attendance->where( new raw('date(date)') , date('Y-m-d'))
            ->whereNotNull('check_in')->get();
        $attendanceInfo = Attendance::attendanceInfo();
        return $this->view->render($response, '/dashboard/admin.twig', [
            'data' => $userAttendance,
            'attendanceInfo' => $attendanceInfo
        ]);
    } elseif ($this->app->getUserRole() == 'employee') {
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/dashboard/employee.twig', ['message' => $msg]);
    }
})->add($authMiddleware);