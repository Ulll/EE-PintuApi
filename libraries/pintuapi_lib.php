<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pintuapi_lib
{

    public function __construct()
    {                   
        
    }

    // --------------------------------------------------------------------

    /**
     * Has the user free access
     * User who exists has never free access
     * 0 = not free
     * 1 = no username, free access
     * 2 = inlog require, free access
     *
     * @param string $method
     * @return int
     */
    public function has_free_access()
    {
        return true;
    }


    public function check_jwt_access()
    {
        //load the api class
        ee()->load->library('api/uauth');
        $jwtToken = ee()->uauth->getBearerToken();
        if (!$jwtToken) {
            throw new Exception("missing authorization");
        }
        $uid = ee()->uauth->verify_jwt($jwtToken);
        return $uid;
    }
}