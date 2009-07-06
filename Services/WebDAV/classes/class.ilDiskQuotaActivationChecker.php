<?php

/**
 * Activation Checker. Keep this class small, since it is included, even if 
 * DiskQuota is deactivated.
 */
class ilDiskQuotaActivationChecker
{
	private static $isActive;

   	/**
	* Static getter. Returns true, if the WebDAV server is active.
	*
	* THe WebDAV Server is active, if the variable file_access::webdav_enabled
	* is set in the client ini file, and if PEAR Auth_HTTP is installed.
	*
	* @return	boolean	value
	*/
	public static function _isActive()
	{
		if (self::$isActive == null)
		{
			$settings = new ilSetting('disk_quota');
			self::$isActive = $settings->get('enabled') == true;
		}

		return self::$isActive;
	}
}
?>
