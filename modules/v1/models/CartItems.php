<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "cart_items".
 *
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $product_id
 * @property int|null $quantity
 * @property string $created_at
 *
 * @property CustomerInfo $customer
 * @property Product $product
 */
class CartItems extends \yii\db\ActiveRecord
{
    const SESSION_KEY = 'CART_ITEMS';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cart_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_id', 'product_id', 'quantity'], 'integer'],
            [['created_at'], 'safe'],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['customer_id' => 'id']],
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
            'customer_id' => 'Customer ID',
            'product_id' => 'Product ID',
            'quantity' => 'Quantity',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(CustomerInfo::className(), ['id' => 'customer_id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
    }
    public static function getItemsForUser($currUserId)
    {
        $cartItems = CartItems::findBySql(
            "SELECT
                           c.id as cart_id,
                           c.product_id as product_id,
                           c.delivery_days,
                           c.total_delivery_days,
                           p.product_image,
                           p.product_name,
                           p.product_price,
                           c.quantity,
                           p.product_price * c.quantity * c.total_delivery_days as total_price
                    FROM cart_items c
                             LEFT JOIN product p on p.id = c.product_id
                     WHERE c.customer_id = :userId",
            ['userId' => $currUserId]
        )
            ->asArray()
            ->all();
        if ($cartItems)
            return $cartItems;
        else
            return null;
    }
    public static function getTotalQuantityForUser($currUserId)
    {
        if (\Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItems::SESSION_KEY, []);
            $sum = 0;
            foreach ($cartItems as $cartItem) {
                $sum += $cartItem['quantity'];
            }
        } else {
            $sum = CartItems::findBySql(
                "SELECT SUM(quantity) FROM cart_items WHERE customer_id = :userId",
                ['userId' => $currUserId]
            )->scalar();
        }

        return $sum;
    }
    public static function getTotalPriceForUser($currUserId)
    {
        if (Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItems::SESSION_KEY, []);
            $sum = 0;
            foreach ($cartItems as $cartItem) {
                $sum += $cartItem['quantity'] * $cartItem['product_price'];
            }
        } else {
            $sum = CartItems::findBySql(
                "SELECT SUM(c.quantity * c.total_delivery_days * p.product_price) 
                    FROM cart_items c 
                    LEFT JOIN product p on p.id = c.product_id 
                WHERE c.customer_id = :userId",
                ['userId' => $currUserId]
            )->scalar();
        }

        return $sum;
    }

    public static function getTotalPriceForItemForUser($productId, $currUserId)
    {
        if (Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItems::SESSION_KEY, []);
            $sum = 0;
            foreach ($cartItems as $cartItem) {
                if ($cartItem['id'] == $productId) {
                    $sum += $cartItem['quantity'] * $cartItem['product_price'];
                }
            }
        } else {
            $sum = CartItems::findBySql(
                "SELECT SUM(c.quantity * c.total_delivery_days * p.product_price) 
                    FROM cart_items c 
                    LEFT JOIN product p on p.id = c.product_id 
                WHERE c.product_id = :id AND c.customer_id = :userId",
                ['id' => $productId, 'userId' => $currUserId]
            )->scalar();
        }

        return $sum;
    }

    public static function clearCartItems($currUserId)
    {
        CartItems::deleteAll(['customer_id' => $currUserId]);
        // if (Yii::$app->user->isGuest) {
        //     Yii::$app->session->remove(CartItems::SESSION_KEY);
        // } else {
        //     CartItems::deleteAll(['customer_id' => $currUserId]);
        // }
    }
    public static function checkItemExistInCart($productId)
    {
        $cartItem = self::find()->where(['product_id' => $productId])->one();
        if ($cartItem)
            return true;
        else
            return false;
    }
    public static function totalDeliveryDays($currUserId)
    {
        $query = (new \yii\db\Query())->from('cart_items')->where(['customer_id' => $currUserId]);
        $sum = $query->sum('total_delivery_days');
        if ($sum)
            return $sum;
        else
            return null;
    }
    public static function itemDeliveryDays($cartId)
    {
        $cartItem = self::find()->where(['id' => $cartId])->one();
        if ($cartItem)
            return $cartItem->total_delivery_days;
        else
            return 0;
    }
    // public static function getUnitPrice($itemId)
    // {
    //     $query = (new \yii\db\Query())->from('cart_items')->where(['customer_id' => $currUserId]);
    //     $sum = $query->sum('total_delivery_days');
    //     if ($sum)
    //         return $sum;
    //     else
    //         return 0;
    // }
}
