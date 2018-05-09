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

class Webservice_rte_ft
{
	public $name = 'rte';

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
		return $this->parse_data($data, $this->field_data);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned for matrix
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_matrix($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->parse_data($data, $this->col_data);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned for matrix
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_grid($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->parse_data($data, $this->col_data);
	}

	// ----------------------------------------------------------------------

	/**
	 * parse the data
	 */
	private function parse_data($data = null, $field_data)
	{
		$data = ee()->typography->parse_type(
			ee()->functions->encode_ee_tags(
				ee()->typography->parse_file_paths($data)
			),
			array(
				'text_format'	=> 'xhtml',
				'html_format'	=> $field_data['channel_settings']['channel_html_formatting'],
				'auto_links'	=> $field_data['channel_settings']['channel_auto_link_urls'],
				'allow_img_url' => $field_data['channel_settings']['channel_allow_img_urls']
			)
		);
		return $data;
	}
}