<?php

namespace Phapper;

require_once('config.php');
require_once('inc/oauth2.php');
require_once('inc/reddit.php');
require_once('inc/live.php');


class PhAWR {
    private $config;
    private $oauth2;
    private $reddit;
    private $live;

    public function __construct() {
        $this->config = new Config();
        $this->oauth2 = new OAuth2($this->config);
        $this->reddit = new reddit();
        $this->live = new Live();
    }

    public function printIt() {
        echo $this->reddit->foo."\n";
    }
}