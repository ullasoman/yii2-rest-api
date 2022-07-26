<?php

namespace app\modules\v1\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\v1\models\Product;

/**
 * ProductsSearch represents the model behind the search form of `app\models\Product`.
 */
class ProductsSearch extends Product
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'cat_id', 'status'], 'integer'],
            [['product_name', 'product_description', 'product_image', 'product_price', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Product::find()->select('id,product_name,product_description,product_image,product_price')->andFilterWhere(['status' => 1]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'cat_id' => $this->cat_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'product_name', $this->product_name])
            ->andFilterWhere(['like', 'product_description', $this->product_description])
            ->andFilterWhere(['like', 'product_image', $this->product_image]);

        return $dataProvider;
    }
}