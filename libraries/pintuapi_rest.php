<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Pintuapi_rest
{   
    /*
    *   The username
    */
    public $username;
    
    /*
    *   the password
    */
    public $password;
    
    /*
    *   The postdata
    */
    public $post_data;
    
    /*
    *   the channel
    */
    public $post_data_channel;
    
    /*
    *   REST server
    */
    private $server;

    /*
    *   Method using
    */
    private $method;

    /*
    *   Class calling
    */
    private $calling_class;
    
    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_supported_formats = array(
        'xml' => 'application/xml',
        'json' => 'application/json',
        //'jsonp' => 'application/javascript',
        'serialized' => 'application/vnd.php.serialized',
        'php' => 'text/plain',
        //'html' => 'text/html',
        //'csv' => 'application/csv'
    );
    
    /**
     * The arguments for the GET request method
     *
     * @var array
     */
    protected $_get_args = array();

    /**
     * The arguments for the POST request method
     *
     * @var array
     */
    protected $_post_args = array();

    /**
     * The arguments for the PUT request method
     *
     * @var array
     */
    protected $_put_args = array();

    /**
     * The arguments for the DELETE request method
     *
     * @var array
     */
    protected $_delete_args = array();

    /**
     * The arguments from GET, POST, PUT, DELETE request methods combined.
     *
     * @var array
     */
    protected $_args = array();
    
     /**
     * Determines if output compression is enabled
     *
     * @var boolean
     */
    protected $_zlib_oc = FALSE;

    // ----------------------------------------------------------------------
    
    /**
     * Constructor
     */
    public function __construct()
    {
        //load the helper
        ee()->load->helper('url');

        $this->init();
    }

    // --------------------------------------------------------------------

    /**
     * Call Method
     *
     * @param string $method
     */
    public function init()
    {        
        /** ---------------------------------------
        /** From here we do some Specific things
        /** ---------------------------------------*/
        //-----------------------------------------------------------------------------------------------------------------------------

        // let's learn about the request
        $this->request = new stdClass();

        // Is it over SSL?
        $this->request->ssl = $this->_detect_ssl();

        // How is this request being made? POST, DELETE, GET, PUT?
        $this->request->method = $this->_detect_method();

        // Create argument container, if nonexistent
        if ( ! isset($this->{'_'.$this->request->method.'_args'}))
        {
            $this->{'_'.$this->request->method.'_args'} = array();
        }

        // This library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter itself
        ee()->load->library('format');

        // Set up our GET variables
        $this->_get_args = array_merge($this->_get_args, ee()->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->request->format = $this->_detect_input_format();

        // Some Methods cant have a body
        $this->request->body = NULL;

        $this->{'_parse_' . $this->request->method}();

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->request->format and $this->request->body)
        {
            $this->request->body = ee()->format->factory($this->request->body, $this->request->format)->to_array();
            // Assign payload arguments to proper method container
            $this->{'_'.$this->request->method.'_args'} = $this->request->body;
        }

        // Merge both for one mega-args variable
        $this->_args = array_merge($this->_get_args, $this->_put_args, $this->_post_args, $this->_delete_args, $this->{'_'.$this->request->method.'_args'});

        // Which format should the data be returned in?
        $this->response = new stdClass();
        $this->response->format = $this->_detect_output_format();

        // Which format should the data be returned in?
        $this->response->lang = $this->_detect_lang();

        //parse the vars
        $require = array();
        $data_vars = $this->_get_vars($this->_detect_method(), $require);

        $vars['data'] = $data_vars;

        $this->vars = $vars;

        /** ---------------------------------------
        /** End of the specific things
        /** ---------------------------------------*/
    }


    function call($class, $method, $segments)
    {
        //防重放攻击判断
        ee()->pintuapi_lib->check_replay_attack();

        //-----------------------------------------------------------------------------------------------------------------------------
        // Get the API
        //-----------------------------------------------------------------------------------------------------------------------------
        //defaults
        $error_auth = false;

        //check if the file exists
        if(!file_exists(PATH_THIRD.'pintuapi/libraries/api/'.$class.'.php'))
        {
            //return response
            $this->Pterror('API does not exist', 400);
        }

        //load the api class
        ee()->load->library('api/'.$class);

        // check if method exists
        if (!method_exists(ucfirst($class), $method))
        {
            //return response
            $this->Pterror('Method does not exist', 400);
        }

        //-----------------------------------------------------------------------------------------------------------------------------

        if ($error_auth === false) {

            //call the method
            $rfMethod = new ReflectionMethod($class, $method);

            $sgNums = $rfMethod->getNumberOfParameters();

            array_push($segments, $this->vars['data']);

            $result = $rfMethod->invokeArgs(new $class(), $segments);

            $return_data = array(
                'data'     => null,
                'message'  => '',
                'httpcode' => 200,
                'success'  => true
            );
            $return_data = array_merge($return_data, $result);
        }
        //return
        $this->Ptsucc($return_data['data'], $return_data['message'], $return_data['httpcode'], $return_data['success']);
    }



    // --------------------------------------------------------------------
        
    /**
     * Get Variables
     */
    private function _get_vars($method, $required=array(), $defaults=array())
    {
        $vars = array();

        // populate the variables
        foreach ($this->_args as $key => $val) 
        {
            $vars[$key] = $this->{$method}($key);
        }

        $missing = array();

        // check if any required variables are not set or blank
        foreach ($required as $key) 
        {
            if (!isset($vars[$key]) OR $vars[$key] == '')
            {
                $missing[] = $key;
            }
        }

        if (count($missing))
        {
            $this->Pterror('Required variables missing: '.implode(', ', $missing), 400);
        }

        // populate fields with defaults if not set
        foreach ($defaults as $key => $val) 
        {
            if (!isset($vars[$key]))
            {
                $vars[$key] = $val;
            }
        }

        return $vars;
    }
    
    /**
     * Response
     *
     * Takes pure data and optionally a status code, then creates the response.
     *
     * @param array $data
     * @param null|int $http_code
     */
    public function response($data = array(), $http_code = null)
    {

        // If data is empty and not code provide, error and bail
        if (empty($data) && $http_code === null)
        {
            $http_code = 404;

            // create the output variable here in the case of $this->response(array());
            $output = NULL;
        }

        // If data is empty but http code provided, keep the output empty
        else if (empty($data) && is_numeric($http_code))
        {
            $output = NULL;
        }

        // Otherwise (if no data but 200 provided) or some data, carry on camping!
        else
        {
            // Is compression requested?
            if (ee()->config->item('compress_output') === TRUE && $this->_zlib_oc == FALSE)
            {
                if (extension_loaded('zlib'))
                {
                    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
                    {
                        ob_start('ob_gzhandler');
                    }
                }
            }

            is_numeric($http_code) OR $http_code = 200;

            //no data 
            if(!isset($this->response->format))
            {
                $output = $data;
            }
            else
            {
                // If the format method exists, call and return the output in that format
                if (method_exists($this, '_format_'.$this->response->format))
                {
                    // Set the correct format header
                    header('Content-Type: '.$this->_supported_formats[$this->response->format]);

                    $output = $this->{'_format_'.$this->response->format}($data);
                }

                // If the format method exists, call and return the output in that format
                elseif (method_exists(ee()->format, 'to_'.$this->response->format))
                {
                    // Set the correct format header
                    header('Content-Type: '.$this->_supported_formats[$this->response->format]);

                    $output = ee()->format->factory($data)->{'to_'.$this->response->format}();
                }

                // Format not supported, output directly
                else
                {
                    $output = $data;
                }
            }
        }

        header('HTTP/1.1: ' . $http_code);
        header('Status: ' . $http_code);

        // If zlib.output_compression is enabled it will compress the output,
        // but it will not modify the content-length header to compensate for
        // the reduction, causing the browser to hang waiting for more data.
        // We'll just skip content-length in those cases.
        if ( ! $this->_zlib_oc && ! ee()->config->item('compress_output'))
        {
            header('Content-Length: ' . strlen($output));
        }

        exit($output);
    }

    function Ptecho($data, $msg = '', $httpcode = 200, $succ = true)
    {
        $ret = array(
            'success'  => $succ,
            'message'  => $msg,
            'httpcode' => $httpcode,
            'data'     => $data
        );
        $this->response($ret, $httpcode);
    }


    function Pterror($msg = '', $httpcode = 200, $succ = false)
    {
        $this->Ptecho(null, $msg, $httpcode, $succ);
    }


    function Ptsucc($data, $msg = '', $httpcode = 200, $succ = true)
    {
        $this->Ptecho($data, $msg, $httpcode, $succ);
    }

    
     // ----------------------------------------------------------------------

     /**
     * Detect the method
     */
    protected function _detect_method()
    {
        $method = strtolower(ee()->input->server('REQUEST_METHOD'));
        
        if (ee()->config->item('enable_emulate_request')) 
        {
            if (ee()->input->post('_method')) 
            {
                $method = strtolower(ee()->input->post('_method'));
            } 
            else if (ee()->input->server('HTTP_X_HTTP_METHOD_OVERRIDE')) 
            {
                $method = strtolower(ee()->input->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            }      
        }
        
        if (in_array($method, array('get', 'delete', 'post', 'put')))
        {
            return $method;
        }
        return 'get';
    }
    
    /*
     * Detect SSL use
     *
     * Detect whether SSL is being used or not
     */
    protected function _detect_ssl()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
    }
    
    /**
     * Retrieve a value from the GET request arguments.
     *
     * @param string $key The key for the GET request argument to retrieve
     * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string The GET argument value.
     */
    public function get($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL)
        {
            return $this->_get_args;
        }

        return array_key_exists($key, $this->_get_args) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the POST request arguments.
     *
     * @param string $key The key for the POST request argument to retrieve
     * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string The POST argument value.
     */
    public function post($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL)
        {
            return $this->_post_args;
        }

        return array_key_exists($key, $this->_post_args) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the PUT request arguments.
     *
     * @param string $key The key for the PUT request argument to retrieve
     * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string The PUT argument value.
     */
    public function put($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL)
        {
            return $this->_put_args;
        }

        return array_key_exists($key, $this->_put_args) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the DELETE request arguments.
     *
     * @param string $key The key for the DELETE request argument to retrieve
     * @param boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string The DELETE argument value.
     */
    public function delete($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL)
        {
            return $this->_delete_args;
        }

        return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : FALSE;
    }
    
     /*
     * Detect input format
     *
     * Detect which format the HTTP Body is provided in
     */
    protected function _detect_input_format()
    {
        if (ee()->input->server('CONTENT_TYPE'))
        {
            // Check all formats against the HTTP_ACCEPT header
            foreach ($this->_supported_formats as $format => $mime)
            {
                if (strpos($match = ee()->input->server('CONTENT_TYPE'), ';'))
                {
                    $match = current(explode(';', $match));
                }

                if ($match == $mime)
                {
                    return $format;
                }
            }
        }

        return NULL;
    }
    
    /**
     * Parse GET
     */
    protected function _parse_get()
    {
        // Grab proper GET variables
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $get);

        // Merge both the URI segments and GET params
        $this->_get_args = array_merge($this->_get_args, $get);
    }

    /**
     * Parse POST
     */
    protected function _parse_post()
    {
        $this->_post_args = $_POST;

        $this->request->format and $this->request->body = file_get_contents('php://input');
    }

    /**
     * Parse PUT
     */
    protected function _parse_put()
    {
        // It might be a HTTP body
        if ($this->request->format)
        {
            $this->request->body = file_get_contents('php://input');
        }

        // If no file type is provided, this is probably just arguments
        else
        {
            parse_str(file_get_contents('php://input'), $this->_put_args);
        }
    }

    /**
     * Parse DELETE
     */
    protected function _parse_delete()
    {
        // Set up out DELETE variables (which shouldn't really exist, but sssh!)
        parse_str(file_get_contents('php://input'), $this->_delete_args);
    }
    
     /**
     * Process to protect from XSS attacks.
     *
     * @param string $val The input.
     * @param boolean $process Do clean or note the input.
     * @return string
     */
    protected function _xss_clean($val, $process)
    {
        return $process ? ee()->security->xss_clean($val) : $val;
    }
    
    /**
     * Detect format
     *
     * Detect which format should be used to output the data.
     *
     * @return string The output format.
     */
    protected function _detect_output_format()
    {
        //pattern = '/\.('.implode('|', array_keys($this->_supported_formats)).')$/';

        // Check if a file extension is used
        // if (preg_match($pattern, ee()->uri->uri_string(), $matches))
        // {
            // return $matches[1];
        // }
        $pattern = array_keys($this->_supported_formats);
        if(in_array(ee()->uri->segment(4), $pattern))
        {
            return ee()->uri->segment(4);
        }
        // Just use the default format
        return 'json';
    }

    /**
     * Detect language(s)
     *
     * What language do they want it in?
     *
     * @return null|string The language code.
     */
    protected function _detect_lang()
    {
        if ( ! $lang = ee()->input->server('HTTP_ACCEPT_LANGUAGE'))
        {
                return NULL;
        }

        // They might have sent a few, make it an array
        if (strpos($lang, ',') !== FALSE)
        {
            $langs = explode(',', $lang);

            $return_langs = array();
            $i = 1;
            foreach ($langs as $lang)
            {
                // Remove weight and strip space
                list($lang) = explode(';', $lang);
                $return_langs[] = trim($lang);
            }

            return $return_langs;
        }

        // Nope, just return the string
        return $lang;
    }
}
/* End of file webservice_rest.php */
/* Location: /system/expressionengine/third_party/webservice/libraries/webservice_rest.php */