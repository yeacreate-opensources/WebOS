<?php

$service_file = "/home/php/service.lock";
   
shell_exec("echo 1 > {$service_file}");

shell_exec("chown -R www-data:www-data /home/php/www/tabletweb");

shell_exec("chmod -R 0755 /home/php/www/tabletweb");

$s66phpwebsocketserver = "/home/php/yeacreate/install/S66phpwebsocketserver";

$etc_cp = "/etc/init.d/";
    
shell_exec("cp -R {$s66phpwebsocketserver} {$etc_cp}");
    
shell_exec("chmod -R 0755 {$etc_cp}S66phpwebsocketserver");

$input_event_daemon = "/home/php/yeacreate/install/input-event-daemon.conf";
    
shell_exec("cp -R {$input_event_daemon} /etc/input-event-daemon.conf");

$s91chromium = "/home/php/yeacreate/install/S91chromium";

shell_exec("cp -R {$s91chromium} {$etc_cp}");

shell_exec("chmod -R 0755 {$etc_cp}S91chromium");

$arr = "workerman_service:WorkerMan_master:/bin/sh /etc/init.d/S66phpwebsocketserver restart:sleep 5s";
    
shell_exec("echo '{$arr}' >> /etc/service.conf");

shell_exec("rm -rf {$install}");
    
$install_php = "/home/php/www/index.php";
    
shell_exec("rm {$install_php}");

shell_exec("reboot ");


