<?php

namespace app\modules\v1\models;

use yii\base\Model;
use app\models\User;
use DateTime;

class RegisterForm extends Model
{

    public $firstname;
    public $lastname;
    public $phone_no;
    public $username;
    public $email;
    public $password;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['firstname', 'required'],
            ['firstname', 'string', 'max' => '255'],
            ['lastname', 'required'],
            ['lastname', 'string', 'max' => '255'],
            ['phone_no', 'required'],
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\app\models\User', 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\app\models\User', 'message' => 'This email address has already been taken.'],
            ['password', 'required'],
            ['password', 'string', 'min' => 8],
        ];
    }

    /**
     * Register user.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function register()
    {
        if (!$this->validate()) {
            return null;
        }
        $now = new DateTime();

        $user = new User();
        $user->firstname = $this->firstname;
        $user->lastname = $this->lastname;
        $user->username = $this->username;
        $user->email = $this->email;
        $user->phone_no = $this->phone_no;
        $user->created_at = $now->format('Y-m-d H:i:s');
        $user->setPassword($this->password);
        $user->generateAuthKey();

        return $user->save() ? $user : null;
    }
}
