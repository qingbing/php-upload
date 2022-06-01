<?php
/**
 * @link        http://www.phpcorner.net
 * @author      qingbing<780042175@qq.com>
 * @copyright   Chengdu Qb Technology Co., Ltd.
 */

namespace Zf\PhpUpload;


use Exception;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Http\Error;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Zf\PhpUpload\base\BaseUpload;

/**
 * 七牛云文件管理
 *    - 未封装的功能
 *      - 字节数组上传
 *      - 文件分片上传
 *      - 修改文件MimeType
 *      - 批量修改文件类型
 *      - 更新镜像空间中存储的文件内容
 *    - 遗留问题
 *      - 七牛云文件列表功能第二页通过 marker 传递查询不成功
 *
 * Class QiniuUpload
 * @package Zf\PhpUpload
 */
class QiniuUpload extends BaseUpload
{
    /**
     * 存储类型
     */
    const SAVE_TYPE_COMMON = 0; // 普通存储
    const SAVE_TYPE_LOWER  = 1; // 低频存储

    /**
     * @var string 访问 key， AK
     */
    public $accessKey;
    /**
     * @var string 私钥 key， SK
     */
    public $secretKey;
    /**
     * @var int token的有效时间，官方默认有效时间为1小时
     */
    public $tokenExpire = 3600;
    /**
     * @var array 默认生成token的策略配置
     */
    public $policy = [
        'returnBody' => '{"key":"$(key)","hash":"$(etag)","fsize":$(fsize)}'
    ];
    /**
     * @var string 空间名
     */
    private $_bucketName;
    /**
     * @var Auth 七牛云的鉴权对象
     */
    protected $auth;

    /**
     * @var 当前操作api
     */
    protected $currentApi;
    /**
     * @var 当前操作关键字
     */
    protected $currentKeyword;
    /**
     * @var 当前操作请求内容
     */
    protected $currentRequest;

    /**
     * 正常响应码
     */
    const SUCCESS_CODE     = 200;
    const ERROR_PARAM_CODE = 9999;
    /**
     * 错误码列表
     */
    const STATUS_MESSAGES = array(
        298                    => '部分操作执行成功',
        400                    => '请求报文格式错误',
        401                    => '认证授权失败',
        403                    => '权限不足，拒绝访问。',
        404                    => '资源不存在',
        405                    => '请求方式错误',
        406                    => '上传的数据 CRC32 校验错误',
        413                    => '请求资源大小大于指定的最大值',
        419                    => '用户账号被冻结',
        478                    => '镜像回源失败',
        502                    => '错误网关',
        503                    => '服务端不可用',
        504                    => '服务端操作超时',
        573                    => '单个资源访问频率过高',
        579                    => '上传成功但是回调失败',
        599                    => '服务端操作失败',
        608                    => '资源内容被修改',
        612                    => '指定资源不存在或已被删除',
        614                    => '目标资源已存在',
        630                    => '已创建的空间数量达到上限，无法创建新空间。',
        631                    => '指定空间不存在',
        640                    => '调用列举资源(list)接口时，指定非法的marker参数。',
        701                    => '在断点续上传过程中，后续上传接收地址不正确或ctx信息已过期。',
        self::ERROR_PARAM_CODE => '参数传递错误', // 自定义错误代码
    );

    /**
     * 通过错误码获取错误信息
     *
     * @param int $code
     * @param null|string $message
     * @return mixed
     */
    public static function getMessageByCode($code, $message = null)
    {
        return isset(self::STATUS_MESSAGES[$code]) ? self::STATUS_MESSAGES[$code] : $message;
    }

    /**
     * 获取空间名
     *
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->_bucketName;
    }

    /**
     * 设置空间
     *
     * @param string $bucketName
     * @return $this
     */
    public function setBucketName(string $bucketName)
    {
        $this->_bucketName = $bucketName;
        return $this;
    }

    /**
     * 获取授权组件
     *
     * @return Auth
     */
    protected function getAuth(): Auth
    {
        if (null === $this->auth) {
            $this->auth = new Auth($this->accessKey, $this->secretKey);
        }
        return $this->auth;
    }

    /**
     * 获取上传组件
     *
     * @return UploadManager
     */
    protected function getUploaderManager(): UploadManager
    {
        return new UploadManager();
    }

    /**
     * 获取上传组件
     *
     * @return Config
     */
    protected function getConfig(): Config
    {
        return new Config();
    }

    /**
     * 获取上传组件
     *
     * @return BucketManager
     */
    protected function getBucketManager(): BucketManager
    {
        return new BucketManager($this->getAuth(), $this->getConfig());
    }

    /**
     * 生成一个上传的token
     *
     * @param null|string $key
     * @param null|array $policy
     * @param bool $strictPolicy
     * @return string
     */
    public function getToken($key = null, $policy = null, $strictPolicy = true)
    {
        $policy = is_array($policy) ? array_merge($this->policy) : $this->policy;
        return $this->getAuth()->uploadToken($this->getBucketName(), $key, $this->tokenExpire, $policy, $strictPolicy);
    }

    /**
     * 获取私有空间的临时访问链接
     *
     * @param string $url
     * @param int $expire
     * @return string
     */
    public function getPrivateUrl($url, $expire = 3600)
    {
        return $this->getAuth()->privateDownloadUrl($url, $expire);
    }

    /**
     * 判断文件是否存在
     *
     * @param string $file
     * @return bool
     */
    public function has($file): bool
    {
        $response = $this->getBucketManager()
            ->stat($this->getBucketName(), $file);
        if ($response[1]) {
            return false;
        }
        return true;
    }

    /**
     * 文件的信息，对于单个文件信息
     *  ['code' => int code, 'data' => array], code 为200表示正常，其它为异常
     *
     * @param mixed $file
     * @return array
     */
    public function info($file)
    {
        $this->currentApi     = 'qiniu:info:stat';
        $this->currentKeyword = "获取文件信息";
        $this->currentRequest = [
            'bucket' => $this->getBucketName(),
            'file'   => $file,
        ];

        if (is_array($file)) {
            $ops      = BucketManager::buildBatchStat($this->getBucketName(), $file);
            $response = $this->getBucketManager()->batch($ops);
            return $this->parseResponse($response);
        }
        $response = $this->getBucketManager()->stat($this->getBucketName(), $file);
        return $this->parseResponse($response);
    }

    /**
     * 设置文件的生存时间，过期将自动删除
     *
     * @param string $file
     * @param int $day 设置为0表示取消生存时间
     * @return array
     */
    public function setLifeTime($file, $day = 0)
    {
        $this->currentApi     = 'qiniu:setLifeTime:deleteAfterDays';
        $this->currentKeyword = "设置文件的生存时间";
        $this->currentRequest = [
            'bucket' => $this->getBucketName(),
            'file'   => $file,
            'day'    => $day,
        ];
        if (is_array($file)) {
            // 批量设置
            $pairs = [];
            foreach ($file as $k => $v) {
                if (is_numeric($k)) {
                    $pairs[$v] = $day;
                } else {
                    $pairs[$k] = $v;
                }
            }
            $ops      = BucketManager::buildBatchDeleteAfterDays($this->getBucketName(), $pairs);
            $response = $this->getBucketManager()->batch($ops);
            return $this->parseResponse($response);
        }
        // 单个设置
        $response = $this->getBucketManager()
            ->deleteAfterDays($this->getBucketName(), $file, $day);
        return $this->parseResponse($response);
    }

    /**
     * 修改文件的存储类型
     *
     * @param array|string $file
     * @param $type
     * @return array|mixed
     */
    public function changeSaveType($file, $type)
    {
        $this->currentApi     = 'qiniu:changeSaveType:changeType';
        $this->currentKeyword = "修改文件的存储类型";
        $this->currentRequest = [
            'bucket' => $this->getBucketName(),
            'file'   => $file,
            'type'   => $type,
        ];
        if (is_array($file)) {
            // 批量修改
            $pairs = [];
            foreach ($file as $k => $v) {
                if (is_numeric($k)) {
                    $pairs[$v] = $type;
                } else {
                    $pairs[$k] = $v;
                }
            }
            $ops      = BucketManager::buildBatchChangeType($this->getBucketName(), $pairs);
            $response = $this->getBucketManager()->batch($ops);
            return $this->parseResponse($response);
        }
        // 单个修改
        $response = $this->getBucketManager()
            ->changeType($this->getBucketName(), $file, $type);
        return $this->parseResponse($response);
    }

    /**
     * 上传文件
     *
     * @param mixed $file
     * @param string|null $folder
     * @param string|null $saveName
     * @param bool $useTmp
     * @return array
     * @throws Exception
     */
    public function upload($file, $folder = null, $saveName = null, $useTmp = false)
    {
        if (is_array($file)) {
            $filepath = $file['tmp_name'];
            $filename = $file['name'];
        } else {
            $filepath = $file;
            $filename = $file;
        }
        if (empty($saveName)) {
            $saveName = $this->generateSaveName($filename, $folder, $useTmp);
        }
        $this->currentApi     = 'qiniu:upload:putFile';
        $this->currentKeyword = "修改文件的存储类型";
        $this->currentRequest = [
            'bucket'   => $this->getBucketName(),
            'file'     => $file,
            'folder'   => $folder,
            'saveName' => $saveName,
        ];

        $response = $this->getUploaderManager()
            ->putFile($this->getToken($saveName), $saveName, $filepath);
        $res      = $this->parseResponse($response);
        if ($res['code'] == self::SUCCESS_CODE) {
            if ($useTmp) {
                $this->setLifeTime($res['data']['key'], $this->tmpDay);
            }
            $res['data']['code'] = self::SUCCESS_CODE;
            return $res['data'];
        }
        return $res;
    }

    /**
     * 复制文件
     *
     * @param string $file
     * @param string $newFile
     * @param bool $force
     * @param null $newBucket
     * @return array
     */
    public function copy($file, $newFile = null, $force = true, $newBucket = null)
    {
        $newBucket = empty($newBucket) ? $this->getBucketName() : $newBucket;

        $this->currentApi     = 'qiniu:copy:copy';
        $this->currentKeyword = "复制文件";
        $this->currentRequest = [
            'bucket'    => $this->getBucketName(),
            'file'      => $file,
            'newFile'   => $newFile,
            'force'     => $force,
            'newBucket' => $newBucket,
        ];

        if (is_array($file)) {
            // 批量复制
            $ops      = BucketManager::buildBatchCopy($this->getBucketName(), $file, $newBucket, $force);
            $response = $this->getBucketManager()->batch($ops);
            return $this->parseResponse($response);
        }
        if (!$newFile) {
            return [
                'code'    => self::ERROR_PARAM_CODE,
                'message' => self::STATUS_MESSAGES[self::ERROR_PARAM_CODE]
            ];
        }
        // 单个文件复制
        $response = $this->getBucketManager()
            ->copy($this->getBucketName(), $file, $newBucket, $newFile, $force);
        return $this->parseResponse($response);
    }

    /**
     * 移动文件
     *
     * @param string $file
     * @param string $newFile
     * @param bool $force
     * @param null $newBucket
     * @return array
     */
    public function move($file, $newFile = null, $force = true, $newBucket = null)
    {
        $newBucket = empty($newBucket) ? $this->getBucketName() : $newBucket;

        $this->currentApi     = 'qiniu:move:move';
        $this->currentKeyword = "移动文件";
        $this->currentRequest = [
            'bucket'    => $this->getBucketName(),
            'file'      => $file,
            'newFile'   => $newFile,
            'force'     => $force,
            'newBucket' => $newBucket,
        ];

        if (is_array($file)) {
            // 批量移动
            $ops      = BucketManager::buildBatchMove($this->getBucketName(), $file, $newBucket, $force);
            $response = $this->getBucketManager()->batch($ops);
            return $this->parseResponse($response);
        }
        if (!$newFile) {
            return [
                'code'    => self::ERROR_PARAM_CODE,
                'message' => self::STATUS_MESSAGES[self::ERROR_PARAM_CODE]
            ];
        }
        // 单个移动
        $response = $this->getBucketManager()
            ->move($this->getBucketName(), $file, $newBucket, $newFile, $force);
        return $this->parseResponse($response);
    }

    /**
     * 删除文件
     *
     * @param string $file
     * @return array|bool
     */
    public function del($file)
    {
        $this->currentApi     = 'qiniu:del:delete';
        $this->currentKeyword = "删除文件";
        $this->currentRequest = [
            'bucket' => $this->getBucketName(),
            'file'   => $file,
        ];

        if (is_array($file)) {
            // 批量删除
            $ops      = BucketManager::buildBatchDelete($this->getBucketName(), $file);
            $response = $this->getBucketManager()->batch($ops);
            return $this->parseResponse($response);
        }
        // 单个删除
        $response = $this->getBucketManager()
            ->delete($this->getBucketName(), $file);
        return $this->parseResponse($response);
    }

    /**
     * 抓取网络图片
     *
     * @param string|array $url
     * @param string|null $folder
     * @return array|bool
     */
    public function fetchUrl($url, $folder = null)
    {
        if (is_array($url)) {
            // 批量抓取
            $res = [];
            foreach ($url as $folder => $u) {
                $res[] = $this->fetchUrl($u, $folder);
            }
            return $res;
        }
        // 单个抓取
        $saveName = $this->generateSaveName($url, $folder); // 保存文件名

        $this->currentApi     = 'qiniu:fetchUrl:fetch';
        $this->currentKeyword = "删除文件";
        $this->currentRequest = [
            'bucket'   => $this->getBucketName(),
            'url'      => $url,
            'saveName' => $saveName,
        ];

        $response = $this->getBucketManager()
            ->fetch($url, $this->getBucketName(), $saveName);
        return $this->parseResponse($response);
    }

    /**
     * todo 循环列表 marker 报错：Error(401):认证授权失败
     * 获取文件目录的文件列表
     *
     * @param int $limit 个数限制
     * @param string $marker 标识符
     * @param string $prefix 前缀
     * @param string $delimiter 指定目录分隔符
     * @return array|mixed
     */
    public function list($limit = 1000, $marker = null, $prefix = null, $delimiter = null)
    {
        $this->currentApi     = 'qiniu:list:listFiles';
        $this->currentKeyword = "获取文件目录的文件列表";
        $this->currentRequest = [
            'bucket'    => $this->getBucketName(),
            'prefix'    => $prefix,
            'marker'    => $marker,
            'limit'     => $limit,
            'delimiter' => $delimiter,
        ];

        $response = $this->getBucketManager()
            ->listFiles($this->getBucketName(), $prefix, $marker, $limit, $delimiter);
        $res      = $this->parseResponse($response);
        if ($res['code'] === self::SUCCESS_CODE) {
            $res['data']['code']   = self::SUCCESS_CODE;
            $res['data']['marker'] = $res['data']['marker'] ?? null;
            return $res['data'];
        }
        return $res;
    }

    /**
     * 解析七牛云响应
     *
     * @param array $result
     * @param string $dataField
     * @return array
     */
    protected function parseResponse($result, $dataField = 'data')
    {
        list($res, $error) = $result;
        if ($error && $error instanceof Error) {
            $response = [
                'code'    => $error->code(),
                'message' => $error->message(),
            ];
            $this->errorLog($this->currentApi, $this->currentKeyword, $this->currentRequest, $error->message(), $response);
            return $response;
        }
        if ($res && is_real_array($res)) {
            return $res;
        }
        return [
            'code'     => self::SUCCESS_CODE,
            $dataField => $res,
        ];
    }
}