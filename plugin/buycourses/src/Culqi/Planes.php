<?php
namespace Culqi;

class Planes extends Resource {

    const URL_PLANES = "/planes/";

    public function create($options = NULL)
    {
        return $this->request("POST", Planes::URL_PLANES, $api_key = $this->culqi->api_key, $options);
    }


    public function getList($options)
    {

        return $this->request("GET", Planes::URL_PLANES, $api_key = $this->culqi->api_key, $options);
    }


    public function get($id)
    {

        return $this->request("GET", Planes::URL_PLANES . $id . "/", $api_key = $this->culqi->api_key);
    }

    public function delete($id)
    {

       return $this->request("DELETE", Planes::URL_PLANES . $id . "/", $api_key = $this->culqi->api_key);

   }




}
