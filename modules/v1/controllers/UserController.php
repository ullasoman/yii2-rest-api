<?php

namespace app\modules\v1\controllers;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;
use Yii;
use app\components\Controller;
use app\components\JwtCreator;
use app\modules\v1\models\CartItems;
use app\modules\v1\models\RegisterForm;
use app\modules\v1\models\LoginForm;
use app\modules\v1\models\Order;
use app\modules\v1\models\OrderItem;
use app\modules\v1\models\Product;
use app\modules\v1\models\User;
use app\modules\v1\models\UserAddress;
use DateTime;
use Razorpay\Api\Api;
use yii\helpers\VarDumper;

class UserController extends RestController
{

    /**
     * @return \yii\web\Response
     */
    // User Registration 
    public function actionRegister()
    {
        $dataRequest['RegisterForm'] = Yii::$app->request->getBodyParams();

        $model = new RegisterForm();

        if ($model->load($dataRequest)) {

            if ($user = $model->register()) {
                return $this->apiSuccess();
            } else {
                if ($dataRequest['RegisterForm']['username'] == null) {
                    return $this->apiValidate($model->getErrors('username')[0]);
                } elseif ($dataRequest['RegisterForm']['email'] == null) {
                    return $this->apiValidate($model->errors['email'][0]);
                } elseif ($dataRequest['RegisterForm']['phone_no'] == null) {
                    return $this->apiValidate($model->errors['phone_no'][0]);
                } elseif ($dataRequest['RegisterForm']['password'] == null) {
                    return $this->apiValidate($model->errors['password'][0]);
                } elseif (strlen($dataRequest['RegisterForm']['password']) < 8) {
                    return $this->apiValidate($model->errors['password'][0]);
                } else {
                    return $this->apiValidate($model->getErrors('email')[0]);
                }
            }
        }
        return $this->apiValidate($model->getErrors('username'));
    }
    // User Login
    public function actionLogin()
    {

        $dataRequest['LoginForm'] = Yii::$app->request->post();
        $model = new LoginForm();
        if ($model->load($dataRequest) && ($result = $model->login())) {

            //generation jwt auth token for the logged in user
            $accesstocken = JwtCreator::createToken(Yii::$app->user->id);

            $response = [
                "userid" => Yii::$app->user->id,
                "name" => Yii::$app->user->identity->username,
                "email" => Yii::$app->user->identity->email,
                "phone_no" => (string)Yii::$app->user->identity->phone_no,
                "token" => $accesstocken,
                "emailVerify" => true,
                "message" => "Logged in as " . Yii::$app->user->identity->email,
            ];

            return $this->apiItem($response);
        } else {

            $user = User::findByEmail($dataRequest['LoginForm']['username']);

            if ($dataRequest['LoginForm']['username'] == null) {
                return $this->apiValidate($model->errors['username'][0]);
            } elseif ($user == false) {
                return $this->apiValidate('Invalid email');
            } elseif ($dataRequest['LoginForm']['password'] == null) {
                return $this->apiValidate($model->errors['password'][0]);
            } else {
                return $this->apiValidate('Password is not valid');
            }
        }

        return $this->apiValidate('Error');
    }

    /**
     * @return \yii\web\Response
     */

    //User Profile details
    public function actionProfile($user_id)
    {
        $sectionArr = null;
        // $user = Yii::$app->user->identity;

        $userModel = new User();
        $userDetail = $userModel->getBasicDetails($user_id);

        $address = UserAddress::find()->select('address, city, zipcode')->where(['id' => $user_id])->one();
        $cartItems = CartItems::getItemsForUser($user_id);
        $cartItems = $this->actionCartDetails($user_id);

        $data = [
            "basic_info" => $userDetail,
            "address_info" => $address,
            "cart_items" => $cartItems,
        ];

        return $this->apiItem($data);
    }

    // User Add Address

    public function actionAddress()
    {

        $dataRequest['UserAddress'] = Yii::$app->request->getBodyParams();
        $model = UserAddress::find()->where(['user_id' => $dataRequest['UserAddress']['user_id']])->one();

        if ($model == null) {
            $model = new UserAddress;
            $model->state = 'Karnataka';
        }

        if ($model->load($dataRequest) && $model->save()) {
            return $this->apiSuccess();
        } else {
            if ($dataRequest['UserAddress']['user_id'] == null) {
                return $this->apiValidate($model->getErrors('user_id')[0]);
            } elseif ($dataRequest['UserAddress']['address'] == null) {
                return $this->apiValidate($model->errors['address'][0]);
            } elseif ($dataRequest['UserAddress']['city'] == null) {
                return $this->apiValidate($model->errors['city'][0]);
            } elseif ($dataRequest['UserAddress']['zipcode'] == null) {
                return $this->apiValidate($model->errors['zipcode'][0]);
            }
            // elseif ($dataRequest['UserAddress']['zipcode'] != '572213') {
            //     return $this->apiValidate('Sorry, the delivery is available only on 572213 pin number.');
            // } 
            else {
                return $this->apiValidate($model->getErrors('address')[0]);
            }
        }

        return $this->apiValidate($model->getErrors('address')[0]);
    }

    // Add item to the cart
    public function actionAddToCart()
    {
        if (!Yii::$app->request->getBodyParams()) {
            return $this->apiValidate('Please select item');
        }

        $dataRequest['cart'] = Yii::$app->request->getBodyParams();

        $productId = $dataRequest['cart']['product_id'];
        $userId = $dataRequest['cart']['user_id'];

        $product = Product::find()->where(['id' => $productId, 'status' => 1])->one();

        if (!$product) {
            return $this->apiValidate('Item not found');
        } else {
            $cartItem = CartItems::find()->where(['customer_id' => $userId, 'product_id' => $productId])->one();
            if ($cartItem) {
                $cartItem->quantity++;
            } else {
                $cartItem = new CartItems();
                $cartItem->product_id = $productId;
                $cartItem->customer_id = $userId;
                $cartItem->quantity = 1;
            }
            if ($cartItem->save()) {
                return $this->apiSuccess('Item added to the cart');
            } else {
                return $this->apiValidate($cartItem->errors);
            }
        }
    }
    // cart list
    public function actionCartDetails($user_id)
    {

        $cartItems = CartItems::getItemsForUser($user_id);
        $productQuantity = CartItems::getTotalQuantityForUser($user_id);
        $totalPrice = CartItems::getTotalPriceForUser($user_id);
        $totalDeliveryDays = CartItems::totalDeliveryDays($user_id);

        $data = [
            'items' => $cartItems,
            'productQuantity' => $productQuantity,
            'totalPrice' => $totalPrice,
            'totalDeliveryDays' => $totalDeliveryDays
        ];

        return $data;
    }

    // Update Cart

    // public function actionUpdateCart()
    // {

    //     $cartJsonItems = json_decode(file_get_contents("php://input"), true);

    //     if (!$cartJsonItems) {
    //         return $this->apiValidate('Please select item');
    //     }

    //     $cartArrayItems = (array)$cartJsonItems; 

    //     $index = 0;
    //     foreach ($cartJsonItems as $cartArrayItem) {

    //         $cart_id = $cartArrayItem[$index]['cart_id'];
    //         $user_id = $cartArrayItem[$index]['user_id'];
    //         $selected_days = $cartArrayItem[$index]['selected_days'];
    //         $quantity = $cartArrayItem[$index]['quantity'];

    //         $CartItems = CartItems::find()->where(['customer_id' => $user_id, 'id' => $cart_id])->one();
    //         $CartItems->delivery_days = $selected_days;
    //         $CartItems->quantity = $quantity;

    //         if ($CartItems->save()) {

    //             $message = 'Cart updated';
    //             return $this->apiSuccess($message);
    //         } else {
    //             return $this->apiValidate($CartItems->errors);
    //         }
    //     }
    // }
    public function actionUpdateCartItem()
    {
        if (!Yii::$app->request->getBodyParams()) {
            return $this->apiValidate('Please select item');
        }
        $dataRequest['cart'] = Yii::$app->request->getBodyParams();

        $userId = $dataRequest['cart']['user_id'];
        $cartId = $dataRequest['cart']['cart_id'];
        $selectedDates = $dataRequest['cart']['selected_days'];
        $quantity =  $dataRequest['cart']['quantity'];

        if ($dataRequest['cart']['user_id'] == null) {
            return $this->apiValidate('Error');
        }
        if ($dataRequest['cart']['cart_id'] == null) {
            return $this->apiValidate('Error');
        }
        if ($dataRequest['cart']['selected_days'] == null) {
            return $this->apiValidate('Delivery date cannot be blank.');
        }
        if ($dataRequest['cart']['quantity'] == null) {
            return $this->apiValidate('Quantity cannot be blank.');
        }
        $cartItem = CartItems::find()->where(['id' => $cartId, 'customer_id' => $userId])->one();
        if ($cartItem == null) {
            return $this->apiValidate('Invalid cart.');
        }

        $tagArray = ($selectedDates != '') ? explode(",", $selectedDates) : NULL;
        if ($tagArray)
            $selectedDatesCount = count($tagArray);
        else
            $selectedDatesCount = 0;


        $cartItem->delivery_days = $selectedDates;
        $cartItem->total_delivery_days = $selectedDatesCount;
        $cartItem->quantity = $quantity;
        $product = new Product();

        if ($cartItem->save()) {

            $productId = $cartItem->product_id;
            $totalQuantity = CartItems::getTotalQuantityForUser($userId);
            $totalDeliveryDays = CartItems::totalDeliveryDays($userId);
            $totalPrice = CartItems::getTotalPriceForUser($userId);
            $totalItemDelivery = CartItems::itemDeliveryDays($cartId);
            $totalItemPrice = CartItems::getTotalPriceForItemForUser($productId, $userId);

            $total_price = 0;
            $unit_price = 100;
            $data = [
                'ItemUnitPrice' => $product->getPrice($cartItem->product_id),
                'totalItemQnty' => $quantity,
                'itemDelivery' => $totalItemDelivery,
                'ItemDeliveryDays' => $cartItem->delivery_days,
                'totalItemPrice' => $totalItemPrice,
                'totalCartQuantity' => $totalQuantity,
                'totalCartDelivery' => $totalDeliveryDays,
                'totalCartPrice' => $totalPrice,
            ];

            $message = 'Cart updated';

            return $this->apiItem($data, $message);
        } else {
            return $this->apiValidate('Error');
        }
    }
    public function actionPlaceOrder()
    {
        if (!Yii::$app->request->getBodyParams()) {
            return $this->apiValidate('Please select order');
        }
        $dataRequest['order'] = Yii::$app->request->getBodyParams();

        $userId = $dataRequest['order']['user_id'];

        if ($dataRequest['order']['user_id'] == null) {
            return $this->apiValidate('Error');
        }

        if ($dataRequest['order']['payment_type'] == null) {
            return $this->apiValidate('Select payment method');
        }
        if ($dataRequest['order']['delivery_time'] == null) {
            return $this->apiValidate('Select delivery time');
        }

        // delivery instructions
        $order_notes = $dataRequest['order']['order_notes'];

        $payment_type = $dataRequest['order']['payment_type'];
        $delivery_time = $dataRequest['order']['delivery_time'];

        $receipt = '#FE' . time();
        $key_id = Yii::$app->params['razorPayKeyId'];
        $secret = Yii::$app->params['razorPaySecret'];

        $cartItems = CartItems::getItemsForUser($userId);
        $totalPrice = CartItems::getTotalPriceForUser($userId);
        $totalDeliveryDays = CartItems::totalDeliveryDays($userId);

        if ($totalDeliveryDays < 4) {
            return $this->apiValidate('Minimum 4 delivery is required');
        }

        $order = new Order();

        if ($payment_type == 1) {

            // RazorPay Order Creation
            $api = new Api($key_id, $secret);
            $orderData = [
                'receipt'         => $receipt,
                'amount'          => $totalPrice * 100, // 2000 rupees in paise
                'currency'        => 'INR',
            ];

            $razorpayOrder = $api->order->create($orderData);
            $razorpayOrderId = $razorpayOrder['id'];
            $order->payment_type = Order::PAYMENT_TYPE_ONLINE;
            $order->transaction_id = $receipt;
        } else {
            $order->payment_type = Order::PAYMENT_TYPE_COD;
            $razorpayOrderId = '';
        }
        $order->firstname = Yii::$app->user->identity->username;
        $order->delivery_timing = $delivery_time;
        $order->total_delivery = $totalDeliveryDays;
        $order->total_price = $totalPrice;
        $order->status = Order::STATUS_DRAFT;
        $order->transaction_id = null;
        $order->razorpay_order_id = $razorpayOrderId;
        $order->created_at = time();
        $order->created_by = $userId;
        $order->order_notes = $order_notes;
        $transaction = Yii::$app->db->beginTransaction();

        if ($order->save() && $order->saveOrderItems($userId)) {

            $transaction->commit();
            CartItems::clearCartItems($userId);

            $data = Order::find()->select('id, payment_type,razorpay_order_id')->where(['id' => $order->id])->one();
            $message = 'Order placed';
            return $this->apiItem($data, $message);
        } else {
            // if ($dataRequest['order']['user_id'] == null) {
            //     return $this->apiValidate('Error');
            // } elseif ($dataRequest['order']['delivery_time'] == null) {
            //     return $this->apiValidate($order->errors['delivery_time'][0]);
            // } elseif ($dataRequest['order']['payment_type'] == null) {
            //     return $this->apiValidate($order->errors['payment_type'][0]);
            // } else {
            //     return $this->apiValidate('Error');
            // }
            return $this->apiValidate('Error');
        }
    }
    // Offline Checkout

    // public function actionCheckoutOffline()
    // {
    //     $dataRequest['order'] = Yii::$app->request->getBodyParams();

    //     $order_id = $dataRequest['order']['order_id'];
    //     //$delivery_time = $dataRequest['order']['delivery_time'];

    //     $order = Order::find()->where(['id' => $order_id])->andWhere(['is_active' => true])->one();
    //     if (!$order) {
    //         return $this->apiValidate('order not found');
    //     }

    //     $order->firstname = Yii::$app->user->identity->firstname;
    //     $order->lastname = Yii::$app->user->identity->lastname;
    //     $order->email = Yii::$app->user->identity->email;
    //     $order->status = Order::STATUS_DRAFT;

    //     if ($order->save()) {
    //         return $this->apiSuccess();
    //     } else {
    //         return $this->apiValidate($order->errors);
    //     }
    // }
    public function actionUpdatePaymentDetails()
    {

        $dataRequest['checkout'] = Yii::$app->request->getBodyParams();

        $orderId = $dataRequest['checkout']['order_id'];

        $where = ['id' => $orderId, 'status' => Order::STATUS_DRAFT];

        $order = Order::findOne($where);
        if (!$order) {
            return $this->apiValidate('order not found');
        }

        if ($dataRequest['checkout']['razorpay_order_id'] == null) {
            return $this->apiValidate('Error');
        }

        if ($dataRequest['checkout']['razorpay_payment_id'] == null) {
            return $this->apiValidate('Error');
        }
        if ($dataRequest['checkout']['razorpay_signature'] == null) {
            return $this->apiValidate('Error');
        }

        // $delivery_time = $dataRequest['checkout']['delivery_time'];
        $razorpayOrderId = $dataRequest['checkout']['razorpay_order_id'];
        $razorpayPaymentId = $dataRequest['checkout']['razorpay_payment_id'];
        $razorpaySignature = $dataRequest['checkout']['razorpay_signature'];

        $exists = Order::find()->andWhere(['razorpay_payment_id' => $razorpayPaymentId])->exists();
        if ($exists) {
            return $this->apiValidate('something went wrong');
        }

        $order->firstname = Yii::$app->user->identity->username;
        //$order->lastname = Yii::$app->user->identity->lastname;
        // $order->delivery_timing = $delivery_time;
        $order->email = Yii::$app->user->identity->email;
        $order->status = Order::STATUS_PAID;
        $order->razorpay_payment_id = $razorpayPaymentId;
        $order->razorpay_signature = $razorpaySignature;

        if ($order->save()) {
            // if (!$order->sendEmailToVendor()) {
            //     return $this->apiValidate('Email to the vendor is not sent');
            // }
            // if (!$order->sendEmailToCustomer()) {
            //     return $this->apiValidate('Email to the customer is not sent');
            // }

            return $this->apiSuccess();
        } else {
            return $this->apiValidate($order->errors);
        }
    }
    // Get User Active Order List
    public function actionOrderHistory($user_id)
    {
        $orderLists = Order::find()->where(['created_by' => $user_id])->orderBy(['id' => SORT_DESC])->all();

        foreach ($orderLists as $orderList) {
            //$order['items'] = OrderItem::getOrderItems($orderList['id']);
            $order['order_id'] = $orderList['id'];
            $order['total_delivery'] = $orderList['total_delivery'];
            $order['total_price'] = $orderList['total_price'];
            $order['order_status'] = $orderList->orderStatus($orderList['status']);
            $order['payment_type'] = $orderList->PaymentType($orderList['payment_type']);
            $order['created_at'] = Yii::$app->formatter->asDateTime($orderList['created_at'], Yii::$app->params['apidateFormat']);

            if ($orderList['delivered_at'] == null)
                $order['delivered_at'] = null;
            else
                $order['delivered_at'] = Yii::$app->formatter->asDateTime($orderList['delivered_at'], Yii::$app->params['apidateFormat']);

            $sectionArr[] = $order;
        }
        // $data = [
        //     "order_details" => $sectionArr
        // ];

        return $this->apiItem($sectionArr);
    }
    public function actionOrderDetails($order_id)
    {
        $order = new Order();
        $OrderItems = OrderItem::getOrderItems($order_id);

        if (!$OrderItems) {
            return $this->apiValidate('order not found');
        }

        $totalItems = $order->getItemsQuantity($order_id);
        $totalDelivery = $order->getItemsDelivery($order_id);
        $totalPrice = $order->getTotalPrice($order_id);
        $paymentType = $order->getPaymentMethod($order_id);

        $data = [
            'order_items' => $OrderItems,
            'total_items' => $totalItems,
            'total_delivery' => $totalDelivery,
            'total_price' => $totalPrice,
            'payment_method' => $paymentType,
        ];
        return $this->apiItem($data);
    }
    // public function actionUpdatePaymentDetails()
    // {
    //     if (!Yii::$app->request->getBodyParams()) {
    //         return $this->apiValidate('Something went wrong');
    //     }

    //     $dataRequest['order'] = Yii::$app->request->getBodyParams();

    //     $orderId = $dataRequest['order']['order_id'];
    //     $razorpayPaymentId = $dataRequest['order']['razorpay_payment_id'];
    //     $razorpaySignature = $dataRequest['order']['razorpay_signature'];

    //     $where = ['id' => $orderId, 'status' => Order::STATUS_DRAFT];

    //     $order = Order::findOne($where);

    //     if (!$order) {
    //         return $this->apiValidate('Please select order');
    //     }

    //     $exists = Order::find()->andWhere(['razorpay_payment_id' => $razorpayPaymentId])->exists();
    //     if ($exists) {
    //         return $this->apiValidate('Please select valid order');
    //     }

    //     $order->firstname = Yii::$app->user->identity->firstname;
    //     $order->lastname = Yii::$app->user->identity->lastname;
    //     $order->email = Yii::$app->user->identity->email;
    //     $order->status = Order::STATUS_PAID;
    //     $order->razorpay_payment_id = $razorpayPaymentId;
    //     $order->razorpay_signature = $razorpaySignature;

    //     if ($order->save()) {
    //         // $message = 'Cart updated';
    //         return $this->apiSuccess();
    //     } else {
    //         return $this->apiValidate($order->errors);
    //     }
    // }

    public function actionProfileUpdate()
    {
        if (!Yii::$app->request->getBodyParams()) {
            return $this->apiValidate('Something went wrong');
        }

        $dataRequest['profile'] = Yii::$app->request->getBodyParams();

        $user_id = $dataRequest['profile']['user_id'];
        $firstname = $dataRequest['profile']['firstname'];
        $lastname = $dataRequest['profile']['lastname'];
        $username = $dataRequest['profile']['username'];
        $email = $dataRequest['profile']['email'];
        $phone_no = $dataRequest['profile']['phone_no'];
        $password = $dataRequest['profile']['password'];

        $user = User::find()->where(['id' => $user_id])->one();
        $now = new DateTime();
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->username = $username;
        $user->email = $email;
        $user->phone_no = $phone_no;
        $user->updated_at = $now->format('Y-m-d H:i:s');
        $user->setPassword($password);
        $user->generateAuthKey();
        if ($user->save()) {
            return $this->apiSuccess();
        } else {
            return $this->apiValidate($user->errors);
        }
    }
}
