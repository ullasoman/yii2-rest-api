<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "faq".
 *
 * @property int $id
 * @property string $faq_title
 * @property string $faq_content
 * @property int|null $is_active
 * @property string $created_at
 */
class Faq extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'faq';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['faq_title', 'faq_content'], 'required'],
            [['is_active'], 'integer'],
            [['created_at'], 'safe'],
            [['faq_title', 'faq_content'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'faq_title' => 'Faq Title',
            'faq_content' => 'Faq Content',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
        ];
    }
}
