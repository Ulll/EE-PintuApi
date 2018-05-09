<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Text fieldtype file
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

class Webservice_text_ft
{
	public $name = 'text';

	// ----------------------------------------------------------------

	/**
	 * Validate the field
	 * 
	 * @param  mixed $data  
	 * @param  bool $is_new
	 * @return void            
	 */
	public function webservice_validate($data = null, $is_new = false)
	{
		//max length
		if(strlen($data) > $this->field_data['field_maxl'])
		{
			$this->validate_error = 'Max length('.$this->field_data['field_maxl'].') exceeded';
			return false;
		}

		return true;
	}
}