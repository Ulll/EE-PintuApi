<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Entry
{
    public $limit;
    public $offset;
    public $total_results;
    public $absolute_results;

    //-------------------------------------------------------------------------

    /**
     * Constructor
    */
    public function __construct()
    {
        // load the stats class because this is not loaded because of the use of the extension
        ee()->load->library('stats'); 
                
        /** ---------------------------------------
        /** load the api`s
        /** ---------------------------------------*/
        ee()->load->library('api');
        ee()->load->library('entry_lib');
        ee()->api->instantiate('channel_entries');
        ee()->api->instantiate('channel_fields');
    }


    /**
     * build a entry data array for a new entry
     *
     * @return  void
     */
    public function update_entry_bak($entry_id, $post_data = array())
    {
        ee()->pintuapi_lib->check_jwt_access();

        return [
            'message' => 'update success'
        ];
    }

    /**
     * build a entry data array for a new entry
     *
     * @return  void
     */
    public function update_entry($entry_id, $post_data = array())
    {
        ee()->pintuapi_lib->check_jwt_access();

        /** ---------------------------------------
        /**  Validate data
        /** ---------------------------------------*/
        $data_errors = array();

        if (!$entry_id) {
            $data_errors['entry_id'];
        }

        /** ---------------------------------------
        /**  Return error when there are fields who are empty en shoulnd`t
        /** ---------------------------------------*/
        if(!empty($data_errors) || count($data_errors) > 0)
        {
            //generate error
            return array(
                'message' => 'The following fields are not filled in: '.implode(', ',$data_errors)
            );
        }

        /** ---------------------------------------
        /**  get the entry data and check if the entry exists
        /** ---------------------------------------*/
        $entry_data = ee()->entry_lib->get_entry($entry_id, array('channel_data.*, channel_titles.*'),  false, true);

        //check the data
        if ( empty($entry_data))
        {
            //generate error
            return array(
                'message' => 'No Entry found'
            );
        }

        //** ---------------------------------------
        /**  Publisher support
        /** ---------------------------------------*/
        if(isset($post_data['publisher_lang_id']))
        {
            $entry_data['publisher_lang_id'] = $post_data['publisher_lang_id'];
        }
        if(isset($post_data['publisher_status']))
        {
            $entry_data['publisher_status'] = $post_data['publisher_status'];
        }

        /** ---------------------------------------
        /**  Parse Out Channel Information and check if the use is auth for the channel
        /** ---------------------------------------*/
        $channel_check = $this->_parse_channel($entry_id);

        if( ! $channel_check['success'])
        {
            return $channel_check;
        }
        
        /** ---------------------------------------
        /**  Check the other fields witch are required
        /** ---------------------------------------*/
        if(!empty($this->fields))
        {
            foreach($this->fields as $key=>$val)
            {
                if($val['field_required'] == 'y')
                {
                    if(!isset($post_data[$val['field_name']]) || $post_data[$val['field_name']] == '') {
                        $data_errors[] = $val['field_name'];
                    }
                }
            }
        }       
        
        /** ---------------------------------------
        /**  Return error when there are fields who are empty en shoulnd`t
        /** ---------------------------------------*/
        if(!empty($data_errors) || count($data_errors) > 0)
        {
            //generate error
            return array(
                'message' => 'The following fields are not filled in: '.implode(', ',$data_errors)
            );
        }
        
        /** ---------------------------------------
        /**  check if the given channel_id match the channel_id of the entry
        /** ---------------------------------------*/
        if($entry_data['channel_id'] != $this->channel['channel_id'])
        {
            //generate error
            return array(
                'message' => 'Specified entry does not appear in the specified channel'
            );
        }

        /** ---------------------------------------
        /**  validate fields by the fieldtype parser
        /** ---------------------------------------*/
        $validate_errors = array();
        if(!empty($this->fields))
        {
            foreach($this->fields as $key=>$val)
            {
                if(isset($post_data[$val['field_name']])) 
                {
                    //validate the data
                    $validate_field = (bool) ee()->webservice_fieldtype->validate($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, false, $post_data['entry_id']);
                    
                    if($validate_field == false)
                    {
                        $validate_errors[] = $val['field_name'].' : '.ee()->webservice_fieldtype->validate_error;
                    }
                }
            }
        }

        /** ---------------------------------------
        /**  Return the errors from the validate functions
        /** ---------------------------------------*/
        if(!empty($validate_errors) || count($validate_errors) > 0)
        {
            //generate error
            return array(
                'message' => 'The following fields have errors: '.implode(', ',$validate_errors)
            );
        }

        /** ---------------------------------------
        /**  default data
        /** ---------------------------------------*/
        $entry_data['title'] = isset($post_data['title']) ? $post_data['title'] : $entry_data['title'] ;
        $entry_data['status'] = isset($post_data['status']) ? $post_data['status'] : $entry_data['status'] ;
        $entry_data['sticky'] = isset($post_data['sticky']) ? $post_data['sticky'] : $entry_data['sticky'] ;
        $entry_data['allow_comments'] = isset($post_data['allow_comments']) ? $post_data['allow_comments'] : $entry_data['allow_comments'] ;
        $entry_data['entry_date'] = isset($post_data['entry_date']) ? $post_data['entry_date'] : $entry_data['entry_date'] ;
        $entry_data['edit_date'] = isset($post_data['edit_date']) ? $post_data['edit_date'] : ee()->localize->now  ;
        $entry_data['expiration_date'] = isset($post_data['expiration_date']) ? $post_data['expiration_date'] : 0 ;
        $entry_data['comment_expiration_date'] = isset($post_data['comment_expiration_date']) ? $post_data['comment_expiration_date'] : 0  ;
        $entry_data['author_id'] = isset($post_data['author_id']) ? $post_data['author_id'] : $entry_data['author_id']  ;

        /** ---------------------------------------
        /**  validate dates
        /** ---------------------------------------*/
        $date_error = $this->validate_dates(array('entry_date', 'edit_date', 'expiration_date', 'comment_expiration_date'), $entry_data);
        if($date_error !== true)
        {
            return $date_error;
        }

        //** ---------------------------------------
        /**  Fill out the other custom fields
        /** ---------------------------------------*/
        if(!empty($this->fields))
        {
            foreach($this->fields as $key=>$val)
            {
                //set the Posted data
                if(isset($post_data[$val['field_name']])) 
                {
                    $entry_data['field_ft_'.$val['field_id']]  = $val['field_fmt']; 
                    $entry_data['field_id_'.$val['field_id']]  = ee()->webservice_fieldtype->save($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, false, $entry_data['entry_id']);
                }
            }
        }

        /** ---------------------------------------
        /**  Get the old assigned categories or the new one
        /** ---------------------------------------*/
        $assigned_categories = isset($post_data['category']) ? $post_data['category'] : ee()->category_model->get_entry_categories($entry_data['entry_id'], true);

        /** ---------------------------------------*/

        /** ---------------------------------------
        /**  set the channel setting 
        /** ---------------------------------------*/
        ee()->api_channel_fields->setup_entry_settings($this->channel['channel_id'], $entry_data);
        /** ---------------------------------------
        /**  update entry some times return show_error() views
        /** ---------------------------------------*/

        $r = ee()->api_channel_entries->save_entry($entry_data, $this->channel['channel_id'], $entry_data['entry_id']);

        //Any errors?
        if ( ! $r)
        {
            //return een fout bericht met de errors
            $errors = implode(', ', ee()->api_channel_entries->get_errors());

            //generate error
            return array(
                'message' => $errors
            );
        }

        /** ---------------------------------------
        /** Okay, now lets add a new category or update is. Just after saving the data
        /** ---------------------------------------*/
        ee()->category_model->update_category((array) $assigned_categories, ee()->api_channel_entries->entry_id);

        /** ---------------------------------------
        /**  Post save callback
        /** ---------------------------------------*/
        if(!empty($this->fields))
        {

            foreach($this->fields as $key=>$val)
            {
                if(isset($post_data[$val['field_name']])) 
                {
                    //validate the data
                    ee()->webservice_fieldtype->post_save($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, $entry_data, ee()->api_channel_entries->entry_id);
                }
            }
        }

        /** ---------------------------------------
        /** return response
        /** ---------------------------------------*/

        return [
            'data' => [
                'entry_id' => $entry_data['entry_id']
            ],
            'message' => 'update success'
        ];
    }

    /**
     * Parses out received channel parameters
     *
     * @access  public
     * @param   int
     * @return  void
     */
    private function _parse_channel($entry_channel_id = '', $entry_based = true, $method = '')
    {
        //get the channel data
        ee()->db->select('*')->from('channels');
        //select based on entry_id
        if($entry_based)
        {
            ee()->db->where('channel_titles.entry_id', $entry_channel_id);
            ee()->db->join('channel_titles', 'channels.channel_id = channel_titles.channel_id', 'left');
        }
        //based on channelname
        else
        {
            if(is_numeric($entry_channel_id))
            {
                ee()->db->where('channel_id', $entry_channel_id);
            }
            else
            {
                ee()->db->where('channel_name', $entry_channel_id);
            }
        }
        
        $query = ee()->db->get();

        //no result?
        if ($query->num_rows() == 0)
        {   
            return array(
                'success' => false,
                'message' => 'Given channel does not exist'
            );
        }
        $this->channel = $query->result_array()[0];

        if(!$this->channel)
        {
            //no rights to the channel
            return array(
                'success' => false,
                'message' => 'You are not authorized to use this channel'
            );
        }

        $this->fields = $this->_get_fieldtypes();
        
        //everything is ok
        return array('success'=>true);
    }

    // ----------------------------------------------------------------

    /**
     * Search an entry based on the given values
     *
     * @access  public
     * @param   parameter list
     * @return  void
     */
    private function _get_fieldtypes()
    {
        $channel_id = isset($this->channel['channel_id']) ? $this->channel['channel_id'] : null ;
        $channel_fields = ee()->channel_data->get_channel_fields($channel_id)->result_array();
        $fields = ee()->channel_data->utility->reindex($channel_fields, 'field_name');
        return $fields;
    }

    //validate dates
    public function validate_dates($dates = array('entry_date', 'edit_date', 'expiration_date', 'comment_expiration_date'), &$post_data = array())
    {
        //validate the date if needed
        $validate_dates = array();

        //loop over the default dates
        foreach($dates as $date)
        {
            //no date set?
            if ( ! isset($post_data[$date]) OR ! $post_data[$date])
            {
                $post_data[$date] = 0;
            }

            //otherwise save it, and validate it later
            else
            {
                $validate_dates[] = $date;
            }
        }

        //validate the dates
        foreach($validate_dates as $date)
        {
            if ( ! is_numeric($post_data[$date]) && trim($post_data[$date]))
            {
                $post_data[$date] = ee()->localize->string_to_timestamp($post_data[$date]);
            }

            if ($post_data[$date] === FALSE)
            {
                //generate error
                return array(
                    'message' => 'the field '.$date.' is an invalid date.'
                );
            }

            if (isset($post_data['revision_post'][$date]))
            {
                $post_data['revision_post'][$date] = $post_data[$date];
            }
        }

        return true;
    }

}

