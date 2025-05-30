<?php

namespace ModulesGarden\Geolocation\Submodules;

use ModulesGarden\Geolocation\Helpers\ServerHelper;

/**
 * Submodule GeoIP
 */
class GeoIP {
    /**
     * getCountry return ISO 3166-1 Country Code (Alpha-2) by IP
     */
    public function getCountry() {    
        
        if(!file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."GeoIP".DIRECTORY_SEPARATOR."geoip.inc"))
        {
            throw new \Exception('Geoip.inc file not found.');
        }
        if(!file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."GeoIP".DIRECTORY_SEPARATOR."GeoIP.dat"))
        {
            throw new \Exception('GeoIP.dat file not found.');
        }
        include dirname(__FILE__).DIRECTORY_SEPARATOR."GeoIP".DIRECTORY_SEPARATOR."geoip.inc";
        if(!function_exists('geoip_open'))
        {
            throw new \Exception('Geoip extension has not loaded.');
        }
        
        $gi = geoip_open(dirname(__FILE__).DIRECTORY_SEPARATOR."GeoIP".DIRECTORY_SEPARATOR."GeoIP.dat", GEOIP_STANDARD); 
        $currentCountry = geoip_country_code_by_addr($gi, ServerHelper::getCurrentIPAddress());
        geoip_close($gi);
        return $currentCountry;
    }
}
