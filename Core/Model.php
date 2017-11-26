<?php
/**
 * User: idgu
 * Date: 19.11.2017
 * Time: 18:27
 */

namespace Core;

use PDO;
use App\Config;

abstract class Model
{
    public static function getDB()
    {
        static $db = null;
        if ($db == null) {
            try{
                $db = new PDO('mysql:host='.Config::DB_HOST.';dbname='.Config::DB_NAME.';charset=utf8', 'root', '');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $db;
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }
        return $db;
    }
}