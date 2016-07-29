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

/**
 * Class User
 * @package Mcwebb\CloudConvert
 */
class User
{
    /**
     * @var string API key for CloudConvert
     */
    private $apiKey;

    /**
     * User constructor.
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}
