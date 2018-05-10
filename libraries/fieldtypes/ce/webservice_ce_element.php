<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Base fieldtype file
 *
 * @package		webservice
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @link        http://reinos.nl/add-ons//add-ons/entry-api
 * @copyright 	Copyright (c) 2014 Reinos.nl Internet Media
 */


class Webservice_ce_element
{

	private $_bundled_element_types = array(
		'default',
		'heading',
		'playa',
		'table',
		'gallery'
	);

	public function __construct(){}
//
//	// ----------------------------------------------------------------------
//
//	/**
//	 * Preps the data for saving
//	 *
//	 * @param  mixed $data
//	 * @param  string $field_type
//	 * @param  string  $field_name
//	 * @param  array $field_data
//	 * @param  array $channel_settings
//	 * @param  bool $is_new
//	 * @param  int $entry_id
//	 * @return mixed string
//	 */
//	public function save($data = null, $field_settings = null)
//	{
//		//get the fieldtype
//		$obj = $this->_get_fieldtype($field_type, $field_data, $entry_id);
//
//		//set the field_name
//		$obj->field_name = $field_name;
//		//set the field_id
//		$obj->field_id = $field_data['field_id'];
//		//set the field data
//		$obj->field_data = $field_data;
//		//set the field settings
//		$obj->field_settings = unserialize(base64_decode($field_data['field_settings']));
//		//set the channel_settings
//		$obj->channel_settings = $channel_settings;
//		//set the type
//		$obj->field_type = $field_type;
//
//		//assign channel data
//		$obj->field_data['channel_settings'] = $this->_assign_channel_settings($entry_id);
//
//		//is the class exists
//		if(method_exists($obj, 'webservice_save'))
//		{
//			return $obj->webservice_save($data, $is_new, $entry_id);
//		}
//
//		return $data;
//	}
//
//	// ----------------------------------------------------------------------
//
//	/**
//	 * validates the field input
//	 *
//	 * @param  mixed $data
//	 * @param  string $field_type
//	 * @param  string  $field_name
//	 * @param  array $field_data
//	 * @param  array $channel_settings
//	 * @param  bool $is_new
//	 * @return bool
//	 */
//	public function validate($data = null, $field_type = null, $field_name = null, $field_data = array(), $channel_settings = array(), $is_new = false, $entry_id = 0)
//	{
//		//get the fieldtype
//		$obj = $this->_get_fieldtype($field_type, $field_data, $entry_id);
//
//		//set the field_name
//		$obj->field_name = $field_name;
//		//set the field_id
//		$obj->field_id = $field_data['field_id'];
//		//set the field data
//		$obj->field_data = $field_data;
//		//set the field settings
//		$obj->field_settings = unserialize(base64_decode($field_data['field_settings']));
//		//set the channel_settings
//		$obj->channel_settings = $channel_settings;
//		//set the type
//		$obj->field_type = $field_type;
//
//		//assign channel data
//		$obj->field_data['channel_settings'] = $this->_assign_channel_settings($entry_id);
//
//		//is the class exists
//		if(method_exists($obj, 'webservice_validate'))
//		{
//			//reset errror msg
//			$this->validate_error = '';
//
//			//validate field
//			$validated = $obj->webservice_validate($data, $is_new, $entry_id);
//
//			//set the error msg
//			if(isset($obj->validate_error))
//			{
//				$this->validate_error = $obj->validate_error;
//			}
//
//			return $validated;
//		}
//
//		return true;
//	}
//
//	// ----------------------------------------------------------------------
//
//	/**
//	 * Handles any custom logic after an entry is saved.
//	 *
//	 * @param  mixed $data
//	 * @param  string $field_type
//	 * @param  string  $field_name
//	 * @param  array $field_data
//	 * @param  array $channel_settings
//	 * @param  array $inserted_data
//	 * @param  int $entry_id
//	 * @return void
//	 */
//	public function post_save($data = null, $field_type = null, $field_name = null, $field_data = array(), $channel_settings = array(), $inserted_data = array(), $entry_id = 0)
//	{
//		//get the fieldtype
//		$obj = $this->_get_fieldtype($field_type, $field_data, $entry_id);
//
//		//set the field_name
//		$obj->field_name = $field_name;
//		//set the field_id
//		$obj->field_id = $field_data['field_id'];
//		//set the field data
//		$obj->field_data = $field_data;
//		//set the field settings
//		$obj->field_settings = unserialize(base64_decode($field_data['field_settings']));
//		//set the channel_settings
//		$obj->channel_settings = $channel_settings;
//		//set the type
//		$obj->field_type = $field_type;
//
//		//assign channel data
//		$obj->field_data['channel_settings'] = $this->_assign_channel_settings($entry_id);
//
//		//is the class exists
//		if(method_exists($obj, 'webservice_post_save'))
//		{
//			$obj->webservice_post_save($data, $inserted_data, $entry_id);
//		}
//	}

	// ----------------------------------------------------------------------
	
	/**
	 * Preprocess the data to be returned
	 * 
	 * @param  mixed $data  
	 * @param  string $field_type  
	 * @param  string  $field_name
	 * @param  array $field_data
	 * @param  array $channel_settings
	 * @param  array $method (for free access)
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function pre_process($data = null, $element_id = null, $element_type = null, $element_settings = null, $entry_id = 0)
	{
		//get the fieldtype
		$obj = $this->_get_element($element_type);

		//fetch the settings
		$element_settings = unserialize(base64_decode($element_settings));

		//set the element_name
		$obj->element_name = $element_settings['settings']['title'];
		//set the element_id
		$obj->element_id = $element_id;
		//set the field data
		$obj->element_data = $data;
		//set the field settings
		$obj->element_settings = $element_settings;
		//set the type
		$obj->element_type = $element_type;

		//is the class exists
		if(method_exists($obj, 'webservice_ce_pre_process'))
		{
			return $obj->webservice_ce_pre_process($data, $entry_id);
		}

		return $data;
	}

	// ----------------------------------------------------------------------
	
	/**
	 * delete field data, before the entry is deleted
	 * 
	 * @param  mixed $data  
	 * @param  string $field_type  
	 * @param  string  $field_name
	 * @param  array $field_data
	 * @param  array $channel_settings
	 * @param  int $entry_id
	 * @return void
	 */
//	public function delete($data = null, $field_type = null, $field_name = null, $field_data = array(), $channel_settings = array(), $entry_id = 0)
//	{
//		//get the fieldtype
//		$obj = $this->_get_fieldtype($field_type, $field_data, $entry_id);
//
//		//set the field_name
//		$obj->field_name = $field_name;
//		//set the field_id
//		$obj->field_id = $field_data['field_id'];
//		//set the field data
//		$obj->field_data = $field_data;
//		//set the field settings
//		$obj->field_settings = unserialize(base64_decode($field_data['field_settings']));
//		//set the channel_settings
//		$obj->channel_settings = $channel_settings;
//		//set the type
//		$obj->field_type = $field_type;
//
//		//assign channel data
//		$obj->field_data['channel_settings'] = $this->_assign_channel_settings($entry_id);
//
//		//is the class exists
//		if(method_exists($obj, 'webservice_delete'))
//		{
//			$obj->webservice_delete($data, $entry_id);
//		}
//	}
//
//	// ----------------------------------------------------------------------
//
//	/**
//	 * delete field data, after the entry is deleted
//	 *
//	 * @param  mixed $data
//	 * @param  string $field_type
//	 * @param  string  $field_name
//	 * @param  array $field_data
//	 * @param  array $channel_settings
//	 * @param  int $entry_id
//	 * @return void
//	 */
//	public function post_delete($data = null, $field_type = null, $field_name = null, $field_data = array(), $channel_settings = array(), $entry_id = 0)
//	{
//		//get the fieldtype
//		$obj = $this->_get_fieldtype($field_type, $field_data, $entry_id);
//
//		//set the field_name
//		$obj->field_name = $field_name;
//		//set the field_id
//		$obj->field_id = $field_data['field_id'];
//		//set the field data
//		$obj->field_data = $field_data;
//		//set the field settings
//		$obj->field_settings = unserialize(base64_decode($field_data['field_settings']));
//		//set the channel_settings
//		$obj->channel_settings = $channel_settings;
//		//set the type
//		$obj->field_type = $field_type;
//
//		//assign channel data
//		$obj->field_data['channel_settings'] = $this->_assign_channel_settings($entry_id);
//
//		//is the class exists
//		if(method_exists($obj, 'webservice_post_delete'))
//		{
//			$obj->webservice_post_delete($data, $entry_id);
//		}
//	}


	// ---------------------------------------------------------------------- 
	
	/**
	 * get the class of the fieldata
	 * 
	 * @param none
	 * @return void
	 */
	private function _get_element($name = '')
	{
		static $classCache = array();

		if (!empty($classCache[$name]))
		{
			$class = $classCache[$name];
		}
		else
		{
			// is this a bundled celltype?
			if (in_array($name, $this->_bundled_element_types))
			{
				$class = 'Webservice_ce_'.$name."_element";

				if (! class_exists($class))
				{
					// load it from entryapi/fieldtypes/
					require_once PATH_THIRD.'webservice/libraries/api/entry/fieldtypes/ce/webservice_ce_'.$name."_element.php";
				}
			}
			else
			{
				$class = null;
			}
		}


		// set the class correct
		//$class = (string) $class;

		//return the fieldtype class
		if(is_object($class))
		{
			return $class;
		}

		//return the default Webservice fieldtype class
		else if (class_exists((string)$class))
		{
			$ft = new $class();
			$classCache[$name] = $ft;
			return $ft;
		}

		//return the base default class
		else
		{
			ee()->load->library('api/entry/fieldtypes/ce/webservice_ce_default_element');

			return new Webservice_ce_default_element();
		}
	}


	
	
} // END CLASS

/* End of file default_model.php  */
/* Location: ./system/expressionengine/third_party/default/models/default_model.php */