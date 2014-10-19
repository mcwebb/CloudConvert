<?php
/*
 * Part of mcwebb/CloudConvert
 * 2014 Matthew Webb <matthewowebb@gmail.com>
 * MIT License
 *
 * Based on Lunaweb's API Example Class
 * 2013 by Lunaweb Ltd.
 * Feel free to use, modify or publish it.
 */
namespace Mcwebb\CloudConvert;

class User {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getApiKey() {
        return $this->apiKey;
    }
}