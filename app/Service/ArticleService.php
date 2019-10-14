<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 文章服务层
 */
class ArticleService
{
    /**
     * 获取文章列表.
     *
     * @param $params
     *
     * @return array
     */
    public function articleList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'a.*' : $params['field'];
        $m = isset($params['m']) ? (int) ($params['m']) : 1;
        $n = isset($params['n']) ? (int) ($params['n']) : 10;

        $data = Db::table('article')->join('article_category', 'article.article_category_id', '=', 'article_category.id')->orderByRaw($field)->where($where)->orderByRaw('article.id desc')->limit($n)->offset(($m - 1) * $n)->get();
        if (! empty($data)) {
//            $common_is_enable_tips = lang('common_is_enable_tips');
            foreach ($data as &$v) {
                // url
//                $v['url'] = MyUrl('index/article/index', ['id' => $v['id']]);

                // 分类名称
                if (isset($v->article_category_id)) {
                    $v->article_category_name = Db::table('article_category')->where(['id' => $v->article_category_id])->value('name');
                }

                // 是否启用
//                if (isset($v->is_enable)) {
//                    $v->is_enable_text = $common_is_enable_tips[$v->is_enable]['name'];
//                }

                // 内容
                if (isset($v->content)) {
                    $v->content = ResourcesService::ContentStaticReplace($v->content, 'get');
                }
            }
        }//end if

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 文章总数.
     *
     * @param $where
     *
     * @return int
     */
    public function articleTotal($where)
    {
        return Db::table('article')->join('article_category', 'article.article_category_id', '=', ' article_category.id')->where($where)->count();
    }

    /**
     * 列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleListWhere($params = [])
    {
        $where = [];

        if (! empty($params['keywords'])) {
            $where[] = [
                'article.title',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] === 1) {
            // 等值
            if (isset($params['is_enable']) && $params['is_enable'] > -1) {
                $where[] = [
                    'article.is_enable',
                    '=',
                    (int) ($params['is_enable']),
                ];
            }

            if (isset($params['article_category_id']) && $params['article_category_id'] > -1) {
                $where[] = [
                    'article.article_category_id',
                    '=',
                    (int) ($params['article_category_id']),
                ];
            }

            if (isset($params['is_home_recommended']) && $params['is_home_recommended'] > -1) {
                $where[] = [
                    'article.is_home_recommended',
                    '=',
                    (int) ($params['is_home_recommended']),
                ];
            }

            if (isset($params['access_count']) && $params['access_count'] > -1) {
                $where[] = [
                    'article.access_count',
                    '>',
                    (int) ($params['access_count']),
                ];
            }

            if (! empty($params['time_start'])) {
                $where[] = [
                    'article.add_time',
                    '>',
                    strtotime($params['time_start']),
                ];
            }

            if (! empty($params['time_end'])) {
                $where[] = [
                    'article.add_time',
                    '<',
                    strtotime($params['time_end']),
                ];
            }
        }

        return $where;
    }

    /**
     * 文章保存.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type' => 'length',
                'key_name' => 'title',
                'checked_data' => '2,60',
                'error_msg' => '标题长度 2~60 个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'article_category_id',
                'error_msg' => '请选择文章分类',
            ],
            [
                'checked_type' => 'fun',
                'key_name' => 'jump_url',
                'checked_data' => 'CheckUrl',
                'is_checked' => 1,
                'error_msg' => '跳转url地址格式有误',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'content',
                'checked_data' => '10,105000',
                'error_msg' => '内容 10~105000 个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'seo_title',
                'checked_data' => '100',
                'is_checked' => 1,
                'error_msg' => 'SEO标题格式 最多100个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'seo_keywords',
                'checked_data' => '130',
                'is_checked' => 1,
                'error_msg' => 'SEO关键字格式 最多130个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'seo_desc',
                'checked_data' => '230',
                'is_checked' => 1,
                'error_msg' => 'SEO描述格式 最多230个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 编辑器内容
        $content = isset($params['content']) ? htmlspecialchars_decode($params['content']) : '';

        // 数据
        $image = $this->matchContentImage($content);
        $data = [
            'title' => $params['title'],
            'title_color' => empty($params['title_color']) ? '' : $params['title_color'],
            'article_category_id' => (int) ($params['article_category_id']),
            'jump_url' => empty($params['jump_url']) ? '' : $params['jump_url'],
            'content' => ResourcesService::ContentStaticReplace($content, 'add'),
            'image' => empty($image) ? '' : json_encode($image),
            'image_count' => \count($image),
            'is_enable' => isset($params['is_enable']) ? (int) ($params['is_enable']) : 0,
            'is_home_recommended' => isset($params['is_home_recommended']) ? (int) ($params['is_home_recommended']) : 0,
            'seo_title' => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords' => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc' => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('article')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('article')->where(['id' => (int) ($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    /**
     * 获取分类和所有文章.
     *
     * @return array
     */
    public function articleCategoryListContent()
    {
        $data = Db::table('article_category')->selectRaw('id,name')->where(['is_enable' => 1])->orderByRaw('id asc, sort asc')->get();
        if (! empty($data)) {
            foreach ($data as &$v) {
                $items = Db::table('article')->selectRaw('id,title,title_color')->where(['article_category_id' => $v['id'], 'is_enable' => 1])->get();
//                if (! empty($items)) {
//                    foreach ($items as &$vs) {
//                        // url
//                        $vs['url'] = MyUrl('index/article/index', ['id' => $vs['id']]);
//                    }
//                }

                $v['items'] = $items;
            }
        }

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 文章访问统计加1.
     *
     * @param array $params
     *
     * @return bool
     */
    public function articleAccessCountInc($params = [])
    {
        if (! empty($params['id'])) {
            return Db::table('article')->where(['id' => (int) ($params['id'])])->increment('access_count') === 1;
        }

        return false;
    }

    /**
     * 文章分类.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleCategoryList($params = [])
    {
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc' : trim($params['order_by']);

        $data = Db::table('article_category')->where(['is_enable' => 1])->selectRaw($field)->orderByRaw($order_by)->get();

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'id',
                'error_msg' => '操作id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 删除操作
        if (Db::table('article')->where(['id' => $params['id']])->delete() === 1) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 状态更新.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'id',
                'error_msg' => '操作id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'field',
                'error_msg' => '操作字段有误',
            ],
            [
                'checked_type' => 'in',
                'key_name' => 'state',
                'checked_data' => [
                    0,
                    1,
                ],
                'error_msg' => '状态有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据更新
        if (Db::table('article')->where(['id' => (int) ($params['id'])])->update([$params['field'] => (int) ($params['state'])]) === 1) {
            return dataReturn('编辑成功');
        }

        return dataReturn('编辑失败或数据未改变', -100);
    }

    /**
     * 获取文章分类节点数据.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleCategoryNodeSon($params = [])
    {
        // id
        $id = isset($params['id']) ? (int) ($params['id']) : 0;

        // 获取数据
        $field = '*';
        $data = Db::table('article_category')->selectRaw($field)->where(['pid' => $id])->orderByRaw('sort asc')->get();
        if (! empty($data)) {
            foreach ($data as &$v) {
                $v->is_son = (Db::table('article_category')->where(['pid' => $v->id])->count() > 0) ? 'ok' : 'no';
//                $v->ajax_url = MyUrl('admin/articlecategory/getnodeson', ['id' => $v->id]);
//                $v->delete_url = MyUrl('admin/articlecategory/delete');
                $v->json = json_encode($v);
            }

            return dataReturn('操作成功', 0, $data);
        }

        return dataReturn('没有相关数据', -100);
    }

    /**
     * 文章分类保存.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleCategorySave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'length',
                'key_name' => 'name',
                'checked_data' => '2,16',
                'error_msg' => '名称格式 2~16 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据
        $data = [
            'name' => $params['name'],
            'pid' => isset($params['pid']) ? (int) ($params['pid']) : 0,
            'sort' => isset($params['sort']) ? (int) ($params['sort']) : 0,
            'is_enable' => isset($params['is_enable']) ? (int) ($params['is_enable']) : 0,
        ];

        // 添加
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('article_category')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('article_category')->where(['id' => (int) ($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    /**
     * 文章分类删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function articleCategoryDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'id',
                'error_msg' => '删除数据id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'admin',
                'error_msg' => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 开始删除
        if (Db::table('article_category')->where(['id' => (int) ($params['id'])])->delete() === 1) {
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败', -100);
    }

    /**
     * 正则匹配文章图片.
     *
     * @param string $content [文章内容]
     *
     * @return array [文章图片数组（一维）]
     */
    private function matchContentImage(string $content)
    {
        if (! empty($content)) {
            $pattern = '/<img.*?src=[\'|\"](\/\/upload\/article\/image\/.*?[\.gif|\.jpg|\.jpeg|\.png|\.bmp])[\'|\"].*?[\/]?>/';
            preg_match_all($pattern, $content, $match);

            return empty($match[1]) ? [] : $match[1];
        }

        return [];
    }
}
