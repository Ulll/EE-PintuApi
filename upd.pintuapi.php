<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pintuapi_upd
{
    public $version = '1.0.0';

    public $module_class = 'Pintuapi';

    public function __construct()
    {
        $this->EE =& get_instance();
    }

    /**
     * 安装扩展
     * @return boolean
     */
    public function install()
    {
        $this->EE->db->insert('modules', array(
            'module_name' => 'pintuapi',
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        ));
        //install the extension
        $this->_register_hook('sessions_start', 'sessions_start');
        return TRUE;
    }

    /**
     * 升级扩展
     * @param  string $current
     * @return boolean
     */
    public function update( $current = '' )
    {
        if($current == $this->version) {
            return FALSE; 
        }
        return TRUE;
    }

    /**
     * 卸载扩展
     * @return json
     */
    public function uninstall()
    {
        $this->EE->load->dbforge();
        $this->EE->db->query("DELETE FROM exp_modules WHERE module_name = 'pintuapi'");
        return TRUE;
    }

    /**
     * Install a hook for the extension
     *
     * @return  boolean     TRUE
     */     
    private function _register_hook($hook, $method = NULL, $priority = 11)
    {
        if (is_null($method))
        {
            $method = $hook;
        }

        if (ee()->db->where('class', $this->module_class.'_ext')
            ->where('hook', $hook)
            ->count_all_results('extensions') == 0)
        {
            ee()->db->insert('extensions', array(
                'class'     => $this->module_class.'_ext',
                'method'    => $method,
                'hook'      => $hook,
                'settings'  => '',
                'priority'  => $priority,
                'version'   => $this->version,
                'enabled'   => 'y'
            ));
        }
    }
}

/* End of File: upd.module.php */
