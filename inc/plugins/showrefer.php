<?php
if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

// Pre-load templates
global $mybb, $templatelist;

if(THIS_SCRIPT == 'member.php' && $mybb->input['action'] == 'profile')
{
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}

	$templatelist .= 'member_profile_showrefer,member_profile_showrefer_avatar';
}

// Credits: DennisTT for PM function, Rakes for query optimisation and name format, Destroy666 for avatars, JeffChan for the 1.4 version

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
		"version"	=> "1.2",
		"compatibility" => "18",
		"codename" => "leefish_showrefer"	
	);
}
function showrefer_is_installed()
{
	global $db;
	
	$query = $db->simple_select("templates", "`title`", "`title` = 'member_profile_showrefer'");
	$g = $db->fetch_array($query);
	
	if($g) {
		
		return true;
		
	}	
	
	return false;
}


function showrefer_install() 
{
	global $db;
	
	$showrefer_template['member_profile_showrefer'] ='<tr>
	<td class=\"trow1\" valign=\"top\"><strong>{\$lang->referrals} ({$memprofile[\'referrals\']})</strong></td>
	<td class=\"trow1\">{$showrefer_referrals}</td>
</tr>';
	$showrefer_template['member_profile_showrefer_avatar'] ='<img src={$useravatar[\'image\']} {$useravatar[\'width_height\']} style="margin-right:5px;max-width:20px;height:auto;"/>';


	foreach($showrefer_template as $title => $template)
	    {
	    	$showrefer_template = array('title' => $db->escape_string($title), 'template' => $db->escape_string($template), 'sid' => '-1', 'version' => '1800', 'dateline' => TIME_NOW);
	    	$db->insert_query('templates', $showrefer_template);
	    }
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
	$db->delete_query("templates", "`title` = 'member_profile_showrefer_avatar'");
	
	rebuild_settings();
}

function showrefer()
{
	global $mybb, $templates, $db, $theme, $showrefer, $showrefer_referrals, $memprofile, $lang;
	
	$lang->load("showrefer");
	
	if ($memprofile['referrals'] > 0) {
	
	$referrer = (int)$mybb->input['uid'];
	
	$query = $db->simple_select("users", "uid,username,usergroup,displaygroup,avatar,avatardimensions,referrer,referrals" , "referrer = '$referrer'");
		
	$sep = "";
	
		while($referral = $db->fetch_array($query))
		{
		$useravatar = format_avatar($referral['avatar'], $referral['avatardimensions']);
		
		eval("\$useravatar = \"".$templates->get("member_profile_showrefer_avatar")."\";");
		
			$showrefer_referrals .= $sep.$useravatar.build_profile_link(format_name(htmlspecialchars_uni($referral['username']), $referral['usergroup'], $referral['displaygroup']), $referral['uid']); 

			$sep = ", ";

		}	
		
	} else	{
	
		$showrefer_referrals = "{$lang->no_referrals}";
	}
	eval("\$showrefer = \"".$templates->get('member_profile_showrefer')."\";");
	
}
function showrefer_send_pm()
{   
	global $lang, $mybb, $db,$user,$user_info,$plugins;
	
	$lang->load("showrefer");
	
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
		$newblink = '[url='.$mybb->settings['bburl'].'/member.php?action=profile&uid='.$new_uid.']'.$new_user.'[/url]';
		
		$pmsubject = "New member referred by you.";
		$pm_message = "Thanks for referring me. Check out my profile ";
		$pm_message .= $newblink;

		require_once MYBB_ROOT."inc/datahandlers/pm.php";
		$pmhandler = new PMDataHandler();
		
		//PM will always be sent regardless of receivers settings
		$pmhandler->admin_override = true;
		$pm = array(
			"subject" => $pmsubject,
			"message" => $pm_message,
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
				
		if(!$pmhandler->validate_pm()) {
		
			// No PM :(
			
		} else 	{
		
			$pminfo = $pmhandler->insert_pm();
		}
	} 
}