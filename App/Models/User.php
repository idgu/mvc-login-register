<?php
/**
 * User: idgu
 * Date: 21.11.2017
 * Time: 18:05
 */

namespace App\Models;
use PDO;
use \App\Token;
use \App\Mail;
use \App\Config;
use \Core\View;
use \App\Form;
use \App\Validator;

class User extends \core\Model
{


    public $errors = array();


//    private $id;
//    private $email;
//    private $password;
    public function __construct($data = [], $validator = null)
    {
        foreach ($data as $key => $value) {
            if ($key == ['token_form']) continue;
            $this->$key = $value;
        }
    }


    /**
     * Validate form inputs, then if no error occured insert into database new user with activation_hash
     * and create dynamically User variable, $this->activation_token, which is neccesery for account activate
     *
     * @return bool
     */
    public function save(Validator $validator)
    {

        $errors = $this->validate($validator);

        if (empty($errors)) {
            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $token = new Token();
            $hashed_token = $token->getHash();
            $this->activation_token = $token->getValue();

            $sql = 'INSERT INTO users (name, email, password_hash, activation_hash) VALUES (:name, :email, :password_hash, :activation_hash)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':activation_hash', $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }


    /**
     * Check if form inputs are correct, if not add error messages into $this->errors table;
     */
    public function validate( Validator $validator)
    {
        $validator->validate();
        $this->errors = $validator->getErrors();
        return $validator->getErrors();
    }


    /**
     *
     * Check if user with given email exists
     *
     * @param $email
     * @param null $ignore_id
     * @return bool
     */
    public static function emailExists($email, $ignore_id = null)
    {
       $user = static::findByEmail($email);
       if ($user) {
           if ($user->id != $ignore_id){
                return true;
           }
       }

       return false;
    }


    /**
     * Check if given email exists in users database, when it exists return object of user via PDO::FETCH_CLASS
     *
     * @param $email
     * @return obj | null
     */
    public static function findByEmail($email)
    {
        $sql = 'SELECT * FROM users WHERE email = :email';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->execute();

        return $stmt->fetch();
    }


    /**
     * Check if given id exists in users database, when exists return object of user via PDO::FETCH_CLASS
     *
     * @param int $id
     * @return obj | null
     */
    public static function findById($id)
    {
        $sql = 'SELECT * FROM users WHERE id = :id';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->execute();

        return $stmt->fetch();
    }


    /**
     *
     * Check if user with given $mail exists in database, then check if password is correct.
     * If is correct, return \App\Models\User object.
     *
     * @param $email
     * @param $password
     * @return obj|bool
     */
    public static function authenticate($email, $password)
    {
        $user = static::findByEmail($email);
        if ($user && $user->is_active) {
            if (password_verify($password, $user->password_hash)) {
                return $user;
            }
        }

        return false;
    }


    /**
     * Create in database record (remembered) and dinamically create User instance variables
     * like: $this->remember_token and $this->expiry_timestamp.
     *
     * @return bool
     */
    public function rememberLogin()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->remember_token = $token->getValue();
        $this->expiry_timestamp = time() + 60 * 60 * 24 * 40;

        $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at) VALUES (:token_hash, :user_id, :expires_at)';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);

        return $stmt->execute();

    }



    /**
     * Find user by password_reset_hash
     *
     * @param $token Token send to user via Email
     * @return mixed
     */
    public static function findByPasswordReset($token)
    {
        $token = new Token($token);
        $hashed_token = $token->getHash();

        $sql = 'SELECT * FROM users WHERE password_reset_hash = :token_hash';
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->execute();

        $user = $stmt->fetch();
        if ($user) {
            if (strtotime($user->password_reset_expires_at) > time()) {

                return $user;
            }
        }
    }


    /**
     *
     * Resets users password
     *
     * @param $password
     * @param $password_confirmation
     * @return bool
     */
    public function resetPassword(Validator $validator, $password, $password_confirmation)
    {
        $this->password = $password;
        $this->password_confirmation = $password_confirmation;

        $this->validate($validator);

        if (empty($this->errors)) {
            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
            $sql = 'UPDATE users
                    SET password_hash = :password_hash,
                        password_reset_hash = NULL,
                        password_reset_expires_at = NULL
                    WHERE id = :id';
            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        }

        return false;
    }


    /**
     *
     * Send to given email password reset message if email exists in database
     *
     * @param $email
     * @return bool
     */
    public static function sendPasswordReset($email)
    {
        $user = static::findByEmail($email);

        if ($user) {
            if ($user->startPasswordReset()) {

                $user->sendPasswordResetEmail();
                return true;
            }
        }

        return false;
    }

    protected function sendPasswordResetEmail()
    {
        $url = Config::URL.'/password/reset/' . $this->password_reset_token;

        $text = View::getTemplate('Password/reset_email.txt', ['url' => $url]);
        $html = View::getTemplate('Password/reset_email.html', ['url' => $url]);
        Mail::send($this->email, 'Password reset', $text, $html);
    }


    /**
     *
     * Update database password_rest_has and expires date
     *
     * @return bool
     */
    protected function startPasswordReset()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->password_reset_token = $token->getValue();

        $expiry_timestamp = time() + 60 * 60 * 2;

        $sql = 'UPDATE users
                SET password_reset_hash = :token_hash,
                password_reset_expires_at = :expires_at
                WHERE id = :id
                ';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
        return $stmt->execute();
    }


    /**
     * Send activation email to newly created user
     */
    public function sendActivationEmail()
    {
        $url = Config::URL.'/signup/activate/' . $this->activation_token;

        $text = View::getTemplate('Signup/activation_email.txt', ['url' => $url]);
        $html = View::getTemplate('Signup/activation_email.html', ['url' => $url]);
        Mail::send($this->email, 'Account activation', $text, $html);
    }


    /**
     * Activate user account (via EMAIL);
     *
     * @param $value activation_token send via email
     * @return bool
     */
    public static function activate($value)
    {
        $token = new Token($value);
        $hashed_token = $token->getHash();

        $sql = 'UPDATE users
                SET is_active = 1,
                    activation_hash = null
                WHERE activation_hash = :hashed_token';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);
        return $stmt->execute();


    }

    public function updateProfile(Validator $validator, $data)
    {

        $this->name = $data['name'];
        $this->password = $data['password'];
        $this->password_confirmation = $data['password_confirmation'];

        $errors = $this->validate($validator);

        if (empty($errors)) {
            $sql = 'UPDATE users
            SET name = :name';

            if (isset($this->password) && isset($this->password_confirmation)) {
               $sql .= ', password_hash = :password_hash ';
            }

            $sql .= ' WHERE id = :id';

            print_r($sql);
            $db = static::getDB();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);

            if (isset($this->password)) {
                $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
                $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $this->id, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }




}