<?php

return array(
    'id' =>             'auth:etuutt',
    'version' =>        '0.1',
    'name' =>           'Oauth2 Authentication and Lookup with EtuUTT website',
    'author' =>         'Christian d\'Autume',
    'description' =>    'Provides a configurable authentication backend
        for authenticating staff and clients using Etuutt OAuth.',
    'url' =>            'https://christian.dautume.fr',
    'plugin' =>         'authentication.php:EtuUTTauthPluginAuthPlugin',
    'requires' => array(
        "ohmy/auth" => array(
            "version" => "*",
            "map" => array(
                "ohmy/auth/src" => 'lib',
            )
        ),
    ),
);

?>
