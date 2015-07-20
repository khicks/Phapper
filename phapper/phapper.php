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
    /**
     * Gets information about the current user's account.
     * @return mixed|null An object representing the current user. Null if failed.
     */
    public function getMe() {
        $response = $this->apiCall("/api/v1/me");
        var_dump($response);

        if (!isset($response->id)) {
            return null;
        }

        $this->user_id = $response->id;
        return $response;
    }

    /**
     * Gets karma breakdown of current user.
     * @return array|null Array of objects representing subreddits and corresponding karma values. Null if failed.
     */
    public function getMyKarmaBreakdown() {
        $response = $this->apiCall("/api/v1/me/karma");

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Gets current user's site preferences.
     * @return mixed|null Object representing user's preferences. Null if failed.
     */
    public function getMyPrefs() {
        $response = $this->apiCall("/api/v1/me/prefs");

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Gets current user's trophies.
     * @return array|null Array containing user's trophy objects. Null if failed.
     */
    public function getMyTrophies() {
        $response = $this->apiCall("/api/v1/me/trophies");

        if (isset($response->error)) {
            return null;
        }

        return $response->data->trophies;
    }

    /**
     * Gets a list of the current user's friends.
     * @return mixed|null Listing of current user's friend objects. Null if failed.
     */
    public function getMyFriends() {
        $response = $this->apiCall("/api/v1/me/friends");

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }


    /**
     * Gets a list of the current user's blocked users.
     * @return mixed|null Listing of current user's blocked users. Null if failed.
     */
    public function getBlockedUsers() {
        $response = $this->apiCall("/prefs/blocked");

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
    /**
     * Comments on an object.
     * @param $parent Thing ID of parent object on which to comment. Could be link, text post, or comment.
     * @param $text Comment text.
     * @param bool|false $distinguish Whether or not it should be mod distinguished (for modded subreddits only).
     * @return string|null Comment ID if success. Null if failed.
     */
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

    /**
     * Deletes a post or comment.
     * @param $thing_id Thing ID of object to delete. Could be link, text post, or comment.
     */
    public function delete($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $this->apiCall("/api/del", 'POST', $params);
    }


    /**
     * Edits the text of a comment or text post.
     * @param $thing_id Thing ID of text object to edit. Could be text post or comment.
     * @param $text New text to replace the old.
     * @return mixed|null Object of thing that was just edited. Null if failed (such as editing a link post).
     */
    public function editText($thing_id, $text) {
        $params = array(
            'api_type' => 'json',
            'text' => $text,
            'thing_id' => $thing_id
        );

        $response = $this->apiCall("/api/editusertext", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->json->data->things[0]->data;
    }

    /**
     * Hides a post from user's listings.
     * @param $thing_ids String or array of thing ID's of links to hide.
     * @return bool|null Returns true if success. Null if failed.
     */
    public function hide($thing_ids) {
        if (is_array($thing_ids)) {
            $thing_ids = implode(',', $thing_ids);
        }

        $params = array(
            'id' => $thing_ids
        );

        $response = $this->apiCall("/api/hide", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return true;
    }

    /**
     * Gives a listing of information on objects.
     * @param $thing_ids String or array of single or multiple thing ID's.
     * @return mixed Listing object if success. Null if failed.
     */
    public function getInfo($thing_ids) {
        if (is_array($thing_ids)) {
            $thing_ids = implode(',', $thing_ids);
        }

        $params = array(
            'id' => $thing_ids
        );

        $response = $this->apiCall("/api/info", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Marks a post as NSFW.
     * @param $thing_id Thing ID of post to mark as NSFW.
     * @return bool|null Returns true of success. Null if failed.
     */
    public function markNSFW($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $response = $this->apiCall("/api/marknsfw", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return true;
    }

    public function getMoreChildren($children) {
        // TODO
    }

    /**
     * Reports a post, comment, or message.
     * @param $thing_id Thing ID of object to report.
     * @param null $reason The reason for the report. Must be <100 characters.
     * @return mixed Array of errors. Length of 0 if successful.
     */
    public function report($thing_id, $reason = null) {
        $params = array(
            'api_type' => 'json',
            'reason' => $reason,
            'thing_id' => $thing_id
        );

        $response = $this->apiCall("/api/report", 'POST', $params);

        return $response->json->errors;
    }

    public function save($thing_id, $category = null) {
        
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