<?php
if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
} 

$plugins->add_hook('member_profile_end', 'showrefer');
$plugins->add_hook("member_do_register_end", "showrefer_send_pm");

// The information that shows up on the plugin manager
function showrefer_info()
{
	return array(
		"name"		=> "Referral in Profile",
		"description"	=> "This plugin displays the user's referrals in their profiles.",
		"website"	=> "http://www.leefish.nl",
		"author"	=> "LeeFish based on work by Jeff Chan",
		"authorsite"	=> "http://www.leefish.nl",
		"version"	=> "1.1",
		'compatibility' => "1.6"
	);
}

// This function is called to establish whether the plugin is installed or not
function showrefer_is_installed()
{
	global $db;
	
	$query = $db->simple_select("templates", "`title`", "`title` = 'member_profile_showrefer'");
	$g = $db->fetch_array($query);
	if($g)
	{
		return true;
	}
	return false;
}

function showrefer_install() {
	global $db;

	$showrefer_template = array(
		"title"		=> 'member_profile_showrefer',
		"template"	=> "<tr>
	<td class=\"trow1\ \"valign = top\"><strong>Referrals ({\$showrefer_count})</strong></td>
	<td class=\"trow1\">{\$showrefer_referrals}</td>
</tr>",
		"sid"		=> -1,
		"version"	=> 120,
		"status"	=> '',
		"dateline"	=> 1134703642,
	);
	//Create Referrals in Profiles template
	$db->insert_query('templates', $showrefer_template);
}

// This function runs when the plugin is activated.
function showrefer_activate()
{
	// Insert code into profile template
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#{\$reputation}#', "{\$reputation}\n{\$showrefer}\n");
}

// This function runs when the plugin is deactivated.
function showrefer_deactivate()
{
	// Remove code from template
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#(\n?){\$showrefer}(\n?)#', '', 0);
}

// This function runs when the plugin is deactivated.
function showrefer_uninstall()
{
    global $db;

	// Remove code from template
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#(\n?){\$showrefer}(\n?)#', '', 0);
	
	//Delete Referrals in Profiles template
	$db->delete_query("templates", "`title` = 'member_profile_showrefer'");
	
	//Rebuild settings.php
	rebuild_settings();
}

function showrefer()
{
	global $mybb, $templates, $db, $theme, $showrefer_count, $showrefer, $showrefer_referrals;
	
	$query = $db->write_query("
				SELECT u.*, u.username, u.uid, u.referrer, u.referrals, u.avatar
				FROM ".TABLE_PREFIX."users u
				WHERE u.referrer > '0'
				AND u.referrer = '".intval($mybb->input['uid'])."'
				");			
				
	$showrefer_count = $db->num_rows($query);
	
	
	
	if($showrefer_count > 0)
	{
		$sep = "";
		while($referral = $db->fetch_array($query))
		{
		    $avatar = htmlspecialchars_uni($referrals['avatar']);
			$showrefer_referrals .= $sep.$avatar.build_profile_link($referral['username'],$referral['uid']);
			$sep = ", ";
			
			
        	}
	}
	else
	{
		$showrefer_referrals = "None";
	}
	eval("\$showrefer = \"".$templates->get('member_profile_showrefer')."\";");
}
function showrefer_send_pm()
    {   
	
	global $lang, $mybb, $db,$user,$user_info,$plugins;
	
		if($user['referrer'] != "" && isset($user_info)) {
			//Fetch Referrer Information
			$query = $db->simple_select("users", "uid,username" , "`username`='".$user['referrer']."'");
			$refers = $db->fetch_array($query);
			//Set newly registered user information (Referral)
			$toid = $refers['uid'];
			$newb = $user_info;
			$new_username = $newb['username'];
			$new_uid = $newb['uid'];
			$newblink = "[URL=".$mybb->settings['bburl']."/member.php?action=profile&uid=".$newb['uid']."]".$newb['username']."[/URL]";

			require_once MYBB_ROOT."inc/datahandlers/pm.php";
			$pmhandler = new PMDataHandler();
			$pmhandler->admin_override = true;
				$pm = array(
				"subject" => "New member referred by you.",
				"message" => "Thanks for referring me. Check out my profile $newblink :) ",
				"icon" => "-1",
				"toid" => $toid,
				"fromid" => $new_uid,
				"do" => '',
				"pmid" => ''
			);
			$pm['options'] = array(
				"signature" => "0",
				"disablesmilies" => "0",
				"savecopy" => "0",
				"readreceipt" => "0"
			);
		
			$pmhandler->set_data($pm);
					
			if(!$pmhandler->validate_pm())
			{
				// No PM :(
			}
			else
			{
				$pminfo = $pmhandler->insert_pm();
			}
		} 
		else
		{
			//do nothing
		}
	}
?>