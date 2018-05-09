<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
*/

//auth
$route['auth/token'] = array(
    'post' => "uauth/token",
    // 'get' => "uauth/token",
);

//entities
$route['entry/([0-9]+)/update'] = array(
    'post' => "entry/update_entry/$1",
);

$route['entry/([0-9]+)/share']  = array(
    'get' => "entry/share/$1",
);

