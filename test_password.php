<?php
$hash = '$2y$13$E6cnSsRe60TKrlxYo7apquc86RNpfZrJwuwlLeqYaVY0E9HuXCBai';
$plain = 'Test123!';

var_dump(password_verify($plain, $hash));
