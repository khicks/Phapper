<?php

class PhapperLive {
    /** @var Phapper */
    private $phapper;
    private $thread_id;

    public function __construct($phapper, $thread_id) {
        $this->phapper = $phapper;
        $this->thread_id = $thread_id;
    }

    /**
     * Returns the thread ID of the current thread. Useful for newly created threads.
     * @return string Thread ID.
     */
    public function getThreadId() {
        return $this->thread_id;
    }

    /**
     * Accepts a pending invitation to contribute to the live thread.
     * @return object Response to API call.
     */
    public function acceptContributorInvite() {
        $params = array(
            'api_type' => 'json'
        );

        return $this->apiCall("/accept_contributor_invite", 'POST', $params);
    }

    /**
     * Permanently closes the live thread.
     * @return object Response to API call.
     */
    public function close() {
        $params = array(
            'api_type' => 'json'
        );

        return $this->apiCall("/close_thread", 'POST', $params);
    }

    /**
     * Deletes the specified update.
     * @param string $update_id ID (rather, name attribute) of update to delete.
     * @return object Response to API call.
     */
    public function deleteUpdate($update_id) {
        $params = array(
            'api_type' => 'json',
            'id' => $update_id
        );

        return $this->apiCall("/delete_update", 'POST', $params);
    }

    /**
     * Edit the settings for the live thread.
     * @param string|null $title The thread's title.
     * @param string|null $description The thread's description.
     * @param string|null $resources The thread's resources section in the sidebar.
     * @param bool|null $nsfw Whether or not the thread is NSFW. Prompts guests to continue when visiting.
     * @return mixed|null Response to API call OR null if not all settings defined and getting current settings failed.
     */
    public function editSettings($title = null, $description = null, $resources = null, $nsfw = null) {
        $current_settings = null;
        if (is_null($title) || is_null($description) || is_null($resources) || is_null($nsfw)) {
            $current_settings = $this->about();
            if (!isset($current_settings->data)) {
                return null;
            }
        }

        $params = array(
            'api_type' => 'json',
            'description' => (is_null($description)) ? $current_settings->data->description : $description,
            'nsfw' => (is_null($nsfw)) ? $current_settings->data->nsfw : (($nsfw) ? 'true' : 'false'),
            'resources' => (is_null($resources)) ? $current_settings->data->resources : $resources,
            'title' => (is_null($title)) ? $current_settings->data->title : $title
        );

        return $this->apiCall("/edit", 'POST', $params);
    }

    /**
     * Invite a user as a contributor to the live thread.
     * @param string $user Username of user to invite.
     * @param bool $perm_all If the user should have full permissions.
     * @param bool $perm_close If the user should have the 'close live thread' permission. User must have 'settings' too to close via the web UI.
     * @param bool $perm_edit If the user should have the 'edit' permission.
     * @param bool $perm_manage If the user should have the 'manage contributors' permission.
     * @param bool $perm_settings If the user should have the 'settings' permission.
     * @param bool $perm_update If the user should have the 'update' permission.
     * @return object Response to API call.
     */
    public function inviteContributor($user, $perm_all = true, $perm_close = false, $perm_edit = false, $perm_manage = false, $perm_settings = false, $perm_update = false) {
        $permissions = array();
        if ($perm_all) {
            $permissions[] = '+all';
        }
        else {
            if ($perm_close) {
                $permissions[] = '+close';
            }
            if ($perm_edit) {
                $permissions[] = '+edit';
            }
            if ($perm_manage) {
                $permissions[] = '+manage';
            }
            if ($perm_settings) {
                $permissions[] = '+settings';
            }
            if ($perm_update) {
                $permissions[] = '+update';
            }
        }
        if (count($permissions) == 0) {
            $permissions = array('-all', '-close', '-edit', '-manage', '-settings', '-update');
        }

        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'permissions' => implode(',', $permissions),
            'type' => 'liveupdate_contributor_invite'
        );

        return $this->apicall("/invite_contributor", 'POST', $params);
    }

    /**
     * Abdicate contributorship of the thread.
     * @return object Response to API call.
     */
    public function leaveContributor() {
        $params = array(
            'api_type' => 'json'
        );

        return $this->apiCall("/leave_contributor", 'POST', $params);
    }

    /**
     * Report the thread to the reddit admins for breaking one of the site's rules.
     * @param string $type One of 'spam', 'vote-manipulation', 'personal-information', 'sexualizing-minors', 'site-breaking'.
     * @return object Response to API call.
     */
    public function report($type) {
        $params = array(
            'api_type' => 'json',
            'type' => $type
        );

        return $this->apiCall("/report", 'POST', $params);
    }

    /**
     * Remove a user as a contributor from the thread. To revoke a pending invitation, use uninviteContributor().
     * @param string $user Username of user to remove from thread's contributor list.
     * @return object|null Response to API call OR null if the user does not exist.
     */
    public function removeContributor($user) {
        $user_object = $this->phapper->getUser($user);
        if (!isset($user_object->data)) {
            return null;
        }

        $user_id = $user_object->kind.'_'.$user_object->data->id;

        $params = array(
            'api_type' => 'json',
            'id' => $user_id
        );

        return $this->apiCall("/rm_contributor", 'POST', $params);
    }

    /**
     * Revoke a pending contributor invitation. To remove a current contributor, use removeContributor().
     * @param string $user Username of user to uninvite.
     * @return object|null Response to API call OR null if the user does not exit.
     */
    public function uninviteContributor($user) {
        $user_object = $this->phapper->getUser($user);
        if (!isset($user_object->data)) {
            return null;
        }

        $user_id = $user_object->kind.'_'.$user_object->data->id;

        $params = array(
            'api_type' => 'json',
            'id' => $user_id
        );

        return $this->apiCall("/rm_contributor_invite", 'POST', $params);
    }

    /**
     * Modify a current contributor's permission set. To modify an invited contributor's permissions, use setInvitationPermissions().
     * @param string $user Username of user for which to edit permissions.
     * @param bool $perm_all If the user should have full permissions.
     * @param bool $perm_close If the user should have the 'close live thread' permission. User must have 'settings' too to close via the web UI.
     * @param bool $perm_edit If the user should have the 'edit' permission.
     * @param bool $perm_manage If the user should have the 'manage contributors' permission.
     * @param bool $perm_settings If the user should have the 'settings' permission.
     * @param bool $perm_update If the user should have the 'update' permission.
     * @return object Response to API call.
     */
    public function setContributorPermissions($user, $perm_all = true, $perm_close = false, $perm_edit = false, $perm_manage = false, $perm_settings = false, $perm_update = false) {
        $permissions = array();
        if ($perm_all) {
            $permissions[] = '+all';
        }
        else {
            if ($perm_close) {
                $permissions[] = '+close';
            }
            if ($perm_edit) {
                $permissions[] = '+edit';
            }
            if ($perm_manage) {
                $permissions[] = '+manage';
            }
            if ($perm_settings) {
                $permissions[] = '+settings';
            }
            if ($perm_update) {
                $permissions[] = '+update';
            }
        }
        if (count($permissions) == 0) {
            $permissions = array('-all', '-close', '-edit', '-manage', '-settings', '-update');
        }

        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'permissions' => implode(',', $permissions),
            'type' => 'liveupdate_contributor'
        );

        return $this->apicall("/set_contributor_permissions", 'POST', $params);
    }

    /**
     * Modify an invited contributor's permission set. To modify a current contributor's permissions, use setContributorPermissions().
     * @param string $user Username of user for which to edit permissions.
     * @param bool $perm_all If the user should have full permissions.
     * @param bool $perm_close If the user should have the 'close live thread' permission. User must have 'settings' too to close via the web UI.
     * @param bool $perm_edit If the user should have the 'edit' permission.
     * @param bool $perm_manage If the user should have the 'manage contributors' permission.
     * @param bool $perm_settings If the user should have the 'settings' permission.
     * @param bool $perm_update If the user should have the 'update' permission.
     * @return object Response to API call.
     */
    public function setInvitationPermissions($user, $perm_all = true, $perm_close = false, $perm_edit = false, $perm_manage = false, $perm_settings = false, $perm_update = false) {
        $permissions = array();
        if ($perm_all) {
            $permissions[] = '+all';
        }
        else {
            if ($perm_close) {
                $permissions[] = '+close';
            }
            if ($perm_edit) {
                $permissions[] = '+edit';
            }
            if ($perm_manage) {
                $permissions[] = '+manage';
            }
            if ($perm_settings) {
                $permissions[] = '+settings';
            }
            if ($perm_update) {
                $permissions[] = '+update';
            }
        }
        if (count($permissions) == 0) {
            $permissions = array('-all', '-close', '-edit', '-manage', '-settings', '-update');
        }

        $params = array(
            'api_type' => 'json',
            'name' => $user,
            'permissions' => implode(',', $permissions),
            'type' => 'liveupdate_contributor_invite'
        );

        return $this->apiCall("/set_contributor_permissions", 'POST', $params);
    }

    /**
     * Strikes the specified update, which will show up as crossed out in the live thread.
     * @param string $update_id ID (rather, name attribute) of update to strike.
     * @return object Response to API call.
     */
    public function strikeUpdate($update_id) {
        $params = array(
            'api_type' => 'json',
            'id' => $update_id
        );

        return $this->apiCall("/strike_update", 'POST', $params);
    }

    /**
     * Makes an update on the live thread.
     * @param string $body Body of update to post.
     * @return object Response to API call. Unfortunately, no update ID is returned yet. You need to run getUpdates() to find this.
     */
    public function update($body) {
        $params = array(
            'api_type' => 'json',
            'body' => $body
        );
        return $this->apiCall("/update", 'POST', $params);
    }

    /**
     * Retrieves updates on a thread.
     * @param int $limit Upper limit of the number of links to retrieve. Maximum is 100.
     * @param string|null $after Get items lower on list than this entry. Does not mean chronologically. Should be the *name* of an update: "LiveUpdate_..."
     * @param string|null $before Get items higher on list than this entry. Does not mean chronologically. Should be the *name* of an update: "LiveUpdate_..."
     * @return object Listing of LiveUpdate objects.
     */
    public function getUpdates($limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit
        );

        return $this->liveCall(".json", 'GET', $params);
    }

    /**
     * Retrieves information about the live thread.
     * @return object LiveUpdateEvent object.
     */
    public function about() {
        return $this->liveCall("/about.json");
    }

    /**
     * Retrieves a list of contributors for the thread. To see invitations, the current user must have the 'manage' permission.
     * @return object|array UserList object OR array of two UserList objects if there are visible pending invitations.
     */
    public function getContributors() {
        return $this->liveCall("/contributors.json");
    }

    /**
     * Retrieves a list of discussions about the current thread.
     * @param int $limit Upper limit of the number of links to retrieve. Maximum is 100.
     * @param string|null $after Get items lower on list than this entry. Does not mean chronologically.
     * @param string|null $before Get items higher on list than this entry. Does not mean chronologically.
     * @return object Listing of posts.
     */
    public function getDiscussions($limit = 25, $after = null, $before = null) {
        $params = array(
            'after' => $after,
            'before' => $before,
            'limit' => $limit,
            'show' => 'all'
        );

        return $this->liveCall("/discussions", 'GET', $params);
    }

    public function apiCall($path, $method = 'GET', $params = null) {
        return $this->phapper->apiCall("/api/live/{$this->thread_id}$path", $method, $params);
    }

    public function liveCall($path, $method = 'GET', $params = null) {
        return $this->phapper->apiCall("/live/{$this->thread_id}$path", $method, $params);
    }
}