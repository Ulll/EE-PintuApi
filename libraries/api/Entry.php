<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Entry
{
    public $limit = 10;
    public $offset = 0;
    public $total_results = 0;
    public $cache_time = 600;//10 minute



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
     * Read a entry
     * @param  string $auth 
     * @param  array  $post_data 
     * @return array            
     */
    public function read_entry($entry_id)
    {
        $outputFields = array(
            'entry_id',
            'author',
            'title',
            'url_title',
            'entry_date',
            'edit_date',
            'categories',
        );

        // $edit_date = ee()->entry_lib->get_edit_date($entry_id);
        // if (empty($edit_date)) {
        //     return [
        //         'message' => 'entry no found',
        //         'httpcode' => 403
        //     ];
        // }
        // $edit_date = $edit_date->edit_date;
        // $cache_key = __METHOD__.":".$entry_id;
        // $cache_data    = ee()->cache->get($cache_key);

        // if ($cache_data == false || $cache_data['edit_date'] != $edit_date) {
        //     $entry_data = ee()->entry_lib->get_entry($entry_id, array('*'), true);
        //     // Cache version information for a while
        //     ee()->cache->save(
        //         $cache_key,
        //         $entry_data,
        //         $this->cache_time
        //     );
        // }else {
        //     $entry_data = $cache_data;
        // }
        
        $entry_data = ee()->entry_lib->get_entry($entry_id, array('*'), true);

        return [
            'data' => $entry_data
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
        $channel_check = $this->entry_lib->parse_channel($entry_id);

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
        $date_error = $this->entry_lib->validate_dates(array('entry_date', 'edit_date', 'expiration_date', 'comment_expiration_date'), $entry_data);
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
                'message' => $errors,
                'httpcode' => 400,
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
}

