<?php
// 信令服务器(Signaling Server)地址，需要用wss协议，并且必须是域名
// $SIGNALING_ADDRESS = 'ws://localhost:8443';
$SIGNALING_ADDRESS_WEBSOCKET = 'websocket://localhost:2020';
$SIGNALING_ADDRESS_WS = 'ws://localhost:2020';
$system_ver_arr = '10101010';
$system_name_arr = 'Yea Create Webos';

/*
$SSL_CONTEXT = array(
    // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
    'ssl' => array(
        // 请使用绝对路径
        'local_cert'        => '磁盘路径/server.pem', // 也可以是crt文件
        'local_pk'          => '磁盘路径/server.key',
        'verify_peer'       => false,
        'allow_self_signed' => true, //如果是自签名证书需要开启此选项
    )
);
*/

