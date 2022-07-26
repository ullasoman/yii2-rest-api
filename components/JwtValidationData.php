<?php

namespace app\components;

class JwtValidationData extends \sizeg\jwt\JwtValidationData
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->validationData->setIssuer('http://localhost/yii2-jwt-test');
        $this->validationData->setAudience('http://localhost/yii2-jwt-test');
        $this->validationData->setId('4f1g23a12aa');

        parent::init();
    }
}
