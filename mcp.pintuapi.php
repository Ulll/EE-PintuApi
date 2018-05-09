<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

require __DIR__ . '/vendor/autoload.php';

class Pintuapi_mcp
{
    public function __construct()
    {
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');
        $this->prefix_params = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pintuapi';
        $this->base_url = BASE.AMP.$this->prefix_params;

        // load table lib for control panel
        $this->EE->load->library('table');
        $this->EE->load->helper('form');

        $this->EE->cp->load_package_css('style');


        // Set page title
        // $this->EE->cp->set_variable was deprecated in 2.6
        if (version_compare(APP_VER, '2.6', '>=')) {
            $this->EE->view->cp_page_title = $this->EE->lang->line('pintuapi_module_name');
        } else {
            $this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('pintuapi_module_name'));
        }
    }
    /**
     * Module CP index function
     *
     * @return void
     * @author Bryant Hughes
     */
    public function index()
    {
        $this->data['form_action'] = $this->prefix_params.AMP.'method=submit_settings';
        return $this->EE->load->view('index', $this->data, TRUE);
    }
}

/* End of File: mcp.module.php */
