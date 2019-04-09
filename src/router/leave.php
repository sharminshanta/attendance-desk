<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Model\Users;
use Model\LeaveType;
use Model\Leave;
use Model\TypeYear;
use Lib\App;
use Illuminate\Database\Query\Expression as raw;

$app->group('/leave', function () use ($app, $adminMiddleware) {

    $app->get('/create', function ($request, $response) {
        $msg = $this->flash->getMessages();
        $leaveModal = new Leave();
        $year = date('Y');
        $leaveStatCount = $leaveModal->leaveStatCount($year, App::getUserId());
        $officeLeave = TypeYear::officeLeave();
        $leavreport = $leaveModal->leaveReports($year, $officeLeave->totalOfficeLeave, App::getUserId());
        $leaveType = TypeYear::where('year', date('Y'))
            ->get();
        return $this->view->render($response, '/leave/request.twig', [
            'leaveTypes' => $leaveType,
            'message' => $msg,
            'leavreports' => $leavreport,
            'totalOfficedays' => $officeLeave->totalOfficeLeave,
            'leaveStatCount' => $leaveStatCount,
            'typeErrorMessage' => "There has no type . Please, inform to admin"
        ]);
    });

    $app->post('/create', function ($request, $response) {
        $formData = $request->getParsedBody();
        $leaveModal = new Leave();
        $year = date('Y');
        $leaveStatCount = $leaveModal->leaveStatCount($year, App::getUserId());
        $totalOfficeLeave = TypeYear::officeLeave();
        $leavreport = $leaveModal->leaveReports($year, $totalOfficeLeave->totalOfficeLeave, App::getUserId());
        $validation = new Valitron\Validator($_POST);
        $validation->rule('required', 'type_year_id')->message('Type is required');
        $validation->rule('required', 'request_start_date')->message('Start Date is required');

        if ($formData['request_start_date'] < date("m/d/Y")) {
            $validation->addInstanceRule('checkStartDate', function () {
                return false;
            });
            $validation->rule('checkStartDate', 'request_start_date')->message('Start date can not be previous day');
        }

        $validation->rule('required', 'request_end_date')->message('End date is required');
        $validation->rule('required', 'description')->message('Description is required');

        if ($formData['request_start_date'] > $formData['request_end_date']) {
            $validation->addInstanceRule('checkDate', function () {
                return false;
            });
            $validation->rule('checkDate', 'request_end_date')->message('End date can not be less than start date');
        }

        if ($validation->validate()) {
            if ($leaveModal->add($formData)) {
                $this->flash->addMessage('success', 'Leave request has been successfully created');
                return $response->withStatus(302)->withHeader('Location', '/leave');
            }
        } else {
            $leaveType = TypeYear::where('year', date('Y'))->get();
            $errors = $validation->errors();
            $oldValue = $validation->data();
            return $this->view->render($response, '/leave/request.twig', [
                'errors' => $errors,
                'oldValue' => $oldValue,
                'leaveTypes' => $leaveType,
                'leavreports' => $leavreport,
                'totalOfficedays' => $totalOfficeLeave->totalOfficeLeave,
                'leaveStatCount' => $leaveStatCount
            ]);
        }
    });

    $app->get('/details/{uuid}', function ($request, $response) {
        $leaveModal = new Leave();
        $msg = $this->flash->getMessages();
        $uuid = $request->getAttribute('uuid');
        $leaveRequest = Leave::where('uuid', $uuid)->first();
        $totalOfficeLeave = TypeYear::officeLeave();
        $year = date('Y');
        $leaveStatCount = $leaveModal->leaveStatCount($year, $leaveRequest->user_id);
        $leavreport = $leaveModal->leaveReports($year, $totalOfficeLeave->totalOfficeLeave, $leaveRequest->user_id);
        return $this->view->render($response, '/leave/details.twig', [
            'leaveRequest' => $leaveRequest,
            'leavreports' => $leavreport,
            'totalOfficedays' => $totalOfficeLeave->totalOfficeLeave,
            'leaveStatCount' => $leaveStatCount,
            'message' => $msg
        ]);
    });

    $app->post('/confirm/{uuid}', function ($request, $response) {
        $uuid = $request->getAttribute('uuid');
        $formData = $request->getParsedBody();
        $leaveRequest = Leave::where('uuid', $uuid)->first();

        $startDate = new DateTime($formData['confirm_start_date']);
        $endDate = new DateTime($formData['confirm_end_date']);

        if ($startDate->format('Y-m-d') < date('Y-m-d')) {
            $this->flash->addMessage('error', 'Invalid start date');
        }
        elseif ($startDate->format('Y-m-d') > $endDate->format('Y-m-d')) {
            $this->flash->addMessage('error', 'Invalid end date');
        } else {
            $leaveRequest->uuid = $formData['uuid'];
            $leaveRequest->id = $formData['id'];
            $leaveRequest->status = 2;
            $leaveRequest->confirm_start_date = date("Y-m-d h:i:s", strtotime($formData['confirm_start_date']));
            $leaveRequest->confirm_end_date = date("Y-m-d h:i:s", strtotime($formData['confirm_end_date']));
            $leaveRequest->approved_by = App::getUserId();

            if ($leaveRequest->save()) {
                $this->flash->addMessage('success', 'Leave request has been accepted');
            } else {
                $this->flash->addMesssge('error', 'Something went wrong');
            }
        }

        return $response->withStatus(302)->withHeader('Location', '/leave/details/' . $uuid);
    });

    $app->get('/cancel/{uuid}', function ($request, $response) {
        $uuid = $request->getAttribute('uuid');
        $leaveRequest = Leave::where('uuid', $uuid)->first();
        $leaveRequest->status = 3;
        $leaveRequest->save();
        return $response->withStatus(302)->withHeader('Location', '/leave/list');
    });

    $app->get('/type', function ($request, $response) {
        $typeYearModel = new TypeYear();
        $msg = $this->flash->getMessages();
        $leaveTypes = LeaveType::get();
        $typeYear = TypeYear::typeYear();
        $d = 'Leave type is empty. Please click <a>here<a/> to add leave types';
        return $this->view->render($response, '/leave/type.twig', [
            'leaveTypes' => $leaveTypes,
            'typeYear' => $typeYear,
            'typeYearModel' => $typeYearModel,
            'message' => $msg,
            'typeErrorMessage' => 'Leave type is empty. Please add leave types'
        ]);
    })->add($adminMiddleware);

    $app->post('/type', function ($request, $response) {
        $formData = $request->getParsedBody();
        $validation = new Valitron\Validator($_POST);
        $validation->rule('required', 'name')->message('This field is required');
        $leaveType = LeaveType::where('name', $formData['name'])->get()->first();
        $validation->rule('in', $leaveType)->message('This type has already existed');

        if ($validation->validate()) {

            foreach($formData['name'] as $name) {
                $leaveType = new LeaveType();
                $leaveType->uuid = \Lib\UUID::v4();
                $leaveType->name = $name;
                $leaveType->save();
            }

            $this->flash->addMessage('success', 'Leave type has been successfully created');
            return $response->withStatus(302)->withHeader('Location', '/leave/type');
        } else {
            $this->flash->addMessage('error', 'Sorry, This type has already existed');
            $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
            return $response->withStatus(302)->withHeader('Location', $refererLink);
        }
    })->add($adminMiddleware);

    $app->post('/update', function ($request, $response) {
        $formData = $request->getParsedBody();
        $typeYear = TypeYear::where('id', $formData['id'])->first();

        $checkData = TypeYear::where('leave_type_id' , $formData['leave_type_id'])
            ->where('id','!=' , $formData['id'])->where('year', $formData['year'])->first();
        if ( $checkData == null) {
            $typeYear->days = $formData['days'];
            $typeYear->updated_at = date("Y-m-d h:i:s");

            if ($typeYear->save()) {
                $this->flash->addMessage('success', 'type has been successfully updated');
            }
        }

        $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
        return $response->withStatus(302)->withHeader('Location', $refererLink);

    })->add($adminMiddleware);

    $app->post('/day', function ($request, $response) {
        $formData = $request->getParsedBody();
        $checkData = TypeYear::where('leave_type_id' , $formData['leave_type_id'])
            ->where('year', $formData['year'])->first();

        if ($formData['year'] < date("Y")) {
            $this->flash->addMessage('error', 'Previous year is not permitted');
        }

        if($checkData == null) {
            $leaveDayYear = new TypeYear();
            $leaveDayYear->leave_type_id = $formData['leave_type_id'];
            $leaveDayYear->days = $formData['days'];
            $leaveDayYear->year = $formData['year'];
            $leaveDayYear->created_at = date("Y-m-d h:i:s");

            if ($leaveDayYear->save()) {
                $this->flash->addMessage('success', 'Leave day and year successfully created');
            }
        } else {
            $this->flash->addMessage('error', 'Sorry, This type has already existed');
        }
        return $response->withStatus(302)->withHeader('Location', '/leave/type');
    })->add($adminMiddleware);

    $app->get('/delete/{id}', function ($request, $response) {
        $id = $request->getAttribute('id');
        $typeCount = TypeYear::where('id', $id)->where('year', date("Y"))->count();

        if ($typeCount != 0) {
            $this->flash->addMessage('error', 'This leave type has already used . You can not delete this');
            return $response->withStatus(302)->withHeader('Location', '/leave/type');

        } else {
            $leaveType = TypeYear::find($id);
            if ($leaveType->delete()) {
                $this->flash->addMessage('success', 'Leave type has been deleted successfully');
                return $response->withStatus(302)->withHeader('Location', '/leave/type');
            }
        }
    })->add($adminMiddleware);

    $app->get('/reports[/{year}]', function ($request, $response) {
        $queryData = $request->getQueryParams();
        $page = 0;
        $limit = App::getMetaValue('leave_list_perPage');

        if (isset($queryData['page']) && $queryData['page']) {
            $page = (int)$queryData['page'];
            $page = $page - 1;
        }

        $leaveModel = new Leave();
        $yearChoose = $request->getAttribute('year', date('Y'));
        $totalOffice = TypeYear::select(new raw('sum(days) as totalOfficeLeave'))
            ->where('year', date('Y'))->first();
        $acceptedLeave = $leaveModel->leaveReports($yearChoose, $totalOffice->totalOfficeLeave);
        $year = Leave::select(new raw('year(created_at) as year'))
            ->groupBy(new raw('year(created_at)'))->get();

        $count = $leaveModel->count();
        $totalPage = (int)($count / $limit);

        if ($count % $limit > 0) {
            $totalPage = $totalPage + 1;
        }
        return $this->view->render($response, 'leave/reports.twig', [
            'acceptedLeave' => $acceptedLeave,
            'leaveModel' => $leaveModel,
            'totalOfficeLeave' => $totalOffice->totalOfficeLeave,
            'years' => $year,
            'totalPage' => $totalPage,
            'count' => $count,
        ]);
    })->add($adminMiddleware);

    $app->get('/reports-details/{id}', function ($request, $response) {
        $userID = $request->getAttribute('id');
        $leave = new Leave();
        $leaveDays =  $leave->getLeaveDayByYear($userID);
        $totalOfficeLeave = TypeYear::officeLeave();
        $year = date('Y');
        $leavreport = $leave->leaveReports($year, $totalOfficeLeave->totalOfficeLeave, $userID);

        return $this->view->render($response, 'leave/reports-details.twig', [
            'reports' => $leaveDays,
            'leaveModel' => $leave,
            'totalOfficeLeave' => $totalOfficeLeave->totalOfficeLeave,
            'leaveReport' => $leavreport,
            'userDetails' => Users::find($userID)

        ]);
    })->add($adminMiddleware);

    $app->get('[/{type}[/{year}]]', function ($request, $response) {
        $queryData = $request->getQueryParams();
        $users = Users::get();
        $dataType = $request->getAttribute('type', 'pending');
        $yearChoose = $request->getAttribute('year', date('Y'));
        $msg = $this->flash->getMessage();

        $page = 0;
        $limit = \Lib\App::getMetaValue('leave_list_perPage');

        if ($limit == null || $limit == 0) {
            $limit = 10;
        }

        if (isset($queryData['page']) && $queryData['page']) {
            $page = (int)$queryData['page'];
            $page = $page - 1;
        }

        /**
         * initial variables $userID, $status, $role
         */
        $userID = App::getUserRole() == 'admin'?  null : App::getUserId();
        $status = null;
        $role = App::getUserRole();

        if ($dataType == 'pending') {
            $typeTitle = 'pending';
            $status = 1;
        } elseif ($dataType == 'accepted') {
            $typeTitle = 'accepted';
            $status = 2;
        } elseif ($dataType == 'denied') {
            $typeTitle = 'denied';
            $status = 3;
        }

        $requestData = Leave::lists($userID, $status, $role, $queryData , $yearChoose, $page, $limit);
        $count = Leave::lists($userID, 1, $role, $queryData, $yearChoose, $page, $limit)->count();
        $totalPage = (int)($count / $limit);

        if ($count % $limit > 0) {
            $totalPage = $totalPage + 1;
        }

        $year = Leave::select(new raw('year(created_at) as year'))
            ->groupBy(new raw('year(created_at)'))->get();
        return $this->view->render($response, '/leave/list.twig', [
            'leaveRequests' => $requestData,
            'typeTitle' => $typeTitle,
            'totalPage' => $totalPage,
            'count' => $count,
            'message' => $msg,
            'users' => $users,
            'years' => $year
        ]);
    });

})->add($authMiddleware);








