<#2949>
<?php
	// empty step
?>
<#2950>
<?php
	$ilDB->modifyTableColumn('table_properties', 'value',
		array("type" => "text", "length" => 4000, "notnull" => true));
?>
<#2951>
<?php
if(!$ilDB->tableExists('payment_paymethods'))
{
	$fields = array (
    'pm_id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
    'pm_title'   => array ('type' => 'text', 'notnull' => true, 'length' => 60, 'fixed' => false),
    'pm_enabled'    => array ('type' => 'integer', 'length'  => 1,"notnull" => true,"default" => 0),
    'save_usr_adr'  => array ('type' => 'integer', 'length'  => 1,"notnull" => true,"default" => 0)
  );
  $ilDB->createTable('payment_paymethods', $fields);
  $ilDB->addPrimaryKey('payment_paymethods', array('pm_id'));
// this is not necessary and triggers an error under oracle, since the index
// is already created with the primary key: (alex, 19.7.2010)
//  $ilDB->addIndex('payment_paymethods',array('pm_id'),'i1');
  $ilDB->createSequence("payment_paymethods");
}
?>
<#2952>
<?php
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_bill','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_bill = 1 : $pm_bill = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_bill','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $adr_bill = 1: $adr_bill = 0;

	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_bmf','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_bmf =1: $pm_bmf = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_bmf','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $adr_bmf = 1: $adr_bmf = 0;

	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_paypal','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_ppal =1: $pm_ppal = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_paypal','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $adr_ppal = 1: $adr_ppal = 0;

	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_epay','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_epay = 1: $pm_epay = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_epay','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']==1 ? $adr_epay = 1: $adr_epay = 0;

	$query = 'INSERT INTO payment_paymethods (pm_id, pm_title, pm_enabled, save_usr_adr) VALUES (%s, %s, %s, %s)';
	$types = array("integer", "text", "integer", "integer");

	$nextId = $ilDB->nextId('payment_paymethods');
	$bill_data = array($nextId, 'bill', $pm_bill, $adr_bill);
	$ilDB->manipulateF($query,$types,$bill_data);

	$nextId = $ilDB->nextId('payment_paymethods');
	$bmf_data  = array($nextId, 'bmf',  $pm_bmf, $adr_bmf);
	$ilDB->manipulateF($query,$types,$bmf_data);

	$nextId = $ilDB->nextId('payment_paymethods');
	$paypal_data  = array($nextId, 'paypal',  $pm_ppal, $adr_ppal);
	$ilDB->manipulateF($query,$types,$paypal_data);

	$nextId = $ilDB->nextId('payment_paymethods');
	$epay_data  = array($nextId, 'epay', $pm_epay, $adr_epay);
	  $ilDB->manipulateF($query,$types,$epay_data);
?>
<#2953>
<?php
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_bill', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_bmf', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_paypal', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_epay', 'text'));

	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_bill', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_bmf', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_paypal', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_epay', 'text'));
?>
<#2954>
<?php
	if($ilDB->tableColumnExists("payment_settings", "hide_filtering"))
	{
		$ilDB->renameTableColumn('payment_settings', 'hide_filtering', 'objects_allow_custom_sorting');
	}
	$ilDB->modifyTableColumn('payment_settings', 'objects_allow_custom_sorting', array(
			"type" => "integer",
			'length' => 4,
			"notnull" => true,
			"default" => 0));
?>
<#2955>
<?php
	if (!$ilDB->tableColumnExists("payment_statistic", "currency_unit"))
	{
		$ilDB->addTableColumn("payment_statistic", "currency_unit", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 16,
		 	"fixed" => false));
	}
	$res = $ilDB->query('SELECT price from payment_statistic');
	$row = $ilDB->fetchAssoc($res);
	while($row = $ilDB->fetchAssoc($res))
	{
		$exp = explode(' ', $row['price']);
		$amount = $exp['0'];
		$currency = $exp['1'];

		$upd = $ilDB->manipulateF('UPDATE payment_statistic
			SET price = %s, currency_unit = %s',
		array('float','text'), array($amount, $currency));
	}
?>
<#2956>
<?php
if($ilDB->tableExists('payment_currencies'))
	$ilDB->dropTable('payment_currencies');
if($ilDB->tableExists('payment_currencies_seq'))
		$ilDB->dropTable('payment_currencies_seq');
?>
<#2957>
<?php
if(!$ilDB->tableExists('payment_currencies'))
{
	$fields = array (
    'currency_id'    => array(
    		'type' => 'integer',
    		'length'  => 4,
    		'notnull' => true,
    		'default' => 0),

    'unit'   => array(
    		'type' => 'text',
    		'notnull' => true,
    		'length' => 16,
    		'fixed' => false),

	'iso_code' => array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 4,
		 	"fixed" => false),

	'symbol' => array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 4,
		 	"fixed" => false),

	'conversion_rate' => array(
			"type" => "float",
			"notnull" => true,
			"default" => 0)
  );
  $ilDB->createTable('payment_currencies', $fields);
  $ilDB->addPrimaryKey('payment_currencies', array('currency_id'));
  $ilDB->createSequence("payment_currencies");
}
?>
<#2958>
<?php
	$res = $ilDB->query('SELECT currency_unit FROM payment_settings');
	$row = $ilDB->fetchAssoc($res);

	while($row = $ilDB->fetchAssoc($res))
	{
		$nextId = $ilDB->nextId('payment_currencies');
		$ins_res = $ilDB->manipulateF('INSERT INTO payment_currencies (currency_id, unit)
			VALUES(%s,%s)',
		array('integer', 'text'),
		array($nextId, $row['currency_unit']));

		$upd_prices = $ilDB->manipulateF('UPDATE payment_prices SET currency = %s',
		array('integer'), array($nextId));
		$ilDB->manipulateF('UPDATE payment_statistic SET currency_unit = %s', array('text'), array($row['currency_unit']));
	}
?>
<#2959>
<?php
	$res = $ilDB->query('SELECT * FROM payment_statistic');
	$statistic_arr = array();
	$counter = 0;
	while($row = $ilDB->fetchAssoc($res))
	{
		$statistic_arr[$counter]['booking_id'] = $row['booking_id'];
		$tmp_p = str_replace(",", ".", $row['price']);
		$pr = str_replace(' ', '', $tmp_p);
		$statistic_arr[$counter]['price'] = (float)$pr;
		$tmp_d =  str_replace(",", ".",  $row['discount']);
		$dis = str_replace(' ', '', $tmp_d);
		$statistic_arr[$counter]['discount'] =(float)$dis;
		$counter++;
	}

	$ilDB->modifyTableColumn('payment_statistic', 'price', array(
			"type" => "float",
			"notnull" => true,
			"default" => 0));

	$ilDB->modifyTableColumn('payment_statistic', 'discount', array(
			"type" => "float",
			"notnull" => true,
			"default" => 0));

	foreach($statistic_arr as $stat)
	{
		$upd = $ilDB->manipulateF('UPDATE payment_statistic SET
			price = %s,
			discount = %s
		WHERE booking_id = %s',
		array('float', 'float','integer'),
		array($stat['price'],$stat['discount'], $stat['booking_id']));
	}
?>
<#2960>
<?php
	if (!$ilDB->tableColumnExists("payment_statistic", "email_extern"))
	{
		$ilDB->addTableColumn('payment_statistic', 'email_extern', array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 80,
		 	"fixed" => false));
	}
	if (!$ilDB->tableColumnExists("payment_statistic", "name_extern"))
	{
		$ilDB->addTableColumn('payment_statistic', 'name_extern', array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 80,
		 	"fixed" => false));
	}
?>
<#2961>
<?php
	if (!$ilDB->tableColumnExists("payment_shopping_cart", "session_id"))
	{
		$ilDB->addTableColumn('payment_shopping_cart', 'session_id', array(
			"type" => "text",
			"notnull" => true,
		 	"length" => 80,
		 	"fixed" => false));
	}
?>
<#2962>
<?php
	if ($ilDB->tableExists('payment_bill_vendor'))
	{
		$ilDB->dropTable('payment_bill_vendor');
	}
?>
<#2963>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#2964>
<?php
	$ilDB->dropIndex('svy_category', 'i2');
?>
<#2965>
<?php
if($ilDB->tableColumnExists('svy_category','scale'))
{
	$ilDB->dropTableColumn('svy_category', 'scale');
}
?>
<#2966>
<?php
if($ilDB->tableColumnExists('svy_category','other'))
{
	$ilDB->dropTableColumn('svy_category', 'other');
}
?>
<#2967>
<?php
if(!$ilDB->tableColumnExists('svy_variable','other'))
{
  $ilDB->addTableColumn("svy_variable", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
}
?>
<#2968>
<?php
if($ilDB->tableColumnExists('svy_qst_mc','use_other_answer'))
{
	$ilDB->dropTableColumn('svy_qst_mc', 'use_other_answer');
}
?>
<#2969>
<?php
if($ilDB->tableColumnExists('svy_qst_sc','use_other_answer'))
{
	$ilDB->dropTableColumn('svy_qst_sc', 'use_other_answer');
}
?>
<#2970>
<?php
	// mail rcp_to
	$ilDB->addTableColumn("mail", "rcp_to_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET rcp_to_tmp = rcp_to');
	$ilDB->dropTableColumn('mail', 'rcp_to');
	$ilDB->renameTableColumn("mail", "rcp_to_tmp", "rcp_to");
?>
<#2971>
<?php
	// mail rcp_cc
	$ilDB->addTableColumn("mail", "rcp_cc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET rcp_cc_tmp = rcp_cc');
	$ilDB->dropTableColumn('mail', 'rcp_cc');
	$ilDB->renameTableColumn("mail", "rcp_cc_tmp", "rcp_cc");
?>
<#2972>
<?php
	// mail rcp_bcc
	$ilDB->addTableColumn("mail", "rcp_bcc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET rcp_bcc_tmp = rcp_bcc');
	$ilDB->dropTableColumn('mail', 'rcp_bcc');
	$ilDB->renameTableColumn("mail", "rcp_bcc_tmp", "rcp_bcc");
?>
<#2973>
<?php
	// mail_saved rcp_to
	$ilDB->addTableColumn("mail_saved", "rcp_to_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET rcp_to_tmp = rcp_to');
	$ilDB->dropTableColumn('mail_saved', 'rcp_to');
	$ilDB->renameTableColumn("mail_saved", "rcp_to_tmp", "rcp_to");
?>
<#2974>
<?php
	// mail_saved rcp_cc
	$ilDB->addTableColumn("mail_saved", "rcp_cc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET rcp_cc_tmp = rcp_cc');
	$ilDB->dropTableColumn('mail_saved', 'rcp_cc');
	$ilDB->renameTableColumn("mail_saved", "rcp_cc_tmp", "rcp_cc");
?>
<#2975>
<?php
	// mail_saved rcp_bcc
	$ilDB->addTableColumn("mail_saved", "rcp_bcc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET rcp_bcc_tmp = rcp_bcc');
	$ilDB->dropTableColumn('mail_saved', 'rcp_bcc');
	$ilDB->renameTableColumn("mail_saved", "rcp_bcc_tmp", "rcp_bcc");
?>
<#2976>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#2977>
<?php
	$ilDB->addTableColumn("content_object", "public_scorm_file", array(
		"type" => "text",
		"notnull" => false,
		"length" => 50));
?>
<#2978>
<?php
	$query = 'UPDATE usr_pref SET value = ROUND(value / 60) WHERE keyword = %s AND value IS NOT NULL';
	if($ilDB->getDBType() == 'oracle')
	{
		$query .= " AND LENGTH(TRIM(TRANSLATE (value, ' +-.0123456789',' '))) IS NULL";
		$ilDB->manipulateF($query, array('text'), array('session_reminder_lead_time'));
	}
	else
	{
		$ilDB->manipulateF($query, array('text'), array('session_reminder_lead_time'));
	}
?>
<#2979>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#2980>
<?php

if(!$ilDB->tableExists('openid_provider'))
{
	$fields = array(
		'provider_id'	=> array(
			'type'		=> 'integer',
			'length'	=> 4,
		),
		'enabled' 		=> array(
			'type' 			=> 'integer',
			'length' 		=> 1,
		),
		'name' 			=> array(
			'type' 			=> 'text',
			'length' 		=> 128,
			'fixed'			=> false,
			'notnull'		=> false
		),
		'url'			=> array(
			'type'			=> 'text',
			'length'		=> 512,
			'fixed'			=> false,
			'notnull'		=> false
		),
		'image'			=> array(
			'type'			=> 'integer',
			'length'		=> 2
		)
	);
	$ilDB->createTable('openid_provider',$fields);
	$ilDB->addPrimaryKey('openid_provider',array('provider_id'));
	$ilDB->createSequence('openid_provider');
	
}
?>

<#2981>
<?php
$query = "INSERT INTO openid_provider (provider_id,enabled,name,url,image) ".
	"VALUES ( ".
	$ilDB->quote($ilDB->nextId('openid_provider'),'integer').','.
	$ilDB->quote(1,'integer').','.
	$ilDB->quote('MyOpenID','text').','.
	$ilDB->quote('http://%s.myopenid.com').','.
	$ilDB->quote(1,'integer').
	")";
$res = $ilDB->query($query);
?>
<#2982>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 0)
	{
		$ilDB->createTable("exc_assignment",
			array(
				"id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"exc_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"time_stamp" => array(
					"type" => "integer", "length" => 4, "notnull" => false
				),
				"instruction" => array(
					"type" => "clob"
				)
			)
		);

		$ilDB->addPrimaryKey("exc_assignment", array("id"));
		
		$ilDB->createSequence("exc_assignment");
	}
?>
<#2983>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 1)
	{
		$ilDB->createTable("exc_mem_ass_status",
			array(
				"ass_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"usr_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"notice" => array(
					"type" => "text", "length" => 4000, "notnull" => false
				),
				"returned" => array(
					"type" => "integer", "length" => 1, "notnull" => true, "default" => 0
				),
				"solved" => array(
					"type" => "integer", "length" => 1, "notnull" => false
				),
				"status_time" => array(
					"type" => "timestamp", "notnull" => false
				),
				"sent" => array(
					"type" => "integer", "length" => 1, "notnull" => false
				),
				"sent_time" => array(
					"type" => "timestamp", "notnull" => false
				),
				"feedback_time" => array(
					"type" => "timestamp", "notnull" => false
				),
				"feedback" => array(
					"type" => "integer", "length" => 1, "notnull" => true, "default" => 0
				),
				"status" => array(
					"type" => "text", "length" => 9, "fixed" => true, "default" => "notgraded", "notnull" => false
				)
			)
		);
			
		$ilDB->addPrimaryKey("exc_mem_ass_status", array("ass_id", "usr_id"));
	}
?>
<#2984>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 2)
	{
		$ilDB->addTableColumn("exc_returned",
			"ass_id",
			array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#2985>
<?php
	/*$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 3)
	{
		$ilDB->createTable("exc_mem_tut_status",
			array (
				"ass_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"mem_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"tut_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"download_time" => array(
					"type" => "timestamp"
				)
			)
		);
	}*/
?>
<#2986>
<?php
	/*$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 4)
	{
		$ilDB->addPrimaryKey("exc_mem_tut_status", array("ass_id", "mem_id", "tut_id"));
	}*/
?>
<#2987>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 5)
	{
		$set = $ilDB->query("SELECT * FROM exc_data");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			// Create exc_assignment records for all existing exercises
			// -> instruction and time_stamp fields in exc_data are obsolete
			$next_id = $ilDB->nextId("exc_assignment");
			$ilDB->insert("exc_assignment", array(
				"id" => array("integer", $next_id),
				"exc_id" => array("integer", $rec["obj_id"]),
				"time_stamp" => array("integer", $rec["time_stamp"]),
				"instruction" => array("clob", $rec["instruction"])
				));
		}
	}
?>
<#2988>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 6)
	{
		$ilDB->addIndex("exc_members", array("obj_id"), "ob");
	}
?>
<#2989>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 7)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$set2 = $ilDB->query("SELECT * FROM exc_members ".
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
			while ($rec2  = $ilDB->fetchAssoc($set2))
			{
				$ilDB->manipulate("INSERT INTO exc_mem_ass_status ".
					"(ass_id, usr_id, notice, returned, solved, status_time, sent, sent_time,".
					"feedback_time, feedback, status) VALUES (".
					$ilDB->quote($rec["id"], "integer").",".
					$ilDB->quote($rec2["usr_id"], "integer").",".
					$ilDB->quote($rec2["notice"], "text").",".
					$ilDB->quote($rec2["returned"], "integer").",".
					$ilDB->quote($rec2["solved"], "integer").",".
					$ilDB->quote($rec2["status_time"], "timestamp").",".
					$ilDB->quote($rec2["sent"], "integer").",".
					$ilDB->quote($rec2["sent_time"], "timestamp").",".
					$ilDB->quote($rec2["feedback_time"], "timestamp").",".
					$ilDB->quote($rec2["feedback"], "integer").",".
					$ilDB->quote($rec2["status"], "text").
					")");
			}
		}
	}
?>
<#2990>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 8)
	{
		$ilDB->addIndex("exc_usr_tutor", array("obj_id"), "ob");
	}
?>
<#2991>
<?php
	/*$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 9)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$set2 = $ilDB->query("SELECT * FROM exc_usr_tutor ".
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
			while ($rec2  = $ilDB->fetchAssoc($set2))
			{
				$ilDB->manipulate("INSERT INTO exc_mem_tut_status ".
					"(ass_id, mem_id, tut_id, download_time) VALUES (".
					$ilDB->quote($rec["id"], "integer").",".
					$ilDB->quote($rec2["usr_id"], "integer").",".
					$ilDB->quote($rec2["tutor_id"], "integer").",".
					$ilDB->quote($rec2["download_time"], "timestamp").
					")");
			}
		}
	}*/
?>
<#2992>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 10)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$ilDB->manipulate("UPDATE exc_returned SET ".
				" ass_id = ".$ilDB->quote($rec["id"], "integer").
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
		}
	}
?>
<#2993>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 11)
	{
		$ilDB->addTableColumn("exc_assignment",
			"title",
			array("type" => "text", "length" => 200, "notnull" => false));

		$ilDB->addTableColumn("exc_assignment",
			"start_time",
			array("type" => "integer", "length" => 4, "notnull" => false));

		$ilDB->addTableColumn("exc_assignment",
			"mandatory",
			array("type" => "integer", "length" => 1, "notnull" => false, "default" => 0));
	}
?>
<#2994>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 12)
	{
		$ilDB->addTableColumn("exc_data",
			"pass_mode",
			array("type" => "text", "length" => 8, "fixed" => false,
				"notnull" => true, "default" => "all"));

		$ilDB->addTableColumn("exc_data",
			"pass_nr",
			array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#2995>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 13)
	{
		$ilDB->addTableColumn("exc_assignment",
			"order_nr",
			array("type" => "integer", "length" => 4, "notnull" => true, "default" => 0));
	}
?>
<#2996>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 14)
	{
		$ilDB->addTableColumn("exc_data",
			"show_submissions",
			array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
	}
?>
<#2997>
<?php
/*	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 15)
	{
			$new_ex_path = CLIENT_DATA_DIR."/ilExercise";
			
			$old_ex_path = CLIENT_DATA_DIR."/exercise";
			
			$old_ex_files = array();
			
			if (is_dir($old_ex_path))
			{
				$dh_old_ex_path = opendir($old_ex_path);
				
				// old exercise files into an assoc array to
				// avoid reading of all files each time
				
				while($file = readdir($dh_old_ex_path))
				{
					if(is_dir($old_ex_path."/".$file))
					{
						continue;
					}
					list($obj_id,$rest) = split('_',$file,2);
					$old_ex_files[$obj_id][] = array("full" => $file,
						"rest" => $rest);
				}
			}
//var_dump($old_ex_files);
			
			$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
			while ($rec  = $ilDB->fetchAssoc($set))
			{
				// move exercise files to assignment directories
				if (is_array($old_ex_files[$rec["exc_id"]]))
				{
					foreach ($old_ex_files[$rec["exc_id"]] as $file)
					{
						$old = $old_ex_path."/".$file["full"];
						$new = $new_ex_path."/".$this->createPathFromId($rec["exc_id"], "exc").
							"/ass_".$rec["id"]."/".$file["rest"];
							
						if (is_file($old))
						{
							ilUtil::makeDirParents(dirname($new));
							rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
						}
					}
				}

				// move submitted files to assignment directories
				if (is_dir($old_ex_path."/".$rec["exc_id"]))
				{
					$old = $old_ex_path."/".$rec["exc_id"];
					$new = $new_ex_path."/".$this->createPathFromId($rec["exc_id"], "exc").
						"/subm_".$rec["id"];
					ilUtil::makeDirParents(dirname($new));
					rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
				}
				
	}*/
?>
<#2998>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 16)
	{
		$ilDB->addTableColumn("exc_usr_tutor",
			"ass_id",
			array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#2999>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 17)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$ilDB->manipulate("UPDATE exc_usr_tutor SET ".
				" ass_id = ".$ilDB->quote($rec["id"], "integer").
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
		}
		$ilDB->manipulate("DELETE FROM exc_usr_tutor WHERE ass_id IS NULL");
	}
?>
<#3000>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 18)
	{
		$ilCtrlStructureReader->getStructure();
	}
?>
<#3001>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 19)
	{
		$ilDB->addTableColumn("exc_mem_ass_status",
			"mark",
			array("type" => "text", "length" => 32, "notnull" => false));
		$ilDB->addTableColumn("exc_mem_ass_status",
			"u_comment",
			array("type" => "text", "length" => 4000, "notnull" => false));

		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$set2 = $ilDB->query("SELECT * FROM ut_lp_marks WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer"));
			while ($rec2 = $ilDB->fetchAssoc($set2))
			{
				$set3 = $ilDB->query("SELECT ass_id FROM exc_mem_ass_status WHERE ".
					"ass_id = ".$ilDB->quote($rec["id"], "integer").
					" AND usr_id = ".$ilDB->quote($rec2["usr_id"], "integer"));
				if ($rec3 = $ilDB->fetchAssoc($set3))
				{
					$ilDB->manipulate("UPDATE exc_mem_ass_status SET ".
						" mark = ".$ilDB->quote($rec2["mark"], "text").",".
						" u_comment = ".$ilDB->quote($rec2["u_comment"], "text").
						" WHERE ass_id = ".$ilDB->quote($rec["id"], "integer").
						" AND usr_id = ".$ilDB->quote($rec2["usr_id"], "integer")
						);
				}
				else
				{
					$ilDB->manipulate("INSERT INTO exc_mem_ass_status (ass_id, usr_id, mark, u_comment) VALUES (".
						$ilDB->quote($rec["id"], "integer").", ".
						$ilDB->quote($rec2["usr_id"], "integer").", ".
						$ilDB->quote($rec2["mark"], "text").", ".
						$ilDB->quote($rec2["u_comment"], "text").")"
						);
				}
			}
		}
	}
?>
<#3002>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 20)
	{
		$ilDB->dropPrimaryKey("exc_usr_tutor");
		$ilDB->addPrimaryKey("exc_usr_tutor",
			array("ass_id", "usr_id", "tutor_id"));
	}
?>
<#3003>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 23)
	{
		$ilDB->modifyTableColumn("exc_assignment",
			"mandatory",
			array("type" => "integer", "length" => 1, "notnull" => false, "default" => 1));
		$ilDB->manipulate("UPDATE exc_assignment SET ".
			" mandatory = ".$ilDB->quote(1, "integer"));
		
		$set = $ilDB->query("SELECT e.id, e.exc_id, o.title, e.title t2 FROM exc_assignment e JOIN object_data o".
							" ON (e.exc_id = o.obj_id)");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			if ($rec["t2"] == "")
			{
				$ilDB->manipulate("UPDATE exc_assignment SET ".
					" title = ".$ilDB->quote($rec["title"], "text")." ".
					"WHERE id = ".$ilDB->quote($rec["id"], "text"));
			}
		}
	}
?>
<#3004>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 15)
	{
			include_once("./Services/Migration/DBUpdate_3004/classes/class.ilDBUpdate3004.php");
		
			$new_ex_path = CLIENT_DATA_DIR."/ilExercise";
			
			$old_ex_path = CLIENT_DATA_DIR."/exercise";
			
			$old_ex_files = array();
			
			if (is_dir($old_ex_path))
			{
				$dh_old_ex_path = opendir($old_ex_path);
				
				// old exercise files into an assoc array to
				// avoid reading of all files each time
				
				while($file = readdir($dh_old_ex_path))
				{
					if(is_dir($old_ex_path."/".$file))
					{
						continue;
					}
					list($obj_id,$rest) = split('_',$file,2);
					$old_ex_files[$obj_id][] = array("full" => $file,
						"rest" => $rest);
				}
			}

//var_dump($old_ex_files);
			
			$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
			while ($rec  = $ilDB->fetchAssoc($set))
			{
				// move exercise files to assignment directories
				if (is_array($old_ex_files[$rec["exc_id"]]))
				{
					foreach ($old_ex_files[$rec["exc_id"]] as $file)
					{
						$old = $old_ex_path."/".$file["full"];
						$new = $new_ex_path."/".ilDBUpdate3004::createPathFromId($rec["exc_id"], "exc").
							"/ass_".$rec["id"]."/".$file["rest"];
							
						if (is_file($old))
						{
							ilUtil::makeDirParents(dirname($new));
							rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
						}
					}
				}

				// move submitted files to assignment directories
				if (is_dir($old_ex_path."/".$rec["exc_id"]))
				{
					$old = $old_ex_path."/".$rec["exc_id"];
					$new = $new_ex_path."/".ilDBUpdate3004::createPathFromId($rec["exc_id"], "exc").
						"/subm_".$rec["id"];
					ilUtil::makeDirParents(dirname($new));
					rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
				}
				
			}
	}

?>
<#3005>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail rcp_to
		if (!$ilDB->tableColumnExists('mail', 'rcp_to_tmp'))
		{
			$ilDB->addTableColumn("mail", "rcp_to_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3006>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail SET rcp_to_tmp = rcp_to');
	}
?>
<#3007>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail', 'rcp_to'))
			$ilDB->dropTableColumn('mail', 'rcp_to');

		$ilDB->renameTableColumn("mail", "rcp_to_tmp", "rcp_to");
	}
?>
<#3008>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail rcp_cc
		if (!$ilDB->tableColumnExists('mail', 'rcp_cc_tmp'))
		{
			$ilDB->addTableColumn("mail", "rcp_cc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3009>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail SET rcp_cc_tmp = rcp_cc');
	}
?>
<#3010>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail', 'rcp_cc'))
			$ilDB->dropTableColumn('mail', 'rcp_cc');

		$ilDB->renameTableColumn("mail", "rcp_cc_tmp", "rcp_cc");
	}
?>
<#3011>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail rcp_bcc
		if (!$ilDB->tableColumnExists('mail', 'rcp_bcc_tmp'))
		{
			$ilDB->addTableColumn("mail", "rcp_bcc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3012>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail SET rcp_bcc_tmp = rcp_bcc');
	}
?>
<#3013>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if($ilDB->tableColumnExists('mail', 'rcp_bcc'))
			$ilDB->dropTableColumn('mail', 'rcp_bcc');

		$ilDB->renameTableColumn("mail", "rcp_bcc_tmp", "rcp_bcc");
	}
?>
<#3014>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail_saved rcp_to
		if (!$ilDB->tableColumnExists('mail_saved', 'rcp_to_tmp'))
		{
			$ilDB->addTableColumn("mail_saved", "rcp_to_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3015>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail_saved SET rcp_to_tmp = rcp_to');
	}
?>
<#3016>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail_saved', 'rcp_to'))
			$ilDB->dropTableColumn('mail_saved', 'rcp_to');

		$ilDB->renameTableColumn("mail_saved", "rcp_to_tmp", "rcp_to");
	}
?>
<#3017>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail_saved rcp_cc
		if (!$ilDB->tableColumnExists('mail_saved', 'rcp_cc_tmp'))
		{
			$ilDB->addTableColumn("mail_saved", "rcp_cc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3018>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail_saved SET rcp_cc_tmp = rcp_cc');
	}
?>
<#3019>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail_saved', 'rcp_cc'))
			$ilDB->dropTableColumn('mail_saved', 'rcp_cc');

		$ilDB->renameTableColumn("mail_saved", "rcp_cc_tmp", "rcp_cc");
	}
?>
<#3020>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail_saved rcp_bcc
		if (!$ilDB->tableColumnExists('mail_saved', 'rcp_bcc_tmp'))
		{
			$ilDB->addTableColumn("mail_saved", "rcp_bcc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3021>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail_saved SET rcp_bcc_tmp = rcp_bcc');
	}
?>
<#3022>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail_saved', 'rcp_bcc'))
			$ilDB->dropTableColumn('mail_saved', 'rcp_bcc');

		$ilDB->renameTableColumn("mail_saved", "rcp_bcc_tmp", "rcp_bcc");
	}
?>
<#3023>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail','rcp_to', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3024>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail','rcp_cc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3025>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail','rcp_bcc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3026>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail_saved','rcp_to', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3027>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail_saved','rcp_cc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3028>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		$ilDB->modifyTableColumn('mail_saved','rcp_bcc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3029>
<?php
	if(!$ilDB->tableColumnExists('usr_session', 'type'))
	{
		$ilDB->addTableColumn(
			'usr_session',
			'type',
			array(
				"type" => "integer",
				"notnull" => false,
				"length" => 4,
				"default" => null
			)
		);
	}
	if(!$ilDB->tableColumnExists('usr_session', 'createtime'))
	{
		$ilDB->addTableColumn(
			'usr_session',
			'createtime',
			array(
				"type" => "integer",
				"notnull" => false,
				"length" => 4,
				"default" => null
			)
		);
	}
 ?>
<#3030>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#3031>
<?php
	// new permission
	$new_ops_id = $ilDB->nextId('rbac_operations');

	$res = $ilDB->manipulatef('
		INSERT INTO rbac_operations (ops_id, operation, description, class)
	 	VALUES(%s, %s, %s, %s)',
	array('integer','text', 'text', 'text'),
	array($new_ops_id, 'mail_to_global_roles','User may send mails to global roles','object'));

	$res = $ilDB->queryF('SELECT obj_id FROM object_data WHERE type = %s AND title = %s',
	array('text', 'text'), array('typ', 'mail'));
	$row = $ilDB->fetchAssoc($res);

	$typ_id = $row['obj_id'];

	$query = $ilDB->manipulateF('INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
	array('integer','integer'), array($typ_id, $new_ops_id));
?>
<#3032>
<?php
	$query = 'SELECT ref_id FROM object_data '
		   . 'INNER JOIN object_reference ON object_reference.obj_id = object_data.obj_id '
		   . 'WHERE type = '.$ilDB->quote('mail', 'text');
	$res = $ilDB->query($query);
	$ref_ids = array();
	while($row = $ilDB->fetchAssoc($res))
	{
		$ref_ids[] = $row['ref_id'];
	}

	$query = 'SELECT rol_id FROM rbac_fa '
		   . 'WHERE assign = '.$ilDB->quote('y', 'text').' '
		   . 'AND parent = '.$ilDB->quote(ROLE_FOLDER_ID, 'integer');
	$res = $ilDB->query($query);
	$global_roles = array();
	while($row = $ilDB->fetchAssoc($res))
	{
		$global_roles[] = $row['rol_id'];
	}

	$query = 'SELECT ops_id FROM rbac_operations '
	       . 'WHERE operation = '.$ilDB->quote('mail_to_global_roles', 'text');
	$res = $ilDB->query($query);
	$data = $ilDB->fetchAssoc($res);
	$mtgr_permission = array();
	if((int)$data['ops_id'])
		$mtgr_permission[] = $data['ops_id'];

	foreach($global_roles as $role)
	{
		if($role == SYSTEM_ROLE_ID)
		{
			continue;
		}

		foreach($ref_ids as $ref_id)
		{
			$query = 'SELECT ops_id FROM rbac_pa '
			       . 'WHERE rol_id = '.$ilDB->quote($role, 'integer').' '
				   . 'AND ref_id = '.$ilDB->quote($ref_id, 'integer');
			$res = $ilDB->query($query);
			$operations = array();
			while($row = $ilDB->fetchAssoc($res))
			{
				$operations = unserialize($row['ops_id']);
			}
			if(!is_array($operations)) $operations = array();

			$permissions = array_unique(array_merge($operations, $mtgr_permission));

			// convert all values to integer
			foreach($permissions as $key => $operation)
			{
				$permissions[$key] = (int)$operation;
			}

			// Serialization des ops_id Arrays
			$ops_ids = serialize($permissions);

			$query = 'DELETE FROM rbac_pa '
			       . 'WHERE rol_id = %s '
			       . 'AND ref_id = %s';
			$res = $ilDB->queryF(
				$query, array('integer', 'integer'),
				array($role, $ref_id)
			);
			
			if(!count($permissions))
			{
				continue;
			}

			$ilDB->insert('rbac_pa',
				array(
					'rol_id' => array('integer', $role),
					'ops_id' => array('text', $ops_ids),
					'ref_id' => array('integer', $ref_id)
				)
			);
		}
	}
?>
<#3033>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3034>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3035>
<?php
	$ilDB->addTableColumn("ut_lp_marks", "status", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
	$ilDB->addTableColumn("ut_lp_marks", "status_changed", array(
		"type" => "timestamp",
		"notnull" => false));
	$ilDB->addTableColumn("ut_lp_marks", "status_dirty", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
	$ilDB->addTableColumn("ut_lp_marks", "percentage", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3036>
<?php
	$ilDB->addIndex('mail_obj_data',array('user_id','m_type'),'i1');
?>
<#3037>
<?php
$ilDB->addTableColumn('read_event', 'childs_read_count', array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0));
$ilDB->addTableColumn('read_event', 'childs_spent_seconds', array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0));
?>
<#3038>
<?php
  $ilDB->addIndex('addressbook',array('user_id','login','firstname','lastname'),'i1');
?>
<#3039>
<?php
	$ilDB->addIndex('mail',array('sender_id','user_id'), 'i4');
?>
<#3040>
<?php
		$ilDB->addTableColumn("frm_settings", "new_post_title", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3041>
<?php
// register new object type 'frma' for forum administration
 $id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "typ", "frma", "Forum administration", -1, ilUtil::now(), ilUtil::now()));
$typ_id = $id;

// create object data entry
$id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "frma", "__ForumAdministration", "Forum Administration", -1, ilUtil::now(), ilUtil::now()));

// create object reference entry
$ref_id = $ilDB->nextId('object_reference');
$res = $ilDB->manipulateF("INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($ref_id, $id));

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($ref_id, SYSTEM_FOLDER_ID);

// add rbac operations
// 1: edit_permissions, 2: visible, 3: read, 4:write
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 1));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 2));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 3));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 4));
?>
<#3042>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3043>
<?php
if(!$ilDB->tableExists('reg_registration_codes'))
{
	$fields = array (
		'code_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0),

		'code' => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 50,
			'fixed' => false),

	 	'role' => array(
			'type' => 'integer',
			'notnull' => false,
			'length' => 4,
			'default' => 0),

		'generated' => array(
			'type' => 'integer',
			'notnull' => false,
			'length' => 4,
			'default' => 0),

		'used' => array(
			'type' => 'integer',
			'notnull' => true,
			'length' => 4,
			'default' => 0)
	);
	$ilDB->createTable('reg_registration_codes', $fields);
	$ilDB->addPrimaryKey('reg_registration_codes', array('code_id'));
	$ilDB->addIndex('reg_registration_codes', array('code'), 'i1');
	$ilDB->createSequence("reg_registration_codes");
}
?>
<#3044>
<?php
	$ilDB->update("settings", array("value"=>array("integer", 0)), array("module"=>array("text", "common"), "keyword"=>array("text", "usr_settings_visib_reg_birthday")));
	$ilDB->update("settings", array("value"=>array("integer", 0)), array("module"=>array("text", "common"), "keyword"=>array("text", "usr_settings_visib_reg_instant_messengers")));
?>
<#3045>
<?php
if(!$ilDB->tableExists('org_unit_data'))
{
	$ilDB->createTable('org_unit_data', array(
			'ou_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'ou_title' => array(
				'type'     => 'text',
				'length'   => 128,
				'notnull' => false,
				'default' => null
			),
			'ou_subtitle' => array(
				'type'     => 'text',
				'length'   => 128,
				'notnull' => false,
				'default' => null
			),
			'ou_import_id' => array(
				'type'     => 'text',
				'length'   => 64,
				'notnull' => false,
				'default' => null
			)
	));
	$ilDB->addPrimaryKey('org_unit_data', array('ou_id'));
	$ilDB->createSequence('org_unit_data');
	$root_unit_id = $ilDB->nextId('org_unit_data');
	$ilDB->insert('org_unit_data', array(
		'ou_id' => array('integer', $root_unit_id),
		'ou_title' => array('text', 'RootUnit')
	));
}

if(!$ilDB->tableExists('org_unit_tree'))
{
	$ilDB->createTable('org_unit_tree', array(
			'tree' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'child' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'parent' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'lft' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'rgt' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'depth' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			)
	));
	$ilDB->addPrimaryKey('org_unit_tree', array('tree', 'child'));
	$ilDB->insert('org_unit_tree', array(
		'tree' => array('integer', 1),
		'child' => array('integer', $root_unit_id),
		'parent' => array('integer', 0),
		'lft' => array('integer', 1),
		'rgt' => array('integer', 2),
		'depth' => array('integer', 1)
	));
}

if(!$ilDB->tableExists('org_unit_assignments'))
{
	$ilDB->createTable('org_unit_assignments', array(
			'oa_ou_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_usr_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_reporting_access' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_cc_compl_invit' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_cc_compl_not1' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_cc_compl_not2' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			)
	));
	$ilDB->addPrimaryKey('org_unit_assignments', array('oa_ou_id', 'oa_usr_id'));
}

?>

<#3046>
<?php
	$ilDB->modifyTableColumn('glossary_definition', 'short_text',
		array("type" => "text", "length" => 4000, "notnull" => false));
?>

<#3047>
<?php
$ilDB->addTableColumn('glossary', 'pres_mode', array(
	"type" => "text",
	"notnull" => true,
	"length" => 10,
	"default" => "table"));
?>
<#3048>
<?php
$ilDB->addTableColumn('glossary', 'snippet_length', array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 200));
?>

<#3049>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3050>
<?php
$ilDB->addTableColumn("glossary_definition", "short_text_dirty", array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0
	));
?>

<#3051>
<?php
if(!$ilDB->tableExists('table_templates'))
{
	$ilDB->createTable('table_templates', array(
			'name' => array(
				'type'     => 'text',
				'length'   => 64,
				'notnull' => true
			),
			'user_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true
			),
			'context' => array(
				'type'     => 'text',
				'length'   => 128,
				'notnull' => true
			),
			'value' => array(
				'type'     => 'clob'
			)
	));
	$ilDB->addPrimaryKey('table_templates', array('name', 'user_id', 'context'));
}
?>

<#3052>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3053>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3054>
<?php
$ilDB->addTableColumn('frm_settings', 'notification_type', array(
	"type" => "text",
	"notnull" => true,
	"length" => 10,
	"default" => "all_users"));
?>
<#3055>
<?php
	$ilDB->addTableColumn("udf_definition", "visible_lua", array(
		"type" => "integer",
		"length" => 1,
		"notnull" => true,
		"default" => 0
	));
	$ilDB->addTableColumn("udf_definition", "changeable_lua", array(
		"type" => "integer",
		"length" => 1,
		"notnull" => true,
		"default" => 0
	));
?>
<#3056>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3057>
<?php
	$ilDB->addTableColumn("benchmark", "sql_stmt", array(
		"type" => "clob"
	));
?>
<#3058>
<?php
	$ilDB->dropTable("ut_lp_filter", false);
?>
<#3059>
<?php
	$ilDB->modifyTableColumn("exc_mem_ass_status",
		"u_comment",
		array("type" => "text", "length" => 4000, "notnull" => false));
?>
<#3060>
<?php
	// mail attachments
	$ilDB->addTableColumn("mail", "attachments_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET attachments_tmp = attachments');
	$ilDB->dropTableColumn('mail', 'attachments');
	$ilDB->renameTableColumn("mail", "attachments_tmp", "attachments");
?>
<#3061>
<?php
	// mail_saved attachments
	$ilDB->addTableColumn("mail_saved", "attachments_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET attachments_tmp = attachments');
	$ilDB->dropTableColumn('mail_saved', 'attachments');
	$ilDB->renameTableColumn("mail_saved", "attachments_tmp", "attachments");
?>
<#3062>
<?php
	$ilDB->addTableColumn(
		'usr_search',
		'item_filter',
		array(
			'type' => 'text',
			'length' => 1000,
			'notnull' => false,
			'default'	=> NULL
		)
	);
?>
<#3063>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// test sequence
		if (!$ilDB->tableColumnExists('tst_sequence', 'sequence_tmp'))
		{
			$ilDB->addTableColumn("tst_sequence", "sequence_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3064>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE tst_sequence SET sequence_tmp = sequence');
	}
?>
<#3065>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('tst_sequence', 'sequence'))
			$ilDB->dropTableColumn('tst_sequence', 'sequence');

		$ilDB->renameTableColumn("tst_sequence", "sequence_tmp", "sequence");
	}
?>
<#3066>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		$ilDB->modifyTableColumn('tst_sequence','sequence', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3067>
<?php
	$ilDB->modifyTableColumn('ut_lp_marks','u_comment',array('type' => 'text', 'default' => null, 'length' => 4000, 'notnull' => false));
?>
<#3068>
<?php
	$ilDB->addTableColumn('udf_definition','group_export',array('type' => 'integer','default' => 0,'length' => 1, 'notnull' => false));
?>
<#3069>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3070>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 200)),
		array('operation'=>array('text','mail_visible')));
?>
<#3071>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 210)),
		array('operation'=>array('text','smtp_mail')));
?>
<#3072>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 220)),
		array('operation'=>array('text','system_message')));
?>
<#3073>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 230)),
		array('operation'=>array('text','mail_to_global_roles')));
?>
<#3074>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3075>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3076>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3077>
<?php
if(!$ilDB->tableExists('notification'))
{
	$ilDB->createTable('notification', array(
			'type' => array(
				'type'     => 'integer',
				'length'   => 1,
				'notnull' => true
			),
			'id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true
			),
			'user_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true
			),
			'last_mail' => array(
				'type'     => 'timestamp',
				'notnull'	=> false
			)
	));
	$ilDB->addPrimaryKey('notification', array('type', 'id', 'user_id'));
}
?>
<#3078>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3079>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3080>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3081>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3082>
<?php
	$ilDB->createTable('cal_rec_exclusion',array(
		'excl_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'cal_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'excl_date'	=> array(
			'type'	=> 'date',
			'notnull' => FALSE,
		)
	));
	
	$ilDB->addPrimaryKey('cal_rec_exclusion',array('excl_id'));
	$ilDB->addIndex('cal_rec_exclusion',array('cal_id'),'i1');
	$ilDB->createSequence('cal_rec_exclusion');
?>
<#3083>
<?php

// new permission
$new_ops_id = $ilDB->nextId('rbac_operations');
$query = "INSERT INTO rbac_operations (operation,description,class,op_order) ".
	"VALUES( ".
	$ilDB->quote('add_consultation_hours','text').', '.
	$ilDB->quote('Add Consultation Hours Calendar','text').", ".
	$ilDB->quote('object','text').", ".
	$ilDB->quote(300,'integer').
	")";
$res = $ilDB->query($query);

// Calendar settings
$query = "SELECT obj_id FROM object_data WHERE type = 'typ' AND title = 'cals' ";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$cals = $row[0];



$ilDB->manipulateF(
	'INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
	array('integer','integer'), 
	array($cals, $new_ops_id));

?>
<#3084>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3085>
<?php
	$ilDB->createTable('rbac_log',array(
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'created'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'ref_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'action'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE
		),
		'data'	=> array(
			'type'	=> 'text',
			'length' => 4000
		)
	));
	$ilDB->addIndex('rbac_log',array('ref_id'),'i1');
?>
<#3086>
<?php
	$ilDB->createTable('booking_entry',array(
		'booking_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 256,
			'notnull' => FALSE
		),
		'description'	=> array(
			'type'	=> 'text',
			'length'=> 4000,
			'notnull' => FALSE
		),
		'location'	=> array(
			'type'	=> 'text',
			'length' => 512,
			'notnull' => FALSE
		),
		'deadline'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => TRUE
		),
		'num_bookings'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => TRUE
		)
	));
	$ilDB->addPrimaryKey('booking_entry',array('booking_id'));
	$ilDB->createSequence('booking_entry');
?>
<#3087>
<?php
	$ilCtrlStructureReader->getStructure();
?>
?>
<#3088>
<?php
	$ilDB->createTable('page_qst_answer',array(
		'qst_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'try'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE
		),
		'passed'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE
		),
		'points'	=> array(
			'type'	=> 'float',
			'notnull' => TRUE
		)
	));
	$ilDB->addPrimaryKey('page_qst_answer', array('qst_id', 'user_id'));

?>
<#3089>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3090>
<?php
if($ilDB->tableColumnExists('booking_entry','title'))
{
	$ilDB->dropTableColumn('booking_entry', 'title');
}
if($ilDB->tableColumnExists('booking_entry','description'))
{
	$ilDB->dropTableColumn('booking_entry', 'description');
}
if($ilDB->tableColumnExists('booking_entry','location'))
{
	$ilDB->dropTableColumn('booking_entry', 'location');
}
?>
<#3091>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3092>
<?php
$fields = array (
    'id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
    'user_id'   => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
	'order_nr'   => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
    'title'    => array ('type' => 'text', 'length' => 200)
);

$ilDB->createTable('usr_ext_profile_page', $fields);
$ilDB->addPrimaryKey('usr_ext_profile_page', array('id'));
$ilDB->createSequence("usr_ext_profile_page");

?>
<#3093>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3094>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3095>
<?php
if(!$ilDB->tableExists('booking_user'))
{
	$ilDB->createTable('booking_user',array(
		'entry_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'tstamp'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_user', array('entry_id', 'user_id'));
}
?>
<#3096>
<?php
	if(!$ilDB->tableColumnExists('crs_settings','reg_ac_enabled'))
	{
		
		$ilDB->addTableColumn('crs_settings','reg_ac_enabled',array(
			'type'		=> 'integer',
			'notnull'	=> true,
			'length'	=> 1,
			'default'	=> 0
		));
	
		$ilDB->addTableColumn('crs_settings','reg_ac',array(
			'type'		=> 'text',
			'notnull'	=> false,
			'length'	=> 32
		));
	}
?>

<#3097>
<?php
	if(!$ilDB->tableColumnExists('grp_settings','reg_ac_enabled'))
	{
		$ilDB->addTableColumn('grp_settings','reg_ac_enabled',array(
			'type'		=> 'integer',
			'notnull'	=> true,
			'length'	=> 1,
			'default'	=> 0
		));
	
		$ilDB->addTableColumn('grp_settings','reg_ac',array(
			'type'		=> 'text',
			'notnull'	=> false,
			'length'	=> 32
		));
	}
?>
<#3098>
<?php
	if($ilDB->tableColumnExists("frm_settings", "new_post_title"))
	{
		$ilDB->renameTableColumn('frm_settings', 'new_post_title', 'preset_subject');
	}
?>
<#3099>
<?php
		$ilDB->addTableColumn("frm_settings", "add_re_subject", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3100>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3101>
<?php
	$ilDB->addTableColumn('il_object_def','export', array(
		'type'	=> 'integer',
		'notnull' => true,
		'length' => 1,
		'default' => 0
	));
?>
<#3102>
<?php
if(!$ilDB->tableExists('booking_type'))
{
	$ilDB->createTable('booking_type',array(
		'booking_type_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 255,
			'notnull' => true
		),
		'pool_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_type', array('booking_type_id'));
	$ilDB->createSequence('booking_type');
}
?>
<#3103>
<?php
if(!$ilDB->tableExists('export_file_info'))
{
	$ilDB->createTable('export_file_info',array(
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'export_type'	=> array(
			'type'	=> 'text',
			'length'=> 32,
			'notnull' => false
		),
		'file_name'	=> array(
			'type'	=> 'text',
			'length'=> 64,
			'notnull' => false
		),
		'version'	=> array(
			'type'	=> 'text',
			'length'=> 16,
			'notnull' => false
		),
		'create_date'	=> array(
			'type'	=> 'timestamp',
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('export_file_info', array('obj_id','export_type','file_name'));
	$ilDB->addIndex('export_file_info',array('create_date'),'i1');
}
?>
<#3104>
<?php
if(!$ilDB->tableExists('export_options'))
{
	$ilDB->createTable('export_options',array(
		'export_id'	=> array(
			'type'	=> 'integer',
			'length'=> 2,
			'notnull' => true
		),
		'keyword'	=> array(
			'type'	=> 'integer',
			'length'=> 2,
			'notnull' => true
		),
		'ref_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'value'	=> array(
			'type'	=> 'text',
			'length' => 32,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('export_options', array('export_id','keyword','ref_id'));
}
?>
<#3105>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3106>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3107>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_mc','use_other_answer'))
	{
	  $ilDB->addTableColumn("svy_qst_mc", "use_other_answer", array("type" => "integer", "length" => 2, "notnull" => false));
	  $ilDB->addTableColumn("svy_qst_mc", "other_answer_label", array("type" => "text", "length" => 255, "notnull" => false, "default" => null));
	}
?>
<#3108>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_sc','use_other_answer'))
	{
	  $ilDB->addTableColumn("svy_qst_sc", "use_other_answer", array("type" => "integer", "length" => 2, "notnull" => false));
	  $ilDB->addTableColumn("svy_qst_sc", "other_answer_label", array("type" => "text", "length" => 255, "notnull" => false, "default" => null));
	}
?>
<#3109>
<?php
	if(!$ilDB->tableColumnExists('svy_category','scale'))
	{
	  $ilDB->addTableColumn("svy_category", "scale", array("type" => "integer", "length" => 4, "notnull" => false, "default" => null));
	}
?>
<#3110>
<?php
	if(!$ilDB->tableColumnExists('svy_category','other'))
	{
	  $ilDB->addTableColumn("svy_category", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
	}
?>
<#3111>
<?php
	if($ilDB->tableColumnExists('svy_qst_mc','other_answer_label'))
	{
		$ilDB->dropTableColumn('svy_qst_mc', 'other_answer_label');
	}
?>
<#3112>
<?php
	if($ilDB->tableColumnExists('svy_qst_sc','other_answer_label'))
	{
		$ilDB->dropTableColumn('svy_qst_sc', 'other_answer_label');
	}
?>
<#3113>
<?php
	$ilDB->addIndex('svy_category',array('other'),'i2');
?>
<#3114>
<?php
	$ilDB->dropIndex('svy_category', 'i2');
?>
<#3115>
<?php
	if($ilDB->tableColumnExists('svy_category','scale'))
	{
		$ilDB->dropTableColumn('svy_category', 'scale');
	}
?>
<#3116>
<?php
	if($ilDB->tableColumnExists('svy_category','other'))
	{
		$ilDB->dropTableColumn('svy_category', 'other');
	}
?>
<#3117>
<?php
	if(!$ilDB->tableColumnExists('svy_variable','other'))
	{
	  $ilDB->addTableColumn("svy_variable", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
	}
?>
<#3118>
<?php
	if($ilDB->tableColumnExists('svy_qst_mc','use_other_answer'))
	{
		$ilDB->dropTableColumn('svy_qst_mc', 'use_other_answer');
	}
?>
<#3119>
<?php
	if($ilDB->tableColumnExists('svy_qst_sc','use_other_answer'))
	{
		$ilDB->dropTableColumn('svy_qst_sc', 'use_other_answer');
	}
?>
<#3120>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_mc','use_min_answers'))
	{
		$ilDB->addTableColumn("svy_qst_mc", "use_min_answers", array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
		$ilDB->addTableColumn("svy_qst_mc", "nr_min_answers", array("type" => "integer", "length" => 2, "notnull" => false));
	}
?>
<#3121>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_matrixrows','other'))
	{
		$ilDB->addTableColumn("svy_qst_matrixrows", "other", array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
	}
?>
<#3122>
<?php
	if(!$ilDB->tableColumnExists('svy_question','label'))
	{
		$ilDB->addTableColumn("svy_question", "label", array("type" => "text", "length" => 255, "notnull" => false));
	}
?>
<#3123>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_matrixrows','label'))
	{
		$ilDB->addTableColumn("svy_qst_matrixrows", "label", array("type" => "text", "length" => 255, "notnull" => false));
	}
?>
<#3124>
<?php
	if(!$ilDB->tableColumnExists('svy_variable','scale'))
	{
		$ilDB->addTableColumn("svy_variable", "scale", array("type" => "integer", "length" => 3, "notnull" => false));
	}
?>
<#3125>
<?php
	if(!$ilDB->tableColumnExists('svy_svy','mailnotification'))
	{
		$ilDB->addTableColumn("svy_svy", "mailnotification", array("type" => "integer", "length" => 1, "notnull" => false));
	}
	if(!$ilDB->tableColumnExists('svy_svy','mailaddresses'))
	{
		$ilDB->addTableColumn("svy_svy", "mailaddresses", array("type" => "text", "length" => 2000, "notnull" => false));
	}
	if(!$ilDB->tableColumnExists('svy_svy','mailparticipantdata'))
	{
		$ilDB->addTableColumn("svy_svy", "mailparticipantdata", array("type" => "text", "length" => 4000, "notnull" => false));
	}
?>
<#3126>
<?php
	if(!$ilDB->tableColumnExists('svy_anonymous','externaldata'))
	{
		$ilDB->addTableColumn("svy_anonymous", "externaldata", array("type" => "text", "length" => 4000, "notnull" => false));
	}
?>
<#3127>
<?php
	if(!$ilDB->tableColumnExists('svy_constraint','conjunction'))
	{
		$ilDB->addTableColumn("svy_constraint", "conjunction", array("type" => "integer", "length" => 2, "default" => 0, "notnull" => true));
	}
?>
<#3128>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3129>
<?php
if(!$ilDB->tableExists('booking_object'))
{
	$ilDB->createTable('booking_object',array(
		'booking_object_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 255,
			'notnull' => true
		),
		'type_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_object', array('booking_object_id'));
	$ilDB->createSequence('booking_object');
}
?>

<#3130>
<?php
	@rename(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilFiles',CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilFile');
?>
<#3131>
<?php
	@rename(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilEvents',CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilSession');
?>

<#3132>
<?php
function dirRek($dir)
{
	$dp = @opendir($dir);
	while($file = @readdir($dp))
	{
		if($file == '.' or $file == '..')
		{
			continue;
		}
		if(substr($file,0,7) == 'course_')
		{
			$parts = explode('_',$file);
			@rename($dir.$file,$dir.'crs_'.$parts[1]);
			continue;
		}
		if(is_dir($dir.$file))
		{
			dirRek($dir.$file.DIRECTORY_SEPARATOR);
		}
	}
	@closedir($dp);
}

$dir = CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilCourses'.DIRECTORY_SEPARATOR;
dirRek($dir);
?>
<#3133>
<?php
	@rename(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilCourses',CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilCourse');
?>

<#3134>
<?php
	$set = $ilDB->query("SELECT obj_id FROM bookmark_data WHERE ".
		" obj_id = ".$ilDB->quote(1, "integer"));
	$rec = $ilDB->fetchAssoc($set);
	if ($rec["obj_id"] != 1)
	{
		$ilDB->manipulate("INSERT INTO bookmark_data ".
			"(obj_id, user_id, title, description, target, type) VALUES (".
			$ilDB->quote(1, "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote("dummy_folder", "text").",".
			$ilDB->quote("", "text").",".
			$ilDB->quote("", "text").",".
			$ilDB->quote("bmf", "text").
			")");
	}
?>

<#3135>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3136>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 101)
	{
		$set = $ilDB->query("SELECT * FROM object_data WHERE type = 'sty'");
		while ($rec = $ilDB->fetchAssoc($set))	// all styles
		{
			$ast = array(
				array("tag" => "a", "type" => "link", "class" => "FileLink",
					"par" => array(
						array("name" => "text-decoration", "value" => "undeline"),
						array("name" => "font-weight", "value" => "normal"),
						array("name" => "color", "value" => "blue")
						)),
				array("tag" => "div", "type" => "glo_overlay", "class" => "GlossaryOverlay",
					"par" => array(
						array("name" => "background-color", "value" => "#FFFFFF"),
						array("name" => "border-color", "value" => "#A0A0A0"),
						array("name" => "border-style", "value" => "solid"),
						array("name" => "border-width", "value" => "2px"),
						array("name" => "padding-top", "value" => "5px"),
						array("name" => "padding-bottom", "value" => "5px"),
						array("name" => "padding-left", "value" => "5px"),
						array("name" => "padding-right", "value" => "5px")
						))
				);

			foreach($ast as $st)
			{
				$set2 = $ilDB->query("SELECT * FROM style_char WHERE ".
					"style_id = ".$ilDB->quote($rec["obj_id"], "integer")." AND ".
					"characteristic = ".$ilDB->quote($st["class"], "text")." AND ".
					"type = ".$ilDB->quote($st["type"], "text"));
				if (!$ilDB->fetchAssoc($set2))
				{
					$q = "INSERT INTO style_char (style_id, type, characteristic, hide)".
						" VALUES (".
						$ilDB->quote($rec["obj_id"], "integer").",".
						$ilDB->quote($st["type"], "text").",".
						$ilDB->quote($st["class"], "text").",".
						$ilDB->quote(0, "integer").")";
//echo "<br>-$q-";
					$ilDB->manipulate($q);
					foreach ($st["par"] as $par)
					{
						$spid = $ilDB->nextId("style_parameter");
						$q = "INSERT INTO style_parameter (id, style_id, type, class, tag, parameter, value)".
							" VALUES (".
							$ilDB->quote($spid, "integer").",".
							$ilDB->quote($rec["obj_id"], "integer").",".
							$ilDB->quote($st["type"], "text").",".
							$ilDB->quote($st["class"], "text").",".
							$ilDB->quote($st["tag"], "text").",".
							$ilDB->quote($par["name"], "text").",".
							$ilDB->quote($par["value"], "text").
							")";
//echo "<br>-$q-";
					$ilDB->manipulate($q);
					}
				}
			}
		}
	}

?>

<#3137>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 102)
	{
		include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
		ilDBUpdate3136::copyStyleClass("IntLink", "GlossaryLink", "link", "a");
	}

?>

<#3138>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 103)
	{
		include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
		ilDBUpdate3136::addStyleClass("GlossaryOvTitle", "glo_ovtitle", "h1",
					array("font-size" => "120%",
						  "margin-bottom" => "10px",
						  "margin-top" => "10px",
						  "font-weight" => "normal"
						  ));
	}
?>

<#3139>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 104)
	{
		include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
		ilDBUpdate3136::addStyleClass("GlossaryOvCloseLink", "glo_ovclink", "a",
					array("text-decoration" => "underline",
						  "font-weight" => "normal",
						  "color" => "blue"
						  ));
		ilDBUpdate3136::addStyleClass("GlossaryOvUnitGloLink", "glo_ovuglink", "a",
					array("text-decoration" => "underline",
						  "font-weight" => "normal",
						  "color" => "blue"
						  ));
		ilDBUpdate3136::addStyleClass("GlossaryOvUGListLink", "glo_ovuglistlink", "a",
					array("text-decoration" => "underline",
						  "font-weight" => "normal",
						  "color" => "blue"
						  ));

	}

?>
<#3140>
<?php
if(!$ilDB->tableExists('booking_schedule'))
{
	$ilDB->createTable('booking_schedule',array(
		'booking_schedule_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 255,
			'notnull' => true
		),
		'pool_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'deadline'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'rent_min'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'rent_max'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'raster'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'auto_break'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'definition'	=> array(
			'type'	=> 'text',
			'length' => 500,
			'notnull' => true,
			'fixed' => false
		)
	));
	$ilDB->addPrimaryKey('booking_schedule', array('booking_schedule_id'));
	$ilDB->createSequence('booking_schedule');
}
?>
<#3141>
<?php

	$ilDB->addTableColumn("usr_data", "sel_country", array(
		"type" => "text",
		"notnull" => false,
		"default" => "",
		"length" => 2
		));

?>
<#3142>
<?php

	$ilDB->addTableColumn("booking_type", "schedule_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
		));

?>
<#3143>
<?php

	$ilDB->addTableColumn("booking_object", "schedule_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
		));

?>


<#3144>
<?php

$id = $ilDB->nextId('object_data');

// register new object type 'book' for booking manager
$query = "INSERT INTO object_data (obj_id,type, title, description, owner, create_date, last_update) ".
		"VALUES (".$ilDB->quote($id, 'integer').",".$ilDB->quote('typ', 'text').
		", ".$ilDB->quote('book', 'text').", ".$ilDB->quote('Booking Manager', 'text').
		", ".$ilDB->quote(-1, 'integer').", ".$ilDB->now().", ".$ilDB->now().")";
$this->db->query($query);

$query = "SELECT obj_id FROM object_data WHERE type = ".$ilDB->quote('typ', 'text').
	" AND title = ".$ilDB->quote('book', 'text');
$res = $this->db->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

// add rbac operations for booking object
// 1: edit_permissions, 2: visible, 3: read, 4:write
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(1, 'integer').")";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(2, 'integer').")";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(3, 'integer').")";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(4, 'integer').")";
$this->db->query($query);
?>

<#3145>
<?php
	$setting = new ilSetting();
	$setting->set("usr_settings_hide_sel_country", 1);
	$setting->set("usr_settings_disable_sel_country", 1);
	$setting->set("usr_settings_visib_reg_sel_country" ,0);
	$setting->set("usr_settings_visib_lua_sel_country" ,0);
	$setting->set("usr_settings_changeable_lua_sel_country" ,0);
?>

<#3146>
<?php
if(!$ilDB->tableExists('cal_registrations'))
{
	$ilDB->createTable('cal_registrations',array(
		'cal_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'usr_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('cal_registrations', array('cal_id', 'usr_id'));
}
?>

<#3147>
<?php
if(!$ilDB->tableExists('booking_reservation'))
{
	$ilDB->createTable('booking_reservation',array(
		'booking_reservation_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'object_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'date_from'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'date_to'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'status'	=> array(
			'type'	=> 'integer',
			'length'=> 2,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('booking_reservation', array('booking_reservation_id'));
	$ilDB->createSequence('booking_reservation');
}
?>
<#3148>
<?php
	$ilDB->addTableColumn("cal_registrations", "dstart", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 4
		));
	
	$ilDB->addTableColumn("cal_registrations", "dend", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 4
		));
		
	$ilDB->dropPrimaryKey('cal_registrations');
	$ilDB->addPrimaryKey('cal_registrations', array('cal_id', 'usr_id','dstart','dend'));
?>
<#3149>
<?php
	$ilDB->addTableColumn("svy_anonymous", "sent", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 2
		));
?>
<#3150>
<?php
	$ilDB->addIndex('svy_anonymous',array('sent'),'i3');
?>

<#3151>
<?php
if(!$ilDB->tableExists('booking_settings'))
{
	$ilDB->createTable('booking_settings',array(
		'booking_pool_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'public_log'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		),
		'pool_offline'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('booking_settings', array('booking_pool_id'));
}
?>
<#3152>
<?php
	$ilDB->addTableColumn("qpl_qst_essay", "matchcondition", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 2
		));
?>

<#3153>
<?php
	$ilDB->addTableColumn("booking_settings", "slots_no", array(
		"type" => "integer",
		"notnull" => false,
		"default" => 0,
		"length" => 2
		));
?>
<#3154>
<?php
	
	$permission_ordering = array(
		'visible'		=> 1000,
		'join'			=> 1200,
		'leave'			=> 1400,
		'read'			=> 2000,
		'edit_content'	=> 3000,
		'add_thread'	=> 3100,
		'edit_event'	=> 3600,
		'moderate'		=> 3700,
		'moderate_frm'	=> 3750,
		'edit_learning_progress' => 3600,
		'copy'			=> 4000,
		'write'			=> 6000,
		'read_users'	=> 7000,
		'cat_administrate_users' => 7050,
		'invite'			=> 7200,
		'tst_statistics'	=> 7100, 
		'delete'		=> 8000,
		'edit_permission' => 9000
	);
	
	foreach($permission_ordering as $op => $order)
	{
		$query = "UPDATE rbac_operations SET ".
			'op_order = '.$ilDB->quote($order,'integer').' '.
			'WHERE operation = '.$ilDB->quote($op,'text').' ';
		$ilDB->manipulate($query);
	}
?>

<#3155>
<?php
	if($ilDB->tableColumnExists('svy_svy','mailaddresses'))
	{
		$ilDB->dropTableColumn('svy_svy', 'mailaddresses');
	}
	if($ilDB->tableColumnExists('svy_svy','mailparticipantdata'))
	{
		$ilDB->dropTableColumn('svy_svy', 'mailparticipantdata');
	}
?>
<#3156>
<?php
	if(!$ilDB->tableColumnExists('svy_svy','mailaddresses'))
	{
		$ilDB->addTableColumn("svy_svy", "mailaddresses", array("type" => "text", "length" => 2000, "notnull" => false));
		$ilDB->addTableColumn("svy_svy", "mailparticipantdata", array("type" => "text", "length" => 4000, "notnull" => false));
	}
?>

<#3157>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_mc','nr_max_answers'))
	{
		$ilDB->addTableColumn("svy_qst_mc", "nr_max_answers", array("type" => "integer", "length" => 2, "notnull" => false));
	}
?>
<#3158>
<?php
if(!$ilDB->tableExists('svy_times'))
{
	$ilDB->createTable('svy_times',array(
		'finished_fi'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'entered_page'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'left_page'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		)
	));
	$ilDB->addIndex('svy_times',array('finished_fi'),'i1');
}
?>

<#3159>
<?php
	$query = 'UPDATE rbac_operations SET op_order = '.$ilDB->quote(9999,'integer').' WHERE class = '.$ilDB->quote('create','text');
	$ilDB->manipulate($query);
?>

<#3160>
<?php

// add create operation for booking pools

$ops_id = $ilDB->nextId('rbac_operations');

$query = 'INSERT INTO rbac_operations (ops_id, operation, class, description, op_order)'.
	' VALUES ('.$ilDB->quote($ops_id,'integer').','.$ilDB->quote('create_book','text').
	','.$ilDB->quote('create','text').','.$ilDB->quote('create booking pool','text').
	','.$ilDB->quote(9999,'integer').')';
$ilDB->query($query);

// add create booking pool for root,crs,cat,fold and grp
foreach(array('cat', 'crs', 'grp', 'fold', 'root') as $type)
{
	$query = 'SELECT obj_id FROM object_data WHERE type='.$ilDB->quote('typ','text').
		' AND title='.$ilDB->quote($type,'text');
	$res = $ilDB->query($query);
	$row = $ilDB->fetchAssoc($res);
	$typ_id = $row['obj_id'];

	$query = 'INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('.$ilDB->quote($typ_id,'integer').
		','.$ilDB->quote($ops_id,'integer').')';
	$ilDB->query($query);
}

?>
<#3161>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3162>
<?php
	$ilDB->manipulate("UPDATE ut_lp_marks SET ".
		" status_dirty = ".$ilDB->quote(1, "integer")
		);
?>
<#3163>
<?php
	$ilDB->addIndex("conditions", array("target_obj_id", "target_type"), "tot");
?>
<#3164>
<?php
if($ilDB->tableExists('svy_times'))
{
	$ilDB->addTableColumn("svy_times", "first_question", array("type" => "integer", "length" => 4, "notnull" => false));
}
?>
<#3165>
<?php
if(!$ilDB->tableExists('svy_settings'))
{
	$ilDB->createTable('svy_settings',array(
		'settings_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'usr_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'keyword'	=> array(
			'type'	=> 'text',
			'length'=> 40,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 400,
			'notnull' => false
		),
		'value'	=> array(
			'type'	=> 'clob',
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('svy_settings', array('settings_id'));
	$ilDB->addIndex('svy_settings',array('usr_id'),'i1');
}
?>
<#3166>
<?php
	$ilDB->createSequence('svy_settings');
?>
<#3167>
<?php
if(!$ilDB->tableExists('cal_ch_settings'))
{
	$ilDB->createTable('cal_ch_settings',array(
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'admin_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
	));
	$ilDB->addPrimaryKey('cal_ch_settings', array('user_id', 'admin_id'));
}
?>
<#3168>
<?php
	if(!$ilDB->tableColumnExists('booking_entry','target_obj_id'))
	{
		$ilDB->addTableColumn("booking_entry", "target_obj_id", array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#3169>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3170>
<?php
	
	$permission_ordering = array(
		'add_post'		=> 3050,
		'edit_roleassignment' => 2500,
		'push_desktop_items'			=> 2400,
		'search'			=> 300,
		'export_memberdata'			=> 400,
		'edit_userasignment'	=> 2600,
	);
	
	foreach($permission_ordering as $op => $order)
	{
		$query = "UPDATE rbac_operations SET ".
			'op_order = '.$ilDB->quote($order,'integer').' '.
			'WHERE operation = '.$ilDB->quote($op,'text').' ';
		$ilDB->manipulate($query);
	}
?>
<#3171>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3172>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// test sequence
		if (!$ilDB->tableColumnExists('svy_category', 'title_tmp'))
		{
			$ilDB->addTableColumn("svy_category", "title_tmp", array(
			"type" => "text",
			"length" => 1000,
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3173>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE svy_category SET title_tmp = title');
	}
?>
<#3174>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('svy_category', 'title'))
			$ilDB->dropTableColumn('svy_category', 'title');

		$ilDB->renameTableColumn("svy_category", "title_tmp", "title");
	}
?>
<#3175>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		$ilDB->modifyTableColumn('svy_category','title', array("type" => "text", "length" => 1000, "default" => null, "notnull" => false));
	}
?>
<#3176>
<?php
if (!$ilDB->tableColumnExists('qpl_qst_ordering', 'scoring_type'))
{
	$ilDB->addTableColumn("qpl_qst_ordering", "scoring_type", array(
		"type" => "integer",
		"length" => 3,
		"notnull" => true,
		"default" => 0)
	);
	$ilDB->addTableColumn("qpl_qst_ordering", "reduced_points", array(
		"type" => "float",
		"notnull" => true,
		"default" => 0)
	);
}
?>
<#3177>
<?php
if (!$ilDB->tableColumnExists('tst_tests', 'mailnottype'))
{
	$ilDB->addTableColumn("tst_tests", "mailnottype", array(
		"type" => "integer",
		"length" => 2,
		"notnull" => true,
		"default" => 0)
	);
}
?>
<#3178>
<?php

	$query = "UPDATE crs_settings SET view_mode = 0 WHERE view_mode = 3";
	$ilDB->manipulate($query);
?>
<#3179>
<?php

// copy permission id
$query = "SELECT * FROM rbac_operations WHERE operation = ".$ilDB->quote('copy','text');
$res = $ilDB->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
$ops_id = $row->ops_id;

$all_types = array('spl','qpl');
foreach($all_types as $type)
{
	$query = "SELECT obj_id FROM object_data WHERE type = 'typ' AND title = ".$ilDB->quote($type,'text');
	$res = $ilDB->query($query);
	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

	$query = "INSERT INTO rbac_ta (typ_id,ops_id) ".
		"VALUES( ".
		$ilDB->quote($row->obj_id,'integer').', '.
		$ilDB->quote($ops_id,'integer').' '.
		')';
	$ilDB->manipulate($query);
}
?>
<#3180>
<?php

// Calendar settings
$query = 'SELECT obj_id FROM object_data WHERE type = '.$ilDB->quote('typ','text').
	' AND title = '.$ilDB->quote('cals','text');
$res = $ilDB->query($query);
$row = $res->fetchRow();
$cals = $row[0];

$insert = false;
$query = 'SELECT ops_id FROM rbac_operations WHERE operation = '.$ilDB->quote('add_consultation_hours','text');
$res = $ilDB->query($query);
if($ilDB->numRows($res))
{
	$row = $res->fetchRow();
	$ops_id = (int)$row[0];

	// remove old (faulty) ops [see #3083]
	if($ops_id === 0)
	{
		$query = 'DELETE FROM rbac_operations WHERE operation = '.$ilDB->quote('add_consultation_hours','text');
		$ilDB->query($query);
		$query = 'DELETE FROM rbac_ta  WHERE ops_id = '.$ilDB->quote(0,'integer').
			' AND typ_id = '.$ilDB->quote($cals,'integer');
		$ilDB->query($query);

		$insert = true;
	}
}
else
{
	$insert = true;
}

if($insert)
{
	// new permission
	$new_ops_id = $ilDB->nextId('rbac_operations');
	$query = 'INSERT INTO rbac_operations (ops_id,operation,description,class,op_order) '.
		'VALUES( '.
		$ilDB->quote($new_ops_id,'integer').', '.
		$ilDB->quote('add_consultation_hours','text').', '.
		$ilDB->quote('Add Consultation Hours Calendar','text').", ".
		$ilDB->quote('object','text').", ".
		$ilDB->quote(300,'integer').
		')';
	$res = $ilDB->query($query);

	$ilDB->manipulateF(
		'INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
		array('integer','integer'),
		array($cals, $new_ops_id));
}

?>
<#3181>
<?php
if (!$ilDB->tableColumnExists('svy_finished', 'lastpage'))
{
	$ilDB->addTableColumn("svy_finished", "lastpage", array(
		"type" => "integer",
		"length" => 4,
		"notnull" => true,
		"default" => 0)
	);
}
?>
<#3182>
<?php
if (!$ilDB->tableColumnExists('svy_phrase_cat', 'other'))
{
  $ilDB->addTableColumn("svy_phrase_cat", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
}
?>
<#3183>
<?php
if (!$ilDB->tableColumnExists('svy_phrase_cat', 'scale'))
{
  $ilDB->addTableColumn("svy_phrase_cat", "scale", array("type" => "integer", "length" => 4, "notnull" => false, "default" => null));
}
?>
<#3184>
<?php
if (!$ilDB->tableColumnExists('tst_tests', 'exportsettings'))
{
  $ilDB->addTableColumn("tst_tests", "exportsettings", array("type" => "integer", "length" => 4, "notnull" => true, "default" => 0));
}
?>
<#3185>
<?php
	$query = 'UPDATE rbac_operations SET operation = '.$ilDB->quote('create_usr','text').' WHERE operation = '.$ilDB->quote('create_user','text');
	$ilDB->manipulate($query);
?>
<#3186>
<?php

// create new table
if(!$ilDB->tableExists('booking_schedule_slot'))
{
	$ilDB->createTable('booking_schedule_slot',array(
		'booking_schedule_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'day_id'	=> array(
			'type'	=> 'text',
			'length'=> 2,
			'notnull' => true
		),
		'slot_id'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => true
		),
		'times'	=> array(
			'type'	=> 'text',
			'length'=> 50,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_schedule_slot', array('booking_schedule_id', 'day_id', 'slot_id'));
	
	if($ilDB->tableColumnExists('booking_schedule','definition'))
	{
		// migrate existing schedules
		$set = $ilDB->query('SELECT booking_schedule_id,definition FROM booking_schedule');
		while($row = $ilDB->fetchAssoc($set))
		{
			$definition = @unserialize($row["definition"]);
			if($definition)
			{
				foreach($definition as $day_id => $slots)
				{
					foreach($slots as $slot_id => $times)
					{
						$fields = array(
							"booking_schedule_id" => array('integer', $row["booking_schedule_id"]),
							"day_id" => array('text', $day_id),
							"slot_id" => array('integer', $slot_id),
							"times" => array('text', $times)
							);
						$ilDB->insert('booking_schedule_slot', $fields);
					}
				}
			}
		}

		// remove old column
		$ilDB->dropTableColumn('booking_schedule', 'definition');
	}
}
?>
<#3187>
<?php
if (!$ilDB->tableColumnExists('notification', 'page_id'))
{
  $ilDB->addTableColumn("notification", "page_id", array("type" => "integer", "length" => 4, "notnull" => false, "default" => 0));
}
?>
<#3188>
<?php
	$ilDB->addTableColumn("svy_svy", "startdate_tmp", array(
		"type" => "text",
		"notnull" => false,
		'length'=> 14,
		"default" => null
	));
?>
<#3189>
<?php
$res = $ilDB->query('SELECT survey_id, startdate FROM svy_svy');
while ($row = $ilDB->fetchAssoc($res))
{
	if (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $row['startdate'], $matches))
	{
		$ilDB->manipulateF('UPDATE svy_svy SET startdate_tmp = %s WHERE survey_id = %s',
			array('text', 'integer'),
			array(sprintf("%04d%02d%02d%02d%02d%02d", $matches[1], $matches[2], $matches[3], 0, 0, 0), $row['survey_id'])
		);
	}
}
?>
<#3190>
<?php
$ilDB->dropTableColumn('svy_svy', 'startdate');
?>
<#3191>
<?php
$ilDB->renameTableColumn("svy_svy", "startdate_tmp", "startdate");
?>
<#3192>
<?php
	$ilDB->addTableColumn("svy_svy", "enddate_tmp", array(
		"type" => "text",
		"notnull" => false,
		'length'=> 14,
		"default" => null
	));
?>
<#3193>
<?php
$res = $ilDB->query('SELECT survey_id, enddate FROM svy_svy');
while ($row = $ilDB->fetchAssoc($res))
{
	if (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $row['enddate'], $matches))
	{
		$ilDB->manipulateF('UPDATE svy_svy SET enddate_tmp = %s WHERE survey_id = %s',
			array('text', 'integer'),
			array(sprintf("%04d%02d%02d%02d%02d%02d", $matches[1], $matches[2], $matches[3], 0, 0, 0), $row['survey_id'])
		);
	}
}
?>
<#3194>
<?php
$ilDB->dropTableColumn('svy_svy', 'enddate');
?>
<#3195>
<?php
$ilDB->renameTableColumn("svy_svy", "enddate_tmp", "enddate");
?>

<#3196>
<?php
	$ilDB->addTableColumn("export_file_info", "filename", array(
		"type" => "text",
		"notnull" => false,
		'length'=> 64,
		"default" => null
	));
?>
<#3197>
<?php
	$query = "UPDATE export_file_info SET filename = file_name ";
	$ilDB->manipulate($query);
?>

<#3198>
<?php
	$ilDB->dropPrimaryKey('export_file_info');
?>
<#3199>
<?php
	$ilDB->addPrimaryKey('export_file_info',array('obj_id','export_type','filename'));
?>

<#3200>
<?php
	
	// Invalid assign flags
	$query = "SELECT rol_id FROM rbac_fa ".
		"WHERE assign = ".$ilDB->quote('y','text').' '.
		"GROUP BY rol_id HAVING count(*) > 1";
	$res = $ilDB->query($query);
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$role_id = $row->rol_id;
		
		$query = "SELECT depth, fa.parent parent FROM rbac_fa fa ".
			"JOIN tree t ON fa.parent = child ".
			"WHERE rol_id = ".$ilDB->quote($role_id,'integer').' '.
			"AND assign = ".$ilDB->quote('y','text').' '.
			"ORDER BY depth, fa.parent";
		$assignable_res = $ilDB->query($query);
		$first = true;
		while($assignable_row = $assignable_res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($first)
			{
				$first = false;
				continue;
			}
			// Only for security
			if($assignable_row->parent == ROLE_FOLDER_ID)
			{
				continue;
			}
			$GLOBALS['ilLog']->write(__METHOD__.': Ressetting assignable flag for role_id: '.$role_id.' parent: '.$assignable_row->parent);
			$query = "UPDATE rbac_fa SET assign = ".$ilDB->quote('n','text').' '.
				"WHERE rol_id = ".$ilDB->quote($role_id,'integer').' '.
				"AND parent = ".$ilDB->quote($assignable_row->parent,'integer');
			$ilDB->manipulate($query);
		} 
	}
?>
<#3201>
<?php
if (!$ilDB->tableColumnExists('tst_test_random', 'sequence'))
{
	$ilDB->addTableColumn("tst_test_random", "sequence", array(
	"type" => "integer",
	"length" => 4,
	"notnull" => true,
	"default" => 0));
}
?>
<#3202>
<?php
if ($ilDB->tableColumnExists('tst_test_random', 'sequence'))
{
	$ilDB->manipulate("UPDATE tst_test_random SET sequence = test_random_id");
}
?>
<#3203>
<?php
if (!$ilDB->tableColumnExists('usr_session', 'remote_addr'))
{
	$ilDB->addTableColumn("usr_session", "remote_addr", array(
	"type" => "text",
	"length" => 50,
	"notnull" => false,
	"default" => null));
}
?>
<#3204>
<?php

include_once "Services/Tracking/classes/class.ilLPMarks.php";

$set = $ilDB->query("SELECT event_id,usr_id,mark,e_comment".
	" FROM event_participants".
	" WHERE mark IS NOT NULL OR e_comment IS NOT NULL");
while($row = $ilDB->fetchAssoc($set))
{
	// move to ut_lp_marks

	$fields = array();
	$fields["mark"] = array("text", $row["mark"]);
	$fields["u_comment"] = array("text", $row["e_comment"]);
	// $fields["status_changed"] = array("timestamp", date("Y-m-d H:i:s"));

	$where = array();
	$where["obj_id"] = array("integer", $row["event_id"]);
	$where["usr_id"] = array("integer", $row["usr_id"]);

	$old = $ilDB->query("SELECT obj_id,usr_id".
		" FROM ut_lp_marks".
		" WHERE obj_id = ".$ilDB->quote($row["event_id"]).
		" AND usr_id = ".$ilDB->quote($row["usr_id"]));
	if($ilDB->numRows($old))
	{
		$ilDB->update("ut_lp_marks", $fields, $where);
	}
	else
	{
		$fields = array_merge($fields, $where);
		$ilDB->insert("ut_lp_marks", $fields);
	}

	
	// delete old values
	
	$fields = array();
	$fields["mark"] = array("text", null);
	$fields["e_comment"] = array("text", null);
	
	$where = array();
	$where["event_id"] = array("integer", $row["event_id"]);
	$where["usr_id"] = array("integer", $row["usr_id"]);

	$ilDB->update("event_participants", $fields, $where);
}
?>
<#3205>
<?php
	if(!$ilDB->tableColumnExists('export_options','pos'))
	{
		$ilDB->addTableColumn(
			'export_options',
			'pos',
			array(
				'type' 		=> 'integer', 
				'length' 	=> 4,
				'notnull'	=> true,
				'default'	=> 0
			)
		);
	}
?>

<#3206>
<?php
	$ilDB->modifyTableColumn('ldap_server_settings', 'filter',
		array("type" => "text", "length" => 512, "notnull" => false));
?>
<#3207>
<?php
$ilDB->manipulate
(
	'UPDATE mail_obj_data SET title = '.$ilDB->quote('z_local', 'text').' '
   .'WHERE title != '.$ilDB->quote('z_local', 'text').' AND m_type = '.$ilDB->quote('local', 'text')
); 
?>
<#3208>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTEMenu", "rte_menu", "div",
				array());
?>
<#3209>
<?php
$set = $ilDB->query("SELECT obj_id FROM object_data WHERE type = ".$ilDB->quote("tst", "text"));
while ($r = $ilDB->fetchAssoc($set))
{
	$ilDB->manipulate("UPDATE ut_lp_marks SET ".
		" status_dirty = ".$ilDB->quote(1, "integer").
		" WHERE obj_id = ".$ilDB->quote($r["obj_id"], "integer")
		);
}
?>
<#3210>
<?php
$ilDB->addTableColumn("sahs_lm", "entry_page", array(
	"type" => "integer",
	"notnull" => true,
	"default" => 0,
	"length" => 4
));
?>
<#3211>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3212>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3213>
<?php
// convert old qpl export files
$qpl_export_base = ilUtil::getDataDir()."/qpl_data/";
// quit if import dir not available
if (@is_dir($qpl_export_base) && is_writeable($qpl_export_base))
{
	// open directory
	$h_dir = dir($qpl_export_base);

	// get files and save the in the array
	while ($entry = $h_dir->read())
	{
		if ($entry != "." && $entry != "..")
		{
			if (@is_dir($qpl_export_base . $entry))
			{
				$q_dir = dir($qpl_export_base . $entry);
				while ($q_entry = $q_dir->read())
				{
					if ($q_entry != "." and $q_entry != "..")
					{
						if (@is_dir($qpl_export_base . $entry . '/' . $q_entry) && strcmp($q_entry, 'export') == 0)
						{
							$exp_dir = dir($qpl_export_base . $entry . '/' . $q_entry);
							while ($exp_entry = $exp_dir->read())
							{
								if (@is_file($qpl_export_base . $entry . '/' . $q_entry . '/' . $exp_entry))
								{
									$res = preg_match("/^([0-9]{10}_{2}[0-9]+_{2})(qpl__)*([0-9]+)\.(zip)\$/", $exp_entry, $matches);
									if ($res)
									{
										switch ($matches[4])
										{
											case 'zip':
												if (!@is_dir($qpl_export_base . $entry . '/' .  'export_zip')) ilUtil::makeDir($qpl_export_base . $entry . '/' . 'export_zip');
												@rename($qpl_export_base . $entry . '/' . $q_entry . '/' . $exp_entry, $qpl_export_base . $entry . '/' . 'export_zip' . '/' . $matches[1].'qpl_'.$matches[3].'.zip');
												break;
										}
									}
								}
							}
							$exp_dir->close();
							if (@is_dir($qpl_export_base . $entry . '/' . $q_entry)) ilUtil::delDir($qpl_export_base . $entry . '/' . $q_entry);
						}
					}
				}
				$q_dir->close();
			}
		}
	}
	$h_dir->close();
}
?>
<#3214>
<?php
if($ilDB->tableColumnExists('svy_svy','mailaddresses'))
{
	$ilDB->addTableColumn("svy_svy", "mailaddresses_tmp", array("type" => "text", "length" => 2000, "notnull" => false, "default" => null));
	$ilDB->manipulate('UPDATE svy_svy SET mailaddresses_tmp = mailaddresses');
	$ilDB->dropTableColumn('svy_svy', 'mailaddresses');
	$ilDB->renameTableColumn("svy_svy", "mailaddresses_tmp", "mailaddresses");
}
?>
<#3215>
<?php
if($ilDB->tableColumnExists('svy_svy','mailparticipantdata'))
{
	$ilDB->addTableColumn("svy_svy", "mailparticipantdata_tmp", array("type" => "text", "length" => 4000, "notnull" => false, "default" => null));
	$ilDB->manipulate('UPDATE svy_svy SET mailparticipantdata_tmp = mailparticipantdata');
	$ilDB->dropTableColumn('svy_svy', 'mailparticipantdata');
	$ilDB->renameTableColumn("svy_svy", "mailparticipantdata_tmp", "mailparticipantdata");
}
?>
<#3216>
<?php
$ilDB->addTableColumn("page_layout", "style_id", array(
	"type" => "integer",
	"notnull" => false,
	"default" => 0,
	"length" => 4
));
?>
<#3217>
<?php
$ilDB->addIndex('frm_thread_access', array('access_last'), 'i1');
?>
<#3218>
<?php
	$setting = new ilSetting();

	$old_setting = $setting->get('disable_anonymous_fora');
	$new_setting = $setting->set('enable_anonymous_fora', $old_setting ? false : true);

	$ilDB->manipulateF('DELETE FROM settings WHERE keyword = %s',
			array('text'), array('disable_anonymous_fora'));

?>
<#3219>
<?php
$fields = array(
	'id' => array
	(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'hide_obj_page' => array
	(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);
$ilDB->createTable('sahs_sc13_sco', $fields);
$ilDB->addPrimaryKey("sahs_sc13_sco", array("id"));
	
?>
<#3220>
<?php
$set = $ilDB->query("SELECT * FROM sahs_sc13_tree_node WHERE ".
	" type = ".$ilDB->quote("sco", "text")
	);
while ($rec = $ilDB->fetchAssoc($set))
{
	$ilDB->manipulate("INSERT INTO sahs_sc13_sco ".
		"(id, hide_obj_page) VALUES (".
		$ilDB->quote($rec["obj_id"], "integer").",".
		$ilDB->quote(0, "integer").
		")");
}
?>

<#3221>
<?php
if(!$ilDB->tableExists('tree_workspace'))
{
	$fields = array (
		'tree'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'child'   => array ('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'parent'    => array ('type' => 'integer', 'length'  => 4,"notnull" => true,"default" => 0),
		'lft'  => array ('type' => 'integer', 'length'  => 4,"notnull" => true,"default" => 0),
		'rgt'  => array ('type' => 'integer', 'length'  => 4,"notnull" => true,"default" => 0),
		'depth'  => array ('type' => 'integer', 'length'  => 2,"notnull" => true,"default" => 0)
	  );
  $ilDB->createTable('tree_workspace', $fields);
  $ilDB->addIndex('tree_workspace', array('child'), 'i1');
  $ilDB->addIndex('tree_workspace', array('parent'), 'i2');
  $ilDB->addIndex('tree_workspace', array('tree'), 'i3');
}
?>
<#3222>
<?php
if(!$ilDB->tableExists('object_reference_ws'))
{
	$fields = array (
		'wsp_id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'obj_id'   => array ('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'deleted'    => array ('type' => 'timestamp', 'notnull' => false)
	  );
  $ilDB->createTable('object_reference_ws', $fields);
  $ilDB->addPrimaryKey('object_reference_ws', array('wsp_id'));
  $ilDB->addIndex('object_reference_ws', array('obj_id'), 'i1');
  $ilDB->addIndex('object_reference_ws', array('deleted'), 'i2');
  $ilDB->createSequence('object_reference_ws');
}
?>
<#3223>
<?php
if(!$ilDB->tableColumnExists('il_object_def','repository'))
{
	$ilDB->addTableColumn("il_object_def", "repository",
		array("type" => "integer", "length" => 1, "notnull" => true, "default" => 1));
}
?>
<#3224>
<?php
if(!$ilDB->tableColumnExists('il_object_def','workspace'))
{
	$ilDB->addTableColumn("il_object_def", "workspace",
		array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
}
?>
<#3225>
<?php

		if(!$ilDB->tableColumnExists('ldap_server_settings','authentication'))
		{
			$ilDB->addTableColumn(
				'ldap_server_settings',
				'authentication',
				array(
					'type' => 'integer',
					'length' => '1',
					'notnull' => true,
					'default' => 1
				)
			);
		}
?>

<#3226>
<?php
		if(!$ilDB->tableColumnExists('ldap_server_settings','authentication_type'))
		{
			$ilDB->addTableColumn(
				'ldap_server_settings',
				'authentication_type',
				array(
					'type' => 'integer',
					'length' => '1',
					'notnull' => true,
					'default' => 0
				)
			);
		}
?>
<#3227>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELogo", "rte_menu", "div",
				array("float" => "left"));
?>
<#3228>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELinkBar", "rte_menu", "div",
				array());
?>
<#3229>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELink", "rte_mlink", "a",
				array());
?>
<#3230>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELinkDisabled", "rte_mlink", "a",
				array());
?>
<#3231>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETree", "rte_tree", "div",
				array());
?>
<#3232>
<?php
$ilDB->addTableColumn("sahs_lm", "final_sco_page", array(
	"type" => "integer",
	"notnull" => true,
	"default" => 0,
	"length" => 4
));
$ilDB->addTableColumn("sahs_lm", "final_lm_page", array(
	"type" => "integer",
	"notnull" => true,
	"default" => 0,
	"length" => 4
));
?>
<#3233>
<?php
if(!$ilDB->tableExists('il_blog_posting'))
{
	$fields = array (
		'id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'blog_id' => array ('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'title' => array ('type' => 'text', 'notnull' => false, 'length' => 400),
		'created' => array ('type' => 'timestamp', 'notnull' => true)
	  );
  $ilDB->createTable('il_blog_posting', $fields);
  $ilDB->addPrimaryKey('il_blog_posting', array('id'));
  $ilDB->addIndex('il_blog_posting', array('created'), 'i1');
  $ilDB->createSequence('il_blog_posting');
}
?>
<#3234>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTECourse", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEChapter", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTESco", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEAsset", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTECourseDisabled", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEChapterDisabled", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEScoDisabled", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEAssetDisabled", "rte_node", "td",
				array());
?>
<#3235>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTEAsset", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTECompleted", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTENotAttempted", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTERunning", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEIncomplete", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEPassed", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEFailed", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEBrowsed", "rte_status", "a",
				array());
?>
<#3236>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETreeLink", "rte_tlink", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTETreeLinkDisabled", "rte_tlink", "a",
				array());
?>

<#3237>
<?php
// this is a fix for patched scorm editor installations (separate branch)
if($ilDB->getDBType() == 'mysql')
{
	$set = $ilDB->query("SELECT max(id) mid FROM style_template");
	$rec = $ilDB->fetchAssoc($set);
	if ($rec["mid"] > 0)
	{
		$ilDB->manipulate("UPDATE style_template_seq SET ".
			" sequence = ".$ilDB->quote($rec["mid"], "integer"));
	}
}
?>
<#3238>
<?php
$ilDB->addTableColumn("page_layout", "special_page", array(
	"type" => "integer",
	"notnull" => false,
	"default" => 0,
	"length" => 1
));
?>
<#3239>
<?php
$ilDB->addTableColumn("il_wiki_data", "public_notes", array(
	"type" => "integer",
	"notnull" => false,
	"default" => 1,
	"length" => 1
));
?>
<#3240>
<?php
if(!$ilDB->tableExists('il_blog'))
{
	$fields = array (
		'id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'notes' => array ('type' => 'integer', 'notnull' => false, 'length' => 1, 'default' => 1)
	  );
  $ilDB->createTable('il_blog', $fields);
  $ilDB->addPrimaryKey('il_blog', array('id'));
}
?>
<#3241>
<?php
if(!$ilDB->tableExists('acl_ws'))
{
	$fields = array (
		'node_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'object_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
	  );
  $ilDB->createTable('acl_ws', $fields);
  $ilDB->addPrimaryKey('acl_ws', array('node_id', 'object_id'));
}
?>
<#3242>
<?php
	if(!$ilDB->tableColumnExists('conditions', 'obligatory'))
	{
		$ilDB->addTableColumn(
			'conditions',
			'obligatory',
			array(
				'type' => 'integer',
				'notnull' => true,
				'length' => 1,
				'default' => 1
			)
		);
	}
?>
<#3243>
<?php
	if(!$ilDB->tableColumnExists('ut_lp_collections', 'grouping_id'))
	{
		$ilDB->addTableColumn(
			'ut_lp_collections',
			'grouping_id',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 4,
				'default'	=> 0
			)
		);
		$ilDB->addTableColumn(
			'ut_lp_collections',
			'num_obligatory',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 4,
				'default'	=> 0
			)
		);
	}
?>
<#3244>
<?php
	if(!$ilDB->tableColumnExists('ut_lp_collections', 'active'))
	{
		$ilDB->addTableColumn(
			'ut_lp_collections',
			'active',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 1,
				'default'	=> 1
			)
		);

	}
?>
<#3245>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("FinalMessage", "sco_fmess", "div",
		array("margin" => "100px", "padding" => "50px", "font-size" => "125%",
			"border-width" => "1px", "border-style" => "solid", "border-color" => "#F0F0F0",
			"background-color" => "#FAFAFA", "text-align" => "center"));
?>
<#3246>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3247>
<?php
	$ilDB->dropPrimaryKey("sahs_sc13_seq_item");
	$ilDB->addPrimaryKey("sahs_sc13_seq_item",
		array("sahs_sc13_tree_node_id", "rootlevel"));
?>
<#3248>
<?php
	$ilDB->addTableColumn("sahs_sc13_seq_item", "importseqxml", array(
		"type" => "clob"));
?>
<#3249>
<?php
	$ilDB->addTableColumn("sahs_lm", "seq_exp_mode", array(
		"type" => "integer", "length" => 1, "notnull" => false, "default" => 0));
?>
<#3250>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3251>
<?php
	if(!$ilDB->tableColumnExists('conditions','num_obligatory'))
	{
		$ilDB->addTableColumn(
			'conditions',
			'num_obligatory',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 1,
				'default'	=> 0
			)
		);
	}
?>
<#3252>
<?php
	if(!$ilDB->tableColumnExists('payment_prices','extension'))
	{
		$ilDB->addTableColumn(
			'payment_prices',
			'extension',
			array(
				'type' => 'integer',
				'length' => '1',
				'notnull' => true,
				'default' => 0
			)
		);
	}
?>
<#3253>
<?php
	if(!$ilDB->tableColumnExists('payment_statistic','access_enddate'))
	{
		$ilDB->addTableColumn(
			'payment_statistic',
			'access_enddate',
			array(
				'type'     => 'timestamp',
				'notnull'	=> false
			)
		);
	}
?>
<#3254>
<?php
	$res = $ilDB->queryf('
		SELECT booking_id, order_date, duration
		FROM payment_statistic
		WHERE duration > %s',
		array('integer'),array(0));

	while($row = $ilDB->fetchAssoc($res))
	{
		$order_date = $row['order_date'];
		$duration = $row['duration'];

		$orderDateYear = date("Y", $row['order_date']);
		$orderDateMonth = date("m", $row['order_date']);
		$orderDateDay = date("d", $row['order_date']);
		$orderDateHour = date("H",$row['order_date']);
		$orderDateMinute = date("i", $row['order_date']);
		$orderDateSecond = date("s", $row['order_date']);

		$access_enddate = date("Y-m-d H:i:s", mktime($orderDateHour, $orderDateMinute, $orderDateSecond,
				$orderDateMonth + $duration, $orderDateDay, $orderDateYear));

		$ilDB->update('payment_statistic',
			array('access_enddate' => array('timestamp', $access_enddate)),
			array('booking_id' => array('integer', $row['booking_id'])));
	}
?>
<#3255>
<?php

if (!$ilDB->tableColumnExists("il_wiki_data", "imp_pages"))
{
	$ilDB->addTableColumn("il_wiki_data", "imp_pages", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3256>
<?php
if (!$ilDB->tableExists('il_wiki_imp_pages'))
{
	$fields = array(
		'wiki_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'ord' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'indent' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
			),
		'page_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			)
	);
	$ilDB->createTable('il_wiki_imp_pages', $fields);
}
?>
<#3257>
<?php
if (!$ilDB->tableExists('adm_settings_template'))
{
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'type' => array(
			'type' => 'text',
			'length' => 5,
			'notnull' => true
			),
		'title' => array(
			'type' => 'text',
			'length' => 100,
			'notnull' => true
			),
		'description' => array(
			'type' => 'clob'
			)
	);
	$ilDB->createTable('adm_settings_template', $fields);
	$ilDB->createSequence('adm_settings_template');
}
?>
<#3258>
<?php
if (!$ilDB->tableExists('adm_set_templ_value'))
{
	$fields = array(
		'template_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'setting' => array(
			'type' => 'text',
			'length' => 40,
			'notnull' => true
			),
		'value' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
			),
		'hide' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
	);
	$ilDB->createTable('adm_set_templ_value', $fields);
}
?>
<#3259>
<?php
if (!$ilDB->tableExists('adm_set_templ_hide_tab'))
{
	$fields = array(
		'template_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'tab_id' => array(
			'type' => 'text',
			'length' => 80,
			'notnull' => true
			)
	);
	$ilDB->createTable('adm_set_templ_hide_tab', $fields);
}
?>
<#3260>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3261>
<?php
if (!$ilDB->tableColumnExists("il_wiki_data", "page_toc"))
{
	$ilDB->addTableColumn("il_wiki_data", "page_toc", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3262>
<?php
if (!$ilDB->tableColumnExists("il_wiki_page", "blocked"))
{
	$ilDB->addTableColumn("il_wiki_page", "blocked", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3263>
<?php
if (!$ilDB->tableColumnExists("svy_svy", "template_id"))
{
	$ilDB->addTableColumn("svy_svy", "template_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
	));
}
?>
<#3264>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "express_qpool_allowed"))
{
	$ilDB->addTableColumn("tst_tests", "express_qpool_allowed", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1,
		"default" => 0
	));
}
?>
<#3265>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "enabled_view_mode"))
{
	$ilDB->addTableColumn("tst_tests", "enabled_view_mode", array(
		"type" => "text",
		"notnull" => false,
		"length" => 20,
		"default" => 0
	));
}
?>
<#3266>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "template_id"))
{
	$ilDB->addTableColumn("tst_tests", "template_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
	));
}
?>
<#3267>
<?php
if (!$ilDB->tableColumnExists("svy_svy", "pool_usage"))
{
	$ilDB->addTableColumn("svy_svy", "pool_usage", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3268>
<?php
if (!$ilDB->tableColumnExists("svy_qblk", "show_blocktitle"))
{
	$ilDB->addTableColumn("svy_qblk", "show_blocktitle", array(
		"type" => "text",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3269>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "pool_usage"))
{
	$ilDB->addTableColumn("tst_tests", "pool_usage", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3270>
<?php
if ($ilDB->tableColumnExists("tst_tests", "express_qpool_allowed"))
{
	$ilDB->dropTableColumn("tst_tests", "express_qpool_allowed");
}
?>
<#3271>
<?php
if (!$ilDB->tableColumnExists("il_news_item", "content_text_is_lang_var"))
{
	$ilDB->addTableColumn("il_news_item", "content_text_is_lang_var", array(
		"type" => "integer",
		"length" => 1,
		"notnull" => true,
		"default" => 0
		));
}
?>
<#3272>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Page", "page", "div",
				array());
?>
<#3273>
<?php	
	if(!$ilDB->tableColumnExists('exc_data', 'compl_by_submission'))
	{
		$ilDB->addTableColumn('exc_data', 'compl_by_submission', array(
			'type'		=> 'integer',
			'length'	=> 1,
			'notnull'	=> true,
			'default'	=> 0
		));
	}
?>
<#3274>
<?php
	if(!$ilDB->tableColumnExists('crs_settings', 'auto_noti_disabled'))
	{
		$ilDB->addTableColumn('crs_settings', 'auto_noti_disabled', array(
			'type'		=> 'integer',
			'length'	=> 1,
			'notnull'	=> true,
			'default'	=> 0
		));
	}
?>
<#3275>
<?php
if(!$ilDB->tableExists('il_verification'))
{
	$ilDB->createTable('il_verification',array(
		'id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'type'	=> array(
			'type'	=> 'text',
			'length'=> 100,
			'notnull' => true
		),
		'parameters' => array(
			'type'	=> 'text',
			'length'=> 1000,
			'notnull' => false
		),
		'raw_data'	=> array(
			'type'	=> 'clob',
			'notnull' => false
		)
	));

	$ilDB->addIndex('il_verification', array('id'), 'i1');
}
?>
<#3276>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3277>
<?php	
	if(!$ilDB->tableColumnExists('qpl_qst_fileupload', 'compl_by_submission'))
	{
		$ilDB->addTableColumn('qpl_qst_fileupload', 'compl_by_submission', array(
			'type'		=> 'integer',
			'length'	=> 1,
			'notnull'	=> true,
			'default'	=> 0
		));
	}
?>
<#3278>
<?php
	if(!$ilDB->tableColumnExists('payment_objects', 'subtype'))
	{
		$ilDB->addTableColumn("payment_objects", "subtype", array(
			"type" => "text",
			"length" => 10,
			"notnull" => false,
			"default" => null));
	}
?>
<#3279>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'mail_use_placeholders'))
	{
		$ilDB->addTableColumn("payment_settings", "mail_use_placeholders", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3280>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'mail_billing_text'))
	{
		$ilDB->addTableColumn("payment_settings", "mail_billing_text", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
	}
?>
<#3281>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'hide_shop_info'))
	{
		$ilDB->addTableColumn("payment_settings", "hide_shop_info", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3282>
<?php
	if(!$ilDB->tableColumnExists('payment_objects', 'is_special'))
	{
		$ilDB->addTableColumn("payment_objects", "is_special", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3283>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'use_shop_specials'))
	{
		$ilDB->addTableColumn("payment_settings", "use_shop_specials", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3284>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3285>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3286>
<?php
if(!$ilDB->tableExists('usr_portfolio'))
{
	$ilDB->createTable('usr_portfolio',array(
		'id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'user_id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 250,
			'notnull' => true
		),
		'description' => array(
			'type'	=> 'clob',
			'notnull' => false
		),
		'is_online' => array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		),
		'is_default' => array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('usr_portfolio', array('id'));
	$ilDB->createSequence('usr_portfolio');
}
?>
<#3287>
<?php
if(!$ilDB->tableExists('usr_portfolio_page'))
{
	$ilDB->createTable('usr_portfolio_page',array(
		'id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'portfolio_id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 250,
			'notnull' => true
		),
		'order_nr' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
	));
	$ilDB->addPrimaryKey('usr_portfolio_page', array('id'));
	$ilDB->createSequence('usr_portfolio_page');
}
?>
<#3288>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3289>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("QuestionImage", "qimg", "img",
				array("margin" => "5px"));
?>
<#3290>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderList", "qordul", "ul",
				array("margin" => "0px",
					"padding" => "0px",
					"list-style" => "none",
					"list-style-position" => "outside"
					));
?>
<#3291>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderListItem", "qordli", "li",
				array(
					"margin-top" => "5px",
					"margin-bottom" => "5px",
					"margin-left" => "0px",
					"margin-right" => "0px",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#D0D0FF",
					"padding" => "10px",
					"cursor" => "move"
					));

?>
<#3292>
<?php

	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ImageDetailsLink", "qimgd", "a",
				array("font-size" => "90%"));

?>
<#3293>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextItem", "qetitem", "a",
				array("text-decoration" => "none",
					"color" => "#000000",
					"padding" => "2px"
					));
?>
<#3294>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextItem:hover", "qetitem", "a",
				array("text-decoration" => "none",
					"color" => "#000000",
					"background-color" => "#D0D0D0"
					));
?>
<#3295>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextSelected", "qetitem", "a",
				array("border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#606060",
					"background-color" => "#9BD9FE"
					));
?>
<#3296>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextCorrected", "qetcorr", "span",
				array("text-decoration" => "line-through",
					"color" => "#909090"
					));
?>
<#3297>
<?php
	$set = $ilDB->query("SELECT * FROM style_char ".
		" WHERE ".$ilDB->like("characteristic", "text", "%:hover%")
		);
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$s = substr($rec["characteristic"], strlen($rec["characteristic"]) - 6);
		if ($s == ":hover")
		{
			$ilDB->manipulate("DELETE FROM style_char WHERE ".
				" characteristic = ".$ilDB->quote($rec["characteristic"], "text")
				);
		}
	}
?>
<#3298>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderListHorizontal", "qordul", "ul",
				array("margin" => "0px",
					"padding" => "0px",
					"list-style" => "none",
					"list-style-position" => "outside"
					));
?>
<#3299>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderListItemHorizontal", "qordli", "li",
				array(
					"float" => "left",
					"margin-top" => "5px",
					"margin-bottom" => "5px",
					"margin-right" => "10px",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#D0D0FF",
					"padding" => "10px",
					"cursor" => "move"
					));

?>
<#3300>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3301>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorText", "question", "div",
				array());
?>
<#3302>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("TextSubset", "question", "div",
				array());
?>
<#3303>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3304>
<?php
$ilDB->modifyTableColumn('frm_settings', 'notification_type', array(
	"type" => "text",
	"notnull" => false,
	"length" => 10,
	"default" => null));
?>
<#3305>
<?php

$set = $ilDB->query("SELECT * FROM object_data WHERE type = 'sty'");

while ($rec = $ilDB->fetchAssoc($set))	// all styles
{
	$imgs = array("icon_pin.png", "icon_pin_on.png");
	
	$a_style_id = $rec["obj_id"];
	
	$sty_data_dir = CLIENT_WEB_DIR."/sty";
	ilUtil::makeDir($sty_data_dir);

	$style_dir = $sty_data_dir."/sty_".$a_style_id;
	ilUtil::makeDir($style_dir);

	// create images subdirectory
	$im_dir = $style_dir."/images";
	ilUtil::makeDir($im_dir);

	// create thumbnails directory
	$thumb_dir = $style_dir."/images/thumbnails";
	ilUtil::makeDir($thumb_dir);
	
//	ilObjStyleSheet::_createImagesDirectory($rec["obj_id"]);
	$imdir = CLIENT_WEB_DIR."/sty/sty_".$a_style_id.
			"/images";
	foreach($imgs as $cim)
	{
		if (!is_file($imdir."/".$cim))
		{
			copy("./Services/Style/basic_style/images/".$cim, $imdir."/".$cim);
		}
	}
}
?>
<#3306>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ContentPopup", "iim", "div",
				array("background-color" => "#FFFFFF",
					"border-color" => "#A0A0A0",
					"border-style" => "solid",
					"border-width" => "2px",
					"padding-top" => "5px",
					"padding-right" => "10px",
					"padding-bottom" => "5px",
					"padding-left" => "10px"
					));
?>
<#3307>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Marker", "marker", "a",
				array("display" => "block",
					"cursor" => "pointer",
					"width" => "27px",
					"height" => "32px",
					"position" => "absolute",
					"background-image" => "icon_pin.png"
					));
?>
<#3308>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Marker:hover", "marker", "a",
				array("background-image" => "icon_pin_on.png"
					));
?>
<#3309>
<?php
	$set = $ilDB->query("SELECT * FROM style_char ".
		" WHERE ".$ilDB->like("characteristic", "text", "%:hover%")
		);
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$s = substr($rec["characteristic"], strlen($rec["characteristic"]) - 6);
		if ($s == ":hover")
		{
			$ilDB->manipulate("DELETE FROM style_char WHERE ".
				" characteristic = ".$ilDB->quote($rec["characteristic"], "text")
				);
		}
	}
?>
<#3310>
<?php
if(!$ilDB->tableExists('usr_portf_acl'))
{
	$fields = array (
		'node_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'object_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
	  );
  $ilDB->createTable('usr_portf_acl', $fields);
  $ilDB->addPrimaryKey('usr_portf_acl', array('node_id', 'object_id'));
}
?>
<#3311>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3312>
<?php
	if(!$ilDB->tableColumnExists('note', 'no_repository'))
	{
		$ilDB->addTableColumn("note", "no_repository", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => false,
			"default" => 0));
	}
?>
<#3313>
<?php
	$ilDB->createTable(
		'ecs_server',
		array(
			'server_id' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'active' => array('type' => 'integer','length' => 1, 'notnull' => false, 'default' => 0),
			'protocol' => array('type' => 'integer','length' => 1, 'notnull' => false, 'default' => 1),
			'server' => array('type' => 'text', 'length' => 255, 'notnull' => false),
			'port' => array('type' => 'integer','length' => 2, 'notnull' => false, 'default' => 1),
			'auth_type' => array('type' => 'integer', 'length' => 1, 'notnull' => false, 'default' => 1),
			'client_cert_path' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'ca_cert_path' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'key_path' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'key_password' => array('type' => 'text', 'length' => 32, 'notnull' => false),
			'cert_serial' => array('type' => 'text', 'length' => 32, 'notnull' => false),
			'polling_time' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'import_id' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'global_role' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'econtent_rcp' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'user_rcp' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'approval_rcp' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'duration' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0)
		));
	$ilDB->createSequence('ecs_server');

?>
<#3314>
<?php
	if(!$ilDB->tableColumnExists('payment_statistic','access_startdate'))
	{
		$ilDB->addTableColumn(
			'payment_statistic',
			'access_startdate',
			array(
				'type'     => 'timestamp',
				'notnull'	=> false
			)
		);
	}
?>
<#3315>
<?php

	// Migration of ecs settings
	$query = 'SELECT * FROM settings WHERE '.
		'module = '.$ilDB->quote('ecs','text');
	$res = $ilDB->query($query);

	$ecs = array();
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$ecs[$row->keyword] = $row->value;
	}
	if(count($ecs))
	{
		$ilDB->manipulate('INSERT INTO ecs_server (server_id,active,protocol,server,port,auth_type,client_cert_path,ca_cert_path,'.
			'key_path,key_password,cert_serial,polling_time,import_id,global_role,econtent_rcp,user_rcp,approval_rcp,duration) '.
			'VALUES ('.
			$ilDB->quote($ilDB->nextId('ecs_server'),'integer').', '.
			$ilDB->quote($ecs['active'],'integer').', '.
			$ilDB->quote($ecs['protocol'],'integer').', '.
			$ilDB->quote($ecs['server'],'text').', '.
			$ilDB->quote($ecs['port'],'integer').', '.
			$ilDB->quote(1,'integer').', '.
			$ilDB->quote($ecs['client_cert_path'],'text').', '.
			$ilDB->quote($ecs['ca_cert_path'],'text').', '.
			$ilDB->quote($ecs['key_path'],'text').', '.
			$ilDB->quote($ecs['key_password'],'text').', '.
			$ilDB->quote($ecs['cert_serial'],'text').', '.
			$ilDB->quote($ecs['polling_time'],'integer').', '.
			$ilDB->quote($ecs['import_id'],'integer').', '.
			$ilDB->quote($ecs['global_role'],'integer').', '.
			$ilDB->quote($ecs['econtent_rcp'],'text').', '.
			$ilDB->quote($ecs['user_rcp'],'text').', '.
			$ilDB->quote($ecs['approval_rcp'],'text').', '.
			$ilDB->quote($ecs['duration'],'integer').
			')');
	}
?>
<#3316>
<?php
	$ilDB->modifyTableColumn('cal_entries', 'context_id',
		array("type" => "integer", "length" => 4, "default" => 0, "notnull" => true));
?>
<#3317>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3318>
<?php
	if(!$ilDB->tableColumnExists('payment_settings','show_general_filter'))
	{
		$ilDB->addTableColumn('payment_settings','show_general_filter',
		array(  "type" => "integer",
				"length" => 1,
				"notnull" => false,
				"default" => 0));
	}
?>
<#3319>
<?php
	if(!$ilDB->tableColumnExists('payment_settings','show_topics_filter'))
	{
		$ilDB->addTableColumn('payment_settings','show_topics_filter',
		array(  "type" => "integer",
				"length" => 1,
				"notnull" => false,
				"default" => 0));
	}
?>
<#3320>
<?php
	if(!$ilDB->tableColumnExists('payment_settings','show_shop_explorer'))
	{
		$ilDB->addTableColumn('payment_settings','show_shop_explorer',
		array(  "type" => "integer",
				"length" => 1,
				"notnull" => false,
				"default" => 0));
	}
?>
<#3321>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'ud_invoice_number'))
	{
		$ilDB->addTableColumn('payment_settings', 'ud_invoice_number', array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0));
	}
?>
<#3322>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'invoice_number_text'))
	{
		$ilDB->addTableColumn('payment_settings', 'invoice_number_text', array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false,
			'default' => null));
	}
?>
<#3323>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_start_value'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_start_value', array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true));
	}
?>
<#3324>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_current_value'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_current_value', array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true));
	}
?>
<#3325>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_reset_period'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_reset_period', array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0));
	}
?>
<#3326>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_last_reset'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_last_reset', array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true));
	}
?>
<#3327>
<?php
	$ilDB->update('payment_settings',
			array('inc_last_reset' => array('integer', time())),
			array('settings_id' => array('integer', 1)));
?>
<#3328>
<?php
		$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'obj_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true
			),
		'yyyy' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => false
			),
		'mm' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'dd' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'hh' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
	);
	$ilDB->createTable('obj_stat', $fields);
	$ilDB->addIndex("obj_stat", array("obj_id", "yyyy", "mm"), "i1");
?>
<#3329>
<?php
		$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'obj_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true
			),
		'tstamp' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'yyyy' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => false
			),
		'mm' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'dd' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'hh' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
	);
	$ilDB->createTable('obj_stat_tmp', $fields);
?>
<#3330>
<?php		
		$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'obj_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true
			),
		'tstamp' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'yyyy' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => false
			),
		'mm' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'dd' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'hh' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
	);
	$ilDB->createTable('obj_stat_log', $fields);
?>
<#3331>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3332>
<?php
if(!$ilDB->tableExists('cp_datamap'))
{
	$fields = array (
		"sco_node_id" => array (
			"notnull" => true
			,"length" => 4
			,"unsigned" => false
			,"default" => "0"
			,"type" => "integer"
		)
		,"cp_node_id" => array (
			"notnull" => false
			,"length" => 4
			,"unsigned" => false
			,"default" => "0"
			,"type" => "integer"
		)
		,"slm_id" => array (
			"notnull" => true
			,"length" => 4
			,"type" => "integer"
		)
		,"target_id" => array (
			"notnull" => true
			,"length" => 4000
			,"fixed" => false
			,"type" => "text"
		)
		,"read_shared_data" => array (
			"notnull" => false
			,"length" => 1
			,"unsigned" => false
			,"default" => "1"
			,"type" => "integer"
		)
		,"write_shared_data" => array (
			"notnull" => false
			,"length" => 1
			,"unsigned" => false
			,"default" => "1"
			,"type" => "integer"
		)
	);
	$ilDB->createTable("cp_datamap", $fields);
	
	$pk_fields = array("cp_node_id");
	$ilDB->addPrimaryKey("cp_datamap", $pk_fields);
}
?>
<#3333>
<?php
if(!$ilDB->tableExists('adl_shared_data'))
{
	$fields = array (
		"slm_id" => array (
			"notnull" => true
			,"length" => 4
			,"type" => "integer"
		)
		,"user_id" => array (
			"notnull" => true
			,"length" => 4
			,"type" => "integer"
		)
		,"target_id" => array (
			"notnull" => true
			,"length" => 4000
			,"fixed" => false
			,"type" => "text"
		)
		,"store" => array (
			"notnull" => false
			,"type" => "clob"
		)
	);
	$ilDB->createTable("adl_shared_data", $fields);
}
?>
<#3334>
<?php
	$ilDB->addTableColumn("cp_package", "shared_data_global_to_system", array(
		"type" => "integer",
		"notnull" => false,
		"unsigned" => false,
		"default" => "1",
		"length" => 1));
?>
<#3335>
<?php
	if($ilDB->tableExists('cmi_gobjective'))
	{	 
		$ilDB->addTableColumn("cmi_gobjective", "score_raw", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "score_min", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "score_max", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "progress_measure", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "completion_status", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
	}
?>
<#3336>
<?php
	$ilDB->addTableColumn("cp_item", "progressweight", array(
		"type" => "text",
		"notnull" => false,
		"default" => "1.0",
		"fixed" => false,
		"length" => 50));
	$ilDB->addTableColumn("cp_item", "completedbymeasure", array(
		"type" => "integer",
		"notnull" => false,
		"unsigned" => false,
		"default" => "0",
		"length" => 1));
	$ilDB->modifyTableColumn("cp_item", "completionthreshold", array("default" => "1.0"));
?>
<#3337>
<?php
	// cmi_objective completion_status from double to text
	$ilDB->addTableColumn("cmi_objective", "completion_status_tmp", array(
		"type" => "text",
		"length" => 32,
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE cmi_objective SET completion_status_tmp = completion_status');
	$ilDB->dropTableColumn('cmi_objective', 'completion_status');
	$ilDB->renameTableColumn("cmi_objective", "completion_status_tmp", "completion_status");
?>
<#3338>
<?php
	if(!$ilDB->tableColumnExists('ecs_server','title'))
	{
		$ilDB->addTableColumn("ecs_server", "title", array(
			"type" => "text",
			"length" => 128,
			"notnull" => false,
			"default" => null)
		);
	}
?>
<#3339>
<?php
if (!$ilDB->tableColumnExists('tst_active', 'importname'))
{
	$ilDB->addTableColumn("tst_active", "importname",
			array(
				"type" => "text",
				"notnull" => false,
			 	"length" => 400,
			 	"fixed" => false));
}
?>
<#3340>
<?php
	if(!$ilDB->tableExists('ecs_part_settings'))
	{
		$fields = array(
			"sid" => array("notnull" => true,"length" => 4,"type" => "integer"),
			"mid" => array("notnull" => true,"length" => 4,"type" => "integer"),
			"export" => array("notnull" => true,"length" => 1,"type" => "integer"),
			"import" => array("notnull" => true,"length" => 1,"type" => "integer"),
			"import_type" => array("notnull" => false,'length' => 1, "type" => "integer")
		);
		$ilDB->createTable("ecs_part_settings", $fields);
	}
?>
<#3341>
<?php
	if(!$ilDB->tableExists('ecs_data_mapping'))
	{
		$fields = array(
			"sid" => array("notnull" => true,"length" => 4,"type" => "integer"),
			"import_type" => array("notnull" => true,"length" => 1,"type" => "integer"),
			"ecs_field" => array("notnull" => false,'length' => 32,"type" => "text"),
			"advmd_id" => array("notnull" => true, "length" => 4, "type" => "integer")
		);
		$ilDB->createTable("ecs_data_mapping", $fields);
		$ilDB->addPrimaryKey('ecs_data_mapping', array('sid','import_type','ecs_field'));
	}
?>
<#3342>
<?php
	if(!$ilDB->tableExists('payment_tmp'))
	{
		$fields = array (
		'keyword' => array ('type' => 'text', 'length'  => 50,'notnull' => true, "fixed" => false),
		'value' => array('type' => 'clob', 'notnull' => false, 'default' => null),
		'scope' =>array ('type' => 'text', 'length'  => 50,'notnull' => false, "default" => null)
		);
		$ilDB->createTable('payment_tmp', $fields);
		$ilDB->addPrimaryKey('payment_tmp', array('keyword'));
	}
?>
<#3343>
<?php
$old = array();
$res = $ilDB->query('SELECT * FROM payment_settings');
$old = $ilDB->fetchAssoc($res);

if($old == NULL)
{
  //use default values
  $old['settings_id'] = '0';
  $old['currency_unit'] = NULL;
  $old['currency_subunit'] =  NULL;
  $old['address'] = NULL;
  $old['bank_data'] =  NULL;
  $old['add_info'] = NULL;
  $old['pdf_path'] =  NULL;
  $old['paypal'] = NULL;
  $old['bmf'] = NULL;
  $old['topics_allow_custom_sorting'] = '0';
  $old['topics_sorting_type'] = '0';
  $old['topics_sorting_direction'] = NULL;
  $old['shop_enabled'] = '0';
  $old['max_hits'] =  '0';
  $old['hide_advanced_search'] =  NULL;
  $old['objects_allow_custom_sorting'] = '0';
  $old['hide_coupons'] =  NULL;
  $old['epay '] = NULL;
  $old['hide_news'] =  NULL;
  $old['mail_use_placeholders'] =  '0';
  $old['mail_billing_text'] = null;
  $old['hide_shop_info'] = '0';
  $old['use_shop_specials'] =  '0';
  $old['ud_invoice_number'] = '0';
  $old['invoice_number_text'] = NULL;
  $old['inc_start_value'] = '0';
  $old['inc_current_value'] = '0';
  $old['inc_reset_period'] = '0';
  $old['inc_last_reset'] = null;
  $old['show_general_filter'] =  '0';
  $old['show_topics_filter'] =  '0';
  $old['show_shop_explorer'] =  '0';
}
foreach($old as $key=>$value)
{
	switch($key)
	{
		case 'paypal':
			$scope = 'paypal';
			break;
		case 'bmf':
			$scope = 'bmf';
			break;
		case 'epay':
			$scope = 'epay';
			break;

		case 'currency_unit':
		case 'currency_subunit':
			$scope = 'currencies';
			break;

		case 'address':
		case 'bank_data':
		case 'add_info':
		case 'pdf_path':
		case 'mail_use_placeholders':
		case 'mail_billing_text':
			$scope = 'invoice';
			break;

		case 'ud_invoice_number':
		case 'ud_shop_specials':
		case 'invoice_number_text':
		case 'inc_start_value':
		case 'inc_currend_value':
		case 'inc_reset_period':
		case 'inc_last_reset':
			$scope = 'invoice_number';
			break;
		case 'topics_allow_custom_sorting':
		case 'topics_sorting_type':
		case 'topics_sorting_direction':
		case 'max_hits':
		case 'hide_advanced_search':
		case 'objects_allow_custom_sorting':
		case 'hide_coupons':
		case 'hide_news':
		case 'hide_shop_info':
		case 'show_general_filter':
		case 'show_topics_filter':
		case 'show_shop_explorer':
		case 'use_shop_specials':
			$scope = 'gui';
			break;

		case 'shop_enabled':
			$scope = 'common';
			break;

		default:
			// for custom settings
			$scope = NULL;
			break;

	}

	$ilDB->insert('payment_tmp',
	array(
		'keyword' => array('text', $key),
		'value' => array('clob', $value),
		'scope' => array('text', $scope)
	));
}
?>
<#3344>
<?php
	if($ilDB->tableExists('payment_settings'))
	{
		$ilDB->dropTable('payment_settings');
	}
	if($ilDB->tableExists('payment_settings_seq'))
	{
		$ilDB->dropTable('payment_settings_seq');
	}
?>
<#3345>
<?php
	if($ilDB->tableExists('payment_tmp'))
	{
		$ilDB->renameTable('payment_tmp', 'payment_settings');
	}
?>
<#3346>
<?php

	$ilDB->dropPrimaryKey('ecs_data_mapping');
	$ilDB->renameTableColumn('ecs_data_mapping','import_type','mapping_type');
	$ilDB->addPrimaryKey('ecs_data_mapping', array('sid','mapping_type','ecs_field'));
?>
<#3347>
<?php
	if (!$ilDB->tableColumnExists("acl_ws", "extended_data"))
	{
		$ilDB->addTableColumn("acl_ws", "extended_data", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 200,
		 	"fixed" => false));
	}
?>
<#3348>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3349>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3350>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Correct", "qover", "div",
				array(
					"margin-top" => "20px",
					"margin-bottom" => "20px",
					"padding-top" => "10px",
					"padding-right" => "60px",
					"padding-bottom" => "10px",
					"padding-left" => "30px",
					"background-color" => "#E7FFE7",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#808080"
				));
?>
<#3351>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Incorrect", "qover", "div",
				array(
					"margin-top" => "20px",
					"margin-bottom" => "20px",
					"padding-top" => "10px",
					"padding-right" => "60px",
					"padding-bottom" => "10px",
					"padding-left" => "30px",
					"background-color" => "#FFE7E7",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#808080"
				));
?>
<#3352>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("StatusMessage", "qover", "div",
				array(
					"padding-bottom" => "7px"
				));
?>
<#3353>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("WrongAnswersMessage", "qover", "div",
				array(
				));
?>
<#3354>
<?php
	if (!$ilDB->tableColumnExists("ecs_import", "server_id"))
	{
		$ilDB->addTableColumn("ecs_import", "server_id", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 4,
		 	"default" => 0)
		);
	}
	$ilDB->dropPrimaryKey('ecs_import');
	$ilDB->addPrimaryKey('ecs_import', array('server_id','obj_id'));
?>
<#3355>
<?php

	$res = $ilDB->query('SELECT server_id FROM ecs_server');

	if($res->numRows())
	{
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		$query = 'UPDATE ecs_import SET server_id = '.$ilDB->quote($row->server_id);
		$ilDB->manipulate($query);
	}
?>
<#3356>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3357>
<?php
$ilDB->addTableColumn("sahs_lm", "localization", array(
	"type" => "text",
	"notnull" => false,
	"length" => 2
));
?>

<#3358>
<?php
	if (!$ilDB->tableColumnExists("ecs_export", "server_id"))
	{
		$ilDB->addTableColumn("ecs_export", "server_id", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 4,
		 	"default" => 0)
		);
	}
	$ilDB->dropPrimaryKey('ecs_export');
	$ilDB->addPrimaryKey('ecs_export', array('server_id','obj_id'));
?>
<#3359>
<?php

	$res = $ilDB->query('SELECT server_id FROM ecs_server');

	if($res->numRows())
	{
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		$query = 'UPDATE ecs_export SET server_id = '.$ilDB->quote($row->server_id);
		$ilDB->manipulate($query);
	}
?>
<#3360>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3361>
<?php
	if (!$ilDB->tableColumnExists("ecs_part_settings", "title"))
	{
		$ilDB->addTableColumn("ecs_part_settings", "title", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 255
			)
		);
		$ilDB->addTableColumn("ecs_part_settings", "cname", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 255
			)
		);
	}
?>
<#3362>
<?php
	if (!$ilDB->tableColumnExists("exc_assignment", "type"))
	{
		$ilDB->addTableColumn("exc_assignment", "type", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 1,
			"default" => 1
			)
		);
	}
?>