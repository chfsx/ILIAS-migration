<?php
require_once('./Services/GlobalCache/classes/class.ilGlobalCacheService.php');

/**
 * Class ilXcache
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class ilXcache extends ilGlobalCacheService {

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function exists($key) {
		return xcache_isset($key);
	}


	/**
	 * @param      $key
	 * @param      $serialized_value
	 * @param null $ttl
	 *
	 * @return bool
	 */
	public function set($key, $serialized_value, $ttl = NULL) {
		return xcache_set($key, $serialized_value, $ttl);
	}


	/**
	 * @param      $key
	 *
	 * @return mixed
	 */
	public function get($key) {
		return xcache_get($key);
	}


	/**
	 * @param      $key
	 *
	 * @return bool
	 */
	public function delete($key) {
		return xcache_unset($key);
	}


	/**
	 * @return bool
	 */
	public function flush() {
		xcache_clear_cache(XC_TYPE_VAR, 0);

		return true;
	}


	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public function serialize($value) {
		return serialize($value);
	}


	/**
	 * @param $serialized_value
	 *
	 * @return mixed
	 */
	public function unserialize($serialized_value) {
		return unserialize($serialized_value);
	}


	/**
	 * @return bool
	 */
	protected function getActive() {
		$function_exists = function_exists('xcache_set');
		$var_size = ini_get('xcache.var_size') != '0M';
		$var_count = ini_get('xcache.var_count') > 0;
		$api = (php_sapi_name() !== 'cli');

		$active = $function_exists AND $var_size AND $var_count AND $api;

		return $active;
	}


	/**
	 * @return bool
	 */
	protected function getInstallable() {
		//return false;
		return function_exists('xcache_set');
	}


	public function getInfo() {
		if ($this->isActive()) {
			return xcache_info(XC_TYPE_VAR, 0);
		}

		return NULL;
	}
}

?>
