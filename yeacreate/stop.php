<?php
$php_php = exec("which php");
shell_exec("{$php_php} /home/php/yeacreate/gpio.php > /dev/null 2>&1 &");