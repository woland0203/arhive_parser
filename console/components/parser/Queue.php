<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 26.08.18
 * Time: 21:35
 */

namespace console\components\parser;


class Queue
{
    protected $resource;

    public function __construct($resource = '')
    {
        $this->resource = $resource;
    }

    public function addLinks($links = []){

    }

    /**
     * @return QueueElement
     */
    public function get(){
        return new QueueElement();
    }


}