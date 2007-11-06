<?php
/**
 * MyBB 1.2
 * Copyright � 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/about/license
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->forum_management, "index.php?".SID."&amp;module=forum/management");

if($mybb->input['action'] == "add" || $mybb->input['action'] == "copy" || $mybb->input['action'] == "permissions" || !$mybb->input['action'])
{
	if($mybb->input['fid'] && ($mybb->input['action'] == "management" || !$mybb->input['action']))
	{
		$sub_tabs['view_forum'] = array(
			'title' => $lang->view_forum,
			'link' => "index.php?".SID."&amp;module=forum/management&amp;fid=".$mybb->input['fid'],
			'description' => $lang->view_forum_desc
		);
	
		$sub_tabs['add_child_forum'] = array(
			'title' => $lang->add_child_forum,
			'link' => "index.php?".SID."&amp;module=forum/management&amp;action=add&amp;pid=".$mybb->input['fid'],
			'description' => $lang->add_child_forum_desc
		);
		
		$sub_tabs['edit_forum_settings'] = array(
			'title' => $lang->edit_forum_settings,
			'link' => "index.php?".SID."&amp;module=forum/management&amp;action=edit&amp;fid=".$mybb->input['fid'],
			'description' => $lang->edit_forum_settings_desc
		);
	
		$sub_tabs['copy_forum'] = array(
			'title' => $lang->copy_forum,
			'link' => "index.php?".SID."&amp;module=forum/management&amp;action=copy&amp;fid=".$mybb->input['fid'],
			'description' => $lang->copy_forum_desc
		);
	}
	else
	{
		$sub_tabs['forum_management'] = array(
			'title' => $lang->forum_management,
			'link' => "index.php?".SID."&amp;module=forum/management",
			'description' => $lang->forum_management_desc
		);
	
		$sub_tabs['add_forum'] = array(
			'title' => $lang->add_forum,
			'link' => "index.php?".SID."&amp;module=forum/management&amp;action=add",
			'description' => $lang->add_forum_desc
		);
	}
}

if($mybb->input['action'] == "add")
{
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}
		
		$pid = intval($mybb->input['pid']);
		$type = $mybb->input['type'];
		
		if($pid == 0 && $type == "f")
		{
			$errors[] = $lang->error_no_parent;
		}
		
		if(!$errors)
		{
			$insert_array = array(
				"name" => $db->escape_string($mybb->input['title']),
				"description" => $db->escape_string($mybb->input['description']),
				"linkto" => $db->escape_string($mybb->input['linkto']),
				"type" => $db->escape_string($type),
				"pid" => $pid,
				"disporder" => intval($mybb->input['disporder']),
				"active" => intval($mybb->input['active']),
				"open" => intval($mybb->input['open']),
				"allowhtml" => intval($mybb->input['allowhtml']),
				"allowmycode" => intval($mybb->input['allowmycode']),
				"allowsmilies" => intval($mybb->input['allowsmilies']),
				"allowimgcode" => intval($mybb->input['allowimgcode']),
				"allowpicons" => intval($mybb->input['allowpicons']),
				"allowtratings" => intval($mybb->input['allowtratings']),
				"usepostcounts" => intval($mybb->input['usepostcounts']),
				"password" => $db->escape_string($mybb->input['password']),
				"showinjump" => intval($mybb->input['showinjump']),
				"modposts" => intval($mybb->input['modposts']),
				"modthreads" => intval($mybb->input['modthreads']),
				"mod_edit_posts" => intval($mybb->input['mod_edit_posts']),
				"modattachments" => intval($mybb->input['modattachments']),
				"style" => intval($mybb->input['fstyle']),
				"overridestyle" => intval($mybb->input['overridestyle']),
				"rulestype" => intval($mybb->input['rulestype']),
				"rulestitle" => $db->escape_string($mybb->input['rulestitle']),
				"rules" => $db->escape_string($mybb->input['rules']),
				"defaultdatecut" => intval($mybb->input['defaultdatecut']),
				"defaultsortby" => $db->escape_string($mybb->input['defaultsortby']),
				"defaultsortorder" => $db->escape_string($mybb->input['defaultsortorder']),
			);
			$db->insert_query("forums", $insert_array);
			
			$fid = $db->insert_id();
			$parentlist = make_parent_list($fid);
			$updatearray = array(
				"parentlist" => $parentlist
			);
			$db->update_query("forums", $updatearray, "fid='$fid'");
			$inherit = $mybb->input['default_permissions'];
			
			foreach($mybb->input['permissions'] as $gid => $permission)
			{
				foreach(array('canview','canpostthreads','canpostreplys','canpostpolls','canpostattachments') as $name)
				{
					if($permission[$name])
					{
						$permissions[$name][$gid] = 1;
					}
					else
					{
						$permissions[$name][$gid] = 0;
					}
				}			
			}
			
			$canview = $permissions['canview'];
			$canpostthreads = $permissions['canpostthreads'];
			$canpostreplies = $permissions['canpostreplies'];
			$canpostpolls = $permissions['canpostpolls'];
			$canpostattachments = $permissions['canpostattachments'];
			$canpostreplies = $permissions['canpostreplys'];
			save_quick_perms($fid);
			$cache->update_forums();
			
			flash_message($lang->success_forum_added, 'success');
			admin_redirect("index.php?".SID."&module=forum/management");
		}
	}
	
	$page->output_header($lang->add_forum);	
	$page->output_nav_tabs($sub_tabs, 'add_forum');
	
	$form = new Form("index.php?".SID."&amp;module=forum/management&amp;action=add", "post");

	if($errors)
	{
		$page->output_inline_error($errors);
		$forum_data = $mybb->input;
	}
	else
	{
		$forum_data['type'] = "f";
		$forum_data['title'] = "";
		$forum_data['description'] = "";
		
		if(!$mybb->input['pid'])
		{
			$forum_data['pid'] = "-1";
		}
		else
		{
			$forum_data['pid'] = intval($mybb->input['pid']);
		}
		$forum_data['disporder'] = "";
		$forum_data['linkto'] = "";
		$forum_data['password'] = "";
		$forum_data['active'] = 1;
		$forum_data['open'] = 1;
		$forum_data['modposts'] = "";
		$forum_data['modthreads'] = "";
		$forum_data['modattachments'] = "";
		$forum_data['mod_edit_posts'] = "";
		$forum_data['overridestyle'] = "";
		$forum_data['style'] = "";
		$forum_data['rulestype'] = "";
		$forum_data['rulestitle'] = "";
		$forum_data['rules'] = "";
		$forum_data['defaultdatecut'] = "";
		$forum_data['defaultsortby'] = "";
		$forum_data['defaultsortorder'] = "";
		$forum_data['allowhtml'] = "";
		$forum_data['allowmycode'] = 1;
		$forum_data['allowsmilies'] = 1;
		$forum_data['allowimgcode'] = 1;
		$forum_data['allowpicons'] = 1;
		$forum_data['allowtratings'] = 1;
		$forum_data['showinjump'] = 1;
		$forum_data['usepostcounts'] = 1;
	}
	
	$types = array(
		'f' => $lang->forum,
		'c' => $lang->category
	);
	
	$create_a_options_f = array(
		'id' => 'type'
	);
	
	$create_a_options_c = array(
		'id' => 'type'
	);
	
	if($forum_data['type'] == "f")
	{
		$create_a_options_f['checked'] = true;
	}
	else
	{
		$create_a_options_c['checked'] = true;
	}

	$form_container = new FormContainer($lang->add_forum);
	$form_container->output_row($lang->create_a, $lang->create_a_desc, $form->generate_radio_button('type', 'f', $lang->forum, $create_a_options_f)."<br />\n".$form->generate_radio_button('type', 'c', $lang->category, $create_a_options_c), 'type');
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $forum_data['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->description, "", $form->generate_text_area('description', $forum_data['description'], array('id' => 'description')), 'description');
	$form_container->output_row($lang->parent_forum." <em>*</em>", $lang->parent_forum_desc, $form->generate_forum_select('pid', $forum_data['pid'], array('id' => 'pid', 'main_option' => $lang->none)), 'pid');
	$form_container->output_row($lang->display_order, "", $form->generate_text_box('disporder', $forum_data['disporder'], array('id' => 'disporder')), 'disporder');
	$form_container->end();
	
	$form_container = new FormContainer($lang->additional_forum_options);
	$form_container->output_row($lang->forum_link, $lang->forum_link_desc, $form->generate_text_box('linkto', $forum_data['linkto'], array('id' => 'linkto')), 'linkto');
	$form_container->output_row($lang->forum_password, $lang->forum_password_desc, $form->generate_text_box('password', $forum_data['password'], array('id' => 'password')), 'password');
	
	$access_options = array(
		$form->generate_check_box('active', 1, $lang->forum_is_active."<br />\n<small>{$lang->forum_is_active_desc}</small>", array('checked' => $forum_data['active'], 'id' => 'active')),
		$form->generate_check_box('open', 1, $lang->forum_is_open."<br />\n<small>{$lang->forum_is_open_desc}</small>", array('checked' => $forum_data['open'], 'id' => 'open'))
	);
		
	
	$form_container->output_row($lang->access_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $access_options)."</div>");
	
	$moderator_options = array(
		$form->generate_check_box('modposts', 1, $lang->mod_new_posts, array('checked' => $forum_data['modposts'], 'id' => 'modposts')),
		$form->generate_check_box('modthreads', 1, $lang->mod_new_threads, array('checked' => $forum_data['modthreads'], 'id' => 'modthreads')),
		$form->generate_check_box('modattachments', 1, $lang->mod_new_attachments, array('checked' => $forum_data['modattachments'], 'id' => 'modattachments')),
		$form->generate_check_box('mod_edit_posts', 1, $lang->mod_after_edit, array('checked' => $forum_data['mod_edit_posts'], 'id' => 'mod_edit_posts'))
	);
	
	$form_container->output_row($lang->moderation_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $moderator_options)."</div>");
	
	$styles = array(
		'0' => $lang->use_default
	);
	
	$query = $db->simple_select("themes", "tid,name", "name!='((master))' AND name!='((master-backup))'", array('order_by' => 'name'));
	while($style = $db->fetch_array($query))
	{
		$styles[$style['tid']] = $style['name'];
	}
	
	$style_options = array(
		$form->generate_check_box('overridestyle', 1, $lang->override_user_style, array('checked' => $forum_data['overridestyle'], 'id' => 'overridestyle')),
		$lang->forum_specific_style."<br />\n".$form->generate_select_box('style', $styles, $forum_data['style'], array('id' => 'style'))
	);
	
	$form_container->output_row($lang->style_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $style_options)."</div>");
	
	$display_methods = array(
		'0' => $lang->dont_display_rules,
		'1' => $lang->display_rules_inline,
		'2' => $lang->display_rules_link
	);
	
	$forum_rules = array(
		$lang->display_method."<br />\n".$form->generate_select_box('rulestype', $display_methods, $forum_data['rulestype'], array('checked' => $forum_data['rulestype'], 'id' => 'rulestype')),
		$lang->title."<br />\n".$form->generate_text_box('rulestitle', $forum_data['rulestitle'], array('checked' => $forum_data['rulestitle'], 'id' => 'rulestitle')),
		$lang->rules."<br />\n".$form->generate_text_area('rules', $forum_data['rules'], array('checked' => $forum_data['rules'], 'id' => 'rules'))
	);
	
	$form_container->output_row($lang->forum_rules, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $forum_rules)."</div>");
	
	$default_date_cut = array(
		0 => $lang->board_default,
		1 => $lang->datelimit_1day,
		5 => $lang->datelimit_5days,
		10 => $lang->datelimit_10days,
		20 => $lang->datelimit_20days,
		50 => $lang->datelimit_50days,
		75 => $lang->datelimit_75days,
		100 => $lang->datelimit_100days,
		365 => $lang->datelimit_lastyear,
		9999 => $lang->datelimit_beginning,
	);
	
	$default_sort_by = array(
		"" => $lang->board_default,
		"subject" => $lang->sort_by_subject,
		"lastpost" => $lang->sort_by_lastpost,
		"starter" => $lang->sort_by_starter,
		"started" => $lang->sort_by_started,
		"rating" => $lang->sort_by_rating,
		"replies" => $lang->sort_by_replies,
		"views" => $lang->sort_by_views,
	);
	
	$default_sort_order = array(
		"" => $lang->board_default,
		"asc" => $lang->sort_order_asc,
		"desc" => $lang->sort_order_desc,
	);
	
	$view_options = array(
		$lang->default_date_cut."<br />\n".$form->generate_select_box('defaultdatecut', $default_date_cut, $forum_data['defaultdatecut'], array('checked' => $forum_data['defaultdatecut'], 'id' => 'defaultdatecut')),
		$lang->default_sort_by."<br />\n".$form->generate_select_box('defaultsortby', $default_sort_by, $forum_data['defaultsortby'], array('checked' => $forum_data['defaultsortby'], 'id' => 'defaultsortby')),
		$lang->default_sort_order."<br />\n".$form->generate_select_box('defaultsortorder', $default_sort_order, $forum_data['defaultsortorder'], array('checked' => $forum_data['defaultsortorder'], 'id' => 'defaultsortorder')),
	);
	
	$form_container->output_row($lang->default_view_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $view_options)."</div>");
	
	$misc_options = array(
		$form->generate_check_box('allowhtml', 1, $lang->allow_html, array('checked' => $forum_data['allowhtml'], 'id' => 'allowhtml')),
		$form->generate_check_box('allowmycode', 1, $lang->allow_mycode, array('checked' => $forum_data['allowmycode'], 'id' => 'allowmycode')),
		$form->generate_check_box('allowsmilies', 1, $lang->allow_smilies, array('checked' => $forum_data['allowsmilies'], 'id' => 'allowsmilies')),
		$form->generate_check_box('allowimgcode', 1, $lang->allow_img_code, array('checked' => $forum_data['allowimgcode'], 'id' => 'allowimgcode')),
		$form->generate_check_box('allowpicons', 1, $lang->allow_post_icons, array('checked' => $forum_data['allowpicons'], 'id' => 'allowpicons')),
		$form->generate_check_box('allowtratings', 1, $lang->allow_thread_ratings, array('checked' => $forum_data['allowtratings'], 'id' => 'allowtratings')),
		$form->generate_check_box('showinjump', 1, $lang->show_forum_jump, array('checked' => $forum_data['showinjump'], 'id' => 'showinjump')),
		$form->generate_check_box('usepostcounts', 1, $lang->use_postcounts, array('checked' => $forum_data['usepostscounts'], 'id' => 'usepostcounts'))
	);
	
	$form_container->output_row($lang->misc_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $misc_options)."</div>");
	$form_container->end();

	$query = $db->simple_select("usergroups", "*", "", array("order_dir" => "name"));
	while($usergroup = $db->fetch_array($query))
	{
		$usergroups[$usergroup['gid']] = $usergroup;
	}
	
	$field_list = array('canview', 'canpostthreads', 'canpostreplys', 'canpostpolls', 'canpostattachments');
	
	$form_container = new FormContainer($lang->forum_permissions);
	$form_container->output_row_header($lang->permissions_group);
	$form_container->output_row_header($lang->permissions_canview, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canpostthreads, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canpostreplys, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canpostpolls, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canuploadattachments, array("class" => "align_center", "width" => "11%"));
	$form_container->output_row_header($lang->permissions_all, array("class" => "align_center", "width" => "10%"));
	foreach($usergroups as $usergroup)
	{
		$perms = $usergroup;
		$default_checked = true;
		
		$perm_check = "";
		$all_checked = true;
		foreach($field_list as $forum_permission)
		{
			if($usergroup[$forum_permission] == 1)
			{
				$value = "true";
			}
			else
			{
				$value = "false";
			}
			
			if($mybb->input['permissions'][$usergroup['gid']][$forum_permission])
			{
				$value = $mybb->input['permissions'][$usergroup['gid']][$forum_permission];
			}
			
			if(isset($mybb->input['permissions']))
			{
				if($mybb->input['permissions'][$usergroup['gid']]['all'])
				{
					$all_checked = false;
				}
				
				if($mybb->input['permissions'][$usergroup['gid']][$forum_permission])
				{
					$perms_checked[$forum_permission] = 1;
				}
				else
				{
					$perms_checked[$forum_permission] = 0;
				}
			}
			else
			{
				if($perms[$forum_permission] != 1)
				{
					$all_checked = false;
				}
				if($perms[$forum_permission] == 1)
				{
					$perms_checked[$forum_permission] = 1;
				}
				else
				{
					$perms_checked[$forum_permission] = 0;
				}
			}
			$all_check .= "\$('permissions_{$usergroup['gid']}_{$forum_permission}').checked = \$('permissions_{$usergroup['gid']}_all').checked;\n";
			$perm_check .= "\$('permissions_{$usergroup['gid']}_{$forum_permission}').checked = $value;\n";
		}
		$default_click = "if(this.checked == true) { $perm_check }";
		$reset_default = "\$('default_permissions_{$usergroup['gid']}').checked = false; if(this.checked == false) { \$('permissions_{$usergroup['gid']}_all').checked = false; }\n";
		$usergroup['title'] = htmlspecialchars_uni($usergroup['title']);
		$form_container->output_cell("<strong>{$usergroup['title']}</strong><br /><small style=\"vertical-align: middle;\">".$form->generate_check_box("default_permissions[{$usergroup['gid']}];", 1, "", array("id" => "default_permissions_{$usergroup['gid']}", "checked" => $default_checked, "onclick" => $default_click))." <label for=\"default_permissions_{$usergroup['gid']}\">{$lang->permissions_use_group_default}</label></small>");
		foreach($field_list as $forum_permission)
		{
			$form_container->output_cell($form->generate_check_box("permissions[{$usergroup['gid']}][{$forum_permission}]", 1, "", array("id" => "permissions_{$usergroup['gid']}_{$forum_permission}", "checked" => $perms_checked[$forum_permission], "onclick" => $reset_default)), array('class' => 'align_center'));
		}
		$form_container->output_cell($form->generate_check_box("permissions[{$usergroup['gid']}][all]", 1, "", array("id" => "permissions_{$usergroup['gid']}_all", "checked" => $all_checked, "onclick" => $all_check)), array('class' => 'align_center'));
		$form_container->construct_row();
	}
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save_forum);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();	
}

if($mybb->input['action'] == "edit")
{
	if(!$mybb->input['fid'])
	{
		flash_message($lang->error_invalid_fid, 'error');
		admin_redirect("index.php?".SID."&module=forum/management");
	}
	
	$query = $db->simple_select("forums", "COUNT(fid) AS count", "fid='{$mybb->input['fid']}'");
	$exists = $db->fetch_field($query, "count");
	if(!$exists)
	{
		flash_message($lang->error_invalid_fid, 'error');
		admin_redirect("index.php?".SID."&module=forum/management");
	}
	
	$fid = intval($mybb->input['fid']);
	
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}
		
		$pid = intval($mybb->input['pid']);
		
		if($pid == $mybb->input['fid'])
		{
			$errors[] = $lang->error_forum_parent_itself;
		}
		else
		{
			$query = $db->simple_select("forums", "*", "pid='{$mybb->input['fid']}'");
			while($child = $db->fetch_array($query))
			{
				if($child['fid'] == $pid)
				{
					$errors[] = $lang->error_forum_parent_child;
					break;
				}
			}
		}
		
		$type = $mybb->input['type'];
		
		if($pid == 0 && $type == "f")
		{
			$errors[] = $lang->error_no_parent;
		}		
		
		if(!$errors)
		{
			$update_array = array(
				"name" => $db->escape_string($mybb->input['title']),
				"description" => $db->escape_string($mybb->input['description']),
				"linkto" => $db->escape_string($mybb->input['linkto']),
				"type" => $db->escape_string($type),
				"pid" => $pid,
				"parentlist" => make_parent_list($fid),
				"disporder" => intval($mybb->input['disporder']),
				"active" => intval($mybb->input['active']),
				"open" => intval($mybb->input['open']),
				"allowhtml" => intval($mybb->input['allowhtml']),
				"allowmycode" => intval($mybb->input['allowmycode']),
				"allowsmilies" => intval($mybb->input['allowsmilies']),
				"allowimgcode" => intval($mybb->input['allowimgcode']),
				"allowpicons" => intval($mybb->input['allowpicons']),
				"allowtratings" => intval($mybb->input['allowtratings']),
				"usepostcounts" => intval($mybb->input['usepostcounts']),
				"password" => $db->escape_string($mybb->input['password']),
				"showinjump" => intval($mybb->input['showinjump']),
				"modposts" => intval($mybb->input['modposts']),
				"modthreads" => intval($mybb->input['modthreads']),
				"mod_edit_posts" => intval($mybb->input['mod_edit_posts']),
				"modattachments" => intval($mybb->input['modattachments']),
				"style" => intval($mybb->input['fstyle']),
				"overridestyle" => intval($mybb->input['overridestyle']),
				"rulestype" => intval($mybb->input['rulestype']),
				"rulestitle" => $db->escape_string($mybb->input['rulestitle']),
				"rules" => $db->escape_string($mybb->input['rules']),
				"defaultdatecut" => intval($mybb->input['defaultdatecut']),
				"defaultsortby" => $db->escape_string($mybb->input['defaultsortby']),
				"defaultsortorder" => $db->escape_string($mybb->input['defaultsortorder']),
			);
			$db->update_query("forums", $update_array, "fid='{$fid}'");
			
			$inherit = $mybb->input['default_permissions'];
			
			foreach($mybb->input['permissions'] as $gid => $permission)
			{
				foreach(array('canview','canpostthreads','canpostreplys','canpostpolls','canpostattachments') as $name)
				{
					if($permission[$name])
					{
						$permissions[$name][$gid] = 1;
					}
					else
					{
						$permissions[$name][$gid] = 0;
					}
				}			
			}
			
			$canview = $permissions['canview'];
			$canpostthreads = $permissions['canpostthreads'];
			$canpostreplies = $permissions['canpostreplies'];
			$canpostpolls = $permissions['canpostpolls'];
			$canpostattachments = $permissions['canpostattachments'];
			$canpostreplies = $permissions['canpostreplys'];
			save_quick_perms($fid);
			$cache->update_forums();
			
			flash_message($lang->success_forum_updated, 'success');
			admin_redirect("index.php?".SID."&module=forum/management");
		}
	}
	
	$page->output_header($lang->edit_forum);
	
	$form = new Form("index.php?".SID."&amp;module=forum/management&amp;action=edit", "post");
	echo $form->generate_hidden_field("fid", $fid);

	if($errors)
	{
		$page->output_inline_error($errors);
		$forum_data = $mybb->input;
	}
	else
	{
		$query = $db->simple_select("forums", "*", "fid='{$fid}'");
		$forum_data = $db->fetch_array($query);
		$forum_data['title'] = $forum_data['name'];
	}
	
	$query = $db->simple_select("usergroups", "*", "", array("order_dir" => "name"));
	while($usergroup = $db->fetch_array($query))
	{
		$usergroups[$usergroup['gid']] = $usergroup;
	}
	
	$query = $db->simple_select("forumpermissions", "*", "fid='{$fid}'");
	while($existing = $db->fetch_array($query))
	{
		$existing_permissions[$existing['gid']] = $existing;
	}
	
	$types = array(
		'f' => $lang->forum,
		'c' => $lang->category
	);
	
	$create_a_options_f = array(
		'id' => 'type'
	);
	
	$create_a_options_c = array(
		'id' => 'type'
	);
	
	if($forum_data['type'] == "f")
	{
		$create_a_options_f['checked'] = true;
	}
	else
	{
		$create_a_options_c['checked'] = true;
	}

	$form_container = new FormContainer($lang->edit_forum);
	$form_container->output_row($lang->create_a, $lang->create_a_desc, $form->generate_radio_button('type', 'f', $lang->forum, $create_a_options_f)."<br />\n".$form->generate_radio_button('type', 'c', $lang->category, $create_a_options_c), 'type');
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $forum_data['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->description, "", $form->generate_text_area('description', $forum_data['description'], array('id' => 'description')), 'description');
	$form_container->output_row($lang->parent_forum." <em>*</em>", $lang->parent_forum_desc, $form->generate_forum_select('pid', $forum_data['pid'], array('id' => 'pid', 'main_option' => $lang->none)), 'pid');
	$form_container->output_row($lang->display_order, "", $form->generate_text_box('disporder', $forum_data['disporder'], array('id' => 'disporder')), 'disporder');
	$form_container->end();
	
	$form_container = new FormContainer($lang->additional_forum_options);
	$form_container->output_row($lang->forum_link, $lang->forum_link_desc, $form->generate_text_box('linkto', $forum_data['linkto'], array('id' => 'linkto')), 'linkto');
	$form_container->output_row($lang->forum_password, $lang->forum_password_desc, $form->generate_text_box('password', $forum_data['password'], array('id' => 'password')), 'password');
	
	$access_options = array(
		$form->generate_check_box('active', 1, $lang->forum_is_active."<br />\n<small>{$lang->forum_is_active_desc}</small>", array('checked' => $forum_data['active'], 'id' => 'active')),
		$form->generate_check_box('open', 1, $lang->forum_is_open."<br />\n<small>{$lang->forum_is_open_desc}</small>", array('checked' => $forum_data['open'], 'id' => 'open'))
	);
		
	$form_container->output_row($lang->access_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $access_options)."</div>");
	
	$moderator_options = array(
		$form->generate_check_box('modposts', 1, $lang->mod_new_posts, array('checked' => $forum_data['modposts'], 'id' => 'modposts')),
		$form->generate_check_box('modthreads', 1, $lang->mod_new_threads, array('checked' => $forum_data['modthreads'], 'id' => 'modthreads')),
		$form->generate_check_box('modattachments', 1, $lang->mod_new_attachments, array('checked' => $forum_data['modattachments'], 'id' => 'modattachments')),
		$form->generate_check_box('mod_edit_posts',1, $lang->mod_after_edit, array('checked' => $forum_data['mod_edit_posts'], 'id' => 'mod_edit_posts'))
	);
	
	$form_container->output_row($lang->moderation_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $moderator_options)."</div>");
	
	$styles = array(
		'0' => $lang->use_default
	);
	
	$query = $db->simple_select("themes", "tid,name", "name!='((master))' AND name!='((master-backup))'", array('order_by' => 'name'));
	while($style = $db->fetch_array($query))
	{
		$styles[$style['tid']] = $style['name'];
	}
	
	$style_options = array(
		$form->generate_check_box('overridestyle', 1, $lang->override_user_style, array('checked' => $forum_data['overridestyle'], 'id' => 'overridestyle')),
		$lang->forum_specific_style."<br />\n".$form->generate_select_box('style', $styles, $forum_data['style'], array('id' => 'style'))
	);
	
	$form_container->output_row($lang->style_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $style_options)."</div>");
	
	$display_methods = array(
		'0' => $lang->dont_display_rules,
		'1' => $lang->display_rules_inline,
		'2' => $lang->display_rules_link
	);
	
	$forum_rules = array(
		$lang->display_method."<br />\n".$form->generate_select_box('rulestype', $display_methods, $forum_data['rulestype'], array('checked' => $forum_data['rulestype'], 'id' => 'rulestype')),
		$lang->title."<br />\n".$form->generate_text_box('rulestitle', $forum_data['rulestitle'], array('checked' => $forum_data['rulestitle'], 'id' => 'rulestitle')),
		$lang->rules."<br />\n".$form->generate_text_area('rules', $forum_data['rules'], array('checked' => $forum_data['rules'], 'id' => 'rules'))
	);
	
	$form_container->output_row($lang->forum_rules, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $forum_rules)."</div>");
	
	$default_date_cut = array(
		0 => $lang->board_default,
		1 => $lang->datelimit_1day,
		5 => $lang->datelimit_5days,
		10 => $lang->datelimit_10days,
		20 => $lang->datelimit_20days,
		50 => $lang->datelimit_50days,
		75 => $lang->datelimit_75days,
		100 => $lang->datelimit_100days,
		365 => $lang->datelimit_lastyear,
		9999 => $lang->datelimit_beginning,
	);
	
	$default_sort_by = array(
		"" => $lang->board_default,
		"subject" => $lang->sort_by_subject,
		"lastpost" => $lang->sort_by_lastpost,
		"starter" => $lang->sort_by_starter,
		"started" => $lang->sort_by_started,
		"rating" => $lang->sort_by_rating,
		"replies" => $lang->sort_by_replies,
		"views" => $lang->sort_by_views,
	);
	
	$default_sort_order = array(
		"" => $lang->board_default,
		"asc" => $lang->sort_order_asc,
		"desc" => $lang->sort_order_desc,
	);
	
	$view_options = array(
		$lang->default_date_cut."<br />\n".$form->generate_select_box('defaultdatecut', $default_date_cut, $forum_data['defaultdatecut'], array('checked' => $forum_data['defaultdatecut'], 'id' => 'defaultdatecut')),
		$lang->default_sort_by."<br />\n".$form->generate_select_box('defaultsortby', $default_sort_by, $forum_data['defaultsortby'], array('checked' => $forum_data['defaultsortby'], 'id' => 'defaultsortby')),
		$lang->default_sort_order."<br />\n".$form->generate_select_box('defaultsortorder', $default_sort_order, $forum_data['defaultsortorder'], array('checked' => $forum_data['defaultsortorder'], 'id' => 'defaultsortorder')),
	);
	
	$form_container->output_row($lang->default_view_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $view_options)."</div>");
	
	$misc_options = array(
		$form->generate_check_box('allowhtml', 1, $lang->allow_html, array('checked' => $forum_data['allowhtml'], 'id' => 'allowhtml')),
		$form->generate_check_box('allowmycode', 1, $lang->allow_mycode, array('checked' => $forum_data['allowmycode'], 'id' => 'allowmycode')),
		$form->generate_check_box('allowsmilies', 1, $lang->allow_smilies, array('checked' => $forum_data['allowsmilies'], 'id' => 'allowsmilies')),
		$form->generate_check_box('allowimgcode', 1, $lang->allow_img_code, array('checked' => $forum_data['allowimgcode'], 'id' => 'allowimgcode')),
		$form->generate_check_box('allowpicons', 1, $lang->allow_post_icons, array('checked' => $forum_data['allowpicons'], 'id' => 'allowpicons')),
		$form->generate_check_box('allowtratings', 1, $lang->allow_thread_ratings, array('checked' => $forum_data['allowtratings'], 'id' => 'allowtratings')),
		$form->generate_check_box('showinjump', 1, $lang->show_forum_jump, array('checked' => $forum_data['showinjump'], 'id' => 'showinjump')),
		$form->generate_check_box('usepostcounts', 1, $lang->use_postcounts, array('checked' => $forum_data['usepostscounts'], 'id' => 'usepostcounts'))
	);
	
	$form_container->output_row($lang->misc_options, "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $misc_options)."</div>");
	$form_container->end();
	
	$field_list = array('canview','canpostthreads','canpostreplys','canpostpolls','canpostattachments');
				
	$form_container = new FormContainer(sprintf($lang->forum_permissions_in, $forum_data['name']));
	$form_container->output_row_header($lang->permissions_group);
	$form_container->output_row_header($lang->permissions_canview, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canpostthreads, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canpostreplys, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canpostpolls, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->permissions_canuploadattachments, array("class" => "align_center", "width" => "11%"));
	$form_container->output_row_header($lang->permissions_all, array("class" => "align_center", "width" => "10%"));
	$form_container->output_row_header($lang->controls, array("class" => "align_center", 'style' => 'width: 150px'));
	foreach($usergroups as $usergroup)
	{
		if(isset($mybb->input['default_permissions']))
		{
			if($mybb->input['default_permissions'][$usergroup['gid']])
			{
				$perms = $existing_permissions[$usergroup['gid']];
				$default_checked = false;
			}
			else
			{
				$perms = $usergroup;
				$default_checked = true;
			}
		}
		else
		{
			if($existing_permissions[$usergroup['gid']])
			{
				$perms = $existing_permissions[$usergroup['gid']];
				$default_checked = false;
			}
			else
			{
				$perms = $usergroup;
				$default_checked = true;
			}
		}
		$perm_check = "";
		$all_checked = true;
		foreach($field_list as $forum_permission)
		{
			if($usergroup[$forum_permission] == 1)
			{
				$value = "true";
			}
			else
			{
				$value = "false";
			}
			
			if($mybb->input['permissions'][$usergroup['gid']][$forum_permission])
			{
				$value = $mybb->input['permissions'][$usergroup['gid']][$forum_permission];
			}
			
			if(isset($mybb->input['permissions']))
			{
				if($mybb->input['permissions'][$usergroup['gid']]['all'])
				{
					$all_checked = false;
				}
				
				if($mybb->input['permissions'][$usergroup['gid']][$forum_permission])
				{
					$perms_checked[$forum_permission] = 1;
				}
				else
				{
					$perms_checked[$forum_permission] = 0;
				}
			}
			else
			{
				if($perms[$forum_permission] != 1)
				{
					$all_checked = false;
				}
				if($perms[$forum_permission] == 1)
				{
					$perms_checked[$forum_permission] = 1;
				}
				else
				{
					$perms_checked[$forum_permission] = 0;
				}
			}
			$all_check .= "\$('permissions_{$usergroup['gid']}_{$forum_permission}').checked = \$('permissions_{$usergroup['gid']}_all').checked;\n";
			$perm_check .= "\$('permissions_{$usergroup['gid']}_{$forum_permission}').checked = $value;\n";
		}
		$default_click = "if(this.checked == true) { $perm_check }";
		$reset_default = "\$('default_permissions_{$usergroup['gid']}').checked = false; if(this.checked == false) { \$('permissions_{$usergroup['gid']}_all').checked = false; }\n";
		$usergroup['title'] = htmlspecialchars_uni($usergroup['title']);
		$form_container->output_cell("<strong>{$usergroup['title']}</strong><br /><small style=\"vertical-align: middle;\">".$form->generate_check_box("default_permissions[{$usergroup['gid']}];", 1, "", array("id" => "default_permissions_{$usergroup['gid']}", "checked" => $default_checked, "onclick" => $default_click))." <label for=\"default_permissions_{$usergroup['gid']}\">{$lang->permissions_use_group_default}</label></small>");
		foreach($field_list as $forum_permission)
		{
			$form_container->output_cell($form->generate_check_box("permissions[{$usergroup['gid']}][{$forum_permission}]", 1, "", array("id" => "permissions_{$usergroup['gid']}_{$forum_permission}", "checked" => $perms_checked[$forum_permission], "onclick" => $reset_default)), array('class' => 'align_center'));
		}
		$form_container->output_cell($form->generate_check_box("permissions[{$usergroup['gid']}][all]", 1, "", array("id" => "permissions_{$usergroup['gid']}_all", "checked" => $all_checked, "onclick" => $all_check)), array('class' => 'align_center'));
		
		if(!$default_checked)
		{
			$form_container->output_cell("<a href=\"index.php?".SID."&amp;action=permissions&amp;gid={$group['gid']}&amp;fid={$fid}\">{$lang->edit_permissions}</a>", array("class" => "align_center"));
		}
		else
		{
			$form_container->output_cell("<a href=\"index.php?".SID."&amp;action=permissions&amp;gid={$group['gid']}&amp;fid={$fid}\">{$lang->set_permissions}</a>", array("class" => "align_center"));
		}
		$form_container->construct_row();
	}
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save_forum);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}

if($mybb->input['action'] == "deletemod")
{
	$query = $db->simple_select("moderators", "*", "uid='{$mybb->input['uid']}' AND fid='{$mybb->input['fid']}'");
	$mod = $db->fetch_array($query);
	
	// Does the forum not exist?
	if(!$mod['mid'])
	{
		flash_message($lang->error_invalid_moderator, 'error');
		admin_redirect("index.php?".SID."&module=forum/management&fid=".$mybb->input['fid']);
	}
	
	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?".SID."&module=forum/management&fid=".$mybb->input['fid']);
	}
	
	if($mybb->request_method == "post")
	{
		$mid = $mod['mid'];
		$query = $db->query("
			SELECT m.*, u.usergroup
			FROM ".TABLE_PREFIX."moderators m 
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=m.uid)
			WHERE m.mid='{$mid}'
		");
		$mod = $db->fetch_array($query);
		
		$db->delete_query("moderators", "mid='{$mid}'");
		$query = $db->simple_select("moderators", "*", "uid='{$mod['uid']}'");
		if($db->fetch_array($query))
		{
			$updatequery = array(
				"usergroup" => "2"
			);
			$db->update_query("users", $updatequery, "uid='{$mod['uid']}' AND usergroup != '4' AND usergroup != '3'");
		}
		$cache->update_moderators();
		flash_message($lang->success_moderator_deleted, 'success');
		admin_redirect("index.php?".SID."&module=forum/management&fid=".$mybb->input['fid']);
	}
	else
	{
		$page->output_confirm_action("index.php?".SID."&amp;module=forum/management&amp;action=deletemod&amp;fid={$mod['fid']}&amp;uid={$mod['uid']}", $lang->confirm_moderator_deletion);
	}
}

if($mybb->input['action'] == "delete")
{
	$query = $db->simple_select("forums", "*", "fid='{$mybb->input['fid']}'");
	$forum = $db->fetch_array($query);
	
	// Does the forum not exist?
	if(!$forum['fid'])
	{
		flash_message($lang->error_invalid_forum, 'error');
		admin_redirect("index.php?".SID."&module=forum/management");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?".SID."&module=forum/management");
	}

	if($mybb->request_method == "post")
	{
		$fid = $mybb->input['fid'];
		// Delete the forum
		$db->delete_query("forums", "fid='$fid'");
		switch($db->type)
		{
			case "sqlite3":
			case "sqlite2":
				$query = $db->simple_select("forums", "*", "','|| parentlist|| ',' LIKE '%,$fid,%'");
				break;
			default:
				$query = $db->simple_select("forums", "*", "CONCAT(',', parentlist, ',') LIKE '%,$fid,%'");
		}		
		while($forum = $db->fetch_array($query))
		{
			$fids[$forum['fid']] = $fid;
			$delquery .= " OR fid='{$forum['fid']}'";
		}

		/**
		 * This slab of code pulls out the moderators for this forum,
		 * checks if they moderate any other forums, and if they don't
		 * it moves them back to the registered usergroup
		 */

		$query = $db->simple_select("moderators", "*", "fid='$fid'");
		while($mod = $db->fetch_array($query))
		{
			$moderators[$mod['uid']] = $mod['uid'];
		}
		
		if(is_array($moderators))
		{
			$mod_list = implode(",", $moderators);
			$query = $db->simple_select("moderators", "*", "fid != '$fid' AND uid IN ($mod_list)");
			while($mod = $db->fetch_array($query))
			{
				unset($moderators[$mod['uid']]);
			}
		}
		
		if(is_array($moderators))
		{
			$mod_list = implode(",", $moderators);
			if($mod_list)
			{
				$updatequery = array(
					"usergroup" => "2"
				);
				$db->update_query("users", $updatequery, "uid IN ($mod_list) AND usergroup='6'");
			}
		}
		
		switch($db->type)
		{
			case "sqlite3":
			case "sqlite2":
				$db->delete_query("forums", "','||parentlist||',' LIKE '%,$fid,%'");
				break;
			default:
				$db->delete_query("forums", "CONCAT(',',parentlist,',') LIKE '%,$fid,%'");
		}
		
		$db->delete_query("threads", "fid='{$fid}' {$delquery}");
		$db->delete_query("posts", "fid='{$fid}' {$delquery}");
		$db->delete_query("moderators", "fid='{$fid}' {$delquery}");

		$cache->update_forums();
		$cache->update_moderators();
		$cache->update_forumpermissions();
		
		// Log admin action
		log_admin_action($forum['name']);

		flash_message($lang->success_forum_deleted, 'success');
		admin_redirect("index.php?".SID."&module=forum/management");
	}
	else
	{
		$page->output_confirm_action("index.php?".SID."&amp;module=forum/management&amp;action=delete&amp;fid={$forum['fid']}", $lang->confirm_forum_deletion);
	}
}

if(!$mybb->input['action'])
{
	if($mybb->request_method == "post")
	{
		if($mybb->input['permissions'])
		{
			$inherit = $mybb->input['default_permissions'];
			
			foreach($mybb->input['permissions'] as $gid => $permission)
			{
				foreach(array('canview','canpostthreads','canpostreplys','canpostpolls','canpostattachments') as $name)
				{
					if($permission[$name])
					{
						$permissions[$name][$gid] = 1;
					}
					else
					{
						$permissions[$name][$gid] = 0;
					}
				}			
			}
			
			$canview = $permissions['canview'];
			$canpostthreads = $permissions['canpostthreads'];
			$canpostreplies = $permissions['canpostreplies'];
			$canpostpolls = $permissions['canpostpolls'];
			$canpostattachments = $permissions['canpostattachments'];
			$canpostreplies = $permissions['canpostreplys'];
			save_quick_perms($mybb->input['fid']);
			
			flash_message($lang->success_forum_permissions_updated, 'success');
			admin_redirect("index.php?".SID."&module=forum/management&fid=".$mybb->input['fid']);
		}
		else
		{
			if(!empty($mybb->input['disporder']))
			{
				foreach($mybb->input['disporder'] as $fid => $order)
				{
					$db->update_query("forums", array('disporder' => intval($order)), "fid='".intval($fid)."'");
				}
						
				$cache->update_forums();
			
				flash_message($lang->success_forum_disporder_updated, 'success');
				admin_redirect("index.php?".SID."&module=forum/management&fid=".$mybb->input['fid']);
			}
		}
	}
	
	$fid = intval($mybb->input['fid']);
	
	$page->add_breadcrumb_item($lang->view_forum, "index.php?".SID."&amp;module=forum/management");
	
	$page->output_header($lang->forum_management);
	
	if($fid)
	{
		$page->output_nav_tabs($sub_tabs, 'view_forum');
	}
	else
	{
		$page->output_nav_tabs($sub_tabs, 'forum_management');
	}

	$form = new Form("index.php?".SID."&amp;module=forum/management", "post", "management");
	echo $form->generate_hidden_field("fid", $mybb->input['fid']);	
	
	if($fid)
	{
		$tabs = array(
			'subforums' => $lang->subforums,
			'permissions' => $lang->forum_permissions,
			'moderators' => $lang->moderators,
		);
		
		$page->output_tab_control($tabs);
	
		echo "<div id=\"tab_subforums\">\n";
		if(!is_array($forum_cache))
		{
			cache_forums();
		}
		$form_container = new FormContainer(sprintf($lang->in_forums, $forum_cache[$fid]['name']));
	}
	else
	{
		$form_container = new FormContainer($lang->manage_forums);
	}
	$form_container->output_row_header($lang->forum);
	$form_container->output_row_header($lang->order, array("class" => "align_center", 'width' => '5%'));
	$form_container->output_row_header($lang->controls, array("class" => "align_center", 'style' => 'width: 200px'));
	
	build_admincp_forums_list($form_container, $fid);
	
	$submit_options = array();
	
	if(count($form_container->container->rows) == 0)
	{
		$form_container->output_cell($lang->no_forums, array('colspan' => 3));
		$form_container->construct_row();
		$submit_options = array('disabled' => true);
	}
	
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->update_forum_orders, $submit_options);
	$buttons[] = $form->generate_reset_button($lang->reset);	
	
	$form->output_submit_wrapper($buttons);
	
	if($fid)
	{
		echo "</div>\n";
		
		$query = $db->simple_select("usergroups", "*", "", array("order_dir" => "name"));
		while($usergroup = $db->fetch_array($query))
		{
			$usergroups[$usergroup['gid']] = $usergroup;
		}
		
		$query = $db->simple_select("forumpermissions", "*", "fid='{$fid}'");
		while($existing = $db->fetch_array($query))
		{
			$existing_permissions[$existing['gid']] = $existing;
		}
		
		$field_list = array('canview','canpostthreads','canpostreplys','canpostpolls','canpostattachments');
				
		echo "<div id=\"tab_permissions\">\n";
		$form_container = new FormContainer(sprintf($lang->forum_permissions_in, $forum_cache[$fid]['name']));
		$form_container->output_row_header($lang->permissions_group);
		$form_container->output_row_header($lang->permissions_canview, array("class" => "align_center", "width" => "10%"));
		$form_container->output_row_header($lang->permissions_canpostthreads, array("class" => "align_center", "width" => "10%"));
		$form_container->output_row_header($lang->permissions_canpostreplys, array("class" => "align_center", "width" => "10%"));
		$form_container->output_row_header($lang->permissions_canpostpolls, array("class" => "align_center", "width" => "10%"));
		$form_container->output_row_header($lang->permissions_canuploadattachments, array("class" => "align_center", "width" => "11%"));
		$form_container->output_row_header($lang->permissions_all, array("class" => "align_center", "width" => "10%"));
		$form_container->output_row_header($lang->controls, array("class" => "align_center", 'style' => 'width: 150px'));
		foreach($usergroups as $usergroup)
		{
			if($existing_permissions[$usergroup['gid']])
			{
				$perms = $existing_permissions[$usergroup['gid']];
				$default_checked = false;
			}
			else
			{
				$perms = $usergroup;
				$default_checked = true;
			}
			$perm_check = "";
			$all_checked = true;
			foreach($field_list as $forum_permission)
			{
				if($usergroup[$forum_permission] == 1)
				{
					$value = "true";
				}
				else
				{
					$value = "false";
				}
				if($perms[$forum_permission] != 1)
				{
					$all_checked = false;
				}
				if($perms[$forum_permission] == 1)
				{
					$perms_checked[$forum_permission] = 1;
				}
				else
				{
					$perms_checked[$forum_permission] = 0;
				}
				$all_check .= "\$('permissions_{$usergroup['gid']}_{$forum_permission}').checked = \$('permissions_{$usergroup['gid']}_all').checked;\n";
				$perm_check .= "\$('permissions_{$usergroup['gid']}_{$forum_permission}').checked = $value;\n";
			}
			$default_click = "if(this.checked == true) { $perm_check }";
			$reset_default = "\$('default_permissions_{$usergroup['gid']}').checked = false; if(this.checked == false) { \$('permissions_{$usergroup['gid']}_all').checked = false; }\n";
			$usergroup['title'] = htmlspecialchars_uni($usergroup['title']);
			$form_container->output_cell("<strong>{$usergroup['title']}</strong><br /><small style=\"vertical-align: middle;\">".$form->generate_check_box("default_permissions[{$usergroup['gid']}];", 1, "", array("id" => "default_permissions_{$usergroup['gid']}", "checked" => $default_checked, "onclick" => $default_click))." <label for=\"default_permissions_{$usergroup['gid']}\">{$lang->permissions_use_group_default}</label></small>");
			foreach($field_list as $forum_permission)
			{
				$form_container->output_cell($form->generate_check_box("permissions[{$usergroup['gid']}][{$forum_permission}]", 1, "", array("id" => "permissions_{$usergroup['gid']}_{$forum_permission}", "checked" => $perms_checked[$forum_permission], "onclick" => $reset_default)), array('class' => 'align_center'));
			}
			$form_container->output_cell($form->generate_check_box("permissions[{$usergroup['gid']}][all]", 1, "", array("id" => "permissions_{$usergroup['gid']}_all", "checked" => $all_checked, "onclick" => $all_check)), array('class' => 'align_center'));
			
			if(!$default_checked)
			{
				$form_container->output_cell("<a href=\"index.php?".SID."&amp;action=permissions&amp;gid={$group['gid']}&amp;fid={$fid}\">{$lang->edit_permissions}</a>", array("class" => "align_center"));
			}
			else
			{
				$form_container->output_cell("<a href=\"index.php?".SID."&amp;action=permissions&amp;gid={$group['gid']}&amp;fid={$fid}\">{$lang->set_permissions}</a>", array("class" => "align_center"));
			}
			$form_container->construct_row();
		}
		$form_container->end();
		
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->update_forum_permissions);
		$buttons[] = $form->generate_reset_button($lang->reset);	
	
		$form->output_submit_wrapper($buttons);
		
		echo "</div>\n";
		echo "<div id=\"tab_moderators\">\n";
		$form_container = new FormContainer(sprintf($lang->moderators_assigned_to, $forum_cache[$fid]['name']));
		$form_container->output_row_header($lang->add_moderator, array('width' => '75%'));
		$form_container->output_row_header($lang->controls, array("class" => "align_center", 'style' => 'width: 200px', 'colspan' => 2));
		$query = $db->query("
			SELECT m.uid, u.username
			FROM ".TABLE_PREFIX."moderators m
			LEFT JOIN ".TABLE_PREFIX."users u ON (m.uid=u.uid)
			WHERE fid='{$fid}'
		");
		while($moderator = $db->fetch_array($query))
		{
			$form_container->output_cell("<a href=\"index.php?".SID."&amp;module=user/users&amp;action=edit&amp;uid={$moderator['uid']}\">{$moderator['username']}</a>");
			$form_container->output_cell("<a href=\"index.php?".SID."&amp;module=forum/management&amp;action=editmod&amp;uid={$moderator['uid']}\">{$lang->edit}</a>", array("class" => "align_center"));
			$form_container->output_cell("<a href=\"index.php?".SID."&amp;module=forum/management&amp;action=deletemod&amp;uid={$moderator['uid']}&amp;fid={$fid}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_moderator_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
			$form_container->construct_row();
		}
		
		if(count($form_container->container->rows) == 0)
		{
			$form_container->output_cell($lang->no_moderators, array('colspan' => 3));
			$form_container->construct_row();
		}
		
		$form_container->end();
		echo "</div>\n";
	}
	
	$form->end();
	
	$page->output_footer();
}

/**
 *
 */
function build_admincp_forums_list(&$form_container, $pid=0, $depth=1)
{
	global $mybb, $lang, $db, $sub_forums;
	static $forums_by_parent;

	if(!is_array($forums_by_parent))
	{
		$forum_cache = cache_forums();

		foreach($forum_cache as $forum)
		{
			$forums_by_parent[$forum['pid']][$val['disporder']][$forum['fid']] = $forum;
		}
	}

	if(!is_array($forums_by_parent[$pid]))
	{
		return;
	}

	foreach($forums_by_parent[$pid] as $children)
	{
		foreach($children as $forum)
		{
			if($forum['active'] == 0)
			{
				$forum['name'] = "<em>".$forum['name']."</em>";
			}
				
			if($forum['type'] == "c" && ($depth == 1 || $depth == 2))
			{
				$form_container->output_cell("<div style=\"padding-left: ".(40*($depth-1))."px;\"><a href=\"index.php?".SID."&amp;module=forum/management&amp;fid={$forum['fid']}\"><strong>{$forum['name']}</strong></a></div>");

				$form_container->output_cell("<input type=\"text\" name=\"disporder[".$forum['fid']."]\" value=\"".$forum['disporder']."\" size=\"2\" />", array("class" => "align_center"));
				
				$popup = new PopupMenu("forum_{$forum['fid']}", $lang->options);
				$popup->add_item($lang->edit_forum, "index.php?".SID."&amp;module=forum/management&amp;action=edit&amp;fid={$forum['fid']}");
				$popup->add_item($lang->subforums, "index.php?".SID."&amp;module=forum/management&amp;fid={$forum['fid']}");
				$popup->add_item($lang->moderators, "index.php?".SID."&amp;module=forum/management&amp;action=moderators&amp;fid={$forum['fid']}");
				$popup->add_item($lang->permissions, "index.php?".SID."&amp;module=forum/management&amp;action=permissions&amp;pid={$forum['fid']}");
				$popup->add_item($lang->add_child_forum, "index.php?".SID."&amp;module=forum/management&amp;action=add&amp;fid={$forum['fid']}");
				$popup->add_item($lang->copy_forum, "index.php?".SID."&amp;module=forum/management&amp;action=copy&amp;fid={$forum['fid']}");
				$popup->add_item($lang->delete_forum, "index.php?".SID."&amp;module=forum/management&amp;action=delete&amp;fid={$forum['fid']}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_forum_deletion}')");
				
				$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
				
				$form_container->construct_row();
				
				// Does this category have any sub forums?
				if($forums_by_parent[$forum['fid']])
				{
					build_admincp_forums_list($form_container, $forum['fid'], $depth+1);
				}
			}
			elseif($forum['type'] == "f" && ($depth == 1 || $depth == 2))
			{
				if($forum['description'])
				{
					if(my_strlen($forum['description']) > 100)
					{
						$forum['description'] = my_substr($forum['description'], 0, 98)."...";
					}
           			$forum['description'] = "<br /><small>".$forum['description']."</small>";
       			}
			
				$sub_forums = '';
				if(isset($forums_by_parent[$forum['fid']]) && $depth == 2)
				{
					build_admincp_forums_list($form_container, $forum['fid'], $depth+1);
				}
				if($sub_forums)
				{
					$sub_forums = "<br /><small>{$lang->sub_forums}: {$sub_forums}</small>";
				}
					
				$form_container->output_cell("<div style=\"padding-left: ".(40*($depth-1))."px;\"><a href=\"index.php?".SID."&amp;module=forum/management&amp;fid={$forum['fid']}\">{$forum['name']}</a>{$forum['description']}{$sub_forums}</div>");
					
				$form_container->output_cell("<input type=\"text\" name=\"disporder[".$forum['fid']."]\" value=\"".$forum['disporder']."\" size=\"2\" />", array("class" => "align_center"));
					
				$popup = new PopupMenu("forum_{$forum['fid']}", $lang->options);
				$popup->add_item($lang->edit_forum, "index.php?".SID."&amp;module=forum/management&amp;action=edit&amp;fid={$forum['fid']}");
				$popup->add_item($lang->subforums, "index.php?".SID."&amp;module=forum/management&amp;fid={$forum['fid']}");
				$popup->add_item($lang->moderators, "index.php?".SID."&amp;module=forum/management&amp;action=moderators&amp;fid={$forum['fid']}");
				$popup->add_item($lang->permissions, "index.php?".SID."&amp;module=forum/management&amp;action=permissions&amp;fid={$forum['fid']}");
				$popup->add_item($lang->add_child_forum, "index.php?".SID."&amp;module=forum/management&amp;action=add&amp;pid={$forum['fid']}");
				$popup->add_item($lang->copy_forum, "index.php?".SID."&amp;module=forum/management&amp;action=copy&amp;fid={$forum['fid']}");
				$popup->add_item($lang->delete_forum, "index.php?".SID."&amp;module=forum/management&amp;action=delete&amp;fid={$forum['fid']}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_forum_deletion}')");
				
				$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
				
				$form_container->construct_row();
				
				if(isset($forums_by_parent[$forum['fid']]) && $depth == 1)
				{
					build_admincp_forums_list($form_container, $forum['fid'], $depth+1);
				}
			}
			else if($depth == 3)
			{
				if($donecount < $mybb->settings['subforumsindex'])
				{
					$sub_forums .= "{$comma} <a href=\"index.php?".SID."&amp;module=forum/management&amp;fid={$forum['fid']}\">{$forum['name']}</a>";
					$comma = ', ';
				}
	
				// Have we reached our max visible subforums? put a nice message and break out of the loop
				++$donecount;
				if($donecount == $mybb->settings['subforumsindex'])
				{
					if(count($children) > $donecount)
					{
						$sub_forums .= $comma.sprintf($lang->more_subforums, (count($children) - $donecount));
						return;
					}
				}
			}
		}
	}
}
?>