<?php
/**
 * This file contains all configuration options (and their default values)
 * for the StatusPage library.
 *
 * Duplicate it, name it config.php and change any options you want.
 */
return array(
    'libPath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR,
    'sortByName' => true,
    'sortOfflineFirst' => true,
    'curl_verifypeer' => true, // If a HTTPS API is having issues with certificates, disable this.

    'services' => array(
        'nodeping' => array(
            'apiKey' => '', // REQUIRED

            'useAutoUpdate' => true,
            'checks' => array(),
            'excludeChecks' => array(),
            'cacheExpires' => 60, // Result data
            'checksCacheExpires' => 1800, // this is the checks meta info

            'apiUrl' => 'https://api.nodeping.com/api/1/',
            'responseTimeDecimals' => 2,
        ),
        'pingdom' => array(
            'apiKey' => 'e8m2doedrm29kigfiiq08bzgmgaf9i57', // Does not need to be site specific
            'username' => '',
            'password' => '',

            'useAutoUpdate' => true,
            'checks' => array(),
            'excludeChecks' => array(),
            'cacheExpires' => 60,

            'apiUrl' => 'https://api.pingdom.com/api/2.0/',
            'responseTimeDecimals' => 2,
        )
    )
);
