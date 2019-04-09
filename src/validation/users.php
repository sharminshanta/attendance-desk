<?php

/*use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Model\Users;*/

class userValidation{

public  function add(){

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

}

}





