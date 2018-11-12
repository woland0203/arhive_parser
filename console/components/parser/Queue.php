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
    protected $data;

    public function __construct($resource = '')
    {
        $this->resource = $resource;
        if(!file_exists($resource)){
            file_put_contents($resource, '');
        }

        if (($handle = fopen($resource, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 3000, ";")) !== FALSE) {
                $this->data[$data[0]] = (int)$data[1];
            }
            fclose($handle);
        }

    }

    public function addLinks($links = []){
        $fp = fopen($this->resource, 'a');

        foreach ($links as $link) {
            if(isset($this->data[$link])) continue;

            $this->data[$link] = 0;
            fputcsv($fp, [$link, ''], ';');

        }
        fclose($fp);
    }

    /**
     * @return QueueElement
     */
    public function get(){
        $link = null;
      //  print_r($this->data);
        foreach ($this->data as $key => &$value){
            if(!$value){
                $this->data[$key] = 1;
                $link = $key;
                break;
            }
        }
        unset($value);

        if($link){
            $resourceTmp = $this->resource . '.tmp';
            $fp = fopen($resourceTmp, 'w');
            foreach ($this->data as $key => $value) {
                fputcsv($fp, [$key, $value], ';');

            }
            fclose($fp);
            rename($resourceTmp, $this->resource);

            return $link;
        }
        return null;

    }


}