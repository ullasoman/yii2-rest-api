<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "company_info".
 *
 * @property int $id
 * @property string $company_name
 * @property string $company_address
 * @property int $company_phone
 * @property string $company_email
 * @property int|null $is_active
 * @property string $created_at
 */
class CompanyInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_name', 'company_address', 'company_phone', 'company_email'], 'required'],
            [['company_phone', 'is_active'], 'integer'],
            [['created_at'], 'safe'],
            [['company_name', 'company_address', 'company_email'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_name' => 'Company Name',
            'company_address' => 'Company Address',
            'company_phone' => 'Company Phone',
            'company_email' => 'Company Email',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
        ];
    }
}
