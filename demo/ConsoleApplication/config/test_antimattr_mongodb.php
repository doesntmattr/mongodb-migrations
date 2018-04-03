<?php

/**
 * @see http://php.net/manual/en/mongoclient.construct.php
 */
return [
    'host' => 'localhost', // default is localhost
    'port' => '27017', // default is 27017
    'dbname' => null, // optional, if authentication DB is required
    'user' => null, // optional, if authentication is required
    'password' => null, // optional, if authentication is required
    'options' => [
        'connect' => true, // recommended
    ],
];
