<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pintuapi_route
{   
    /**
     * CI_Config class object
     *
     * @var object
     */
    public $config;

    /**
     * List of routes
     *
     * @var array
     */
    public $routes =    array();

    /**
     * Current class name
     *
     * @var string
     */
    public $class =     '';

    /**
     * Current method name
     *
     * @var string
     */
    public $method =    'index';

    /**
     * Sub-directory that contains the requested controller class
     *
     * @var string
     */
    public $directory = '';


    public $segments = null;

    /**
     * Default controller (and method if specific)
     *
     * @var string
     */
    public $default_controller;

    /**
     * Translate URI dashes
     *
     * Determines whether dashes in controller & method segments
     * should be automatically replaced by underscores.
     *
     * @var bool
     */
    public $translate_uri_dashes = FALSE;

    /**
     * Enable query strings flag
     *
     * Determines wether to use GET parameters or segment URIs
     *
     * @var bool
     */
    public $enable_query_strings = FALSE;


    /**
     * Constructor
     */
    public function __construct()
    {
        include_once PATH_THIRD .'pintuapi/config/routes.php';
        //load the helper
        ee()->load->helper('url');

        $this->routes = $route;

        $this->_parse_routes();

        $this->rest = new Pintuapi_rest();
        $this->rest->call($this->class, $this->method, $this->segments);
    }


    /**
     * Parse Routes
     *
     * Matches any routes that may exist in the config/routes.php file
     * against the URI to determine if the class/method need to be remapped.
     *
     * @return  void
     */
    protected function _parse_routes()
    {
        $segments = ee()->uri->segment_array();
        array_shift($segments);
        // Turn the segment array into a URI string
        $uri = implode('/', $segments);

        // Get HTTP verb
        $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

        // Is there a literal match?  If so we're done
        if (isset($this->routes[$uri]))
        {
            // Check default routes format
            if (is_string($this->routes[$uri]))
            {
                $this->_set_request(explode('/', $this->routes[$uri]));
                return;
            }
            // Is there a matching http verb?
            elseif (is_array($this->routes[$uri]) && isset($this->routes[$uri][$http_verb]))
            {
                $this->_set_request(explode('/', $this->routes[$uri][$http_verb]));
                return;
            }
        }

        // Loop through the route array looking for wildcards
        foreach ($this->routes as $key => $val)
        {
            // Check if route format is using http verb
            if (is_array($val))
            {
                if (isset($val[$http_verb]))
                {
                    $val = $val[$http_verb];
                }
                else
                {
                    continue;
                }
            }

            // Convert wildcards to RegEx
            $key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);

            // Does the RegEx match?
            if (preg_match('#^'.$key.'$#', $uri, $matches))
            {

                // Are we using callbacks to process back-references?
                if ( ! is_string($val) && is_callable($val))
                {
                    // Remove the original string from the matches array.
                    array_shift($matches);

                    // Execute the callback using the values in matches as its parameters.
                    $val = call_user_func_array($val, $matches);
                }
                // Are we using the default routing method for back-references?
                elseif (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE)
                {
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }

                $this->_set_request(explode('/', $val));
                return;
            }
        }

        throw new Exception("API NoFound");
    }

    protected function _set_request($segments = array())
    {
        // If we don't have any segments left - try the default controller;
        // WARNING: Directories get shifted out of the segments array!
        if (empty($segments))
        {
            throw new Exception("API NoFound");
        }

        if ($this->translate_uri_dashes === TRUE)
        {
            $segments[0] = str_replace('-', '_', $segments[0]);
            if (isset($segments[1]))
            {
                $segments[1] = str_replace('-', '_', $segments[1]);
            }
        }

        $this->set_class($segments[0]);
        if (isset($segments[1]))
        {
            $this->set_method($segments[1]);
        }
        else
        {
            $segments[1] = 'index';
        }
        unset($segments[0],$segments[1]);
        
        $segments = array_values($segments);
        
        $this->segments = $segments;
    }

    /**
     * Set class name
     *
     * @param   string  $class  Class name
     * @return  void
     */
    public function set_class($class)
    {
        $this->class = str_replace(array('/', '.'), '', $class);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the current class
     *
     * @deprecated  3.0.0   Read the 'class' property instead
     * @return  string
     */
    public function fetch_class()
    {
        return $this->class;
    }

    // --------------------------------------------------------------------

    /**
     * Set method name
     *
     * @param   string  $method Method name
     * @return  void
     */
    public function set_method($method)
    {
        $this->method = $method;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the current method
     *
     * @deprecated  3.0.0   Read the 'method' property instead
     * @return  string
     */
    public function fetch_method()
    {
        return $this->method;
    }
}