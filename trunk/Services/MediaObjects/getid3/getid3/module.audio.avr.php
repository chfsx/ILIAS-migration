<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.avr.php                                        //
// module for analyzing AVR Audio files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_avr
{

	function getid3_avr(&$fd, &$ThisFileInfo) {

		// http://cui.unige.ch/OSG/info/AudioFormats/ap11.html
		// http://www.btinternet.com/~AnthonyJ/Atari/programming/avr_format.html
		// offset    type    length    name        comments
		// ---------------------------------------------------------------------
		// 0    char    4    ID        format ID == "2BIT"
		// 4    char    8    name        sample name (unused space filled with 0)
		// 12    short    1    mono/stereo    0=mono, -1 (0xFFFF)=stereo
		//                     With stereo, samples are alternated,
		//                     the first voice is the left :
		//                     (LRLRLRLRLRLRLRLRLR...)
		// 14    short    1    resolution    8, 12 or 16 (bits)
		// 16    short    1    signed or not    0=unsigned, -1 (0xFFFF)=signed
		// 18    short    1    loop or not    0=no loop, -1 (0xFFFF)=loop on
		// 20    short    1    MIDI note    0xFFnn, where 0 <= nn <= 127
		//                     0xFFFF means "no MIDI note defined"
		// 22    byte    1    Replay speed    Frequence in the Replay software
		//                     0=5.485 Khz, 1=8.084 Khz, 2=10.971 Khz,
		//                     3=16.168 Khz, 4=21.942 Khz, 5=32.336 Khz
		//                     6=43.885 Khz, 7=47.261 Khz
		//                     -1 (0xFF)=no defined Frequence
		// 23    byte    3    sample rate    in Hertz
		// 26    long    1    size in bytes (2 * bytes in stereo)
		// 30    long    1    loop begin    0 for no loop
		// 34    long    1    loop size    equal to 'size' for no loop
		// 38  short   2   Reserved, MIDI keyboard split */
		// 40  short   2   Reserved, sample compression */
		// 42  short   2   Reserved */
		// 44  char   20;  Additional filename space, used if (name[7] != 0)
		// 64    byte    64    user data
		// 128    bytes    ?    sample data    (12 bits samples are coded on 16 bits:
		//                     0000 xxxx xxxx xxxx)
		// ---------------------------------------------------------------------

		// Note that all values are in motorola (big-endian) format, and that long is
		// assumed to be 4 bytes, and short 2 bytes.
		// When reading the samples, you should handle both signed and unsigned data,
		// and be prepared to convert 16->8 bit, or mono->stereo if needed. To convert
		// 8-bit data between signed/unsigned just add 127 to the sample values.
		// Simularly for 16-bit data you should add 32769

		$ThisFileInfo['fileformat'] = 'avr';

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$AVRheader = fread($fd, 128);

		$ThisFileInfo['avr']['raw']['magic']    =               substr($AVRheader,  0,  4);
		if ($ThisFileInfo['avr']['raw']['magic'] != '2BIT') {
			$ThisFileInfo['error'][] = 'Expecting "2BIT" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['avr']['raw']['magic'].'"';
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['avr']);
			return false;
		}
		$ThisFileInfo['avdataoffset'] += 128;

		$ThisFileInfo['avr']['sample_name']        =         rtrim(substr($AVRheader,  4,  8));
		$ThisFileInfo['avr']['raw']['mono']        = getid3_lib::BigEndian2Int(substr($AVRheader, 12,  2));
		$ThisFileInfo['avr']['bits_per_sample']    = getid3_lib::BigEndian2Int(substr($AVRheader, 14,  2));
		$ThisFileInfo['avr']['raw']['signed']      = getid3_lib::BigEndian2Int(substr($AVRheader, 16,  2));
		$ThisFileInfo['avr']['raw']['loop']        = getid3_lib::BigEndian2Int(substr($AVRheader, 18,  2));
		$ThisFileInfo['avr']['raw']['midi']        = getid3_lib::BigEndian2Int(substr($AVRheader, 20,  2));
		$ThisFileInfo['avr']['raw']['replay_freq'] = getid3_lib::BigEndian2Int(substr($AVRheader, 22,  1));
		$ThisFileInfo['avr']['sample_rate']        = getid3_lib::BigEndian2Int(substr($AVRheader, 23,  3));
		$ThisFileInfo['avr']['sample_length']      = getid3_lib::BigEndian2Int(substr($AVRheader, 26,  4));
		$ThisFileInfo['avr']['loop_start']         = getid3_lib::BigEndian2Int(substr($AVRheader, 30,  4));
		$ThisFileInfo['avr']['loop_end']           = getid3_lib::BigEndian2Int(substr($AVRheader, 34,  4));
		$ThisFileInfo['avr']['midi_split']         = getid3_lib::BigEndian2Int(substr($AVRheader, 38,  2));
		$ThisFileInfo['avr']['sample_compression'] = getid3_lib::BigEndian2Int(substr($AVRheader, 40,  2));
		$ThisFileInfo['avr']['reserved']           = getid3_lib::BigEndian2Int(substr($AVRheader, 42,  2));
		$ThisFileInfo['avr']['sample_name_extra']  =         rtrim(substr($AVRheader, 44, 20));
		$ThisFileInfo['avr']['comment']            =         rtrim(substr($AVRheader, 64, 64));

		$ThisFileInfo['avr']['flags']['stereo'] = (($ThisFileInfo['avr']['raw']['mono']   == 0) ? false : true);
		$ThisFileInfo['avr']['flags']['signed'] = (($ThisFileInfo['avr']['raw']['signed'] == 0) ? false : true);
		$ThisFileInfo['avr']['flags']['loop']   = (($ThisFileInfo['avr']['raw']['loop']   == 0) ? false : true);

		$ThisFileInfo['avr']['midi_notes'] = array();
		if (($ThisFileInfo['avr']['raw']['midi'] & 0xFF00) != 0xFF00) {
			$ThisFileInfo['avr']['midi_notes'][] = ($ThisFileInfo['avr']['raw']['midi'] & 0xFF00) >> 8;
		}
		if (($ThisFileInfo['avr']['raw']['midi'] & 0x00FF) != 0x00FF) {
			$ThisFileInfo['avr']['midi_notes'][] = ($ThisFileInfo['avr']['raw']['midi'] & 0x00FF);
		}

		if (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) != ($ThisFileInfo['avr']['sample_length'] * (($ThisFileInfo['avr']['bits_per_sample'] == 8) ? 1 : 2))) {
			$ThisFileInfo['warning'][] = 'Probable truncated file: expecting '.($ThisFileInfo['avr']['sample_length'] * (($ThisFileInfo['avr']['bits_per_sample'] == 8) ? 1 : 2)).' bytes of audio data, found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']);
		}

		$ThisFileInfo['audio']['dataformat']      = 'avr';
		$ThisFileInfo['audio']['lossless']        = true;
		$ThisFileInfo['audio']['bitrate_mode']    = 'cbr';
		$ThisFileInfo['audio']['bits_per_sample'] = $ThisFileInfo['avr']['bits_per_sample'];
		$ThisFileInfo['audio']['sample_rate']     = $ThisFileInfo['avr']['sample_rate'];
		$ThisFileInfo['audio']['channels']        = ($ThisFileInfo['avr']['flags']['stereo'] ? 2 : 1);
		$ThisFileInfo['playtime_seconds']         = ($ThisFileInfo['avr']['sample_length'] / $ThisFileInfo['audio']['channels']) / $ThisFileInfo['avr']['sample_rate'];
		$ThisFileInfo['audio']['bitrate']         = ($ThisFileInfo['avr']['sample_length'] * (($ThisFileInfo['avr']['bits_per_sample'] == 8) ? 8 : 16)) / $ThisFileInfo['playtime_seconds'];


		return true;
	}

}


?>