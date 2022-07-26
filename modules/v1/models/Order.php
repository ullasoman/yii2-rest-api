<?php

namespace app\modules\v1\models;

use Exception;
use Yii;
use Razorpay\Api\Api;
use yii\helpers\Url;

/**
 * This is the model class for table "orders".
 *
 * @property int $id
 * @property float $total_price
 * @property int $status
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string|null $transaction_id
 * @property string|null $razorpay_order_id
 * @property int|null $created_at
 * @property int|null $created_by
 *
 * @property User $createdBy
 * @property OrderAddress $OrderAddress
 * @property OrderItems[] $orderItems
 */
class Order extends \yii\db\ActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_PAID = 2;
    const STATUS_FAILED = 3;
    const STATUS_SHIPPING = 4;
    const STATUS_COMPLETED = 10;
    const PAYMENT_TYPE_ONLINE = 1;
    const PAYMENT_TYPE_COD = 2;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orders';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['total_price', 'status'], 'required'],
            [['total_price'], 'number'],
            [['status', 'created_at', 'created_by'], 'integer'],
            [['firstname', 'lastname'], 'string', 'max' => 45],
            [['email', 'transaction_id', 'razorpay_order_id'], 'string', 'max' => 255],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'total_price' => 'Total Price',
            'status' => 'Status',
            'firstname' => 'Firstname',
            'lastname' => 'Lastname',
            'email' => 'Email',
            'transaction_id' => 'Transaction ID',
            'razorpay_order_id' => 'Razorpay Order ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
        ];
    }

    /**
     * Gets query for [[OrderAddress]].
     *
     * @return \yii\db\ActiveQuery|\common\models\query\OrderAddressQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[OrderItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::className(), ['order_id' => 'id']);
    }
    public function saveAddress($user_id)
    {
        //$user_id = Yii::$app->user->id;
        $UserAddress = UserAddress::find()->select('address, city, zipcode')->where(['id' => $user_id])->one();

        $orderAddress = new OrderAddress();
        $orderAddress->order_id = $this->id;
        $orderAddress->address = $UserAddress->address;
        $orderAddress->city = $UserAddress->city;
        $orderAddress->zipcode = $UserAddress->zipcode;
        $orderAddress->state = 'Bengaluru';
        $orderAddress->country = 'India';

        if ($orderAddress->save()) {
            return true;
        }
        return $this->apiValidate($orderAddress->errors);
    }
    public function saveOrderItems($userId)
    {
        $cartItems = CartItems::getItemsForUser($userId);
        foreach ($cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->product_name = $cartItem['product_name'];
            $orderItem->product_id = $cartItem['product_id'];
            $orderItem->unit_price = $cartItem['product_price'];
            $orderItem->order_id = $this->id;
            $orderItem->quantity = $cartItem['quantity'];
            $orderItem->total_delivery = $cartItem['total_delivery_days'];
            $orderItem->delivery_days = $cartItem['delivery_days'];
            if (!$orderItem->save()) {
                return $this->apiValidate($orderItem->errors);
            }
        }

        return true;
    }
    public function getItemsQuantity($orderId)
    {
        return $sum = OrderItem::findBySql(
            "SELECT SUM(quantity) FROM order_items WHERE order_id = :orderId",
            ['orderId' => $orderId]
        )->scalar();
    }
    public function getItemsDelivery($orderId)
    {
        return $sum = OrderItem::findBySql(
            "SELECT SUM(total_delivery) FROM order_items WHERE order_id = :orderId",
            ['orderId' => $orderId]
        )->scalar();
    }
    public function getTotalPrice($orderId)
    {
        $orderDetails = self::find()->where(['id' => $orderId])->one();
        if ($orderDetails)
            return $orderDetails->total_price;
        else
            return null;
    }
    public function getPaymentMethod($orderId)
    {
        $paymentType = self::find()->where(['id' => $orderId])->one();
        if ($paymentType->payment_type == 1)
            return 'Online Payment';
        else
            return 'Cash on Delivery';
    }
    public function sendEmailToVendor()
    {
        //$path = Url::base() . 'config/mail';
        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'order_completed_vendor-html', 'text' => 'order_completed_vendor-text'],
                ['order' => $this]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo(Yii::$app->params['vendorEmail'])
            ->setSubject('New order has been made at ' . Yii::$app->name)
            ->send();
    }

    public function sendEmailToCustomer()
    {
        // $path = Yii::getAlias('@mail');
        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'order_completed_customer-html', 'text' => 'order_completed_customer-text'],
                ['order' => $this]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Your orders is confirmed at ' . Yii::$app->name)
            ->send();
    }
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PAID => 'Paid',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_DRAFT => 'Draft'
        ];
    }
    public function OrderStatus($status)
    {

        if ($status == self::STATUS_DRAFT) {
            return 'Draft';
        } else if (self::STATUS_CONFIRMED) {
            return 'Confirmed';
        } else if ($status == self::STATUS_PAID) {
            return 'Paid';
        } else if ($status == self::STATUS_SHIPPING) {
            return 'Shipping';
        } else if ($status == self::STATUS_COMPLETED) {
            return 'Completed';
        } else {
            return 'Failured';
        }
    }
    public function PaymentType($type)
    {

        if ($type == self::PAYMENT_TYPE_ONLINE) {
            return 'Online';
        } else {
            return 'COD';
        }
    }

    /** After record is saved
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $order_no = str_pad($this->id, 8, '0', STR_PAD_LEFT);

        $this->order_number = '#FE' . $order_no;
        $this->updateAttributes(['order_number']);
    }
}
