<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Model\Users;

$app->get('/', function ($request, $response) {
    if (isset($_SESSION['auth'])) {
        return $response->withStatus(302)->withHeader('Location', '/dashboard');
    }
})->add($authMiddleware);

$app->get('/login', function ($request, $response) {
    $msg = $this->flash->getMessages();
    return $this->view->render($response, '/auth/login.twig', ['message' => $msg]);
});

$app->post('/login', function ($request, $response) {
    $credentials = $request->getParsedBody();
    $auth = new \Lib\Auth();
    $isAuthorized = $auth->login($credentials['email'], $credentials['password']);

    if ($isAuthorized) {
        return $response->withStatus(302)->withHeader('Location', '/dashboard');
    } else {
        $this->flash->addMessage('error', 'Oh no! Credential is mismatched');
        return $response->withStatus(302)->withHeader('Location', '/login');
    }
});

$app->get('/forgot-pwd', function ($request, $response) {;
    $msg = $this->flash->getMessages();
    return $this->view->render($response, '/auth/forgot_password.twig', ['message' => $msg]);
});

$app->post('/forgot-pwd', function ($request, $response) {
    $formData = $request->getParsedBody();
    $userDetails = Lib\App::getUserByEmail($formData['email']);

     if ($userDetails) {
         $uniqidId = \Lib\UUID::v4();
         $userDetails->pwd_reset_token = $uniqidId;
         $userDetails->save();
         $link = 'http://localhost:8080/password-reset?email='. $formData['email'] . '&password_token='.$uniqidId;
         var_dump($link); die();
     } else {
         $this->flash->addMessage('error', 'Sorry, Email address does not registered');
     }
    return $response->withStatus(302)->withHeader('Location', '/forgot-pwd');
});

$app->get('/password-reset', function ($request, $response) {
    $token = $request->getQueryParam('password_token');

    if(isset($token)) {
        $user = Users::where('pwd_reset_token', $token)->first();

        if(isset($user) && $user) {
            return $this->view->render($response, '/auth/reset_password.twig', ['token' => $token]);
        }
    }
    $this->flash->addMessage('error', 'Sorry, Unauthorised access');
    return $response->withStatus(302)->withHeader('Location', '/password-reset');
});

$app->post('/password-reset', function ($request, $response) {
    $formData = $request->getParsedBody();
    $userDetails = \Lib\App::getUserByPwdToken($formData['pwd_reset_token']);

    if (strlen($formData['password']) >= 6) {
        if ($formData['password'] == $formData['confirm_password']) {

            $userDetails->password = password_hash($formData['password'], PASSWORD_BCRYPT);

            if ($userDetails->save()) {
                $this->flash->addMessage('success', 'Password has been successfully changed!');
                return $response->withStatus(302)->withHeader('Location', '/login');
            } else {
                $this->flash->addMessage('error', 'Sorry, something went wrong');
            }

        } else {
            $this->flash->addMessage('error', 'Password doesn\'t match');
        }
    } else {
        $this->flash->addMessage('error', 'Password should be minimum six character');
    }
    return $response->withStatus(302)->withHeader('Location', '/login');
});

$app->get('/logout', function (Request $request, Response $response) {
    session_destroy();
    return $response->withStatus(302)->withHeader('Location', '/login');
});




