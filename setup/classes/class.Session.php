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

// Datei:       class.Session.inc
// Ben�tigt:    mind. 4.0.1pl2

/**
*   "Manueller" Session-Fallback mit PHP4
*
*   @author     Daniel T. Gorski <daniel.gorski@bluemars.de>
*/

class Session {
    var $version     = 106;     // V1.06
    var $usesCookies = false;   // Client nimmt Cookies an
    var $transSID    = false;   // Wurde mit --enable-trans-sid
                                // kompiliert  

//---------------------------------------------------------

/**
*   Konstruktor - nimmt, wenn gew�nscht einen neuen
*   Session-Namen entgegen
*/    
    function Session($sessionName="SESSID") {
        global $PHP_SELF;

        $this->sendNoCacheHeader();

        //  Session-Namen setzen, Session initialisieren   
        session_name(isset($sessionName)
            ? $sessionName
            : session_name());

        @session_start();
        
        //  Pr�fen ob die Session-ID die Standardl�nge
        //  von 32 Zeichen hat,
        //  ansonsten Session-ID neu setzen 
        if (strlen(session_id()) != 32)
            {
                mt_srand ((double)microtime()*1000000);
                session_id(md5(uniqid(mt_rand())));
            }
        
        //  Pr�fen, ob eine Session-ID �bergeben wurde
        //  (�ber Cookie, POST oder GET)
        $IDpassed = false;

        if  (   isset($_COOKIE[session_name()]) &&
                @strlen($_COOKIE[session_name()]) == 32
            )   $IDpassed = true;

        if  (   isset($_POST[session_name()]) &&
                @strlen($_POST[session_name()]) == 32
            )   $IDpassed = true;

        if  (   isset($_GET[session_name()]) &&
                @strlen($_GET[session_name()]) == 32
            )   $IDpassed = true;
        
        if  (!$IDpassed)  
            {   
                // Es wurde keine (g�ltige) Session-ID �bergeben.
                // Script-Parameter der URL zuf�gen
                
                $query = @$_SERVER["QUERY_STRING"] != "" ? "?".$_SERVER["QUERY_STRING"] : "";
             
                header("Status: 302 Found");
                
                // Script terminiert
                $this->redirectTo($PHP_SELF.$query);
            }
            
        // Wenn die Session-ID �bergeben wurde, mu� sie
        // nicht unbedingt g�ltig sein!
        
        // F�r weiteren Gebrauch merken    
        $this->usesCookies =
                       (isset($_COOKIE[session_name()]) &&
                        @strlen($_COOKIE[session_name()])
                        == 32);
    }    
 
### -------------------------------------------------------
/**
*   Cacheing unterbinden
*
*   Erg�nze/Override "session.cache_limiter = nocache"
*
*   @param  void
*   @return void
*/    
    function sendNoCacheHeader()    {        
        header("Expires: Sat, 05 Aug 2000 22:27:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Cache-Control: post-check=0, pre-check=0");        
    }

### -------------------------------------------------------
/**
*   HTTP-Redirect ausf�hren (header("Location: ...")
*
*   Diese Methode ber�cksichtigt auch nicht-standard Ports
*   und SSL. Ein GET-Parameter beim  wird bei Bedarf
*   (Session-ID-Fallback) an die URI drangeh�ngt. Nach
*   dem Aufruf dieser Methode wird das aktive Script
*   beendet und die Kontrolle wird an das Ziel-Script
*   �bergeben.
*
*   @param  string  Ziel-Datei (z.B. "index.php")
*   @return void
*/   
    function redirectTo($pathInfo) {
        
        // Relativer Pfad?
        if ($pathInfo[0] != "/")
            {   $pathInfo = substr(getenv("SCRIPT_NAME"),
                                   0,
                                   strrpos(getenv("SCRIPT_NAME"),"/")+1
                                   )
                            .$pathInfo;
            }

        // L�uft dieses Script auf einem non-standard Port? 
        $port    = !preg_match( "/^(80|443)$/",
                                getenv("SERVER_PORT"),
                                $portMatch)
                   ? ":".getenv("SERVER_PORT")
                   : "";
                                         
        // Redirect    
        header("Location: "
               .(($portMatch[1] == 443) ? "https://" : "http://")
               .$_SERVER["HTTP_HOST"].$port.$this->url($pathInfo));
        exit;
    }

### -------------------------------------------------------
/**
*   Entfernt m�gliche abschlie�ende "&" und "?"
*
*   @param  string  String
*   @return string  String ohne abschlie�ende "&" und "?"
*/
    function removeTrail($pathInfo) {
        $dummy = preg_match("/(.*)(?<!&|\?)/",$pathInfo,$match);
        return $match[0];  
    }

### -------------------------------------------------------
/**
*   Fallback via GET - wenn Cookies ausgeschaltet sind
*
*   @param  string  Ziel-Datei
*   @return string  Ziel-Datei mit - bei Bedarf - angeh�ngter Session-ID
*/
    function url($pathInfo)  {        
        if ($this->usesCookies || $this->transSID) return $pathInfo;

        // Anchor-Fragment extrahieren
        $dummyArray = split("#",$pathInfo);
        $pathInfo = $dummyArray[0];

        // evtl. (kaputte) Session-ID(s) aus dem Querystring entfernen
        $pathInfo = preg_replace(   "/[?|&]".session_name()."=[^&]*/",
                                    "",
                                    $pathInfo);
        
        // evtl. Query-Delimiter korrigieren
        if (preg_match("/&/",$pathInfo) && !preg_match("/\?/",$pathInfo))
            {
                // 4ter Parameter f�r "preg_replace()" erst ab 4.0.1pl2
                $pathInfo = preg_replace("/&/","?",$pathInfo,1); 
            }
        
        // Restm�ll entsorgen
        $pathInfo = $this->removeTrail($pathInfo);
        
        // Session-Name und Session-ID frisch hinzuf�gen  
        $pathInfo .= preg_match("/\?/",$pathInfo) ? "&" : "?";
        $pathInfo .= session_name()."=".session_id();
        
        // Anchor-Fragment wieder anf�gen
        $pathInfo .= isset($dummyArray[1]) ? "#".$dummyArray[1] : "";
        
        return $pathInfo;                       
    }
    
### -------------------------------------------------------
/**
*   Fallback via HIDDEN FIELD - wenn Cookies ausgeschaltet sind
*
*   Ohne Cookies erfolgt Fallback via HTML-Hidden-Field
*   (f�r Formulare)
*   
*   @param  void
*   @return string  HTML-Hidden-Input-Tag mit der Session-ID
*/
    function hidden() {
        if ($this->usesCookies || $this->transSID) return "";
        return "<INPUT  type=\"hidden\"
                        name=\"".session_name()."\"
                        value=\"".session_id()."\">";
    }
} // of class    
    
?>