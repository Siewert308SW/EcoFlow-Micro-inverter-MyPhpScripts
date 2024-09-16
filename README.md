# EcoFlow-Micro-inverter-MyPhpScripts
My php scripts to control my EcoFlow Micro-inverter which is used as home battery inverter.

Explaination, details and how to use will be available later.

Raspberry PI cron:
<code>
x/5 x x x  sudo php /home/siewert/scripts/thuisbatterij/ecoflow_get_serial.php
x/2 x x x sudo php /home/siewert/scripts/thuisbatterij/ecoflow_charging.php
x x x x x sudo php /home/siewert/scripts/thuisbatterij/ecoflow_baseload.php
x x x x x (sleep 10; sudo php /home/siewert/scripts/thuisbatterij/ecoflow_baseload.php)
x x x x x (sleep 20; sudo php /home/siewert/scripts/thuisbatterij/ecoflow_baseload.php)
x x x x x (sleep 30; sudo php /home/siewert/scripts/thuisbatterij/ecoflow_baseload.php)
x x x x x (sleep 40; sudo php /home/siewert/scripts/thuisbatterij/ecoflow_baseload.php)
x x x x x (sleep 50; sudo php /home/siewert/scripts/thuisbatterij/ecoflow_baseload.php)
</code>
