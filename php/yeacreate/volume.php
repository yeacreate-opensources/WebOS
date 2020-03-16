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
# volume.php
# http://yeacreate.com
# run with command volume
# php volume.php + or php volume.php 1
################################################################################
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use WebSocket\Client;
use WebSocket\{ConnectionException,BadOpcodeException,BadUriException};

	$volume = 0;
	$volume_size = isset($argv[1]) ? "{$argv[1]}" : '';
	$get_current = explode('%', shell_exec("amixer get DAC | grep 'Right:' | awk -F '[][]' '{ print $2 }'"))[0];
	if( $volume_size == '+' ){
		$get_current_a = $get_current+2;
		if($get_current_a >= 100){
			exec("amixer -q set DAC '100%'");
		}else{
			if($get_current_a <= 61){
				exec("amixer -q set DAC '62%'");
			}else{
				$get_current_a = $get_current_a.'%';
				exec("amixer -q set DAC '{$get_current_a}'");
			}
		}
		$volume = 1;
	}else if( $volume_size == '-' ){
		$get_current_b = $get_current-2;
		if( $get_current_b <= 61 ){
			exec("amixer -q set DAC '0'");
		}else{
			$get_current_b = $get_current_b.'%';
			exec("amixer -q set DAC '$get_current_b'");
		}
		$volume = 1;
	}
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


