<?php

use ohmy\Auth2;

class EtuuttAuth {
    var $config;
    var $access_token;

    function __construct($config) {
        $this->config = $config;
    }

    function triggerAuth() {
        $self = $this;
        return Auth2::legs(3)
            ->set('id', $this->config->get('client-id'))
            ->set('secret', $this->config->get('client-secret'))
            ->set('redirect', 'https://' . $_SERVER['HTTP_HOST']
                . ROOT_PATH . 'api/auth/ext')
            ->set('scope', 'public_user_account')

            ->authorize('https://etu.utt.fr/api/oauth/authorize')
            ->access('https://etu.utt.fr/api/oauth/token')

            ->finally(function($data) use ($self) {
                $self->access_token = $data['access_token'];
            });
    }
}

class EtuuttStaffAuthBackend extends ExternalStaffAuthenticationBackend {
    static $id = "etuutt";
    static $name = "Etu UTT";

    static $sign_in_image_url = "https://developers.google.com/identity/toolkit/images/sign_in_button.png";
    static $service_name = "EtuUTT";

    var $config;

    function __construct($config) {
        $this->config = $config;
        $this->etuutt = new EtuuttAuth($config);
    }

    function signOn()
    {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['email'])) {
            if (($staff = StaffSession::lookup(array('email' => $_SESSION[':oauth']['email'])))
                && $staff->getId()
            ) {
                if (!$staff instanceof StaffSession) {
                    // osTicket <= v1.9.7 or so
                    $staff = new StaffSession($staff->getId());
                }
                return $staff;
            } elseif (isset($_SESSION[':oauth']['profile'])) {
                $errors = array();
                $staff = array();
                $staff['username'] = $_SESSION[':oauth']['profile']['login'];
                $staff['firstname'] = $_SESSION[':oauth']['profile']['firstName'];
                $staff['lastname'] = $_SESSION[':oauth']['profile']['lastName'];
                $staff['email'] = $_SESSION[':oauth']['profile']['email'];
                $staff['isadmin'] = 0;
                $staff['isactive'] = 0;
                $staff['group_id'] = 1;
                $staff['dept_id'] = 1;
                $staff['welcome_email'] = "on";
                $staff['timezone_id'] = 8;
                $staff['isvisible'] = 1;
                Staff::create($staff, $errors);
                if (($user = StaffSession::lookup(array('email' => $_SESSION[':oauth']['email']))) && $user->getId()) {
                    if (!$user instanceof StaffSession) {
                        // osTicket <= v1.9.7 or so
                        $user = new StaffSession($user->getId());
                    }
                    return $user;
                }
            }
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }


    function triggerAuth() {
        parent::triggerAuth();
        $etuutt = $this->etuutt->triggerAuth();
        $etuutt->GET(
            "https://etu.utt.fr/api/public/user/account?access_token="
            . $this->etuutt->access_token)
            ->then(function($response) {
                require_once INCLUDE_DIR . 'class.json.php';
                if ($json = JsonDataParser::decode($response->text))
                    $_SESSION[':oauth']['profile'] = $json['data'];
                    $_SESSION[':oauth']['email'] = $json['data']['email'];
                Http::redirect(ROOT_PATH . 'scp');
            }
            );
    }
}

class EtuUttClientAuthBackend extends ExternalUserAuthenticationBackend {
    static $id = "etuutt.client";
    static $name = "EtuUTT";

    static $sign_in_image_url = "https://developers.google.com/identity/toolkit/images/sign_in_button.png";
    static $service_name = "EtuUTT";

    function __construct($config) {
        $this->config = $config;
        $this->etuutt = new EtuuttAuth($config);
    }

    function supportsInteractiveAuthentication() {
        return false;
    }

    function signOn() {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['email'])) {
            if (($acct = ClientAccount::lookupByUsername($_SESSION[':oauth']['email']))
                && $acct->getId()
                && ($client = new ClientSession(new EndUser($acct->getUser()))))
                return $client;

            elseif (isset($_SESSION[':oauth']['profile'])) {
                // TODO: Prepare ClientCreateRequest
                $profile = $_SESSION[':oauth']['profile'];
                $info = array(
                    'email' => $_SESSION[':oauth']['email'],
                    'name' => $profile['fullName'],
                );
                return new ClientCreateRequest($this, $info['email'], $info);
            }
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }

    function triggerAuth() {
        require_once INCLUDE_DIR . 'class.json.php';
        parent::triggerAuth();
        $etuutt = $this->etuutt->triggerAuth();
        $token = $this->etuutt->access_token;
        $etuutt->GET("https://etu.utt.fr/api/public/user/account?access_token=". $token)
                    ->then(function($response) {
                        if (!($json = JsonDataParser::decode($response->text)))
                            return;
                        $_SESSION[':oauth']['profile'] = $json['data'];
                        $_SESSION[':oauth']['email'] = $json['data']['email'];
                        Http::redirect(ROOT_PATH . 'login.php');
                    });
    }
}