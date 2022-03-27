# QiniuUpload: 封装七牛云文件的sdk
- 继承上传抽象类: \Zf\PhpUpload\base\BaseUpload
- 实现接口类: \Zf\PhpUpload\base\IUpload
- 未封装的功能
  - 字节数组上传
  - 文件分片上传
  - 修改文件MimeType
  - 批量修改文件类型
  - 更新镜像空间中存储的文件内容
- 遗留问题
  - 七牛云文件列表功能第二页通过 marker 传递查询不成功

## 对外提供接口
- public static function getMessageByCode($code, $message = null): 通过错误码获取错误信息
- public function getToken($key = null, $policy = null, $strictPolicy = true): 生成一个上传的token
- public function getPrivateUrl($url, $expire = 3600): 获取私有空间的临时访问链接
- public function has($file): bool: 判断文件是否存在
- public function info($file): 文件的信息，对于单个文件信息
- public function setLifeTime($file, $day = 0): 设置文件的生存时间，过期将自动删除
- public function changeSaveType($file, $type): 修改文件的存储类型
- public function upload($file, $folder = null, $saveName = null): 上传文件
- public function copy($file, $newFile = null, $force = true, $newBucket = null): 复制文件
- public function move($file, $newFile = null, $force = true, $newBucket = null): 移动文件
- public function del($file): 删除文件
- public function fetchUrl($url, $folder = null): 抓取网络图片
- public function list($limit = 1000, $marker = null, $prefix = null, $delimiter = null): 获取文件目录的文件列表


## 使用demo
```php
$uploader = new QiniuUpload();
// 设置属性
$uploader->accessKey = '';
$uploader->secretKey = '';
$uploader->bucketName = '';


// 判断文件是否存在
var_dump("获取上传token");
$res = $uploader->getToken();
var_dump($res);

// 获取私有空间文件的临时访问URL
var_dump("获取私有空间文件的临时访问URL");
$file = 'default/623f3c61c0a80109ffae23f3c61c2ca0_copy';
$res  = $uploader->getPrivateUrl($file);
var_dump($res);

// 判断文件是否存在
var_dump("判断文件是否存在");
$file = 'default/623f3c61c0a80109ffae23f3c61c2ca0_copy';
$res  = $uploader->has($file);
var_dump($res);

// 获取单个文件信息，code 为200表示正常，其它为异常
var_dump("获取单个文件信息，code 为200表示正常，其它为异常");
$file = 'default/623f5af2c0a80109ffae23f5af26dced';
$res  = $uploader->info($file);
var_dump($res);
$file = 'default/623f5af2c0a80109ffae23f5af26dced_x';
$res  = $uploader->info($file);
var_dump($res);

// 批量获取文件信息
var_dump("批量获取文件信息");
$files = [
    "default/623f3c61c0a80109ffae23f3c61c2ca0",
    "default/623f3c6dc0a80109ffad23f3c6d3adce.php",
];
$res   = $uploader->info($files);
var_dump($res);

// 生存时间管理
var_dump("设置生存时间，取消设置成0即可");
$file = 'default/623f5af2c0a80109ffae23f5af26dced';
$res  = $uploader->setLifeTime($file, 1); // 设置生存时间为1天
var_dump($res);
$file = 'default/623f5af2c0a80109ffae23f5af26dced_x';
$res  = $uploader->setLifeTime($file, 1); // 设置生存时间为1天
var_dump($res);

var_dump("取消生存时间，设置为0即可");
$files = [
    "default/623f3c61c0a80109ffae23f3c61c2ca0",
    "default/623f3c6dc0a80109ffad23f3c6d3adce.php",
];
$res   = $uploader->setLifeTime($files, 0); // 取消生存时间
var_dump($res);


// 修改文件的存储类型
var_dump("设置单个文件存储类型");
$file = 'default/623f5af2c0a80109ffae23f5af26dced';
$res  = $uploader->changeSaveType($file, QiniuUpload::SAVE_TYPE_LOWER);
var_dump($res);
$file = 'default/623f5af2c0a80109ffae23f5af26dced_x';
$res  = $uploader->changeSaveType($file, QiniuUpload::SAVE_TYPE_LOWER);
var_dump($res);

var_dump("批量设置文件存储类型");
$files = [
    "default/623f3c61c0a80109ffae23f3c61c2ca0",
    "default/623f3c6dc0a80109ffad23f3c6d3adce.php",
];
$res   = $uploader->changeSaveType($files, QiniuUpload::SAVE_TYPE_LOWER);
var_dump($res);

// 上传文件
var_dump("上传文件");
$res = $uploader->upload($_FILES['file'], 'default');
var_dump($res);


// 拷贝文件
var_dump("拷贝文件");
$file    = 'default/623f5af2c0a80109ffae23f5af26dced';
$newFile = $file . '_copy';
$res     = $uploader->copy($file, $newFile);
var_dump($res);
$file    = 'default/623f5af2c0a80109ffae23f5af26dced_';
$newFile = $file . '_copy';
$res     = $uploader->copy($file, $newFile);
var_dump($res);

var_dump("批量拷贝文件");
$files = [
    "default/623f3c61c0a80109ffae23f3c61c2ca0"     => 'default/623f3c61c0a80109ffae23f3c61c2ca0_copy',
    "default/623f3c6dc0a80109ffad23f3c6d3adce.php" => 'default/623f3c6dc0a80109ffad23f3c6d3adce.php_copy',
];
$res   = $uploader->copy($files);
var_dump($res);


// 移动文件
$file      = 'default/623f5af2c0a80109ffae23f5af26dced';
$copyFile  = $file . '_copy';
$copyFile1 = $file . '_copy1';
$uploader->copy($file, $copyFile);
$uploader->copy($file, $copyFile1);

var_dump("移动单个文件");
$newFile = $file . '_move';
$res     = $uploader->move($copyFile, $newFile);
var_dump($res);
$newFile = $file . '_move1';
$res     = $uploader->move('xxx', $newFile);
var_dump($res);
$res = $uploader->move('xxx', '');
var_dump($res);

var_dump("批量移动文件");
$files = [
    "xxxxx"    => 'default/623f3c61c0a80109ffae23f3c61c2ca0_move2',
    $copyFile1 => $file . '_move3',
];
$res   = $uploader->move($files);
var_dump($res);


// 删除文件
$file      = 'default/623f5af2c0a80109ffae23f5af26dced';
$copyFile  = $file . '_copy';
$copyFile1 = $file . '_copy1';
$uploader->copy($file, $copyFile);
$uploader->copy($file, $copyFile1);

var_dump("删除单个文件");
$res = $uploader->del('xxxx');
var_dump($res);
$res = $uploader->del($copyFile);
var_dump($res);
var_dump("批量删除文件");
$files = [
    "xxxxx",
    $copyFile1,
];
$res   = $uploader->del($files);
var_dump($res);

// 抓取网络图片
var_dump("抓取单个网络图片");
$url = 'https://www.baidu.com/img/xxx.png';
$res = $uploader->fetchUrl($url);
var_dump($res);
$url = 'https://www.baidu.com/img/flexible/logo/pc/result.png';
$res = $uploader->fetchUrl($url);
var_dump($res);
var_dump("批量抓取网络图片");
$urls = [
    'https://www.baidu.com/img/flexible/xxx.png',
    'https://www.baidu.com/img/flexible/logo/pc/result.png',
];
$res  = $uploader->fetchUrl($urls);
var_dump($res);

// 文件列表
$marker = '';
$res    = $uploader->list(4, '');
var_dump($res);
if ($res['code'] == 200 && $res['marker']) {
    $res = $uploader->list(4, trim($res['marker'], '='));
    var_dump($res);
    $res = $uploader->list(4, urlencode($res['marker']));
    var_dump($res);
}
```

## 示例响应
```text
string(17) "获取上传token"
string(234) "3CBgXcJOMHF-FRLPe1YueohC6sjC7bUze3NCk5o3:KelTAi_iTy3oPasKID1reskDXyc=:eyJyZXR1cm5Cb2R5Ijoie1wia2V5XCI6XCIkKGtleSlcIixcImhhc2hcIjpcIiQoZXRhZylcIixcImZzaXplXCI6JChmc2l6ZSl9Iiwic2NvcGUiOiJxYi1wcm9ncmFtbWVyIiwiZGVhZGxpbmUiOjE2NDgzNzU3NDR9"
string(42) "获取私有空间文件的临时访问URL"
string(134) "default/623f3c61c0a80109ffae23f3c61c2ca0_copy?e=1648375744&token=3CBgXcJOMHF-FRLPe1YueohC6sjC7bUze3NCk5o3:D6pmqirpo_8_Fp1xj66IbuNhDHM="
string(24) "判断文件是否存在"
bool(true)
string(68) "获取单个文件信息，code 为200表示正常，其它为异常"
array(2) {
  ["code"]=>
  int(200)
  ["data"]=>
  array(6) {
    ["fsize"]=>
    int(6617)
    ["hash"]=>
    string(28) "FhWdGMOuaWgZc-gb2wp-DnhrLsMQ"
    ["md5"]=>
    string(32) "6c825ed7ea4cd25657288ab4f7d0227f"
    ["mimeType"]=>
    string(9) "image/png"
    ["putTime"]=>
    int(16483192187908644)
    ["type"]=>
    int(1)
  }
}
array(2) {
  ["code"]=>
  int(612)
  ["message"]=>
  string(25) "no such file or directory"
}
string(24) "批量获取文件信息"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(612)
    ["data"]=>
    array(1) {
      ["error"]=>
      string(25) "no such file or directory"
    }
  }
  [1]=>
  array(2) {
    ["code"]=>
    int(200)
    ["data"]=>
    array(6) {
      ["fsize"]=>
      int(7713)
      ["hash"]=>
      string(28) "FuOTphwS5tf5kwlOqdYpUDAD176T"
      ["md5"]=>
      string(32) "384c60aafa96916cfb114562b26ac4b9"
      ["mimeType"]=>
      string(23) "application/x-httpd-php"
      ["putTime"]=>
      int(16483114054940566)
      ["type"]=>
      int(1)
    }
  }
}
string(43) "设置生存时间，取消设置成0即可"
array(2) {
  ["code"]=>
  int(200)
  ["data"]=>
  NULL
}
array(2) {
  ["code"]=>
  int(612)
  ["message"]=>
  string(25) "no such file or directory"
}
string(37) "取消生存时间，设置为0即可"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(612)
    ["data"]=>
    array(1) {
      ["error"]=>
      string(25) "no such file or directory"
    }
  }
  [1]=>
  array(1) {
    ["code"]=>
    int(200)
  }
}
string(30) "设置单个文件存储类型"
array(2) {
  ["code"]=>
  int(400)
  ["message"]=>
  string(20) "already in line stat"
}
array(2) {
  ["code"]=>
  int(612)
  ["message"]=>
  string(25) "no such file or directory"
}
string(30) "批量设置文件存储类型"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(612)
    ["data"]=>
    array(1) {
      ["error"]=>
      string(25) "no such file or directory"
    }
  }
  [1]=>
  array(2) {
    ["code"]=>
    int(400)
    ["data"]=>
    array(2) {
      ["error"]=>
      string(20) "already in line stat"
      ["error_code"]=>
      string(17) "AlreadyInLineStat"
    }
  }
}
string(12) "上传文件"
array(4) {
  ["key"]=>
  string(40) "default/624029b2c0a80109ffaf24029b2a4a45"
  ["hash"]=>
  string(28) "FigGjzli9GjFJ24JwS6sTzcP09Am"
  ["fsize"]=>
  int(8343)
  ["code"]=>
  int(200)
}
string(12) "拷贝文件"
array(2) {
  ["code"]=>
  int(200)
  ["data"]=>
  NULL
}
array(2) {
  ["code"]=>
  int(612)
  ["message"]=>
  string(25) "no such file or directory"
}
string(18) "批量拷贝文件"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(612)
    ["data"]=>
    array(1) {
      ["error"]=>
      string(25) "no such file or directory"
    }
  }
  [1]=>
  array(1) {
    ["code"]=>
    int(200)
  }
}
string(18) "移动单个文件"
array(2) {
  ["code"]=>
  int(200)
  ["data"]=>
  NULL
}
array(2) {
  ["code"]=>
  int(612)
  ["message"]=>
  string(25) "no such file or directory"
}
array(2) {
  ["code"]=>
  int(9999)
  ["message"]=>
  string(18) "参数传递错误"
}
string(18) "批量移动文件"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(612)
    ["data"]=>
    array(1) {
      ["error"]=>
      string(25) "no such file or directory"
    }
  }
  [1]=>
  array(1) {
    ["code"]=>
    int(200)
  }
}
string(18) "删除单个文件"
array(2) {
  ["code"]=>
  int(612)
  ["message"]=>
  string(25) "no such file or directory"
}
array(2) {
  ["code"]=>
  int(200)
  ["data"]=>
  NULL
}
string(18) "批量删除文件"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(612)
    ["data"]=>
    array(1) {
      ["error"]=>
      string(25) "no such file or directory"
    }
  }
  [1]=>
  array(1) {
    ["code"]=>
    int(200)
  }
}
string(24) "抓取单个网络图片"
array(2) {
  ["code"]=>
  int(404)
  ["message"]=>
  string(31) "httpGet url failed and meet 404"
}
array(2) {
  ["code"]=>
  int(200)
  ["data"]=>
  array(6) {
    ["fsize"]=>
    int(6617)
    ["hash"]=>
    string(28) "FhWdGMOuaWgZc-gb2wp-DnhrLsMQ"
    ["key"]=>
    string(40) "default/624029b5c0a80109ffaf24029b552dae"
    ["mimeType"]=>
    string(9) "image/png"
    ["overwritten"]=>
    bool(false)
    ["version"]=>
    string(0) ""
  }
}
string(24) "批量抓取网络图片"
array(2) {
  [0]=>
  array(2) {
    ["code"]=>
    int(404)
    ["message"]=>
    string(31) "httpGet url failed and meet 404"
  }
  [1]=>
  array(2) {
    ["code"]=>
    int(200)
    ["data"]=>
    array(6) {
      ["fsize"]=>
      int(6617)
      ["hash"]=>
      string(28) "FhWdGMOuaWgZc-gb2wp-DnhrLsMQ"
      ["key"]=>
      string(34) "1/624029b5c0a80109ffaf24029b5c5a2f"
      ["mimeType"]=>
      string(9) "image/png"
      ["overwritten"]=>
      bool(false)
      ["version"]=>
      string(0) ""
    }
  }
}
array(3) {
  ["marker"]=>
  string(84) "eyJjIjowLCJrIjoiZGVmYXVsdC82MjNmM2M2ZGMwYTgwMTA5ZmZhZDIzZjNjNmQzYWRjZS5waHBfY29weSJ9"
  ["items"]=>
  array(4) {
    [0]=>
    array(8) {
      ["key"]=>
      string(34) "1/624029b5c0a80109ffaf24029b5c5a2f"
      ["hash"]=>
      string(28) "FhWdGMOuaWgZc-gb2wp-DnhrLsMQ"
      ["fsize"]=>
      int(6617)
      ["mimeType"]=>
      string(9) "image/png"
      ["putTime"]=>
      int(16483721500418474)
      ["type"]=>
      int(0)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "6c825ed7ea4cd25657288ab4f7d0227f"
    }
    [1]=>
    array(8) {
      ["key"]=>
      string(45) "default/623f3c61c0a80109ffae23f3c61c2ca0_copy"
      ["hash"]=>
      string(28) "FuOTphwS5tf5kwlOqdYpUDAD176T"
      ["fsize"]=>
      int(7713)
      ["mimeType"]=>
      string(23) "application/x-httpd-php"
      ["putTime"]=>
      int(16483142983280429)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "384c60aafa96916cfb114562b26ac4b9"
    }
    [2]=>
    array(8) {
      ["key"]=>
      string(44) "default/623f3c6dc0a80109ffad23f3c6d3adce.php"
      ["hash"]=>
      string(28) "FuOTphwS5tf5kwlOqdYpUDAD176T"
      ["fsize"]=>
      int(7713)
      ["mimeType"]=>
      string(23) "application/x-httpd-php"
      ["putTime"]=>
      int(16483114054940566)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "384c60aafa96916cfb114562b26ac4b9"
    }
    [3]=>
    array(8) {
      ["key"]=>
      string(49) "default/623f3c6dc0a80109ffad23f3c6d3adce.php_copy"
      ["hash"]=>
      string(28) "FuOTphwS5tf5kwlOqdYpUDAD176T"
      ["fsize"]=>
      int(7713)
      ["mimeType"]=>
      string(23) "application/x-httpd-php"
      ["putTime"]=>
      int(16483721474217889)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "384c60aafa96916cfb114562b26ac4b9"
    }
  }
  ["code"]=>
  int(200)
}
array(3) {
  ["marker"]=>
  string(80) "eyJjIjowLCJrIjoiZGVmYXVsdC82MjNmNWFmMmMwYTgwMTA5ZmZhZTIzZjVhZjI2ZGNlZF9tb3ZlIn0="
  ["items"]=>
  array(4) {
    [0]=>
    array(8) {
      ["key"]=>
      string(45) "default/623f3c8ac0a80109ffaf23f3c8aaae67_copy"
      ["hash"]=>
      string(28) "FigGjzli9GjFJ24JwS6sTzcP09Am"
      ["fsize"]=>
      int(8343)
      ["mimeType"]=>
      string(9) "image/png"
      ["putTime"]=>
      int(16483127742564665)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "0ec9e81c35c66f66b23e724c6063fce8"
    }
    [1]=>
    array(8) {
      ["key"]=>
      string(45) "default/623f3c8ac0a80109ffaf23f3c8aaae67_move"
      ["hash"]=>
      string(28) "FigGjzli9GjFJ24JwS6sTzcP09Am"
      ["fsize"]=>
      int(8343)
      ["mimeType"]=>
      string(9) "image/png"
      ["putTime"]=>
      int(16483128781909397)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "0ec9e81c35c66f66b23e724c6063fce8"
    }
    [2]=>
    array(8) {
      ["key"]=>
      string(40) "default/623f5af2c0a80109ffae23f5af26dced"
      ["hash"]=>
      string(28) "FhWdGMOuaWgZc-gb2wp-DnhrLsMQ"
      ["fsize"]=>
      int(6617)
      ["mimeType"]=>
      string(9) "image/png"
      ["putTime"]=>
      int(16483192187908644)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "6c825ed7ea4cd25657288ab4f7d0227f"
    }
    [3]=>
    array(8) {
      ["key"]=>
      string(45) "default/623f5af2c0a80109ffae23f5af26dced_move"
      ["hash"]=>
      string(28) "FhWdGMOuaWgZc-gb2wp-DnhrLsMQ"
      ["fsize"]=>
      int(6617)
      ["mimeType"]=>
      string(9) "image/png"
      ["putTime"]=>
      int(16483721478574358)
      ["type"]=>
      int(1)
      ["status"]=>
      int(0)
      ["md5"]=>
      string(32) "6c825ed7ea4cd25657288ab4f7d0227f"
    }
  }
  ["code"]=>
  int(200)
}
array(2) {
  ["code"]=>
  int(400)
  ["message"]=>
  string(14) "invalid marker"
}
```
