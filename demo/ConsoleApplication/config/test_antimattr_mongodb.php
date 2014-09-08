<?php

/**
 * @link http://php.net/manual/en/mongoclient.construct.php
 */
return array(
    'host' => 'localhost', // default is localhost
    'port' => '27017', // default is 27017
    'dbname' => null, // optional, if authentication DB is required
    'user' => null, // optional, if authentication is required
    'password' => null, // optional, if authentication is required
    'options' => array(
        'connect' => true // recommended
    )
);