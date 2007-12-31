<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

define ("IL_WIKI_MODE_REPLACE", "replace");
define ("IL_WIKI_MODE_COLLECT", "collect");

/**
* Utility class for wiki.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ModulesWiki
*/
class ilWikiUtil
{

	/**
	* This one is based on Mediawiki Parser->replaceInternalLinks
	* since we display images in another way, only text links are processed
	*
	* @param	string		input string
	* @param	string		input string
	*
	* @return	string		output string
	*/
	static function replaceInternalLinks($s, $a_wiki_id)
	{
		return ilWikiUtil::processInternalLinks($s, $a_wiki_id);
	}

	/**
	* Collect internal wiki links of a string
	*
	* @param	string		input string
	* @return	string		output string
	*/
	static function collectInternalLinks($s, $a_wiki_id)
	{
		return ilWikiUtil::processInternalLinks($s, $a_wiki_id, IL_WIKI_MODE_COLLECT);
	}
	
	/**
	* Process internal links
	*
	* string		$s				string that includes internal wiki links
	* int			$a_wiki_id		wiki id
	* mode
	*/
	static function processInternalLinks($s, $a_wiki_id,
		$a_mode = IL_WIKI_MODE_REPLACE)
	{
		$collect = array();

		// both from mediawiki DefaulSettings.php
		$wgLegalTitleChars = " %!\"$&'()*,\\-.\\/0-9:;=?@A-Z\\\\^_`a-z~\\x80-\\xFF+";
		
		# the % is needed to support urlencoded titles as well
		//$tc = Title::legalChars().'#%';
		$tc = $wgLegalTitleChars.'#%';

		//$sk = $this->mOptions->getSkin();

		#split the entire text string on occurences of [[
		$a = explode( '[[', ' ' . $s );
		#get the first element (all text up to first [[), and remove the space we added
		$s = array_shift( $a );
		$s = substr( $s, 1 );

		# Match a link having the form [[namespace:link|alternate]]trail
		$e1 = "/^([{$tc}]+)(?:\\|(.+?))?]](.*)\$/sD";
		
		# Match cases where there is no "]]", which might still be images
//		static $e1_img = FALSE;
//		if ( !$e1_img ) { $e1_img = "/^([{$tc}]+)\\|(.*)\$/sD"; }

		# Match the end of a line for a word that's not followed by whitespace,
		# e.g. in the case of 'The Arab al[[Razi]]', 'al' will be matched
//		$e2 = wfMsgForContent( 'linkprefix' );

/*		$useLinkPrefixExtension = $wgContLang->linkPrefixExtension();
		if( is_null( $this->mTitle ) ) {
			throw new MWException( __METHOD__.": \$this->mTitle is null\n" );
		}
		$nottalk = !$this->mTitle->isTalkPage();*/
		$nottalk = true;

/*		if ( $useLinkPrefixExtension ) {
			$m = array();
			if ( preg_match( $e2, $s, $m ) ) {
				$first_prefix = $m[2];
			} else {
				$first_prefix = false;
			}
		} else {*/
			$prefix = '';
//		}

/*		if($wgContLang->hasVariants()) {
			$selflink = $wgContLang->convertLinkToAllVariants($this->mTitle->getPrefixedText());
		} else {
			$selflink = array($this->mTitle->getPrefixedText());
		}
		$useSubpages = $this->areSubpagesAllowed();
		wfProfileOut( $fname.'-setup' );
*/
		$useSubpages = false;
		
		# Loop for each link
		for ($k = 0; isset( $a[$k] ); $k++)
		{
			$line = $a[$k];
/*			if ( $useLinkPrefixExtension ) {
				wfProfileIn( $fname.'-prefixhandling' );
				if ( preg_match( $e2, $s, $m ) ) {
					$prefix = $m[2];
					$s = $m[1];
				} else {
					$prefix='';
				}
				# first link
				if($first_prefix) {
					$prefix = $first_prefix;
					$first_prefix = false;
				}
				wfProfileOut( $fname.'-prefixhandling' );
			}*/

			$might_be_img = false;

			//wfProfileIn( "$fname-e1" );
			if ( preg_match( $e1, $line, $m ) ) { # page with normal text or alt
				$text = $m[2];
				# If we get a ] at the beginning of $m[3] that means we have a link that's something like:
				# [[Image:Foo.jpg|[http://example.com desc]]] <- having three ] in a row fucks up,
				# the real problem is with the $e1 regex
				# See bug 1300.
				#
				# Still some problems for cases where the ] is meant to be outside punctuation,
				# and no image is in sight. See bug 2095.
				#
				if( $text !== '' &&
					substr( $m[3], 0, 1 ) === ']' &&
					strpos($text, '[') !== false
				)
				{
					$text .= ']'; # so that replaceExternalLinks($text) works later
					$m[3] = substr( $m[3], 1 );
				}
				# fix up urlencoded title texts
				if( strpos( $m[1], '%' ) !== false ) {
					# Should anchors '#' also be rejected?
					$m[1] = str_replace( array('<', '>'), array('&lt;', '&gt;'), urldecode($m[1]) );
				}
				$trail = $m[3];
/*			} elseif( preg_match($e1_img, $line, $m) ) { # Invalid, but might be an image with a link in its caption
				$might_be_img = true;
				$text = $m[2];
				if ( strpos( $m[1], '%' ) !== false ) {
					$m[1] = urldecode($m[1]);
				}
				$trail = "";*/
			} else { # Invalid form; output directly
				$s .= $prefix . '[[' . $line ;
				//wfProfileOut( "$fname-e1" );
				continue;
			}
			//wfProfileOut( "$fname-e1" );
			//wfProfileIn( "$fname-misc" );

			# Don't allow internal links to pages containing
			# PROTO: where PROTO is a valid URL protocol; these
			# should be external links.
			if (preg_match('/^\b(?:' . ilWikiUtil::wfUrlProtocols() . ')/', $m[1])) {
				$s .= $prefix . '[[' . $line ;
				continue;
			}

			# Make subpage if necessary
/*			if( $useSubpages ) {
				$link = $this->maybeDoSubpageLink( $m[1], $text );
			} else {*/
				$link = $m[1];
//			}

			$noforce = (substr($m[1], 0, 1) != ':');
			if (!$noforce) {
				# Strip off leading ':'
				$link = substr($link, 1);
			}

//			wfProfileOut( "$fname-misc" );
//			wfProfileIn( "$fname-title" );

			// todo
			include_once("./Modules/Wiki/mediawiki/Title.php");
			include_once("./Services/Utilities/classes/Sanitizer.php");
			//$nt = Title::newFromText( $this->mStripState->unstripNoWiki($link) );
			
			// todo: check step by step
//echo "<br>".htmlentities($link)."---";
			$nt = Title::newFromText($link);

			if( !$nt ) {
				$s .= $prefix . '[[' . $line;
				//wfProfileOut( "$fname-title" );
				continue;
			}

/*			$ns = $nt->getNamespace();
			$iw = $nt->getInterWiki();
			wfProfileOut( "$fname-title" );

/*			if ($might_be_img) { # if this is actually an invalid link
				wfProfileIn( "$fname-might_be_img" );
				if ($ns == NS_IMAGE && $noforce) { #but might be an image
					$found = false;
					while (isset ($a[$k+1]) ) {
						#look at the next 'line' to see if we can close it there
						$spliced = array_splice( $a, $k + 1, 1 );
						$next_line = array_shift( $spliced );
						$m = explode( ']]', $next_line, 3 );
						if ( count( $m ) == 3 ) {
							# the first ]] closes the inner link, the second the image
							$found = true;
							$text .= "[[{$m[0]}]]{$m[1]}";
							$trail = $m[2];
							break;
						} elseif ( count( $m ) == 2 ) {
							#if there's exactly one ]] that's fine, we'll keep looking
							$text .= "[[{$m[0]}]]{$m[1]}";
						} else {
							#if $next_line is invalid too, we need look no further
							$text .= '[[' . $next_line;
							break;
						}
					}
					if ( !$found ) {
						# we couldn't find the end of this imageLink, so output it raw
						#but don't ignore what might be perfectly normal links in the text we've examined
						$text = $this->replaceInternalLinks($text);
						$s .= "{$prefix}[[$link|$text";
						# note: no $trail, because without an end, there *is* no trail
						wfProfileOut( "$fname-might_be_img" );
						continue;
					}
				} else { #it's not an image, so output it raw
					$s .= "{$prefix}[[$link|$text";
					# note: no $trail, because without an end, there *is* no trail
					wfProfileOut( "$fname-might_be_img" );
					continue;
				}
				wfProfileOut( "$fname-might_be_img" );
			}
*/

			$wasblank = ( '' == $text );
			if( $wasblank ) $text = $link;

			# Link not escaped by : , create the various objects
			if( $noforce ) {

				# Interwikis
				/*wfProfileIn( "$fname-interwiki" );
				if( $iw && $this->mOptions->getInterwikiMagic() && $nottalk && $wgContLang->getLanguageName( $iw ) ) {
					$this->mOutput->addLanguageLink( $nt->getFullText() );
					$s = rtrim($s . $prefix);
					$s .= trim($trail, "\n") == '' ? '': $prefix . $trail;
					wfProfileOut( "$fname-interwiki" );
					continue;
				}
				wfProfileOut( "$fname-interwiki" );*/

/*				if ( $ns == NS_IMAGE ) {
					wfProfileIn( "$fname-image" );
					if ( !wfIsBadImage( $nt->getDBkey(), $this->mTitle ) ) {
						# recursively parse links inside the image caption
						# actually, this will parse them in any other parameters, too,
						# but it might be hard to fix that, and it doesn't matter ATM
						$text = $this->replaceExternalLinks($text);
						$text = $this->replaceInternalLinks($text);

						# cloak any absolute URLs inside the image markup, so replaceExternalLinks() won't touch them
						$s .= $prefix . $this->armorLinks( $this->makeImage( $nt, $text ) ) . $trail;
						$this->mOutput->addImage( $nt->getDBkey() );

						wfProfileOut( "$fname-image" );
						continue;
					} else {
						# We still need to record the image's presence on the page
						$this->mOutput->addImage( $nt->getDBkey() );
					}
					wfProfileOut( "$fname-image" );

				}
*/
/*				if ( $ns == NS_CATEGORY ) {
					wfProfileIn( "$fname-category" );
					$s = rtrim($s . "\n"); # bug 87

					if ( $wasblank ) {
						$sortkey = $this->getDefaultSort();
					} else {
						$sortkey = $text;
					}
					$sortkey = Sanitizer::decodeCharReferences( $sortkey );
					$sortkey = str_replace( "\n", '', $sortkey );
					$sortkey = $wgContLang->convertCategoryKey( $sortkey );
					$this->mOutput->addCategory( $nt->getDBkey(), $sortkey );
*/
					/**
					 * Strip the whitespace Category links produce, see bug 87
					 * @todo We might want to use trim($tmp, "\n") here.
					 */
//					$s .= trim($prefix . $trail, "\n") == '' ? '': $prefix . $trail;

//					wfProfileOut( "$fname-category" );
//					continue;
//				}
			}

			# Self-link checking
/*			if( $nt->getFragment() === '' ) {
				if( in_array( $nt->getPrefixedText(), $selflink, true ) ) {
					$s .= $prefix . $sk->makeSelfLinkObj( $nt, $text, '', $trail );
					continue;
				}
			}*/

			# Special and Media are pseudo-namespaces; no pages actually exist in them
/*			if( $ns == NS_MEDIA ) {
				$link = $sk->makeMediaLinkObj( $nt, $text );
				# Cloak with NOPARSE to avoid replacement in replaceExternalLinks
				$s .= $prefix . $this->armorLinks( $link ) . $trail;
				$this->mOutput->addImage( $nt->getDBkey() );
				continue;
			} elseif( $ns == NS_SPECIAL ) {
				$s .= $this->makeKnownLinkHolder( $nt, $text, '', $trail, $prefix );
				continue;
			} elseif( $ns == NS_IMAGE ) {
				$img = new Image( $nt );
				if( $img->exists() ) {
					// Force a blue link if the file exists; may be a remote
					// upload on the shared repository, and we want to see its
					// auto-generated page.
					$s .= $this->makeKnownLinkHolder( $nt, $text, '', $trail, $prefix );
					$this->mOutput->addLink( $nt );
					continue;
				}
			}*/
			
			// Media wiki performs an intermediate step here (Parser->makeLinkHolder)
			if ($a_mode == IL_WIKI_MODE_REPLACE)
			{
				$s .= ilWikiUtil::makeLink($nt, $a_wiki_id, $text, '', $trail, $prefix);
//echo "<br>-".htmlentities($s)."-";
			}
			else
			{
				//$s .= ilWikiUtil::makeLink($nt, $a_wiki_id, $text, '', $trail, $prefix);
				include_once("./Modules/Wiki/classes/class.ilWikiPage.php");
				if (ilWikiPage::_wikiPageExists($a_wiki_id, $text) &&
					!in_array($text, $collect))
				{
					$collect[] = $text;
				}
			}
		}

		//wfProfileOut( $fname );

		if ($a_mode == IL_WIKI_MODE_COLLECT)
		{
			return $collect;
		}
		else
		{
			return $s;
		}
	}

	/**
	* Media wiki performs an intermediate step here (
	*/
	static function makeLink( &$nt, $a_wiki_id, $text = '', $query = '', $trail = '', $prefix = '' )
	{
		global $ilCtrl;
		
		//wfProfileIn( __METHOD__ );
		if ( ! is_object($nt) ) {
			# Fail gracefully
			$retVal = "<!-- ERROR -->{$prefix}{$text}{$trail}";
		} else {
			
//var_dump($trail);
			
			# Separate the link trail from the rest of the link
			list( $inside, $trail ) = ilWikiUtil::splitTrail( $trail );
			
			//$retVal = '***'.$text."***".$trail;
			
			$wiki_link_class = (!ilWikiPage::_wikiPageExists($a_wiki_id, $text))
				? ' class="ilWikiPageMissing" ' : "";
			
			$ilCtrl->setParameterByClass("ilobjwikigui", "page", rawurlencode($text));
			$retVal = '<a '.$wiki_link_class.' href="'.
				$ilCtrl->getLinkTargetByClass("ilobjwikigui", "gotoPage").
				'">'.$text.'</a>'.$trail;

//$ilCtrl->debug("ilWikiUtil::makeLink:-$inside-$trail-");
/*			if ( $nt->isExternal() ) {
				$nr = array_push( $this->mInterwikiLinkHolders['texts'], $prefix.$text.$inside );
				$this->mInterwikiLinkHolders['titles'][] = $nt;
				$retVal = '<!--IWLINK '. ($nr-1) ."-->{$trail}";
			} else {
				$nr = array_push( $this->mLinkHolders['namespaces'], $nt->getNamespace() );
				$this->mLinkHolders['dbkeys'][] = $nt->getDBkey();
				$this->mLinkHolders['queries'][] = $query;
				$this->mLinkHolders['texts'][] = $prefix.$text.$inside;
				$this->mLinkHolders['titles'][] = $nt;

				$retVal = '<!--LINK '. ($nr-1) ."-->{$trail}";
			}
*/
		}
		//wfProfileOut( __METHOD__ );
		return $retVal;
	}

	/**
	* From mediawiki GlobalFunctions.php
	*/
	static function wfUrlProtocols()
	{
		$wgUrlProtocols = array(
			'http://',
			'https://',
			'ftp://',
			'irc://',
			'gopher://',
			'telnet://', // Well if we're going to support the above.. -ævar
			'nntp://', // @bug 3808 RFC 1738
			'worldwind://',
			'mailto:',
			'news:'
		);

		// Support old-style $wgUrlProtocols strings, for backwards compatibility
		// with LocalSettings files from 1.5
		if ( is_array( $wgUrlProtocols ) ) {
			$protocols = array();
			foreach ($wgUrlProtocols as $protocol)
				$protocols[] = preg_quote( $protocol, '/' );
	
			return implode( '|', $protocols );
		} else {
			return $wgUrlProtocols;
		}
	}
	
	/**
	* From GlobalFunctions.php
	*/
	public static function wfUrlencode ( $s )
	{
		$s = urlencode( $s );
		$s = preg_replace( '/%3[Aa]/', ':', $s );
		$s = preg_replace( '/%2[Ff]/', '/', $s );

		return $s;
	}

	// from Linker.php
	static function splitTrail( $trail )
	{
		/*static $regex = false;
		if ( $regex === false ) {
			global $wgContLang;
			$regex = $wgContLang->linkTrail();
		}*/
		$regex = '/^([a-z]+)(.*)$/sD';
		
		$inside = '';
		if ( '' != $trail ) {
			$m = array();
			
			if ( preg_match( $regex, $trail, $m ) ) {
				$inside = $m[1];
				$trail = $m[2];
			}
		}

		return array( $inside, $trail );
	}

}
?>
