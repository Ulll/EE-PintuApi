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
        ee()->api->instantiate('channel_entries');
        ee()->api->instantiate('channel_fields');
    }


    /**
     * build a entry data array for a new entry
     *
     * @return  void
     */
    public function update_entry($entry_id, $post_data = array())
    {
        ee()->pintuapi_lib->check_jwt_access();

        return [
            'message' => 'update success'
        ];
    }
}

