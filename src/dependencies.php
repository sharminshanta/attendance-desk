<?php
// DIC configuration

$container = $app->getContainer();




// view renderer
$container['view'] = function ($container) {

    $userAttendanceRecord = \Model\AttendanceRecord::where('user_id', \Lib\App::getUserId())->
    whereDate('date', date('Y-m-d'))->orderBy('created_at', 'desc')->first();

    $officeTime = \Lib\App::getMetaValue('office_starting_time');

    $settings = $container->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    $view->addExtension(new Twig_Extension_Debug());
    $twigExtra = $view->getEnvironment();
    $twigExtra->addGlobal('userAttendanceRecord', $userAttendanceRecord);
    $twigExtra->addGlobal('session', $_SESSION);
    $twigExtra->addGlobal('user', \Lib\App::getUserProfile());



    if ($officeTime) {
        $twigExtra->addGlobal('office', 1);
    }
    else{
        $twigExtra->addGlobal('office', 2);

    }


    return $view;
};


// monolog logs
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));

    return $logger;
};


// Flash messages
$container['flash'] = function ($c) {
    return new Slim\Flash\Messages();
};

$container['app'] = function ($c){
    return new \Lib\App();
};


use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PWD,
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Set the event dispatcher used by Eloquent models... (optional)
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
$capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();
