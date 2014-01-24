<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Services/Form/classes/class.ilTextInputGUI.php';

/**
 * @author  Michael Jansen <mjansen@databay.de>
 * @version $Id$
 * @ingroup ServicesMail
 */
class ilMailQuickFilterInputGUI extends ilTextInputGUI
{
	/**
	 * @return bool
	 */
	public function checkInput()
	{
		global $lng;

		$ok = parent::checkInput();

		$query = ilUtil::stripSlashes($_POST[$this->getPostVar()]);

		if(!$ok)
		{
			return false;
		}

		include_once 'Services/Mail/classes/class.ilMailLuceneQueryParser.php';
		try
		{
			ilMailLuceneQueryParser::validateQuery($query);
			return true;
		}
		catch(Exception $e)
		{
			$this->setAlert($lng->txt($e->getMessage()));
			return false;
		}
	}

	public function render($a_mode = "")
	{
		$tpl = new ilTemplate("tpl.prop_mail_quick_filter_input.html", true, true, "Services/Mail");
		if (strlen($this->getValue()))
		{
			$tpl->setCurrentBlock("prop_text_propval");
			$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($this->getValue()));
			$tpl->parseCurrentBlock();
		}
		if (strlen($this->getInlineStyle()))
		{
			$tpl->setCurrentBlock("stylecss");
			$tpl->setVariable("CSS_STYLE", ilUtil::prepareFormOutput($this->getInlineStyle()));
			$tpl->parseCurrentBlock();
		}
		if(strlen($this->getCssClass()))
		{
			$tpl->setCurrentBlock("classcss");
			$tpl->setVariable('CLASS_CSS', ilUtil::prepareFormOutput($this->getCssClass()));
			$tpl->parseCurrentBlock();
		}
		if ($this->getSubmitFormOnEnter())
		{
			$tpl->touchBlock("submit_form_on_enter");
		}

		switch($this->getInputType())
		{
			case 'password':
				$tpl->setVariable('PROP_INPUT_TYPE','password');
				break;
			case 'hidden':
				$tpl->setVariable('PROP_INPUT_TYPE','hidden');
				break;
			case 'text':
			default:
				$tpl->setVariable('PROP_INPUT_TYPE','text');
		}
		$tpl->setVariable("ID", $this->getFieldId());
		$tpl->setVariable("SIZE", $this->getSize());
		if($this->getMaxLength() != null)
			$tpl->setVariable("MAXLENGTH", $this->getMaxLength());
		if (strlen($this->getSuffix())) $tpl->setVariable("INPUT_SUFFIX", $this->getSuffix());

		$postvar = $this->getPostVar();
		if($this->getMulti() && substr($postvar, -2) != "[]")
		{
			$postvar .= "[]";
		}

		if ($this->getDisabled())
		{
			if($this->getMulti())
			{
				$value = $this->getMultiValues();
				$hidden = "";
				if(is_array($value))
				{
					foreach($value as $item)
					{
						$hidden .= $this->getHiddenTag($postvar, $item);
					}
				}
			}
			else
			{
				$hidden = $this->getHiddenTag($postvar, $this->getValue());
			}
			if($hidden)
			{
				$tpl->setVariable("DISABLED", " disabled=\"disabled\"");
				$tpl->setVariable("HIDDEN_INPUT", $hidden);
			}
		}
		else
		{
			$tpl->setVariable("POST_VAR", $postvar);
		}

		// use autocomplete feature?		
		if ($this->getDataSource())
		{
			include_once "Services/jQuery/classes/class.iljQueryUtil.php";
			iljQueryUtil::initjQuery();
			iljQueryUtil::initjQueryUI();

			if ($this->getMulti())
			{
				$tpl->setCurrentBlock("ac_multi");
				$tpl->setVariable('MURL_AUTOCOMPLETE', $this->getDataSource());
				$tpl->setVariable('ID_AUTOCOMPLETE', $this->getFieldId());
				$tpl->parseCurrentBlock();

				// set to fields that start with autocomplete selector
				$sel_auto = '[id^="'.$this->getFieldId().'"]';
			}
			else
			{
				// use id for autocomplete selector
				$sel_auto = "#".$this->getFieldId();
			}
			$tpl->setCurrentBlock("prop_text_autocomplete");
			$tpl->setVariable('SEL_AUTOCOMPLETE', $sel_auto);
			$tpl->setVariable('URL_AUTOCOMPLETE', $this->getDataSource());
			$tpl->parseCurrentBlock();
		}

		if ($a_mode == "toolbar")
		{
			// block-inline hack, see: http://blog.mozilla.com/webdev/2009/02/20/cross-browser-inline-block/
			// -moz-inline-stack for FF2
			// zoom 1; *display:inline for IE6 & 7
			$tpl->setVariable("STYLE_PAR", 'display: -moz-inline-stack; display:inline-block; zoom: 1; *display:inline;');
		}
		else
		{
			$tpl->setVariable("STYLE_PAR", '');
		}

		// multi icons
		if($this->getMulti() && !$a_mode && !$this->getDisabled())
		{
			$tpl->setVariable("MULTI_ICONS", $this->getMultiIconsHTML($this->multi_sortable));
		}

		if(is_array($this->sub_items) && $this->sub_items)
		{
			/**
			 * @var $lng ilLanguage
			 */
			global $lng;
			
			$tpl->setVariable("FIELD_ID", $this->getFieldId());
			$tpl->setVariable("TXT_PLACEHOLDER", $lng->txt('mail_filter_field_placeholder'));
			$tpl->setVariable("TXT_FILTER_MESSAGES_BY", $lng->txt('mail_filter_txt'));

			$subitem_html = '';
			foreach($this->sub_items as $item)
			{
				$subitem_html .= $item->render('toolbar');
			}

			$tpl->setVariable('FIELD_SUBITEMS', $subitem_html);
		}

		return $tpl->get();
	}
}
