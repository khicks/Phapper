<?php

namespace Phapper;


class OAuth2 {
    private $config;
    private $access_token;
    private $token_type;
    private $expiration;
    private $scope;

    public function __construct($config) {
        $this->config = $config;
        $this->requestAccessToken();
    }

    public function getAccessToken() {
        if (!(isset($this->access_token) && isset($this->token_type) && time()<$this->expiration)) {
            $this->requestAccessToken();
        }

        return array(
            'access_token' => $this->access_token,
            'token_type' => $this->token_type
        );
    }

    private function requestAccessToken() {
        $url = "https://www.reddit.com/api/v1/access_token";
        $params = array(
            'grant_type' => 'password',
            'username' => $this->config->username,
            'password' => $this->config->password
        );

        $options[CURLOPT_USERAGENT] = $this->config->user_agent;
        $options[CURLOPT_USERPWD] = $this->config->app_id.':'.$this->config->app_secret;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = 5;
        $options[CURLOPT_TIMEOUT] = 10;
        $options[CURLOPT_CUSTOMREQUEST] = 'POST';
        $options[CURLOPT_POSTFIELDS] = $params;

        $got_token = false;
        do {
            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            $response_raw = curl_exec($ch);
            $response = json_decode($response_raw);
            curl_close($ch);

            if (isset($response->access_token)) {
                $got_token = true;
            }
            else {
                echo "ERROR: Access token request failed. Check your credentials.\n";
                sleep(5);
            }
        } while (!$got_token);

        $this->access_token = $response->access_token;
        $this->token_type = $response->token_type;
        $this->expiration = time()+$response->expires_in;
        $this->scope = $response->scope;
    }
}