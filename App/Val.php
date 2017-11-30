<?php
/**
 * User: idgu
 * Date: 29.11.2017
 * Time: 09:35
 */

namespace App;


class Val
{
    public function __construct()
    {

    }

    public function minlength($data, $arg)
    {
        if (strlen($data) < $arg) {
            return "Your string can only be $arg long";
        }
    }

    public function match($data1, $data2)
    {
        if ($data1 != $data2) {
            return 'Not matchesz';
        }
    }

    public function maxlength($data, $arg)
    {
        if (strlen($data) > $arg) {
            return "Your string can only be $arg long";
        }
    }

    public function digit($data)
    {
        if (ctype_digit($data) == false) {
            return "Your string must be a digit";
        }
    }

    public function oneLetter($data)
    {
        if (preg_match('/.*[a-z]+.*/i', $data) == 0) {
            return 'Password needs at least one letter';
        }
    }
    public function oneNumber($data)
    {
        if (preg_match('/.*\d+.*/i', $data) == 0) {
            return 'Password needs at least one number';
        }
    }


    public function __call($name, $arguments)
    {
        throw new Exception("$name does not exist inside of: " . __CLASS__);
    }

}