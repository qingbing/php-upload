<?php
/**
 * @link        http://www.phpcorner.net
 * @author      qingbing<780042175@qq.com>
 * @copyright   Chengdu Qb Technology Co., Ltd.
 */

namespace Zf\PhpUpload\base;


use Zf\Helper\Abstracts\Base;
use Zf\Helper\Exceptions\UnsupportedException;
use Zf\Helper\Util;

/**
 * 文件上传基类
 *
 * Class BaseUpload
 * @package Zf\PhpUpload\base
 */
abstract class BaseUpload extends Base implements IUpload
{
    /**
     * @var bool 临时文件上传后的有效期
     */
    public $tmpDay = 1;
    /**
     * @var string tmp文件目录
     */
    public $tmpFolder = "tmp/";
    /**
     * @var string 上传的文件名是否使用真实的后缀
     */
    public $useRealExtension = false;
    /**
     * @var string tmp文件目录
     */
    public $defaultFolder = "default/";
    /**
     * @var callable 错误日志回调函数
     */
    public $callableErrorLog;

    /**
     * 构造上传文件名
     *
     * @param string $localFile
     * @param string $folder
     * @param bool $useTmp
     * @return string
     */
    protected function generateSaveName($localFile, $folder = null, $useTmp = false)
    {
        if ($this->useRealExtension) {
            $filename = Util::uniqid() . '.' . pathinfo($localFile, PATHINFO_EXTENSION);
        } else {
            $filename = Util::uniqid();
        }
        if (empty($folder)) {
            $folder = $this->defaultFolder;
        }
        if ($useTmp) {
            $folder = $this->tmpFolder . $folder;
        }
        if (empty($folder)) {
            return $filename;
        }
        return trim($folder, "/") . '/' . $filename;
    }

    /**
     * 日志记录， 需提供自定义日志记录 @param string $type
     * @param string $keyword
     * @param mixed $request
     * @param mixed $message
     * @param string $response
     *
     * @see \Zf\PhpUpload\base\BaseUpload::$callableErrorLog
     */
    protected function errorLog($type, $keyword, $request, $message = '', $response = null)
    {
        if (is_callable($this->callableErrorLog)) {
            call_user_func_array($this->callableErrorLog, [
                'type'    => $type,
                'data'    => [
                    'request'  => $request,
                    'response' => $response,
                ],
                'keyword' => $keyword,
                'message' => $message,
            ]);
        }
    }

    /**
     * 通过错误码获取错误信息
     *
     * @param int $code
     * @param null|string $message
     * @return mixed
     * @throws UnsupportedException
     */
    public static function getMessageByCode($code, $message = null)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 生成一个上传的token
     *
     * @param null|string $key
     * @param null $policy
     * @param bool $strictPolicy
     * @return string
     * @throws UnsupportedException
     */
    public function getToken($key = null, $policy = null, $strictPolicy = true)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 获取私有空间的临时访问链接
     *
     * @param string $url
     * @param int $expire
     * @return string
     * @throws UnsupportedException
     */
    public function getPrivateUrl($url, $expire = 3600)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 判断文件是否存在
     *
     * @param string $file
     * @return bool
     * @throws UnsupportedException
     */
    public function has($file): bool
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 文件的信息，对于单个文件信息
     *  ['code' => int code, 'data' => array], code 为200表示正常，其它为异常
     *
     * @param mixed $file
     * @return mixed
     * @throws UnsupportedException
     */
    public function info($file)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 设置文件的生存时间，过期将自动删除
     *
     * @param string $file
     * @param int $day 设置为0表示取消生存时间
     * @return mixed
     * @throws UnsupportedException
     */
    public function setLifeTime($file, $day = 0)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 修改文件的存储类型
     *
     * @param array|string $file
     * @param $type
     * @return mixed
     * @throws UnsupportedException
     */
    public function changeSaveType($file, $type)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 上传文件
     *
     * @param mixed $file
     * @param string|null $folder
     * @param string|null $saveName
     * @param bool $useTmp
     * @return mixed
     * @throws UnsupportedException
     */
    public function upload($file, $folder = null, $saveName = null, $useTmp = false)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 复制文件
     *
     * @param string $file
     * @param string $newFile
     * @param bool $force
     * @param null $newBucket
     * @return mixed
     * @throws UnsupportedException
     */
    public function copy($file, $newFile = null, $force = true, $newBucket = null)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 移动文件
     *
     * @param string $file
     * @param string $newFile
     * @param bool $force
     * @param null $newBucket
     * @return mixed
     * @throws UnsupportedException
     */
    public function move($file, $newFile = null, $force = true, $newBucket = null)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 删除文件
     *
     * @param string $file
     * @return mixed
     * @throws UnsupportedException
     */
    public function del($file)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 抓取网络图片
     *
     * @param string|array $url
     * @param string|null $folder
     * @return mixed
     * @throws UnsupportedException
     */
    public function fetchUrl($url, $folder = null)
    {
        throw new UnsupportedException("不支持的功能");
    }

    /**
     * 获取文件目录的文件列表
     *
     * @param int $limit 个数限制
     * @param string $marker 标识符
     * @param string $prefix 前缀
     * @param string $delimiter 指定目录分隔符
     * @return mixed
     * @throws UnsupportedException
     */
    public function list($limit = 1000, $marker = null, $prefix = null, $delimiter = null)
    {
        throw new UnsupportedException("不支持的功能");
    }
}