<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use WebSocket\Client;
use WebSocket\{ConnectionException,BadOpcodeException,BadUriException};

	$volume = 0;
	$volume_size = isset($argv[1]) ? "{$argv[1]}" : '';
	$get_current = explode('%', shell_exec("amixer get DAC | grep 'Right:' | awk -F '[][]' '{ print $2 }'"))[0];
	if( $volume_size == '+' ){
		$get_current_a = $get_current+2;
		if($get_current_a > 100){
			exec("amixer -q set DAC '100%'");
		}else{
			exec("amixer -q set DAC '2%+'");
		}
		$volume = 1;
	}else if( $volume_size == '-' ){
		$get_current_b = $get_current-2;
		if( $get_current_b < 0 ){
			exec("amixer -q set DAC '0'");
		}else{
			exec("amixer -q set DAC '2%-'");
		}
		$volume = 1;
	}
    // $size_data  = str_split($volume_size,2);
	if( $volume == 1 ){
	    $payload = "{\"command\":\"system_settings\",\"action\":\"get_voice\"}";
	    try
        {
          $client = new Client($SIGNALING_ADDRESS_WS);
          $client->send($payload);
        }
        catch(ConnectionException $connectionexception){}
        catch(BadOpcodeException $badopcodeexception ){}
        catch(BadUriException $baduriexception ){}
        catch(Exception $e){}
	}


