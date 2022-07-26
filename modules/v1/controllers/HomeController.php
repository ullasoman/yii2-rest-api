<?php

namespace app\modules\v1\controllers;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;
use Yii;
use app\components\Controller;
use app\components\JwtCreator;
use app\modules\v1\models\Product;
use app\modules\v1\models\search\ProductsSearch;
use app\modules\v1\models\OrderDeliveryTiming;
use app\modules\v1\models\CompanyInfo;
use app\modules\v1\models\Faq;

class HomeController extends RestController
{
    public function actionIndex()
    {
        // $my_array_of_parameters = ['product_name,product_image'];
        $search['ProductsSearch'] = Yii::$app->request->queryParams;
        $searchModel  = new ProductsSearch();
        // $searchModel->attributes = $my_array_of_parameters; // likely $_POST['something']
        $dataProvider = $searchModel->search($search);

        $deliveryTimings = OrderDeliveryTiming::find()->select('id, delivery_time')->where(['is_active' => true])->orderBy(['id' => SORT_ASC])->all();
        $faqs = Faq::find()->select('faq_title, faq_content')->where(['is_active' => true])->orderBy(['id' => SORT_DESC])->all();
        $companyInfo =  CompanyInfo::find()->select('company_name, company_address, company_phone, company_email')->where(['is_active' => true])->one();

        $data = [
            'dataModels' => $dataProvider->models,
            "delivery_timings" => $deliveryTimings,
            "faqs" => $faqs,
            "companyInfo" => $companyInfo,
        ];
        return $this->apiItem($data);
        // return $this->apiCollection([
        //     'count'      => $dataProvider->count,
        //     'dataModels' => $dataProvider->models,
        // ], $dataProvider->totalCount);
    }
}
