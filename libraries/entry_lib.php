<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Entry_lib
{
    public function __construct()
    {
        //load model
        ee()->load->model('entry_model');
        ee()->load->model('category_model');
        //load the channel data
        ee()->load->driver('channel_data');
        ee()->load->library('fieldtypes/webservice_fieldtype');
        //check the tmp path
        ee()->load->helper('file');
    }

    /**
     * Get entry based on entry_id
     * It also has the pre_proces call to attach the data
     *
     * @access    public
     * @param int $entry_id
     * @param array $select
     * @param bool $more_data
     * @param bool $raw_data
     * @return array
     * @internal param list $parameter
     */
    public function get_entry(
        $entry_id = 0,
        $select = array('channel_data.entry_id', 'channel_data.channel_id', 'channel_titles.author_id', 'channel_titles.title', 'channel_titles.url_title', 'channel_titles.entry_date', 'channel_titles.expiration_date', 'status'),
        $more_data = false,
        $raw_data = false
    )
    {
        //get the entry
        $entry_data_query = ee()->channel_data->get_entry($entry_id, array('select' => $select));

        if(!$entry_data_query || $entry_data_query->num_rows() == 0)
        {
            return array();
        }

        //get the entry
        $entry = $entry_data_query->row_array();

        if($more_data)
        {
            //also get the channel data
            $entry_data = array_merge($entry, $this->_get_channel_data($entry['channel_id']));

            /** ---------------------------------------
            /** Get the categories
            /** ---------------------------------------*/
            $entry_data['categories'] = (ee()->category_model->get_entry_categories(array($entry_data['entry_id'])));

            /** ---------------------------------------
            /**  Process the data per field
            /** ---------------------------------------*/
            $fields = $this->get_fieldtypes($entry['channel_id']);

            if(!empty($fields))
            {
                foreach($fields as $key=>$val)
                {
                    if(isset($entry_data[$val['field_name']]))
                    {
                        $entry_data[$val['field_name']] = ee()->webservice_fieldtype->pre_process($entry_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, null, 'search_entry', $entry_id);
                    }
                }
            }

            $entry = $entry_data;
        }

        /** ---------------------------------------
        /** set the data correct
        /** ---------------------------------------*/
        $entry = $this->_format_read_result($entry, $raw_data);

        return $entry;
    }


    public function get_edit_date($entry_id)
    {
        return ee()->entry_model->get_edit_date($entry_id);
    }


    /**
     * Parses out received channel parameters
     *
     * @access  public
     * @param   int
     * @return  void
     */
    public function parse_channel($entry_channel_id = '', $entry_based = true, $method = '')
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



    /**
     * Get the channel data
     *
     * @access    public
     * @param int $channel_id
     * @internal param int $entry_id
     * @internal param \list $parameter
     * @return    void
     */
    private function _get_channel_data($channel_id = 0)
    {
        ee()->db->select('channel_name, channel_title');
        ee()->db->where('channel_id', $channel_id);
        $q = ee()->db->get('channels');

        if($q->num_rows() > 0)
        {
            return $q->row_array();
        }

        return array();
    }

    /**
     * Search an entry based on the given values
     *
     * @access  public
     * @param   parameter list
     * @return  void
     */
    private function get_fieldtypes($channel_id = 0)
    {
        $channel_fields = ee()->channel_data->get_channel_fields($channel_id)->result_array();
        $fields = ee()->channel_data->utility->reindex($channel_fields, 'field_name');
        return $fields;
    }

    // ----------------------------------------------------------------

    //format an result for a get
    private function _format_read_result($result, $only_raw = false)
    {
        if(!empty($result))
        {
            foreach($result as $key=>$val)
            {

                if(substr($key, 0, 9) == 'field_ft_')
                {
                    unset($result[$key]);
                }

                if($only_raw === false && substr($key, 0, 9) == 'field_id_')
                {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}