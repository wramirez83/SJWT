<?php

$len = 32;
$secret = bin2hex(random_bytes($len));
echo $secret;
