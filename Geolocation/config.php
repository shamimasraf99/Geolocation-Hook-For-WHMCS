<?php
/**
 * Read the instructions in comments to configure the hook file to work according to your needs.
 * Remember to use a comma at the end of each line entry when more than one is entered.
 */


/**
 * Define relations between countries and currencies. 
 * Enter the currency code for each country (codes used), use the below pattern, edit it or add new entries below: 
 */
$countryToCurrency = array(
    'default' => 'USD',
    'US'      => 'USD',
    'GB'      => 'GBP',
    'DE'      => 'EUR',
        // NOTE: You can add more below
);

/**
 * Define language rules by assigning a language to a single country. 
 * Use the below pattern (language name for country code) edit it and/or add new entries below:
 */
$countryToLanguage = array(
    'default' => 'english',
    'US'      => 'english',
    'DE'      => 'german',
    'NO'      => 'norwegian',
        // NOTE: You can add more below
);

/**
 * Configure additional settings: 
 * Firstly assign a WHMCS template to each country used.
 * Use the below pattern (template name for country code) edit it and/or add new entries below: 
 */
$countryToTemplate = array(
    'default' => 'six',
    'US'      => 'six',
    'DE'      => 'six',
        // NOTE: You can add more below
);

/**
 * Now, define the language for each WHMCS template used.
 * Please note that a template available in WHMCS V7 is: 'six'.
 * It is important to use a template that exists within your WHMCS system.
 * Not Logged In Users 
 */
$templateToLanguage = array(
    'default' => 'six',
    'english' => 'six',
    'german'  => 'six',
        // NOTE: You can add more below
);

/**
 * You may define a mobile template per a mobile device: mobile and tablet types.
 * Use the below pattern (template name for mobile device) and edit the entries.
 * Comment out to disable the option.
 */
$mobileToTemplate = [
//    'mobile' => 'mobile_template',
//    'tablet' => 'tablet_template',
];

/**
 * You may define templates per domain name. Enter a domain name and assign a template to each one of them. 
 * Uncomment the below examples to turn on.
 */
$domainToTemplate = [
    //'www.example.mobi' => 'mobile_template',
    //'www.example.com' => 'six',
];

/**
 * Enter pages in your WHMCS which the hook will be active for. 
 * Add more pages at the bottom of the list, comment out single pages to disable the hook for them:
 */
$allowedScripts = array(
    'p1.php',
    'index.php',
    'clientarea.php',
    'cart.php',
    'knowledgebase.php',
    'announcements.php',
    'serverstatus.php',
    'affiliates.php',
    'contact.php',
    'index.php/store',
        // NOTE: You can add more below
);

/**
 * Point single IP addresses. The hook will be turned off for these addresses.
 * Uncomment the below list and edit the exemplary addresses, add more at the bottom of the list:
 */
$disabledForIPs = array(
//    '91.192.166.22',
//    '192.168.0.39',
        // NOTE: You can uncomment or add more below
);

/**
 * Point full IP pools. The hook will be turned off for the addresses in these pools.
 * Uncomment the below list and edit the exemplary addresses, add more at the bottom of the list:
 */
$disabledForCidrIPs = array(
//    '192.168.56.0/24',
//    '192.168.0.39/24',
//        NOTE: You can uncomment or add more below
);

/**
 * Point user agents. The hook will be turned off for the enumerated here devices/browsers.
 * Enter a short or a full user agent name like in the examples below.
 * Uncomment the below list and edit the exemplary entries, add more at the bottom of the list:
 */
$disabledForBrowsers = array(
//    'Chrome',
//    'Firefox',
//    'Google-Site-Verification',
//    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html )',
//    'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 7.1; Trident/5.0)',
//     NOTE: You can uncomment or add more below
);

/**
 * Get the country using an external service, e.g. MaxMind GeoLite
 * http://dev.maxmind.com/geoip/geolite
 * NOTE: You can also create your own submodule, edit the below line in such case as only one submodule can work at a time.
 */
$submodule                       = 'GeoIP2';



/**
 * Currency Session Setup
 * The below three code lines are used to check if the current template is set up correctly.
 */
$allowCurrencySession            = true;

/**
 * Setting up a template to redirect depending on a country
 * Not Logged In Users
 */
$allowRedirectCountryToTemplate  = false;

/**
 * Setting up a template to redirect depending on a language
 * Not Logged In Users
 */
$allowRedirectLanguageToTemplate = true;


/**
 * Creating URL Redirection 
 * The below system is responsible for switching the client area template depending on the chosen language.
 * Not Logged In Users
 */
$allowURLRedirection             = true;

/**
 * Change template if
 * - current template is NOT correct for this language
 * - current template is NOT default (no language chosen)
 *  Not Logged In Users
 */
$changeTemplateByLanguage        = true;

/**
 * Prevent the user from switching the currency
 * Not Logged In Users
 */
$preventSwitchCurrency           = true;

/**
 * Preserve Template
 * This option, when set to false, allows to switch the template along with changing the language
 * Not Logged In Users
 */
$preserveTemplate                = true;

/**
 * Allow to automatic change language per country
 */
$allowChangeLanguage             = true;


