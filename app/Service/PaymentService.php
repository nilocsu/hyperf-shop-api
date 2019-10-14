<?php


namespace App\Service;

use Hyperf\DbConnection\Db;
use Yurun\PaySDK\Alipay\Params\PublicParams;
use Yurun\PaySDK\Alipay\SDK;

/**
 * todo  支付方式服务层
 * @author colin.
 * date 19-6-27 下午4:49
 */
class PaymentService
{
    public const common_platform_type = [
        'pc'      => ['value' => 'pc', 'name' => 'PC网站'],
        'h5'      => ['value' => 'h5', 'name' => 'H5手机网站'],
        'ios'     => ['value' => 'ios', 'name' => '苹果APP'],
        'android' => ['value' => 'android', 'name' => '安卓APP'],
        'alipay'  => ['value' => 'alipay', 'name' => '支付宝小程序'],
        'weixin'  => ['value' => 'weixin', 'name' => '微信小程序'],
        'baidu'   => ['value' => 'baidu', 'name' => '百度小程序'],
    ];

    /**
     * 数据解析
     * @param $data
     * @return array
     */
    private function dataAnalysis($data)
    {
        return [
            'name'           => isset($data['base']['name']) ? htmlentities($data['base']['name']) : '',
            'version'        => isset($data['base']['version']) ? htmlentities($data['base']['version']) : '',
            'apply_version'  => isset($data['base']['apply_version']) ? htmlentities($data['base']['apply_version']) : '',
            'desc'           => isset($data['base']['desc']) ? $data['base']['desc'] : '',
            'author'         => isset($data['base']['author']) ? htmlentities($data['base']['author']) : '',
            'author_url'     => isset($data['base']['author_url']) ? htmlentities($data['base']['author_url']) : '',
            'element'        => isset($data['element']) ? $data['element'] : [],
            'logo'           => '',
            'is_enable'      => 0,
            'is_open_user'   => 0,
            'is_install'     => 0,
            'apply_terminal' => empty($data['base']['apply_terminal']) ? array_column(PaymentService::common_platform_type,
                'value') : $data['base']['apply_terminal'],
            'config'         => '',
        ];
    }

    /**
     * 支付方式列表
     * @param array $params
     * @return $this
     */
    public static function paymentList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        if (isset($params['is_enable'])) {
            $where['is_enable'] = intval($params['is_enable']);
        }
        if (isset($params['is_open_user'])) {
            $where['is_open_user'] = intval($params['is_open_user']);
        }

        $data = Db::table('payment')->where($where)->selectRaw('id,logo,name,sort,payment,config,apply_terminal,apply_terminal,element,is_enable,is_open_user')->orderByRaw('sort asc')->get()->toArray();
        if (!empty($data) && is_array($data)) {
            foreach ($data as &$v) {
                $v['logo_old']       = $v['logo'];
                $v['logo']           = ResourcesService::attachmentPathViewHandle($v['logo']);
                $v['element']        = empty($v['element']) ? '' : json_decode($v['element'], true);
                $v['config']         = empty($v['config']) ? '' : json_decode($v['config'], true);
                $v['apply_terminal'] = empty($v['apply_terminal']) ? '' : json_decode($v['apply_terminal'], true);
            }
        }
        return $data;
    }

    /**
     * 获取支付方式列表
     * @param array $params
     * @return array
     */
    public function buyPaymentList($params = [])
    {
        $data = self::PaymentList($params);

        $result = [];
        if (!empty($data)) {
            foreach ($data as $v) {
                // 根据终端类型筛选
//                if(in_array(APPLICATION_CLIENT_TYPE, $v['apply_terminal']))
//                {
                $result[] = $v;
//                }
            }
        }
        return $result;
    }

    /**
     * 获取订单支付名称
     * @param int $order_id
     * @return mixed|null
     */
    public function orderPaymentName($order_id = 0)
    {
        return empty($order_id) ? null : Db::table('pay_log')->where(['order_id' => intval($order_id)])->value('payment_name');
    }

    /**
     * 数据更新
     * @param array $params
     * @return array
     */
    public function paymentUpdate($params = [])
    {
        // 请求类型
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,60',
                'error_msg'    => '名称长度 2~60 个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'apply_terminal',
                'error_msg'    => '至少选择一个适用终端',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'sort',
                'checked_data' => '3',
                'error_msg'    => '顺序 0~255 之间的数值',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 附件
        $data_fields = ['logo'];
        $attachment  = ResourcesService::attachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'           => $params['name'],
            'apply_terminal' => empty($params['apply_terminal']) ? '' : json_encode(explode(',',
                $params['apply_terminal'])),
            'logo'           => $attachment['data']['logo'],
            'config'         => json_encode(self::getPlugConfig($params)),
            'sort'           => intval($params['sort']),
            'is_enable'      => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'is_open_user'   => isset($params['is_open_user']) ? intval($params['is_open_user']) : 0,
        ];

        $data['upd_time'] = time();
        if (Db::table('Payment')->where(['id' => intval($params['id'])])->update($data)) {
            return dataReturn('编辑成功', 0);
        }
        return dataReturn('编辑失败', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    private function getPlugConfig($params = [])
    {
        $data = [];
        foreach ($params as $k => $v) {
            if (substr($k, 0, 8) == 'plugins_') {
                $data[substr($k, 8)] = $v;
            }
        }
        return $data;
    }

    /**
     * 状态更新
     * @param array $params
     * @return array
     */
    public function paymentStatusUpdate($params = [])
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
                'error_msg'    => '未指定操作字段',
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
        if (Db::table('payment')->where(['payment' => $params['id']])->update([
            $params['field'] => intval($params['state']),
            'upd_time'       => time(),
        ])) {
            return dataReturn('操作成功');
        }
        return dataReturn('操作失败', -100);
    }

    /**
     * @return PublicParams
     */
    public function aliPayCommon(){
        $param = new PublicParams();
        $param->appID = config('pay.app_id', '');
        $param->appPrivateKey = config('pay.private_key','');
        $param->appPublicKey = config('pay.private_key', '');
        return $param;
    }

    public function aliPay(){
        $pay = new SDK($this->aliPayCommon());

    }
}