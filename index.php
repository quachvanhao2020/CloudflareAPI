<?php
define("CLOUDFLARE_API_HOST","https://api.cloudflare.com/client/v4");
class CloudflareAPI{
    public $key;
    public function __construct(string $key)
    {
        $this->key = $key;
    }
    /**
     * @return CloudflareZone[]
     */
    public function zones(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->key
            ),
            CURLOPT_URL => CLOUDFLARE_API_HOST."/zones",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $rs = json_decode($response,true);
        if(empty($rs["result"])){
            throw new \Exception($response);
        }
        $data = [];
        foreach ($rs["result"] as $key => $value) {
            $e = new CloudflareZone($this->key);
            $e->id = $value['id'];
            $e->name = $value['name'];
            $data[] = $e;
        }
        return $data;
    }
    function clear_dns_records_by_name(string $name){
        $zones = $this->zones();
        $records = [];
        foreach ($zones as $key => $value) {
            if($value->name == $name){
                $records = $value->dns_records();
            }
        }
        if(!$records) throw new Exception("records", 1);
        foreach ($records as $key => $value) {
            var_dump($value->name);
            $value->delete();
        }
    }
}
class CloudflareZone{
    public string $id;
    public string $name;
    public $key;
    public function __construct(string $key)
    {
        $this->key = $key;
    }
    /**
     * @return CloudflareRecord[]
     */
    public function dns_records(){
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->key
            ],
            CURLOPT_URL => CLOUDFLARE_API_HOST."/zones/{$this->id}/dns_records",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rs = json_decode($response,true);
        if(empty($rs["result"])){
            throw new \Exception($response);
        }
        $data = [];
        foreach ($rs["result"] as $key => $value) {
            $e = new CloudflareRecord($this->key,$this->id);
            $e->id = $value['id'];
            $e->name = $value['name'];
            $data[] = $e;
        }
        return $data;
    }
}
class CloudflareRecord{
    public string $id;
    public string $name;
    public string $zone;
    public $key;
    public function __construct(string $key,string $zone)
    {
        $this->key = $key;
        $this->zone = $zone;
    }
    public function delete(){
        $url = CLOUDFLARE_API_HOST."/zones/{$this->zone}/dns_records/{$this->id}";
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->key
            ],
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rs = json_decode($response,true);
        if(empty($rs["result"])){
            throw new \Exception($url.$response);
        }
        return $rs["result"];
    }
}