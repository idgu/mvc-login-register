<?php

// Autoloader composer
require '../vendor/autoload.php';


//Autoloader App
spl_autoload_register(function ($class) {
    $root = dirname(__dir__); //get the parent directory
    $file = $root . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_readable($file)) {
        require $root . '/' . str_replace('\\', '/', $class) . '.php';
    }
});


//Own error handler
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');


session_start();


$router = new Core\Router();

//Routing table
$router->add('{controller}/{action}/{userid:\d+}');
$router->add('{controller}/{action}');
$router->add('posts', ['controller'=> 'Posts', 'action'=> 'index']);
$router->add('home', ['controller'=> 'Home', 'action'=> 'index']);
$router->add('login', ['controller'=> 'Login', 'action'=> 'new']);
$router->add('signup', ['controller'=> 'Signup', 'action'=> 'new']);
$router->add('logout', ['controller'=> 'Login', 'action'=> 'destroy']);
$router->add('password/reset/{token:[\da-f]+}', ['controller'=> 'Password', 'action'=> 'reset']);
$router->add('signup/activate/{token:[\da-f]+}', ['controller'=> 'Signup', 'action'=> 'activate']);
$router->add('', ['controller'=> 'Home', 'action'=> 'index']);


$router->dispatch($_SERVER['QUERY_STRING']);
