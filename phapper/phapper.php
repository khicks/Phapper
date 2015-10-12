<?php

namespace Phapper;

require_once('config.php');
require_once('inc/oauth2.php');
require_once('inc/ratelimiter.php');
require_once('inc/live.php');


class Phapper {
    /** @var OAuth2 */
    private $oauth2;

    /** @var RateLimiter */
    public $ratelimiter;

    private $user_agent;
    private $endpoint;

    private $debug;

    public function __construct($auth_type = 'oauth', $username = REDDIT_USERNAME, $password = REDDIT_PASSWORD, $app_id = REDDIT_APP_ID, $app_secret = REDDIT_APP_SECRET, $user_agent = PHAPPER_USER_AGENT, $endpoint = PHAPPER_OAUTH_ENDPOINT) {
        if ($auth_type == 'oauth') {
            $this->oauth2 = new OAuth2($username, $password, $app_id, $app_secret, $user_agent);
            $this->ratelimiter = new RateLimiter(true, 1);
        } elseif ($auth_type == 'cookie') {
            return null;
        }

        $this->user_agent = $user_agent;
        $this->endpoint = $endpoint;
        $this->debug = false;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
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
    /**
     * Retrieves a list of all assigned user flair in the specified subreddit.
     * @param string $subreddit Name of subreddit from which to retrieve flair list.
     * @param int $limit Upper limit of number of items to retrieve. Upper limit is 1000.
     * @param null $after Use 'next' attribute of previous call to retrieve next page.
     * @param null $before Retrieve only flairs that are higher than this user ID on the list.
     * @return mixed|null
     */
    public function getUserFlairList($subreddit, $limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/api/flairlist.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Adds or modifies a flair template in a subreddit.
     * @param string $subreddit Name of subreddit to add flair template.
     * @param string $type Specifies user or link flair template. One of 'link' or 'user'.
     * @param null $text Flair text.
     * @param null $css_class Flair CSS class.
     * @param bool|false $editable Whether or not to allow users to edit the flair's text when assigning it.
     * @param null $template_id The template ID of an existing flair to modify. If null, will add a new one.
     * @return mixed|null Returns response to API call on success. Null if failed.
     */
    public function addFlairTemplate($subreddit, $type, $text = null, $css_class = null, $editable = false, $template_id = null) {
        $params = array(
            'api_type' => 'json',
            'css_class' => $css_class,
            'flair_template_id' => $template_id,
            'flair_type' => ($type=='link') ? 'LINK_FLAIR' : 'USER_FLAIR',
            'text' => $text,
            'text_editable' => ($editable) ? 'true' : 'false'
        );

        $response = $this->apiCall("/r/$subreddit/api/flairtemplate", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Deletes all flair templates of the selected type from the selected subreddit.
     * @param string $subreddit Subreddit of flairs to clear.
     * @param string $type One of 'user' or 'link'.
     * @return mixed|null Returns result of API call on success. Null if failed or incorrect type.
     */
    public function clearFlairTemplates($subreddit, $type) {
        if ($type !== 'user' && $type !== 'link') {
            return null;
        }

        $params = array(
            'api_type' => 'json',
            'flair_type' => $type
        );

        $response = $this->apiCall("/r/$subreddit/api/clearflairtemplates", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Deletes the selected flair template from the specified subreddit.
     * @param string $subreddit Subreddit from which to delete flair template.
     * @param string $template_id ID of template to delete.
     * @return mixed|null Returns result of API call on success. Null if failed.
     */
    public function deleteFlairTemplate($subreddit, $template_id) {
        $params = array(
            'api_type' => 'json',
            'flair_template_id' => $template_id
        );

        $response = $this->apiCall("/r/$subreddit/api/deleteflairtemplate", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Deletes a user's flair from the specified subreddit.
     * @param string $subreddit Subreddit in which to delete user flair.
     * @param string $user Username of user whose flair to delete.
     * @return mixed|null Returns result of API call on success. Null if failed.
     */
    public function deleteUserFlair($subreddit, $user) {
        $params = array(
            'api_type' => 'json',
            'name' => $user
        );

        $response = $this->apiCall("/r/$subreddit/api/deleteflair", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Gets current flair and a list of possible flairs for the specified user in the specified subreddit.
     * Also useful for obtaining flair ID's.
     * @param string $subreddit Subreddit in which to view flair options.
     * @param string|null $user Username for whom to view selection. Defaults to current user.
     * @return mixed Returns API response.
     */
    public function getUserFlairSelector($subreddit, $user = null) {
        $params = array(
            'name' => $user,
        );

        $response = $this->apiCall("/r/$subreddit/api/flairselector", 'POST', $params);

        return $response;
    }

    /**
     * Gets current flair and a list of possible flairs for the specified link.
     * @param string $thing_id Thing ID of object to view flairs.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function getLinkFlairSelector($thing_id) {
        $params = array(
            'link' => $thing_id
        );

        $response = $this->apiCall("/api/flairselector", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Selects a user flair to use from the flair selection list.
     * @param string $subreddit Subreddit in which to select flair.
     * @param string $user Username of user to whom to apply flair. Mandatory, don't ask me why.
     * @param string|null $template_id Template ID of template to select. Null will remove the user's flair.
     * @param string|null $text Modified flair text, if allowed.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function selectUserFlair($subreddit, $user, $template_id = null, $text = null) {
        $params = array(
            'api_type' => 'json',
            'flair_template_id' => $template_id,
            'name' => $user,
            'text' => $text
        );

        $response = $this->apiCall("/r/$subreddit/api/selectflair", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Applies a link flair template from the selection list to a link.
     * @param string $thing_id Thing ID of link to apply flair.
     * @param string|null $template_id Template ID of template to apply to link. Null will remove the link's flair.
     * @param string|null $text Modified flair text, if allowed.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function selectLinkFlair($thing_id, $template_id = null, $text = null) {
        $params = array(
            'api_type' => 'json',
            'flair_template_id' => $template_id,
            'link' => $thing_id,
            'text' => $text
        );

        $response = $this->apiCall("/api/selectflair", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Assigns the selected user custom flair text and CSS class in the specified subreddit. Mods only.
     * @param string $subreddit Subreddit in which to assign flair.
     * @param string $user Username of user to assign flair.
     * @param string|null $text Custom flair text.
     * @param string|null $css_class Custom flair CSS class. If both fields are null, deletes flair.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function assignUserFlair($subreddit, $user, $text = null, $css_class = null) {
        $params = array(
            'api_type' => 'json',
            'css_class' => $css_class,
            'name' => $user,
            'text' => $text
        );

        $response = $this->apiCall("/r/$subreddit/api/flair", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Assigns the selected link custom flair text and CSS class in the specified subreddit. Mods only.
     * @param string $subreddit Subreddit in which to assign flair. Mandatory, don't ask me why.
     * @param string $thing_id Thing ID of link to assign flair.
     * @param string|null $text Custom flair text.
     * @param string|null $css_class Custom flair CSS class. If both fields are null, deletes flair.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function assignLinkFlair($subreddit, $thing_id, $text = null, $css_class = null) {
        $params = array(
            'api_type' => 'json',
            'css_class' => $css_class,
            'link' => $thing_id,
            'text' => $text
        );

        $response = $this->apiCall("/r/$subreddit/api/flair", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Selects whether or not to show the current user's flair in the selected subreddit.
     * @param string $subreddit Subreddit in which to enable or disable flair.
     * @param bool|true $show True to show flair. False to hide flair.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function showMyFlair($subreddit, $show = true) {
        $params = array(
            'api_type' => 'json',
            'flair_enabled' => ($show) ? 'true' : 'false'
        );

        $response = $this->apiCall("/r/$subreddit/api/setflairenabled", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Updates all options in a subreddit's flair configuration.
     * @param string $subreddit Subreddit in which to configure flair.
     * @param boolean $user_enabled Whether or not user flair is displayed.
     * @param string $user_position On which side to display user flair. One of 'left' or 'right'.
     * @param boolean $user_self_assign Whether or not users can select their own user flair.
     * @param string $link_position On which side to display links' flair. One of 'left', 'right', or 'none'.
     * @param boolean $link_self_assign Whether or not users can select their own links' flair.
     * @return mixed|null Returns API response on success. Null if failed.
     */
    public function configureSubredditFlair($subreddit, $user_enabled, $user_position, $user_self_assign, $link_position, $link_self_assign) {
        if (!($user_position == 'left' || $user_position == 'right') || !(is_null($link_position) || $link_position == 'none' || $link_position == 'left' || $link_position == 'right')) {
            return null;
        }

        if ($link_position == 'none') {
            $link_position = null;
        }

        $params = array(
            'api_type' => 'json',
            'flair_enabled' => ($user_enabled) ? 'true' : 'false',
            'flair_position' => $user_position,
            'flair_self_assign_enabled' => ($user_self_assign) ? 'true' : 'false',
            'link_flair_position' => $link_position,
            'link_flair_self_assign_enabled' => ($link_self_assign) ? 'true' : 'false'
        );

        $response = $this->apiCall("/r/$subreddit/api/flairconfig", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;

    }


    //-----------------------------------------
    // reddit gold
    //-----------------------------------------

    //-----------------------------------------
    // Links & comments
    //-----------------------------------------
    /**
     * Submits a new link post.
     * @param string $subreddit Subreddit in which to post link.
     * @param string $title Title of post.
     * @param string $url Link to post.
     * @param bool|true $send_replies Send comment replies to the current user's inbox. True to enable, false to disable.
     * @return mixed New post's thing ID if successful. Error object if failed.
     */
    public function submitLinkPost($subreddit, $title, $url, $send_replies = true) {
        $params = array(
            'api_type' => 'json',
            'extension' => 'json',
            'kind' => 'link',
            'resubmit' => 'true',
            'sendreplies' => ($send_replies) ? 'true' : 'false',
            'sr' => $subreddit,
            'title' => $title,
            'url' => $url
        );

        $response = $this->apiCall("/api/submit", 'POST', $params);

        if (isset($response->json->data->name)) {
            return $response->json->data->name;
        }

        return $response->json;
    }

    /**
     * Submits a new text post.
     * @param string $subreddit Subreddit in which to post.
     * @param string $title Title of post.
     * @param string|null $text Text of post.
     * @param bool|true $send_replies Send comment replies to the current user's inbox. True to enable, false to disable.
     * @return mixed New post's thing ID if successful. Error object if failed.
     */
    public function submitTextPost($subreddit, $title, $text = null, $send_replies = true) {
        $params = array(
            'api_type' => 'json',
            'extension' => 'json',
            'kind' => 'self',
            'resubmit' => 'true',
            'sendreplies' => ($send_replies) ? 'true' : 'false',
            'sr' => $subreddit,
            'text' => $text,
            'title' => $title
        );

        $response = $this->apiCall("/api/submit", 'POST', $params);

        if (isset($response->json->data->name)) {
            return $response->json->data->name;
        }

        return $response->json;
    }

    /**
     * Comments on an object.
     * @param string $parent Thing ID of parent object on which to comment. Could be link, text post, or comment.
     * @param string $text Comment text.
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
     * @param string $thing_id Thing ID of object to delete. Could be link, text post, or comment.
     */
    public function delete($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $this->apiCall("/api/del", 'POST', $params);
    }


    /**
     * Edits the text of a comment or text post.
     * @param string $thing_id Thing ID of text object to edit. Could be text post or comment.
     * @param string $text New text to replace the old.
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
     * @param string|array $thing_ids String or array of thing ID's of links to hide.
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
     * Unhides a post from user's hidden posts.
     * @param string|array $thing_ids String or array of thing ID's of links to unhide.
     * @return bool|null Returns true if success. Null if failed.
     */
    public function unhide($thing_ids) {
        if (is_array($thing_ids)) {
            $thing_ids = implode(',', $thing_ids);
        }

        $params = array(
            'id' => $thing_ids
        );

        $response = $this->apiCall("/api/unhide", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return true;
    }

    /**
     * Gives a listing of information on objects.
     * @param string|array $thing_ids String or array of single or multiple thing ID's.
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
     * @param string $thing_id Thing ID of post to mark as NSFW.
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

    /**
     * Unmarks a post as NSFW.
     * @param string $thing_id Thing ID of post to unmark as NSFW.
     * @return bool|null Returns true of success. Null if failed.
     */
    public function unmarkNSFW($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $response = $this->apiCall("/api/unmarknsfw", 'POST', $params);

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
     * @param string $thing_id Thing ID of object to report.
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

    /**
     * Saves a post or comment in the selected category.
     * @param string $thing_id Thing ID of object to save. Can be post or comment.
     * @param null $category Category in which to save object. Defaults to none.
     */
    public function save($thing_id, $category = null) {
        $params = array(
            'category' => $category,
            'id' => $thing_id
        );

        $this->apiCall("/api/save", 'POST', $params);
    }

    /**
     * Unsaves a post or comment from the current user's saved posts.
     * @param string $thing_id Thing ID of object to unsave. Can be post or comment.
     */
    public function unsave($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $this->apiCall("/api/unsave", 'POST', $params);
    }

    /**
     * Gets the current user's save categories.
     * @return mixed Array of category objects.
     */
    public function getSavedCategories() {
        $response = $this->apiCall("/api/saved_categories", 'GET');

        return $response->categories;
    }

    /**
     * Toggles whether or not the current user should receive replies to a specific post or comment to their inbox.
     * @param string $thing_id Thing ID of object to toggle.
     * @param bool|true $state State of inbox replies. True to receive, false for not.
     */
    public function toggleInboxReplies($thing_id, $state = true) {
        $params = array(
            'id' => $thing_id,
            'state' => ($state) ? 'true' : 'false'
        );

        $this->apiCall("/api/sendreplies", 'POST', $params);
    }

    /**
     * Store that the current user has visited a certain link.
     * @param string|array $thing_ids String or array of thing ID's of links to store as visited.
     */
    public function storeVisits($thing_ids) {
        if (is_array($thing_ids)) {
            $thing_ids = implode(',', $thing_ids);
        }

        $params = array(
            'links' => $thing_ids
        );

        $this->apiCall("/api/store_visits", 'POST', $params);
    }

    /**
     * VOTES MUST BE CAST BY A HUMAN!!
     * Proxying a person's single vote is okay, but bots should not use vote functions on their own.
     *
     * Upvotes a post or comment.
     * @param string $thing_id Thing ID of object to upvote.
     */
    public function upvote($thing_id) {
        $params = array(
            'dir' => '1',
            'id' => $thing_id
        );

        $this->apiCall("/api/vote", 'POST', $params);
    }

    /**
     * Downvotes a post or comment.
     * @param string $thing_id Thing ID of object to downvote.
     */
    public function downvote($thing_id) {
        $params = array(
            'dir' => '-1',
            'id' => $thing_id
        );

        $this->apiCall("/api/vote", 'POST', $params);
    }

    /**
     * Resets the current user's vote on a post or comment.
     * @param string $thing_id Thing ID of object to reset vote.
     */
    public function unvote($thing_id) {
        $params = array(
            'dir' => '0',
            'id' => $thing_id
        );

        $this->apiCall("/api/vote", 'POST', $params);
    }

    //-----------------------------------------
    // Listings
    //-----------------------------------------
    /**
     * Private function to unify process of retrieving several subreddit listings.
     * @param string $listing Listing type. Can be hot, new, controversial, top, gilded, ads.
     * @param string $subreddit
     * @param string $limit
     * @param string $after
     * @param string $before
     * @param string|null $time
     * @return mixed|null
     */
    private function getSubredditListing($listing, $subreddit, $limit, $after, $before, $time = null) {
        $params = array(
            't' => $time,
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );
        $api_sr = ($subreddit) ? "/r/$subreddit" : "";

        $response = $this->apiCall("$api_sr/$listing.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves the hot listing for the optionally specified subreddit.
     * @param string|null $subreddit Subreddit of listing to retrieve. If none, defaults to front page.
     * @param string|int $limit Upper limit of number of items to retrieve. Maxiumum is 100.
     * @param string|null $after Get items lower on list than this entry. Does not mean chronologically.
     * @param string|null $before Get items higher on list than this entry. Does not mean chronologically.
     * @return mixed|null Returns listing object on success. Null if failed.
     */
    public function getHot($subreddit = null, $limit = 25, $after = null, $before = null) {
        return $this->getSubredditListing('hot', $subreddit, $limit, $after, $before);
    }

    /**
     * Retrieves the new listing for the optionally specified subreddit.
     * @param string|null $subreddit Subreddit of listing to retrieve. If none, defaults to front page.
     * @param string|int $limit Upper limit of number of items to retrieve. Maxiumum is 100.
     * @param string|null $after Get items lower on list than this entry. Does not mean chronologically.
     * @param string|null $before Get items higher on list than this entry. Does not mean chronologically.
     * @return mixed|null Returns listing object on success. Null if failed.
     */
    public function getNew($subreddit = null, $limit = 25, $after = null, $before = null) {
        return $this->getSubredditListing('new', $subreddit, $limit, $after, $before);
    }

    /**
     * Retrieves the controversial listing for the optionally specified subreddit.
     * @param string|null $subreddit Subreddit of listing to retrieve. If none, defaults to front page.
     * @param string $time Time constraint for age of items on list. One of hour, day, week, month, year, all.
     * @param string|int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param string|null $after Get items lower on list than this entry. Does not mean chronologically.
     * @param string|null $before Get items higher on list than this entry. Does not mean chronologically.
     * @return mixed|null Returns listing object on success. Null if failed.
     */
    public function getControversial($subreddit = null, $time = 'all', $limit = 25, $after = null, $before = null) {
        return $this->getSubredditListing('controversial', $subreddit, $limit, $after, $before, $time);
    }

    /**
     * Retrieves the top listing for the optionally specified subreddit.
     * @param string|null $subreddit Subreddit of listing to retrieve. If none, defaults to front page.
     * @param string $time Time constraint for age of items on list. One of hour, day, week, month, year, all.
     * @param string|int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param string|null $after Get items lower on list than this entry. Does not mean chronologically.
     * @param string|null $before Get items higher on list than this entry. Does not mean chronologically.
     * @return mixed|null Returns listing object on success. Null if failed.
     */
    public function getTop($subreddit = null, $time = 'all', $limit = 25, $after = null, $before = null) {
        return $this->getSubredditListing('top', $subreddit, $limit, $after, $before, $time);
    }

    //-----------------------------------------
    // Live threads
    //-----------------------------------------
    /**
     * Creates a new Live thread. To use an existing one, use attachLiveThread().
     * @param string $title The thread's title.
     * @param null $description The thread's description.
     * @param null $resources The thread's list of resources.
     * @param bool|false $nsfw Whether or not the thread is NSFW.
     * @return null|Live Returns a Live thread object on success. Null if failed.
     */
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

    /**
     * Uses an existing Live thread to create a Live object.
     * @param string $thread_id Thread ID of the thread to attach.
     * @return Live Returns the resulting Live object.
     */
    public function attachLiveThread($thread_id) {
        return new Live($this, $thread_id);
    }

    //-----------------------------------------
    // Private messages
    //-----------------------------------------
    /**
     * Retrieves modmail messages.
     * @param string $subreddit Subreddit for which to retrieve modmail. 'mod' means all moderated subreddits.
     * @param int $limit Limit of the number of message threads to retrieve. Maximum of 100.
     * @param bool|false $messages_read Whether or not to turn off the orangered mail icon. Does not mark each message as read.
     * @param null $after Retrieve the page of results that come after the specified message ID.
     * @param null $before Retrieve the page of results that come before the specified message ID.
     * @return mixed|null Returns listing object on success, null if failed.
     */
    public function getModmail($subreddit = 'mod', $limit = 25, $messages_read = false, $after = null, $before = null) {
        $params = array(
            'mark' => ($messages_read) ? 'true' : 'false',
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/about/message/inbox/.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Marks one or more messages as read.
     * @param string|array $thing_ids Either a comma-separated string of one or more thing ID's, or an array of the same.
     * @return mixed|null Returns empty object if success, null if failed.
     */
    public function markMessageRead($thing_ids) {
        if (is_array($thing_ids)) {
            $thing_ids = implode(',', $thing_ids);
        }

        $params = array(
            'id' => $thing_ids
        );

        $response = $this->apiCall("/api/read_message", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Sends a message to a user or subreddit.
     * @param $to Username or subreddit to send to.
     * @param $subject Subject of message.
     * @param $body Body of message.
     * @param null $from_subreddit Optionally the name of the subreddit from which to send the message.
     */
    public function composeMessage($to, $subject, $body, $from_subreddit = null) {
        $params = array(
            'api_type' => 'json',
            'from_sr' => $from_subreddit,
            'subject' => $subject,
            'text' => $body,
            'to' => $to
        );

        $response = $this->apiCall("/api/compose", 'POST', $params);
    }

    //-----------------------------------------
    // Moderation
    //-----------------------------------------
    /**
     * Toggles contest mode on a post.
     * @param string $thing_id Thing ID of post to toggle contest mode.
     * @param bool|false $state True to enable contest mode, false to disable.
     */
    public function toggleContestMode($thing_id, $state = false) {
        $params = array(
            'api_type' => 'json',
            'id' => $thing_id,
            'state' => ($state) ? 'true' : 'false'
        );

        $this->apiCall("/api/set_contest_mode", 'POST', $params);
    }

    /**
     * Stickies a post at the top of the subreddit.
     * @param string $thing_id Thing ID of post to sticky.
     * @param int $num Position of new sticky. 1 for top, 2 for bottom. Defaults to 2.
     */
    public function stickyPost($thing_id, $num = 2) {
        $params = array(
            'api_type' => 'json',
            'id' => $thing_id,
            'num' => $num,
            'state' => 'true'
        );

        $this->apiCall("/api/set_subreddit_sticky", 'POST', $params);
    }

    /**
     * Unsticky a post from the top of a subreddit.
     * @param string $thing_id Thing ID of post to unsticky.
     */
    public function unstickyPost($thing_id) {
        $params = array(
            'api_type' => 'json',
            'id' => $thing_id,
            'num' => null,
            'state' => 'false'
        );

        $this->apiCall("/api/set_subreddit_sticky", 'POST', $params);
    }

    /**
     * Sets the default sort of a link's comments.
     * @param string $thing_id Thing ID of link to set suggested sort.
     * @param string $sort Sort method. One of: confidence, top, new, hot, controversial, old, random, qa, blank
     */
    public function setSuggestedSort($thing_id, $sort = 'blank') {
        $params = array(
            'api_type' => 'json',
            'id' => $thing_id,
            'sort' => $sort
        );

        $this->apiCall("/api/set_suggested_sort", 'POST', $params);
    }

    /**
     * Mod distinguish a post or comment.
     * @param string $thing_id Thing ID of object to distinguish.
     * @param bool|true $how True to set [M] distinguish. False to undistinguish.
     * @return mixed|null Returns details of object distinguished on success. Null if failed.
     */
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

    /**
     * Retrieves recent entries from the moderation log for the specified subreddit.
     * @param string $subreddit Subreddit of log to retrieve. All moderated subreddits by default.
     * @param int $limit Upper limit of number of items to retrieve. Maximum is 500.
     * @param null $after Obtain the page of the results that come after the specified ModAction.
     * @param null $mod Filter by moderator.
     * @param null $action Filter by mod action.
     * @param null $before Obtain the page of the results that come before the specified ModAction.
     * @return mixed|null Returns a listing object with modaction children. Null if failed.
     */
    public function getModerationLog($subreddit = 'mod', $limit = 25, $after = null, $mod = null, $action = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => "$limit",
            'mod' => $mod,
            'show' => 'all',
            'type' => $action
        );

        $response = $this->apiCall("/r/$subreddit/about/log.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves a list of things that have been reported in the specified subreddit.
     * @param string $subreddit Subreddit of items to retrieve. All moderated subreddits by default.
     * @param int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param null $after Obtain the page of the results that come after the specified thing.
     * @param null $before Obtain the page of the results that come before the specified thing.
     * @return mixed|null Returns a listing object with link and comment children. Null if failed.
     */
    public function getReports($subreddit = 'mod', $limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/about/reports.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves a list of things that have been marked as spam in the specified subreddit.
     * @param string $subreddit Subreddit of items to retrieve. All moderated subreddits by default.
     * @param int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param null $after Obtain the page of the results that come after the specified thing.
     * @param null $before Obtain the page of the results that come before the specified thing.
     * @return mixed|null Returns a listing object with link and comment children. Null if failed.
     */
    public function getSpam($subreddit = 'mod', $limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/about/spam.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves a list of things that have been placed in the modqueue of the specified subreddit.
     * @param string $subreddit Subreddit of items to retrieve. All moderated subreddits by default.
     * @param int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param null $after Obtain the page of the results that come after the specified thing.
     * @param null $before Obtain the page of the results that come before the specified thing.
     * @return mixed|null Returns a listing object with link and comment children. Null if failed.
     */
    public function getModqueue($subreddit = 'mod', $limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/about/modqueue.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves a list of things that have not been reviewed by a mod in the specified subreddit.
     * @param string $subreddit Subreddit of items to retrieve. All moderated subreddits by default.
     * @param int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param null $after Obtain the page of the results that come after the specified thing.
     * @param null $before Obtain the page of the results that come before the specified thing.
     * @return mixed|null Returns a listing object with link and comment children. Null if failed.
     */
    public function getUnmoderated($subreddit = 'mod', $limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/about/unmoderated.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves a list of things that have been edited in the specified subreddit.
     * @param string $subreddit Subreddit of items to retrieve. All moderated subreddits by default.
     * @param int $limit Upper limit of number of items to retrieve. Maximum is 100.
     * @param null $after Obtain the page of the results that come after the specified thing.
     * @param null $before Obtain the page of the results that come before the specified thing.
     * @return mixed|null Returns a listing object with link and comment children. Null if failed.
     */
    public function getEdited($subreddit = 'mod', $limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        $response = $this->apiCall("/r/$subreddit/about/edited.json", 'GET', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Accepts a moderator invitation for the specified subreddit. You must have a pending invitation for that subreddit.
     * @param string $subreddit Subreddit to accept invitation.
     * @return mixed|null Returns response error list. Empty list on success.
     */
    public function acceptModeratorInvite($subreddit) {
        $params = array(
            'api_type' => 'json'
        );

        $response = $this->apiCall("/r/$subreddit/api/accept_moderator_invite", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response->json;
    }

    /**
     * Marks the specified thing as approved.
     * @param string $thing_id Thing ID of object to be approved.
     */
    public function approve($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $this->apiCall("/api/approve", 'POST', $params);
    }

    /**
     * Removes a post or comment from a subreddit.
     * @param string $thing_id Thing ID of object to remove.
     * @param bool|false $spam Whether or not the object should be removed as spam.
     */
    public function remove($thing_id, $spam = false) {
        $params = array(
            'id' => $thing_id,
            'spam' => ($spam) ? 'true' : 'false'
        );

        $this->apiCall("/api/remove", 'POST', $params);
    }

    /**
     * Ignores reports for the specified thing.
     * @param string $thing_id Thing ID of object to be ignored.
     */
    public function ignoreReports($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $this->apiCall("/api/ignore_reports", 'POST', $params);
    }

    /**
     * Unignores reports for the specified thing.
     * @param string $thing_id Thing ID of object to be unignored.
     */
    public function unignoreReports($thing_id) {
        $params = array(
            'id' => $thing_id
        );

        $this->apiCall("/api/unignore_reports", 'POST', $params);
    }

    /**
     * Abdicate approved submitter status in a subreddit.
     * @param string $subreddit Name of subreddit to leave.
     * @return bool|null Returns true on success. Null if failed.
     */
    public function leaveContributor($subreddit) {
        $subreddit_info = $this->aboutSubreddit($subreddit);

        if (is_null($subreddit_info)) {
            return null;
        }

        $params = array(
            'id' => $subreddit_info->name
        );

        $this->apiCall("/api/leavecontributor", 'POST', $params);

        return true;
    }

    /**
     * Abdicate moderator status in a subreddit.
     * @param string $subreddit Name of subreddit to leave.
     * @return bool|null Returns true on success. Null if failed.
     */
    public function leaveModerator($subreddit) {
        $subreddit_info = $this->aboutSubreddit($subreddit);

        if (is_null($subreddit_info)) {
            return null;
        }

        $params = array(
            'id' => $subreddit_info->name
        );

        $this->apiCall("/api/leavemoderator", 'POST', $params);

        return true;
    }

    /**
     * Ban a user from the selected subreddit.
     * @param string $subreddit Subreddit from which to ban user.
     * @param string $user Username of user to ban.
     * @param string|null $note Ban note in banned users list. Not shown to user.
     * @param string|null $message Ban message sent to user.
     * @param int|null $duration Duration of ban in days.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function ban($subreddit, $user, $note = null, $message = null, $duration = null) {
        $params = array(
            'api_type' => 'json',
            'ban_message' => $message,
            'duration' => $duration,
            'name' => $user,
            'note' => $note,
            'type' => 'banned'
        );

        $response = $this->apiCall("/r/$subreddit/api/friend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Unban a user from a subreddit.
     * @param string $subreddit Subreddit from which to unban the user.
     * @param string $user Username of user to unban.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function unban($subreddit, $user) {
        $params = array(
            'name' => $user,
            'type' => 'banned'
        );

        $response = $this->apiCall("/r/$subreddit/api/unfriend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Add a user as a contributor to a subreddit.
     * @param string $subreddit Subreddit to which to add user.
     * @param string $user Username of user to add.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function addContributor($subreddit, $user) {
        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'type' => 'contributor'
        );

        $response = $this->apiCall("/r/$subreddit/api/friend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Remove a user as a contributor from a subreddit.
     * @param string $subreddit Subreddit from which to remove the user.
     * @param string $user Username of user to remove.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function removeContributor($subreddit, $user) {
        $params = array(
            'name' => $user,
            'type' => 'contributor'
        );

        $response = $this->apiCall("/r/$subreddit/api/unfriend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Invite a user to become a moderator to a subreddit.
     * @param string $subreddit Subreddit to which to invite user.
     * @param string $user Username of user to invite.
     * @param bool|true $perm_all If the user should have full permissions.
     * @param bool|false $perm_access If the user should have the 'access' permission.
     * @param bool|false $perm_config If the user should have the 'config' permission.
     * @param bool|false $perm_flair If the user should have the 'flair' permission.
     * @param bool|false $perm_mail If the user should have the 'mail' permission.
     * @param bool|false $perm_posts If the user should have the 'posts' permission.
     * @param bool|false $perm_wiki If the user should have the 'wiki' permission.
     * @return mixed|null Returns the response of the API call on success. Null if failed.
     */
    public function inviteModerator($subreddit, $user, $perm_all = true, $perm_access = false, $perm_config = false, $perm_flair = false, $perm_mail = false, $perm_posts = false, $perm_wiki = false) {
        $permissions = array();
        if ($perm_all) {
            $permissions[] = '+all';
        }
        else {
            if ($perm_access) {
                $permissions[] = '+access';
            }
            if ($perm_config) {
                $permissions[] = '+config';
            }
            if ($perm_flair) {
                $permissions[] = '+flair';
            }
            if ($perm_mail) {
                $permissions[] = '+mail';
            }
            if ($perm_posts) {
                $permissions[] = '+posts';
            }
            if ($perm_wiki) {
                $permissions[] = '+wiki';
            }
        }
        if (count($permissions) == 0) {
            $permissions = array('-all', '-access', '-config', '-flair', '-mail', '-posts', '-wiki');
        }

        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'permissions' => implode(',', $permissions),
            'type' => 'moderator_invite'
        );

        $response = $this->apiCall("/r/$subreddit/api/friend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Remove an existing moderator as a moderator from a subreddit. To revoke an invitation, use uninviteModerator().
     * @param string $subreddit Subreddit from which to remove a user as a moderator.
     * @param string $user Username of user to remove
     * @return mixed|null Returns the response of the API call on success. Null if failed.
     */
    public function removeModerator($subreddit, $user) {
        $params = array(
            'name' => $user,
            'type' => 'moderator'
        );

        $response = $this->apiCall("/r/$subreddit/api/unfriend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Revoke a user's pending invitation to moderate a subreddit. To remove an existing moderator, use removeModerator().
     * @param string $subreddit Subreddit from which to revoke a user's invitation.
     * @param string $user User whose invitation to revoke.
     * @return mixed|null Returns the response of the API call on success. Null if failed.
     */
    public function uninviteModerator($subreddit, $user) {
        $params = array(
            'name' => $user,
            'type' => 'moderator_invite'
        );

        $response = $this->apiCall("/r/$subreddit/api/unfriend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Modify an existing moderator's permission set. To modify an invited moderator's permissions, use setInvitationPermissions().
     * @param string $subreddit Subreddit in which to edit a user's permissions
     * @param string $user Username of user to edit permissions.
     * @param bool|true $perm_all If the user should have full permissions.
     * @param bool|false $perm_access If the user should have the 'access' permission.
     * @param bool|false $perm_config If the user should have the 'config' permission.
     * @param bool|false $perm_flair If the user should have the 'flair' permission.
     * @param bool|false $perm_mail If the user should have the 'mail' permission.
     * @param bool|false $perm_posts If the user should have the 'posts' permission.
     * @param bool|false $perm_wiki If the user should have the 'wiki' permission.
     * @return mixed|null Returns the response of the API call on success. Null if failed.
     */
    public function setModeratorPermissions($subreddit, $user, $perm_all = true, $perm_access = false, $perm_config = false, $perm_flair = false, $perm_mail = false, $perm_posts = false, $perm_wiki = false) {
        $permissions = array();
        if ($perm_all) {
            $permissions[] = '+all';
        }
        else {
            if ($perm_access) {
                $permissions[] = '+access';
            }
            if ($perm_config) {
                $permissions[] = '+config';
            }
            if ($perm_flair) {
                $permissions[] = '+flair';
            }
            if ($perm_mail) {
                $permissions[] = '+mail';
            }
            if ($perm_posts) {
                $permissions[] = '+posts';
            }
            if ($perm_wiki) {
                $permissions[] = '+wiki';
            }
        }
        if (count($permissions) == 0) {
            $permissions = array('-all', '-access', '-config', '-flair', '-mail', '-posts', '-wiki');
        }

        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'permissions' => implode(',', $permissions),
            'type' => 'moderator'
        );

        $response = $this->apiCall("/r/$subreddit/api/setpermissions", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Modify an invited moderator's permission set. To modify an existing moderator's permissions, use setModeratorPermissions().
     * @param string $subreddit Subreddit in which to edit a user's permissions
     * @param string $user Username of user to edit permissions.
     * @param bool|true $perm_all If the user should have full permissions.
     * @param bool|false $perm_access If the user should have the 'access' permission.
     * @param bool|false $perm_config If the user should have the 'config' permission.
     * @param bool|false $perm_flair If the user should have the 'flair' permission.
     * @param bool|false $perm_mail If the user should have the 'mail' permission.
     * @param bool|false $perm_posts If the user should have the 'posts' permission.
     * @param bool|false $perm_wiki If the user should have the 'wiki' permission.
     * @return mixed|null Returns the response of the API call on success. Null if failed.
     */
    public function setInvitationPermissions($subreddit, $user, $perm_all = true, $perm_access = false, $perm_config = false, $perm_flair = false, $perm_mail = false, $perm_posts = false, $perm_wiki = false) {
        $permissions = array();
        if ($perm_all) {
            $permissions[] = '+all';
        }
        else {
            if ($perm_access) {
                $permissions[] = '+access';
            }
            if ($perm_config) {
                $permissions[] = '+config';
            }
            if ($perm_flair) {
                $permissions[] = '+flair';
            }
            if ($perm_mail) {
                $permissions[] = '+mail';
            }
            if ($perm_posts) {
                $permissions[] = '+posts';
            }
            if ($perm_wiki) {
                $permissions[] = '+wiki';
            }
        }
        if (count($permissions) == 0) {
            $permissions = array('-all', '-access', '-config', '-flair', '-mail', '-posts', '-wiki');
        }

        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'permissions' => implode(',', $permissions),
            'type' => 'moderator_invite'
        );

        $response = $this->apiCall("/r/$subreddit/api/setpermissions", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Ban a user from contributing to a subreddit's wiki.
     * @param string $subreddit Subreddit from which to ban user.
     * @param string $user Username of user to ban.
     * @param string|null $note Ban note in banned users list. Not shown to user.
     * @param int|null $duration Duration of ban in days.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function wikiBan($subreddit, $user, $note = null, $duration = null) {
        $params = array(
            'api_type' => 'json',
            'duration' => $duration,
            'name' => $user,
            'note' => $note,
            'type' => 'wikibanned'
        );

        $response = $this->apiCall("/r/$subreddit/api/friend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Unban a user from a subreddit's wiki.
     * @param string $subreddit Subreddit from which to unban the user.
     * @param string $user Username of user to unban.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function wikiUnban($subreddit, $user) {
        $params = array(
            'name' => $user,
            'type' => 'wikibanned'
        );

        $response = $this->apiCall("/r/$subreddit/api/unfriend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Add a user as a contributor to a subreddit's wiki.
     * @param string $subreddit Subreddit to which to add user.
     * @param string $user Username of user to add.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function addWikiContributor($subreddit, $user) {
        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'type' => 'wikicontributor'
        );

        $response = $this->apiCall("/r/$subreddit/api/friend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Remove a user as a contributor from a subreddit's wiki.
     * @param string $subreddit Subreddit from which to remove the user.
     * @param string $user Username of user to remove.
     * @return mixed|null Response of API call on success. Null if failed.
     */
    public function removeWikiContributor($subreddit, $user) {
        $params = array(
            'name' => $user,
            'type' => 'wikicontributor'
        );

        $response = $this->apiCall("/r/$subreddit/api/unfriend", 'POST', $params);

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }


    //-----------------------------------------
    // Multis
    //-----------------------------------------

    //-----------------------------------------
    // Search
    //-----------------------------------------

    //-----------------------------------------
    // Subreddits
    //-----------------------------------------
    /**
     * Retrieves information about the specified subreddit, including subreddit ID.
     * @param string $subreddit Name of subreddit for which to retrieve information.
     * @return mixed|null Returns an object with subreddit data on success. Null if failed.
     */
    public function aboutSubreddit($subreddit) {
        $response = $this->apiCall("/r/$subreddit/about.json");

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieve a list of the subreddit's settings.
     * @param string $subreddit The subreddit to retrieve.
     * @return mixed|null An object with subreddit settings as properties. Null if failed.
     */
    public function getSubredditSettings($subreddit) {
        $response = $this->apiCall("/r/$subreddit/about/edit.json");

        if (isset($response->error)) {
            return null;
        }

        return $response->data;
    }

    /**
     * Retrieves the "submitting to /r/$subreddit" text for the selected subreddit.
     * @param string $subreddit Name of subreddit to use.
     * @return string|null Returns a string of the subreddit's submit_text. 0-length string if none, null if failed.
     */
    public function getSubmitText($subreddit) {
        $response = $this->apiCall("/r/$subreddit/api/submit_text");

        if (!isset($response->submit_text)) {
            return null;
        }

        return $response->submit_text;
    }

    //-----------------------------------------
    // Users
    //-----------------------------------------

    //-----------------------------------------
    // Wiki
    //-----------------------------------------

    public function apiCall($path, $method = 'GET', $params = null) {
        $url = $this->endpoint.$path;

        $token = $this->oauth2->getAccessToken();

        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = 5;
        $options[CURLOPT_TIMEOUT] = 10;
        $options[CURLOPT_USERAGENT] = $this->user_agent;
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

        $this->ratelimiter->wait();
        if ($this->debug) {
            echo $url."\n";
        }

        $response_raw = curl_exec($ch);
        $response = json_decode($response_raw);
        curl_close($ch);

        return $response;
    }
}