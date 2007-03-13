<?php
/**
 * ILIAS Open Source
 * --------------------------------
 * Implementation of ADL SCORM 2004
 * 
 * This program is free software. The use and distribution terms for this software
 * are covered by the GNU General Public License Version 2
 * 	<http://opensource.org/licenses/gpl-license.php>.
 * By using this software in any fashion, you are agreeing to be bound by the terms 
 * of this license.
 * 
 * Note: This code derives from other work by the original author that has been
 * published under Common Public License (CPL 1.0). Please send mail for more
 * information to <alfred.kohnert@bigfoot.com>.
 * 
 * You must not remove this notice, or any other, from this software.
 * 
 * PRELIMINARY EDITION 
 * This is work in progress and therefore incomplete and buggy ... 
 *  
 * Content-Type: application/x-httpd-php; charset=ISO-8859-1 
 * 
 * @author Alfred Kohnert <alfred.kohnert@bigfoot.com>
 * @version $Id$
 * @copyright: (c) 2005-2007 Alfred Kohnert 
 * 
 */ 

class ilSCORM13Package
{

	const DB_ENCODE_XSL = 'templates/xsl/op/op-scorm13.xsl';
	const DB_DECODE_XSL = 'templates/xsl/op/op-scorm13-revert.xsl';
	const VALIDATE_XSD = 'templates/xsd/op/op-scorm13.xsd';
	
	private $packageFile;
	private $packageFolder;
	private $packagesFolder;
	private $packageData;

	public $imsmanifest;
	public $manifest;
	public $diagnostic;
	public $status;
	public $packageId;
	public $packageName;
	public $packageHash;

	private $idmap = array();
	private $progress = 0.0;

	static private $elements = array(
		'cp' => array(
			'manifest', 
			'organization', 
			'item', 
			'hideLMSUI', 
			'resource', 
			'file', 
			'dependency', 
			'sequencing',
			'rule', 
			'auxilaryResource', 
			'condition', 
			'mapinfo',
			'objective',
		),
		'cmi' => array(
			'comment',
			'correct_response',
			'interaction',
			'node',
			'objective',
		), 
	);
	
	public function __construct($packageId = null)
	{
		$this->packagesFolder = IL_OP_PACKAGES_FOLDER;
		$this->load($packageId);
	}
	
	public function getAdmin()
	{
		$packages = ilSCORM13DB::getRecords('cp_package');
		$users = ilSCORM13DB::getRecords('usr_data');

		$samples = scandir(IL_OP_SAMPLES_FOLDER);
		$samples = array_flip($samples);
		foreach ($samples as $k => $v) 
		{
			$fn = IL_OP_SAMPLES_FOLDER . '/' . $k;
			if (is_file($fn))
			{
				$samples[$k] = $k . ' (' . number_format(filesize($fn)/1000, 0) . ' kb)'; 
			}
			else
			{
				unset($samples[$k]);
			} 
		}
		$samples[''] = 'Select a sample file...';
		
 		header('Content-Type: text/html; charset=UTF-8');
		$tpl = new ilSCORM13Template();
		$tpl->load('templates/tpl/tpl.scorm2004.admin.html');
		$tpl->setParam('DOC_TITLE', 'ILIAS SCORM 2004 Admin');
		$tpl->setParam('THEME_CSS', 'templates/css/delos.css');
		$tpl->setParam('PACKAGES', $tpl->toHTMLSelect($packages, $this->packageId, 
			array('name'=>'packageId', 'size'=>10), 'obj_id', 'identifier'));
		$tpl->setParam('USERS', $tpl->toHTMLSelect($users, ilSCORM13Utils::$userId, 
			array('name'=>'userId', 'size'=>10), 'user_id', 'email'));
		$tpl->setParam('PACKAGE_FILES', $tpl->toHTMLSelect($samples, '', 'packagefile'));
		$tpl->setParam('MESSAGE', isset($msg) ? (is_array($msg) ? implode('<br>', $msg) : $msg) : '');
		$tpl->setParam('PACKAGE_ID', $this->packageID);
		$tpl->save();
	}
	
	public function load($packageId)
	{
		if (!is_numeric($packageId)) 
		{
			return false;
		}
		$sahs = ilSCORM13DB::getRecord('sahs_lm', 'id', $packageId); 
		$package = ilSCORM13DB::getRecord('cp_package', 'obj_id', $packageId); 
		$this->packageData = array(
			'api_adapter'=>null, 'api_func_prefix'=>null, 'auto_review'=>null, 'credit'=>null,
			'default_lesson_mode'=>null, 'id'=>null, 'online'=>null, 'type'=>null,
			'created'=>null, 'identifier'=>null, 'jsdata	modified'=>null, 'obj_id'=>null, 
			'persistPreviousAttempts'=>null, 'settings'=>null, 'xmldata'=>null);
		if ($sahs) $this->packageData = array_merge($this->packageData, $sahs);
		if ($package) $this->packageData = array_merge($this->packageData, $package);
		$this->packageId = $packageId;
		$this->packageFolder = $this->packagesFolder . '/' . $packageId;
		$this->packageFile = $this->packageFolder . '.zip';
		$this->imsmanifestFile = $this->packageFolder . '/' . 'imsmanifest.xml';
		return true;
	}
	
	public function rollback()
	{
		$this->setProgress(0, 'Rolling back...');
		$this->dbRemoveAll();
		if (is_dir($this->packageFolder)) 
			ilSCORM13Utils::dir_delete($this->packageFolder);
		if (is_file($this->packageFile)) 
			@unlink($this->packageFile);
		$this->setProgress(0, 'Roll back finished: Ok. ');
	}
	
	public function exportZIP()
	{
		header('content-type: application/zip');
		header('content-disposition: attachment; filename="' . basename($this->packageFile) . '"');
		readfile($this->packageFile); 
	}
	
	/**
	 *	Export normalized manifest as xml file
	 */	
	public function exportXML()
	{
		header('content-type: text/xml');
		header('content-disposition: attachment; filename="manifest.xml"');
		$row = ilSCORM13DB::getRecord("cp_package", "obj_id", $this->packageId);
		print($row["xmldata"]);
	}
	
	public function removeCMIData()
	{
		// remove CMI entries 
		ilSCORM13DB::exec("DELETE FROM cmi_correct_response WHERE cmi_correct_response.cmi_interaction_id IN (SELECT cmi_interaction.cmi_interaction_id FROM cmi_interaction, cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_objective WHERE cmi_objective.cmi_interaction_id IN (SELECT cmi_interaction.cmi_interaction_id FROM cmi_interaction, cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_objective WHERE cmi_objective.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_interaction WHERE cmi_interaction.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_comment WHERE cmi_comment.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_node WHERE cmi_node.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		$row = ilSCORM13DB::getRecord("cp_package", "obj_id",$this->packageId);
		return $row["xmldata"];
	}
	
	// TODO needs exception handling 
	public function exportPackage()
	{
		// get filename for temp zip  
		$fn1 = tempnam($packagesFolder, 'zip');
		// copy package files and folders into zip 
		$fn2 = $this->packageFolder . '/*.*';
		ilSCORM13Utils::zip($fn1, $fn2);
		// copy xsd schema files for SCORM 1.3 into zip
		// this will overwrite files existing in packageFolder 
		$fn2 = realpath(dirname('./')) . '/templates/xsd/*.*';
		ilSCORM13Utils::zip($fn1, $fn2);
		// create imsmanifest.xml file from database content 
		// and copy into zip (this will overwrite existing (old) imsmanifest) 
		// and delete afterwards
		$doc = $this->generateManifest();
		$fn2 = tempnam($packagesFolder, 'xml');
		$doc->save($fn3);
		ilSCORM13Utils::zip($fn1, $fn2);
		unlink($fn2);
		// write data into output
		header('content-type: application/zip');
		header('content-disposition: attachment; filename="' . basename($this->packageFile) . '"');
		readfile($fn1); 
		// delete temp zip file 
		unlink($fn1);
	}
	
	/**
	 *	Generate imsmanifest from database structure
	 *	and return as DOMDocument	 
	 */	
	private function generateManifest()
	{
		$q = 'SELECT cp_node.cp_node_id as cp_node_id, 
			cp_node.nodeName as nodeName, cp_tree.depth as depth FROM cp_node 
			INNER JOIN cp_tree ON cp_tree.child = cp_node.cp_node_id 
			WHERE cp_node.slm_id=' . $this->packageId . ' ORDER BY cp_tree.lft';
		$nodes = ilSCORM13DB::query($q);
		$doc = new DOMDocument;
		$path = array($doc); 
		foreach ($nodes as &$node) 
		{
			$name = $node['nodeName'];
			$id = $node['cp_node_id'];
			$depth = $node['depth']-1;
			$data = ilSCORM13DB::getRecord('cp_' . $name, 'cp_node_id', $id);
			if (!isset($data)) continue;
			unset($data['cp_node_id']);
			$elm = $doc->createElement($name);
			$path[$depth]->appendChild($elm);
			$path[$depth+1] = $elm;
			foreach ($data as $k => $v) 
			{
				if (isset($v)) 
				{
					$elm->setAttribute($k, $v);
				}
			}
		}
		return $doc;
	}

	/**
	 *	Export immanifest as xml file
	 */	
	public function exportManifest()
	{
		$doc = $this->generateManifest();
		header('content-type: text/xml');
		header('content-disposition: attachment; filename="imsmanifest.xml"');
		return $this->transform($doc, self::DB_DECODE_XSL)->saveXML();
	}

	/**
	 */	
	public function uploadAndImport($packageFile)
	{
		$this->packageName = basename($packageFile);
		$this->packageHash = md5_file($packageFile);
		$this->packageFile = $this->packagesFolder . '/' . $this->packageHash . '.zip';
		$this->packageFolder = $this->packagesFolder . '/' . $this->packageHash;
		$this->imsmanifestFile = $this->packageFolder . '/' . 'imsmanifest.xml';

		// STEP 0
		$this->setProgress(0.1, 'Step 0: copy zip file: ' . $this->packageFile);
		@copy($packageFile, $this->packageFile);
		
		// STEP 1
		$this->setProgress(0.2, 'Step 1: unzip into temp folder: ' . $this->packageFile);
		if (file_exists($this->packageFolder)) 
		{
			$this->diagnostic[] = 'Folder already exists: ' . $this->packageFolder;
			//return false;
		} else 
		{
			@mkdir($this->packageFolder);
		}
		$e = ilSCORM13Utils::unzip($this->packageFile, $this->packageFolder);
		if ($e!==0) 
		{
			$this->diagnostic[] = 'unzip error:' . $e;
			return false;
		}
		
		// STEP 2		
		$this->setProgress(0.3, 'Step 2: load imsmanifest.xml: '. $this->imsmanifestFile);		
		$this->imsmanifest = new DOMDocument;
		$this->imsmanifest->async = false;
		if (!@$this->imsmanifest->load($this->imsmanifestFile))
		{
			$this->diagnostic[] = 'XML not wellformed';
			return false;
		}
		
		// STEP 3
		$this->setProgress(0.4, 'Step 3: normalize imsmanifest.xml using ' . self::DB_ENCODE_XSL);
		$this->manifest = $this->transform($this->imsmanifest, self::DB_ENCODE_XSL);
		if (!$this->manifest)
		{
			$this->diagnostic[] = 'Cannot transform into normalized manifest';
			return false;
		}
		
		// STEP 4
		$this->setProgress(0.5, 'Step 4: validate op-scorm13-manifest.xml against ' . self::VALIDATE_XSD);
		if (!$this->validate($this->manifest, self::VALIDATE_XSD))
		{
			$this->diagnostic[] = 'normalized XML is not conform to ' . self::VALIDATE_XSD;
			return false;
		}

		// STEP 5
		$this->setProgress(0.6, 'Step 5: Import into database');
		$this->dbAddNew(); // add new package record
		$this->dbRemoveAll(); // remove old data on this id
		
		ilSCORM13DB::begin();
		$this->dbImport($this->manifest);
		ilSCORM13DB::commit();

		// STEP 6
		// create json via simple xml
		$this->setProgress(0.7, 'Step 6: create player json data');
		$x = simplexml_load_string($this->manifest->saveXML());
		// add database values from package and sahs_lm records as defaults
		$x['persistPreviousAttempts'] = $this->packageData['persistPreviousAttempts'];
		$x['online'] = $this->packageData['online'];
		$x['defaultLessonMode'] = $this->packageData['default_lesson_mode'];
		$x['credit'] = $this->packageData['credit'];
		$x['autoReview'] = $this->packageData['auto_review'];
		$j = array();
		// first read resources into flat array to resolve item/identifierref later
		$r = array();
		foreach ($x->resource as $xe) 
		{
			$r[strval($xe['id'])] = $xe;
			unset($xe);
		}
		// iterate through items and set href and scoType as activity attributes 
		foreach ($x->xpath('//*[local-name()="item"]') as $xe) 
		{
			// get reference to resource and set href accordingly
			if ($b = $r[strval($xe['resourceId'])])
			{
				$xe['href'] = strval($b['base']) . strval($b['href']);
				unset($xe['resourceId']);
				if (strval($b['scormType'])=='sco') $xe['sco'] = true;
			} 
		}
		// iterate recursivly through activities and build up simple php object
		// with items and associated sequencings
		// top node is the default organization which is handled as an item
		self::jsonNode($x->organization, $j['item']);
		foreach($x->sequencing as $s) 
		{
			self::jsonNode($s, $j['sequencing'][]);
		}
		// combined manifest+resources xml:base is set as organization base
		$j['item']['base'] = strval($x['base']);
		// package folder is base to whole playing process
		$j['base'] = $packageFolder . '/';

		// STEP 7
		$this->setProgress(0.8, 'Step 7: Wrapping up');
		// save xml and json for further use in db
		ilSCORM13DB::setRecord('cp_package', array(
			'obj_id' => $this->packageId,
			'xmldata' => $x->asXML(),
			'jsdata' => json_encode($j),
		), 'obj_id');
		// rename temp files/folders
		$tf = $this->packagesFolder . '/' . $this->packageId . '.zip';
		if (is_file($tf)) 
		{
			unlink($tf);
		}
		@rename($this->packageFile, $tf);
		$tf = $this->packagesFolder . '/' . $this->packageId;
		if (is_dir($tf)) 
		{
			ilSCORM13Utils::dir_delete($tf);
		}
		@rename($this->packageFolder, $tf);
		
		// FINISH
		$this->setProgress(1.0, 'Done. Everything ok.');
		return true;		
	}
	
	/**
	 */	
	private function setProgress($progress, $msg = '')
	{
		$this->progress = $progress;
		$this->diagnostic[] = $msg;
	}
	
	/**
	 * Helper for UploadAndImport
	 * Recursively copies values from XML into PHP array for export as json
	 * Elements are translated into sub array, attributes into literals
	 * @param xml element to process
	 * @param reference to array object where to copy values
	 * @return void
	 */	
	private function jsonNode($node, &$sink)
	{
		foreach ($node->attributes() as $k => $v) 
		{
			$sink[$k] = strval($v);
		}
		foreach ($node->children() as $name => $child)
		{
			self::jsonNode($child, $sink[$name][]); // RECURSION
		}
	}

	private function dbImport($node, &$lft=1, $depth=1, $parent=0)
	{
		switch ($node->nodeType) 
		{
			case XML_DOCUMENT_NODE:
				// insert into cp_package 
				ilSCORM13DB::setRecord('cp_package', array(
					'obj_id' => $this->packageId, 
					'identifier' => $this->packageName, 
					'persistPreviousAttempts' => 0, 
					'settings' => '',
				));
				// run sub nodes
				$this->dbImport($node->documentElement); // RECURSION
				break;

			case XML_ELEMENT_NODE:
				if ($node->nodeName==='manifest')
				{
					if ($node->getAttribute('uri')=="") 
					{
						// default URI is md5 hash of zip file, i.e. packageHash  
						$node->setAttribute('uri', 'md5:' . $this->packageHash);
					}
				}
				// insert into cp_node
				$cp_node_id = ilSCORM13DB::setRecord('cp_node', array(
					'slm_id' => $this->packageId, 
					'nodeName' => $node->nodeName, 
				), 'cp_node_id');
				
				// insert into cp_tree
				ilSCORM13DB::setRecord('cp_tree', array(
					'child' => $cp_node_id,
					'depth' => $depth,
					'lft' => $lft++,
					'obj_id' => $this->packageId,
					'parent' => $parent,
					'rgt' => 0,
				));

				// insert into cp_*
				$a = array('cp_node_id' => $cp_node_id);
				foreach ($node->attributes as $attr) 
				{
					$a[$attr->name] = $attr->value;
				}
				ilSCORM13DB::setRecord('cp_' . $node->nodeName, $a);
				$node->setAttribute('foreignId', $cp_node_id);
				$this->idmap[$node->getAttribute('id')] = $cp_node_id; 
				
				// run sub nodes
				foreach($node->childNodes as $child)
				{
					$this->dbImport($child, $lft, $depth+1, $cp_node_id); // RECURSION
				}
				
				// update cp_tree (rgt value for pre order walk in sql tree)
				$q = 'UPDATE cp_tree SET rgt=' . $lft++ . ' WHERE child=' . $cp_node_id;
				ilSCORM13DB::exec($q);

				break;
		}
	}
	
	/**
	 * add new package record	
	 */	
	private function dbAddNew()
	{
		ilSCORM13DB::setRecord('cp_package', array(
			'obj_id' => $this->packageId,
		), 'obj_id');
		return true;
	}
	
	/**
	 * 	
	 */	
	private function dbRemoveAll()
	{
		// remove CP element entries 
		foreach (self::$elements['cp'] as $t) 
		{
			$t = 'cp_' . $t;
			$sql = "DELETE FROM $t WHERE $t.cp_node_id IN (SELECT cp_node.cp_node_id FROM cp_node WHERE cp_node.slm_id=$this->packageId)";
			ilSCORM13DB::exec($sql);
		} 
		// remove CMI entries 
		ilSCORM13DB::exec("DELETE FROM cmi_correct_response WHERE cmi_correct_response.cmi_interaction_id IN (SELECT cmi_interaction.cmi_interaction_id FROM cmi_interaction, cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_objective WHERE cmi_objective.cmi_interaction_id IN (SELECT cmi_interaction.cmi_interaction_id FROM cmi_interaction, cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_objective WHERE cmi_objective.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_interaction WHERE cmi_interaction.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_comment WHERE cmi_comment.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		ilSCORM13DB::exec("DELETE FROM cmi_node WHERE cmi_node.cmi_node_id IN (SELECT cmi_node.cmi_node_id FROM cmi_node, cp_node WHERE cp_node.slm_id=$this->packageId)");
		// remove CP structure entries in tree and node 
		ilSCORM13DB::exec("DELETE FROM cp_tree WHERE cp_tree.obj_id=$this->packageId"); 
		ilSCORM13DB::exec("DELETE FROM cp_node WHERE cp_node.slm_id=$this->packageId");
		// remove general package entry 
		ilSCORM13DB::exec("DELETE FROM cp_package WHERE cp_package.obj_id=$this->packageId");
	}
	
	public function transform($inputdoc, $xslfile, $outputpath = null)
	{
		$xsl = new DOMDocument;
		$xsl->async = false;
		if (!@$xsl->load($xslfile)) 
		{
			die('ERROR: load StyleSheet ' . $xslfile);
		}		
		$prc = new XSLTProcessor;
		$r = @$prc->importStyleSheet($xsl);
		if (false===@$prc->importStyleSheet($xsl)) 
		{
			die('ERROR: importStyleSheet ' . $xslfile);
		}
		if ($outputpath)
		{
			file_put_contents($outputpath, $prc->transformToXML($inputdoc));
		}
		else
		{
			return $prc->transformToDoc($inputdoc);
		}
	}
	
	public function validate($doc, $schema) 
	{
		libxml_use_internal_errors(true);
		$return = @$doc->schemaValidate($schema);
		if (!$return) 
		{
			$levels = array(
				LIBXML_ERR_ERROR => 'Error', 
				LIBXML_ERR_FATAL => 'Fatal Error'
			);
			foreach (libxml_get_errors() as $error) 
			{
				$level = $levels[$error->level];
				if (isset($level))
				{
					$message = trim($error->message);
					//$file = $error->file ? 'in <b>' . $error->file . '</b>' : '';
					// $error->code: 
					$this->diagnostic[] = "XSLT $level (Line $error->line) $message";
				}
			}
			libxml_clear_errors();
		}
		libxml_use_internal_errors(false);
		return $return;
	}
}

?>
