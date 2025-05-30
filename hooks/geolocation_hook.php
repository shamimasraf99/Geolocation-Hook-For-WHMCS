<?php

use ModulesGarden\Geolocation\Geolocation;
use ModulesGarden\Geolocation\Helpers\ServerHelper;

/**
 * GEOLOCATION HOOK FOR WHMCS
 * This hook will automatically setup currency and language
 * for not logged users in your client area.
 * 
 * 
 * For more information, read the whole article here:
 * http://blog.whmcs.com
 * 
 * 
 * @author  ModulesGarden
 * @link    http://www.modulesgarden.com
 */


try {

    require dirname(__DIR__) . '/Geolocation/vendor/autoload.php';
    require dirname(__DIR__) . '/Geolocation/config.php';

    if(php_sapi_name() === 'cli' || !ServerHelper::getCurrentIPAddress())
    {
        //disable module when array is not available
        return;
    }

    $geolocation = new Geolocation();
    if($geolocation->isAdminArea()){
        return;
    }
    if($geolocation->isAjaxRequest()){
        return;
    }
    /** 
     * Configuration
     */
    $geolocation->setResponseCode($responseCode);
    $geolocation->setCountryCurrency($countryToCurrency);
    $geolocation->setCountryLanguage($countryToLanguage);
    $geolocation->setCountryTemplate($countryToTemplate);
    $geolocation->setLanguageTemplate($templateToLanguage);
    $geolocation->setAllowedScripts($allowedScripts);
    $geolocation->setDisabledForIPs($disabledForIPs);
    $geolocation->setDisabledForCidrIPs($disabledForCidrIPs);
    $geolocation->setDisabledForBrowsers($disabledForBrowsers);
    $geolocation->setMobileToTemplate((array)$mobileToTemplate);
    $geolocation->setDomainToTemplate((array)$domainToTemplate);

    /**
     * Set Submodule to get a country by IP 
     * NOTE: You can create your own submodule.
     */
    $geolocation->setSubmodule($submodule);

    /**
     * Main Geolocation Function
     * 
     * NOT run script
     * - if we are in admin area
     * - if already setup for this session
     * - if user is logged in
     * - allowing to run the hook only for specific scripts (defined above)
     */
    $geolocation->run($allowCurrencySession, $allowRedirectCountryToTemplate, $allowRedirectLanguageToTemplate, $allowURLRedirection, $preserveTemplate, $allowChangeLanguage);
    /**
     * Change template if
     * - current template is NOT correct for this language
     * - current template is NOT default if no language was chosen
     */
    if($allowRedirectLanguageToTemplate) {
        $geolocation->changeTemplateByLanguage();
    }
    $geolocation->changeTemplateByDomain();
    $geolocation->changeTemplateByMobile();
    /**
     * Preventing from switching currency by user
     */
    if($preventSwitchCurrency) {
        $geolocation->preventSwitchCurrency();
    }
} catch (\Exception $e)
{
    \logModuleCall("Geo Location Hook","error", "", $e->getMessage()); 
    
}
