<?php

require_once("phapper/phapper.php");
$r = new \Phapper\Phapper();

var_dump($r->configureSubredditFlair('rotorcowboy', true, 'right', true, 'left', true));

