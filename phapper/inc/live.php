<?php

namespace Phapper;


class Live {
    private $phapper;
    private $thread_id;

    public function __construct($phapper, $thread_id) {
        $this->phapper = $phapper;
        $this->thread_id = $thread_id;
    }

    public function update($body) {
        $params = array(
            'body' => $body
        );

        $response = $this->phapper->apiCall("/api/live/$this->thread_id/update", 'POST', $params);

        var_dump($response);
    }
}