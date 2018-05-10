<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Parser;


class Uauth
{
    public $limit;
    public $offset;
    public $total_results;
    public $absolute_results;

    const JWT_ISS             = 'http://pintu360.com';
    const JWT_AUD             = 'http://pintu360.com';
    const JWT_KEY             = 'Pt2018!';
    const REYPLAY_ATTACK_TIME = 300;

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


    public function token($pdata = [])
    {
        $username = $pdata['username'];
        $password = $pdata['password'];
        // get member id
        $query = ee()->db->get_where('members', array('username' => $username));
        if (!$row = $query->row()) {
            throw new Exception("user no found");
        }
        
        $member_id = $row->member_id;
        // authenticate member
        $memberData = $this->_authenticate_member($member_id, $password);

        if ($memberData) {
            $signer = new Sha256();
            $token  = (new Builder())->setIssuer(self::JWT_ISS) // Configures the issuer (iss claim)
                        ->setAudience(self::JWT_AUD) // Configures the audience (aud claim)
                        ->setId($this->session_id, true) // Configures the id (jti claim), replicating as a header item
                        ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
                        ->setNotBefore(time()) // Configures the time that the token can be used (nbf claim)
                        ->setExpiration(time() + 2592000) // Configures the expiration time of the token (exp claim)
                        ->set('uid', $memberData->member_id) // Configures a new claim, called "uid"
                        ->sign($signer,  self::JWT_KEY) // creates a signature using your private key
                        ->getToken(); // Retrieves the generated token
        }else {
            throw new Exception("username or password invalid");
        }
        return [
            'data' => [
                'token'     => $token->__toString(),
                'expire_in' => $token->getClaim('exp')
            ],
        ];
    }


    /**
     * 验证jwt token合法性
     * @param  string $token
     * @return false|uid
     */
    public function verify_jwt($token)
    {
        try {
            $token = (new Parser())->parse((string) $token); // Parses from a string
            $token->getHeaders(); // Retrieves the token header
            $token->getClaims(); // Retrieves the token claims

            $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
            $data->setIssuer($token->getClaim('iss'));
            $data->setAudience($token->getClaim('aud'));
            $data->setId($token->getClaim('jti'));
            if (!$token->validate($data)) {
                throw new Exception("authentication failed");
            }
            //设置session
            $session_id = $token->getClaim('jti');
            //get session from session_id
            $this->_authenticate_session($session_id);
            return $token->getClaim('uid');
        } catch (Exception $e) {
            throw new Exception("authentication failed");
        }
    }


    public static function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }


    /**
     * Authenticate Member
     */
    private function _authenticate_member($member_id, $password)
    {
        // start hook
        // $member_id = $this->_hook('authenticate_member_start', $member_id);

        // load auth library
        ee()->load->library('auth');
        
        // authenticate member id
        $userdata = ee()->auth->authenticate_id($member_id, $password);

        if (!$userdata)
        {
            return false;
        }

        // success hook
        $this->_hook('authenticate_member_success', $member_id);

        // create a new session id
        $this->session_id = ee()->session->create_new_session($member_id);
        //set some data
        $this->_fetch_member_data($member_id);
        $this->_setup_channel_privs();
        $this->_setup_module_privs();
        $this->_setup_template_privs();
        $this->_setup_assigned_sites();

        // get member details
        $query = ee()->db->get_where('members', array('member_id' => $member_id));
        $member = $query->row();
            
        return $member;
    }

    /**
     * Authenticate Session
     *
     */
    private function _authenticate_session($session_id = '')
    {
        ee()->session->delete_old_sessions();
        // check for session id
        if ($session_id == '' || empty($session_id))
        {
            return false;
        }
        // check if session id exists in database and get member id
        ee()->db->select('member_id, user_agent, fingerprint');
        ee()->db->where('session_id', $session_id);
        $query = ee()->db->get('sessions');
        
        if (!$row = $query->row())
        {
            return false;
        }
        //set member_id
        $member_id = $row->member_id;
        // get member data
        ee()->db->select('*');
        ee()->db->where('member_id', $member_id);
        $query = ee()->db->get('members');
        $member_data = $query->row();
        
        //set some session data
        ee()->session->sdata['member_id'] = $member_id;
        ee()->session->sdata['session_id'] = $session_id;
        ee()->session->validation = null;
        ee()->session->sess_crypt_key = $member_data->crypt_key;

        //create al other stuff for the logged in member
        $this->_fetch_member_data($member_id);
        $this->_setup_channel_privs();
        $this->_setup_module_privs();
        $this->_setup_template_privs();
        $this->_setup_assigned_sites();

        return array(
            'member_id'   => $member_id,
            'username'    => $member_data->username,
            'screen_name' => $member_data->screen_name
        );
    }


    /**
     * Setup Assigned Sites
     *
     * @return void
     */
    protected function _setup_assigned_sites()
    {
        // Fetch Assigned Sites Available to User

        $assigned_sites = array();

        if (ee()->session->userdata('group_id') == 1)
        {
            $qry = ee()->db->select('site_id, site_label')
                                ->order_by('site_label')
                                ->get('sites');
        }
        else
        {
            // Groups that can access the Site's CP, see the site in the 'Sites' pulldown
            $qry = ee()->db->select('es.site_id, es.site_label')
                                ->from(array('sites es', 'member_groups mg'))
                                ->where('mg.site_id', ' es.site_id', FALSE)
                                ->where('mg.group_id', ee()->session->userdata('group_id'))
                                ->order_by('es.site_label')
                                ->get();
        }

        if ($qry->num_rows() > 0)
        {
            foreach ($qry->result() as $row)
            {
                $assigned_sites[$row->site_id] = $row->site_label;
            }
        }

        ee()->session->userdata['assigned_sites'] = $assigned_sites;
    }


    /**
     * Perform the big query to grab member data
     *
     * @return  object  database result.
     */
    private function _fetch_member_data($member_id = 0)
    {
        // Query DB for member data.  Depending on the validation type we'll
        // either use the cookie data or the member ID gathered with the session query.

        ee()->db->from(array('members m', 'member_groups g'))
            ->where('g.site_id', (int) ee()->config->item('site_id'))
            ->where('m.group_id', ' g.group_id', FALSE);

        ee()->db->where('member_id', (int) $member_id);

        $member_query = ee()->db->get();

        // Turn the query rows into array values
        foreach ($member_query->row_array() as $key => $val)
        {
            if ($key != 'crypt_key')
            {
                ee()->session->userdata[$key] = $val;
            }
        }

        //set member_id
        ee()->session->userdata['member_id'] = $member_id;


    }

    /**
     * Setup CP Channel Privileges
     *
     * @return void
     */
    protected function _setup_channel_privs()
    {
        // Fetch channel privileges

        $assigned_channels = array();

        if (ee()->session->userdata('group_id') == 1)
        {
            ee()->db->select('channel_id, channel_title');
            ee()->db->order_by('channel_title');
            $res = ee()->db->get_where(
                'channels',
                array('site_id' => ee()->config->item('site_id'))
            );
        }
        else
        {
            ee()->db->save_queries = true;
            $res = ee()->db->select('ec.channel_id, ec.channel_title')
                ->from(array('channel_member_groups ecmg', 'channels ec'))
                ->where('ecmg.channel_id', 'ec.channel_id',  FALSE)
                ->where('ecmg.group_id', ee()->session->userdata('group_id'))
                ->where('site_id', ee()->config->item('site_id'))
                ->order_by('ec.channel_title')
                ->get();
        }

        if ($res->num_rows() > 0)
        {
            foreach ($res->result() as $row)
            {
                $assigned_channels[$row->channel_id] = $row->channel_title;
            }
        }

        $res->free_result();

        ee()->session->userdata['assigned_channels'] = $assigned_channels;
    }

    /**
     * Setup Module Privileges
     *
     * @return void
     */
    protected function _setup_module_privs()
    {
        $assigned_modules = array();

        ee()->db->select('module_id');
        $qry = ee()->db->get_where('module_member_groups',
                                        array('group_id' => ee()->session->userdata('group_id')));

        if ($qry->num_rows() > 0)
        {
            foreach ($qry->result() as $row)
            {
                $assigned_modules[$row->module_id] = TRUE;
            }
        }

        ee()->session->userdata['assigned_modules'] = $assigned_modules;

        $qry->free_result();
    }

    // --------------------------------------------------------------------

    /**
     * Setup Template Privileges
     *
     * @return void
     */
    protected function _setup_template_privs()
    {
        $assigned_template_groups = array();

        ee()->db->select('template_group_id');
        $qry = ee()->db->get_where('template_member_groups',
                                        array('group_id' => ee()->session->userdata('group_id')));


        if ($qry->num_rows() > 0)
        {
            foreach ($qry->result() as $row)
            {
                $assigned_template_groups[$row->template_group_id] = TRUE;
            }
        }

        ee()->session->userdata['assigned_template_groups'] = $assigned_template_groups;

        $qry->free_result();
    }

    // --------------------------------------------------------------------



    /**
     * 生成防重复攻击的一次性token
     * @return 
     */
    public function create_replay_attack_token()
    {
        $t     = time();
        $nonce = pt_rand_str(32);
        $key   = self::JWT_KEY;
        $token = md5($t+$nonce+$key);
        return array(
            'token'    => $token,
            'nonce'    => $nonce,
            'unixtime' => $t,
        );
    }


    public function check_replay_attack_token($token, $nonce, $unixtime)
    {
        $key = self::JWT_KEY;
        $md5_string = md5($unixtime+$nonce+$key);
        if ($md5_string != $token) {
            throw new Exception("bad request");
        }
        if (time() - $unixtime > self::REYPLAY_ATTACK_TIME ) {
            throw new Exception("bad request");
        }
        $cached = ee()->cache->get($nonce);
        if ($cached) {
            throw new Exception("bad request");
        }
        // Cache version information for a day
        ee()->cache->save(
            $nonce,
            true,
            self::REYPLAY_ATTACK_TIME
        );
        return true;
    }


    public function check_replay_attack()
    {
        $headers = null;
        if (isset($_SERVER['X_PT_RA'])) {
            $headers = trim($_SERVER["X_PT_RA"]);
        }
        else if (isset($_SERVER['HTTP_X_PT_RA'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_X_PT_RA"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['X_PT_RA'])) {
                $headers = trim($requestHeaders['X_PT_RA']);
            }
        }
        $replay_attack_arr = explode(':', $headers);
        if (count($replay_attack_arr) != 3) {
            throw new Exception("bad request");
        }
        return $this->check_replay_attack_token($replay_attack_arr[0],$replay_attack_arr[1],$replay_attack_arr[2]);
    }


    /**
     * Hook - allows each method to check for relevant hooks
     */
    private function _hook($hook='', $data=array())
    {
        if ($hook AND ee()->extensions->active_hook('pintuapi_'.$hook) === TRUE)
        {
            $data = ee()->extensions->call('pintuapi_'.$hook, $data);
            if (ee()->extensions->end_script === TRUE) return;
        }
        
        return $data;
    }
}

