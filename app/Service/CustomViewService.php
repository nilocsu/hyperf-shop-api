<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 自定义页面服务层
 * @author colin.
 * date 19-6-26 下午6:27
 */
class CustomViewService
{

    const common_is_enable_list = [
        0 => ['id' => 0, 'name' => '不启用'],
        1 => ['id' => 1, 'name' => '启用', 'checked' => true],
    ];

    /**
     * 获取自定义列表
     * @param array $params
     * @return array
     */
    public function customViewList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'id,title,content,is_header,is_footer,is_full_screen,access_count,is_enable' : $params['field'];
        $m     = isset($params['m']) ? intval($params['m']) : 0;
        $n     = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::table('custom_view')->selectRaw($field)->where($where)->orderByRaw('id desc')->limit($n)->offset($m * $n)->get();
        if (!empty($data)) {
            $common_is_enable_list = customViewService::common_is_enable_list;
            foreach ($data as &$v) {
                // 是否启用
                if (isset($v['is_enable'])) {
                    $v['is_enable_text'] = $common_is_enable_list[$v['is_enable']]['name'];
                }

                // 内容
                if (isset($v['content'])) {
                    $v['content'] = ResourcesService::contentStaticReplace($v['content'], 'get');
                }

                // 时间
                if (isset($v['add_time'])) {
                    $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                    $v['add_time_date'] = date('Y-m-d', $v['add_time']);
                }
                if (isset($v['upd_time'])) {
                    $v['upd_time_time'] = date('Y-m-d H:i:s', $v['upd_time']);
                    $v['upd_time_date'] = date('Y-m-d', $v['upd_time']);
                }
            }
        }
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * @param array $where
     * @return int
     */
    public function customViewTotal($where = [])
    {
        return Db::table('custom_view')->where($where)->count();
    }

    /**
     * @param array $params
     * @return array
     */
    public function customViewListWhere($params = [])
    {
        $where = [];

        // id
        if (!empty($params['id'])) {
            $where[] = ['id', '=', $params['id']];
        }

        if (!empty($params['keywords'])) {
            $where[] = ['title', 'like', '%' . $params['keywords'] . '%'];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['is_enable']) && $params['is_enable'] > -1) {
                $where[] = ['is_enable', '=', intval($params['is_enable'])];
            }
            if (isset($params['is_full_screen']) && $params['is_full_screen'] > -1) {
                $where[] = ['is_full_screen', '=', intval($params['is_full_screen'])];
            }
            if (isset($params['is_header']) && $params['is_header'] > -1) {
                $where[] = ['is_header', '=', intval($params['is_header'])];
            }
            if (isset($params['is_footer']) && $params['is_footer'] > -1) {
                $where[] = ['is_footer', '=', intval($params['is_footer'])];
            }

            if (!empty($params['time_start'])) {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if (!empty($params['time_end'])) {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * 自定义页面访问统计加1
     * @param array $params
     * @return bool
     */
    public function customViewAccessCountInc($params = [])
    {
        if (!empty($params['id'])) {
            return Db::table('custom_view')->where(['id' => intval($params['id'])])->increment('access_count') == 1;
        }
        return false;
    }

    /**
     * @param array $params
     * @return array
     */
    public function customViewSave($params = [])
    {
        // 请求类型
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'title',
                'checked_data' => '2,60',
                'error_msg'    => '标题长度 2~60 个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'content',
                'checked_data' => '50,105000',
                'error_msg'    => '内容长度最少 50~105000 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 编辑器内容
        $content = isset($params['content']) ? htmlspecialchars_decode($params['content']) : '';

        // 数据
        $image = $this->MatchContentImage($content);
        $data  = [
            'title'          => $params['title'],
            'content'        => ResourcesService::contentStaticReplace($content, 'add'),
            'image'          => empty($image) ? '' : json_encode($image),
            'image_count'    => count($image),
            'is_enable'      => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'is_header'      => isset($params['is_header']) ? intval($params['is_header']) : 0,
            'is_footer'      => isset($params['is_footer']) ? intval($params['is_footer']) : 0,
            'is_full_screen' => isset($params['is_full_screen']) ? intval($params['is_full_screen']) : 0,
        ];

        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('custom_view')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }
            return dataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if (Db::table('custom_view')->where(['id' => intval($params['id'])])->update($data)) {
                return dataReturn('编辑成功', 0);
            }
            return dataReturn('编辑失败', -100);
        }
    }

    /**
     * 正则匹配文章图片
     * @param $content
     * @return array
     */
    private function matchContentImage($content)
    {
        if (!empty($content)) {
            $pattern = '/<img.*?src=[\'|\"](\/\/upload\/customView\/image\/.*?[\.gif|\.jpg|\.jpeg|\.png|\.bmp])[\'|\"].*?[\/]?>/';
            preg_match_all($pattern, $content, $match);
            return empty($match[1]) ? [] : $match[1];
        }
        return [];
    }

    /**
     * @param array $params
     * @return array
     */
    public function customViewDelete($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 删除操作
        if (Db::table('custom_view')->where(['id' => $params['id']])->delete()) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public function customViewStatusUpdate($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'field',
                'error_msg'    => '字段有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'state',
                'checked_data' => [0, 1],
                'error_msg'    => '状态有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据更新
        if (Db::table('custom_view')->where(['id' => intval($params['id'])])->update([$params['field'] => intval($params['state'])])) {
            return dataReturn('编辑成功');
        }
        return dataReturn('编辑失败或数据未改变', -100);
    }

}