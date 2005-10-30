<?php
/*
   --------------------------------------------------
   | Administrator's Toolbox                        |
   | =======================                        |
   | This script allows members of a global Admin   |
   | group to modify the global preferences, user   |
   | profiles, global lists, global groups, pretty  |
   | much everything global.                        |
   --------------------------------------------------
*/

$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'admin');
$fs->get_language_pack($lang, 'index');
$fs->get_language_pack($lang, 'newproject');

$this_page = htmlspecialchars($_SERVER["REQUEST_URI"]);
$area      = Get::val('area', 'prefs');

// The user must be a member of the global "Admin" group to use this page
if ($permissions['is_admin'] != '1') {
    $fs->Redirect( $fs->CreateURL('error', null) );
}
// Show the menu that stays visible, regardless of which area we're in
echo '<div id="toolboxmenu">';
echo '<small>|</small><a id="globprefslink"   href="' . $fs->CreateURL('admin', 'prefs     ') . '">' . $admin_text['preferences']      . '</a>';
echo '<small>|</small><a id="globuglink"      href="' . $fs->CreateURL('admin', 'groups    ') . '">' . $admin_text['usergroups']       . '</a>';
echo '<small>|</small><a id="globttlink"      href="' . $fs->CreateURL('admin', 'tt        ') . '">' . $admin_text['tasktypes']        . '</a>';
echo '<small>|</small><a id="globreslink"     href="' . $fs->CreateURL('admin', 'res       ') . '">' . $admin_text['resolutions']      . '</a>';
echo '<small>|</small><a id="globcatlink"     href="' . $fs->CreateURL('admin', 'cat       ') . '">' . $admin_text['categories']       . '</a>';
echo '<small>|</small><a id="globoslink"      href="' . $fs->CreateURL('admin', 'os        ') . '">' . $admin_text['operatingsystems'] . '</a>';
echo '<small>|</small><a id="globverlink"     href="' . $fs->CreateURL('admin', 'ver       ') . '">' . $admin_text['versions']         . '</a>';
echo '<small>|</small><a id="globnewprojlink" href="' . $fs->CreateURL('admin', 'newproject') . '">' . $admin_text['newproject']       . '</a>';
echo '</div>';

?>
<div id="toolbox">
<?php
if     ($area == 'prefs'): // {{{
    //////////////////////////////////////
    // Start of application preferences //
    //////////////////////////////////////

    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['preferences'] . '</h3>';
?>
<form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
  <fieldset class="admin">
    <legend><?php echo $admin_text['general'];?></legend>
    <table class="admin">
      <tr>
        <td>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="globaloptions" />
          <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
          <label id="defaultprojectlabel" for="defaultproject"><?php echo $admin_text['defaultproject'];?></label>
        </td>
        <td>
          <select id="defaultproject" name="default_project">
            <?php
            $get_projects = $db->Query("SELECT * FROM {projects}");
            while ($row = $db->FetchArray($get_projects)) {
                if ($flyspray_prefs['default_project'] == $row['project_id']) {
                    echo '<option value="' . $row['project_id'] . '" selected="selected">' . stripslashes($row['project_title']) . '</option>';
                } else {
                    echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
                }
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td><label id="langcodelabel" for="langcode"><?php echo $admin_text['language'];?></label></td>
        <td>
          <select id="langcode" name="lang_code">
            <?php
            if ($handle = opendir('lang/')) {
                $lang_array = array();
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != ".." && file_exists("lang/$file/main.php")) {
                        array_push($lang_array, $file);
                    }
                }
                closedir($handle);
            }
            sort($lang_array);

            while (list($key, $val) = each($lang_array)) {
                if ($val == $flyspray_prefs['lang_code']) {
                    echo "<option class=\"adminlist\" selected=\"selected\">$val</option>\n";
                } else {
                    echo "<option class=\"adminlist\">$val</option>\n";
                }
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td><label id="dateformatlabel" for="dateformat"><?php echo $admin_text['dateformat'];?></label></td>
        <td>
          <input id="dateformat" name="dateformat" type="text" size="40" maxlength="30" value="<?php echo $flyspray_prefs['dateformat'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="dateformatextendedlabel" for="dateformat_extended"><?php echo $admin_text['dateformat_extended'];?></label></td>
        <td>
          <input id="dateformat_extended" name="dateformat_extended" type="text" size="40" maxlength="30" value="<?php echo $flyspray_prefs['dateformat_extended'];?>" />
        </td>
      </tr>
    </table>
  </fieldset>

  <fieldset class="admin">
    <legend><?php echo $admin_text['userregistration'];?></legend>
    <table class="admin">
      <tr>
        <td><label id="allowusersignupslabel" for="allowusersignups"><?php echo $admin_text['anonreg'];?></label></td>
        <td>
          <input id="allowusersignups" type="checkbox" name="anon_reg" value="1" <?php if ($flyspray_prefs['anon_reg'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
      </tr>
      <tr>
        <td><label id="spamprooflabel" for="spamproof"><?php echo $admin_text['spamproof']; ?></label></td>
        <td>
          <input id="spamproof" type="checkbox" name="spam_proof" value="1" <?php if ($flyspray_prefs['spam_proof'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
      </tr>
      <tr>
        <td><label id="defglobalgplabel" for="defaultglobalgroup"><?php echo $admin_text['defaultglobalgroup'];?></label></td>
        <td>
          <select id="defaultglobalgroup" name="anon_group">
            <?php // Get the group names
            $get_group_details = $db->Query("SELECT group_id, group_name FROM {groups} WHERE belongs_to_project = '0' ORDER BY group_id ASC");
            while ($row = $db->FetchArray($get_group_details)) {
                if ($flyspray_prefs['anon_group'] == $row['group_id']) {
                    echo "<option value=\"{$row['group_id']}\" selected=\"selected\">{$row['group_name']}</option>";
                } else {
                    echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
                }
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td><label id="groupsassignedlabel"><?php echo $admin_text['groupassigned'];?></label></td>
        <td class="admintext">
          <?php // Get the group names
          $get_group_details = $db->Query("SELECT group_id, group_name FROM {groups} WHERE belongs_to_project = '0' ORDER BY group_id ASC");
          while ($row = $db->FetchArray($get_group_details)) {
              if (ereg($row['group_id'], $flyspray_prefs['assigned_groups'])) {
                  echo "<input type=\"checkbox\" name=\"assigned_groups[{$row['group_id']}]\" value=\"1\" checked=\"checked\" />{$row['group_name']}<br />\n";
              } else {
                  echo "<input type=\"checkbox\" name=\"assigned_groups[{$row['group_id']}]\" value=\"1\" />{$row['group_name']}<br />\n";
              }
          }
          ?>
        </td>
      </tr>
    </table>
  </fieldset>

  <fieldset class="admin">
    <legend><?php echo $admin_text['notifications'];?></legend>
    <table class="admin">
      <tr>
        <td><label id="usernotifylabel" for="usernotify"><?php echo $admin_text['forcenotify'];?></label></td>
        <td>
          <select id="usernotify" name="user_notify">
            <option value="0" <?php if ($flyspray_prefs['user_notify'] == "0") { echo "selected=\"selected\"";};?>><?php echo $admin_text['none'];?></option>
            <option value="1" <?php if ($flyspray_prefs['user_notify'] == "1") { echo "selected=\"selected\"";};?>><?php echo $admin_text['userchoose'];?></option>
            <option value="2" <?php if ($flyspray_prefs['user_notify'] == "2") { echo "selected=\"selected\"";};?>><?php echo $admin_text['email'];?></option>
            <option value="3" <?php if ($flyspray_prefs['user_notify'] == "3") { echo "selected=\"\"";};?>><?php echo $admin_text['jabber'];?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th colspan="2"><hr />
          <?php echo $admin_text['emailnotify'];?>
        </th>
      </tr>
      <tr>
        <td><label id="adminemaillabel" for="adminemail"><?php echo $admin_text['fromaddress'];?></label></td>
        <td>
          <input id="adminemail" name="admin_email" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['admin_email'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="smtpservlabel" for="smtpserv"><?php echo $admin_text['smtpserver'];?></label></td>
        <td>
          <input id="smtpserv" name="smtp_server" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['smtp_server'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="smtpuserlabel" for="smtpuser"><?php echo $admin_text['smtpuser'];?></label></td>
        <td>
          <input id="smtpuser" name="smtp_user" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['smtp_user'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="smtppasslabel" for="smtppass"><?php echo $admin_text['smtppass'];?></label></td>
        <td>
          <input id="smtppass" name="smtp_pass" type="text" size="40" maxlength="100" value="<?php echo $flyspray_prefs['smtp_pass'];?>" />
        </td>
      </tr>
      <tr>
        <th colspan="2"><hr />
          <?php echo $admin_text['jabbernotify'];?>
        </th>
      </tr>
      <tr>
        <td><label id="jabservlabel" for="jabberserver"><?php echo $admin_text['jabberserver'];?></label></td>
        <td>
          <input id="jabberserver" name="jabber_server" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_server'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="jabportlabel" for="jabberport"><?php echo $admin_text['jabberport'];?></label></td>
        <td>
          <input id="jabberport" name="jabber_port" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_port'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="jabuserlabel" for="jabberusername"><?php echo $admin_text['jabberuser'];?></label></td>
        <td>
          <input id="jabberusername" name="jabber_username" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_username'];?>" />
        </td>
      </tr>
      <tr>
        <td><label id="jabpasslabel" for="jabberpassword"><?php echo $admin_text['jabberpass'];?></label></td>
        <td>
          <input id="jabberpassword" name="jabber_password" type="password" size="40" maxlength="100" value="<?php echo $flyspray_prefs['jabber_password'];?>" />
        </td>
      </tr>
    </table>
  </fieldset>

  <fieldset class="admin">
    <legend><?php echo $admin_text['lookandfeel'];?></legend>
    <table class="admin">
      <tr>
        <td><label id="globalthemelabel" for="globaltheme"><?php echo $admin_text['globaltheme'];?></label></td>
        <td>
          <select id="globaltheme" name="global_theme">
            <?php
            if ($handle = opendir('themes/')) {
                $theme_array = array();
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != ".." && file_exists("themes/$file/theme.css")) {
                        array_push($theme_array, $file);
                    }
                }
                closedir($handle);
            }
            sort($theme_array);

            while (list($key, $val) = each($theme_array)) {
                if ($val == $flyspray_prefs['global_theme']) {
                    echo "<option class=\"adminlist\" selected=\"selected\">$val</option>\n";
                } else {
                    echo "<option class=\"adminlist\">$val</option>\n";
                }
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td><label id="viscollabel"><?php echo $admin_text['visiblecolumns'];?></label></td>
        <td class="admintext">
          <?php // Set the selectable column names
          $columnnames =
          array('id','project','tasktype','category','severity','priority','summary','dateopened','status','openedby','assignedto',
                  'lastedit','reportedin','dueversion','duedate','comments','attachments','progress');
          foreach ($columnnames AS $column) {
              if (ereg($column, $flyspray_prefs['visible_columns']) ) {
                  echo "<input type=\"checkbox\" name=\"visible_columns[{$column}]\" value=\"1\" checked=\"checked\" />$index_text[$column]<br />\n";
              } else {
                  echo "<input type=\"checkbox\" name=\"visible_columns[{$column}]\" value=\"1\" />$index_text[$column]<br />\n";
              }
          }
          ?>
        </td>
      </tr>
    </table>
  </fieldset>

  <table>
    <tr>
      <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['saveoptions'];?>" /></td>
      <td class="buttons"><input class="adminbutton" type="reset" value="<?php echo $admin_text['resetoptions'];?>" /></td>
    </tr>
  </table>
</form>
<?php // }}}
elseif ($area == 'users' && Get::val('id')): // {{{
    ////////////////////////////
    // Start of editing users //
    ////////////////////////////


    $get_user_details = $db->Query("SELECT * FROM {users} WHERE user_id = ?", array($_GET['id']));
    $user_details = $db->FetchArray($get_user_details);

    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['edituser'] . ': ' . $user_details['user_name'] . '</h3>';
?>
<fieldset class="admin">
  <legend><?php echo $admin_text['edituser'];?></legend>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <table class="admin">
      <tr>
        <td>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="edituser" />
          <input type="hidden" name="user_id" value="<?php echo $user_details['user_id'];?>" />
          <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
          <label for="realname"><?php echo $admin_text['realname'];?></label>
        </td>
        <td><input id="realname" type="text" name="real_name" size="50" maxlength="100" value="<?php echo stripslashes($user_details['real_name']);?>" /></td>
      </tr>
      <tr>
        <td><label for="emailaddress"><?php echo $admin_text['emailaddress'];?></label></td>
        <td><input id="emailaddress" type="text" name="email_address" size="50" maxlength="100" value="<?php echo $user_details['email_address'];?>" /></td>
      </tr>
      <tr>
        <td><label for="jabberid"><?php echo $admin_text['jabberid'];?></label></td>
        <td><input id="jabberid" type="text" name="jabber_id" size="50" maxlength="100" value="<?php echo $user_details['jabber_id'];?>" /></td>
      </tr>
      <tr>
        <td><label for="notifytype"><?php echo $admin_text['notifytype'];?></label></td>
        <td>
          <?php if ($flyspray_prefs['user_notify'] == '1') { ?>
          <select id="notifytype" name="notify_type">
            <option value="0" <?php if ($user_details['notify_type'] == "0") {echo "selected=\"selected\"";};?>>None</option>
            <option value="1" <?php if ($user_details['notify_type'] == "1") {echo "selected=\"selected\"";};?>>Email</option>
            <option value="2" <?php if ($user_details['notify_type'] == "2") {echo "selected=\"selected\"";};?>>Jabber</option>
          </select>
          <?php
          } else {
              echo $admin_text['setglobally'];
          }; ?>
        </td>
      </tr>
      <tr>
        <td><label for="dateformat"><?php echo $admin_text['dateformat'];?></label></td>
        <td><input id="dateformat" name="dateformat" type="text" size="40" maxlength="30" value="<?php echo $user_details['dateformat'];?>" /></td>
      </tr>
      <tr>
        <td><label for="dateformat_extended"><?php echo $admin_text['dateformat_extended'];?></label></td>
        <td><input id="dateformat_extended" name="dateformat_extended" type="text" size="40" maxlength="30" value="<?php echo $user_details['dateformat_extended'];?>" /></td>
      </tr>
      <tr>
        <td><label for="tasks_perpage"><?php echo $admin_text['tasksperpage'];?></label></td>
        <td>
          <select name="tasks_perpage">
            <?php
            // This should really share its list of values with myprofile.php...
            $perpagevals = array(10,25,50,100,250,500);
            foreach ($perpagevals as $n) {
                $s = ($current_user['tasks_perpage'] == $n ? " selected=\"selected\"" : "");
                echo "<option value=\"$n\"$s>$n</option>\n";
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td><label for="groupin"><?php echo $admin_text['globalgroup'];?></label></td>
        <td>
          <select id="groupin" name="group_in">
            <?php
            // Get the groups list
            $result = $db->Query("SELECT  * FROM {users_in_groups} uig
                               LEFT JOIN  {groups} g ON uig.group_id = g.group_id
                                   WHERE  uig.user_id = ? AND g.belongs_to_project = ?
                                ORDER BY  g.group_id ASC", array($user_details['user_id'], '0'));
            $current_global_group = $db->FetchArray($result);

            // Now, get the list of global groups and compare for display
            $global_groups = $db->Query("SELECT * FROM {groups}
                                          WHERE  belongs_to_project = ?", array('0'));
            while ($row = $db->FetchArray($global_groups)) {
                if ($row['group_id'] == $current_global_group['group_id']) {
                    echo "<option value=\"{$row['group_id']}\" selected=\"selected\">{$row['group_name']}</option>";
                } else {
                    echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
                }
            }
            ?>
          </select>
          <input type="hidden" name="record_id" value="<?php echo $current_global_group['record_id'];?>" />
        </td>
      </tr>
      <tr>
        <td><label for="accountenabled"><?php echo $admin_text['accountenabled'];?></label></td>
        <td><input id="accountenabled" type="checkbox" name="account_enabled" value="1" <?php if ($user_details['account_enabled'] == "1") {echo "checked=\"checked\"";};?> /></td>
      </tr>
      <tr>
        <td colspan="2"><hr /></td>
      </tr>
      <tr>
        <td><label for="changepass"><?php echo $admin_text['changepass'];?></label></td>
        <td><input id="changepass" type="password" name="changepass" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td><label for="confirmpass"><?php echo $admin_text['confirmpass'];?></label></td>
        <td><input id="confirmpass" type="password" name="confirmpass" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>" /></td>
      </tr>
    </table>
  </form>
</fieldset>
<?php // }}}
elseif ($area == 'groups'): // {{{
    /////////////////////////////////
    // Start of the groups manager //
    /////////////////////////////////

    $get_groups = $db->Query("SELECT  * FROM {groups}
                               WHERE  belongs_to_project = '0'
                            ORDER BY  group_id ASC"
                        );

    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['usergroups'] . '</h3>';
?>
<fieldset class="admin">
  <legend><?php echo $admin_text['usergroups'] ?></legend>
  <p>
    <a href="<?php echo $fs->CreateURL('newuser', null) ?>"><?php echo $admin_text['newuser'] ?></a> | 
    <a href="<?php echo $fs->CreateURL('newgroup', '0') ?>"><?php echo $admin_text['newgroup'] ?></a>
  </p>
  <?php
  // Cycle through the global groups
  while ($group = $db->FetchArray($get_groups)):
  ?>
  <a class="grouptitle" href="<?php echo $fs->CreateURL('group', $group['group_id']) ?>"><?php echo stripslashes($group['group_name']) ?></a>
  <p><?php echo stripslashes($group['group_desc']) ?></p>
  <form action="<?php echo $conf['general']['baseurl'] ?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="movetogroup" />
      <input type="hidden" name="old_group" value="<?php echo $group['group_id'] ?>" />
      <input type="hidden" name="project_id" value="<?php echo $project_id ?>" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page ?>" />
      <table class="userlist">
        <tr>
          <th></th>
          <th><?php echo $admin_text['username'] ?></th>
          <th><?php echo $admin_text['realname'] ?></th>
          <th><?php echo $admin_text['accountenabled'] ?></th>
        </tr>
        <?php
        $get_user_list = $db->Query("SELECT  * FROM {users_in_groups} uig
                                  LEFT JOIN  {users} u on uig.user_id = u.user_id
                                      WHERE  uig.group_id = ? ORDER BY u.user_name ASC", array($group['group_id']));
        while ($row = $db->FetchArray($get_user_list)):
        ?>
        <tr>
          <td><input type="checkbox" name="users[<?php echo $row['user_id'] ?>]" value="1" /></td>
          <td><a href="<?php echo $fs->CreateURL('user', $row['user_id']) ?>"><?php echo $row['user_name'] ?></a></td>
          <td><?php echo $row['real_name'] ?></td>
          <?php
          if ($row['account_enabled'] == "1") {
              echo "<td>{$admin_text['yes']}</td>";
          } else {
              echo "<td>{$admin_text['no']}</td>";
          }
          ?>
        </tr>
        <?php endwhile; ?>
        <tr>
          <td colspan="4">
            <input class="adminbutton" type="submit" value="<?php echo $admin_text['moveuserstogroup'] ?>" />
            <select class="adminlist" name="switch_to_group">
              <?php
              $groups = $db->Query("SELECT * FROM {groups} WHERE belongs_to_project = '0' ORDER BY group_id ASC");
              while ($group = $db->FetchArray($groups)) {
                  echo '<option value="' . $group['group_id'] . '">' . htmlspecialchars(stripslashes($group['group_name']),ENT_COMPAT,'utf-8') . "</option>\n";
              }
              ?>
            </select>
          </td>
        </tr>
      </table>
    </div>
  </form>
  <?php endwhile; ?>
</fieldset>
<?php // }}}
elseif ($area == 'editgroup'): // {{{
    /////////////////////////////
    // Start of editing groups //
    /////////////////////////////
    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['editgroup'] . '</h3>';

    $get_group_details = $db->Query("SELECT * FROM {groups} WHERE group_id = ?", array($_GET['id']));
    $group_details = $db->FetchArray($get_group_details);
?>
<form action="<?php echo $conf['general']['baseurl'];?>index.php?project=<?php echo $group_details['belongs_to_project'];?>" method="post">
  <table class="admin">
    <tr>
      <td>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="editgroup" />
        <input type="hidden" name="group_id" value="<?php echo $group_details['group_id'];?>" />
        <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
        <label for="groupname"><?php echo $admin_text['groupname'];?></label>
      </td>
      <td><input id="groupname" type="text" name="group_name" size="20" maxlength="20" value="<?php echo htmlspecialchars(stripslashes($group_details['group_name']),ENT_COMPAT,'utf-8');?>" /></td>
    </tr>
    <tr>
      <td><label for="groupdesc"><?php echo $admin_text['description'];?></label></td>
      <td><input id="groupdesc" type="text" name="group_desc" size="50" maxlength="100" value="<?php echo htmlspecialchars(stripslashes($group_details['group_desc']),ENT_COMPAT,'utf-8');?>" /></td>
    </tr>
    <?php
    // We don't need this stuff shown for the admin group
    if (Get::val('id') == '1'):
        echo $admin_text['notshownforadmin'];
    else:
    ?>
    <tr>
      <td><label for="projectmanager"><?php echo $admin_text['projectmanager'];?></label></td>
      <td><input id="projectmanager" type="checkbox" name="manage_project" value="1" <?php if ($group_details['manage_project'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewtasks"><?php echo $admin_text['viewtasks'];?></label></td>
      <td><input id="viewtasks" type="checkbox" name="view_tasks" value="1" <?php if ($group_details['view_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="canopenjobs"><?php echo $admin_text['opennewtasks'];?></label></td>
      <td><input id="canopenjobs" type="checkbox" name="open_new_tasks" value="1" <?php if ($group_details['open_new_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="modifyowntasks"><?php echo $admin_text['modifyowntasks'];?></label></td>
      <td><input id="modifyowntasks" type="checkbox" name="modify_own_tasks" value="1" <?php if ($group_details['modify_own_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="modifyalltasks"><?php echo $admin_text['modifyalltasks'];?></label></td>
      <td><input id="modifyalltasks" type="checkbox" name="modify_all_tasks" value="1" <?php if ($group_details['modify_all_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewcomments"><?php echo $admin_text['viewcomments'];?></label></td>
      <td><input id="viewcomments" type="checkbox" name="view_comments" value="1" <?php if ($group_details['view_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="canaddcomments"><?php echo $admin_text['addcomments'];?></label></td>
      <td><input id="canaddcomments" type="checkbox" name="add_comments" value="1" <?php if ($group_details['add_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="editcomments"><?php echo $admin_text['editcomments'];?></label></td>
      <td><input id="editcomments" type="checkbox" name="edit_comments" value="1" <?php if ($group_details['edit_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="deletecomments"><?php echo $admin_text['deletecomments'];?></label></td>
      <td><input id="deletecomments" type="checkbox" name="delete_comments" value="1" <?php if ($group_details['delete_comments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewattachments"><?php echo $admin_text['viewattachments'];?></label></td>
      <td><input id="viewattachments" type="checkbox" name="view_attachments" value="1" <?php if ($group_details['view_attachments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="createattachments"><?php echo $admin_text['createattachments'];?></label></td>
      <td><input id="createattachments" type="checkbox" name="create_attachments" value="1" <?php if ($group_details['create_attachments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="deleteattachments"><?php echo $admin_text['deleteattachments'];?></label></td>
      <td><input id="deleteattachments" type="checkbox" name="delete_attachments" value="1" <?php if ($group_details['delete_attachments'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewhistory"><?php echo $admin_text['viewhistory'];?></label></td>
      <td><input id="viewhistory" type="checkbox" name="view_history" value="1" <?php if ($group_details['view_history'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="closeowntasks"><?php echo $admin_text['closeowntasks'];?></label></td>
      <td><input id="closeowntasks" type="checkbox" name="close_own_tasks" value="1" <?php if ($group_details['close_own_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="closeothertasks"><?php echo $admin_text['closeothertasks'];?></label></td>
      <td><input id="closeothertasks" type="checkbox" name="close_other_tasks" value="1" <?php if ($group_details['close_other_tasks'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="assigntoself"><?php echo $admin_text['assigntoself'];?></label></td>
      <td><input id="assigntoself" type="checkbox" name="assign_to_self" value="1" <?php if ($group_details['assign_to_self'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="assignotherstoself"><?php echo $admin_text['assignotherstoself'];?></label></td>
      <td><input id="assignotherstoself" type="checkbox" name="assign_others_to_self" value="1" <?php if ($group_details['assign_others_to_self'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <tr>
      <td><label for="viewreports"><?php echo $admin_text['viewreports'];?></label></td>
      <td><input id="viewreports" type="checkbox" name="view_reports" value="1" <?php if ($group_details['view_reports'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <?php if ($group_details['belongs_to_project'] < '1') { ?>
    <tr>
      <td><label for="groupopen"><?php echo $admin_text['groupenabled'];?></label></td>
      <td><input id="groupopen" type="checkbox" name="group_open" value="1" <?php if ($group_details['group_open'] == "1") { echo "checked=\"checked\"";};?> /></td>
    </tr>
    <?php }; ?>
    <?php endif; ?>
    <tr>
      <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>" /></td>
    </tr>
  </table>
</form>
<?php // }}}
elseif ($area == 'tt'): // {{{
    /////////////////////////
    // Start of task types //
    /////////////////////////
    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['tasktypes'] . '</h3>';
?>
<p><?php echo $admin_text['listnote'];?></p>
<fieldset class="admin">
  <legend><?php echo $admin_text['tasktypes'];?></legend>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update_list" />
      <input type="hidden" name="list_type" value="tasktype" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
    </div>
    <table class="list">
      <?php
      $get_tasktypes = $db->Query("SELECT  tt.*, count(t.task_id) AS used_in_tasks
                                     FROM  {list_tasktype} tt
                                LEFT JOIN  {tasks} t ON ( t.task_type = tt.tasktype_id )
                                    WHERE  project_id = '0'
                                 GROUP BY  tt.tasktype_id, tt.tasktype_name, tt.list_position, tt.show_in_list, tt.project_id
                                 ORDER BY  list_position");
      $countlines = 0;
      while ($row = $db->FetchArray($get_tasktypes)):
      ?>
      <tr>
        <td>
          <input type="hidden" name="id[]" value="<?php echo $row['tasktype_id'];?>" />
          <label for="listname<?php echo $countlines?>"><?php echo $admin_text['name'];?></label>
          <input id="listname<?php echo $countlines?>" type="text" size="15" maxlength="40" name="list_name[]" 
              value="<?php echo htmlspecialchars(stripslashes($row['tasktype_name']),ENT_COMPAT,'utf-8');?>" />
        </td>
        <td title="The order these items will appear in the TaskType list">
          <label for="listposition<?php echo $countlines?>"><?php echo $admin_text['order'];?></label>
          <input id="listposition<?php echo $countlines?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
        </td>
        <td title="Show this item in the TaskType list">
          <label for="showinlist<?php echo $countlines?>"><?php echo $admin_text['show'];?></label>
          <input id="showinlist<?php echo $countlines?>" type="checkbox" name="show_in_list[<?php echo $countlines?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
        <?php if ($row['used_in_tasks'] == 0): ?>
        <td title="Delete this item from the TaskType list">
          <label for="delete<?php echo $row['tasktype_id']?>"><?php echo $admin_text['delete'];?></label>
          <input id="delete<?php echo $row['tasktype_id']?>" type="checkbox" name="delete[<?php echo $row['tasktype_id']?>]" value="1" />
        </td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
      </tr>
      <?php
          $countlines++;
      endwhile;
      ?>
      <tr>
        <td colspan="3"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
  </form>
  <hr />
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="add_to_list" />
      <input type="hidden" name="list_type" value="tasktype" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
    </div>
    <table class="list">
      <tr>
        <td>
          <label for="listnamenew"><?php echo $admin_text['name'];?></label>
          <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" />
        </td>
        <td>
          <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
          <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
        </td>
        <td>
          <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
          <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
        </td>
        <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
    </table>
  </form>
</fieldset>
<?php // }}}
elseif ($area == 'res'): // {{{
    //////////////////////////
    // Start of Resolutions //
    //////////////////////////

    $get_resolution = $db->Query("SELECT  r.*, count(t.task_id) AS used_in_tasks
                                    FROM  {list_resolution} r
                               LEFT JOIN  {tasks} t ON ( t.resolution_reason = r.resolution_id )
                                   WHERE  project_id = '0'
                                GROUP BY  r.resolution_id, r.resolution_name, r.list_position, r.show_in_list, r.project_id
                                ORDER BY  list_position");
    $countlines = 0;
    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['resolutions'] . '</h3>';
?>
<p><?php echo $admin_text['listnote'];?></p>
<fieldset class="admin">
  <legend><?php echo $admin_text['resolutions'];?></legend>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update_list" />
      <input type="hidden" name="list_type" value="resolution" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
    </div>
    <table class="list">
      <?php while ($row = $db->FetchArray($get_resolution)): ?>
      <tr>
        <td>
          <input type="hidden" name="id[]" value="<?php echo $row['resolution_id'];?>" />
          <label for="listname<?php echo $countlines;?>"><?php echo $admin_text['name'];?></label>
          <input id="listname<?php echo $countlines;?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['resolution_name']),ENT_COMPAT,'utf-8');?>" />
        </td>
        <td title="The order these items will be shown in the Resolution list">
          <label for="listposition<?php echo $countlines;?>"><?php echo $admin_text['order'];?></label>
          <input id="listposition<?php echo $countlines;?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
        </td>
        <td title="Show this item in the Resolution list">
          <label for="showinlist<?php echo $countlines;?>"><?php echo $admin_text['show'];?></label>
          <input id="showinlist<?php echo $countlines;?>" type="checkbox" name="show_in_list[<?php echo $countlines;?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
        <?php if ($row['used_in_tasks'] == 0): ?>
        <td title="<?php echo $admin_text['listdeletetip'];?>">
          <label for="delete<?php echo $row['resolution_id']?>"><?php echo $admin_text['delete'];?></label>
          <input id="delete<?php echo $row['resolution_id']?>" type="checkbox" name="delete[<?php echo $row['resolution_id']?>]" value="1" />
        </td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
      </tr>
      <?php
          $countlines++;
      endwhile;
      ?>
      <tr>
        <td colspan="3"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
  </form>
  <hr />
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="add_to_list" />
      <input type="hidden" name="list_type" value="resolution" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
    </div>
    <table class="list">
      <tr>
        <td>
          <label for="listnamenew"><?php echo $admin_text['name'];?></label>
          <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" />
        </td>
        <td>
          <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
          <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
        </td>
        <td>
          <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
          <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
        </td>
        <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
    </table>
  </form>
</fieldset>
<?php // }}}
elseif ($area == 'cat'): // {{{
    /////////////////////////
    // Start of categories //
    /////////////////////////

    $get_categories = $db->Query("SELECT  c.*, count(t.task_id) AS used_in_tasks
                                    FROM  {list_category} c
                               LEFT JOIN  {tasks} t ON (t.product_category = c.category_id)
                                   WHERE  project_id = '0' AND parent_id < '1'
                                GROUP BY  c.category_id, c.project_id, c.category_name, c.list_position,
                                          c.show_in_list, c.category_owner, c.parent_id
                                ORDER BY  list_position");
    $countlines = 0;

    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['categories'] . '</h3>';
?>
<p><?php echo $admin_text['listnote'];?></p>
<fieldset class="admin">
  <legend><?php echo $admin_text['categorylist'];?></legend>
  <p><?php echo $admin_text['listnote'];?></p>
  <div class="admin">
    <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="update_category" />
        <input type="hidden" name="list_type" value="category" />
        <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      </div>
      <table class="list">
        <?php
        while ($row = $db->FetchArray($get_categories)):
            $get_subcategories = $db->Query("SELECT  c.*, count(t.task_id) AS used_in_tasks
                                               FROM  {list_category} c
                                          LEFT JOIN  {tasks} t ON (t.product_category = c.category_id)
                                              WHERE  project_id = '0' AND parent_id = ?
                                           GROUP BY  c.category_id, c.project_id, c.category_name,
                                                     c.list_position, c.show_in_list, c.category_owner, c.parent_id
                                           ORDER BY  list_position", array($row['category_id']));
        ?>
        <tr>
          <td>
            <input type="hidden" name="id[]" value="<?php echo $row['category_id'];?>" />
            <label for="categoryname<?php echo $countlines; ?>"><?php echo $admin_text['name'];?></label>
            <input id="categoryname<?php echo $countlines; ?>" type="text" size="15" maxlength="40" name="list_name[]"
                value="<?php echo htmlspecialchars(stripslashes($row['category_name']),ENT_COMPAT,'utf-8');?>" />
          </td>
          <td title="<?php echo $admin_text['listordertip'];?>">
            <label for="listposition<?php echo $countlines; ?>"><?php echo $admin_text['order'];?></label>
            <input id="listposition<?php echo $countlines; ?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
          </td>
          <td title="<?php echo $admin_text['listshowtip'];?>">
            <label for="showinlist<?php echo $countlines; ?>"><?php echo $admin_text['show'];?></label>
            <input id="showinlist<?php echo $countlines; ?>" type="checkbox" name="show_in_list[<?php echo $countlines; ?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
          </td>
          <td title="<?php echo $admin_text['categoryownertip'];?>">
            <label for="categoryowner<?php echo $countlines; ?>"><?php echo $admin_text['owner'];?></label>
            <select id="categoryowner<?php echo $countlines; ?>" name="category_owner[]">
              <option value=""><?php echo $admin_text['selectowner'];?></option>
              <?php $fs->listUsers($row['category_owner'], 0); ?>
            </select>
          </td>
          <?php if ($row['used_in_tasks'] == 0 and $get_subcategories->RowCount() < 1): ?>
          <td title="<?php echo $admin_text['listdeletetip'];?>">
            <label for="delete<?php echo $row['category_id']?>"><?php echo $admin_text['delete'];?></label>
            <input id="delete<?php echo $row['category_id']?>" type="checkbox" name="delete[<?php echo $row['category_id']?>]" value="1" />
          </td>
          <?php else: ?>
          <td>&nbsp;</td>
          <?php endif; ?>
        </tr>
        <?php
            $countlines++;
            // Now we have to cycle through the subcategories
            while ($subrow = $db->FetchArray($get_subcategories)):
        ?>
        <tr>
          <td>
            <input type="hidden" name="id[]" value="<?php echo $subrow['category_id'];?>" />
            &rarr;
            <label for="categoryname<?php echo $countlines; ?>"><?php echo $admin_text['name'];?></label>
            <input id="categoryname<?php echo $countlines; ?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo stripslashes($subrow['category_name']);?>" />
          </td>
          <td title="<?php echo $admin_text['listordertip'];?>">
            <label for="listposition<?php echo $countlines; ?>"><?php echo $admin_text['order'];?></label>
            <input id="listposition<?php echo $countlines; ?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $subrow['list_position'];?>" />
          </td>
          <td title="<?php echo $admin_text['listshowtip'];?>">
            <label for="showinlist<?php echo $countlines; ?>"><?php echo $admin_text['show'];?></label>
            <input id="showinlist<?php echo $countlines; ?>" type="checkbox" name="show_in_list[<?php echo $countlines; ?>]"
                value="1" <?php if ($subrow['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
          </td>
          <td title="<?php echo $admin_text['categoryownertip'];?>">
            <label for="categoryowner<?php echo $countlines; ?>"><?php echo $admin_text['owner'];?></label>
            <select id="categoryowner<?php echo $countlines; ?>" name="category_owner[]">
              <option value=""><?php echo $admin_text['selectowner'];?></option>
              <?php $fs->listUsers($subrow['category_owner'], 0); ?>
            </select>
          </td>
          <?php if ($subrow['used_in_tasks'] == 0): ?>
          <td title="<?php echo $admin_text['listdeletetip'];?>">
            <label for="delete<?php echo $subrow['category_id']?>"><?php echo $admin_text['delete'];?></label>
            <input id="delete<?php echo $subrow['category_id']?>" type="checkbox" name="delete[<?php echo $subrow['category_id']?>]" value="1" />
          </td>
          <?php else: ?>
          <td>&nbsp;</td>
          <?php endif; ?>
        </tr>
        <?php
                $countlines++;
            endwhile;
        endwhile;
        ?>
        <tr>
          <td colspan="4"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
        </tr>
      </table>
    </form>

    <hr />

    <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="add_category" />
        <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      </div>
      <table class="list">
        <tr>
          <td>
            <label for="listnamenew"><?php echo $admin_text['name'];?></label>
            <input id="listnamenew" type="text" size="15" maxlength="30" name="list_name" />
          </td>
          <td title="<?php echo $admin_text['listordertip'];?>">
            <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
            <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
          </td>
          <td title="<?php echo $admin_text['listshowtip'];?>">
            <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
            <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
          </td>
          <td title="<?php echo $admin_text['categoryownertip'];?>" colspan="2">
            <label for="categoryownernew" ><?php echo $admin_text['owner'];?></label>
            <select id="categoryownernew" name="category_owner">
              <option value=""><?php echo $admin_text['selectowner'];?></option>
              <?php $fs->listUsers($novar, 0); ?>
            </select>
          </td>
          <td colspan="2" title="<?php echo $admin_text['categoryparenttip'];?>">
            <label for="parent_id"><?php echo $admin_text['subcategoryof'];?></label>
            <select id="parent_id" name="parent_id">
              <option value=""><?php echo $admin_text['notsubcategory'];?></option>
              <?php
              $cat_list = $db->Query("SELECT  category_id, category_name
                                        FROM  {list_category}
                                       WHERE  project_id= 0 AND show_in_list= 1 AND parent_id < 1
                                    ORDER BY  list_position");
              while ($row = $db->FetchArray($cat_list)) {
                  $category_name = stripslashes($row['category_name']);
                  if (isset($_GET['cat']) && $_GET['cat'] == $row['category_id']) {
                      echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
                  } else {
                      echo "<option value=\"{$row['category_id']}\">$category_name</option>\n";
                  }
              }
              ?>
            </select>
          </td>
          <td class="buttons"><br /><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
        </tr>
      </table>
    </form>
  </div>
</fieldset>
<?php // }}}
elseif ($area == 'os'): // {{{
    ////////////////////////////////////////
    // Show the list of Operating Systems //
    ////////////////////////////////////////
    $get_os = $db->Query("SELECT  os.*, count(t.task_id) AS used_in_tasks
                            FROM  {list_os} os
                       LEFT JOIN  {tasks} t ON (t.operating_system = os.os_id AND t.attached_to_project = os.project_id)
                           WHERE  os.project_id = '0'
                        GROUP BY  os.os_id, os.project_id, os.os_name, os.list_position, os.show_in_list
                        ORDER BY  list_position");
    $countlines = 0;
    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['operatingsystems'] . '</h3>';
?>
<p><?php echo $admin_text['listnote'];?></p>
<fieldset class="admin">
  <legend><?php echo $admin_text['operatingsystems'];?></legend>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update_list" />
      <input type="hidden" name="list_type" value="os" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
    </div>
    <table class="list">
      <?php while ($row = $db->FetchArray($get_os)): ?>
      <tr>
        <td>
          <input type="hidden" name="id[]" value="<?php echo $row['os_id'];?>" />
          <label for="listname<?php echo $countlines;?>"><?php echo $admin_text['name'];?></label>
          <input id="listname<?php echo $countlines;?>" type="text" size="15" maxlength="40" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['os_name']),ENT_COMPAT,'utf-8');?>" />
        </td>
        <td title="The order these items will appear in the Operating System list">
          <label for="listposition<?php echo $countlines;?>"><?php echo $admin_text['order'];?></label>
          <input id="listposition<?php echo $countlines;?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
        </td>
        <td title="Show this item in the Operating System list">
          <label for="showinlist<?php echo $countlines;?>"><?php echo $admin_text['show'];?></label>
          <input id="showinlist<?php echo $countlines;?>" type="checkbox" name="show_in_list[<?php echo $countlines;?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
        </td>
        <?php if ($row['used_in_tasks'] == 0): ?>
        <td title="Delete this item from the Operating System list">
          <label for="delete<?php echo $row['os_id']?>"><?php echo $admin_text['delete'];?></label>
          <input id="delete<?php echo $row['os_id']?>" type="checkbox" name="delete[<?php echo $row['os_id']?>]" value="1" />
        </td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
      </tr>
      <?php
          $countlines++;
      endwhile;
      ?>
      <tr>
        <td colspan="3"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
      </tr>
    </table>
  </form>

  <hr />

  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="add_to_list" />
      <input type="hidden" name="list_type" value="os" />
      <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
    </div>
    <table class="list">
      <tr>
        <td>
          <label for="listnamenew"><?php echo $admin_text['name'];?></label>
          <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" />
        </td>
        <td>
          <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
          <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
        </td>
        <td>
          <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
          <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
        </td>
        <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
      </tr>
    </table>
  </form>
</fieldset>
<?php // }}}
elseif ($area == 'ver'): // {{{
    ///////////////////////////////
    // Show the list of Versions //
    ///////////////////////////////
    $get_version = $db->Query("SELECT  v.*, count(t.task_id) AS used_in_tasks
                                 FROM  {list_version} v
                            LEFT JOIN  {tasks} t ON (t.product_version = v.version_id OR t.closedby_version = v.version_id AND t.attached_to_project = v.project_id)
                                WHERE  v.project_id = '0'
                             GROUP BY  v.version_id, v.project_id, v.version_name, v.list_position, v.show_in_list, v.version_tense
                             ORDER BY  list_position"
                              );
    $countlines = 0;
    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $admin_text['versions'] . '</h3>';
?>
<p><?php echo $admin_text['listnote'];?></p>
<fieldset class="admin">
  <legend><?php echo $admin_text['versions'];?></legend>
  <p><?php echo $admin_text['listnote'];?></p>
  <div class="admin">
    <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="update_version_list" />
        <input type="hidden" name="list_type" value="version" />
        <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      </div>
      <table class="list">
        <?php while ($row = $db->FetchArray($get_version)): ?>
        <tr>
          <td>
            <input type="hidden" name="id[]" value="<?php echo $row['version_id'];?>" />
            <label for="listname<?php echo $countlines;?>"><?php echo $admin_text['name'];?></label>
            <input id="listname<?php echo $countlines;?>" type="text" size="15" maxlength="20" name="list_name[]" value="<?php echo htmlspecialchars(stripslashes($row['version_name']),ENT_COMPAT,'utf-8');?>" />
          </td>
          <td title="<?php echo $admin_text['listordertip'];?>">
            <label for="listposition<?php echo $countlines;?>"><?php echo $admin_text['order'];?></label>
            <input id="listposition<?php echo $countlines;?>" type="text" size="3" maxlength="3" name="list_position[]" value="<?php echo $row['list_position'];?>" />
          </td>
          <td title="<?php echo $admin_text['listshowtip'];?>">
            <label for="showinlist<?php echo $countlines;?>"><?php echo $admin_text['show'];?></label>
            <input id="showinlist<?php echo $countlines;?>" type="checkbox" name="show_in_list[<?php echo $countlines;?>]" value="1" <?php if ($row['show_in_list'] == '1') { echo "checked=\"checked\"";};?> />
          </td>
          <td title="<?php echo $admin_text['listtensetip'];?>">
            <label for="tense<?php echo $countlines;?>"><?php echo $admin_text['tense'];?></label>
            <select id="tense<?php echo $countlines;?>" name="version_tense[<?php echo $countlines;?>]">
              <option value="1" <?php if ($row['version_tense'] == '1') { echo 'selected="selected"';};?>><?php echo $admin_text['past'];?></option>
              <option value="2" <?php if ($row['version_tense'] == '2') { echo 'selected="selected"';};?>><?php echo $admin_text['present'];?></option>
              <option value="3" <?php if ($row['version_tense'] == '3') { echo 'selected="selected"';};?>><?php echo $admin_text['future'];?></option>
            </select>
          </td>
          <?php if ($row['used_in_tasks'] == 0): ?>
          <td title="<?php echo $admin_text['listdeletetip'];?>">
            <label for="delete<?php echo $row['version_id']?>"><?php echo $admin_text['delete'];?></label>
            <input id="delete<?php echo $row['version_id']?>" type="checkbox" name="delete[<?php echo $row['version_id']?>]" value="1" />
          </td>
          <?php else: ?>
          <td>&nbsp;</td>
          <?php endif; ?>
        </tr>
        <?php
            $countlines++;
        endwhile;
        ?>
        <tr>
          <td colspan="4"></td><td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['update'];?>" /></td>
        </tr>
      </table>
    </form>

    <hr />

    <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="add_to_version_list" />
        <input type="hidden" name="list_type" value="version" />
        <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />
      </div>
      <table class="list">
        <tr>
          <td>
            <label for="listnamenew"><?php echo $admin_text['name'];?></label>
            <input id="listnamenew" type="text" size="15" maxlength="20" name="list_name" />
          </td>
          <td>
            <label for="listpositionnew"><?php echo $admin_text['order'];?></label>
            <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
          </td>
          <td>
            <label for="showinlistnew"><?php echo $admin_text['show'];?></label>
            <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
          </td>
          <td title="<?php echo $admin_text['listtensetip'];?>">
            <label for="tensenew"><?php echo $admin_text['tense'];?></label>
            <select id="tensenew" name="version_tense">
              <option value="1"><?php echo $admin_text['past'];?></option>
              <option value="2" selected="selected"><?php echo $admin_text['present'];?></option>
              <option value="3"><?php echo $admin_text['future'];?></option>
            </select>
          </td>
          <td class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['addnew'];?>" /></td>
        </tr>
      </table>
    </form>
  </div>
</fieldset>
<?php // }}}
elseif ($area == 'newproject'): // {{{
    ///////////////////////////////////
    // Start of adding a new project //
    ///////////////////////////////////
    echo '<h3>' . $admin_text['admintoolbox'] . ':: ' . $newproject_text['createnewproject'] . '</h3>';
?>
<fieldset class="admin">
  <legend><?php echo $admin_text['newproject'];?></legend>
  <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="newproject" />
    </div>
    <table class="admin">
      <tr>
        <td><label for="projecttitle"><?php echo $newproject_text['projecttitle'];?></label></td>
        <td><input id="projecttitle" name="project_title" type="text" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td><label for="themestyle"><?php echo $newproject_text['themestyle'];?></label></td>
        <td>
          <select id="themestyle" name="theme_style">
            <?php
            if ($handle = opendir('themes/')) {
                $theme_array = array();
                while (false !== ($dir = readdir($handle))) {
                    if ($dir != "." && $dir != ".." && file_exists("themes/$dir/theme.css")) {
                        array_push($theme_array, $dir);
                    }
                }
                closedir($handle);
            }
            sort($theme_array);

            while (list($key, $val) = each($theme_array)) {
                echo "<option class=\"adminlist\">$val</option>\n";
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td><label for="show_logo"><?php echo $newproject_text['showlogo'];?></label></td>
        <td><input id="show_logo" type="checkbox" name="show_logo" value="1" checked="checked" /></td>
      </tr>
      <tr>
        <td><label for="intro_message"><?php echo $newproject_text['intromessage'];?></label></td>
        <td><textarea id="intro_message" name="intro_message" rows="10" cols="50"></textarea></td>
      </tr>
      <tr>
        <td><label for="othersview"><?php echo $newproject_text['othersview'];?></label></td>
        <td><input id="othersview" type="checkbox" name="others_view" value="1" checked="checked" /></td>
      </tr>
      <tr>
        <td><label for="anonopen"><?php echo $newproject_text['allowanonopentask'];?></label></td>
        <td><input id="anonopen" type="checkbox" name="anon_open" value="1" /></td>
      </tr>
      <tr>
        <td class="buttons" colspan="2"><input class="adminbutton" type="submit" value="<?php echo $newproject_text['createthisproject'];?>" /></td>
      </tr>
    </table>
  </form>
</fieldset>
<?php // }}}
else:
    $fs->Redirect( $fs->CreateURL('error', null) );
endif;
?>
</div>
