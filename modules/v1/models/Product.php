<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property int|null $cat_id
 * @property string $product_name
 * @property string|null $product_description
 * @property string $product_image
 * @property int $status 0 for Inactive, 1 for Active
 * @property string $created_at
 *
 * @property ProductCategory $cat
 * @property ProductQuantity[] $productQuantities
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $file;
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cat_id', 'status'], 'integer'],
            [['cat_id', 'product_name', 'product_image', 'product_price', 'status'], 'required'],
            [['product_description'], 'string'],
            [['file'], 'file'],
            [['file', 'created_at'], 'safe'],
            [['product_name', 'product_image'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file' => 'Product Image',
            'cat_id' => 'Category',
            'product_name' => 'Product Name',
            'product_description' => 'Product Description',
            'product_image' => 'Product Image',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }


    /**
     * {@inheritdoc}
     * @return \common\models\query\Product the active query used by this AR class.
     */
    public function published()
    {
        return $this->andWhere(['status' => 1]);
    }
    public function getPrice($productId)
    {
        $product = self::find()->select('product_price')->where(['status' => 1])->andWhere(['id' => $productId])->one();
        return $product->product_price;
    }
    public function getCount()
    {
        return self::find()->where(['status' => 1])->count();
    }
    public function getProduct()
    {
        return self::find()->where(['status' => 1])->all();
    }
    public function getImageUrl()
    {
        return self::formatImageUrl($this->product_image);
    }

    public static function formatImageUrl($imagePath)
    {
        if (Yii::$app->id == 'app-admin')
            $path = Yii::getAlias('@web');
        else
            $path = Yii::getAlias('@web') . '/admin';

        if ($imagePath) {
            return $path . '/' . $imagePath;
        }

        return $path . '/web/img/no_image_available.png';
    }

    /**
     * Get short version of the description
     *
     * @return string
     */
    public function getShortDescription()
    {
        return \yii\helpers\StringHelper::truncateWords(strip_tags($this->product_description), 30);
    }
}
