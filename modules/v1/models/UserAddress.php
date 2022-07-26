<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "user_addresses".
 *
 * @property int $id
 * @property int $user_id
 * @property string $address
 * @property string $city
 * @property string $state
 * @property string|null $zipcode
 *
 * @property User $user
 */
class UserAddress extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_addresses';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'address', 'city', 'state', 'zipcode'], 'required'],
            [['user_id'], 'integer'],
            [['address', 'city', 'state', 'zipcode'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'zipcode' => 'Zipcode',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
