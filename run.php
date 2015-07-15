<?php

require_once("phapper/phapper.php");
$r = new \Phapper\Phapper();

$live = $r->createLiveThread('Derp');
var_dump($live);
$live->update("Oh yeahhh..... It works! :D");