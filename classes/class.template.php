<?php
require_once('HTML/ITX.php');

/**
* special template class to simplify handling of ITX/PEAR
* @author Stefan Kesseler <skesseler@databay.de>
* @author Sascha Hofmann <shofmann@databay.de>
* @version $Id$
* @package application
*/
class Template extends IntegratedTemplateExtension {

    /*
    * Konstruktor
    * @param string $file templatefile (mit oder ohne pfad)
    * @param bool $flag1 wie in IntegratedTemplate
    * @param bool $flag1 wie in IntegratedTemplate
    * @param array $vars zu ersetzenden Variablen
    * @access public
    */

    var $vars;

    /**
    *	Aktueller Block
    *	Der wird gemerkt bei der �berladenen Funktion setCurrentBlock, damit beim ParseBlock
    *	vorher ein replace auf alle Variablen gemacht werden kann, die mit dem BLockname anfangen.
    */
    var $activeBlock;

/**
* constructor
* @param string
* @param string
* @param string
* @param string
*/
   function Template($file,$flag1,$flag2,$vars="DEFAULT") {

        global $ilias;

        if ($vars == "DEFAULT" ) {
            $vars = $template_vars;
        }
        $this->vars = $vars;

        if (strpos($file,"/")===false) {
            $fname = $ilias->tplPath.basename($file);
        } else {
            $fname = $file;
        }
        if (!file_exists($fname)) {
            echo "template ".$fname." was not found.";
            return false;
        }

        $this->IntegratedTemplateExtension(dirname($fname));
        $this->LoadTemplatefile(basename($fname), $flag1, $flag2);

//         $this->replace($this->vars);

        return true;
    }

/**
* @param string
*/
    function get($part = "DEFAULT") {

//         $this->replace($this->vars);

        if ($part == "DEFAULT") {
            return parent::get();
        } else {
            return parent::get($part);
        }
    }

/**
* @param string
*/
    function show($part = "DEFAULT") {

        $this->replace($this->vars);

        if ($part == "DEFAULT") {
            parent::show();
        } else {
            parent::show($part);
        }


    }

    /**
    *	�berladene Funktion, die sich hier lokal noch den aktuellen Block merkt.
    * @param string
    */
    function setCurrentBlock($part = "DEFAULT") {
	    $this->activeBlock = $part;

        if ($part == "DEFAULT") {
            return parent::setCurrentBlock();
        } else {
            return parent::setCurrentBlock($part);
        }
    }

    /**
    *	�berladene Funktion, die auf den aktuelle Block vorher noch ein replace ausf�hrt
    * @param string
    */
    function parseCurrentBlock($part = "DEFAULT") {

	    // Hier erst noch ein replace aufrufen
        if ($part != "DEFAULT") {
	        $tmp = $this->activeBlock;
	        $this->activeBlock = $part;
		}

        if ($part != "DEFAULT") {
	        $this->activeBlock = $tmp;
		}

		$blockName = $this->activeBlock;
		if ( $blockName != "" ) $blockName .= "_";
        $this->replace( $this->vars , $blockName );


        if ($part == "DEFAULT") {
            return parent::parseCurrentBlock();
        } else {
            return parent::parseCurrentBlock($part);
        }
    }


    /**
    *		$block = "anzeige_loop";
            $conv = array("kd_pk"=>"kategorie_value",
                "name"=>"kategorie_text");
            $select = array("id"=>"kd_pk",
                "value"=>$herecopy["pd_kategorie"],
                "field"=>"kategorie_selected",
                "text"=>"selected"
                );
		* @param string
		* @param string
		* @param string
		* @param string
    */
    function replaceFromDatabase(&$DB,$block,$conv,$select="default") {

       $res = $DB->selectDbAll();
       while ($DB->getDbNextElement($res)) {
          $this->setCurrentBlock($block);
          $result = array();
          reset($conv);
          while (list ($key,$val) = each ($conv)) {
              $result[$val]=$DB->element->data[$key];
          }

          if (
                ($select != "default")
                &&
                (
                    $DB->element->data[$select["id"]]==$select["value"]
                    ||
                    (
                        strtolower($select["text"]) == "checked"
                        &&
                        strpos( ",,".$select["value"].",," , ",".$DB->element->data[$select["id"]]."," )!=false
                    )
                )
              ) {
              $result[$select["field"]] = $select["text"];
          }
          $this->replace($result);
          $this->parseCurrentBlock($block);
       }
    }

       /**
       *    Wird angewendet, wenn die Daten in ein Formular replaced werden sollen,
       *    Dann wird erst noch ein htmlspecialchars drumherum gemacht.
       * @param string
       */
    function prepareForFormular($vars) {
        if (!is_array($vars))  return;
        reset($vars);
        while(list($i) = each($vars)) {
            $vars[$i] = stripslashes($vars[$i]);
            $vars[$i] = htmlspecialchars($vars[$i]);
        }
        return($vars);
    }

    /**
    * @param string
    * @param string
    */
    function replace($vars,$Prefix = "DEFAULT") {
        if (!is_array($vars))  return;

        $vars = array_merge($vars,$this->vars);

        if ($Prefix == "DEFAULT") {
			$Prefix = $this->activeBlock;
			if ( $Prefix != "" ) $Prefix .= "_";
        }

        reset($vars);
        while(list($i) = each($vars)) {
            $this->setVariable($Prefix.$i,stripslashes($vars[$i]));
            $this->setVariable(strtoupper($Prefix.$i),stripslashes($vars[$i]));
        }
    }

    function replaceDefault() {
	    $this->replace($this->vars);
    }

}

?>