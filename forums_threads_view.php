<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* forums_threads_view
*
* @author Wolfgang Merkens <wmerkens@databay.de>
* @version $Id$
*
* @package ilias
*/
require_once "./include/inc.header.php";
require_once "classes/class.ilObjForum.php";
require_once "./classes/class.ilFileDataForum.php";

$lng->loadLanguageModule("forum");

$forumObj = new ilObjForum($_GET["ref_id"]);
$frm =& $forumObj->Forum;

$file_obj =& new ilFileDataForum($forumObj->getId(),$_GET["pos_pk"]);

$frm->setForumId($forumObj->getId());
$frm->setForumRefId($forumObj->getRefId());

$tpl->addBlockFile("CONTENT", "content", "tpl.forums_threads_view.html");
$tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
$tpl->addBlockFile("BUTTONS", "buttons", "tpl.buttons.html");
$tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
$tpl->addBlockFile("TABS", "tabs", "tpl.tabs.html");
// catch stored message
sendInfo();
// display infopanel if something happened
infoPanel();

if (!$rbacsystem->checkAccess("read,visible", $_GET["ref_id"]))
{
	$ilias->raiseError($lng->txt("permission_denied"),$ilias->error_obj->MESSAGE);
}

// UPLOAD FILE
// DELETE FILE
if(isset($_POST["cmd"]["delete_file"]))
{
	$file_obj->unlinkFiles($_POST["del_file"]);
	sendInfo("Datei gel�scht");
}
// DOWNLOAD FILE
if($_GET["file"])
{
	if(!$path = $file_obj->getAbsolutePath(urldecode($_GET["file"])))
	{
		sendInfo("Error reading file!");
	}
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".$_GET["file"]."\"");
	readfile($path);
}

$tpl->setVariable("TXT_FORUM_ARTICLES", $lng->txt("forums_posts"));

if($_SESSION["viewmode"] == 'flat')
{
	$new_order = "answers";
	$orderField = "frm_posts_tree.date";
}
else
{
	$new_order = "date";
	$orderField = "frm_posts_tree.rgt";
}

// sorting val for posts
/*
if ($_GET["orderby"] == "")
{
	$old_order = "answers";
}
else
{
	$old_order = $_GET["orderby"];
}

if ($old_order == "date")
{
	$new_order = "answers";
	$orderField = "frm_posts_tree.date";
}
else
{
	$new_order = "date";
	$orderField = "frm_posts_tree.rgt";
}
*/
#tpl->setVariable("LINK_SORT", "<b>></b><a href=\"forums_threads_view.php?orderby=".$new_order."&thr_pk=".$_GET["thr_pk"]."&ref_id=".$_GET["ref_id"]."\">".$lng->txt("order_by")." ".$lng->txt($new_order)."</a>");

// get forum- and thread-data
$frm->setWhereCondition("top_frm_fk = ".$frm->getForumId());

if (is_array($topicData = $frm->getOneTopic()))
{
	$frm->setWhereCondition("thr_pk = ".$_GET["thr_pk"]);
	$threadData = $frm->getOneThread();

	$tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("forums_thread")." \"".$threadData["thr_subject"]."\"");

	// Visit-Counter
	$frm->setDbTable("frm_threads");
	$frm->setWhereCondition("thr_pk = ".$_GET["thr_pk"]);
	$frm->updateVisits($_GET["thr_pk"]);

	// ********************************************************************************
	// build location-links
	include_once("classes/class.ilForumLocatorGUI.php");
	$frm_loc =& new ilForumLocatorGUI();
	$frm_loc->setRefId($_GET["ref_id"]);
	$frm_loc->setForum($frm);
	$frm_loc->setThread($_GET["thr_pk"], $threadData["thr_subject"]);
	$frm_loc->display();

	// set tabs
	// display different buttons depending on viewmode
	if (!isset($_SESSION["viewmode"]) or $_SESSION["viewmode"] == "flat")
	{
		$ftabtype = "tabactive";
		$ttabtype = "tabinactive";
	}
	else
	{
		$ftabtype = "tabinactive";
		$ttabtype = "tabactive";
	}

	$tpl->setCurrentBlock("tab");
	$tpl->setVariable("TAB_TYPE", $ttabtype);
	$tpl->setVariable("TAB_LINK", "forums_frameset.php?viewmode=tree&thr_pk=$_GET[thr_pk]&ref_id=$_GET[ref_id]");
	$tpl->setVariable("TAB_TEXT", $lng->txt("order_by")." ".$lng->txt("answers"));
	$tpl->setVariable("TAB_TARGET", "bottom");
	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("tab");
	$tpl->setVariable("TAB_TYPE", $ftabtype);
	$tpl->setVariable("TAB_LINK", "forums_frameset.php?viewmode=flat&thr_pk=$_GET[thr_pk]&ref_id=$_GET[ref_id]");
	$tpl->setVariable("TAB_TEXT", $lng->txt("order_by")." ".$lng->txt("date"));
	$tpl->setVariable("TAB_TARGET", "bottom");
	$tpl->parseCurrentBlock();

	// menu template (contains linkbar, new topic and print thread button)
	$menutpl =& new ilTemplate("tpl.forums_threads_menu.html", true, true);

	if ($rbacsystem->checkAccess("edit_post", $_GET["ref_id"]))
	{
		$menutpl->setCurrentBlock("btn_cell");
		$menutpl->setVariable("BTN_LINK","forums_threads_new.php?ref_id=".$_GET["ref_id"]);
		$menutpl->setVariable("BTN_TARGET","target=\"bottom\"");
		$menutpl->setVariable("BTN_TXT", $lng->txt("forums_new_thread"));
		$menutpl->parseCurrentBlock();
	}
	else
	{
		//$tpl->setVariable("NO_BTN", "<br/><br/>");
	}

	// print thread
	$menutpl->setCurrentBlock("btn_cell");
	$menutpl->setVariable("BTN_LINK","forums_export.php?print_thread=".$_GET["thr_pk"].
		"&thr_top_fk=".$threadData["thr_top_fk"]);
	$menutpl->setVariable("BTN_TARGET","target=\"_new\"");
	$menutpl->setVariable("BTN_TXT", $lng->txt("forums_print_thread"));
	$menutpl->parseCurrentBlock();

	// ********************************************************************************

	// form processing (edit & reply)
	if ($_GET["cmd"] == "ready_showreply" || $_GET["cmd"] == "ready_showedit" || $_GET["cmd"] == "ready_censor")
	{
		$formData = $_POST["formData"];

		if ($_GET["cmd"] != "ready_censor")
		{
			// check form-dates
			$checkEmptyFields = array(
				$lng->txt("message")   => $formData["message"]
			);

			$errors = ilUtil::checkFormEmpty($checkEmptyFields);

			if ($errors != "")
			{
				sendInfo($lng->txt("form_empty_fields")." ".$errors);
			}
			else
			{
				if ($_GET["cmd"] == "ready_showreply")
				{
					// reply: new post
					$newPost = $frm->generatePost($topicData["top_pk"], $_GET["thr_pk"],
												  $_SESSION["AccountId"], $formData["message"], 
												  $_GET["pos_pk"],$_POST["notify"],$threadData["thr_subject"]);
					sendInfo($lng->txt("forums_post_new_entry"));
					if(isset($_FILES["userfile"]))
					{
						$tmp_file_obj =& new ilFileDataForum($forumObj->getId(),$newPost);
						$tmp_file_obj->storeUploadedFile($_FILES["userfile"]);
					}

				}
				else
				{
					// edit: update post
					if ($frm->updatePost($formData["message"], $_GET["pos_pk"],$_POST["notify"]))
					{
						sendInfo($lng->txt("forums_post_modified"));
					}
					if(isset($_FILES["userfile"]))
					{
						$file_obj->storeUploadedFile($_FILES["userfile"]);
					}
				}
			}

		} // if ($_GET["cmd"] != "ready_censor")
		// insert censorship
		elseif ($_POST["confirm"] != "" && $_GET["cmd"] == "ready_censor")
		{
			$frm->postCensorship($formData["cens_message"], $_GET["pos_pk"],1);
		}
		elseif ($_POST["cancel"] != "" && $_GET["cmd"] == "ready_censor")
		{
			$frm->postCensorship($formData["cens_message"], $_GET["pos_pk"]);
		}
	}

	// delete post and its sub-posts
/*
	if ($_GET["cmd"] == "ready_delete" && $_POST["confirm"] != "")
	{
		$dead_thr = $frm->deletePost($_GET["pos_pk"]);

		// if complete thread was deleted ...
		if ($dead_thr == $_GET["thr_pk"])
		{
			sendInfo($lng->txt("forums_post_deleted"),true);
			header("location: forums.php?ref_id=".$_GET["ref_id"]);
			exit();
		}

		sendInfo($lng->txt("forums_post_deleted"));
	}
*/
	// get first post of thread
	$first_node = $frm->getFirstPostNode($_GET["thr_pk"]);

	// get complete tree of thread
	$frm->setOrderField($orderField);
//echo "orderField:$orderField:<br>";
	$subtree_nodes = $frm->getPostTree($first_node);
	$posNum = count($subtree_nodes);

	$pageHits = $frm->getPageHits();

	$z = 0;

	// navigation to browse
	if ($posNum > $pageHits)
	{
		$params = array(
			"ref_id"		=> $_GET["ref_id"],
			"thr_pk"		=> $_GET["thr_pk"],
			"orderby"		=> $_GET["orderby"]
		);

		if (!$_GET["offset"])
		{
			$Start = 0;
		}
		else
		{
			$Start = $_GET["offset"];
		}

		$linkbar = ilUtil::Linkbar(basename($_SERVER["PHP_SELF"]),$posNum,$pageHits,$Start,$params);
//echo ":$linkbar:";
		if ($linkbar != "")
		{
			$menutpl->setCurrentBlock("linkbar");
			$menutpl->setVariable("LINKBAR", $linkbar);
			$menutpl->parseCurrentBlock();
		}
	}

	$menutpl->setCurrentBlock("btn_row");
	$menutpl->parseCurrentBlock();
	$tpl->setVariable("THREAD_MENU", $menutpl->get());


	// assistance val for anchor-links
	$jump = 0;

	// generate post-dates
	foreach ($subtree_nodes as $node)
	{
//echo ":".$frm->convertDate($node["create_date"]).":<br>";
		if ($_GET["pos_pk"] && $_GET["pos_pk"] == $node["pos_pk"])
		{
			$jump ++;
		}

		if ($posNum > $pageHits && $z >= ($Start+$pageHits))
		{
			// if anchor-link was not found ...
			if ($_GET["pos_pk"] && $jump < 1)
			{
				header("location: forums_threads_view.php?thr_pk=".$_GET["thr_pk"]."&ref_id=".
					   $_GET["ref_id"]."&pos_pk=".$_GET["pos_pk"]."&offset=".($Start+$pageHits)."&orderby=".$_GET["orderby"]);
				exit();
			}
			else
			{
				break;
			}
		}

		if (($posNum > $pageHits && $z >= $Start) || $posNum <= $pageHits)
		{
			if ($rbacsystem->checkAccess("edit_post", $_GET["ref_id"]))
			{
				// reply/edit
				if (($_GET["cmd"] == "showreply" || $_GET["cmd"] == "showedit") && $_GET["pos_pk"] == $node["pos_pk"])
				{
					// EDIT ATTACHMENTS
					if (count($file_obj->getFilesOfPost()) && $_GET["cmd"] == "showedit")
					{
						foreach ($file_obj->getFilesOfPost() as $file)
						{
							$tpl->setCurrentBlock("ATTACHMENT_EDIT_ROW");
							$tpl->setVariable("FILENAME",$file["name"]);
							$tpl->setVariable("CHECK_FILE",ilUtil::formCheckbox(0,"del_file[]",$file["name"]));
							$tpl->parseCurrentBlock();
						}

						$tpl->setCurrentBlock("reply_attachment_edit");
						$tpl->setVariable("FILE_DELETE_ACTION","forums_threads_view.php?ref_id=$_GET[ref_id]&cmd=showedit".
										  "&pos_pk=$_GET[pos_pk]&thr_pk=$_GET[thr_pk]");
						$tpl->setVariable("TXT_ATTACHMENTS_EDIT",$lng->txt("forums_attachments_edit"));
						$tpl->setVariable("ATTACHMENT_EDIT_DELETE",$lng->txt("forums_delete_file"));
						$tpl->parseCurrentBlock();
					}

					// ADD ATTACHMENTS
					$tpl->setCurrentBlock("reply_attachment");
					$tpl->setVariable("TXT_ATTACHMENTS_ADD",$lng->txt("forums_attachments_add"));
					#						$tpl->setVariable("UPLOAD_ACTION","forums_threads_view.php?ref_id=$_GET[ref_id]&cmd=showedit".
					#										  "&pos_pk=$_GET[pos_pk]&thr_pk=$_GET[thr_pk]");
					$tpl->setVariable("BUTTON_UPLOAD",$lng->txt("upload"));
					$tpl->parseCurrentBlock();
					$tpl->setCurrentBlock("reply_post");
					$tpl->setVariable("REPLY_ANKER", $_GET["pos_pk"]);

					if ($_GET["cmd"] == "showreply")
					{
						$tpl->setVariable("TXT_FORM_HEADER", $lng->txt("forums_your_reply"));
					}
					else
					{
						$tpl->setVariable("TXT_FORM_HEADER", $lng->txt("forums_edit_post"));
					}

					$tpl->setVariable("TXT_FORM_MESSAGE", $lng->txt("forums_the_post"));

					if ($_GET["cmd"] == "showreply")
					{
						$tpl->setVariable("FORM_MESSAGE", $frm->prepareText($node["message"],1));
					}
					else
					{
						$tpl->setVariable("FORM_MESSAGE", $frm->prepareText($node["message"],2));
					}
					// NOTIFY
					$tpl->setVariable("NOTIFY",$lng->txt("forum_notify_me"));
					$tpl->setVariable("NOTIFY_CHECKED",$node["notify"] ? "checked=\"checked\"" : "");
					$tpl->setVariable("SUBMIT", $lng->txt("submit"));
					$tpl->setVariable("RESET", $lng->txt("reset"));
					$tpl->setVariable("FORMACTION", basename($_SERVER["PHP_SELF"])."?cmd=ready_".$_GET["cmd"]."&ref_id=".
									  $_GET["ref_id"]."&pos_pk=".$_GET["pos_pk"]."&thr_pk=".$_GET["thr_pk"].
									  "&offset=".$Start."&orderby=".$_GET["orderby"]);
					$tpl->parseCurrentBlock("reply_post");

				} // if (($_GET["cmd"] == "showreply" || $_GET["cmd"] == "showedit") && $_GET["pos_pk"] == $node["pos_pk"])
				else
				{
					// button: delete article
					if ($rbacsystem->checkAccess("delete_post", $_GET["ref_id"]))
					{
						// 2. delete-level
						if ($_GET["cmd"] == "delete" && $_GET["pos_pk"] == $node["pos_pk"])
						{
							$tpl->setCurrentBlock("kill_cell");
							$tpl->setVariable("KILL_ANKER", $_GET["pos_pk"]);
							$tpl->setVariable("KILL_SPACER","<hr noshade=\"noshade\" width=\"100%\" size=\"1\" align=\"center\">");
							$tpl->setVariable("TXT_KILL", $lng->txt("forums_info_delete_post"));
//							$tpl->setVariable("DEL_FORMACTION", basename($_SERVER["PHP_SELF"])."?cmd=ready_delete&ref_id=".$_GET["ref_id"]."&pos_pk=".$node["pos_pk"]."&thr_pk=".$_GET["thr_pk"]."&offset=".$Start."&orderby=".$_GET["orderby"]);
							$tpl->setVariable("DEL_FORMACTION", "forums_frameset.php?cmd=ready_delete&ref_id=".
											  $_GET["ref_id"]."&pos_pk=".$node["pos_pk"]."&thr_pk=".$_GET["thr_pk"].
											  "&offset=".$Start."&orderby=".$_GET["orderby"]);
							$tpl->setVariable("CANCEL_BUTTON", $lng->txt("cancel"));
							$tpl->setVariable("CONFIRM_BUTTON", $lng->txt("confirm"));
							$tpl->parseCurrentBlock("kill_cell");
						}
						else
						{
							// 1. delete-level
							if ($_GET["cmd"] != "censor" || $_GET["pos_pk"] != $node["pos_pk"])
							{
								$tpl->setCurrentBlock("del_cell");
								$tpl->setVariable("DEL_BUTTON","<a href=\"forums_threads_view.php?cmd=delete&pos_pk=".
												  $node["pos_pk"]."&ref_id=".$_GET["ref_id"]."&offset=".$Start.
												  "&orderby=".$_GET["orderby"]."&thr_pk=".$_GET["thr_pk"]."#".
												  $node["pos_pk"]."\">".$lng->txt("delete")."</a>");
								$tpl->parseCurrentBlock("del_cell");
							}
						}

						// censorship
						// 2. cens formular
						if ($_GET["cmd"] == "censor" && $_GET["pos_pk"] == $node["pos_pk"])
						{
							$tpl->setCurrentBlock("censorship_cell");
							$tpl->setVariable("CENS_ANKER", $_GET["pos_pk"]);
							$tpl->setVariable("CENS_SPACER","<hr noshade=\"noshade\" width=\"100%\" size=\"1\" align=\"center\">");
							$tpl->setVariable("CENS_FORMACTION", basename($_SERVER["PHP_SELF"])."?cmd=ready_censor&ref_id=".
											  $_GET["ref_id"]."&pos_pk=".$node["pos_pk"]."&thr_pk=".$_GET["thr_pk"].
											  "&offset=".$Start."&orderby=".$_GET["orderby"]);
							$tpl->setVariable("TXT_CENS_MESSAGE", $lng->txt("forums_the_post"));
							$tpl->setVariable("TXT_CENS_COMMENT", $lng->txt("forums_censor_comment").":");
							$tpl->setVariable("CENS_MESSAGE", $frm->prepareText($node["pos_cens_com"],2));
							$tpl->setVariable("CANCEL_BUTTON", $lng->txt("cancel"));
							$tpl->setVariable("CONFIRM_BUTTON", $lng->txt("confirm"));

							if ($node["pos_cens"] == 1)
							{
								$tpl->setVariable("TXT_CENS", $lng->txt("forums_info_censor2_post"));
								$tpl->setVariable("CANCEL_BUTTON", $lng->txt("yes"));
								$tpl->setVariable("CONFIRM_BUTTON", $lng->txt("no"));
							}
							else
								$tpl->setVariable("TXT_CENS", $lng->txt("forums_info_censor_post"));

							$tpl->parseCurrentBlock("censorship_cell");
						}
						elseif (($_GET["cmd"] == "delete" && $_GET["pos_pk"] != $node["pos_pk"]) || $_GET["cmd"] != "delete")
						{
							// 1. cens button
							$tpl->setCurrentBlock("cens_cell");
							$tpl->setVariable("CENS_BUTTON","<a href=\"forums_threads_view.php?cmd=censor&pos_pk=".
											  $node["pos_pk"]."&ref_id=".$_GET["ref_id"]."&offset=".
											  $Start."&orderby=".$_GET["orderby"]."&thr_pk=".$_GET["thr_pk"]."#".
											  $node["pos_pk"]."\">".$lng->txt("censorship")."</a>");
							$tpl->parseCurrentBlock("cens_cell");
						}
					} // if ($rbacsystem->checkAccess("delete post", $_GET["ref_id"]))

					if (($_GET["cmd"] != "delete") || ($_GET["cmd"] == "delete" && $_GET["pos_pk"] != $node["pos_pk"]))
					{
						if ($_GET["cmd"] != "censor" || $_GET["pos_pk"] != $node["pos_pk"])
						{
							// button: edit article
							if ($frm->checkEditRight($node["pos_pk"]) && $node["pos_cens"] != 1)
							{
								$tpl->setCurrentBlock("edit_cell");
								$tpl->setVariable("EDIT_BUTTON","<a href=\"forums_threads_view.php?cmd=showedit&pos_pk=".
												$node["pos_pk"]."&ref_id=".$_GET["ref_id"]."&offset=".$Start."&orderby=".
												$_GET["orderby"]."&thr_pk=".$_GET["thr_pk"]."#".$node["pos_pk"]."\">".
												$lng->txt("edit")."</a>");
								$tpl->parseCurrentBlock("edit_cell");
							}

							if ($node["pos_cens"] != 1)
							{
								// button: print
								$tpl->setCurrentBlock("print_cell");
								//$tpl->setVariable("SPACER","<hr noshade=\"noshade\" width=\"100%\" size=\"1\" align=\"center\">");
								$tpl->setVariable("PRINT_BUTTON","<a href=\"forums_export.php?&print_post=".
												$node["pos_pk"]."&top_pk=".$topicData["top_pk"]."&thr_pk=".
												$threadData["thr_pk"]."\" target=\"_blank\">".$lng->txt("print")."</a>");
								$tpl->parseCurrentBlock("print_cell");
							}
							if ($node["pos_cens"] != 1)
							{
							// button: reply
							$tpl->setCurrentBlock("reply_cell");
							//$tpl->setVariable("SPACER","<hr noshade=\"noshade\" width=\"100%\" size=\"1\" align=\"center\">");
							$tpl->setVariable("REPLY_BUTTON","<a href=\"forums_threads_view.php?cmd=showreply&pos_pk=".
											$node["pos_pk"]."&ref_id=".$_GET["ref_id"]."&offset=".$Start."&orderby=".
											$_GET["orderby"]."&thr_pk=".$_GET["thr_pk"]."#".$node["pos_pk"]."\">".
											$lng->txt("reply")."</a>");
							$tpl->parseCurrentBlock("reply_cell");
							}
						}

						$tpl->setVariable("POST_ANKER", $node["pos_pk"]);

					} // if (($_GET["cmd"] != "delete") || ($_GET["cmd"] == "delete" && $_GET["pos_pk"] != $node["pos_pk"]))

					$tpl->setVariable("SPACER","<hr noshade=\"noshade\" width=\"100%\" size=\"1\" align=\"center\">");

				} // else

			} // if ($rbacsystem->checkAccess("write", $_GET["ref_id"]))
			else
			{
				$tpl->setVariable("POST_ANKER", $node["pos_pk"]);
			}
			// DOWNLOAD ATTACHMENTS
			$tmp_file_obj =& new ilFileDataForum($forumObj->getId(),$node["pos_pk"]);
			if(count($tmp_file_obj->getFilesOfPost()))
			{
				if($node["pos_pk"] != $_GET["pos_pk"] || $_GET["cmd"] != "showedit")
				{
					foreach($tmp_file_obj->getFilesOfPost() as $file)
					{
						$tpl->setCurrentBlock("attachment_download_row");
						$tpl->setVariable("HREF_DOWNLOAD","forums_threads_view.php?ref_id=$_GET[ref_id]&pos_pk=$node[pos_pk]&file=".
										  urlencode($file["name"]));
						$tpl->setVariable("DOWNLOAD_IMGPATH",$tpl->tplPath);
						$tpl->setVariable("TXT_DOWNLOAD_ATTACHMENT",$lng->txt("forums_download_attachment"));
						$tpl->parseCurrentBlock();
					}
					$tpl->setCurrentBlock("attachments");
					$tpl->setVariable("TXT_ATTACHMENTS_DOWNLOAD",$lng->txt("forums_attachments"));
					$tpl->parseCurrentBlock();
				}
			}


			$tpl->setCurrentBlock("posts_row");
			$rowCol = ilUtil::switchColor($z,"tblrow2","tblrow1");
			if ($_GET["cmd"] != "censor" || $_GET["pos_pk"] != $node["pos_pk"])
			{
				$tpl->setVariable("ROWCOL", $rowCol);
			}
			else
			{
				$tpl->setVariable("ROWCOL", "tblrowmarked");
			}

			// get author data

			unset($author);
			$author = $frm->getUser($node["author"]);
			/*
			$tpl->setVariable("AUTHOR","<a href=\"forums_user_view.php?ref_id=".$_GET["ref_id"]."&user=".
							  $node["author"]."&backurl=forums_threads_view&offset=".$Start."&orderby=".
							  $_GET["orderby"]."&thr_pk=".$_GET["thr_pk"]."\">".$author->getLogin()."</a>");
			*/
			// GET USER DATA, USED FOR IMPORTED USERS
			$usr_data = $frm->getUserData($node["author"]);

			if($node["author"])
			{
				$tpl->setVariable("AUTHOR","<a href=\"forums_user_view.php?ref_id=".$_GET["ref_id"]."&user=".
								  $usr_data["usr_id"]."&backurl=forums_threads_liste&offset=".
								  $Start."\">".$usr_data["login"]."</a>");
			}
			else
			{
				$tpl->setVariable("AUTHOR",$usr_data["login"]);
			}


			// get create- and update-dates
			if ($node["update_user"] > 0)
			{
				$span_class = "";

				// last update from moderator?
				$posMod = $frm->getModeratorFromPost($node["pos_pk"]);

				if (is_array($posMod) && $posMod["top_mods"] > 0)
				{
					$MODS = $rbacreview->assignedUsers($posMod["top_mods"]);

					if (is_array($MODS))
					{
						if (in_array($node["update_user"], $MODS))
							$span_class = "moderator_small";
					}
				}

				$node["update"] = $frm->convertDate($node["update"]);
				unset($lastuser);
				$lastuser = $frm->getUser($node["update_user"]);
				if ($span_class == "")
					$span_class = "small";
				$tpl->setVariable("POST_UPDATE","<span class=\"".$span_class."\"><br/>[".$lng->txt("edited_at").": ".
								  $node["update"]." - ".strtolower($lng->txt("from"))." ".$lastuser->getLogin()."]</span>");

			} // if ($node["update_user"] > 0)

			if($node["author"])
			{
				$tpl->setVariable("TXT_REGISTERED", $lng->txt("registered_since").":");
				$tpl->setVariable("REGISTERED_SINCE",$frm->convertDate($author->getCreateDate()));
				$numPosts = $frm->countUserArticles($author->id);
				$tpl->setVariable("TXT_NUM_POSTS", $lng->txt("forums_posts").":");
				$tpl->setVariable("NUM_POSTS",$numPosts);
			}


			// prepare post
			$node["message"] = $frm->prepareText($node["message"]);

			// make links in post usable
			$node["message"] = ilUtil::makeClickable($node["message"]);

			$tpl->setVariable("TXT_CREATE_DATE",$lng->txt("forums_thread_create_date"));
			$tpl->setVariable("POST_DATE",$frm->convertDate($node["create_date"]));
			$tpl->setVariable("SPACER","<hr noshade width=100% size=1 align='center'>");
			if ($node["pos_cens"] > 0)
				$tpl->setVariable("POST","<span class=\"moderator\">".nl2br(stripslashes($node["pos_cens_com"]))."</span>");
			else
			{
				// post from moderator?
				$modAuthor = $frm->getModeratorFromPost($node["pos_pk"]);

				$spanClass = "";

				if (is_array($modAuthor) && $modAuthor["top_mods"] > 0)
				{
					unset($MODS);

					$MODS = $rbacreview->assignedUsers($modAuthor["top_mods"]);

					if (is_array($MODS))
					{
						if (in_array($node["author"], $MODS))
							$spanClass = "moderator";
					}
				}
				if ($spanClass != "")
					$tpl->setVariable("POST","<span class=\"".$spanClass."\">".nl2br($node["message"])."</span>");
				else
					$tpl->setVariable("POST",nl2br($node["message"]));
			}

			$tpl->parseCurrentBlock("posts_row");

		} // if (($posNum > $pageHits && $z >= $Start) || $posNum <= $pageHits)

		$z ++;

	} // foreach($subtree_nodes as $node)
}
else
{
	$tpl->setCurrentBlock("posts_no");
	$tpl->setVAriable("TXT_MSG_NO_POSTS_AVAILABLE",$lng->txt("forums_posts_not_available"));
	$tpl->parseCurrentBlock("posts_no");
}

$tpl->setCurrentBlock("posttable");
$tpl->setVariable("COUNT_POST", $lng->txt("forums_count_art").": ".$posNum);
$tpl->setVariable("TXT_AUTHOR", $lng->txt("author"));
$tpl->setVariable("TXT_POST", $lng->txt("forums_thread").": ".$threadData["thr_subject"]);

$tpl->parseCurrentBlock("posttable");

$tpl->setVariable("TPLPATH", $tpl->vars["TPLPATH"]);

$tpl->show();
?>
