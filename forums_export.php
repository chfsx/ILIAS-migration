<?php
/**
* forums
*
* @author Wolfgang Merkens <wmerkens@databay.de>
* @version $Id$
*
* @package ilias
*/
require_once "./include/inc.header.php";
require_once "classes/class.ilForumExport.php";

$frmEx = new ilForumExport();

$lng->loadLanguageModule("forum");

// print
if ($_GET["print_thread"] > 0 || $_GET["print_post"] > 0)
{
	$tplEx = new ilTemplate("tpl.forums_export_print.html",true,true);
	//$tplEx->setVariable("JSPATH",dirname($_SERVER["SCRIPT_FILENAME"]));
	
	// Thread drucken
	if ($_GET["print_thread"] > 0)
	{
		$frmEx->setWhereCondition("top_pk = ".$_GET["thr_top_fk"]);
		
		// get forum- and thread-data
		if (is_array($topicData = $frmEx->getOneTopic()))
		{
		
			$frmEx->setWhereCondition("thr_pk = ".$_GET["print_thread"]);
			$threadData = $frmEx->getOneThread();			
			
			// get first post of thread
			$first_node = $frmEx->getFirstPostNode($_GET["print_thread"]);	
			
			// get complete tree of thread
			$frmEx->setOrderField("frm_posts_tree.rgt");
			$subtree_nodes = $frmEx->getPostTree($first_node);
			$posNum = count($subtree_nodes);
			
			// headline
			$tplEx->setVariable("HEADLINE", $lng->txt("forum").": ".$topicData["top_name"]." > ".$lng->txt("forums_thread").": ".$threadData["thr_subject"]." > ".$lng->txt("forums_count_art").": ".$posNum);
			
			// generate post-dates
			foreach($subtree_nodes as $node)
			{			
					
					$tplEx->setCurrentBlock("posts_row");
					$rowCol = ilUtil::switchColor($z,"tblrow2","tblrow1");
					$tplEx->setVariable("ROWCOL", $rowCol);
					
					// get author data
					unset($author);
					$author = $frmEx->getUser($node["author"]);	
					$tplEx->setVariable("AUTHOR",$author->getLogin()); 
					
					// get create- and update-dates
					if ($node["update_user"] > 0)
					{
						$node["update"] = $frmEx->convertDate($node["update"]);
						unset($lastuser);
						$lastuser = $frmEx->getUser($node["update_user"]);					
						$tplEx->setVariable("POST_UPDATE","<br/>[".$lng->txt("edited_at").": ".$node["update"]." - ".strtolower($lng->txt("from"))." ".$lastuser->getLogin()."]");
					}
		
					$tplEx->setVariable("TXT_REGISTERED", $lng->txt("registered_since"));
					$tplEx->setVariable("REGISTERED_SINCE",$frmEx->convertDate($author->getCreateDate()));
		
					$numPosts = $frmEx->countUserArticles($author->id);
					$tplEx->setVariable("TXT_NUM_POSTS", $lng->txt("forums_posts"));
					$tplEx->setVariable("NUM_POSTS",$numPosts);
					
					// prepare post
					$node["message"] = $frmEx->prepareText($node["message"]);
							
					$tplEx->setVariable("TXT_CREATE_DATE",$lng->txt("forums_thread_create_date"));
					$tplEx->setVariable("POST_DATE",$frmEx->convertDate($node["create_date"]));
					$tplEx->setVariable("SPACER","<hr noshade width=100% size=1 align='center'>");			
					$tplEx->setVariable("POST",nl2br($node["message"]));	
					$tplEx->parseCurrentBlock("posts_row");	
					
					$z ++;
					
			} // foreach($subtree_nodes as $node)
			
			$tplEx->setCurrentBlock("posttable");			
			$tplEx->setVariable("TXT_AUTHOR", $lng->txt("author"));		
			$tplEx->setVariable("TXT_POST", $lng->txt("forums_thread").": ".$threadData["thr_subject"]);	
			$tplEx->parseCurrentBlock("posttable");
			
		} // if (is_array($topicData = $frmEx->getOneTopic()))
		
	} // if ($_GET["print_thread"] > 0)
	
	// Post drucken
	elseif ($_GET["print_post"] > 0)
	{
		
		$frmEx->setWhereCondition("top_pk = ".$_GET["top_pk"]);
		
		// get forum- and thread-data
		if (is_array($topicData = $frmEx->getOneTopic()))
		{
			$frmEx->setWhereCondition("thr_pk = ".$_GET["thr_pk"]);
			$threadData = $frmEx->getOneThread();
			
			// headline
			$tplEx->setVariable("HEADLINE", $lng->txt("forum").": ".$topicData["top_name"]." > ".$lng->txt("forums_thread").": ".$threadData["thr_subject"]);
			
			$node = $frmEx->getOnePost($_GET["print_post"]);
			
			$tplEx->setCurrentBlock("posts_row");			
			$tplEx->setVariable("ROWCOL", "tblrow2");
			
			// get author data
			unset($author);
			$author = $frmEx->getUser($node["author"]);	
			$tplEx->setVariable("AUTHOR",$author->getLogin()); 
			
			// get create- and update-dates
			if ($node["update_user"] > 0)
			{
				$node["update"] = $frmEx->convertDate($node["update"]);
				unset($lastuser);
				$lastuser = $frmEx->getUser($node["update_user"]);					
				$tplEx->setVariable("POST_UPDATE","<br/>[".$lng->txt("edited_at").": ".$node["update"]." - ".strtolower($lng->txt("from"))." ".$lastuser->getLogin()."]");
			}

			$tplEx->setVariable("TXT_REGISTERED", $lng->txt("registered_since"));
			$tplEx->setVariable("REGISTERED_SINCE",$frmEx->convertDate($author->getCreateDate()));

			$numPosts = $frmEx->countUserArticles($author->id);
			$tplEx->setVariable("TXT_NUM_POSTS", $lng->txt("forums_posts"));
			$tplEx->setVariable("NUM_POSTS",$numPosts);
			
			// prepare post
			$node["message"] = $frmEx->prepareText($node["message"]);
					
			$tplEx->setVariable("TXT_CREATE_DATE",$lng->txt("forums_thread_create_date"));
			$tplEx->setVariable("POST_DATE",$frmEx->convertDate($node["create_date"]));
			$tplEx->setVariable("SPACER","<hr noshade width=100% size=1 align='center'>");			
			$tplEx->setVariable("POST",nl2br($node["message"]));	
			$tplEx->parseCurrentBlock("posts_row");	
			
			$tplEx->setCurrentBlock("posttable");			
			$tplEx->setVariable("TXT_AUTHOR", $lng->txt("author"));		
			$tplEx->setVariable("TXT_POST", $lng->txt("forums_thread").": ".$threadData["thr_subject"]);	
			$tplEx->parseCurrentBlock("posttable");
			
		} // if (is_array($topicData = $frmEx->getOneTopic()))	
		
	} // elseif ($_GET["print_post"] > 0)
	
	$tplEx->show();
	
} // if ($_GET["print_thread"] > 0 || $_GET["print_post"] > 0)
// export html
elseif ($_POST["action"] == "html")
{
	$tplEx = new ilTemplate("tpl.forums_export_html.html",true,true);
	
	for ($j = 0; $j < count($_POST["forum_id"]); $j++)
	{
		
		unset($topicData);
		unset($threadData);
		
		$frmEx->setWhereCondition("top_pk = ".$_POST["forum_id"][$j]);
		
		// get forum- and thread-data
		if (is_array($topicData = $frmEx->getOneTopic()))
		{
			// source: forum list
			if ($startTbl == "frm_data")
			{							
				// get list of threads
				$frmEx->setOrderField("thr_date DESC");
				$resThreads = $frmEx->getThreadList($topicData["top_pk"]);
				$thrNum = $resThreads->numRows();
				
				if ($thrNum > 0)
				{
					
					while ($threadData = $resThreads->fetchRow(DB_FETCHMODE_ASSOC))
					{							
						
						// get first post of thread
						$first_node = $frmEx->getFirstPostNode($threadData["thr_pk"]);	
						
						// get complete tree of thread
						$frmEx->setOrderField("frm_posts_tree.rgt");
						$subtree_nodes = $frmEx->getPostTree($first_node);
						$posNum = count($subtree_nodes);	
						$z = 0;
						
						// generate post-dates
						foreach($subtree_nodes as $node)
						{			
								
								$tplEx->setCurrentBlock("posts_row");
								$rowCol = ilUtil::switchColor($z,"tblrow2","tblrow1");
								$tplEx->setVariable("ROWCOL", $rowCol);
								
								// get author data
								unset($author);
								$author = $frmEx->getUser($node["author"]);	
								$tplEx->setVariable("AUTHOR",$author->getLogin()); 
								
								// get create- and update-dates
								if ($node["update_user"] > 0)
								{
									$node["update"] = $frmEx->convertDate($node["update"]);
									unset($lastuser);
									$lastuser = $frmEx->getUser($node["update_user"]);					
									$tplEx->setVariable("POST_UPDATE","<br/>[".$lng->txt("edited_at").": ".$node["update"]." - ".strtolower($lng->txt("from"))." ".$lastuser->getLogin()."]");
								}
					
								$tplEx->setVariable("TXT_REGISTERED", $lng->txt("registered_since"));
								$tplEx->setVariable("REGISTERED_SINCE",$frmEx->convertDate($author->getCreateDate()));
					
								$numPosts = $frmEx->countUserArticles($author->id);
								$tplEx->setVariable("TXT_NUM_POSTS", $lng->txt("forums_posts"));
								$tplEx->setVariable("NUM_POSTS",$numPosts);
								
								// prepare post
								$node["message"] = $frmEx->prepareText($node["message"]);
										
								$tplEx->setVariable("TXT_CREATE_DATE",$lng->txt("forums_thread_create_date"));
								$tplEx->setVariable("POST_DATE",$frmEx->convertDate($node["create_date"]));
								$tplEx->setVariable("SPACER","<hr noshade width=100% size=1 align='center'>");			
								$tplEx->setVariable("POST",nl2br($node["message"]));	
								$tplEx->parseCurrentBlock("posts_row");	
								
								$z ++;
								
						} // foreach($subtree_nodes as $node)
						
						$tplEx->setCurrentBlock("posttable");			
						$tplEx->setVariable("TXT_AUTHOR", $lng->txt("author"));		
						$tplEx->setVariable("TXT_POST", $lng->txt("forums_thread").": ".$threadData["thr_subject"]);	
						$tplEx->parseCurrentBlock("posttable");
						
						// Thread Headline
						$tplEx->setCurrentBlock("thread_headline");			
						$tplEx->setVariable("T_TITLE",$threadData["thr_subject"]);
						$tplEx->setVariable("T_NUM_POSTS",$threadData["thr_num_posts"]);	
						$tplEx->setVariable("T_NUM_VISITS",$threadData["visits"]);
						$tplEx->setVariable("T_FORUM",$topicData["top_name"]);
											
						unset($t_author);
						$t_author = $frmEx->getUser($threadData["thr_usr_id"]);	
						$tplEx->setVariable("T_AUTHOR",$t_author->getLogin()); 
						
						$tplEx->setVariable("T_TXT_FORUM", $lng->txt("forum").": ");					
						$tplEx->setVariable("T_TXT_TOPIC", $lng->txt("forums_thread").": ");
						$tplEx->setVariable("T_TXT_AUTHOR", $lng->txt("forums_thread_create_from").": ");
						$tplEx->setVariable("T_TXT_NUM_POSTS", $lng->txt("forums_articles").": ");
						$tplEx->setVariable("T_TXT_NUM_VISITS", $lng->txt("visits").": ");
						
						$tplEx->parseCurrentBlock("thread_headline");
						
						$tplEx->setCurrentBlock("thread_block");	
						$tplEx->parseCurrentBlock("thread_block");		
						
					} // while ($threadData = $resThreads->fetchRow(DB_FETCHMODE_ASSOC))
					
				} // if ($thrNum > 0)
				
				// Forum Headline
				$tplEx->setCurrentBlock("forum_headline");		
				
				$f_moderators = "";
				
				// create-dates of forum
				if ($topicData["top_usr_id"] > 0)
				{			
					$f_moderator = $frmEx->getUser($topicData["top_usr_id"]);	
					
					$tplEx->setVariable("F_START_DATE_TXT1", $lng->txt("launch"));
					$tplEx->setVariable("F_START_DATE_TXT2", strtolower($lng->txt("by")));
					$tplEx->setVariable("F_START_DATE", $frmEx->convertDate($topicData["top_date"]));
					$tplEx->setVariable("F_START_DATE_USER",$f_moderator->getLogin()); 										
				}
				
				// when forum was changed ...
				if ($topicData["update_user"] > 0)
				{			
					$f_moderator = $frmEx->getUser($topicData["update_user"]);	
					
					$tplEx->setVariable("F_LAST_UPDATE_TXT1", $lng->txt("last_change"));
					$tplEx->setVariable("F_LAST_UPDATE_TXT2", strtolower($lng->txt("by")));
					$tplEx->setVariable("F_LAST_UPDATE", $frmEx->convertDate($topicData["top_update"]));
					$tplEx->setVariable("F_LAST_UPDATE_USER",$f_moderator->getLogin()); 						
				}
				
				// get dates of moderators
				if ($topicData["top_mods"] > 0)
				{			
					$MODS = $rbacreview->assignedUsers($topicData["top_mods"]);
												
					for ($i = 0; $i < count($MODS); $i++)
					{
						unset($f_moderator);						
						$f_moderator = $frmEx->getUser($MODS[$i]);
						
						if ($f_moderators != "")
						{
							$f_moderators .= ", ";
						}

						$f_moderators .= $f_moderator->getLogin();
					}
					
				}							
				$tplEx->setVariable("F_MODS",$f_moderators); 
				
				$tplEx->setVariable("F_TITLE",$topicData["top_name"]);
				$tplEx->setVariable("F_DESCRIPTION",$topicData["top_description"]);
				$tplEx->setVariable("F_NUM_THREADS",$topicData["top_num_threads"]);
				$tplEx->setVariable("F_NUM_POSTS",$topicData["top_num_posts"]);		
				$tplEx->setVariable("F_NUM_VISITS",$topicData["visits"]);
				
				$tplEx->setVariable("F_TXT_FORUM", $lng->txt("forum").": ");
				$tplEx->setVariable("F_TXT_NUM_THREADS", $lng->txt("forums_threads").": ");
				$tplEx->setVariable("F_TXT_NUM_POSTS", $lng->txt("forums_articles").": ");
				$tplEx->setVariable("F_TXT_NUM_VISITS", $lng->txt("visits").": ");
				$tplEx->setVariable("F_TXT_MODS", $lng->txt("forums_moderators").": ");				
				
				$tplEx->parseCurrentBlock("forum_headline");
				
				$tplEx->setCurrentBlock("forum_block");	
				$tplEx->parseCurrentBlock("forum_block");
			
			} // if ($startTbl == "frm_data")		
			// source: forum list
			elseif ($startTbl == "frm_threads")	
			{
				unset($topicData);
				unset($threadData);
				
				if (is_array($topicData = $frmEx->getOneTopic()))
				{
					
					for ($j = 0; $j < count($_POST["forum_id"]); $j++)
					{
						
						$frmEx->setWhereCondition("thr_pk = ".$_POST["forum_id"][$j]);
						$threadData = $frmEx->getOneThread();			
						
						// get first post of thread
						$first_node = $frmEx->getFirstPostNode($_POST["forum_id"][$j]);	
						
						// get complete tree of thread
						$frmEx->setOrderField("frm_posts_tree.rgt");
						$subtree_nodes = $frmEx->getPostTree($first_node);
						$posNum = count($subtree_nodes);						
						$z = 0;
						
						// generate post-dates
						foreach($subtree_nodes as $node)
						{			
								
								$tplEx->setCurrentBlock("posts_row");
								$rowCol = ilUtil::switchColor($z,"tblrow2","tblrow1");
								$tplEx->setVariable("ROWCOL", $rowCol);
								
								// get author data
								unset($author);
								$author = $frmEx->getUser($node["author"]);	
								$tplEx->setVariable("AUTHOR",$author->getLogin()); 
								
								// get create- and update-dates
								if ($node["update_user"] > 0)
								{
									$node["update"] = $frmEx->convertDate($node["update"]);
									unset($lastuser);
									$lastuser = $frmEx->getUser($node["update_user"]);					
									$tplEx->setVariable("POST_UPDATE","<br/>[".$lng->txt("edited_at").": ".$node["update"]." - ".strtolower($lng->txt("from"))." ".$lastuser->getLogin()."]");
								}
					
								$tplEx->setVariable("TXT_REGISTERED", $lng->txt("registered_since"));
								$tplEx->setVariable("REGISTERED_SINCE",$frmEx->convertDate($author->getCreateDate()));
					
								$numPosts = $frmEx->countUserArticles($author->id);
								$tplEx->setVariable("TXT_NUM_POSTS", $lng->txt("forums_posts"));
								$tplEx->setVariable("NUM_POSTS",$numPosts);
								
								// prepare post
								$node["message"] = $frmEx->prepareText($node["message"]);
										
								$tplEx->setVariable("TXT_CREATE_DATE",$lng->txt("forums_thread_create_date"));
								$tplEx->setVariable("POST_DATE",$frmEx->convertDate($node["create_date"]));
								$tplEx->setVariable("SPACER","<hr noshade width=100% size=1 align='center'>");			
								$tplEx->setVariable("POST",nl2br($node["message"]));	
								$tplEx->parseCurrentBlock("posts_row");	
								
								$z ++;
								
						} // foreach($subtree_nodes as $node)
						
						$tplEx->setCurrentBlock("posttable");			
						$tplEx->setVariable("TXT_AUTHOR", $lng->txt("author"));		
						$tplEx->setVariable("TXT_POST", $lng->txt("forums_thread").": ".$threadData["thr_subject"]);	
						$tplEx->parseCurrentBlock("posttable");
						
						// Thread Headline
						$tplEx->setCurrentBlock("thread_headline");			
						$tplEx->setVariable("T_TITLE",$threadData["thr_subject"]);
						$tplEx->setVariable("T_NUM_POSTS",$threadData["thr_num_posts"]);	
						$tplEx->setVariable("T_NUM_VISITS",$threadData["visits"]);
						$tplEx->setVariable("T_FORUM",$topicData["top_name"]);
											
						unset($t_author);
						$t_author = $frmEx->getUser($threadData["thr_usr_id"]);	
						$tplEx->setVariable("T_AUTHOR",$t_author->getLogin()); 
						
						$tplEx->setVariable("T_TXT_FORUM", $lng->txt("forum").": ");					
						$tplEx->setVariable("T_TXT_TOPIC", $lng->txt("forums_thread").": ");
						$tplEx->setVariable("T_TXT_AUTHOR", $lng->txt("forums_thread_create_from").": ");
						$tplEx->setVariable("T_TXT_NUM_POSTS", $lng->txt("forums_articles").": ");
						$tplEx->setVariable("T_TXT_NUM_VISITS", $lng->txt("visits").": ");
						
						$tplEx->parseCurrentBlock("thread_headline");
						
						$tplEx->setCurrentBlock("thread_block");	
						$tplEx->parseCurrentBlock("thread_block");	
						
					} // for ($j = 0; $j < count($_POST["forum_id"]); $j++)
					
					$tplEx->setCurrentBlock("forum_block");	
					$tplEx->parseCurrentBlock("forum_block");
					
				} // if (is_array($topicData = $frmEx->getOneTopic()))
				
			}
			
			
		} // if (is_array($topicData = $frmEx->getOneTopic()))
		
		
	} // for ($j = 0; $j < count($_POST["forum_id"]); $j++)
	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"forum_html_export_".$_GET["ref_id"].".html\"");
	
	$tplEx->show();
	
	exit();
	
} // elseif ($_POST["action"] == "html")

?>
