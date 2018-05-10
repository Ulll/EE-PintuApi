<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Entry_model
{
    public function __construct()
    {                   
        //load other models
        ee()->load->model('category_model');
    }
} // END CLASS

/* End of file default_model.php  */
/* Location: ./system/expressionengine/third_party/default/models/default_model.php */