<?php
/***************************************************************************
 *
 *	Author:	Jordan Mussi
 *	File:	./inc/plugins/ModCPUsername.php
 *  
 *	License:
 *  
 *	This program is free software: you can redistribute it and/or modify it under 
 *	the terms of the GNU General Public License as published by the Free Software 
 *	Foundation, either version 3 of the License, or (at your option) any later 
 *	version.
 *	
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY 
 *	WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 *	FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License 
 *	for more details.
 *	
 ***************************************************************************/
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("modcp_do_editprofile_updated_user", "ModCPUsername_do_modcp");
$plugins->add_hook("modcp_editprofile_end", "ModCPUsername_modcp");
if(!defined("PLUGINLIBRARY"))
{
	define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}
function ModCPUsername_info()
{
	global $lang;
	$lang->load("ModCPUsername");
	return array(
		"name"			=> $lang->ModCPUsername,
		"description"	=> $lang->ModCPUsername_desc,
		"website"		=> "https://github.com/JordanMussi/ModCPUsername/",
		"author"		=> "Jordan Mussi",
		"authorsite"	=> "https://github.com/JordanMussi/",
		"guid"          => "556a9d32a35183cdb8e012ed47f99256",
		"version"		=> "1",
		"compatibility" => "16*"
	);
}

function ModCPUsername_is_installed()
{
	global $mybb;
	
	if($mybb->settings['ModCPUsername_usergroups'])
	{
		return true;
	}
	return false;
}

function ModCPUsername_install()
{
	global $db, $lang, $PL;
	$lang->load("ModCPUsername");
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message($lang->sprintf($lang->ModCPUsername_pluginlibrary_missing, $lang->ModCPUsername_installed), "error");
		admin_redirect("index.php?module=config-plugins");
	}
	$PL or require_once PLUGINLIBRARY;

	if ((int) $PL->version < 9) {
		flash_message($lang->ModCPUsername_pluginlibrary_outdated, 'error');
		admin_redirect('index.php?module=config-plugins');
	}
	$PL->edit_core('ModCPUsername', 'modcp.php',
			array(
				array(
				'search' => '// Set the data of the user in the datahandler.',
				'before' => '$plugins->run_hooks("modcp_do_editprofile_updated_user");'
					),
				),
			true);

	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");
	
	$setting_group = array(
		'name'			=>	'ModCPUsername',
		'title'			=>	$lang->ModCPUsername_setting_group,
		'description'	=>	$lang->ModCPUsername_setting_group_desc,
		'disporder'		=>	$rows+1,
		'isdefault'		=>	'0'
	);
	$db->insert_query('settinggroups', $setting_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		'name'			=> 'ModCPUsername_usergroups',
		'title'			=> $lang->ModCPUsername_setting_allowed_groups,
		'description'	=> $lang->ModCPUsername_setting_allowed_groups_desc,
		'optionscode'	=> 'text',
		'value'			=> '3,4,6'
		);
		
	foreach($settings as $setting)
	{
		$setting['gid'] = intval($gid);
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();
}

function ModCPUsername_uninstall()
{
	global $db, $lang, $PL;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message($lang->sprintf($lang->ModCPUsername_pluginlibrary_missing, $lang->ModCPUsername_uninstalled), "error");
		admin_redirect("index.php?module=config-plugins");
	}
	$PL or require_once PLUGINLIBRARY;

	if ((int) $PL->version < 9) {
		flash_message($lang->sprintf($lang->ModCPUsername_pluginlibrary_outdated, "9"), 'error');
		admin_redirect('index.php?module=config-plugins');
	}
	$PL->edit_core('ModCPUsername', 'modcp.php',
		array(),
		true);
	$db->delete_query("settings", "`name` = 'ModCPUsername_usergroups'");
	$db->delete_query("settinggroups", "`name` = 'ModCPUsername'");
	rebuild_settings();
}

function ModCPUsername_activate()
{
	global $db, $lang;
	$lang->load("ModCPUsername");
	$db->update_query("settinggroups", "`description` = '".$lang->ModCPUsername_setting_group_desc."'", "`name` = 'ModCPUsername'");
}

function ModCPUsername_deactivate()
{
	global $db, $lang;
	$lang->load("ModCPUsername");
	$db->update_query("settinggroups", "`description` = '".$lang->ModCPUsername_setting_group_desc_de."'", "`name` = 'ModCPUsername'");
}

function ModCPUsername_do_modcp()
{
	global $updated_user, $mybb;
	$usergroups = explode(",",$mybb->settings['ModCPUsername_usergroups']);
	if(in_array($mybb->user['usergroup'], $usergroups))
	{
		if($mybb->input['username'] != '')
		{
			$updated_user['username'] = $mybb->input['username'];
		}
	}
}

function ModCPUsername_modcp()
{
	global $mybb, $user, $profile_link;
	$usergroups = explode(",",$mybb->settings['ModCPUsername_usergroups']);
	if(in_array($mybb->user['usergroup'], $usergroups))
	{
		$profile_link = "<input type=\"text\" class=\"textbox\" name=\"username\" size=\"25\" maxlength=\"30\" value=\"{$user['username']}\">";
	}
}
?>