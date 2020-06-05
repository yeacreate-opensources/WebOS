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
# gpio.php
# http://yeacreate.com
# gpio files
################################################################################
 */
wiringpisetup();

function export_out_add($id,$status=0,$value=0){
    if($status){
        pinmode($id,1);
        digitalwrite($id,$value);
    }else{
        pinmode($id,1);
        digitalwrite($id,1);
    }
}
$array_add = array(8,9,16,15,11,10,14,13,12,30,31);

$timeStop = date('Y-m-d H:i:s',strtotime("+10 seconds"));
$php_php = @trim(str_replace(PHP_EOL,'',shell_exec("which php")));
while( true ){
	if( strtotime(date("Y-m-d H:i:s")) >= strtotime($timeStop) )
    {
        shell_exec("{$php_php} /home/php/yeacreate/gpio.php > /dev/null 2>&1 &");
        break;
    }
    $file = "/tmp/gpio_stop.txt";
    if( !file_exists($file) ){
    	break;
    }
	foreach ($array_add as $key => $value) {
	    export_out_add($value,1,0);
	    usleep(30000);
	    export_out_add($value,1,1);
	    usleep(20000);
	}
}