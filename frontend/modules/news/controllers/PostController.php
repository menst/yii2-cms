<?php
/**
 * @link https://github.com/gromver/yii2-cmf.git#readme
 * @copyright Copyright (c) Gayazov Roman, 2014
 * @license https://github.com/gromver/yii2-cmf/blob/master/LICENSE
 * @package yii2-cmf
 * @version 1.0.0
 */

namespace gromver\cmf\frontend\modules\news\controllers;

use gromver\cmf\common\models\Category;
use gromver\cmf\common\models\Post;
use gromver\cmf\common\models\Table;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use Yii;
use Zelenin\yii\extensions\Rss\RssView;

/**
 * Class PostController
 * @package yii2-cmf
 * @author Gayazov Roman <gromver5@gmail.com>
 */
class PostController extends Controller
{
    public $defaultAction = 'view';

    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\HttpCache',
                'only' => ['rss'],
                'lastModified' => function ($action, $params) {
                        return Table::timestamp('{{%cms_post}}');
                    },
            ],
        ];
    }

    public function actionIndex($category_id = null, $tag_id = null)
    {
        return $this->render('index', [
            'categoryId' => $category_id,
            'tagId' => $tag_id
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->loadModel($id)
        ]);
    }

    public function actionDay($year, $month, $day, $category_id = null)
    {
        return $this->render('day', [
            'model' => $category_id ? $this->loadCategoryModel($category_id) : null,
            'year' => $year,
            'month' => $month,
            'day' => $day
        ]);
    }

    public function actionRss($category_id = null)
    {
        return RssView::widget([
            'dataProvider' => new ActiveDataProvider([
                    'query' => Post::find()->published()->category($category_id)->language(Yii::$app->language)->orderBy(['published_at' => SORT_DESC]),
                    'pagination' => [
                        'pageSize' => $this->module->rssPageSize
                    ],
                ]),
            'channel' => [
                'title' => Yii::$app->cmf->siteName,
                'link' => Url::toRoute(['', 'category_id' => $category_id], true),
                'description' => $category_id ? $this->loadCategoryModel($category_id)->title : Yii::t('gromver.cmf', 'All news'),
                'language' => Yii::$app->language
            ],
            'items' => [
                'title' => function ($model, $widget) {
                        /** @var $model \gromver\cmf\common\models\Post */
                        return $model->title;
                    },
                'description' => function ($model, $widget) {
                        /** @var $model \gromver\cmf\common\models\Post */
                        return $model->preview_text ? $model->preview_text : StringHelper::truncateWords(strip_tags($model->detail_text), 40);
                    },
                'link' => function ($model, $widget) {
                        /** @var $model \gromver\cmf\common\models\Post */
                        return Url::toRoute($model->getViewLink(), true);
                    },
                'author' => function ($model, $widget) {
                        /** @var $model \gromver\cmf\common\models\Post */
                        return $model->user->email . ' (' . $model->user->username . ')';
                    },
                'guid' => function ($model, $widget) {
                        /** @var $model \gromver\cmf\common\models\Post */
                        return Url::toRoute($model->getViewLink(), true) . ' ' . Yii::$app->formatter->asDatetime($model->updated_at, 'php:'.DATE_RSS);
                    },
                'pubDate' => function ($model, $widget) {
                        /** @var $model \gromver\cmf\common\models\Post */
                        return Yii::$app->formatter->asDatetime($model->published_at, 'php:'.DATE_RSS);
                    }
            ]
        ]);
    }

    public function loadModel($id)
    {
        if(!($model = Post::findOne($id))) {
            throw new NotFoundHttpException(Yii::t('gromver.cmf', 'The requested post does not exist..'));
        }

        return $model;
    }

    public function loadCategoryModel($id)
    {
        if(!($model = Category::findOne($id))) {
            throw new NotFoundHttpException(Yii::t('gromver.cmf', 'The requested category does not exist..'));
        }

        return $model;
    }
}
