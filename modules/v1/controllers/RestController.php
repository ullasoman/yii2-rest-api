<?php

namespace app\modules\v1\controllers;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;
use Yii;
use app\components\Controller;
use app\components\JwtCreator;
use app\modules\v1\models\LoginForm;

class RestController extends Controller
{
    public function init()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->user->enableSession = false;
    }

    /**
     * @inheritdoc
     */

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'except' => [
                'login', 'register', 'index'
            ],
        ];

        return $behaviors;
    }

    /**
     * @return \yii\web\Response
     */

    public function actionLogin()
    {

        $dataRequest['LoginForm'] = Yii::$app->request->post();
        $model = new LoginForm();
        if ($model->load($dataRequest) && ($result = $model->login())) {

            //generation jwt auth token for the logged in user
            $accesstocken = JwtCreator::createToken(Yii::$app->user->id);

            $response = [
                "userid" => Yii::$app->user->id,
                "token" => $accesstocken,
                "emailVerify" => true,
                "message" => "Logged in as " . Yii::$app->user->identity->email,
            ];

            return $this->apiItem($response);
        }

        return $this->apiValidate($model->errors);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionData()
    {
        $user = Yii::$app->user->identity;

        return $this->apiItem($user);
    }
}
