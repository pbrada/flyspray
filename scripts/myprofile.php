<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoMyprofile extends FlysprayDo
{
    var $default_handler = 'prefs';

    // **********************
    // Begin area_ functions
    // **********************
    function area_prefs()
    {
        global $page, $user, $fs;

        $page->assign('groups', Flyspray::ListGroups());
        $page->assign('all_groups', Flyspray::listallGroups($user->id));

        $page->assign('theuser', $user);

        $page->setTitle($fs->prefs['page_title'] . L('editmydetails'));
    }

    function area_notifs()
    {
        global $user, $fs, $page, $db;

        require_once BASEDIR . '/includes/events.inc.php';
        $events_since = strtotime(Get::val('events_since', '-1 week'));
        $tasks = $db->x->getAll('SELECT h.task_id, t.*, p.project_prefix
                               FROM {history} h
                          LEFT JOIN {tasks} t ON h.task_id = t.task_id
                          LEFT JOIN {projects} p ON t.project_id = p.project_id
                          LEFT JOIN {notifications} n ON t.task_id = n.task_id
                              WHERE h.event_date > ? AND h.task_id > 0 AND n.user_id = ?
                                    AND event_type NOT IN (9,10,5,6,8,17,18)
                           GROUP BY h.task_id
                           ORDER BY h.event_date DESC', null,
                          array($events_since, $user->id));

        $task_events = array();
        foreach ($tasks as $task) {
            $task_events[$task['task_id']] = get_events($task['task_id'], 'AND event_type NOT IN (9,10,5,6,8,17,18) AND h.event_date > ' . $events_since, 'DESC');
        }

        $page->assign('task_events', $task_events);
        $page->assign('tasks', $tasks);
        $page->setTitle($fs->prefs['page_title'] . L('mynotifications'));
    }

    function area_notes()
    {
        global $user, $fs, $page, $db;

        $page->assign('saved_notes', $db->x->getAll('SELECT * FROM {notes} WHERE user_id = ?', null, $user->id));

        if (Req::num('note_id') && Get::val('action') != 'deletenote') {
            $note = $db->x->getRow('SELECT note_id, message_subject, message_body, n.last_updated, content, n.syntax_plugins
                                   FROM {notes} n
                              LEFT JOIN {cache} c ON note_id = topic AND type = ? AND n.last_updated < c.last_updated
                                  WHERE user_id = ? AND note_id = ?', null,
                                  array('note', $user->id, Req::num('note_id')));
            $page->assign('show_note', $note);
        }
    }
    // **********************
    // End of area_ functions
    // **********************

    // **********************
    // Begin action_ functions
    // **********************
    function action_edituser()
    {
        return FlysprayDoAdmin::action_edituser();
    }

    function action_addnote()
    {
        global $db, $user;
        if (!Post::val('message_subject')) {
            return array(ERROR_RECOVER, L('enternotesubject'));
        }
        $db->x->autoExecute('{notes}', array('message_subject'=> Post::val('message_subject'),
                                             'message_body' => Post::val('message_body'),
                                             'last_updated' => time(),
                                             'user_id' => $user->id,
                                             'syntax_plugins' => implode(' ', (array) Post::val('message_body_syntax_plugins'))));

        return array(SUBMIT_OK, L('noteadded'));
    }

    function action_deletenote()
    {
    
        global $db, $user;
        $num = $db->x->execParam('DELETE FROM {notes} WHERE note_id = ? AND user_id = ?',
                                 array(Req::val('note_id'), $user->id));

        if ($num) {
            return array(SUBMIT_OK, L('notedeleted'));
        } else {
            return array(ERROR_RECOVER, L('notedoesnotexist'));
        }
    }

    function action_updatenote()
    {
        global $db, $user;
        if (!Post::val('message_subject')) {
            return array(ERROR_RECOVER, L('enternotesubject'));
        }

        $num = $db->x->autoExecute('{notes}', array('message_subject'=> Post::val('message_subject'),
                                                    'message_body'=> Post::val('message_body'),
                                                    'last_updated'=> time(),
                                                    'syntax_plugins' => implode(' ', (array)Post::val('message_body_syntax_plugins'))),
                                   MDB2_AUTOQUERY_UPDATE, sprintf('note_id = %d AND user_id = %d', Post::num('note_id'), $user->id));

        if ($num) {
            return array(SUBMIT_OK, L('noteupdated'), CreateUrl(array('myprofile', 'notes'), array('note_id' => Post::val('note_id'))));
        } else {
            return array(ERROR_INPUT, L('notedoesnotexist'));
        }
    }

    // **********************
    // End of action_ functions
    // **********************

	function show($area = null)
	{
		global $page, $fs, $db, $proj;

        $page->pushTpl('myprofile.menu.tpl');

        $this->handle('area', $area);

		$page->pushTpl('myprofile.'. $area .'.tpl');
	}

	function _onsubmit()
	{
        global $fs, $db, $proj, $user;

        $proj = new Project(0);

        return $this->handle('action', Req::val('action'));
	}

	function is_accessible()
	{
		global $user;
		return !$user->isAnon();
	}
}

?>
