<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Default fieldtype file, every fieldtype, except the one overridden, goes through this class
 *
 * @package		webservice
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @link        http://reinos.nl/add-ons//add-ons/entry-api
 * @copyright 	Copyright (c) 2014 Reinos.nl Internet Media
 */

/**
 * Include the config file
 */
require_once PATH_THIRD.'webservice/config.php';

class Webservice_simple_s3_uploader_ft
{
	public $name = 'simple_s3_uploader';

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_matrix($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_grid($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	private function _parse_data($data = null, $free_access = false, $entry_id = 0)
	{
		if(!is_array($data)) {
			$data = unserialize(base64_decode($data));
		}

		return $data;
	}

}