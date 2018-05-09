<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function pt_rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    $chars_length = (strlen($chars)-1);//Length of character list
    $string = $chars{rand(0, $chars_length)};//Start our string
        
    //Generate random string
    for($i=1; $i < $length; $i = strlen($string))
    {
        $r = $chars{rand(0,$chars_length)};//Grab a random character from our list
        if ($r != $string{$i - 1}) $string .= $r;//Make sure the same two characters don't appear next to each other
    }
    
    return $string;
}