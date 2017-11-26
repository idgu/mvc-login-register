<?php
/**
 * User: idgu
 * Date: 18.11.2017
 * Time: 20:14
 */

namespace Core;

use \App\Auth;
use \App\Flash;
use \App\Config;

abstract class Controller
{


    protected $route_params = array();


    public function __call($name, $args)
    {
        $method = $name. 'Action';
        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func([$this, $method], $args);
                $this->after();
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }

    }


    protected function before()
    {

    }


    protected function after()
    {

    }

    public function redirect($url)
    {
        header('Location: '.Config::URL.$url);
        exit;
    }

    /**
     * Save page, and redirect user to /login if is not logged
     */
    public function requireLogin()
    {
        if (!Auth::getUser()) {
            Flash::addMessage('Please login to access that page', Flash::INFO);
            Auth::rememberRequestedPage();

            $this->redirect('login');
        }
    }


    public function __construct($route_params)
    {
        $this->route_params = $route_params;
    }
}