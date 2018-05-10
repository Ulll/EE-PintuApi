<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Relationship fieldtype file
 *
 * @package		webservice
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @link        http://reinos.nl/add-ons//add-ons/entry-api
 * @copyright 	Copyright (c) 2014 Reinos.nl Internet Media
 */

class Webservice_relationship_ft
{
	public $name = 'relationship';

	private $tmp_entry_id = 42949672;
	
	// ----------------------------------------------------------------

	/**
	 * Preps the data for saving
	 * 
	 * @param  mixed $data  
	 * @param  bool $is_new
	 * @param  int $entry_id
	 * @return void            
	 */
	public function webservice_save($data = null, $is_new = false, $entry_id = 0)
	{
		if(!empty($data) && is_array($data))
		{
			//only one item allowed
			if(!$this->field_settings['allow_multiple'])
			{
				$data = array_slice($data, 0, 1);
			}

			//if not new, an update (doh ;-), delete the old ones
			if($is_new == false)
			{
				ee()->db->where('parent_id', $entry_id);
				ee()->db->delete('relationships');
			}

			//set the insert array
			$insert_array = array();

			//loop over the items
			foreach($data as $order => $row)
			{
				$insert_array['data'][] = $row;
				$insert_array['sort'][] = $order;
			}
			
			//return the data
			return $insert_array;
		}
		
		//return empty, because the values are in the relationship table
		return '';
		
		/*
			Array
(
    [sort] => Array
        (
            [0] => 3
            [1] => 2
            [2] => 1
            [3] => 4
        )

    [data] => Array
        (
            [0] => 42
            [1] => 33
            [2] => 36
            [3] => 38
        )

)
		*/
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
	public function webservice_pre_process_grid($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
	}

	// ----------------------------------------------------------------------
	
	/**
	 * get the channel id for an entry_id
	 * 
	 */
	public function channel_id($entry_id = 0) 
	{
		ee()->db->select('channel_id');
		ee()->db->where('entry_id', $entry_id);
		$query = ee()->db->get('channel_titles');

		if($query->num_rows() > 0)
		{
			return $query->row()->channel_id;
		}

		return false;
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
		//get the data
		ee()->db->select('child_id as entry_id, order');
		ee()->db->where('parent_id', $entry_id);
		if($this->field_id !== null)
		{
			ee()->db->where('field_id', $this->field_id);
		}
		$query = ee()->db->get('relationships');

		if($query->num_rows() > 0)
		{
			$return = array();
			foreach($query->result_array() as $row)
			{
				if(ee()->webservice_settings->item('parse_rel_data'))
				{
					$return[] = ee()->webservice_lib->get_entry($row['entry_id'], array('*'), true);
				}
				else
				{
					$return[] = $row;
				}
				//$return[] = ee()->webservice_lib->get_entry($row['entry_id'], array('*'), true);
			}

			return $return;
		}

		return $data;
	}
}