<?php

require_once("phapper/phapper.php");
$r = new \Phapper\Phapper();

$thread = $r->createLiveThread("Testing", "I'm still going!");
var_dump($thread->update("You think I'm dead? Nah."));