<?php

namespace Phapper;

require_once('config.php');
require_once('inc/oauth2.php');
require_once('inc/live.php');


class Phapper {
    private $config;
    private $oauth2;
    private $user_id;

    public function __construct() {
        $this->config = new Config();
        $this->oauth2 = new OAuth2($this->config);
    }

    //-----------------------------------------
    // Account
    //-----------------------------------------
    public function getMe() {
        $response = $this->apiCall("/api/v1/me");

        if (!isset($response->id)) {
            return null;
        }

        $this->user_id = $response->id;
        return $response;
    }

    public function getMyKarmaBreakdown() {
        $response = $this->apiCall("/api/v1/me/karma");

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    public function getMyPrefs() {
        $response = $this->apiCall("/api/v1/me/prefs");

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    public function getMyTrophies() {
        $response = $this->apiCall("/api/v1/me/trophies");

        if (isset($response->error)) {
            return null;
        }

        return $response->data->trophies;
    }

    public function getMyFriends() {
        $response = $this->apiCall("/api/v1/me/friends");

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    public function getBlockedUsers() {
        $response = $this->apiCall("/api/v1/me/blocked");

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    //-----------------------------------------
    // Flair
    //-----------------------------------------

    //-----------------------------------------
    // reddit gold
    //-----------------------------------------

    //-----------------------------------------
    // Links & comments
    //-----------------------------------------
    public function comment($parent, $text, $distinguish = false) {
        $params = array(
            'api_type' => 'json',
            'text' => $text,
            'thing_id' => $parent
        );

        $response = $this->apiCall("/api/comment", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        $id = $response->json->data->things[0]->data->name;
        if ($distinguish) {
            $this->distinguish($id, true);
        }

        return $id;
    }

    public function delete($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $response = $this->apiCall("/api/del", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    //-----------------------------------------
    // Listings
    //-----------------------------------------

    //-----------------------------------------
    // Live threads
    //-----------------------------------------
    public function createLiveThread($title, $description = null, $resources = null, $nsfw = false) {
        $params = array(
            'api_type' => 'json',
            'description' => $description,
            'nsfw' => ($nsfw) ? 'true':'false',
            'resources' => $resources,
            'title' => $title
        );

        $response = $this->apiCall('/api/live/create', 'POST', $params);

        if (isset($response->json->data->id)) {
            return new Live($this, $response->json->data->id);
        }
        return null;
    }

    public function attachLiveThread($thread_id) {
        return new Live($this, $thread_id);
    }

    //-----------------------------------------
    // Private messages
    //-----------------------------------------

    //-----------------------------------------
    // Moderation
    //-----------------------------------------

    //-----------------------------------------
    // Multis
    //-----------------------------------------

    //-----------------------------------------
    // Search
    //-----------------------------------------

    //-----------------------------------------
    // Subreddits
    //-----------------------------------------
    public function distinguish($thing_id, $how = true) {
        $params = array(
            'api_type' => 'json',
            'how' => ($how) ? 'yes':'no',
            'id' => $thing_id
        );

        $response = $this->apiCall("/api/distinguish", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    //-----------------------------------------
    // Users
    //-----------------------------------------

    //-----------------------------------------
    // Wiki
    //-----------------------------------------

    public function apiCall($path, $method = 'GET', $params = null) {
        $url = $this->config->base_url.$path;
        echo $url."\n";

        $token = $this->oauth2->getAccessToken();

        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = 5;
        $options[CURLOPT_TIMEOUT] = 10;
        $options[CURLOPT_USERAGENT] = $this->config->user_agent;
        $options[CURLOPT_CUSTOMREQUEST] = $method;
        $options[CURLOPT_HTTPHEADER] = array(
            "Authorization: ".$token['token_type']." ".$token['access_token']
        );

        if (isset($params)) {
            if ($method == 'GET') {
                $url .= '?'.http_build_query($params);
            }
            else {
                $options[CURLOPT_POSTFIELDS] = $params;
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response_raw = curl_exec($ch);
        $response = json_decode($response_raw);
        curl_close($ch);

        return $response;
    }
}