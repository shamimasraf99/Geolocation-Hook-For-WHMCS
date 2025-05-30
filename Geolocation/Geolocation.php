<?php

namespace ModulesGarden\Geolocation;

use Illuminate\Database\Capsule\Manager as DB;
use ModulesGarden\Geolocation\Helpers\ServerHelper;

class Geolocation
{
    private $responseCode = 301;
    private $countryCurrency = array();
    private $countryLanguage = array();
    private $countryTemplate = array();
    private $languageTemplate = array();
    private $allowedScripts = array();
    private $disabledForIPs = array();
    private $disabledForCidrIPs = array();
    private $disabledForBrowsers = array();

    private $scriptPath;
    private $currentTemplate;
    private $language;
    private $country;
    private $whmcsVersion;

    private $submodule;
    private $mobileToTemplate;
    private $mobileDetect;
    private $clientTemplate;
    private $domainToTemplate;
    private $domain;
    private $enable = false;

    public function __construct()
    {
        $this->whmcsVersion = explode('-',$GLOBALS['CONFIG']['Version'])[0];
        $this->defineScriptPath();
        $this->currentTemplate = $_SESSION['Template'];
        $this->language = $_SESSION['Language'];
    }

    public function setSubmodule($submodule)
    {
        $submodule = __NAMESPACE__ . "\\Submodules\\" . $submodule;
        $this->submodule = new $submodule();
    }

    public function setResponseCode($code)
    {
        $this->reponseCode = $code;
    }

    public function setCountryCurrency($currencies)
    {
        $this->countryCurrency = $currencies;
    }

    public function setCountryLanguage($languages)
    {
        $this->countryLanguage = $languages;
    }

    public function setCountryTemplate($templates)
    {
        $this->countryTemplate = $templates;
    }

    public function setLanguageTemplate($templates)
    {
        $this->languageTemplate = $templates;
    }

    public function setAllowedScripts($scripts)
    {
        $this->allowedScripts = $scripts;
    }

    public function setDisabledForIPs($adresses)
    {
        $this->disabledForIPs = $adresses;
    }

    public function setDisabledForCidrIPs($adresses)
    {
        $this->disabledForCidrIPs = $adresses;
    }

    public function setDisabledForBrowsers($browsers)
    {
        $this->disabledForBrowsers = $browsers;
    }

    /**
     * Preventing from switching currency by user
     */
    public function preventSwitchCurrency()
    {
        if (isset($_SESSION['switched_currency']) && $_SESSION['switched_currency'] != $_SESSION['currency']) {
            $_SESSION['currency'] = $_SESSION['switched_currency'];
        }
    }

    private function getCurrencyId($currency)
    {
        return $this->value(
            DB::table('tblcurrencies')
                ->where('code', '=', $currency),
            'id');
    }

    private function value($db, $column)
    {
        $result = (array)$db->first([$column]);

        return count($result) > 0 ? reset($result) : null;
    }

    private function redirect($location)
    {
        @ob_clean();
        header('location: ' . html_entity_decode($location), true, $this->responseCode);
        die();
    }

    private function isAllowedScript()
    {
        return in_array($this->scriptPath, $this->allowedScripts);
    }

    /**
     * @deprecated since version 2.0.2
     */
    private function isNotAdminFolder()
    {
        global $customadminpath;
        $adminPath = $customadminpath ?: 'admin';
        return strpos($_SERVER['REQUEST_URI'], $adminPath) === false;
    }

    /**
     *
     * @return boolean
     * @assert (param1, param2) == expectedResult
     */
    public function isAdminArea()
    {
        if ((defined('ADMINAREA') && ADMINAREA) || isset($_SESSION['adminid'])) {
            return true;
        }
        return false;
    }

    public function isAjaxRequest()
    {
        return isset($_REQUEST['ajax']) && $_REQUEST['ajax'];
    }

    public function isUserLogged()
    {
        return isset($_SESSION['uid']) && $_SESSION['uid'];
    }

    private function isConfigured()
    {
        //to do
        unset($_SESSION['geolocation_setup']);
        return $_SESSION['geolocation_setup'] === true;
    }

    private function beforeRun()
    {
        if ($this->isAdminArea()) {
            return $this->enable = false;
        }
        if (!$this->isAllowedScript()) {
            return $this->enable = false;
        }
        if (!$this->isBrowserValid()) {
            return $this->enable = false;
        }
        if (!$this->isIpValid()) {
            return $this->enable = false;
        }
        if (!$this->isCidrValid()) {
            return $this->enable = false;
        }
        return $this->enable = true;
    }


    public function run($allowCurrencySession = false, $allowRedirectCountryToTemplate = false, $allowRedirectLanguageToTemplate = false, $allowURLRedirection = false, $preserveTemplate = true, $allowChangeLanguage = true)
    {
        $this->beforeRun();
        if (!$this->isEnable()) {
            return;
        }

        /* return if is not configured && is user logged */
        if (!(!$this->isConfigured() && !$this->isUserLogged())) {
            return;
        }


        $_SESSION['geolocation_setup'] = true; // prevent from redirecting back again in this session
        //SUBMODULE

        if ($this->submodule) {
            try {
                $this->country = $this->submodule->getCountry();
            } catch (\Exception $ex) {
                \logModuleCall("Geo Location Hook", "error", '', $ex->getMessage());
            }
        }
        $currencyId = null;
        /**
         * Get language, currency and currency ID in order to set up the right values in the system
         */
        if ($this->country && (isset($this->countryCurrency[$this->country]) || isset($this->countryCurrency['default']))) {
            $currency = $this->countryCurrency[$this->country] ? $this->countryCurrency[$this->country] : $this->countryCurrency['default'];
            $currencyId = $this->getCurrencyId($currency);
            if (is_null($currencyId)) {
                $currencyId = $this->getCurrencyId($this->countryCurrency['default']);
            }

            if (isset($this->countryLanguage[$this->country]) || $this->countryLanguage['default']) {
                $this->language = $this->countryLanguage[$this->country] ? $this->countryLanguage[$this->country] : $this->countryLanguage['default'];
            }
        }

        /**
         * Client Country Setup
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($this->country) {
            $this->setClientCountry($this->country);
        }

        /**
         * Currency Session Setup
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($allowCurrencySession && $currencyId && $currency) {
            $this->setupCurrencySession($currencyId, $currency);
        }

        /**
         * Currency Global Setup
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($allowCurrencySession && $currencyId) {
            $this->setupCurrencyGlobal($currencyId);
        }
        /**
         * Currency POST Setup
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($allowCurrencySession && $currencyId) {
            $this->setupCurrencyPost($currencyId);
        }
        /**
         * Setting up a template to redirect (country)
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($allowRedirectCountryToTemplate) {
            $systpl = $this->setupRedirectCountryTemplate();
        }
        /**
         * Setting up a template to redirect (language)
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($allowRedirectLanguageToTemplate) {
            $systpl = $this->setupRedirectLanguageTemplate();
        }
        /**
         * Setting up URL redirection to allow the user to switch a language
         * NOTE: You can remove/disable this part if it is not needed.
         */
        if ($allowChangeLanguage) {
            $this->changeLanguage();
        }

        if ($allowURLRedirection) {
            $this->setupURLRedirection($systpl, $preserveTemplate);
        }
    }

    private function setupCurrencySession($currencyId, $currency)
    {
        if (!$_SESSION['switched_currency'] && $currencyId && (!isset($_SESSION['currency']) || $_SESSION['currency'] != $currency)) {
            $_SESSION['currency'] = $_SESSION['switched_currency'] = $currencyId;
        }
    }

    /**
     *
     * @param type $currencyId
     * @return type
     * @global array $currency
     */
    private function setupCurrencyGlobal($currencyId)
    {
        global $currency;
        if (!$currencyId || $currency['id'] === (int)$currencyId) {
            return;
        }

        $currency['id'] = $currencyId;
        return;
    }

    private function setupCurrencyPost($currencyId)
    {
        if ($_POST['currency'] || !$currencyId) {
            return;
        }
        $_POST['currency'] = $currencyId;
    }

    private function setupRedirectLanguageTemplate()
    {
        return isset($this->languageTemplate[$this->language])
            ? $this->languageTemplate[$this->language]
            : $this->languageTemplate['default'];
    }

    private function setupRedirectCountryTemplate()
    {
        return isset($this->countryTemplate[$this->country])
            ? $this->countryTemplate[$this->country]
            : $this->countryTemplate['default'];
    }

    private function setupURLRedirection($systpl, $preserveTemplate)
    {
        if ($systpl) {
            global $whmcs;
            $ref = new \ReflectionClass($whmcs);
            $location = $_SERVER['REQUEST_URI'];
            //Template
            $_SESSION['Template'] = $systpl;
            $clientTemplate = $ref->getProperty("clientTemplate");
            $clientTemplate->setAccessible(true);

            if ($this->versionAtLeast('8.0.0'))
                $clientTemplate->setValue($whmcs, \WHMCS\View\Template\Theme::factory($systpl, $systpl));
            else
                $clientTemplate->setValue($whmcs, \WHMCS\View\Template::factory($systpl, $systpl));

            if ($preserveTemplate) {
                $_SESSION['preserveTemplate'] = $systpl ? $systpl : false;
            }
        }

    }

    public function changeLanguage()
    {
        if (!(!isset($_SESSION['Language']) || $_SESSION['Language'] != $this->language)) {
            return;
        }

        if (!$this->language) {
            return;
        }

        if (isset($_GET['language'])) {
            $this->language = $_GET['language'];
            $_SESSION['userChangedTemplate'] = true;
        }

        $this->updateWhmcsLangFile($this->language);
    }

    public function changeTemplateByLanguage()
    {
        if ($_SESSION['preserveTemplate']) {
            $systpl = $_SESSION['preserveTemplate'];
        } else {
            $systpl = isset($this->languageTemplate[$this->language])
                ? $this->languageTemplate[$this->language]
                : $this->languageTemplate['default'];
        }

        if ($this->isAllowedScript() && $this->isNotAdminFolder() && $this->currentTemplate != $systpl && !empty($systpl)) {
            if ($systpl != $_GET['systpl']) {
                $_SESSION['Template'] = $systpl;
                $nArgs = false;
                $tmp = explode('?', $_SERVER['REQUEST_URI']);
                if ($tmp[1]) {
                    parse_str($tmp[1], $args);

                    if (isset($args['systpl']) && $args['systpl'] == $_GET['systpl']) {
                        unset($args['systpl']);
                    }

                    if (isset($args['language'])) {

                        $lang = str_replace('language=', '', $args['language']);
                        $this->updateWhmcsLangFile($lang);
                    }
                }
            }
        }
    }

    private function setClientCountry($country = "US")
    {
        global $CONFIG;
        $CONFIG['DefaultCountry'] = $country;

        if(isset($_SESSION['cart']['user']['country']))
        {
            $_SESSION['cart']['user']['country'] = $country;
        }

        return true;
    }

    private function isBrowserValid()
    {
        if (!$this->disabledForBrowsers) {
            return true;
        }
        foreach ($this->disabledForBrowsers as $browser) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], $browser) !== false) {
                \logModuleCall("Geo Location Hook", "info", sprintf("Browser: %s is disabled", $_SERVER['HTTP_USER_AGENT']), "");
                return false;
            }
        }

        return true;
    }

    private function isIpValid()
    {
        if (!$this->disabledForIPs) {
            return true;
        }
        return !in_array(ServerHelper::getCurrentIPAddress(), (array)$this->disabledForIPs);
    }

    private function isCidrValid()
    {
        if (!$this->disabledForCidrIPs) {
            return true;
        }
        foreach ($this->disabledForCidrIPs as $cidr) {
            list($subnet, $mask) = explode('/', $cidr);
            $networkAddress = ip2long($subnet) & ~((1 << (32 - $mask)) - 1);
            $broadcast = $networkAddress | (pow(2, (32 - $mask)) - 1);
            $longIp = ip2long(ServerHelper::getCurrentIPAddress());
            if ($networkAddress <= $longIp && $longIp <= $broadcast) {
                return false;
            }
        }
        return true;
    }

    public function getMobileToTemplate()
    {
        return $this->mobileToTemplate;
    }

    public function setMobileToTemplate($mobileToTemplate)
    {
        $this->mobileToTemplate = $mobileToTemplate;
        return $this;
    }

    public function changeTemplateByMobile()
    {
        if (!$this->mobileToTemplate) {
            return;
        }
        $this->mobileDetect = new \Mobile_Detect();
        if ($this->mobileToTemplate['mobile'] && $this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet()) {
            $this->clientTemplate = $this->mobileToTemplate['mobile'];
        } else if ($this->mobileToTemplate['tablet'] && $this->mobileDetect->isTablet()) {
            $this->clientTemplate = $this->mobileToTemplate['tablet'];
        }
        if (!$this->clientTemplate) {
            return;
        }
        global $whmcs;
        $ref = new \ReflectionClass($whmcs);
        //Template
        $_SESSION['Template'] = $this->clientTemplate;
        $clientTemplate = $ref->getProperty("clientTemplate");
        $clientTemplate->setAccessible(true);

        if ($this->versionAtLeast('8.0.0'))
            $clientTemplate->setValue($whmcs, \WHMCS\View\Template\Theme::factory($this->clientTemplate, $this->clientTemplate));
        else
            $clientTemplate->setValue($whmcs, \WHMCS\View\Template::factory($this->clientTemplate, $this->clientTemplate));
    }

    public function getDomainToTemplate()
    {
        return $this->domainToTemplate;
    }

    public function setDomainToTemplate($domainToTemplate)
    {
        $this->domainToTemplate = $domainToTemplate;
        return $this;
    }

    public function changeTemplateByDomain()
    {
        if (!$this->domainToTemplate) {
            return;
        }
        $this->domain = $_SERVER['HTTP_HOST'];
        if (!isset($this->domainToTemplate[$this->domain]) && !$this->domainToTemplate[$this->domain]) {
            return;
        }
        $this->clientTemplate = $this->domainToTemplate[$this->domain];
        global $whmcs;
        $ref = new \ReflectionClass($whmcs);
        //Template
        $_SESSION['Template'] = $this->clientTemplate;
        $clientTemplate = $ref->getProperty("clientTemplate");
        $clientTemplate->setAccessible(true);

        if ($this->versionAtLeast('8.0.0'))
            $clientTemplate->setValue($whmcs, \WHMCS\View\Template\Theme::factory($this->clientTemplate, $this->clientTemplate));
        else
            $clientTemplate->setValue($whmcs, \WHMCS\View\Template::factory($this->clientTemplate, $this->clientTemplate));
    }

    public function isEnable()
    {
        return $this->enable === true;
    }

    private function updateWhmcsLangFile($language, $admin = false)
    {
        if ($_SESSION['userChangedTemplate'] === true) {
            return;
        }

        //ROOTDIR
        $langfilepath = ROOTDIR . "/lang/" . $language . ".php";

        if (file_exists($langfilepath)) {

            /* clean up the current langs */
            if ($admin) {
                global $_ADMINLANG;
                $_ADMINLANG = array();
            } else {
                global $_LANG;
                $_LANG = array();
            }

            $_SESSION['Language'] = $this->language;
            /* WHMCS Lang object */
            \Lang::self()->addResource("whmcs", $langfilepath, $language, "messages");
            \Lang::self()->getCatalogue($language);
            \Lang::self()->setLocale($language);
            /* */
            include $langfilepath;
        }
        return $_LANG;
    }

    private function defineScriptPath()
    {
        if ($_SERVER['PATH_INFO']) {
            $this->scriptPath = "index.php" . substr($_SERVER['PATH_INFO'], 0, strrpos($_SERVER['PATH_INFO'], DIRECTORY_SEPARATOR));
        } else {
            $this->scriptPath = substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'], DIRECTORY_SEPARATOR) + 1);
        }
    }

    private function versionAtLeast($version)
    {
        return version_compare($this->whmcsVersion, $version, '>=');
    }
}
