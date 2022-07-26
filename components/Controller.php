<?php

namespace app\components;

use Yii;
use yii\filters\auth\HttpBearerAuth;

class Controller extends \yii\rest\Controller
{
    use TraitController;

    /**
     * @inheritdoc
     */
    // public function behaviors()
    // {
    //     $behaviors = parent::behaviors();
    //     $behaviors['authenticator'] = [
    //         'class' => HttpBearerAuth::className(),
    //     ];
    //     return $behaviors;
    // }
    public function init()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        \Yii::$app->user->enableSession = false;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'optional' => [
                'login',
                'register'
            ],
        ];

        return $behaviors;
    }
    /**
     * Api Validate error response
     */
    public function apiValidate($errors, $message = false)
    {
        Yii::$app->response->statusCode = 422;
        return [
            'statusCode' => 422,
            'name' => 'ValidateErrorException',
           // 'message' => $message ? $message : 'Error validation',
            'message' => $errors
        ];
    }

    /**
     * Api Created response
     */
    public function apiCreated($data, $message = false)
    {
        Yii::$app->response->statusCode = 201;
        return [
            'statusCode' => 201,
            'message' => $message ? $message : 'Created successfully',
            'data' => $data
        ];
    }

    /**
     * Api Updated response
     */
    public function apiUpdated($data, $message = false)
    {
        Yii::$app->response->statusCode = 202;
        return [
            'statusCode' => 202,
            'message' => $message ? $message : 'Updated successfully',
            'data' => $data
        ];
    }

    /**
     * Api Deleted response
     */
    public function apiDeleted($data, $message = false)
    {
        Yii::$app->response->statusCode = 202;
        return [
            'statusCode' => 202,
            'message' => $message ? $message : 'Deleted successfully',
            'data' => $data
        ];
    }

    /**
     * Api Item response
     */
    public function apiItem($data, $message = false)
    {
        Yii::$app->response->statusCode = 200;
        return [
            'statusCode' => 200,
            'message' => $message ? $message : 'Data retrieval successfully',
            'data' => $data
        ];
    }

    /**
     * Api Collection response
     */
    public function apiCollection($data, $total = 0, $message = false)
    {
        Yii::$app->response->statusCode = 200;
        return [
            'statusCode' => 200,
            'message' => $message ? $message : 'Data retrieval successfully',
            'data' => $data,
            'total' => $total
        ];
    }

    /**
     * Api Success response
     */
    public function apiSuccess($message = false)
    {
        Yii::$app->response->statusCode = 200;
        return [
            'statusCode' => 200,
            'message' => $message ? $message : 'Success',
        ];
    }
}
