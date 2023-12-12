<?php


namespace PixellWeb\Myrentcar\app;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Api
 * @package App\Citadelle
 */
class Api
{
    /**
     * @var string
     */
    protected ?string $url = null;


    protected CookieJar $cookies_jar;


    /**
     * Api constructor.
     */
    public function __construct()
    {
        $this->base_uri = 'https://'.config('myrentcar.domain').config('myrentcar.path');

        if (cache()->get('pixellweb-myrentcar')) {
             $this->cookies_jar = cache()->get('pixellweb-myrentcar');
        } else {
            $this->login();
        }
    }


    /**
     * @return mixed
     * @throws GuzzleException
     * @throws MyrentcarException
     * @throws InvalidArgumentException
     */
    public function login()
    {
        $this->cookies_jar = new CookieJar();

        $client = new Client([
                'base_uri' => $this->base_uri
            ]
        );

        $options = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                "DbIdentifiant" => config('myrentcar.dbIdentifiant'),
                "Societe" => config('myrentcar.societe'),
                "Username" => config('myrentcar.username'),
                "Password" => config('myrentcar.password'),
            ],
            'cookies' => $this->cookies_jar,
        ];

        try {
            $response = $client->post('Login/Login', $options );

            if ($response->getStatusCode() != 200 or empty($response->getBody()->getContents())) {
                throw new MyrentcarException("Impossible de se connecter (" . $response->getStatusCode() . ")");
            }

            cache()->set('pixellweb-myrentcar',  $this->cookies_jar);

        } catch (RequestException $exception) {
            throw new MyrentcarException("Api::login : " . $exception->getMessage());
        }

        return json_decode($response->getBody());
    }

    public function logout()
    {
        $this->cookies_jar = new CookieJar();

        $client = new Client([
                'base_uri' => $this->base_uri
            ]
        );

        $options = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'cookies' => $this->cookies_jar,
        ];

        try {
            $response = $client->post('Login/Logout', $options );

            // cache()->set('pixellweb-myrentcar',  $this->cookies_jar);

        } catch (RequestException $exception) {
            throw new MyrentcarException("Api::logout : " . $exception->getMessage());
        }

        return json_decode($response->getBody());
    }


    /**
     * @param string $method
     * @param string $ressource_path
     * @param array $parameters
     * @return array|null
     * @throws GuzzleException
     * @throws MyrentcarException
     */
    public function request(string $method, string $ressource_path, array $parameters = []): array|null
    {
        $client = new Client(['base_uri' => $this->base_uri]);
        $headers = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'cookies' => $this->cookies_jar
        ];

        if ($method == 'GET') {
            $headers['query'] = $parameters;
        } else {
            $headers['json'] = $parameters;
        }

        try {

            $response = $client->request($method, $ressource_path, $headers);

            if ($response->getStatusCode() != 200) {
                throw new MyrentcarException("Api::".$method." : code http error (" . $response->getStatusCode() . ")  " . $ressource_path);
            }

            return json_decode($response->getBody(), true);

        } catch (RequestException $exception) {

            // Problème de connexion
            if ($exception->getCode() == 401) {
                $this->login();
                return $this->post($ressource_path, $parameters);
            }

            throw new MyrentcarException("Api::".$method." : " . $exception->getResponse()->getBody()->getContents() . ' '.print_r($parameters,true));
        }
    }


    /**
     * @param string $ressource_path
     * @param array $params
     * @return array|null
     * @throws GuzzleException
     * @throws MyrentcarException
     */
    public function get(string $ressource_path, array $params = []): array|null
    {
        return $this->request('GET', $ressource_path, $params);
    }

    /**
     * @param string $ressource_path
     * @param array $params
     * @return array|null
     * @throws GuzzleException
     * @throws MyrentcarException
     */
    public function post(string $ressource_path, array $params = []): array|null
    {
        return $this->request('POST', $ressource_path, $params);
    }


    /**
     * @param string $ressource_path
     * @param array $params
     * @return array|null
     * @throws GuzzleException
     * @throws MyrentcarException
     */
    public function put(string $ressource_path, array $params = []): array|null
    {
        return $this->request('PUT', $ressource_path, $params);
    }
}
