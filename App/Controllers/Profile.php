<?php
/**
 * User: idgu
 * Date: 24.11.2017
 * Time: 23:12
 */

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Flash;


class Profile extends Authenticated
{

    public function showAction()
    {
        View::renderTemplate('/Profile/show.html');
    }

    public function editAction()
    {
        View::renderTemplate('/Profile/edit.html');
    }


    public function updateAction()
    {
        $user = Auth::getUser();

        if ($user->updateProfile($_POST)) {
            Flash::addMessage('Changes saved');
            $this->redirect('/profile/show');
        } else {
            View::renderTemplate('/Profile/edit.html',
                ['user'=>$user]);
        }
    }
}