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

    const JWT_ISS = 'http://pintu360.com';
    const JWT_AUD = 'http://pintu360.com';
    const JWT_KEY = 'Pt2018';

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
                        ->setId(pt_rand_str('16'), true) // Configures the id (jti claim), replicating as a header item
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
        $session_id = ee()->session->create_new_session($member_id);

        // get member details
        $query = ee()->db->get_where('members', array('member_id' => $member_id));
        $member = $query->row();
        
        return $member;
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

