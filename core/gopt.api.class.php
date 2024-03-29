<?php

class goptApi
{
    private $domain_api;
    private $city;
    private $csrf_cookie = "";
    private $csrf_token = "";
    private $asp_sessid = "";
    public $salt = 0;
    public $saltAct = "";

    function __construct(string $domain_api, string $city)
    {
        $this->domain_api = $domain_api;
        $this->city = $city;
    }

    function init()
    {
        $request = curl_init("$this->domain_api/Home/Index/gomel");
        curl_setopt_array($request, [
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0",
        ]);
        $response = curl_exec($request);
        $matches = [];
        preg_match_all('/set-cookie: (__RequestVerificationToken_[a-z0-9]+=.+); path/mi', $response, $matches);
        $this->csrf_cookie = $matches[1][0];
        $matches = [];
        preg_match_all('/set-cookie: (ASP\.NET_SessionId=[a-z0-9]+); path/mi', $response, $matches);
        $this->asp_sessid = $matches[1][0];
        $matches = [];
        preg_match_all('/name="__RequestVerificationToken" type="hidden" value="([a-z0-9_-]+)"/mi', $response, $matches);
        $this->csrf_token = $matches[1][0];
        $matches = [];
        preg_match_all('/return (\d+) (.)/mi', $response, $matches);
        $this->salt = (int)$matches[1][0];
        $this->saltAct = $matches[2][0];
    }

    function method(string $method_name, array $data)
    {
        $data['p'] = $this->city;
        $data['__RequestVerificationToken'] = $this->csrf_token;
        $data = http_build_query($data);
        $url = "$this->domain_api/$method_name";
        $request = curl_init($url);
        curl_setopt_array($request, [
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => [
                "Cookie: $this->csrf_cookie; $this->asp_sessid",
                "Referer: $this->domain_api/Home/Index/$this->city",
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json, text/plain, */*",
                "Te: trailers"
            ],
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0"
        ]);
        $response = curl_exec($request);
        return json_decode($response);
    }
}
