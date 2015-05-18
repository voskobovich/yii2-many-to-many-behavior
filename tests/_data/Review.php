<?php

namespace data;

use Yii;

/**
 * This is the model class for table "review".
 *
 * @property integer $id
 * @property integer $book_id
 * @property string $comment
 * @property integer $rating
 */
class Review extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'review';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['book_id', 'rating'], 'integer'],
            [['comment', 'rating'], 'required'],
            [['comment'], 'string', 'max' => 150]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'book_id' => 'Book ID',
            'comment' => 'Comment',
            'rating' => 'Rating',
        ];
    }
}
