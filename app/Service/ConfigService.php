<?php


namespace App\Service;


use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

/**
 * 配置服务层
 * @author colin.
 * date 19-6-26 下午6:25
 */
class ConfigService
{
    // 富文本,不实例化的字段
    public static $rich_text_list = [
        'home_footer_info',
        'common_email_currency_template',
        'home_email_user_reg',
        'home_email_user_forget_pwd',
        'home_email_user_email_binding',
        'home_site_close_reason',
        'common_agreement_userregister',
    ];

    // 附件字段列表
    public static $attachment_field_list = [
        'home_site_logo',
        'home_site_logo_wap',
        'home_site_desktop_icon',
        'common_customer_store_qrcode',
        'home_site_user_register_bg_images',
        'home_site_user_login_ad1_images',
        'home_site_user_login_ad2_images',
        'home_site_user_login_ad3_images',
        'home_site_user_forgetpwd_ad1_images',
        'home_site_user_forgetpwd_ad2_images',
        'home_site_user_forgetpwd_ad3_images',
    ];

    // 字符串转数组字段列表, 默认使用英文逗号处理 [ , ]
    public static $string_to_array_field_list = [
        'home_user_reg_state',
        'common_images_verify_rules',
    ];

    private static $cacheKey  = 'cache:common:key';

    /**
     * 配置列表，唯一标记作为key
     * @param array $params
     * @return \Hyperf\Utils\Collection
     */
    public function configList($params = [])
    {
        $field = isset($params['field']) ? $params['field'] : 'only_tag,name,describe,value,error_tips';
        return Db::table('config')->selectRaw($field)->get();
    }

    /**
     * 配置数据保存
     * @param array $params
     * @return array
     */
    public function configSave($params = [])
    {
        // 参数校验
        if (empty($params)) {
            return dataReturn('参数不能为空', -1);
        }

        // 当前参数中不存在则移除
        $data_fields = self::$attachment_field_list;
        foreach ($data_fields as $key => $field) {
            if (!isset($params[$field])) {
                unset($data_fields[$key]);
            }
        }

        // 获取附件
        $attachment = ResourcesService::attachmentParams($params, $data_fields);
        foreach ($attachment['data'] as $k => $v) {
            $params[$k] = $v;
        }

        // 循环保存数据
        $success = 0;

        // 开始更新数据
        foreach ($params as $k => $v) {
            if (in_array($k, self::$rich_text_list)) {
                $v = ResourcesService::contentStaticReplace($v, 'add');
            } else {
                $v = htmlentities($v);
            }
            if (Db::table('config')->where(['only_tag' => $k])->update(['value' => $v, 'upd_time' => time()]) == 1) {
                $success++;

                // 单条配置缓存删除
//                cache(config('cache_config_row_key').$k, null);
            }
        }
        if ($success > 0) {
            // 配置信息更新
            $this->configInit();


            return dataReturn('编辑成功' . '[' . $success . ']');
        }
        return dataReturn('编辑失败', -100);
    }

    /**
     * 系统配置信息初始化
     */
    public function configInit()
    {
//        $key = config('cache_common_my_config_key');
//        $data = cache($key);
//        if($status == 1 || empty($data))
//        {
        // 所有配置
        $data = Db::table('config')->get(['value', 'only_tag']);

        // 数据处理
        // 开启用户注册列表
        foreach (self::$string_to_array_field_list as $field) {
            if (isset($data[$field])) {
                $data[$field] = empty($data[$field]) ? [] : explode(',', $data[$field]);
            }
        }

        // 富文本字段处理
        foreach ($data as $k => $v) {
            if (in_array($k, self::$rich_text_list)) {
                $data[$k] = ResourcesService::contentStaticReplace($v, 'get');
            }
        }

//            cache($key, $data);
//        }
    }


    /**
     * 根据唯一标记获取条配置内容
     * @param $only_tag
     * @return array
     */
    public function configContentRow($only_tag)
    {
        // 缓存key
//        $key = config('shopxo.cache_config_row_key').$only_tag;
//        $data = cache($key);

        // 获取内容
//        if(empty($data))
//        {
        $data = Db::table('config')->where(['only_tag' => $only_tag])->selectRaw('name,value,type,upd_time')->first()->toArray();
        if (!empty($data)) {
            // 富文本处理
            if (in_array($only_tag, self::$rich_text_list)) {
                $data['value'] = ResourcesService::contentStaticReplace($data['value'], 'get');
            }
            $data['upd_time_time'] = empty($data['upd_time']) ? null : date('Y-m-d H:i:s', $data['upd_time']);
        }
//            cache($key, $data);
//        }

        return dataReturn('操作成功', 0, $data);
    }

    /**
     * 读取站点配置信息
     * @param string $key   [索引名称]
     * @param null $default [默认值]
     * @param bool $mandatory   [是否强制校验值,默认false]
     * @return mixed
     */
    public static function getConfigCache(string  $key, $default = null, $mandatory = false){
        $cache =make(CacheInterface::class);
        $data = $cache->get(self::$cacheKey);
        if (empty($data)){
            $data = Db::table('config')->pluck('value', 'only_tag')->toArray();
            $cache->set(self::$cacheKey, serialize($data));
        }
        if (is_string($data)){
            $data = unserialize($data);
        }
        if($mandatory === true)
        {
            return empty($data[$key]) ? $default : $data[$key];
        }
        return isset($data[$key]) ? $data[$key] : $default;
    }
}