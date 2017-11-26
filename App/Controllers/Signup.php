<?php
/**
 * User: idgu
 * Date: 21.11.2017
 * Time: 17:48
 */

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Input;

class Signup extends \Core\Controller
{

    public function newAction()
    {
        View::renderTemplate('/Signup/new.html', [
            'token_form' => Input::generateFormToken()
        ]);
    }


    public function createAction()
    {
       $user = new User($_POST);
       if ($user->save()) {

           $user->sendActivationEmail();

           $this->redirect('/signup/success');
       } else {
           View::renderTemplate('/Signup/new.html', [
               'user'       => $user,
               'token_form' => Input::generateFormToken()
           ]);
       }
    }


    public function successAction()
    {
        View::renderTemplate('/Signup/success.html');
    }


    public function activateAction()
    {
        User::activate($this->route_params['token']);
        $this->redirect('/signup/activated');
    }


    public function activatedAction()
    {
        View::renderTemplate('/Signup/activated.html');
    }

}