<?php

class FirstData {

    protected $uri = VTWC_API_URL;

    protected $_uri = "/transaction/v12";

    /*Modify this acording to your firstdata api stuff*/
    protected $hmackey = VTWC_HMAC_KEY;
    protected $keyid = VTWC_KEY_ID;
    protected $gatewayid = VTWC_GATEWAY_ID;
    protected $password = VTWC_TERMINAL_PASSWORD;

    public $error = "";

    public $ModeDebug = VTWC_DEBUG;

    public $response = '';


    public function request( $request ) {

        $location = $this->uri;
        
        $request ['gateway_id']= $this->gatewayid;
        $request ['password']= $this->password;
        $content = json_encode( $request );
        $gge4Date = strftime("%Y-%m-%dT%H:%M:%S", time() - (int) substr(date('O'), 0, 3)*60*60) . 'Z';
        $contentType = "application/json";
        $contentDigest = sha1( $content );
        $method = "POST";
        $hashstr = "$method\n$contentType\n$contentDigest\n$gge4Date\n$this->_uri";
        $authstr = base64_encode(hash_hmac("sha1",$hashstr,$this->hmackey,TRUE));
        $authstr = 'GGE4_API ' . $this->keyid . ':' . $authstr;
        $headers = array(
            "Content-Type: $contentType",
            "X-GGe4-Content-SHA1: $contentDigest",
            "X-GGe4-Date: $gge4Date",
            "Authorization: $authstr",
            "Accept: $contentType"
        );

        //CURL stuff
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $location);

        //Warning ->>>>>>>>>>>>>>>>>>>>
        /*Hardcoded for easier implementation, DO NOT USE THIS ON PRODUCTION!!*/
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //Warning ->>>>>>>>>>>>>>>>>>>>

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);

        //This guy does the job
        $output = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = $this->parseHeader(substr($output, 0, $header_size));
        $body = substr($output, $header_size);

        curl_close($ch);

        /* If we get any of this X-GGe4-Content-SHA1 X-GGe4-Date Authorization
         * then the API call is valid */

        if (isset($header['authorization']))
        {
            //Ovbiously before we do anything we should validate the hash



            $_body = json_decode($body);

            if($_body===NULL){

                    $this->error = $body;
                    return false;
            }else{

                if($_body->transaction_approved=='1'){
                    if($this->ModeDebug){
                        $this->error .= $this->_dump($_body);
                    }
                    $this->response = $_body;
                    return true;
                }else{
                    if($this->ModeDebug){
                        $this->error .= $this->_dump($_body);
                    }else{
                        $this->error .= "Exact message: {$_body->exact_message}<br>
                                        Response Code: {$_body->exact_resp_code}<br>
                                        Bank Message: {$_body->bank_message}<br>";
                        $this->error .=$this->_dump($_body->ctr);
                    }
                    return false;
                }

            }
    }
        //Otherwise just debug the error response, which is just plain text
        else
        {
            $this->error = "Error API credentials";
            if($this->ModeDebug){
                $this->error .= "<br>$body";
            }
        }
    }

    private function parseHeader($rawHeader)
    {
        $header = array();

        $lines = preg_split('/\r\n|\r|\n/', $rawHeader);

        foreach ($lines as $key => $line)
        {
            $keyval = explode(': ', $line, 2);

            if (isset($keyval[0]) && isset($keyval[1]))
            {
                $header[strtolower($keyval[0])] = $keyval[1];
            }
        }

        return $header;
    }

    public function dump($var){
        print ("<pre>".print_r($var,true)."</pre>");
    }

    public function _dump($var){
        return ("<pre>".print_r($var,true)."</pre>");
    }
}