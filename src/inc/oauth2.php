<?php

class PhapperOAuth2 {
    private $access_token;
    private $token_type;
    private $expiration;
    private $scope;

    public $username;
    private $password;
    private $app_id;
    private $app_secret;
    private $user_agent;
    private $endpoint;

    public function __construct($username, $password, $app_id, $app_secret, $user_agent, $endpoint) {
        $this->username = $username;
        $this->password = $password;
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->user_agent = $user_agent;
        $this->endpoint = $endpoint;

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
        $url = "{$this->endpoint}/api/v1/access_token";
        $params = array(
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password
        );

        $options[CURLOPT_USERAGENT] = $this->user_agent;
        $options[CURLOPT_USERPWD] = $this->app_id.':'.$this->app_secret;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = 5;
        $options[CURLOPT_TIMEOUT] = 10;
        $options[CURLOPT_CUSTOMREQUEST] = 'POST';
        $options[CURLOPT_POSTFIELDS] = $params;

        $response = null;
        $got_token = false;
        while (!$got_token) {
            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            $response_raw = curl_exec($ch);
            $response = json_decode($response_raw);
            curl_close($ch);

            if (isset($response->access_token)) {
                $got_token = true;
            }
            else {
                if (isset($response->error)) {
                    if ($response->error === "invalid_grant") {
                        throw new RedditAuthenticationException("Supplied reddit username/password are invalid or the threshold for invalid logins has been exceeded.", 1);
                    }
                    elseif ($response->error === 401) {
                        throw new RedditAuthenticationException("Supplied reddit app ID/secret are invalid.", 2);
                    }
                }
                else {
                    fwrite(STDERR, "WARNING: Request for reddit access token has failed. Check your connection.\n");
                    sleep(5);
                }
            }
        }

        $this->access_token = $response->access_token;
        $this->token_type = $response->token_type;
        $this->expiration = time()+$response->expires_in;
        $this->scope = $response->scope;
    }
}

class RedditAuthenticationException extends \Exception {
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__.": [{$this->code}]: {$this->message}";
    }
}