<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tool
{   
    /**
     * 获取服务器时间
     * @return rest
     */
    public function servertime()
    {
        return [
            'data' => [
                'unixtime' => time()
            ]
        ];
    }
}