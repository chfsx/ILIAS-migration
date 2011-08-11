<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateFilterPattern.php';

/**
 * Implementation of an include filter pattern for didactic template actions
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @ingroup ServicesDidacticTemplate
 */
class ilDidacticTemplateIncludeFilterPattern extends ilDidacticTemplateFilterPattern
{

	/**
	 * Constructor
	 * @param int $a_pattern_id 
	 */
	public function __construct($a_pattern_id = 0)
	{
		parent::__construct($a_pattern_id);
		$this->setPatternType(self::PATTERN_INCLUDE);
	}

	/**
	 * Check if patttern matches
	 */
	public function matches($a_source)
	{
		return true;
	}

	/**
	 * Write xml
	 * @param ilXmlWriter $writer
	 */
	public function toXml(ilXmlWriter $writer)
	{
		switch($this->getPatternSubType())
		{
			case ilDidacticTemplateFilterPattern::PATTERN_SUBTYPE_REGEX:
			default:

				$writer->xmlElement(
					'includePattern',
					array(
						'preg'	=> $this->getPattern()
					)
				);
		}
	}
}
?>