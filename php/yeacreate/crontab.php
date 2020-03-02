<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use WebSocket\Client;
use WebSocket\{ConnectionException,BadOpcodeException,BadUriException};
	
	$json = "/tmp/_home_php_yeacreate_start.json";
	$txt_file = "/tmp/_home_php_yeacreate_start.txt";
	if( !file_exists($json) ){
		shell_exec("echo 1 > {$json}");
	}
	if( file_exists($txt_file) ){
		exit();
	}
	$success_json = trim(shell_exec("cat {$json}"));
	if($success_json == 8){
		shell_exec("echo 6 > {$txt_file}");
		exit();
	}else{
		$success_json = $success_json+1;
	}
	$payload = "{\"command\":\"system_crontab\",\"action\":\"get_add_crontab\"}";
	$i = 0;
	while ( true ){
		$i++;
		sleep(2);
		try
	    {
	      $client = new Client($SIGNALING_ADDRESS_WS);
	      $client->send($payload);
	    }
	    catch(ConnectionException $connectionexception){
	    	shell_exec("/usr/bin/php /home/php/yeacreate/start.php restart -d");
	    	shell_exec("echo 1 > {$txt_file}");
	    	break;
	    }
	    catch(BadOpcodeException $badopcodeexception ){
	    	shell_exec("/usr/bin/php /home/php/yeacreate/start.php restart -d");
	    	shell_exec("echo 2 > {$txt_file}");
	    	break;
	    }
	    catch(BadUriException $baduriexception ){
	    	shell_exec("/usr/bin/php /home/php/yeacreate/start.php restart -d");
	    	shell_exec("echo 3 > {$txt_file}");
	    	break;
	    }
	    catch(Exception $e){
	    	shell_exec("/usr/bin/php /home/php/yeacreate/start.php restart -d");
	    	shell_exec("echo 4 > {$txt_file}");
	    	break;
	    }
	    if( $i>=8 ){
	        $i = 0;
	        shell_exec("echo {$success_json} > {$json}");
	        shell_exec("/usr/bin/php /home/php/yeacreate/crontab.php > /dev/null 2>&1 &");
	        break;
	    }
	}

