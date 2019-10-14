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

use App\Model\Answer;
use Hyperf\DbConnection\Db;

/**
 * 问答/留言服务层
 */
class AnswerService
{
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    //end __construct()

    /**
     * @param array $where
     *
     * @return int
     */
    public function answerTotal($where = [])
    {
        return Answer::query()->where($where)->count();
    }

    //end answerTotal()

    public function answerList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? (int) ($params['m']) : 0;
        $n = isset($params['n']) ? (int) ($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        $data = Answer::query()->where($where)->limit($n)->offset($n * $m)->orderByRaw($order_by)->selectRaw($field)->get();
        if (! empty($data)) {
            /*
             * @var Answer $item
             */
            foreach ($data as $item) {
                if (isset($item->user_id)) {
                    $item->user = $this->userService->getUserViewInfo($item->user_id);
                }
            }
        }

        return $data;
    }

    //end answerList()

    /**
     * 列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public static function answerListWhere($params = [])
    {
        $where = [];

        // id
        if (! empty($params['id'])) {
            $where[] = [
                'id',
                '=',
                $params['id'],
            ];
        }

        // 用户id
        if (! empty($params['user'])) {
            $where[] = [
                'user_id',
                '=',
                $params['user']['id'],
            ];
        }

        if (! empty($params['keywords'])) {
            $where[] = [
                'name|tel|title|content',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] === 1) {
            // 等值
            if (isset($params['is_show']) && $params['is_show'] > -1) {
                $where[] = [
                    'is_show',
                    '=',
                    (int) ($params['is_show']),
                ];
            }

            if (isset($params['is_reply']) && $params['is_reply'] > -1) {
                $where[] = [
                    'is_reply',
                    '=',
                    (int) ($params['is_reply']),
                ];
            }

            if (! empty($params['time_start'])) {
                $where[] = [
                    'add_time',
                    '>',
                    strtotime($params['time_start']),
                ];
            }

            if (! empty($params['time_end'])) {
                $where[] = [
                    'add_time',
                    '<',
                    strtotime($params['time_end']),
                ];
            }
        }//end if

        return $where;
    }

    //end answerListWhere()

    /**
     * 用户留言保存.
     *
     * @param array $params
     *
     * @return array
     */
    public static function answerSave($params = [])
    {
        // 参数校验
        $p = [
            [
                'checked_type' => 'length',
                'key_name' => 'name',
                'checked_data' => '30',
                'is_checked' => 1,
                'error_msg' => '联系人最多30个字符',
            ],
            [
                'checked_type' => 'isset',
                'key_name' => 'tel',
                'error_msg' => '联系电话有误',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'title',
                'checked_data' => '60',
                'is_checked' => 1,
                'error_msg' => '标题最多60个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'content',
                'error_msg' => '详细内容不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'content',
                'checked_data' => '1000',
                'error_msg' => '详细内容格式 2~1000 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 开始操作
        $data = [
            'user_id' => isset($params['user']['id']) ? (int) ($params['user']['id']) : (isset($params['user_id']) ? (int) ($params['user_id']) : 0),
            'name' => isset($params['name']) ? $params['name'] : '',
            'tel' => isset($params['tel']) ? $params['tel'] : '',
            'title' => isset($params['title']) ? $params['title'] : '',
            'content' => $params['content'],
            'reply' => isset($params['reply']) ? $params['reply'] : '',
            'access_count' => isset($params['access_count']) ? (int) ($params['access_count']) : 0,
            'is_reply' => isset($params['is_reply']) ? (int) ($params['is_reply']) : 0,
            'is_show' => isset($params['is_show']) ? (int) ($params['is_show']) : 0,
            'add_time' => time(),
        ];

        // 回复时间
        $data['reply_time'] = (isset($data['is_reply']) && $data['is_reply'] === 1) ? time() : 0;

        // 不存在添加，则更新
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('answer')->insertGetId($data) > 0) {
                return dataReturn('提交成功', 0);
            }

            return dataReturn('提交失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('answer')->where(['id' => (int) ($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    //end answerSave()

    /**
     * @param array $params
     *
     * @return array
     */
    public function answerDelete($params = [])
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
                'key_name' => 'user_type',
                'error_msg' => '用户类型有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 条件
        $where = [
            'id' => (int) ($params['id']),
        ];

        // 用户类型
        if ($params['user_type'] === 'user') {
            if (empty($params['user'])) {
                return dataReturn('用户信息有误', -1);
            }

            $where['user_id'] = $params['user']['id'];
        }

        // 开始删除
        if (Db::table('answer')->where($where)->delete() === 1) {
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败或数据不存在', -1);
    }

    //end answerDelete()

    /**
     * @param array $params
     *
     * @return array
     */
    public function answerReply($params = [])
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
                'key_name' => 'reply',
                'error_msg' => '回复内容不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name' => 'reply',
                'checked_data' => '2,1000',
                'error_msg' => '回复内容格式 2~1000 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 条件
        $where = [
            'id' => (int) ($params['id']),
        ];

        // 问答是否存在
        $temp = Db::table('answer')->where($where)->first(['id']);
        if (empty($temp)) {
            return dataReturn('资源不存在或已被删除', -2);
        }

        // 更新问答
        $data = [
            'reply' => $params['reply'],
            'is_reply' => 1,
            'reply_time' => time(),
            'upd_time' => time(),
        ];
        if (Db::table('answer')->where($where)->update($data) === 1) {
            return dataReturn('操作成功');
        }

        return dataReturn('操作失败', -100);
    }

    //end answerReply()

    /**
     * 状态更新.
     *
     * @param array $params
     *
     * @return array
     */
    public static function answerStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'id',
                'error_msg' => '操作id有误',
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
        if (Db::table('answer')->where(['id' => (int) ($params['id'])])->update(['is_show' => (int) ($params['state'])]) === 1) {
            return dataReturn('编辑成功');
        }

        return dataReturn('编辑失败或数据未改变', -100);
    }

    //end answerStatusUpdate()

    /**
     * 访问统计加1.
     *
     * @param array $params
     *
     * @return bool
     */
    public static function answerAccessCountInc($params = [])
    {
        if (! empty($params['answer_id'])) {
            return Db::table('answer')->where(['id' => (int) ($params['answer_id'])])->increment('access_count') === 1;
        }

        return false;
    }

    //end answerAccessCountInc()
}//end class
