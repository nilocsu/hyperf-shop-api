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

/**
 * 资源服务层
 */
class ResourcesService
{
    public static function contentStaticReplace($content, $type = 'get')
    {
        $static = env('RESOURCE');
        switch ($type) {
            // 读取内容
        case 'get':
            return str_replace('src="/static/', 'src="' . $static . 'static/', $content);
                break;
            // 内容写入
        case 'add':
            return str_replace(['src="' . $static . 'static/', 'src="' . $static . 'static/'], 'src="/static/', $content);
        }

        return $content;
    }

    //end contentStaticReplace()

    public static function attachmentParams($params, $data)
    {
        if (\is_object($params)) {
            $params = (array) $params;
        }

        if (\is_object($data)) {
            $data = (array) $data;
        }

        $result = [];
        if (! empty($data)) {
            foreach ($data as $field) {
                $result[$field] = isset($params[$field]) ? self::attachmentPathHandle($params[$field]) : '';
            }
        }

        return dataReturn('success', 0, $result);
    }

    //end attachmentParams()

    public static function attachmentPathHandle($value)
    {
        $static = env('RESOURCE');

        return empty($value) ? '' : $static . $value;
    }

    //end attachmentPathHandle()

    public static function attachmentPathViewHandle($value)
    {
        if (! empty($value)) {
            if (substr($value, 0, 4) !== 'http') {
                return env('RESOURCE') . $value;
            }

            return $value;
        }

        return '';
    }

    //end attachmentPathViewHandle()
}//end class
