<?php
/**
 * User: idgu
 * Date: 19.11.2017
 * Time: 12:36
 */

namespace App\Controllers\Admin;


class Users extends \Core\Controller
{

    protected  function before()
    {
    }

    protected function after()
    {
    }

    public function indexAction()
    {
        echo 'Hello from users controller';
    }
}