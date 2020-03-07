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
# startos.php
# http://yeacreate.com
# run with command service
# workerman service
################################################################################
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use Workerman\Worker;
use \Workerman\Lib\Timer;

$yeacweboswebsocket = new Worker($SIGNALING_ADDRESS_WEBSOCKET);

$yeacweboswebsocket->count = 1;

$yeacweboswebsocket->name = 'yeacweboswebsocket';

$yeacweboswebsocket->onConnect = function($connection)
{
    $wifi_connect_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 status"))); //分割连接返回结果集
    
    $get_wifi_ssid = @trim(explode('=', @$wifi_connect_result[2])[1]); //获取ssid

    $get_wifi_status = @trim(explode('=', @$wifi_connect_result[9])[1]); //获取连接wifi状态，

    if( $get_wifi_status == 'COMPLETED'  )
    {

        $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":1,\"ssid\":\"$get_wifi_ssid\"}";

    }else{
        $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";
    }
    return $connection->send($new_message);
};

$yeacweboswebsocket->onWorkerStart = function($yeacweboswebsocket)
{
    wiringpisetup();
    export_out_get();
    // @shell_exec("amixer -q set DAC '100%'");
    $file_test = '/proc/gpio_adc0';
    $echo_file_test = '/tmp/test_success.txt';
    Timer::add(1, function()use($yeacweboswebsocket,$echo_file_test,$file_test){
        if( file_exists($file_test) ){
            $success = @trim(shell_exec("cat {$file_test}"));
            $test_new_message = "{\"command\":\"system_settings\",\"action\":\"set_voltage\",\"voltage_value\":{$success}}";
            if( !file_exists($echo_file_test) ){
                @shell_exec("echo '{$success}' > {$echo_file_test}");
                foreach($yeacweboswebsocket->connections as $connection) {
                    $connection->send($test_new_message);
                }
            }else{
                $success_test = trim(shell_exec("cat {$echo_file_test}"));
                $success_test_add = $success_test+20;
                $success_test_del = $success_test-20;
                if( $success >= $success_test_add || $success <= $success_test_del ){
                    @shell_exec("echo '{$success}' > {$echo_file_test}");
                    foreach($yeacweboswebsocket->connections as $connection) {
                        $connection->send($test_new_message);
                    }
                }
            }
        }
    });
};
// 当客户端发来数据时
$yeacweboswebsocket->onMessage = function($connection, $message)use(&$yeacweboswebsocket,&$system_ver_arr,&$system_name_arr)
{
   $data = json_decode($message);
   switch ( @$data->command )
    {

        case 'system_settings':
            wiringpisetup();

            /****************************** 音量调节 ******************************/
                if( @$data->action == 'get_voice' )
                {
                    $get_current = explode('%', shell_exec("amixer get DAC | grep 'Right:' | awk -F '[][]' '{ print $2 }'"))[0];

                    $payload = ['command' =>'system_settings','action' =>'set_voice','volume' =>$get_current];
                    foreach($yeacweboswebsocket->connections as $connection) {
                        $connection->send(json_encode($payload));
                    }
                    return ;
                }

                if( @$data->action == 'sets_voice' )
                {

                    $voice = (int)$data->volume;

                    if( $voice < 0 )
                    {

                        $voice == 0;

                    }elseif( $voice > 100 )
                    {

                        $voice == 100;

                    }

                    shell_exec("amixer -q set DAC '{$voice}%' ");

                    $payload = ['command' =>'system_settings','action' =>'set_voice','volume' =>$voice];
                    return $connection->send(json_encode($payload));

                }

                /****************************** 音量调节 ******************************/

                /****************************** 亮度调节 ******************************/

                if( @$data->action == 'get_brightness' )
                {
                    $get_brightness = trim(shell_exec("cat /sys/class/backlight/backlight/brightness"));

                    $payload = ['command' =>'system_settings','action' =>'set_brightness','brightness' =>$get_brightness];
                    return $connection->send(json_encode($payload));
                }

                if( @$data->action == 'sets_brightness' )
                {

                    $set_brightness = (int)$data->brightness;

                    if( $set_brightness < 0 )
                    {

                        $set_brightness == 0;

                    }elseif( $set_brightness > 255 )
                    {

                        $set_brightness == 255;

                    }

                    shell_exec("echo $set_brightness > /sys/class/backlight/backlight/brightness & ");

                    $get_brightness = trim(shell_exec("cat /sys/class/backlight/backlight/brightness"));

                    $payload = ['command' =>'system_settings','action' =>'set_brightness','brightness' =>$get_brightness];

                    return $connection->send(json_encode($payload));

                }

                 /****************************** 亮度调节 ******************************/

                 /****************************** 电池状态 ******************************/
                 if( @$data->action == 'get_capacity' )
                {
                    // @shell_exec("ps | grep 'enable_screen' | grep -v grep | awk '{print $1}' | xargs kill -9");
                    $now_capacity = (int)trim(shell_exec("cat /sys/class/power_supply/battery/capacity"));

                    $state = (int)trim(shell_exec("cat /sys/class/power_supply/ac/online"));

                    $payload = ['command' =>'system_settings','action' =>'set_capacity','state' =>$state,'capacity' =>$now_capacity];
                    return $connection->send(json_encode($payload));
                }
                 /****************************** 电池状态 ******************************/

                 /****************************** 是否启用屏幕 ******************************/
                 if( @$data->action == 'lighting_form' )
                {

                    if( @$data->type == 1 ){
                        $enable_screen = @trim(shell_exec("ps | grep 'enable_screen' | grep -v grep | awk '{print $1}'"));
                        if($enable_screen == ''){
                            @shell_exec("enable_screen 100 > /dev/null 2>&1 &");
                        }
                    }else{
                        @shell_exec("ps | grep 'enable_screen' | grep -v grep | awk '{print $1}' | xargs kill -9");
                    }

                }
                 /****************************** 是否启用屏幕 ******************************/

                 /****************************** HDMI检测 ******************************/
                if( @$data->action == 'get_hdmi_status' )
                {

                    $hdmi_state = trim(shell_exec("cat /sys/class/switch/hdmi/state"));

                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_hdmi_status\",\"hdmi_join\":\"$hdmi_state\"}";

                    return $connection->send($new_message);

                 }
                 /****************************** HDMI检测 ******************************/

                 /****************************** SD检测 ******************************/
                if( @$data->action == 'get_sd_dev' )
                {

                    if( file_exists('/dev/mmcblk0') )
                    {

                        $ram = trim(shell_exec("fdisk -l | grep 'Disk /dev/mmcblk0' | awk -F ':' '{print $2}' | cut -d',' -f1"));
                        $new_message = "{\"command\":\"system_settings\",\"action\":\"set_sd_dev\",\"dev_id\":\"mmcblk0\",\"ram\":\"$ram\"}";

                        return $connection->send($new_message);

                    }else
                    {

                        $new_message = "{\"command\":\"system_settings\",\"action\":\"set_sd_dev\",\"dev_id\":\"null\",\"ram\":\"null\"}";

                        return $connection->send($new_message);

                    }

                }
                 /****************************** SD检测 ******************************/ 

                 /****************************** 电压值 ******************************/
                if( @$data->action == 'get_voltage' )
                {
                    
                    $echo_file_test = '/tmp/test_success.txt';
                    if( file_exists($echo_file_test) ){
                        $success_test = trim(shell_exec("cat {$echo_file_test}"));
                    }else{
                        $file_test = '/proc/gpio_adc0';
                        if( file_exists($echo_file_test) ){
                            $success_test = trim(shell_exec("cat {$file_test}"));
                        }else{
                            $success_test = 0; 
                        }
                    }
                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_voltage\",\"voltage_value\":{$success_test}}";

                    return $connection->send($new_message);

                }
                 /****************************** 电压值 ******************************/ 

                /****************************** GPIO ******************************/
                if( @$data->action == 'get_gpio' )
                {
                    $file = "/tmp/gpio_stop.txt";
                    if( file_exists($file) ){
                        shell_exec("rm -rf /tmp/gpio_stop.txt");
                    }
                    $gpio_data = export_out_get();
                    $payload = ['command' =>'system_settings','action' =>'set_gpio','data' =>$gpio_data];
                    return $connection->send(json_encode($payload));

                }
                if( @$data->action == 'sets_gpio' )
                {

                    $wpi = $data->wpi;
                    $value = $data->value;
                    if($value==1){
                        $value_value = 0;
                    }else{
                        $value_value=1;
                    }
                    export_out_add($wpi,1,$value_value);
                    $payload = ['command' =>'system_settings','action' =>'set_gpio','wpi' =>$wpi,'value'=>$value];
                    return $connection->send(json_encode($payload));

                }
                if( @$data->action == 'sets_gpio_add' )
                {
                    $php_php = exec("which php");
                    shell_exec("echo 1 > /tmp/gpio_stop.txt");
                    shell_exec("{$php_php} /home/php/yeacreate/stop.php > /dev/null 2>&1 &");
                
                    $payload = ['command' =>'system_settings','action' =>'set_gpio_stop','status'=>1];
                    return $connection->send(json_encode($payload));
                }
                if( @$data->action == 'sets_gpio_stop' )
                {
                    shell_exec("rm -rf /tmp/gpio_stop.txt");
                    $payload = ['command' =>'system_settings','action' =>'set_gpio_stop','status'=>0];
                    return $connection->send(json_encode($payload));
                }
                 /****************************** GPIO ******************************/
            /****************************** 系统信息 ******************************/
            if( $data->action == 'get' )
            {
            // @shell_exec("ps | grep 'enable_screen' | grep -v grep | awk '{print $1}' | xargs kill -9");
            $system_name = @$system_name_arr ?? 'Yea Create Webos';
            
            $wifi_connect_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 status"))); //分割连接返回结果集

            $get_wifi_status = @trim(explode('=', @$wifi_connect_result[9])[1]); //获取连接wifi状态，

            if( $get_wifi_status == 'COMPLETED' )
            {

                $network_state = 1;

            }else
            {

                $network_state = 0;

            }
            $base_version = @trim(shell_exec("cat /home/base_version"));
            $system_ver = @$system_ver_arr ?? '1010';
            $system_ver = $system_ver.$base_version;

            ignore_user_abort();

            $t=0;

            $i=0;

            getcputime($t,$i);

            $system_time = getcurrentlytime();

            $up_time = uptime();

            $cpu_usage = sprintf("%.2f",(($i/$t)*100)/4).'%';

            $memory_usage = getmem().'%';

            $storage_usage = gethdd().'%';

            $new_message = "{\"command\":\"system_settings\",\"action\":\"set_system_info\",\"system_time\":\"$system_time\",\"up_time\":\"$up_time\",\"cpu_usage\":\"$cpu_usage\",\"memory_usage\":\"$memory_usage\",\"storage_usage\":\"$storage_usage\",\"system_ver\":\"$system_ver\",\"system_name\":\"$system_name\",\"network_state\":$network_state}";

            return $connection->send($new_message);

        }
        /****************************** 系统信息 ******************************/
        if( $data->action == 'get_wifi_status' )  //wifi状态
        {
            $wpa_status = (int)trim(shell_exec("ps | grep 'wpa_supplicant' | grep -v grep | awk '{print $1}'"));

            if( empty($wpa_status) )
            {

                $state = 0;

            }else
            {

                $state = 1;

            }

            $new_message = "{\"command\":\"system_settings\",\"action\":\"set_wifi_status\",\"state\":$state}";

            return $connection->send($new_message);

        }

        if( $data->action == 'wifi_switch' )  //wifi开关
        {

            $state = (int)$data->state;

            if( $state == 1 )
            {

                $wpa_status = (int)trim(shell_exec("ps | grep 'wpa_supplicant' | grep -v grep | awk '{print $1}'"));

                if( empty($wpa_status) )
                {

                    shell_exec("wpa_supplicant -B -i wlan0 -c /etc/wpa_supplicant.conf");

                }

            }

            if( $state == 0 )
            {

                $wpa_status = (int)trim(shell_exec("ps | grep 'wpa_supplicant' | grep -v grep | awk '{print $1}'"));

                if( empty($wpa_status) )
                {

                }else
                {

                    shell_exec("kill -9 $wpa_status");

                }

            }

            $new_message = "{\"command\":\"system_settings\",\"action\":\"set_wifi_switch\",\"state\":$state}";

            return $connection->send($new_message);


        }

        if( $data->action == 'get_wifi_scan' )  //扫描wifi列表
        {
            $wlan0_scan_file = "/tmp/wpa_supplicant.pid";
                if( !file_exists($wlan0_scan_file) ){
                    $wlan0_ok = trim(shell_exec("wpa_cli -i wlan0 -p /var/run/wpa_supplicant scan"));
                    if($wlan0_ok != 'OK'){
                        shell_exec("/etc/init.d/S81wpa_supplicant restart");
                        sleep(1);

                    }
                }
            shell_exec("wpa_cli -i wlan0 scan");

            $wifi_scan_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 scan_result")));

            unset($wifi_scan_result[0]);

            $new_arr = [];

            foreach($wifi_scan_result as $key => $value)
            {

                $data = [];
                $wpa = explode("\t", $value);
                if( !empty($wpa[4]) )
                {

                    $wpa_cli_a = strpos($wpa[4],'\\');;
                    if( $wpa_cli_a === false ){
                        $data['bssid'] = $wpa[0]; //路由器mac

                        $data['frequency'] = $wpa[1];  //频率

                        $data['signal'] = $wpa[2];  //信号强度

                        if( @(explode('[ESS]', $wpa[3])[0]) == '' )
                        {

                            $data['flags'] = 1;  //1代表开放

                        }else
                        {

                            $data['flags'] = 0;  //0代表加密

                        }
                        $wpa_status = '';
                        $network_id = '';

                        $wpa_status_data = trim(shell_exec("wpa_cli -i wlan0 list_network | grep -w '".$wpa[4]."' | awk -Fany '{print $1}'"));

                        if( empty($wpa_status_data) )
                        {

                          $data['network_id'] = '';

                        }else
                        {

                          $wpa_arr = explode('\t', $wpa_status_data);

                          $data['network_id'] = (int)$wpa_arr[0];

                        }

                        $data['ssid'] = $wpa[4];

                        $new_arr[] = $data;
                    }


                }

            }

            $json = json_encode($new_arr);
            $new_message = "{\"command\":\"wifi_scan\",\"action\":\"set_wifi_scan\",\"data\":$json}";

            return $connection->send($new_message);

        }

        if( $data->action == 'connect_wifi' )  //连接wifi
        {

            $ssid = $data->ssid;

            $password = $data->password;


            $add_network = (int)trim(shell_exec("wpa_cli -i wlan0 add_network"));  //添加一个网络连接

            if( $add_network >= 0 )
            {

                $set_ssid = trim(shell_exec("wpa_cli -i wlan0 set_network $add_network ssid '\"$ssid\"'")); //设置网络连接ssid
                if( $set_ssid == 'OK' )
                {

                    if( empty($password) )
                    {
                        $set_psk = trim(shell_exec("wpa_cli -i wlan0 set_network $add_network key_mgmt NONE"));

                    }else
                    {

                        $set_psk = trim(shell_exec("wpa_cli -i wlan0 set_network $add_network psk '\"$password\"'"));

                    }

                    if( $set_psk == 'OK' )
                    {

                        $select_network = trim(shell_exec("wpa_cli -i wlan0 -p /var/run/wpa_supplicant select_network $add_network"));

                        shell_exec("wpa_cli -i wlan0 save_config");

                        if( $select_network == 'OK' )
                        {

                            sleep(2);
                            shell_exec("wpa_cli -i wlan0 status");
                            sleep(2);
                            shell_exec("wpa_cli -i wlan0 status");
                            sleep(2);

                            $wifi_connect_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 status"))); //分割连接返回结果集


                            $wifi_result = trim(shell_exec("wpa_cli -i wlan0 status")); //分割连接返回结果集

                            $get_wifi_ssid = @trim(explode('=', @$wifi_connect_result[2])[1]); //获取ssid

                            $get_wifi_bssid = @trim(explode('=', @$wifi_connect_result[0])[1]); //获取bssid,路由器mac

                            $get_wifi_status = @trim(explode('=', @$wifi_connect_result[9])[1]); //获取连接wifi状态，

                            $get_wifi_ip = @trim(explode('=', @$wifi_connect_result[10])[1]); //获取设备ip地址，

                            $get_wifi_address = @trim(explode('=', @$wifi_connect_result[12])[1]); //获取设备mac地址

                            if( $get_wifi_ssid == $data->ssid && $get_wifi_status == 'COMPLETED'  )
                            {

                                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":1,\"ssid\":\"$get_wifi_ssid\",\"bssid\":\"$get_wifi_bssid\",\"ip\":\"$get_wifi_ip\",\"address\":\"$get_wifi_address\"}";

                                return $connection->send($new_message);

                            }else
                            {

                                shell_exec("wpa_cli -i wlan0 remove_network $add_network");
                                shell_exec("wpa_cli -i wlan0 save_config");

                                if( empty($password) )
                                {

                                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";
                                }else
                                {

                                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":2}";

                                }

                                return $connection->send($new_message);

                            }


                        }else
                        {

                            shell_exec("wpa_cli -i wlan0 remove_network $add_network");
                            shell_exec("wpa_cli -i wlan0 save_config");

                            $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                            return $connection->send($new_message);

                        }


                    }else
                    {

                        shell_exec("wpa_cli -i wlan0 remove_network $add_network");
                        shell_exec("wpa_cli -i wlan0 save_config");

                        $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                        return $connection->send($new_message);

                    }


                }else
                {

                    shell_exec("wpa_cli -i wlan0 remove_network $add_network");
                    shell_exec("wpa_cli -i wlan0 save_config");

                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                    return $connection->send($new_message);

                }

            }else
            {

                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                 return $connection->send($new_message);

            }


        }

        if( $data->action == 'old_wifi_connect' ) // 旧wifi连接
        {

            $network_id = (int)$data->network_id;

            $result = trim(shell_exec("wpa_cli -i wlan0 select_network $network_id"));

            shell_exec("wpa_cli -i wlan0 save_config");

            if( $result == 'OK' )
            {

                sleep(2);
                shell_exec("wpa_cli -i wlan0 status");
                sleep(2);
                shell_exec("wpa_cli -i wlan0 status");
                sleep(2);

                $wifi_connect_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 status"))); //分割连接返回结果集

                $get_wifi_ssid = @trim(explode('=', @$wifi_connect_result[2])[1]); //获取ssid

                $get_wifi_bssid = @trim(explode('=', @$wifi_connect_result[0])[1]); //获取bssid,路由器mac

                $get_wifi_status = @trim(explode('=', @$wifi_connect_result[9])[1]); //获取连接wifi状态，

                $get_wifi_ip = @trim(explode('=', @$wifi_connect_result[10])[1]); //获取设备ip地址，

                $get_wifi_address = @trim(explode('=', @$wifi_connect_result[12])[1]); //获取设备mac地址

                if( $get_wifi_ssid == $data->ssid && $get_wifi_status == 'COMPLETED'  )
                {

                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":1,\"ssid\":\"$get_wifi_ssid\",\"bssid\":\"$get_wifi_bssid\",\"ip\":\"$get_wifi_ip\",\"address\":\"$get_wifi_address\"}";

                    return $connection->send($new_message);

                }else
                {

                    shell_exec("wpa_cli -i wlan0 remove_network $network_id");
                    shell_exec("wpa_cli -i wlan0 save_config");

                    $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                    return $connection->send($new_message);

                }

            }elseif( $result == 'FAIL' )
            {

                shell_exec("wpa_cli -i wlan0 remove_network $network_id");
                shell_exec("wpa_cli -i wlan0 save_config");

                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                return $connection->send($new_message);

            }else
            {

                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_connect_wifi\",\"state\":0}";

                return $connection->send($new_message);

            }

        }

        if( @$data->action == 'wifi_auto_connect' ) // 无线自动连接
        {

            $wifi_connect_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 -p /var/run/wpa_supplicant status"))); //分割连接返回结果集

            $get_wifi_ssid = @trim(explode('=', @$wifi_connect_result[2])[1]); //获取ssid

            $get_wifi_bssid = @trim(explode('=', @$wifi_connect_result[0])[1]); //获取bssid,路由器mac

            $get_wifi_status = @trim(explode('=', @$wifi_connect_result[9])[1]); //获取连接wifi状态，

            $get_wifi_ip = @trim(explode('=', @$wifi_connect_result[10])[1]); //获取设备ip地址，

            $get_wifi_address = @trim(explode('=', @$wifi_connect_result[12])[1]); //获取设备mac地址

            if( $get_wifi_status == 'COMPLETED'  )
            {

                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_auto_connect\",\"state\":1,\"ssid\":\"$get_wifi_ssid\",\"bssid\":\"$get_wifi_bssid\",\"ip\":\"$get_wifi_ip\",\"address\":\"$get_wifi_address\"}";

                 return $connection->send($new_message);

            }else
            {

                $network_arr = @$data->network_id;

                if( count($network_arr) > 0 )
                {

                    foreach ($network_arr as $key => $value)
                    {

                        $result = trim(shell_exec("wpa_cli -i wlan0 select_network $value"));

                        shell_exec("wpa_cli -i wlan0 save_config");

                        if( $result == 'OK' )
                        {

                            sleep(2);

                            $wifi_connect_result = explode("\n", trim(shell_exec("wpa_cli -i wlan0 -p /var/run/wpa_supplicant status"))); //分割连接返回结果集

                            $get_wifi_ssid = @trim(explode('=', @$wifi_connect_result[2])[1]); //获取ssid

                            $get_wifi_bssid = @trim(explode('=', @$wifi_connect_result[0])[1]); //获取bssid,路由器mac

                            $get_wifi_status = @trim(explode('=', @$wifi_connect_result[9])[1]); //获取连接wifi状态，

                            $get_wifi_ip = @trim(explode('=', @$wifi_connect_result[10])[1]); //获取设备ip地址，

                            $get_wifi_address = @trim(explode('=', @$wifi_connect_result[12])[1]); //获取设备mac地址

                            if( $get_wifi_ssid == $data->ssid && $get_wifi_status == 'COMPLETED'  )
                            {

                                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_auto_connect\",\"state\":1,\"ssid\":\"$get_wifi_ssid\",\"bssid\":\"$get_wifi_bssid\",\"ip\":\"$get_wifi_ip\",\"address\":\"$get_wifi_address\"}";

                                return $connection->send($new_message);

                            }else
                            {

                                shell_exec("wpa_cli -i wlan0 remove_network $value");
                                shell_exec("wpa_cli -i wlan0 save_config");

                                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_auto_connect\",\"state\":0}";

                                return $connection->send($new_message);

                            }

                        }elseif( $result == 'FAIL' )
                        {

                            shell_exec("wpa_cli -i wlan0 remove_network $value");
                            shell_exec("wpa_cli -i wlan0 save_config");

                            $new_message = "{\"command\":\"system_settings\",\"action\":\"set_auto_connect\",\"state\":0}";

                            return $connection->send($new_message);

                        }else
                        {

                            $new_message = "{\"command\":\"system_settings\",\"action\":\"set_auto_connect\",\"state\":0}";

                            return $connection->send($new_message);

                        }
                        # code...
                    }

                }

            }


        }

        if( $data->action == 'wifi_forget' ) // 忘记wifi
        {

            $network_id = (int)$data->network_id;

            $result = trim(shell_exec("wpa_cli -i wlan0 remove_network $network_id"));

            shell_exec("wpa_cli -i wlan0 save_config");

            if( $result == 'OK' )
            {

                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_wifi_forget\",\"state\":1}";

                return $connection->send($new_message);

            }else
            {

                $new_message = "{\"command\":\"system_settings\",\"action\":\"set_wifi_forget\",\"state\":0}";

                return $connection->send($new_message);

            }

        }
        # code...
        break;
        
        default:
        return $connection->send($message);
            # code...
            break;
    }


};

// 客户端连接关闭时把连接从主题映射数组里删除
$yeacweboswebsocket->onClose = function($connection)
{
    // Timer::del($connection->start_id);
};

function export_out_add($id,$status=0,$value=0){
    if($status){
        pinmode($id,1);
        digitalwrite($id,$value);
    }else{
        pinmode($id,1);
        digitalwrite($id,1);
    }
}
function export_out_get(){
    $array = array();
    $value = 0;
    $wpi_8 = 8;
    $wpi_9 = 9;
    $wpi_16 = 16;
    $wpi_15 = 15;
    $wpi_11 = 11;
    $wpi_10 = 10;
    $wpi_14 = 14;
    $wpi_13 = 13;
    $wpi_12 = 12;
    $wpi_30 = 30;
    $wpi_31 = 31;
    export_out_add($wpi_8);
    export_out_add($wpi_9);
    export_out_add($wpi_16);
    export_out_add($wpi_15);
    export_out_add($wpi_11);
    export_out_add($wpi_10);
    export_out_add($wpi_14);
    export_out_add($wpi_13);
    export_out_add($wpi_12);
    export_out_add($wpi_30);
    export_out_add($wpi_31);
    $array[] = array("wpi"=>0,"name"=>'5v',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'5v',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'3.3v',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'GND',"value"=>$value);
    $array[] = array("wpi"=>$wpi_8,"name"=>'GPIO8A4',"value"=>$value);
    $array[] = array("wpi"=>$wpi_9,"name"=>'GPIO8A5',"value"=>$value);
    $array[] = array("wpi"=>$wpi_16,"name"=>'GPIO5B0',"value"=>$value);
    $array[] = array("wpi"=>$wpi_15,"name"=>'GPIO5B1',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'GND',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'GND',"value"=>$value);
    $array[] = array("wpi"=>$wpi_11,"name"=>'GPIO8A3',"value"=>$value);
    $array[] = array("wpi"=>$wpi_10,"name"=>'GPIO8A7',"value"=>$value);
    $array[] = array("wpi"=>$wpi_14,"name"=>'GPIO8A6',"value"=>$value);
    $array[] = array("wpi"=>$wpi_13,"name"=>'GPIO8B0',"value"=>$value);
    $array[] = array("wpi"=>$wpi_12,"name"=>'GPIO8B1',"value"=>$value);
    $array[] = array("wpi"=>$wpi_30,"name"=>'GPIO7B2',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'ADC_IN0',"value"=>$value);
    $array[] = array("wpi"=>$wpi_31,"name"=>'GPIO7A1',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'UART2TX',"value"=>$value);
    $array[] = array("wpi"=>0,"name"=>'UART2RX',"value"=>$value);
    

    return $array;
}


function gethdd()
{
    $hddTotal = disk_total_space(".");
    $hddFree = disk_free_space(".");
    $hddUsed = $hddTotal - $hddFree;
    $hddPercent = (floatval($hddTotal)!=0) ? round($hddUsed/$hddTotal * 100, 2) : 0;
    return $hddPercent;
}

function getmem()
{
    $str = file_get_contents("/proc/meminfo");
    preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $mems);
    preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);
    $mtotal = $mems[1][0] * 1024;
    $mfree = $mems[2][0] * 1024;
    $mbuffers = $buffers[1][0] * 1024;
    $mcached = $mems[3][0] * 1024;
    $stotal = $mems[4][0] * 1024;
    $sfree = $mems[5][0] * 1024;
    $mused = $mtotal - $mfree;
    $sused = $stotal - $sfree;
        $mrealused = $mtotal - $mfree - $mcached - $mbuffers; //真实内存使用
        $mempercent = (floatval($mtotal) != 0) ? number_format(round($mrealused/$mtotal * 100, 1),2) : 0; //真实内存使用率
        return number_format($mempercent,2);
}

function getcurrentlytime()
{

    return  date("Y-m-d H:i");

}

function uptime()
{
    $str = file_get_contents("/proc/uptime");
    $str = explode(" ", $str);
    $str = trim($str[0]); 
    $min = $str / 60; 
    $hours = $min / 60; 
    $days = floor($hours / 24); 
    $hours = floor($hours - ($days * 24)); 
    $min = floor($min - ($days * 60 * 24) - ($hours * 60)); 
    if ($days !== 0) $uptime = $days."day"; 
    if ($hours !== 0) $uptime .= $hours."hour"; 
    $uptime .= $min."Minutes";
    return $uptime;
}

function getcputime(&$total,&$idle)
{

    $str = file_get_contents("/proc/stat");
    $mode = "/(cpu)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)/";
    preg_match_all($mode, $str, $cpu);
    $total=$cpu[2][0]+$cpu[3][0]+$cpu[4][0]+$cpu[5][0]+$cpu[6][0]+$cpu[7][0]+$cpu[8][0]+$cpu[9][0];
    $idle=$cpu[2][0]+$cpu[3][0]+$cpu[4][0]+$cpu[6][0]+$cpu[7][0]+$cpu[8][0]+$cpu[9][0];

}

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
