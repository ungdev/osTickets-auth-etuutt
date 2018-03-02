<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class EtuUTTauthPluginAuthPlugin extends Plugin {
    var $config_class = "EtuUTTauthPluginConfig";

    function bootstrap() {
        $config = $this->getConfig();

        $etuutt = $config->get('enabled');
        if (in_array($etuutt, array('all', 'staff'))) {
            require_once('etuutt.php');
            StaffAuthenticationBackend::register(
                new EtuuttStaffAuthBackend($this->getConfig()));
        }
        if (in_array($etuutt, array('all', 'client'))) {
            require_once('etuutt.php');
            UserAuthenticationBackend::register(
                new EtuUttClientAuthBackend($this->getConfig()));
        }
    }
}

require_once(INCLUDE_DIR.'UniversalClassLoader.php');
use Symfony\Component\ClassLoader\UniversalClassLoader_osTicket;
$loader = new UniversalClassLoader_osTicket();
$loader->registerNamespaceFallbacks(array(
    dirname(__file__).'/lib'));
$loader->register();
