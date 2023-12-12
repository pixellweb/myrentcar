<?php

namespace PixellWeb\Myrentcar\app\Ressources;


use PixellWeb\Myrentcar\app\Api;

class Ressource
{
    /**
     * @var Api
     */
    public $api;


    /**
     * Ressource constructor.
     */
    public function __construct()
    {
        $this->api = new Api();
    }


}
