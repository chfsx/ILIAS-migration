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

include_once("./classes/class.ilSaxParser.php");

/**
* QTI Parser
*
* @author Helmut Schottmüller <hschottm@gmx.de>
* @version $Id$
*
* @extends ilSaxParser
* @package assessment
*/
class ilQTIParser extends ilSaxParser
{
	var $lng;
	var $hasRootElement;
	var $path;
	var $items;
	var $item;
	var $depth;
	var $qti_element;
	var $in_presentation;
	var $in_response;
	var $render_type;
	var $response_label;
	var $material;
	var $matimage;
	var $response;
	var $resprocessing;
	var $outcomes;
	var $decvar;
	var $respcondition;
	var $setvar;
	var $displayfeedback;
	var $itemfeedback;
	var $flow_mat;
	var $flow;
	var $presentation;
	var $mattext;
	var $sametag;
	var $characterbuffer;
	var $conditionvar;
	
	/**
	* Constructor
	*
	* @param	string		$a_xml_file			xml file
	* @access	public
	*/
	function ilQTIParser($a_xml_file)
	{
		global $lng;

		parent::ilSaxParser($a_xml_file);

		$this->lng =& $lng;
		$this->hasRootElement = FALSE;
		$this->path = array();
		$this->items = array();
		$this->item = NULL;
		$this->depth = array();
		$this->qti_element = "";
		$this->in_presentation = FALSE;
		$this->in_reponse = FALSE;
		$this->render_type = NULL;
		$this->render_hotspot = NULL;
		$this->response_label = NULL;
		$this->material = NULL;
		$this->response = NULL;
		$this->matimage = NULL;
		$this->resprocessing = NULL;
		$this->outcomes = NULL;
		$this->decvar = NULL;
		$this->respcondition = NULL;
		$this->setvar = NULL;
		$this->displayfeedback = NULL;
		$this->itemfeedback = NULL;
		$this->flow_mat = array();
		$this->flow = 0;
		$this->presentation = NULL;
		$this->mattext = NULL;
		$this->sametag = FALSE;
		$this->characterbuffer = "";
	}

	/**
	* set event handler
	* should be overwritten by inherited class
	* @access	private
	*/
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	function startParsing()
	{
		parent::startParsing();
		return FALSE;
	}

	function getParent($a_xml_parser)
	{
		if ($this->depth[$a_xml_parser] > 0)
		{
			return $this->path[$this->depth[$a_xml_parser]-1];
		}
		else
		{
			return "";
		}
	}
	
	/**
	* handler for begin of element
	*/
	function handlerBeginTag($a_xml_parser,$a_name,$a_attribs)
	{
		$this->sametag = FALSE;
		$this->characterbuffer = "";
		$this->depth[$a_xml_parser]++;
		$this->path[$this->depth[$a_xml_parser]] = strtolower($a_name);
		$this->qti_element = $a_name;
		
		switch (strtolower($a_name))
		{
			case "flow":
				include_once ("./assessment/classes/class.ilQTIFlow.php");
				$this->flow++;
				break;
			case "flow_mat":
				include_once ("./assessment/classes/class.ilQTIFlowMat.php");
				array_push($this->flow_mat, new ilQTIFlowMat());
				break;
			case "itemfeedback":
				include_once ("./assessment/classes/class.ilQTIItemfeedback.php");
				$this->itemfeedback = new ilQTIItemfeedback();
				break;
			case "displayfeedback":
				include_once ("./assessment/classes/class.ilQTIDisplayfeedback.php");
				$this->displayfeedback = new ilQTIDisplayfeedback();
				break;
			case "setvar":
				include_once ("./assessment/classes/class.ilQTISetvar.php");
				$this->setvar = new ilQTISetvar();
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "action":
								$this->setvar->setAction($value);
								break;
							case "varname":
								$this->setvar->setVarname($value);
								break;
						}
					}
				}
				break;
			case "conditionvar":
				include_once ("./assessment/classes/class.ilQTIConditionvar.php");
				$this->conditionvar = new ilQTIConditionvar();
				break;
			case "not":
				if ($this->conditionvar != NULL)
				{
					$this->conditionvar->addNot();
				}
				break;
			case "and":
				if ($this->conditionvar != NULL)
				{
					$this->conditionvar->addAnd();
				}
				break;
			case "or":
				if ($this->conditionvar != NULL)
				{
					$this->conditionvar->addOr();
				}
				break;
			case "varequal":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_EQUAL);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "case":
								$this->responsevar->setCase($value);
								break;
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "varlt":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_LT);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "varlte":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_LTE);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "vargt":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_GT);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "vargte":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_GTE);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "varsubset":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_SUBSET);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "setmatch":
								$this->responsevar->setSetmatch($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "varinside":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_INSIDE);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "areatype":
								$this->responsevar->setAreatype($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "varsubstring":
				include_once("./assessment/classes/class.ilQTIResponseVar.php");
				$this->responsevar = new ilQTIResponseVar(RESPONSEVAR_SUBSTRING);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "case":
								$this->responsevar->setCase($value);
								break;
							case "respident":
								$this->responsevar->setRespident($value);
								break;
							case "index":
								$this->responsevar->setIndex($value);
								break;
						}
					}
				}
				break;
			case "respcondition":
				include_once("./assessment/classes/class.ilQTIRespcondition.php");
				$this->respcondition = new ilQTIRespcondition();
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "continue":
								$this->respcondition->setContinue($value);
								break;
							case "title":
								$this->respcondition->setTitle($value);
								break;
						}
					}
				}
				break;
			case "outcomes":
				include_once("./assessment/classes/class.ilQTIOutcomes.php");
				$this->outcomes = new ilQTIOutcomes();
				break;
			case "decvar":
				include_once("./assessment/classes/class.ilQTIDecvar.php");
				$this->decvar = new ilQTIDecvar();
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "varname":
								$this->decvar->setVarname($value);
								break;
							case "vartype":
								$this->decvar->setVartype($value);
								break;
							case "defaultval":
								$this->decvar->setDefaultval($value);
								break;
							case "minvalue":
								$this->decvar->setMinvalue($value);
								break;
							case "maxvalue":
								$this->decvar->setMaxvalue($value);
								break;
							case "members":
								$this->decvar->setMembers($value);
								break;
							case "cutvalue":
								$this->decvar->setCutvalue($value);
								break;
						}
					}
				}
				break;
			case "matimage":
				include_once("./assessment/classes/class.ilQTIMatimage.php");
				$this->matimage = new ilQTIMatimage();
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "imagtype":
								$this->matimage->setImagetype($value);
								break;
							case "label":
								$this->matimage->setLabel($value);
								break;
							case "height":
								$this->matimage->setHeight($value);
								break;
							case "width":
								$this->matimage->setWidth($value);
								break;
							case "uri":
								$this->matimage->setUri($value);
								break;
							case "embedded":
								$this->matimage->setEmbedded($value);
								break;
							case "x0":
								$this->matimage->setX0($value);
								break;
							case "y0":
								$this->matimage->setY0($value);
								break;
							case "entityref":
								$this->matimage->setEntityref($value);
								break;
						}
					}
				}
				break;
			case "material":
				include_once("./assessment/classes/class.ilQTIMaterial.php");
				$this->material = new ilQTIMaterial();
				$this->material->setFlow($this->flow);
				break;
			case "mattext":
				include_once ("./assessment/classes/class.ilQTIMattext.php");
				$this->mattext = new ilQTIMattext();
				break;
			case "questestinterop":
				$this->hasRootElement = TRUE;
				break;
			case "qticomment":
				break;
			case "objectbank":
				// not implemented yet
				break;
			case "assessment":
				break;
			case "section":
				break;
			case "presentation":
				$this->in_presentation = TRUE;
				include_once ("./assessment/classes/class.ilQTIPresentation.php");
				$this->presentation = new ilQTIPresentation();
				break;
			case "response_label":
				if ($this->render_type != NULL)
				{
				include_once("./assessment/classes/class.ilQTIResponseLabel.php");
					$this->response_label = new ilQTIResponseLabel();
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "rshuffle":
								$this->response_label->setRshuffle($value);
								break;
							case "rarea":
								$this->response_label->setRarea($value);
								break;
							case "rrange":
								$this->response_label->setRrange($value);
								break;
							case "labelrefid":
								$this->response_label->setLabelrefid($value);
								break;
							case "ident":
								$this->response_label->setIdent($value);
								break;
							case "match_group":
								$this->response_label->setMatchGroup($value);
								break;
							case "match_max":
								$this->response_label->setMatchMax($value);
								break;
						}
					}
				}
				break;
			case "render_choice":
				if ($this->in_response)
				{
					include_once("./assessment/classes/class.ilQTIRenderChoice.php");
					$this->render_type = new ilQTIRenderChoice();
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "shuffle":
								$this->render_type->setShuffle($value);
								break;
						}
					}
				}
				break;
			case "render_hotspot":
				if ($this->in_response)
				{
					include_once("./assessment/classes/class.ilQTIRenderHotspot.php");
					$this->render_type = new ilQTIRenderHotspot();
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "showdraw":
								$this->render_type->setShuffle($value);
								break;
							case "minnumber":
								$this->render_type->setMinnumber($value);
								break;
							case "maxnumber":
								$this->render_type->setMaxnumber($value);
								break;
						}
					}
				}
				break;
			case "render_fib":
				if ($this->in_response)
				{
					include_once("./assessment/classes/class.ilQTIRenderFib.php");
					$this->render_type = new ilQTIRenderFib();
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "encoding":
								$this->render_type->setEncoding($value);
								break;
							case "fibtype":
								$this->render_type->setFibtype($value);
								break;
							case "rows":
								$this->render_type->setRows($value);
								break;
							case "maxchars":
								$this->render_type->setMaxchars($value);
								break;
							case "prompt":
								$this->render_type->setPrompt($value);
								break;
							case "columns":
								$this->render_type->setColumns($value);
								break;
							case "charset":
								$this->render_type->setCharset($value);
								break;
							case "maxnumber":
								$this->render_type->setMaxnumber($value);
								break;
							case "minnumber":
								$this->render_type->setMinnumber($value);
								break;
						}
					}
				}
				break;
			case "response_lid":
				// Ordering Terms and Definitions    or
				// Ordering Terms and Pictures       or
				// Multiple choice single response   or
				// Multiple choice multiple response
			case "response_xy":
				// Imagemap question
			case "response_str":
				// Close question
			case "response_num":
			case "response_grp":
				// Matching terms and definitions
				// Matching terms and images
				switch (strtolower($a_name))
				{
					case "response_lid":
						$response_type = RT_RESPONSE_LID;
						break;
					case "response_xy":
						$response_type = RT_RESPONSE_XY;
						break;
					case "response_str":
						$response_type = RT_RESPONSE_STR;
						break;
					case "response_num":
						$response_type = RT_RESPONSE_NUM;
						break;
					case "response_grp":
						$response_type = RT_RESPONSE_GRP;
						break;
				}
				$this->in_response = TRUE;
				include_once("./assessment/classes/class.ilQTIResponse.php");
				$this->response = new ilQTIResponse($response_type);
				$this->response->setFlow($this->flow);
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "ident":
								$this->response->setIdent($value);
								break;
							case "rtiming":
								$this->response->setRTiming($value);
								break;
							case "rcardinality":
								$this->response->setRCardinality($value);
								break;
							case "numtype":
								$this->response->setNumtype($value);
								break;
						}
					}
				}
				break;
			case "item":
				include_once("./assessment/classes/class.ilQTIItem.php");
				$this->item =& $this->items[array_push($this->items, new ilQTIItem())-1];
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "ident":
								$this->item->setIdent($value);
								break;
							case "title":
								$this->item->setTitle($value);
								break;
						}
					}
				}
				break;
			case "resprocessing":
				include_once("./assessment/classes/class.ilQTIResprocessing.php");
				$this->resprocessing = new ilQTIResprocessing();
				if (is_array($a_attribs))
				{
					foreach ($a_attribs as $attribute => $value)
					{
						switch (strtolower($attribute))
						{
							case "scoremodel":
								$this->resprocessing->addScoremodel($value);
								break;
						}
					}
				}
				break;
		}
	}


	/**
	* handler for end of element
	*/
	function handlerEndTag($a_xml_parser,$a_name)
	{
		switch (strtolower($a_name))
		{
			case "flow":
				$this->flow--;
				break;
			case "flow_mat":
				if (count($this->flow_mat))
				{
					$flow_mat = array_pop($this->flow_mat);
					if (count($this->flow_mat))
					{
						$this->flow_mat[count($this->flow_mat)-1]->addFlow_mat($flow_mat);
					}
					else if ($this->itemfeedback != NULL)
					{
						$this->itemfeedback->addFlow_mat($flow_mat);
					}
					else if ($this->response_label != NULL)
					{
						$this->response_label->addFlow_mat($flow_mat);
					}
				}
				break;
			case "itemfeedback":
				if ($this->item != NULL)
				{
					if ($this->itemfeedback != NULL)
					{
						$this->item->addItemfeedback($this->itemfeedback);
					}
				}
				$this->itemfeedback = NULL;
				break;
			case "displayfeedback":
				if ($this->respcondition != NULL)
				{
					if ($this->displayfeedback != NULL)
					{
						$this->respcondition->addDisplayfeedback($this->displayfeedback);
					}
				}
				$this->displayfeedback = NULL;
				break;
			case "setvar":
				if ($this->respcondition != NULL)
				{
					if ($this->setvar != NULL)
					{
						$this->respcondition->addSetvar($this->setvar);
					}
				}
				$this->setvar = NULL;
				break;
			case "conditionvar":
				if ($this->respcondition != NULL)
				{
					$this->respcondition->setConditionvar($this->conditionvar);
				}
				$this->conditionvar = NULL;
				break;
			case "varequal":
			case "varlt":
			case "varlte":
			case "vargt":
			case "vargte":
			case "varsubset":
			case "varinside":
			case "varsubstring":
				if ($this->conditionvar != NULL)
				{
					if ($this->responsevar != NULL)
					{
						$this->conditionvar->addResponseVar($this->responsevar);
					}
				}
				$this->responsevar = NULL;
				break;
			case "respcondition":
				if ($this->resprocessing != NULL)
				{
					$this->resprocessing->addRespcondition($this->respcondition);
				}
				$this->respcondition = NULL;
				break;
			case "outcomes":
				if ($this->resprocessing != NULL)
				{
					$this->resprocessing->setOutcomes($this->outcomes);
				}
				$this->outcomes = NULL;
				break;
			case "decvar":
				if ($this->outcomes != NULL)
				{
					$this->outcomes->addDecvar($this->decvar);
				}
				$this->decvar = NULL;
				break;
			case "presentation":
				$this->in_presentation = FALSE;
				if ($this->presentation != NULL)
				{
					if ($this->item != NULL)
					{
						$this->item->setPresentation($this->presentation);
					}
				}
				$this->presentation = NULL;
				break;
			case "response_label":
				if ($this->render_type != NULL)
				{
					$this->render_type->addResponseLabel($this->response_label);
					$this->response_label = NULL;
				}
				break;
			case "render_choice":
			case "render_hotspot":
			case "render_fib":
				if ($this->in_response)
				{
					if ($this->response != NULL)
					{
						if ($this->render_type != NULL)
						{
							$this->response->setRenderType($this->render_type);
							$this->render_type = NULL;
						}
					}
				}
				break;
			case "response_lid":
			case "response_xy":
			case "response_str":
			case "response_num":
			case "response_grp":
				if ($this->presentation != NULL)
				{
					if ($this->response != NULL)
					{
						$this->presentation->addResponse($this->response);
						if ($this->item != NULL)
						{
							$this->item->addPresentationitem($this->response);
						}
					}
				}
				$this->response = NULL;
				$this->in_response = FALSE;
				break;
			case "item":
				global $ilDB;
				global $ilUser;
				// save the item directly to save memory
				// the database id's of the created items are exported. if the import fails
				// ILIAS can delete the already imported items
				
				// problems: the object id of the parent questionpool is not yet known. must be set later
				//           the complete flag must be calculated?
				$qt = $this->item->determineQuestionType();
				$questionpool_id = 99999;
				switch ($qt)
				{
					case QT_UNKNOWN:
						// houston we have a problem
						break;
					case QT_MULTIPLE_CHOICE_SR:
					case QT_MULTIPLE_CHOICE_MR:
						$duration = $this->item->getDuration();
						$questiontext = array();
						$shuffle = 0;
						$now = getdate();
						$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
						$answers = array();
						foreach ($this->item->getPresentation()->order as $entry)
						{
							switch ($entry["type"])
							{
								case "material":
									$material = $this->item->getPresentation()->material[$entry["index"]];
									if (count($material->mattext))
									{
										foreach ($material->mattext as $mattext)
										{
											array_push($questiontext, $mattext->getContent());
										}
									}
									break;
								case "response":
									$response = $this->item->getPresentation()->response[$entry["index"]];
									switch (get_class($response->getRenderType()))
									{
										case "ilQTIRenderChoice":
											$shuffle = $response->getRenderType()->getShuffle();
											$answerorder = 0;
											foreach ($response->getRenderType()->response_labels as $response_label)
											{
												$ident = $response_label->getIdent();
												$answertext = "";
												foreach ($response_label->material as $mat)
												{
													foreach ($mat->mattext as $matt)
													{
														$answertext .= $matt->getContent();
													}
												}
												$answers[$ident] = array(
													"answertext" => $answertext,
													"points" => 0,
													"answerorder" => $answerorder++,
													"correctness" => "",
													"action" => ""
												);
											}
											break;
									}
									break;
							}
						}
						$responses = array();
						foreach ($this->item->resprocessing as $resprocessing)
						{
							foreach ($resprocessing->respcondition as $respcondition)
							{
								$ident = "";
								$correctness = 1;
								$conditionvar = $respcondition->getConditionvar();
								foreach ($conditionvar->order as $order)
								{
									switch ($order["field"])
									{
										case "arr_not":
											$correctness = 0;
											break;
										case "varequal":
											$ident = $conditionvar->varequal[$order["index"]]->getContent();
											break;
									}
								}
								foreach ($respcondition->setvar as $setvar)
								{
									if (strcmp($ident, "") != 0)
									{
										$answers[$ident]["correctness"] = $correctness;
										$answers[$ident]["action"] = $setvar->getAction();
										$answers[$ident]["points"] = $setvar->getContent();
									}
								}
							}
						}
						include_once ("./assessment/classes/class.assMultipleChoice.php");
						$type = 1;
						if ($qt == QT_MULTIPLE_CHOICE_SR)
						{
							$type = 0;
						}
						$question = new ASS_MultipleChoice(
							$this->item->getTitle(),
							$this->item->getComment(),
							$this->item->getAuthor(),
							$ilUser->id,
							join($questiontext, ""),
							$type
						);
						$question->setObjId($questionpool_id);
						$question->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);
						$question->setShuffle($shuffle);
						foreach ($answers as $answer)
						{
							$question->addAnswer($answer["answertext"], $answer["points"], $answer["answerorder"], $answer["correctness"]);
						}
						$question->saveToDb();
						break;
					case QT_CLOZE:
						$duration = $this->item->getDuration();
						$questiontext = array();
						$shuffle = 0;
						$now = getdate();
						$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
						$gaps = array();
						foreach ($this->item->getPresentation()->order as $entry)
						{
							switch ($entry["type"])
							{
								case "material":
									$material = $this->item->getPresentation()->material[$entry["index"]];
									if (count($material->mattext))
									{
										foreach ($material->mattext as $mattext)
										{
											array_push($questiontext, $mattext->getContent());
										}
									}
									break;
								case "response":
									$response = $this->item->getPresentation()->response[$entry["index"]];
									array_push($questiontext, "<<" . $response->getIdent() . ">>");
									switch (get_class($response->getRenderType()))
									{
										case "ilQTIRenderFib":
											array_push($gaps, array("ident" => $response->getIdent(), "type" => "text", "answers" => array()));
											break;
										case "ilQTIRenderChoice":
											$answers = array();
											$shuffle = $response->getRenderType()->getShuffle();
											$answerorder = 0;
											foreach ($response->getRenderType()->response_labels as $response_label)
											{
												$ident = $response_label->getIdent();
												$answertext = "";
												foreach ($response_label->material as $mat)
												{
													foreach ($mat->mattext as $matt)
													{
														$answertext .= $matt->getContent();
													}
												}
												$answers[$ident] = array(
													"answertext" => $answertext,
													"points" => 0,
													"answerorder" => $answerorder++,
													"action" => "",
													"shuffle" => $response->getRenderType()->getShuffle()
												);
											}
											array_push($gaps, array("ident" => $response->getIdent(), "type" => "choice", "shuffle" => $response->getRenderType()->getShuffle(), "answers" => $answers));
											break;
									}
									break;
							}
						}
						$responses = array();
						foreach ($this->item->resprocessing as $resprocessing)
						{
							foreach ($resprocessing->respcondition as $respcondition)
							{
								$ident = "";
								$correctness = 1;
								$conditionvar = $respcondition->getConditionvar();
								foreach ($conditionvar->order as $order)
								{
									switch ($order["field"])
									{
										case "varequal":
											$equals = $conditionvar->varequal[$order["index"]]->getContent();
											$gapident = $conditionvar->varequal[$order["index"]]->getRespident();
											break;
									}
								}
								foreach ($respcondition->setvar as $setvar)
								{
									if (strcmp($gapident, "") != 0)
									{
										foreach ($gaps as $gi => $g)
										{
											if (strcmp($g["ident"], $gapident) == 0)
											{
												if (strcmp($g["type"], "choice") == 0)
												{
													foreach ($gaps[$gi]["answers"] as $ai => $answer)
													{
														if (strcmp($answer["answertext"], $equals) == 0)
														{
															$gaps[$gi]["answers"][$ai]["action"] = $setvar->getAction();
															$gaps[$gi]["answers"][$ai]["points"] = $setvar->getContent();
														}
													}
												}
												else if (strcmp($g["type"], "text") == 0)
												{
													array_push($gaps[$gi]["answers"], array(
														"answertext" => $equals,
														"points" => $setvar->getContent(),
														"answerorder" => count($gaps[$gi]["answers"]),
														"action" => $setvar->getAction(),
														"shuffle" => 1
													));
												}
											}
										}
									}
								}
							}
						}
						include_once ("./assessment/classes/class.assClozeTest.php");
						$question = new ASS_ClozeTest(
							$this->item->getTitle(),
							$this->item->getComment(),
							$this->item->getAuthor(),
							$ilUser->id
						);
						$question->setObjId($questionpool_id);
						$question->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);
						$gaptext = array();
						foreach ($gaps as $gapidx => $gap)
						{
							$gapcontent = array();
							$type = 0;
							$typetext = "text";
							$shuffletext = "";
							if (strcmp($gap["type"], "choice") == 0)
							{
								$type = 1;
								$typetext = "select";
								if ($gap["shuffle"] == 0)
								{
									$shuffletext = "  shuffle=\"no\"";
								}
								else
								{
									$shuffletext = "  shuffle=\"yes\"";
								}
							}
							foreach ($gap["answers"] as $index => $answer)
							{
								$question->addAnswer($gapidx, $answer["answertext"], $answer["points"], $answer["answerorder"], 1, $type, $gap["ident"], $answer["shuffle"]);
								array_push($gapcontent, $answer["answertext"]);
							}
							$gaptext[$gap["ident"]] = "<gap type=\"$typetext\" name=\"" . $gap["ident"] . "\"$shuffletext>" . join(",", $gapcontent). "</gap>";
						}
						$clozetext = join("", $questiontext);
						foreach ($gaptext as $idx => $val)
						{
							$clozetext = str_replace("<<" . $idx . ">>", $val, $clozetext);
						}
						$question->cloze_text = $clozetext;
						$question->saveToDb();
						break;
					case QT_MATCHING:
						$duration = $this->item->getDuration();
						$questiontext = array();
						$shuffle = 0;
						$now = getdate();
						$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
						$terms = array();
						$matches = array();
						$foundimage = FALSE;
						foreach ($this->item->getPresentation()->order as $entry)
						{
							switch ($entry["type"])
							{
								case "material":
									$material = $this->item->getPresentation()->material[$entry["index"]];
									if (count($material->mattext))
									{
										foreach ($material->mattext as $mattext)
										{
											array_push($questiontext, $mattext->getContent());
										}
									}
									break;
								case "response":
									$response = $this->item->getPresentation()->response[$entry["index"]];
									switch (get_class($response->getRenderType()))
									{
										case "ilQTIRenderChoice":
											$shuffle = $response->getRenderType()->getShuffle();
											$answerorder = 0;
											foreach ($response->getRenderType()->response_labels as $response_label)
											{
												$ident = $response_label->getIdent();
												$answertext = "";
												$answerimage = array();
												foreach ($response_label->material as $mat)
												{
													foreach ($mat->mattext as $matt)
													{
														$answertext .= $matt->getContent();
													}
													foreach ($mat->matimage as $matimage)
													{
														$foundimage = TRUE;
														$answerimage = array(
															"imagetype" => $matimage->getImageType(),
															"label" => $matimage->getLabel(),
															"content" => $matimage->getContent()
														);
													}
												}
												if (($response_label->getMatchMax() == 1) && (strlen($response_label->getMatchGroup())))
												{
													$terms[$ident] = array(
														"answertext" => $answertext,
														"answerimage" => $answerimage,
														"points" => 0,
														"answerorder" => $ident,
														"action" => ""
													);
												}
												else
												{
													$matches[$ident] = array(
														"answertext" => $answertext,
														"answerimage" => $answerimage,
														"points" => 0,
														"matchingorder" => $ident,
														"action" => ""
													);
												}
											}
											break;
									}
									break;
							}
						}
						$responses = array();
						foreach ($this->item->resprocessing as $resprocessing)
						{
							foreach ($resprocessing->respcondition as $respcondition)
							{
								$subset = array();
								$correctness = 1;
								$conditionvar = $respcondition->getConditionvar();
								foreach ($conditionvar->order as $order)
								{
									switch ($order["field"])
									{
										case "varsubset":
											$subset = split(",", $conditionvar->varsubset[$order["index"]]->getContent());
											break;
									}
								}
								foreach ($respcondition->setvar as $setvar)
								{
									array_push($responses, array("subset" => $subset, "action" => $setvar->getAction(), "points" => $setvar->getContent())); 
								}
							}
						}
						include_once ("./assessment/classes/class.assMatchingQuestion.php");
						$type = 1;
						if ($foundimage)
						{
							$type = 0;
						}
						$question = new ASS_MatchingQuestion(
							$this->item->getTitle(),
							$this->item->getComment(),
							$this->item->getAuthor(),
							$ilUser->id,
							join($questiontext, ""),
							$type
						);
						$question->setObjId($questionpool_id);
						$question->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);
						$question->setShuffle($shuffle);
						foreach ($responses as $response)
						{
							$subset = $response["subset"];
							$term = array();
							$match = array();
							foreach ($subset as $ident)
							{
								if (array_key_exists($ident, $terms))
								{
									$term = $terms[$ident];
								}
								if (array_key_exists($ident, $matches))
								{
									$match = $matches[$ident];
								}
							}
							if ($type == 0)
							{
								$question->addMatchingPair($term["answerimage"]["label"], $response["points"], $term["answerorder"], $match["answertext"], $match["matchingorder"]);
							}
							else
							{
								$question->addMatchingPair($term["answertext"], $response["points"], $term["answerorder"], $match["answertext"], $match["matchingorder"]);
							}
						}
						$question->saveToDb();
						foreach ($responses as $response)
						{
							$subset = $response["subset"];
							$term = array();
							$match = array();
							foreach ($subset as $ident)
							{
								if (array_key_exists($ident, $terms))
								{
									$term = $terms[$ident];
								}
								if (array_key_exists($ident, $matches))
								{
									$match = $matches[$ident];
								}
							}
							if ($type == 0)
							{
								$image =& base64_decode($term["answerimage"]["content"]);
								$imagepath = $question->getImagePath();
								if (!file_exists($imagepath))
								{
									ilUtil::makeDirParents($imagepath);
								}
								$imagepath .=  $term["answerimage"]["label"];
								$fh = fopen($imagepath, "wb");
								if ($fh == false)
								{
//									global $ilErr;
//									$ilErr->raiseError($this->lng->txt("error_save_image_file") . ": $php_errormsg", $ilErr->MESSAGE);
//									return;
								}
								else
								{
									$imagefile = fwrite($fh, $image);
									fclose($fh);
								}
								// create thumbnail file
								$thumbpath = $imagepath . "." . "thumb.jpg";
								ilUtil::convertImage($imagepath, $thumbpath, "JPEG", 100);
							}
						}
						break;
					case QT_ORDERING:
						break;
					case QT_IMAGEMAP:
						break;
					case QT_JAVAAPPLET:
						break;
					case QT_TEXT:
						break;
				}
				break;
			case "material":
				if (strcmp($this->material->getLabel(), "suggested_solution") == 0)
				{
					$this->item->addSuggestedSolution($this->material->mattext[0]);
				}
				else if (($this->render_type != NULL) && (strcmp(strtolower($this->getParent($a_xml_parser)), "render_hotspot") == 0))
				{
					$this->render_type->addMaterial($this->material);
				}
				else if (count($this->flow_mat) && (strcmp(strtolower($this->getParent($a_xml_parser)), "flow_mat") == 0))
				{
					$this->flow_mat[count($this->flow_mat)-1]->addMaterial($this->material);
				}
				else if ($this->itemfeedback != NULL)
				{
					$this->itemfeedback->addMaterial($this->material);
				}
				else if ($this->response_label != NULL)
				{
					$this->response_label->addMaterial($this->material);
				}
				else if ($this->response != NULL)
				{
					if ($this->response->hasRendering())
					{
						$this->response->setMaterial2($this->material);
					}
					else
					{
						$this->response->setMaterial1($this->material);
					}
				}
				else if ($this->presentation != NULL)
				{
					$this->presentation->addMaterial($this->material);
					if ($this->item != NULL)
					{
						$this->item->addPresentationitem($this->material);
					}
				}
				$this->material = NULL;
				break;
			case "matimage";
				if ($this->material != NULL)
				{
					if ($this->matimage != NULL)
					{
						$this->material->addMatimage($this->matimage);
					}
				}
				$this->matimage = NULL;
				break;
			case "resprocessing":
				if ($this->item != NULL)
				{
					$this->item->addResprocessing($this->resprocessing);
				}
				$this->resprocessing = NULL;
				break;
			case "mattext":
				if ($this->material != NULL)
				{
					$this->material->addMattext($this->mattext);
				}
				$this->mattext = NULL;
		}
		$this->depth[$a_xml_parser]--;
	}

	/**
	* handler for character data
	*/
	function handlerCharacterData($a_xml_parser,$a_data)
	{
		$this->characterbuffer .= $a_data;
		$a_data = $this->characterbuffer;
		switch ($this->qti_element)
		{
			case "response_label":
				if ($this->response_label != NULL)
				{
					$this->response_label->setContent($a_data);
				}
				break;
			case "setvar":
				if ($this->setvar != NULL)
				{
					$this->setvar->setContent($a_data);
				}
				break;
			case "displayfeedback":
				if ($this->displayfeedback != NULL)
				{
					$this->displayfeedback->setContent($a_data);
				}
				break;
			case "varequal":
			case "varlt":
			case "varlte":
			case "vargt":
			case "vargte":
			case "varsubset":
			case "varinside":
			case "varsubstring":
				if ($this->responsevar != NULL)
				{
					$this->responsevar->setContent($a_data);
				}
				break;
			case "decvar":
				if (strlen($a_data))
				{
					if ($this->outcomes != NULL)
					{
						$this->outcomes->setContent($a_data);
					}
				}
				break;
			case "mattext":
				if ($this->mattext != NULL)
				{
					$this->mattext->setContent($a_data);
				}
				if (($this->in_presentation) && (!$this->in_response))
				{
					// question text
					$this->item->setQuestiontext($a_data);
				}
				break;
			case "matimage":
				$this->matimage->setContent($a_data);
				break;
			case "duration":
				switch ($this->getParent($a_xml_parser))
				{
					case "assessment":
						// to be done
						break;
					case "section":
						// to be done
						break;
					case "item":
						$this->item->setDuration($a_data);
						break;
				}
				break;
			case "qticomment":
				switch ($this->getParent($a_xml_parser))
				{
					case "item":
						$this->item->setComment($a_data);
						break;
					default:
						break;
				}
				break;
		}
		$this->sametag = TRUE;
	}

}
?>
