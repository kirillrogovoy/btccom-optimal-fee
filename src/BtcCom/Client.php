<?php
namespace BtcCom;

final class Client {
    private $client;
    private $url;

    public function __construct(\GuzzleHttp\Client $client, $url) {
        $this->client = $client;
        $this->url = $url;
    }

    public function fetch() {
        $response = $this->client->request('GET', $this->url);

        if (($code = $response->getStatusCode()) !== 200) {
            throw new Exception("Status code of the target URL '$url' is $code, but expected 200");
        }

        return $response;
    }
}
