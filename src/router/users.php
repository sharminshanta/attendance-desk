<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Model\Users;

$app->group('/users', function () use ($app, $adminMiddleware) {

    $app->get('', function ($request, $response) {
        $queryData = $request->getQueryParams();
        $msg = $this->flash->getMessages();
        $user = new Users();

        $page = 0;
        $limit = \Lib\App::getMetaValue('user_list_perPage');

        if($limit == null || $limit == 0) {
            $limit = 10;
        }

        if (isset($queryData['page']) && $queryData['page']) {
            $page = (int)$queryData['page'];
            $page = $page - 1;
        }

        $users = $user->list($queryData, $page, $limit);
        $count = $user->count();
        $totalPage = (int)($count / $limit);

        if ($count % $limit > 0) {
            $totalPage = $totalPage + 1;
        }

        return $this->view->render($response, '/users/list.twig', [
            'users' => $users,
            'message' => $msg,
            'totalPage' => $totalPage,
            'count' => $count
        ]);
    })->add($adminMiddleware);

    $app->get('/add', function ($request, $response) {
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/users/add.twig', ['message' => $msg]);
    })->add($adminMiddleware);

    /**
     * Add new user
     */
    $app->post('/add', function ($request, $response) {
        $formData = $request->getParsedBody();
        $validation = new Valitron\Validator($_POST);
        $validation->rule('required', 'first_name')->message('First name is required');
        $validation->rule('required', 'last_name')->message('Last name is required');
        $validation->rule('required', 'email')->message('Email is required');
        $validation->rule('required', 'phone')->message('Phone is required');
        $validation->rule('required', 'gender')->message('Gender is required');
        $validation->rule('required', 'birth_date')->message('Birth date is required');
        $validation->rule('required', 'role')->message('Role is required');
        $validation->rule('required', 'department')->message('Department is required');
        $validation->rule('required', 'join_date')->message('Join date is required');
        $validation->rule('required', 'password')->message('Password is required');
        $validation->rule('required', 'confirm_password')->message('Confirm password is required');
        $validation->rule('url', 'website');
        $validation->rule('lengthMin', 'password', 6);
        $validation->rule('email', 'email');
        $userMail = Users::where('email', $formData['email'])->get()->first();
        $validation->rule('in', $userMail)->message('This email has already registered');
        $validation->rule('url', 'website');
        $validation->rule('equals', 'password', 'confirm_password')->message('Password does not matched');
        $validation->rule('alpha', 'first_name')->message('First name is not valid');
        $validation->rule('alpha', 'last_name')->message('Last name is not valid');
        $validation->rule('numeric', 'phone')->message('Phone number is not valid');

        if ($formData['birth_date'] > date("Y")) {
            $validation->addInstanceRule('checkBirthDate', function () {
                return false;
            });
            $validation->rule('checkBirthDate', 'birth_date')->message('Please make sure that you have used your real birth date');
        }

        if ($formData['join_date'] > date("Y")) {
            $validation->addInstanceRule('checkJoinDate', function () {
                return false;
            });
            $validation->rule('checkJoinDate', 'join_date')->message('Please make sure that you have used your real join date');
        }

        if ($formData['birth_date'] > $formData['join_date']) {
            $validation->addInstanceRule('checkdate', function () {
                return false;
            });
            $validation->rule('checkdate', 'join_date')->message('Please make sure that you have used your real join date');
        }

        if ($validation->validate()) {
            $image = $_FILES['profile_pic'];

            if ($image["size"] > 1000000) {
                $this->flash->addMessage('error', 'Sorry, your file is too large.');
                return $response->withStatus(302)->withHeader('Location', '/users/add');
            }

            if($image["type"] != "jpg" && $image["type"] != "png" && $image["type"] != "jpeg"
                && $image["type"] != "gif" && $image["type"] != '' ) {
                $this->flash->addMessage('error', 'Sorry, only JPG, JPEG, PNG & GIF files are allowed');
                return $response->withStatus(302)->withHeader('Location', '/users/add');
            }

            $profilePic = \Lib\Utilities::uploadImages($image['tmp_name'], $image['name'], 'public/assets/img/profiles/', uniqid());
            $imagePath = $profilePic['path'];
            $user = new Users();

            if ($user->add($formData, $imagePath)) {
                $this->flash->addMessage('success', 'User has been successfully created!');
                return $response->withStatus(302)->withHeader('Location', '/users/view/' . $user->uuid);
            } else {
                $this->flash->addMessage('error', 'Sorry, something went wrong');
            }
        } else {
            $errors = $validation->errors();
            $oldValue = $validation->data();
            return $this->view->render($response, '/users/add.twig', [
                'errors' => $errors,
                'oldValue' => $oldValue
            ]);
        }
    });

    $app->get('/view/{uuid}', function ($request, $response) {
        $uuid = $request->getAttribute('uuid');
        $user = Users::where('uuid', $uuid)->first();
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/users/view.twig', ['user' => $user, 'message' => $msg]);
    });

    $app->get('/update/{uuid}', function ($request, $response) {
        $uuid = $request->getAttribute('uuid');
        $user = Users::where('uuid', $uuid)->first();
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/users/update.twig', ['users' => $user, 'message' => $msg]);
    });

    $app->post('/update/{uuid}', function ($request, $response) {
        $formData = $request->getParsedBody();
        $uuid = $request->getAttribute('uuid');
        $userDetails = Users::where('uuid', $uuid)->first();
        $validation = new Valitron\Validator($formData);
        $validation->rule('required', 'first_name')->message('First name is required');
        $validation->rule('required', 'last_name')->message('Last name is required');
        $validation->rule('required', 'phone')->message('Phone is required');
        $validation->rule('required', 'gender')->message('Gender is required');
        $validation->rule('required', 'birth_date')->message('Birth date is required');
        $validation->rule('required', 'role')->message('Role is required');
        $validation->rule('required', 'department')->message('Department is required');
        $validation->rule('required', 'join_date')->message('Join date is required');
        $validation->rule('url', 'website');
        $validation->rule('alpha', 'first_name')->message('First name is not valid');
        $validation->rule('alpha', 'last_name')->message('Last name is not valid');
        $validation->rule('numeric', 'phone')->message('Phone number is not valid');

        if ($formData['birth_date'] > date("Y-m-d")) {
            $validation->addInstanceRule('checkBirthDate', function () {
                return false;
            });
            $validation->rule('checkBirthDate', 'birth_date')->message('Please make sure that you have used your real birth date');
        }

        if ($formData['join_date'] > date("Y-m-d")) {
            $validation->addInstanceRule('checkJoinDate', function () {
                return false;
            });
            $validation->rule('checkJoinDate', 'join_date')->message('Please make sure that you have used your real join date');
        }

        $birthDate = date_create($formData['birth_date']);
        $joinDate = date_create($formData['join_date']);
        $diff = date_diff($birthDate,$joinDate);


        if ($formData['birth_date'] > $formData['join_date']) {
            $validation->addInstanceRule('checkdate', function () {
                return false;
            });
            $validation->rule('checkdate', 'join_date')->message('Please make sure that you have used your real join date');
        }

        if ($validation->validate()) {
            $user = new Users();
            if ($user->update($formData, $uuid)) {
                $this->flash->addMessage('success', 'User has been successfully updated!');
                return $response->withStatus(302)->withHeader('Location', '/users/view/' . $uuid);
            } else {
                $this->flash->addMessage('error', 'Something went wrong');
            }
        } else {
            $errors = $validation->errors();
            $oldValue = $validation->data();
            return $this->view->render($response, '/users/update.twig', [
                'errors' => $errors,
                'oldValue' => $oldValue,
                'users' => $userDetails
            ]);
        }
    });

    $app->post('/change-profile-picture/{uuid}', function ($request, $response, $args) {
        $uuid = $request->getAttribute('uuid');
        $userDetails = \Lib\App::getUserByUuid($uuid);
        $image = $_FILES['photo'];
        $profilePic = \Lib\Utilities::uploadImages($image['tmp_name'], $image['name'], 'public/assets/img/profiles/', uniqid());
        $user = Users::find($userDetails->id);
        $user->profile_pic = str_replace('public', '', $profilePic['path']);

        if ($user->save()) {
            $this->flash->addMessage('success', 'Profile picture has been successfully updated!');
        } else {
            $this->flash->addMessage('error', 'Sorry, something went wrong');
        }

        $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
        return $response->withStatus(302)->withHeader('Location', $refererLink);
    });


    $this->get('/delete/{uuid}', function (Request $request, Response $response) {
        $uuid = $request->getAttribute('uuid');
        $userDetails = \Lib\App::getUserByUuid($uuid);
        $userId = \Lib\App::getUserId();
        $userDataById = Users::find($userDetails->id);

        if ($userId == $userDataById->id) {
            $this->flash->addMessage('error', 'Sorry, You are not permitted to delete this user');

        } else {
            $userDataById->delete();
            $this->flash->addMessage('success', 'User has been successfully deleted!');
        }
        return $response->withStatus(302)->withHeader('Location', '/users');
    });
})->add($authMiddleware);


/**
 * User profile
 */
$app->group('/profile', function () use ($app) {

    $app->get('/', function ($request, $response) {
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/users/profile.twig', ['message' => $msg]);
    });

    $app->get('/update', function ($request, $response) {
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/users/update_profile.twig', ['message' => $msg]);
    });

    $app->post('/update', function ($request, $response) {
        $formData = $request->getParsedBody();
        $validation = new Valitron\Validator($_POST);
        $validation->rule('required', 'first_name')->message('First name is required');
        $validation->rule('required', 'last_name')->message('Last name is required');
        $validation->rule('required', 'gender')->message('Gender is required');
        $validation->rule('required', 'birth_date')->message('Birth date is required');
        $validation->rule('required', 'website')->message('Website is required');
        $validation->rule('url', 'website');
        $validation->rule('alpha', 'first_name')->message('First name is not valid');
        $validation->rule('alpha', 'last_name')->message('Last name is not valid');

        if ($formData['birth_date'] > date("Y-m-d")) {
            $validation->addInstanceRule('checkBirthDate', function () {
                return false;
            });
            $validation->rule('checkBirthDate', 'birth_date')->message('Please make sure that you have used your real birth date');
        }

        if ($validation->validate()) {
            $user = Users::find(\Lib\App::getUserId());
            $user->first_name = $formData['first_name'];
            $user->last_name = $formData['last_name'];
            $user->gender = $formData['gender'];
            $user->date_of_birth = date($formData['birth_date']);
            $user->website = $formData['website'];

            if ($user->save()) {
                $this->flash->addMessage('success', 'Profile has been successfully updated!');
                return $response->withStatus(302)->withHeader('Location', '/profile/');
            } else {
                $this->flash->addMessage('error', 'Something went wrong');
            }
        } else {
            $errors = $validation->errors();
            $oldValue = $validation->data();
            return $this->view->render($response, '/users/update_profile.twig', ['errors' => $errors, 'oldValue' => $oldValue]);
        }
        return $response->withStatus(302)->withHeader('Location', '/profile/update');
    });

    $app->get('/change-password', function ($request, $response) {
        $msg = $this->flash->getMessages();
        return $this->view->render($response, '/users/change_password.twig', ['message' => $msg]);
    });

    $app->post('/change-password', function ($request, $response) {
        $formData = $request->getParsedBody();
        $user = \Lib\App::getUserProfile();

        if (password_verify($formData['old_password'], $user->password) == true) {
            $validation = new Valitron\Validator($_POST);
            $validation->rule('lengthMin', 'password', 6);
            $validation->rule('equals', 'password', 'confirm_password')->message('Password does not matched');
            $user = Users::find(\Lib\App::getUserId());
            $user->password = password_hash($formData['password'], PASSWORD_BCRYPT);

            if ($validation->validate()) {
                if ($user->save()) {
                    $this->flash->addMessage('success', 'Password has been successfully changed!');
                    return $response->withStatus(302)->withHeader('Location', '/profile/');
                }
            } else {
                $errors = $validation->errors();
                $oldValue = $validation->data();
                return $this->view->render($response, '/users/change_password.twig', ['errors' => $errors, 'oldValue' => $oldValue]);
            }
        } else {
            $this->flash->addMessage('error', 'Old password doesn\'t match');
        }
        return $response->withStatus(302)->withHeader('Location', '/profile/change-password');
    });

    $app->post('/change-profile-picture', function ($request, $response, $args) {
        $image = $_FILES['photo'];
        var_dump($image); die();
        $profilePic = \Lib\Utilities::uploadImages($image['tmp_name'], $image['name'], 'public/assets/img/profiles/', uniqid());
        $user = Users::find(\Lib\App::getUserId());
        $user->profile_pic = str_replace('public', '', $profilePic['path']);

        if ($user->save()) {
            $this->flash->addMessage('success', 'Profile picture has been successfully uploaded!');
        } else {
            $this->flash->addMessage('error', 'Sorry, something went wrong');
        }

        $refererLink = \Lib\Utilities::referer($request->getHeader("HTTP_REFERER"));
        return $response->withStatus(302)->withHeader('Location', $refererLink);
    });
})->add($authMiddleware);






