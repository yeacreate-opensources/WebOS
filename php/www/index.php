<?php
/*
###################################################
##       .(@@@@@@@@@@@@@@@@@@@@@@@@@@@@(.        ##
##     *@.                              .@(      ##
##     @#                      *%(       *@      ##
##     @#                     &.  &.     *@      ##
##     @#       ,@&@.           @*       *@      ##
##     @#       *@  @%          @*       *@      ##
##     @#       *@   *@.        @*       *@      ##
##     @#       *@     @(       @*       *@      ##
##     @#       *@      #@      @*      #( &.    ##
##     @#       *@        @/    @*       ,(      ##
##     #&       *@         %&   @*               ##
##       #@@@@@@@*    #@.   ,@, @*               ##
##             #&       *@#   %@&     .@%        ##
##       *@    #&         .@%           *@*      ##
##      %&     #&           ,@#           &@     ##
##     @/      #&                   #@     (@.   ##
##   @&        #&                   &%      ,@(  ##
##  ,          (@,                 #@.           ##
##                @@@@@@@@@@@@@@@@@              ##
###################################################
################################################################################
#
# index.php
# http://yeacreate.com
# install files
################################################################################
 */

$service_file = "/home/php/service.lock";

$install = "/home/php/yeacreate/install";
   
$install = "/home/php/yeacreate/install";

shell_exec("echo 1 > {$service_file}");

shell_exec("chown -R www-data:www-data /home/php/www/tabletweb");

shell_exec("chmod -R 0755 /home/php/www/tabletweb");

$s66phpwebsocketserver = "/home/php/yeacreate/install/S66phpwebsocketserver";

$etc_cp = "/etc/init.d/";
    
shell_exec("cp -R {$s66phpwebsocketserver} {$etc_cp}");
    
shell_exec("dos2unix {$etc_cp}S66phpwebsocketserver");
shell_exec("chmod -R 0755 {$etc_cp}S66phpwebsocketserver");

$input_conf = "/etc/input-event-daemon.conf";
$content_input = @file_get_contents($input_conf); //读文件
$find = @array("VOLUMEUP     = amixer -q set DAC '2%+'","VOLUMEDOWN   = amixer -q set DAC '2%-'");
$replace = @array("VOLUMEUP     = /usr/bin/php /home/php/yeacreate/volume.php '+'","VOLUMEDOWN   = /usr/bin/php /home/php/yeacreate/volume.php '-'");
$content_input = @str_replace($find,$replace,$content_input);;
@file_put_contents($input_conf, $content_input);
    
shell_exec("dos2unix {$input_conf}");

$s91chromium = "{$etc_cp}S91chromium";
$content_s91chromium = @file_get_contents($s91chromium); //读文件
$content_s91chromium = @str_replace(array('http://127.0.0.1','http://localhost'),'/home/php/www/tabletweb/index.html',$content_s91chromium);
$content_s91chromium_data = @explode('chmod 666 /dev/video-enc0;',$content_s91chromium);
$content_s91chromium = $content_s91chromium_data[0].'chmod 666 /dev/video-enc0;'."\n\t\tamixer -q set DAC '100%';".$content_s91chromium_data[1];
@file_put_contents($s91chromium, $content_s91chromium);
shell_exec("dos2unix {$s91chromium}");
shell_exec("chmod -R 0755 {$s91chromium}");

$arr = "workerman_service:WorkerMan_master:/bin/sh /etc/init.d/S66phpwebsocketserver restart:sleep 5s";
    
shell_exec("echo '{$arr}' >> /etc/service.conf");

shell_exec("rm -rf {$install}");
    
$install_php = "/home/php/www/index.php";
    
shell_exec("rm {$install_php}");

shell_exec("sync");

shell_exec("reboot ");


