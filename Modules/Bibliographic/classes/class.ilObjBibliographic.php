<?php

    /* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

    require_once "Services/Object/classes/class.ilObject2.php";
    require_once "Modules/Bibliographic/classes/class.ilBibliographicEntry.php";


    /* Declaring namespace for library RISReader */
    use \LibRIS\RISReader;

    /**
     * Class ilObjBibliographic
     *
     * @author Oskar Truffer <ot@studer-raimann.ch>, Gabriel Comte <gc@studer-raimann.ch>
     * @version $Id: class.ilObjBibliographic.php 2012-01-11 10:37:11Z otruffer $
     *
     * @extends ilObject2
     */
class ilObjBibliographic extends ilObject2
{

    /**
     * Id of literary articles
     * @var int
     */
    protected $filename;


    /**
     * Id of literary articles
     * @var ilBibliographicEntry[]
     */
    protected $entries;


    /**
     * Models describing how the overview of each entry is showed
     * @var overviewModels[]
     */
    protected $overviewModels;


    /**
     * Models describing how the overview of each entry is showed
     * @var is_online
     */
    protected $is_online;


    /**
     * initType
     * @return void
     */
    public function initType()
    {
        $this->type = "bibl";
    }

	/**
	 * If bibliographic object exists, read it's data from database, otherwise create it
	 *
	 * @param $existant_bibl_id int is not set when object is getting created
	 * @return \ilObjBibliographic
	 */
    public function __construct($existant_bibl_id = 0)
    {
        if($existant_bibl_id){
            $this->setId($existant_bibl_id);
            $this->doRead();
        }

        parent::__construct();
    }

    /**
     * Create object
     * @return void
     */
    function doCreate()
    {
        global $ilDB;

        $ilDB->manipulate("INSERT INTO il_bibl_data " . "(id, filename, is_online) VALUES (" .
            $ilDB->quote($this->getId(), "integer") . "," . // id
            $ilDB->quote($this->getFilename(), "text") . ","  . // filename
            $ilDB->quote($this->getOnline(), "integer") . // is_online
            ")");

    }

    function doRead()
    {

        global $ilDB;
        $set = $ilDB->query("SELECT * FROM il_bibl_data ".
            " WHERE id = ".$ilDB->quote($this->getId(), "integer")
        );
        while ($rec = $ilDB->fetchAssoc($set))
        {
            if(!$this->getFilename()){
                $this->setFilename($rec["filename"]);
            }
            $this->setOnline($rec['is_online']);
        }

    }


    /**
     * Update data
     */
    function doUpdate()
    {
        global $ilDB;

        if(!empty($_FILES['bibliographic_file']['name'])){
            $this->deleteFile();
            $this->doDelete(true);
            $this->moveFile();
        }

        $ilDB->manipulate("UPDATE il_bibl_data SET " .
            "filename = " . $ilDB->quote($this->getFilename(), "text") . ", " .// filename
            "is_online = " . $ilDB->quote($this->getOnline(), "integer") . // is_online
            " WHERE id = " . $ilDB->quote($this->getId(), "integer"));

        $this->writeSourcefileEntriesToDb($this);

    }

    /*
    * Delete data from db
    */
    function doDelete($leave_out_il_bibl_data = false)
    {
        global $ilDB;

        $this->deleteFile();

        //il_bibl_attribute
        $ilDB->manipulate("DELETE FROM il_bibl_attribute WHERE il_bibl_attribute.entry_id IN " .
                           "(SELECT il_bibl_entry.id FROM il_bibl_entry WHERE il_bibl_entry.data_id = " .$ilDB->quote($this->getId(), "integer") . ");");
        //il_bibl_entry
        $ilDB->manipulate("DELETE FROM il_bibl_entry WHERE data_id = " . $ilDB->quote($this->getId(), "integer"));

        if(!$leave_out_il_bibl_data){
            //il_bibl_data
            $ilDB->manipulate("DELETE FROM il_bibl_data WHERE id = " . $ilDB->quote($this->getId(), "integer"));
        }

	    // delete history entries
	    require_once("./Services/History/classes/class.ilHistory.php");
	    ilHistory::_removeEntriesForObject($this->getId());
    }

	/**
	 * @return string the folder is: $ILIAS-data-folder/bibl/$id
	 */
	public function getFileDirectory(){
		return ilUtil::getDataDir() . DIRECTORY_SEPARATOR . $this->getType() . DIRECTORY_SEPARATOR . $this->getId();
	}

    public function moveFile($file_to_copy = false){

        $target_dir = $this->getFileDirectory();

        if(!is_dir($target_dir)){
                ilUtil::makeDir($target_dir);
        }

        if($_FILES['bibliographic_file']['name']){
            $filename = $_FILES['bibliographic_file']['name'];
        }elseif($file_to_copy){
            //file is not uploaded, but a clone is made out of another bibl
            $split_path = explode(DIRECTORY_SEPARATOR, $file_to_copy);
	        $filename = $split_path[sizeof($split_path)-1];
        }else{
	        throw new Exception("Either a file must be delivered via \$_POST/\$_FILE or the file must be delivered via the method argument file_to_copy");
        }

		$target_full_filename = $target_dir . DIRECTORY_SEPARATOR . $filename;

        //If there is no file_to_copy (which is used for clones), copy the file from the temporary upload directory (new creation of object).
        //Therefore, a warning predicates nothing and can be suppressed.
        if(@!copy($file_to_copy, $target_full_filename)){
            ilUtil::moveUploadedFile($_FILES['bibliographic_file']['tmp_name'], $_FILES['bibliographic_file']['name'], $target_full_filename);
        }

        $this->setFilename($filename);
        ilUtil::sendSuccess($this->lng->txt("object_added"), true);
    }


    function  deleteFile(){
        $path = $this->getFilePath(true);
		self::__force_rmdir($path);
      }

    /**
     * @param bool $without_filename
     * @return array with all filepath
     */
     public function getFilePath($without_filename = false){
        global $ilDB;

        $set = $ilDB->query("SELECT filename FROM il_bibl_data ".
                " WHERE id = ".$ilDB->quote($this->getId(), "integer")
        );

        $rec = $ilDB->fetchAssoc($set);
        {
            if($without_filename){
                return substr($rec['filename'], 0, strrpos($rec['filename'], DIRECTORY_SEPARATOR));
            }else{
                return $rec['filename'];
            }

        }
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getFilename()
    {
        return $this->filename;
    }

	/**
	 * @return string returns the absolute filepath of the bib/ris file. it's build as follows: $ILIAS-data-folder/bibl/$id/$filename
	 */
	public function getFileAbsolutePath(){
		return $this->getFileDirectory().DIRECTORY_SEPARATOR.$this->getFilename();
	}


    public function getFiletype()
    {
	//return bib for filetype .bibtex:
	if(strtolower(substr($this->getFilename(), -6)) == "bibtex")
	{
	    return "bib";
	}
	//else return its true filetype
        return strtolower(substr($this->getFilename(), -3 ));
    }


    static function __getAllOverviewModels()
    {
        global $ilDB;

        $set = $ilDB->query('SELECT * FROM il_bibl_overview_model');
        while ($rec = $ilDB->fetchAssoc($set))
        {
            if($rec['literature_type']){
                $overviewModels[$rec['filetype']][$rec['literature_type']] = $rec['pattern'];
            }else{
                $overviewModels[$rec['filetype']] = $rec['pattern'];
            }
        }
        return $overviewModels;

    }

    /**
     * remove a directory recursively
     * @param $path
     * @return bool
     */
    protected static function __force_rmdir($path) {
        if (!file_exists($path)) return false;

        if (is_file($path) || is_link($path)) {
            return unlink($path);
        }

        if (is_dir($path)) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $result = true;

            $dir = new DirectoryIterator($path);

            foreach ($dir as $file) {
                if (!$file->isDot()) {
                    $result &= self::__force_rmdir($path . $file->getFilename(), false);
                }
            }

            $result &= rmdir($path);
            return $result;
        }
    }


    static function __readRisFile($full_filename){

        require_once "./Modules/Bibliographic/lib/LibRIS/src/LibRIS/RISReader.php";

        $ris_reader = new RISReader();
        $ris_reader->parseFile($full_filename);
        return $ris_reader->getRecords();
    }

    static function __readBibFile($full_filename){

        require_once 'Modules/Bibliographic/lib/PEAR_BibTex_1.0.0RC5/Structures/BibTex.php';

        $bibtex_reader = new Structures_BibTex();

        //Loading and parsing the file example.bib
        $ret=$bibtex_reader->loadFile($full_filename);

        $bibtex_reader->setOption("extractAuthors", false);
        $bibtex_reader->parse();

        // Remove library-bug: if there is no cite, the library mixes up the key for the type and the first attribute.
        // It also shows an empty and therefore unwanted cite in the array.
        //
        // The cite is the text coming right after the type. Example:
        // ﻿@book {cite,
        // author = { "...."},
        foreach($bibtex_reader->data as $key => $entry){
            if(empty($entry['cite'])){
                unset($bibtex_reader->data[$key]['cite']);

                foreach ($entry as $attr_key => $attribute){
                    if(strpos($attr_key, '{') !== false){
                        unset($bibtex_reader->data[$key][$attr_key]);
                        $attr_key_exploaded = explode('{', $attr_key);
                        $bibtex_reader->data[$key]['entryType'] = trim($attr_key_exploaded[0]);
                        $bibtex_reader->data[$key][trim($attr_key_exploaded[1])] = $attribute;
                    }
                }
            }
        }
        return $bibtex_reader->data;
    }


	/**
	 * Clone BIBL
	 *
	 * @param ilObjBibliographic $new_obj
	 * @param $a_target_id
	 * @param int $a_copy_id copy id
	 * @internal param \new $ilObjDataCollection object
	 * @return ilObjPoll
	 */
    public function doCloneObject(ilObjBibliographic $new_obj, $a_target_id, $a_copy_id = 0)
    {
        $new_obj->cloneStructure($this->getId());

        return $new_obj;
    }


	/**
	 * Attention only use this for objects who have not yet been created (use like: $x = new ilObjDataCollection; $x->cloneStructure($id))
	 * @param $original_id The original ID of the dataselection you want to clone it's structure
	 * @return void
	 */
    public function cloneStructure($original_id)
    {
        $original = new ilObjBibliographic($original_id);

        $this->moveFile($original->getFileAbsolutePath());

        $this->setOnline($original->getOnline());
        $this->setDescription($original->getDescription());
        $this->setTitle($original->getTitle());
        $this->setType($original->getType());

        $this->doUpdate();

        $this->writeSourcefileEntriesToDb();
    }


    protected static function __removeSpacesAndDashesAtBeginning($input){
        for($i = 0; $i < strlen($input); $i++){
            if($input[$i] != " " && $input[$i] != "-"){
                return substr($input, $i);
            }
        }

    }


    /**
     * Reads out the source file and writes all entries to the database
     * @return void
     */
    public function writeSourcefileEntriesToDb(){

        //Read File
        switch($this->getFiletype()){
            case("ris"):
                $entries_from_file = self::__readRisFile($this->getFileAbsolutePath());
                break;
            case("bib"):
                $entries_from_file = self::__readBibFile($this->getFileAbsolutePath());
                break;
        }

        //fill each entry into a ilBibliographicEntry object and then write it to DB by executing doCreate()
        foreach($entries_from_file as $file_entry){
            $type = null;
            $x = 0;
            $parsed_entry = array();

            foreach($file_entry as $key => $attribute){
                // if the attribute is an array, make a comma separated string out of it
                if(is_array($attribute)){
                    $attribute = implode(", ", $attribute);
                }

                // ty (RIS) or entryType (BIB) is the type and is treated seperately
                if(strtolower($key) == 'ty' || strtolower($key) == 'entrytype'){
                    $type = $attribute;
                    continue;
                }

                //TODO - Refactoring for ILIAS 4.5 - get rid off array restructuring
                //change array structure (name not as the key, but under the key "name")
                $parsed_entry[$x]['name'] = $key;
                $parsed_entry[$x++]['value'] = $attribute;
            }
            //create the entry and fill data into database by executing doCreate()
            $entry_model = new ilBibliographicEntry($this->getFiletype());
            $entry_model->setType($type);
            $entry_model->setAttributes($parsed_entry);
            $entry_model->setBibliographicObjId($this->getId());
            $entry_model->doCreate();
        }
    }




    /**
     * Set Online.
     *
     * @param	boolean	$a_online	Online
     * @return	void
     */
    function setOnline($a_online)
    {
        $this->is_online = $a_online;
    }


    /**
     * Get Online.
     *
     * @return	boolean	Online
     */
    function getOnline()
    {
        return $this->is_online;
    }

}
