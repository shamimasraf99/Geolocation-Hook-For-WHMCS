<?php

namespace ModulesGarden\Geolocation\Submodules;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use ModulesGarden\Geolocation\Helpers\ServerHelper;

/**
 * Submodule GeoIP2
 */
class GeoIP2
{
    /**
     * getCountry return ISO 3166-1 Country Code (Alpha-2) by IP
     * @throws InvalidDatabaseException
     * @throws AddressNotFoundException
     */
    public function getCountry()
    {
        if ( !file_exists(__DIR__ . DIRECTORY_SEPARATOR . "GeoIP2" . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb') )
        {
            throw new \Exception('GeoLite2-Country.mmdb file not found.');
        }

        $reader = new Reader(__DIR__ . DIRECTORY_SEPARATOR . 'GeoIP2' . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb');
        $record = $reader->country(ServerHelper::getCurrentIPAddress());

        return $record->country->isoCode;
    }
}
