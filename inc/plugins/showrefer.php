<?php
if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

// Credits: DennisTT for PM function, Rakes for query optimisation and name format, JeffChan for the 1.4 version

$plugins->add_hook('member_profile_end', 'showrefer');
$plugins->add_hook("member_do_register_end", "showrefer_send_pm");

function showrefer_info()
{
	return array(
		"name"		=> "Show Referrals in Profile",
		"description"	=> "This plugin displays the user's referrals (who they referred) in their profiles.",
		"website"	=> "http://www.leefish.nl",
		"author"	=> "LeeFish",
		"authorsite"	=> "http://www.leefish.nl",
		"version"	=> "1.1",
		"compatibility" => "18",
		"codename" => "leefish_showrefer"	
	);
}

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

function showrefer_install() 
{
	global $db;

	$showrefer_template = array(
		"title"		=> 'member_profile_showrefer',
		"template"	=> "<tr>
	<td class=\"trow1\" valign = \"top\"><strong>Referrals ({\$memprofile[\'referrals\']})</strong></td>
	<td class=\"trow1\">{\$showrefer_referrals}</td>
</tr>",
		"sid"		=> -1,
		"version"	=> 1800,
		"dateline"	=> TIME_NOW,
	);
	$db->insert_query('templates', $showrefer_template);
}

function showrefer_activate()
{
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#{\$referrals}#', '{\$showrefer}');
}

function showrefer_deactivate()
{
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#{\$showrefer}#', '{\$referrals}', 0);
}

function showrefer_uninstall()
{
    global $db;

	$db->delete_query("templates", "`title` = 'member_profile_showrefer'");
	
	rebuild_settings();
}

function showrefer()
{
	global $mybb, $templates, $db, $theme, $showrefer, $showrefer_referrals, $memprofile, $lang;
	
	$lang->load("showrefer");
	
	if ($memprofile['referrals'] > 0) 
	{
	$referrer = (int)$mybb->input['uid'];
	
	$query = $db->simple_select("users", "uid,username,usergroup,displaygroup,avatar,referrer,referrals" , "referrer = '$referrer'");
		
	$sep = "";
	
		while($referral = $db->fetch_array($query))
		{

			if (!empty($referral['avatar'])){
			
				$avatar = htmlspecialchars_uni($referral['avatar']);
				
			} else {
			
				$avatar = $mybb->settings['bburl'] . '/' . $mybb->settings['useravatar'];
			}
			
		    $useravatar = "<img src='$avatar' width='20px' height='20px' style='margin-right:5px'/>";
			
			$showrefer_referrals .= $sep.$useravatar.build_profile_link(format_name(htmlspecialchars_uni($referral['username']), $referral['usergroup'], $referral['displaygroup']), $referral['uid']); 

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
	
	//Get the user and user info from the registration.
	if($user['referrer'] != "" && isset($user_info)) 
	{

		$referrer = htmlspecialchars($user['referrer']);
		
		//Fetch Referrer uid

		$query = $db->simple_select("users", "uid,username" , "username = '".$db->escape_string($referrer)."'");
		
		$refers = $db->fetch_array($query);
		
		//Set newly registered user information for the pm to referrer	
		$toid = (int)$refers['uid'];
		$new_uid = (int)$user_info['uid'];
		$new_user = htmlspecialchars($user_info['username']);
		
		//Style link as BB Code for PM
		$newblink = "[URL=".$mybb->settings['bburl']."/member.php?action=profile&uid=".$user_info['uid']."]".$user_info['username']."[/URL]";

		require_once MYBB_ROOT."inc/datahandlers/pm.php";
		$pmhandler = new PMDataHandler();
		
		//PM will always be sent regardless of receivers settings
		$pmhandler->admin_override = true;
			$pm = array(
			"subject" => "New member referred by you.",
			"message" => "Thanks for referring me. Check out my profile $newblink ",
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
}
?>