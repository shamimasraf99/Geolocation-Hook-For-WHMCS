<?php

namespace ModulesGarden\Geolocation\Helpers;

class ServerHelper
{
    private static $headers = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CF_CONNECTING_IP',
        'REMOTE_ADDR'
    ];

    public static function getCurrentIPAddress()
    {
        foreach(self::$headers as $header)
        {
            $ip = $_SERVER[$header] ?? null;
            if($ip)
            {
                return $ip;
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

}