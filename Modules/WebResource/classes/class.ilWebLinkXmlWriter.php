<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./classes/class.ilXmlWriter.php";

/**
* XML writer for weblinks
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesWebResource
*/
class ilWebLinkXmlWriter extends ilXmlWriter
{
	private $obj_id = 0;
	private $weblink = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Set obj_id of weblink object
	 * @param int obj_id
	 * @return bool
	 */
	public function setObjId($a_obj_id)
	{
		$this->obj_id = $a_obj_id;
	}
	
	/**
	 * Write XML
	 * @return 
	 * @throws UnexpectedValueException Thrown if obj_id is not of type webr or no obj_id is given 
	 */
	public function write()
	{
		$this->init();
		$this->buildHeader();
		$this->weblink->toXML($this);
	}
	
	/**
	 * Build XML header
	 * @return 
	 */
	protected function buildHeader()
	{
		$this->xmlSetDtdDef("<!DOCTYPE WebLinks PUBLIC \"-//ILIAS//DTD WebLinkAdministration//EN\" \"".ILIAS_HTTP_PATH."/xml/ilias_weblinks_4_0.dtd\">");
		$this->xmlSetGenCmt("WebLink Object");
		$this->xmlHeader();

		return true;
	}
	
	
	/**
	 * Init xml writer
	 * @return bool
	 * @throws UnexpectedValueException Thrown if obj_id is not of type webr 
	 */
	protected function init()
	{
		$this->xmlClear();
		
		if(!$this->obj_id)
		{
			throw new UnexpectedValueException('No obj_id given: ');
		}
		include_once './classes/class.ilObjectFactory.php';
		if(!$this->weblink = ilObjectFactory::getInstanceByObjId($this->obj_id,false))
		{
			throw new UnexpectedValueException('Invalid obj_id given: '.$this->obj_id);
		}
		if($this->weblink->getType() != 'webr')
		{
			throw new UnexpectedValueException('Invalid obj_id given. Object is not of type webr');
		}
	}
}
?>
<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./classes/class.ilXmlWriter.php";

/**
* XML writer for weblinks
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesWebResource
*/
class ilWebLinkXmlWriter extends ilXmlWriter
{
	private $obj_id = 0;
	private $weblink = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Set obj_id of weblink object
	 * @param int obj_id
	 * @return bool
	 */
	public function setObjId($a_obj_id)
	{
		$this->obj_id = $a_obj_id;
	}
	
	/**
	 * Write XML
	 * @return 
	 * @throws UnexpectedValueException Thrown if obj_id is not of type webr or no obj_id is given 
	 */
	public function write()
	{
		$this->init();
		$this->buildHeader();
		$this->weblink->toXML($this);
	}
	
	/**
	 * Build XML header
	 * @return 
	 */
	protected function buildHeader()
	{
		$this->xmlSetDtdDef("<!DOCTYPE WebLinks PUBLIC \"-//ILIAS//DTD WebLinkAdministration//EN\" \"".ILIAS_HTTP_PATH."/xml/ilias_weblinks_4_0.dtd\">");
		$this->xmlSetGenCmt("WebLink Object");
		$this->xmlHeader();

		return true;
	}
	
	
	/**
	 * Init xml writer
	 * @return bool
	 * @throws UnexpectedValueException Thrown if obj_id is not of type webr 
	 */
	protected function init()
	{
		$this->xmlClear();
		
		if(!$this->obj_id)
		{
			throw new UnexpectedValueException('No obj_id given: ');
		}
		include_once './classes/class.ilObjectFactory.php';
		if(!$this->weblink = ilObjectFactory::getInstanceByObjId($this->obj_id,false))
		{
			throw new UnexpectedValueException('Invalid obj_id given: '.$this->obj_id);
		}
		if($this->weblink->getType() != 'webr')
		{
			throw new UnexpectedValueException('Invalid obj_id given. Object is not of type webr');
		}
	}
}
?>