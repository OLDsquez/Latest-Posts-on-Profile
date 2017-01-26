<?php
/**************************************************************************\
||========================================================================||
|| Latest Posts on Profile ||
|| Copyright 2016 ||
|| Version 1.1 ||
|| Made by fizz on the official MyBB board ||
|| http://community.mybb.com/user-36020.html ||
|| https://github.com/squez/..... ||
|| I don't take responsibility for any errors caused by this plugin. ||
|| Always keep MyBB up to date and always keep this plugin up to date. ||
|| You may NOT sell this plugin, ||
|| remove copyrights, or claim it as your own in any way. ||
||========================================================================||
\*************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
    die();

// Plugin version
define('LPOP_VERSION', '1.1');

function lpop_info()
{
	return array(
		'name'			=> 'Latest Posts on Profile',
		'description'	=> 'Display a user\'s latest posts on their profile.',
		'website'		=> 'https://community.mybb.com/user-36020.html',
		'author'		=> 'fizz',
		'authorsite'	=> 'https://community.mybb.com/user-36020.html',
		'version'		=> LPOP_VERSION,
		'guid'			=> '',
		'codename'		=> 'lpop',
		'compatibility'	=> '18*'
	);
}

// Add hooks
if(isPluginEnabled())
	$plugins->add_hook('member_profile_end', 'lpop_display');

function lpop_is_installed()
{
	global $db;

	return $db->num_rows($db->simple_select('settings', 'name', 'name=\'lpop_enabled\'')) >= 1;
}

function lpop_install()
{
	global $lang, $mybb, $db;

	$group = array(
        'name'          => 'lpop',
        'title'         => 'Latest Posts on Profile',
        'description'   => 'Edit the settings for Latest Posts on Profile here.',
        'disporder'     => '1',
        'isdefault'     => 'no',
    );
    $db->insert_query('settinggroups', $group);
    $gid = intval($db->insert_id());

    $psettings[] = array(
        'name'          => 'lpop_enabled',
        'title'         => 'Enabled',
        'description'   => 'Do you want to enable Latest Posts on Profile?',
        'optionscode'   => 'yesno',
        'value'         => '1',
        'disporder'     => '1',
        'gid'           => $gid
    );

    $psettings[] = array(
        'name'          => 'lpop_numposts',
        'title'         => 'Numposts',
        'description'   => 'How many posts should be displayed on a user profile? (max 25)',
        'optionscode'   => 'text',
        'value'         => '3',
        'disporder'     => '2',
        'gid'           => $gid
    );

    $psettings[] = array(
        'name'          => 'lpop_excludeforums',
        'title'         => 'Excluded Forums',
        'description'   => 'Posts in these forums will not be shown on users\\\' profiles. Separate forum ids with a comma. (Leave blank to allow all groups to use.)',
        'optionscode'   => 'text',
        'value'         => '4,12,9',
        'disporder'     => '3',
        'gid'           => $gid
    );

    $psettings[] = array(
        'name'          => 'lpop_excludegroups',
        'title'         => 'Excluded Usergroups',
        'description'   => 'Latest Posts on Profile will not affect users in these usergroups. Separate usergroup ids with a comma. (Leave blank to allow all groups to use.)',
        'optionscode'   => 'text',
        'value'         => '',
        'disporder'     => '4',
        'gid'           => $gid
    );

    $psettings[] = array(
        'name'          => 'lpop_dateformat',
        'title'         => 'Date Format',
        'description'   => 'Format the format of displayed posting dates. <strong>Leave this alone unless you know what you\\\'re doing.</strong> Reference can be found
        					on the <a href="https://secure.php.net/manual/en/function.date.php" target="_NEW">PHP website</a> under the "Format" section. If you break this
        					and need to reset it just clear this box and update and the plugin will reset it for you.',
        'optionscode'   => 'text',
        'value'         => 'M d, Y @ H:i T',
        'disporder'     => '5',
        'gid'           => $gid
    );

    $db->insert_query_multiple('settings', $psettings);

    rebuild_settings();
}

function lpop_activate()
{
	global $db, $lang, $mybb;

	// Enable the plugin
	if($mybb->settings['lpop_enabled'] == 0)
		$db->update_query('settings', array('value' => 1), 'name=\'lpop_enabled\'');

	if(!$lang->lpop)
		$lang->load('lpop');

	// Add template <tr><th>Thread</th><th>Forum</th><th># Posts</th><th>Date Posted</th></tr>
	$template[] = array(
		'tid'		=> 'NULL',
		'title'		=> 'lpop_profile',
		'template'	=> $db->escape_string('
	        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	        <tr>
	        	<td colspan="3" class="thead"><strong>{$lang->lpop_title}</strong></td>
	        </tr>
	        <tr>
		        <th class="trow1">{$lang->lpop_thread}</th>
		        <th class="trow1">{$lang->lpop_forum}</th>
		        <th class="trow1">{$lang->lpop_postdate}</th>
	        </tr>
	        {$lpop_data}
	        </table>'),
		'dateline'	=> TIME_NOW,
		'sid'		=> '-2'
	);

	$template[] = array(
		'tid'		=> 'NULL',
		'title'		=> 'lpop_row',
		'template'	=> $db->escape_string('
			<tr>
				<td>{$thread}</td>
				<td>{$forum}</td>
				<td>{$postdate}</td>
			</tr>'),
		'dateline'	=> TIME_NOW,
		'sid'		=> '-2'
	);
	$db->insert_query_multiple('templates', $template);

	require_once(MYBB_ROOT . 'inc/adminfunctions_templates.php');
	find_replace_templatesets('member_profile', '#' . preg_quote('{$adminoptions}') . '#', '{$adminoptions}{$lpop_profile}');

	rebuild_settings();
}

function lpop_deactivate()
{
	global $db, $mybb;

	// Disable the plugin
	if($mybb->settings['lpop_enabled'] == 1)
		$db->update_query('settings', array('value' => 0), "name='lpop_enabled'");

	// Delete templates
	$db->delete_query('templates', "title LIKE 'lpop_%'");

	require_once(MYBB_ROOT . 'inc/adminfunctions_templates.php');
	find_replace_templatesets('member_profile', '#' . preg_quote('{$lpop_profile}') . '#', '');

	rebuild_settings();
}

function lpop_uninstall()
{
	global $db;

	$r = $db->fetch_array($db->simple_select('settinggroups', 'gid', 'name=\'lpop\''));
	$db->delete_query('settinggroups', "gid='{$r['gid']}'");
	$db->delete_query('settings', "gid='{$r['gid']}'");

	rebuild_settings();
}

function lpop_display()
{
	// note: $lpop_profile must be global for it be inserted properly into the template
	global $db, $templates, $theme, $lang, $mybb, $lpop_profile;

	// Make sure plugin is enabled
	if(!isPluginEnabled())
		return;

	if(!$lang->lpop)
		$lang->load('lpop');

	// Make sure $uid is valid int
	$uid = $mybb->get_input('uid', MyBB::INPUT_INT);

	if(!is_int($uid) || $uid <= 0)
		return;

	// Check if user is in excluded usergroup
	if(inExcludedUsergroup($uid))
		return;

	// Trust admins can figure out how to properly mess with the formatting without breaking everything :D
	if(!isset($mybb->settings['lpop_dateformat']) || $mybb->settings['lpop_dateformat'] == '')
	{
		// If it's blank assume they're trying to reset dateformat to default and update db accordingly
		$dateformat = $mybb->settings['lpop_dateformat'] = 'M d, Y @ H:i T';
		$db->update_query('settings', array('value' => 'M d, Y @ H:i T'), 'name = \'lpop_dateformat\'');
	}
	else
		$dateformat = $mybb->settings['lpop_dateformat'];

	// Build thread and forum links and insert them into the template
	$data = fetch_post_data($uid);
	foreach($data as $d)
	{
		$thread = '<a href="'.$mybb->settings['bburl'].'/'.get_thread_link($d['tid']).'#pid'.$d['pid'].'">'.$d['tsubject'].'</a>';
		$forum = '<a href="'.$mybb->settings['bburl'].'/'.get_forum_link($d['fid']).'">'.$d['fname'].'</a>';
		$postdate = date($dateformat, $d['dateline']);

		eval("\$lpop_data .= \"" . $templates->get('lpop_row') . "\";");
	}

	eval("\$lpop_profile = \"" . $templates->get('lpop_profile') . "\";");
}

/**
* Retrieve data on user's most recent posts.
*
* @param int
*
* @return array
*/
function fetch_post_data($uid = 0)
{
	global $db, $mybb;

	if($uid <= 0)
		return array();

	$latestposts = array();

	// Ignore excluded forums
	if(!empty($mybb->settings['lpop_excludeforums']) && !preg_match('/[^0-9,]+/', $mybb->settings['lpop_excludeforums']))
		$exclude = "AND p.fid NOT IN ({$mybb->settings['lpop_excludeforums']})";
	else
		$exclude = '';

	// You shouldn't need to display more than 25 posts...
	if(intval($mybb->settings['lpop_numposts']) < 1 || intval($mybb->settings['lpop_numposts']) > 25)
		$limit = 5;
	else
		$limit = $mybb->settings['lpop_numposts'];

	$tp = TABLE_PREFIX;
	$q = $db->write_query("
		SELECT p.pid, p.tid, p.fid, p.dateline, t.subject, f.name
		FROM {$tp}posts p
		LEFT JOIN {$tp}threads t ON (p.tid = t.tid)
		LEFT JOIN {$tp}forums f ON (p.fid = f.fid)
		WHERE p.uid = '$uid' $exclude
		ORDER BY dateline DESC
		LIMIT $limit
	");

	while($p = $db->fetch_array($q))
	{
		$latestposts[] = array(
			'pid'		=> $p['pid'],
			'tid'		=> $p['tid'],
			'tsubject'	=> $p['subject'],
			'fid'		=> $p['fid'],
			'fname'		=> $p['name'],
			'dateline'	=> $p['dateline']
		);
	}

	return $latestposts;
}

/**
* Check if LPoP is enabled.
*
* @return bool
*/
function isPluginEnabled()
{
	global $mybb;

	if($mybb->settings['lpop_enabled'] == 1)
		return true;

	return false;
}

/**
* Check if user being viewed should be excluded from plugin.
*
* @param int
*
* @return bool
*/
function inExcludedUsergroup($uid = 0)
{
	global $db, $mybb;

	if(!empty($mybb->settings['lpop_excludegroups']))
	{
		$usergroup = $db->fetch_array($db->simple_select('users', 'usergroup', "uid='$uid'"));

		if(strpos($mybb->settings['lpop_excludegroups'], ',') !== false)
		{
			$groups = explode(',', $mybb->settings['lpop_excludegroups']);

			// User is in an excluded group, return
			if(in_array($usergroup['usergroup'], $groups))
				return true;
		}
		else
		{
			if($usergroup['usergroup'] == $mybb->settings['lpop_excludegroups'])
				return true;
		}
	}

	return false;
}
/*
TO DO:
 * ---exclude forums
 * ---exclude usergroups
 * ---fix $timeformat check to actually update the lpop_dateformat setting in the db if the user is trying to reset it
 * ---add usergroup exclusion to separate func -> v1.1 (MyBB::INPUT_INT fixed already)
 * fight the horde, sing and cry
 * valhalla I am coming...
*/
