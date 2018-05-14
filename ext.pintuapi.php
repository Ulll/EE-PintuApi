<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pintuapi_ext
{

    public $name            = 'pintuapi_ext';
    public $description     = 'pintuapi_desc';
    public $version         = '1.0.0';
    public $settings        = array();
    public $docs_url        = 'http://docs.heiljo.com/pintuapi';
    public $settings_exist  = 'n';
    public $required_by     = array();

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    public function __construct($settings = '')
    {
        //get the instance of the EE object
        //$this->EE =& get_instance();      
    }

    /**
     * sessions_start
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function sessions_start($session)
    {
        ee()->session = $session;
        //just an page request?
        if (REQ == 'PAGE' && !empty($session))
        {   
            //is the first segment 'webservice'
            $is_ptapi = ee()->uri->segment(1) == 'api' ? true : false;
            //is the request a page and is the first segment webservice?
            //than we need to trigger te services
            if($is_ptapi)
            {   
                //show all error report E_ALL
                error_reporting(-1);
                ini_set('display_errors', 1);
                //全局捕捉异常
                set_exception_handler(function($e){
                    $msg = $e->getMessage();
                    $r   = new pintuapi_rest();
                    $r->Pterror($msg, 406);
                });
                //set agent if missing
                $_SERVER['HTTP_USER_AGENT'] = ee()->input->user_agent() == false ? '0' : ee()->input->user_agent();
                include_once __DIR__ . '/vendor/autoload.php';
                include_once PATH_THIRD .'pintuapi/libraries/pintuapi_rest.php';
                //load the route class
                include_once PATH_THIRD .'pintuapi/libraries/pintuapi_route.php';
                //load the lib
                ee()->load->library('pintuapi_lib');
                ee()->load->helper('pintuapi');
                //call the class 默认使用rest风格
                $this->ptapi = new Pintuapi_route();
                //stop the whole process because we will not show futher more 
                ee()->extensions->end_script = true;
                die();  
            }   
            
        }
    }

    /**
     * 更新所有缓存
     * @return 
     */
    function entry_submission_end($entry, $values)
    {
        //清除该文章对应的缓存数据
    }
}