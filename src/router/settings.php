<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Lib\App;

$app->get('/settings', function ($request, $response) {
    $msg = $this->flash->getMessages();
    $userListperPage = App::getMetaValue('user_list_perPage');
    $attendanceListperPage = App::getMetaValue('attendance_list_perPage');
    $leaveListperPage = App::getMetaValue('leave_list_perPage');
    $officeTime = App::getMetaValue('office_starting_time');
    return $this->view->render($response, '/settings/settings.twig', [
        'userListperPage' => $userListperPage,
        'attendanceListperPage' => $attendanceListperPage,
        'leaveListperPage' => $leaveListperPage,
        'officeTime' => $officeTime,
        'message' => $msg]);
})->add($authMiddleware);

$app->post('/settings', function ($request, $response) {
    $settings = $request->getParsedBody();
    App::registerMetaData($settings);
    $this->flash->addMessage('success', 'Successfully Updated');
    return $response->withStatus(302)->withHeader('Location', '/settings');
})->add($authMiddleware);