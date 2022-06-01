<?php
/**
 * @link        http://www.phpcorner.net
 * @author      qingbing<780042175@qq.com>
 * @copyright   Chengdu Qb Technology Co., Ltd.
 */

namespace Zf\PhpUpload\base;

/**
 * 接口: 文件上传接口
 *
 * Interface IUpload
 * @package Zf\PhpUpload\base
 */
interface IUpload
{
    /**
     * 通过错误码获取错误信息
     *
     * @param int $code
     * @param null|string $message
     * @return mixed
     */
    public static function getMessageByCode($code, $message = null);

    /**
     * 生成一个上传的token
     *
     * @param null|string $key
     * @param null $policy
     * @param bool $strictPolicy
     * @return string
     */
    public function getToken($key = null, $policy = null, $strictPolicy = true);

    /**
     * 获取私有空间的临时访问链接
     *
     * @param string $url
     * @param int $expire
     * @return string
     */
    public function getPrivateUrl($url, $expire = 3600);

    /**
     * 判断文件是否存在
     *
     * @param string $file
     * @return bool
     */
    public function has($file): bool;

    /**
     * 文件的信息，对于单个文件信息
     *  ['code' => int code, 'data' => array], code 为200表示正常，其它为异常
     *
     * @param mixed $file
     * @return array
     */
    public function info($file);

    /**
     * 设置文件的生存时间，过期将自动删除
     *
     * @param string $file
     * @param int $day 设置为0表示取消生存时间
     * @return mixed
     */
    public function setLifeTime($file, $day = 0);

    /**
     * 修改文件的存储类型
     *
     * @param array|string $file
     * @param $type
     * @return mixed
     */
    public function changeSaveType($file, $type);

    /**
     * 上传文件
     *
     * @param mixed $file
     * @param string|null $folder
     * @param string|null $saveName
     * @param bool $useTmp
     * @return mixed
     */
    public function upload($file, $folder = null, $saveName = null, $useTmp = false);

    /**
     * 复制文件
     *
     * @param string $file
     * @param string $newFile
     * @param bool $force
     * @param null $newBucket
     * @return mixed
     */
    public function copy($file, $newFile = null, $force = true, $newBucket = null);

    /**
     * 移动文件
     *
     * @param string $file
     * @param string $newFile
     * @param bool $force
     * @param null $newBucket
     * @return mixed
     */
    public function move($file, $newFile = null, $force = true, $newBucket = null);

    /**
     * 删除文件
     *
     * @param string $file
     * @return mixed
     */
    public function del($file);

    /**
     * 抓取网络图片
     *
     * @param string|array $url
     * @param string|null $folder
     * @return mixed
     */
    public function fetchUrl($url, $folder = null);

    /**
     * 获取文件目录的文件列表
     *
     * @param int $limit 个数限制
     * @param string $marker 标识符
     * @param string $prefix 前缀
     * @param string $delimiter 指定目录分隔符
     * @return mixed
     */
    public function list($limit = 1000, $marker = null, $prefix = null, $delimiter = null);
}