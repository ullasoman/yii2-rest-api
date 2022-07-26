<?php

namespace app\components;

use Yii;
use sizeg\jwt\Jwt;

class JwtCreator
{

    /**
     * @inheritdoc
     */
    public static function createToken($user_id)
    {
        $jwt = Yii::$app->jwt;
        $signer = $jwt->getSigner('HS256');
        $key = $jwt->getKey();
        $time = time();
        $token = $jwt->getBuilder()
            ->issuedBy('http://localhost/yii2-jwt-test')
            ->permittedFor('http://localhost/yii2-jwt-test')
            ->identifiedBy('4f1g23a12aa', true)
            ->issuedAt($time)
            // ->expiresAt($time + 10)
            ->withClaim('uid', $user_id)
            ->getToken($signer, $key);
        return "Bearer " . (string)$token;
    }
}
