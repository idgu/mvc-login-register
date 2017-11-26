<?php

namespace App;
/**
 * User: idgu
 * Date: 19.11.2017
 * Time: 18:34
 */
class Config
{
    const DB_HOST = 'localhost';
    const DB_NAME = 'mvc';
    const DB_USER = 'root';
    const DB_PASSWORD = '';

    /**
     * true - show errors in browser
     *
     * false - save errors in logs, and display error messages
     */
    const SHOW_ERRORS = true;
    const URL = 'http://localhost/mvc/public';

    /**
     * Key used to create token hash in \App\Token
     */
    const SECRET_KEY = '5enCAecoEjwXRVXdSi7MuWjLrPQI7Wr3';

}