<?php

require_once("phapper/phapper.php");
$r = new \Phapper\Phapper();

var_dump($r->setModeratorPermissions('rotorcowboy', 'rotorcowboy2', true));
