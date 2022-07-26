<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "order_delivery_timing".
 *
 * @property int $id
 * @property string $delivery_time
 * @property int|null $is_active
 * @property string $created_at
 *
 * @property Orders[] $orders
 */
class OrderDeliveryTiming extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_delivery_timing';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['delivery_time'], 'required'],
            [['is_active'], 'integer'],
            [['created_at'], 'safe'],
            [['delivery_time'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'delivery_time' => 'Delivery Time',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Orders::className(), ['delivery_timing' => 'id']);
    }
}
