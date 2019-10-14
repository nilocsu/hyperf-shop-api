<?php

declare(strict_types=1);

/**
 * 金额格式化.
 *
 * @param $value
 * @param int    $decimals
 * @param string $dec_point
 *
 * @return string
 */
function priceNumberFormat($value, $decimals = 2, $dec_point = '.')
{
    return number_format($value, $decimals, $dec_point, '');
}

/**
 * 是否是手机访问.
 *
 * @return bool
 */
function isMobile()
{
    $request = make(\Hyperf\HttpServer\Contract\RequestInterface::class);
    $header = $request->getHeaders();
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($header['x-wap-profile'])) {
        return true;
    }

    // 此条摘自TPM智能切换模板引擎，适合TPM开发
    if (isset($header['client']) && 'PhoneClient' === $header['client'][0]) {
        return true;
    }

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($header['via']) && false !== stristr($header['via'][0], 'wap')) {
        return true;
    }

    // 判断手机发送的客户端标志,兼容性有待提高
    if (isset($header['user-agent'])) {
        $clientKeywords = [
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipad',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile',
        ];
        // 从user-agent中查找手机浏览器的关键字
        if (preg_match('/('.implode('|', $clientKeywords).')/i', strtolower($header['user-agent'][0]))) {
            return true;
        }
    }

    // 协议法，因为有可能不准确，放到最后判断
    if (isset($header['accept'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((false !== strpos($header['accept'][0], 'vnd.wap.wml')) && (false === strpos(
            $header['accept'][0],
            'text/html'
        ) || (strpos($header['accept'], 'vnd.wap.wml') < strpos(
            $header['accept'][0],
            'text/html'
                    )))) {
            return true;
        }
    }

    return false;
}

/**
 * @param int $length
 *
 * @return string
 */
function getNumberCode($length = 6)
{
    $code = '';
    for ($i = 0; $i < (int) $length; ++$i) {
        $code .= rand(0, 9);
    }

    return $code;
}

/**
 * 登录密码加密.
 *
 * @param $pwd
 * @param $salt
 *
 * @return string
 */
function loginPwdEncryption($pwd, $salt)
{
    return md5($salt.trim($pwd));
}

/**
 * 公共返回数据.
 *
 * @param string $msg
 * @param int    $code
 * @param array  $data
 *
 * @return array
 */
function dataReturn($msg = '', $code = 0, $data = [])
{
    // 默认情况下，手动调用当前方法
    $result = ['msg' => $msg, 'code' => $code, 'data' => $data];

    // 错误情况下，防止提示信息为空
    if (0 !== $result['code'] && empty($result['msg'])) {
        $result['msg'] = '操作失败';
    }

    return $result;
}

/**
 * 参数校验方法.
 *
 * @param array $data   原始数据
 * @param array $params 校验数据
 *
 * @return bool|string 成功true, 失败 错误信息
 */
function paramsChecked(array $data, array $params)
{
    if (empty($params) || !is_array($data) || !is_array($params)) {
        return '内部调用参数配置有误';
    }

    foreach ($params as $v) {
        if (empty($v['key_name']) || empty($v['error_msg'])) {
            return '内部调用参数配置有误';
        }

        // 是否需要验证
        $is_checked = true;

        // 数据或字段存在则验证
        // 1 数据存在则验证
        // 2 字段存在则验证
        if (isset($v['is_checked'])) {
            if (1 === $v['is_checked']) {
                if (empty($data[$v['key_name']])) {
                    $is_checked = false;
                }
            } elseif (2 === $v['is_checked']) {
                if (!isset($data[$v['key_name']])) {
                    $is_checked = false;
                }
            }
        }

        // 是否需要验证
        if (false === $is_checked) {
            continue;
        }

        // 数据类型,默认字符串类型
        $data_type = empty($v['data_type']) ? 'string' : $v['data_type'];

        // 验证规则，默认isset
        $checked_type = isset($v['checked_type']) ? $v['checked_type'] : 'isset';
        switch ($checked_type) {
            // 是否存在
            case 'isset':
                if (!isset($data[$v['key_name']])) {
                    return $v['error_msg'];
                }

                break;
            // 是否为空
            case 'empty':
                if (empty($data[$v['key_name']])) {
                    return $v['error_msg'];
                }

                break;
            // 是否存在于验证数组中
            case 'in':
                if (empty($v['checked_data']) || !is_array($v['checked_data'])) {
                    return '内部调用参数配置有误';
                }
                if (!isset($data[$v['key_name']]) || !in_array($data[$v['key_name']], $v['checked_data'], true)) {
                    return $v['error_msg'];
                }

                break;
            // 是否为数组
            case 'is_array':
                if (!isset($data[$v['key_name']]) || !is_array($data[$v['key_name']])) {
                    return $v['error_msg'];
                }

                break;
            // 长度
            case 'length':
                if (!isset($v['checked_data'])) {
                    return '长度规则值未定义';
                }
                if (!is_string($v['checked_data'])) {
                    return '内部调用参数配置有误';
                }
                if (!isset($data[$v['key_name']])) {
                    return $v['error_msg'];
                }
                if ('array' === $data_type) {
                    $length = count($data[$v['key_name']]);
                } else {
                    $length = mb_strlen($data[$v['key_name']], 'utf-8');
                }
                $rule = explode(',', $v['checked_data']);
                if (1 === count($rule)) {
                    if ($length > (int) ($rule[0])) {
                        return $v['error_msg'];
                    }
                } else {
                    if ($length < (int) ($rule[0]) || $length > (int) ($rule[1])) {
                        return $v['error_msg'];
                    }
                }

                break;
            // 自定义函数
            case 'fun':
                if (empty($v['checked_data']) || !function_exists($v['checked_data'])) {
                    return '验证函数为空或函数未定义';
                }
                $fun = $v['checked_data'];
                if (!isset($data[$v['key_name']]) || !$fun($data[$v['key_name']])) {
                    return $v['error_msg'];
                }

                break;
            // 最小
            case 'min':
                if (!isset($v['checked_data'])) {
                    return '验证最小值未定义';
                }
                if (!isset($data[$v['key_name']]) || $data[$v['key_name']] < $v['checked_data']) {
                    return $v['error_msg'];
                }

                break;
            // 最大
            case 'max':
                if (!isset($v['checked_data'])) {
                    return '验证最大值未定义';
                }
                if (!isset($data[$v['key_name']]) || $data[$v['key_name']] > $v['checked_data']) {
                    return $v['error_msg'];
                }

                break;
            // 相等
            case 'eq':
                if (!isset($v['checked_data'])) {
                    return '验证相等未定义';
                }
                if (!isset($data[$v['key_name']]) || $data[$v['key_name']] === $v['checked_data']) {
                    return $v['error_msg'];
                }

                break;
            // 数据库唯一
            case 'unique':
                if (!isset($v['checked_data'])) {
                    return '验证唯一表参数未定义';
                }
                if (empty($data[$v['key_name']])) {
                    return $v['error_msg'];
                }
                $temp = Hyperf\DbConnection\Db::table($v['checked_data'])->where([$v['key_name'] => $data[$v['key_name']]])->first();
                if (!empty($temp)) {
                    return $v['error_msg'];
                }

                break;
        }
    }
    return true;
}

/**
 * 生成url地址
 * @param string $path
 * @param array $params
 * @return string
 */
function responseUrl(string $path, array  $params = [])
{
    $param = '';
    if (count($params)>0){
        foreach ($params as $k => $v){
            if (strlen($param)){
                $param .= '?'.$k . '='.$v;
            }else{
                $param .= '&'.$k . '='.$v;
            }
        }
    }
    return env('RESOURCE').$path . $param;
}

/**
 * PriceBeautify 金额美化
 * @param int $price
 * @param null $default
 * @return bool|int|mixed|null|string
 */
function priceBeautify($price = 0, $default = null)
{
    if(empty($price))
    {
        return $default;
    }

    $price = str_replace('.00', '', $price);
    if(strpos ($price, '.') !== false)
    {
        if(substr($price, -1) == 0)
        {
            $price = substr($price, 0, -1);
        }
    }
    return $price;
}