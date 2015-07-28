<?php

namespace Phapper;

class RateLimiter {
    private $enabled;
    private $interval;
    private $last_request;

    public function __construct($enabled = true, $interval = 2) {
        $this->enabled = $enabled;
        $this->interval = $interval;
        $this->last_request = microtime(true) * 10000;
    }

    public function enable() {
        $this->enabled = true;
    }

    public function disable() {
        $this->enabled = false;
    }

    public function setInterval($interval) {
        $this->interval = $interval;
    }

    public function wait() {
        $now = microtime(true) * 10000;
        $wait_until = $this->last_request + ($this->interval*10000);

        if ($this->enabled && $now < $wait_until) {
            usleep(($wait_until - $now) * 100);
        }

        $this->last_request = microtime(true) * 10000;
    }

}