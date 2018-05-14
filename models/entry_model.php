<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Entry_model
{
    public function __construct()
    {                   
        //load other models
        ee()->load->model('category_model');
    }

    public function get_edit_date($entry_id)
    {
        ee()->db->select('edit_date');
        ee()->db->where('entry_id', $entry_id);
        return ee()->db->get('channel_titles')->row();
    }
} 

// END CLASS

/* End of file default_model.php  */
/* Location: ./system/expressionengine/third_party/default/models/default_model.php */