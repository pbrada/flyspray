<div id="menu">
  <em>{$user->infos['real_name']} ({$user->infos['user_name']})</em>

<ul id="menu-list">
<?php
if ($user->perms['open_new_tasks']): ?>
  <li>
  <a id="newtasklink" href="{$fs->CreateURL('newtask', $proj->id)}"
    accesskey="a">{$language['addnewtask']}</a>
  </li>
<?php
endif;

if ($user->perms['view_reports']): ?>
  <li>
  <a id="reportslink" href="{$fs->CreateURL('reports', null)}"
    accesskey="r">{$language['reports']}</a>
  </li>
<?php
endif; ?>
  <li>
  <a id="editmydetailslink" href="{$fs->CreateURL('myprofile', null)}"
    accesskey="e">{$language['editmydetails']}</a>
  </li>
  <li>
<?php if (!empty($user->infos['last_search'])): ?>
  <a id="lastsearchlink" href="{$user->infos['last_search']}"
    accesskey="m">{$language['lastsearch']}</a>
<?php else: ?>
  <a id="lastsearchlink" href="{$baseurl}"
    accesskey="m">{$language['lastsearch']}</a>
<?php endif; ?>
  </li>
<?php if ($user->perms['is_admin']): ?>
  <li>
  <a id="optionslink" href="{$fs->CreateURL('admin', 'prefs')}">{$language['admintoolbox']}</a>
  </li>
<?php endif; ?>

<?php if ($user->perms['manage_project']): ?>
  <li>
  <a id="projectslink"
    href="{$fs->CreateURL('pm', 'prefs', $proj->id)}">{$language['manageproject']}</a>
  </li>
<?php endif; ?>
  <li class="last">
  <a id="logoutlink" href="{$fs->CreateURL('logout', null)}"
    accesskey="l">{$language['logout']}</a>
  </li>
<?php if ($user->perms['manage_project'] && $pm_pendingreq_num): ?>
  <li>
  <a id="pendingreq" class="attention"
    href="{$fs->CreateURL('pm', 'pendingreq', $proj->id)}">{$num_req} {$language['pendingreq']}</a>
  </li>
<?php endif; ?>
</ul>

</div>
