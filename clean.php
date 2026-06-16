<?php
$lines = file('c:\xampp\htdocs\waba\routes\web.php');
$clean = array_slice($lines, 0, 1507);
file_put_contents('c:\xampp\htdocs\waba\routes\web.php', implode('', $clean));
