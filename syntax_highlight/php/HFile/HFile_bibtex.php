<?php
global $BEAUT_PATH;
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_bibtex extends HFile{
   function HFile_bibtex(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// BibTeX
/*************************************/
// Flags

$this->nocase            	= "0";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "brown", "blue", "gray", "purple");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array();
$this->unindent          	= array();

// String characters and delimiters

$this->stringchars       	= array();
$this->delimiters        	= array("/", "D", "e", "l", "i", "m", "i", "t", "e", "r", "s", " ", "=", " ", "#", "$", "%", "&", "(", ")", "+", ",", "-", ".", " ", "{", "=", "}", " ", " ", " ", " ", "/", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ":", ";", "<", ">", "[", "]", "^", "_", "|", "~", "`");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array("%");
$this->blockcommenton    	= array("");
$this->blockcommentoff   	= array("");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"\\!" => "1", 
			"\\\"" => "1", 
			"\\&" => "1", 
			"\\\'" => "1", 
			"\\(" => "1", 
			"\\)" => "1", 
			"\\*" => "1", 
			"\\+" => "1", 
			"\\," => "1", 
			"\\-" => "1", 
			"\\." => "1", 
			"\\/" => "1", 
			"\\:" => "1", 
			"\\;" => "1", 
			"\\=" => "1", 
			"\\>" => "1", 
			"\\@" => "1", 
			"\\[" => "1", 
			"\\\\" => "1", 
			"\\]" => "1", 
			"\\^" => "1", 
			"\\_" => "1", 
			"=" => "8", 
			"{" => "8", 
			"}" => "8", 
			"," => "8", 
			"@article" => "5", 
			"@book" => "5", 
			"@booklet" => "5", 
			"@conference" => "5", 
			"@inbook" => "5", 
			"@incollection" => "5", 
			"@manual" => "5", 
			"@mastersthesis" => "5", 
			"@phdthesis" => "5", 
			"@proceedings" => "5", 
			"@techreport" => "5", 
			"@unpublished" => "5", 
			"@report" => "5", 
			"@thesis" => "5", 
			"@\\acute" => "5", 
			"\\a\'" => "3", 
			"\\a`" => "3", 
			"\\acute" => "3", 
			"\\aleph" => "3", 
			"\\alph" => "3", 
			"\\alpha" => "3", 
			"\\amalg" => "3", 
			"\\and" => "3", 
			"\\angle" => "3", 
			"\\approx" => "3", 
			"\\arabic" => "3", 
			"\\arccos" => "3", 
			"\\arcsin" => "3", 
			"\\arctan" => "3", 
			"\\arg" => "3", 
			"\\arraybackslash" => "3", 
			"\\arrowvert" => "3", 
			"\\ast" => "3", 
			"\\asymp" => "3", 
			"\\Arrowvert" => "3", 
			"\\backslash" => "3", 
			"\\bar" => "3", 
			"\\bf" => "3", 
			"\\bibitem" => "3", 
			"\\bigcap" => "3", 
			"\\bigcirc" => "3", 
			"\\bigcup" => "3", 
			"\\bigodot" => "3", 
			"\\bigoplus" => "3", 
			"\\bigotimes" => "3", 
			"\\bigsqcup" => "3", 
			"\\bigtriangledown" => "3", 
			"\\bigtriangleup" => "3", 
			"\\biguplus" => "3", 
			"\\bigvee" => "3", 
			"\\bigwedge" => "3", 
			"\\boldmath" => "3", 
			"\\boldsymbol" => "3", 
			"\\boolean" => "3", 
			"\\bot" => "3", 
			"\\botfigrule" => "3", 
			"\\bottomcaption" => "3", 
			"\\bottomfraction" => "3", 
			"\\bowtie" => "3", 
			"\\boxed" => "3", 
			"\\bracevert" => "3", 
			"\\branch" => "3", 
			"\\breve" => "3", 
			"\\bullet" => "3", 
			"\\Box" => "3", 
			"\\cal" => "3", 
			"\\cap" => "3", 
			"\\caption" => "3", 
			"\\cdot" => "3", 
			"\\cdots" => "3", 
			"\\cfrac" => "3", 
			"\\chi" => "3", 
			"\\circ" => "3", 
			"\\circle" => "3", 
			"\\circle*" => "3", 
			"\\cline" => "3", 
			"\\clubsuit" => "3", 
			"\\cong" => "3", 
			"\\coprod" => "3", 
			"\\copyright" => "3", 
			"\\cos" => "3", 
			"\\cosh" => "3", 
			"\\cot" => "3", 
			"\\coth" => "3", 
			"\\csc" => "3", 
			"\\cup" => "3", 
			"\\d" => "3", 
			"\\dag" => "3", 
			"\\dagger" => "3", 
			"\\dashbox" => "3", 
			"\\ddag" => "3", 
			"\\ddagger" => "3", 
			"\\ddot" => "3", 
			"\\ddots" => "3", 
			"\\deg" => "3", 
			"\\delta" => "3", 
			"\\det" => "3", 
			"\\diamond" => "3", 
			"\\diamondpar" => "3", 
			"\\diamondsuit" => "3", 
			"\\dim" => "3", 
			"\\displaystyle" => "3", 
			"\\dot" => "3", 
			"\\doteq" => "3", 
			"\\dotfill" => "3", 
			"\\dots" => "3", 
			"\\downarrow" => "3", 
			"\\Delta" => "3", 
			"\\Diamond" => "3", 
			"\\Downarrow" => "3", 
			"\\ell" => "3", 
			"\\em" => "3", 
			"\\emph" => "3", 
			"\\emptyset" => "3", 
			"\\enspace" => "3", 
			"\\epsilon" => "3", 
			"\\eqref" => "3", 
			"\\eta" => "3", 
			"\\exists" => "3", 
			"\\exp" => "3", 
			"\\fbox" => "3", 
			"\\flat" => "3", 
			"\\fnsymbol" => "3", 
			"\\forall" => "3", 
			"\\frac" => "3", 
			"\\frown" => "3", 
			"\\fussy" => "3", 
			"\\gamma" => "3", 
			"\\ge" => "3", 
			"\\geq" => "3", 
			"\\gets" => "3", 
			"\\gg" => "3", 
			"\\grave" => "3", 
			"\\Gamma" => "3", 
			"\\hat" => "3", 
			"\\heartsuit" => "3", 
			"\\hom" => "3", 
			"\\hookleftarrow" => "3", 
			"\\hookrightarrow" => "3", 
			"\\hspace" => "3", 
			"\\hspace*" => "3", 
			"\\huge" => "3", 
			"\\hyphenation" => "3", 
			"\\hyphenchar" => "3", 
			"\\Huge" => "3", 
			"\\idotsint" => "3", 
			"\\ignorespaces" => "3", 
			"\\imath" => "3", 
			"\\in" => "3", 
			"\\include" => "3", 
			"\\indent" => "3", 
			"\\inf" => "3", 
			"\\infty" => "3", 
			"\\input" => "3", 
			"\\int" => "3", 
			"\\iota" => "3", 
			"\\isucaption" => "3", 
			"\\it" => "3", 
			"\\itdefault" => "3", 
			"\\item" => "3", 
			"\\itemindent" => "3", 
			"\\itemsep" => "3", 
			"\\itshape" => "3", 
			"\\Im" => "3", 
			"\\jmath" => "3", 
			"\\jot" => "3", 
			"\\Join" => "3", 
			"\\kappa" => "3", 
			"\\ker" => "3", 
			"\\label" => "3", 
			"\\lambda" => "3", 
			"\\langle" => "3", 
			"\\large" => "3", 
			"\\lceil" => "3", 
			"\\ldots" => "3", 
			"\\le" => "3", 
			"\\leadsto" => "3", 
			"\\left" => "3", 
			"\\leftarrow" => "3", 
			"\\leftharpoondown" => "3", 
			"\\leftharpoonup" => "3", 
			"\\leftmargin" => "3", 
			"\\leftmark" => "3", 
			"\\leftrightarrow" => "3", 
			"\\leq" => "3", 
			"\\lfloor" => "3", 
			"\\lg" => "3", 
			"\\lgroup" => "3", 
			"\\lhd" => "3", 
			"\\lhead" => "3", 
			"\\lim" => "3", 
			"\\liminf" => "3", 
			"\\limits" => "3", 
			"\\limsup" => "3", 
			"\\line" => "3", 
			"\\linebreak" => "3", 
			"\\linethickness" => "3", 
			"\\linewidth" => "3", 
			"\\ll" => "3", 
			"\\lmoustache" => "3", 
			"\\ln" => "3", 
			"\\log" => "3", 
			"\\longleftarrow" => "3", 
			"\\longleftrightarrow" => "3", 
			"\\longmapsto" => "3", 
			"\\longrightarrow" => "3", 
			"\\LARGE" => "3", 
			"\\Lambda" => "3", 
			"\\Large" => "3", 
			"\\Leftarrow" => "3", 
			"\\Leftrightarrow" => "3", 
			"\\Longleftarrow" => "3", 
			"\\Longleftrightarrow" => "3", 
			"\\Longrightarrow" => "3", 
			"\\mapsto" => "3", 
			"\\mathbf" => "3", 
			"\\mathcal" => "3", 
			"\\mathindent" => "3", 
			"\\mathit" => "3", 
			"\\matrix" => "3", 
			"\\max" => "3", 
			"\\mid" => "3", 
			"\\min" => "3", 
			"\\mod" => "3", 
			"\\mp" => "3", 
			"\\mu" => "3", 
			"\\nabla" => "3", 
			"\\ne" => "3", 
			"\\nearrow" => "3", 
			"\\neg" => "3", 
			"\\neq" => "3", 
			"\\ni" => "3", 
			"\\nobreak" => "3", 
			"\\nolinebreak" => "3", 
			"\\normalsize" => "3", 
			"\\not" => "3", 
			"\\notin" => "3", 
			"\\nu" => "3", 
			"\\nwarrow" => "3", 
			"\\odot" => "3", 
			"\\oint" => "3", 
			"\\omega" => "3", 
			"\\ominus" => "3", 
			"\\oplus" => "3", 
			"\\oslash" => "3", 
			"\\otimes" => "3", 
			"\\oval" => "3", 
			"\\overbrace" => "3", 
			"\\overleftarrow" => "3", 
			"\\overline" => "3", 
			"\\overrightarrow" => "3", 
			"\\Omega" => "3", 
			"\\parallel" => "3", 
			"\\parbox" => "3", 
			"\\part" => "3", 
			"\\part*" => "3", 
			"\\partial" => "3", 
			"\\perp" => "3", 
			"\\phantom" => "3", 
			"\\phi" => "3", 
			"\\pi" => "3", 
			"\\pm" => "3", 
			"\\pmb" => "3", 
			"\\pmod" => "3", 
			"\\pod" => "3", 
			"\\prec" => "3", 
			"\\preceq" => "3", 
			"\\prime" => "3", 
			"\\prod" => "3", 
			"\\propto" => "3", 
			"\\psi" => "3", 
			"\\Phi" => "3", 
			"\\Pi" => "3", 
			"\\Pr" => "3", 
			"\\Psi" => "3", 
			"\\qquad" => "3", 
			"\\quad" => "3", 
			"\\rangle" => "3", 
			"\\rceil" => "3", 
			"\\ref" => "3", 
			"\\rfloor" => "3", 
			"\\rgroup" => "3", 
			"\\rhd" => "3", 
			"\\rho" => "3", 
			"\\right" => "3", 
			"\\rightarrow" => "3", 
			"\\rightharpoondown" => "3", 
			"\\rightharpoonup" => "3", 
			"\\rightmark" => "3", 
			"\\rm" => "3", 
			"\\rmoustache" => "3", 
			"\\roman" => "3", 
			"\\Re" => "3", 
			"\\Rightarrow" => "3", 
			"\\Roman" => "3", 
			"\\scshape" => "3", 
			"\\searrow" => "3", 
			"\\sec" => "3", 
			"\\secdef" => "3", 
			"\\sf" => "3", 
			"\\sharp" => "3", 
			"\\shortstack" => "3", 
			"\\sigma" => "3", 
			"\\sim" => "3", 
			"\\simeq" => "3", 
			"\\sin" => "3", 
			"\\sinh" => "3", 
			"\\sl" => "3", 
			"\\sloppy" => "3", 
			"\\small" => "3", 
			"\\smile" => "3", 
			"\\space" => "3", 
			"\\spadesuit" => "3", 
			"\\spcheck" => "3", 
			"\\sqcap" => "3", 
			"\\sqcup" => "3", 
			"\\sqrt" => "3", 
			"\\sqsubseteq" => "3", 
			"\\sqsupseteq" => "3", 
			"\\ss" => "3", 
			"\\stackrel" => "3", 
			"\\star" => "3", 
			"\\stepcounter" => "3", 
			"\\stretch" => "3", 
			"\\strut" => "3", 
			"\\subset" => "3", 
			"\\subseteq" => "3", 
			"\\subsubsection" => "3", 
			"\\succ" => "3", 
			"\\succeq" => "3", 
			"\\sum" => "3", 
			"\\sup" => "3", 
			"\\supset" => "3", 
			"\\supseteq" => "3", 
			"\\surd" => "3", 
			"\\swarrow" => "3", 
			"\\symbol" => "3", 
			"\\Sigma" => "3", 
			"\\tan" => "3", 
			"\\tanh" => "3", 
			"\\tau" => "3", 
			"\\texlmd" => "3", 
			"\\textbf" => "3", 
			"\\textit" => "3", 
			"\\textnormal" => "3", 
			"\\textrm" => "3", 
			"\\textsc" => "3", 
			"\\textsf" => "3", 
			"\\textsl" => "3", 
			"\\textstyle" => "3", 
			"\\texttt" => "3", 
			"\\textup" => "3", 
			"\\theta" => "3", 
			"\\tilde" => "3", 
			"\\times" => "3", 
			"\\tiny" => "3", 
			"\\top" => "3", 
			"\\triangle" => "3", 
			"\\triangleleft" => "3", 
			"\\triangleright" => "3", 
			"\\tt" => "3", 
			"\\twlrm" => "3", 
			"\\Theta" => "3", 
			"\\unboldmath" => "3", 
			"\\underbrace" => "3", 
			"\\underline" => "3", 
			"\\unlhd" => "3", 
			"\\unrhd" => "3", 
			"\\uparrow" => "3", 
			"\\updownarrow" => "3", 
			"\\uplus" => "3", 
			"\\uproot" => "3", 
			"\\uHFileape" => "3", 
			"\\upsilon" => "3", 
			"\\Uparrow" => "3", 
			"\\Updownarrow" => "3", 
			"\\Upsilon" => "3", 
			"\\vapace" => "3", 
			"\\varepsilon" => "3", 
			"\\varphi" => "3", 
			"\\varpi" => "3", 
			"\\varprojlim" => "3", 
			"\\varrho" => "3", 
			"\\varsigma" => "3", 
			"\\vartheta" => "3", 
			"\\vdash" => "3", 
			"\\vdots" => "3", 
			"\\vec" => "3", 
			"\\vector" => "3", 
			"\\vee" => "3", 
			"\\verb" => "3", 
			"\\vline" => "3", 
			"\\voffset" => "3", 
			"\\vrule" => "3", 
			"\\wedge" => "3", 
			"\\widehat" => "3", 
			"\\widetilde" => "3", 
			"\\wp" => "3", 
			"\\wr" => "3", 
			"\\xi" => "3", 
			"\\Xi" => "3", 
			"\\zeta" => "3", 
			"\\{" => "3", 
			"\\|" => "3", 
			"\\}" => "3", 
			"address" => "2", 
			"author" => "2", 
			"chapter" => "2", 
			"edition" => "2", 
			"editor" => "2", 
			"howpublished" => "2", 
			"journal" => "2", 
			"month" => "2", 
			"note" => "2", 
			"number" => "2", 
			"organization" => "2", 
			"pages" => "2", 
			"publisher" => "2", 
			"series" => "2", 
			"title" => "2", 
			"type" => "2", 
			"volume" => "2", 
			"year" => "2");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"8" => "donothing", 
			"5" => "donothing", 
			"3" => "donothing", 
			"2" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
