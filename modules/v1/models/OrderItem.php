<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "{{%order_items}}".
 *
 * @property int $id
 * @property string $product_name
 * @property int $product_id
 * @property float $unit_price
 * @property int $order_id
 * @property int $quantity
 *
 * @property Order $order
 * @property Product $product
 */
class OrderItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_items}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_name', 'product_id', 'unit_price', 'order_id', 'quantity'], 'required'],
            [['product_id', 'order_id', 'quantity'], 'integer'],
            [['unit_price'], 'number'],
            [['product_name'], 'string', 'max' => 255],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_name' => 'Product Name',
            'product_id' => 'Product ID',
            'unit_price' => 'Unit Price',
            'order_id' => 'Order ID',
            'quantity' => 'Quantity',
        ];
    }

    public static function getTotalPriceForItem($productId, $orderId)
    {
        $sum = Self::findBySql(
            "SELECT SUM(o.quantity * o.total_delivery * p.product_price) 
                FROM order_items o 
                LEFT JOIN product p on p.id = o.product_id 
            WHERE o.product_id = :id AND o.order_id = :orderId",
            ['id' => $productId, 'orderId' => $orderId]
        )->scalar();

        return $sum;
    }
    public static function getOrderItems($orderId)
    {
        $cartItems = self::findBySql(
            "SELECT
                           o.id as id,
                           o.order_id,
                           o.product_id as product_id,
                           o.delivery_days,
                           o.total_delivery as item_total_delivery,
                           p.product_image,
                           p.product_name,
                           p.product_price,
                           o.quantity,
                           p.product_price * o.quantity * o.total_delivery as item_total_price
                    FROM order_items o
                             LEFT JOIN product p on p.id = o.product_id
                     WHERE o.order_id = :orderId",
            ['orderId' => $orderId]
        )
            ->asArray()
            ->all();
        return $cartItems;
    }
}
