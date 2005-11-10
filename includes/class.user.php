<?php

class User
{
    var $id = null;
    var $perms = array();
    var $infos = array();

    function User($uid = 0)
    {
        global $db;

        $sql = $db->Query("SELECT  *, g.group_id AS global_group,
                                   uig.record_id AS global_record_id
                             FROM  {users}           u
                       INNER JOIN  {users_in_groups} uig
                       INNER JOIN  {groups}          g   ON uig.group_id = g.group_id
                            WHERE  u.user_id = ? AND g.belongs_to_project = '0'",
                    array($uid));
        if ($db->countRows($sql)) {
            $this->infos = $db->FetchArray($sql);
            $this->id = $uid;
        } else {
            $this->id = -1;
        }
    }

    /* misc functions {{{ */

    function save_search()
    {
        global $db;
        // Only logged in users get to use the 'last search' functionality
        foreach (array('string','type','sev','due','dev','cat','status','order','sort') as $key) {
            if (Get::has($key)) {
                $db->Query("UPDATE  {users}
                               SET  last_search = ?
                             WHERE  user_id = ?",
                        array($_SERVER['REQUEST_URI'], $this->id)
                );
                break;
            }
        }
    }

    function get_perms($proj)
    {
        global $db;

        $fields = array('is_admin', 'manage_project', 'view_tasks',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments',
                'delete_comments', 'view_attachments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'view_reports', 'group_open');

        $this->perms = array();

        if ($this->isAnon()) {
            foreach ($fields as $key) {
                $this->perms[$key] = false;
            }
            $this->perms['global_view'] = false;
        } else {
            $max = array_map(create_function('$x', 'return "MAX($x) AS $x";'),
                    $fields);

            // Get the global group permissions for the current user
            $sql = $db->Query("SELECT  ".join(', ', $max).",
                                       MAX(IF(g.belongs_to_project, view_tasks, 0)) AS global_view
                                 FROM  {groups} g
                            LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                                WHERE  uig.user_id = ?  AND
                                       (g.belongs_to_project = '0' OR g.belongs_to_project = ?)",
                                array($this->id, $proj->id));

            $this->perms = $db->fetchArray($sql);
            if ($this->perms['is_admin']) {
                $this->perms = array_map(create_function('$x', 'return 1;'), $this->perms);
            }
        }
    }

    function check_account_ok()
    {
        global $fs, $conf;

        if (Cookie::val('flyspray_passhash') !=
                crypt($this->infos['user_pass'], $conf['general']['cookiesalt'])
                || !$this->infos['account_enabled']
                || !$this->perms['group_open'])
        {
            $fs->setcookie('flyspray_userid',   '', time()-60);
            $fs->setcookie('flyspray_passhash', '', time()-60);
            $fs->Redirect($fs->CreateURL('logout', null));
        }
    }

    function isAnon()
    {
        return $this->id < 0;
    }

    /* }}} */
    /* permission related {{{ */

    function can_create_user()
    {
        global $fs;

        return $this->perms['is_admin']
            || ( $this->isAnon() && !$fs->prefs['spam_proof']
                    && $fs->prefs['anon_reg']);
    }

    function can_create_group()
    {
        return $this->perms['is_admin']
            || ($this->perms['manage_project'] && !Get::val('project'));
    }

    function can_edit_comment($comment)
    {
        return $this->perms['edit_comments'];
        /*  || (isset($comment['user_id']) && $comment['user_id'] == $this->id);
         * 
         * TODO : do we want users to be able to edit their own comments ?
         *
         * Tony says: not really, as it destroys the proper flow of conversation
         *            between users and developers.
         *            perhaps this could be made an project-level option in the future.
         */
    }

    function can_view_project()
    {
        global $proj;
        return $proj->prefs['project_is_active']
            && ($proj->prefs['others_view'] || $this->perms['view_tasks']);
    }

    function can_view_task($task)
    {
        global $proj;
        return $this->can_view_project()
            && ($this->perms['manage_project'] || !$task['mark_private']
                    || $task['assigned_to'] == $this->id);
    }

    function can_edit_task($task)
    {
        return !$task['is_closed']
            && ($this->perms['modify_all_tasks'] ||
                    ($this->perms['modify_own_tasks']
                     && $task['assigned_to'] == $this->id));
    }

    function can_take_ownership($task)
    {
        return ($this->perms['assign_to_self'] && !$task['assigned_to'])
            || ($this->perms['assign_others_to_self'] && $task['assigned_to'] != $this->id);
    }

    function can_close_task($task)
    {
        return ($this->perms['close_own_tasks'] && $task['assigned_to'] == $this->id)
            || $this->perms['close_other_tasks'];
    }

    function can_register()
    {
        global $fs;
        return $this->isAnon() && $fs->prefs['spam_proof'] && $fs->prefs['anon_reg'];
    }

    function can_open_task()
    {
        global $proj;
        return $this->perms['open_new_tasks'] || $proj->prefs['anon_open'];
    }

    function can_mark_private($task)
    {
       global $proj;
       return ($this->perms['manage_project'] && !$task['mark_private'])
           || ($task['assigned_to'] == $this->id && !$task['mark_private']);
    }

    function can_mark_public($task)
    {
       global $proj;
       return ($this->perms['manage_project'] && $task['mark_private'])
           || ($task['assigned_to'] == $this->id && $task['mark_private']);
    }

    /* }}} */
}

?>