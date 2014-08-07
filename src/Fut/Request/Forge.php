<?php

namespace Fut\Request;

use \GuzzleHttp\Client;
use \GuzzleHttp\Message\MessageInterface;
use \GuzzleHttp\Message\RequestInterface;
use \GuzzleHttp\Stream;

/**
 * Class Request_Forge
 */
class Forge
{
    /**
     * endpoint constants
     */
    const ENDPOINT_WEBAPP = 'WebApp';
    const ENDPOINT_MOBILE = 'Mobile';

    /**
     * platform constants
     */
    const PLATFORM_PLAYSTATION = 'ps';
    const PLATFORM_XBOX = 'xbox';

    /**
     * defaults endpoint
     *
     * @var string
     */
    private static $endpoint = 'WebApp';

    /**
     * defaults platform
     *
     * @var string
     */
    private static $platform = 'ps';

    /**
     * base url for requests
     *
     * @var string[]
     */
    private static $baseUrl = array(
        'ps'    => 'https://utas.s2.fut.ea.com',
        'xbox'  => 'https://utas.fut.ea.com'
    );

    /**
     * platform related headers, if second parameter of the header starts with an '@' the instance attribute with this
     * name will be set as value if it is not empty
     *
     * @var array[]
     */
    private static $platformHeaders = array(
        'WebApp' => array(
            // defaults
            array('X-UT-Embed-Error', 'true'),
            array('X-Requested-With', 'XMLHttpRequest'),
            array('Content-Type', 'application/json'),
            array('Accept', 'text/html,application/xhtml+xml,application/json,application/xml;q=0.9,image/webp,*/*;q=0.8'),
            array('Referer', 'http://www.easports.com/iframe/fut/?baseShowoffUrl=http%3A%2F%2Fwww.easports.com%2Fuk%2Ffifa%2Ffootball-club%2Fultimate-team%2Fshow-off&guest_app_uri=http%3A%2F%2Fwww.easports.com%2Fuk%2Ffifa%2Ffootball-club%2Fultimate-team&locale=en_GB'),
            array('Accept-Language', 'en-US,en;q=0.8'),

            // optional
            array('X-UT-Route', '@route'),
            array('X-HTTP-Method-Override', '@methodOverride'),
        ),
        'Mobile' => array(
            // defaults
            array('Content-Type', 'application/json'),
            array('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml'),
            array('Accept', 'application/json, text/plain, */*; q=0.01'),

            // optional
            array('X-POW-SID', '@pid'),
        )
    );

    /**
     * fut related headers, if second parameter of the header starts with an '@' the instance attribute with this
     * name will be set as value if it is not empty
     *
     * @var array[]
     */
    private static $futHeaders = array(
        // optional
        array('X-UT-SID', '@sid'),
        array('X-UT-PHISHING-TOKEN', '@phishing'),
        array('Easw-Session-Data-Nucleus-Id', '@nucId'),
    );

    /**
     * headers which has to be attached on every request
     *
     * @var array[]
     */
    private static $obligatedEndpointHeaders = array(
        'WebApp' => array(
            array('User-Agent', 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.62 Safari/537.36'),
        ),
        'Mobile' => array(
            array('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30'),
        )
    );

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $method;

    /**
     * @var null|string
     */
    private $methodOverride = null;

    /**
     * @var null|string
     */
    private $sid = null;

    /**
     * @var null|string
     */
    private $pid = null;

    /**
     * @var null|string
     */
    private $phishing = null;

    /**
     * @var null|string
     */
    private $nucId = null;

    /**
     * @var string[]
     */
    private $headers = array();

    /**
     * @var string[]
     */
    private $removedHeaders = array();

    /**
     * @var mixed
     */
    private $body = null;

    /**
     * @var bool
     */
    private $bodyAsString = false;

    /**
     * @var bool
     */
    private $applyEndpointHeaders = true;

    /**
     * @var null|string
     */
    private $route = null;

    /**
     * creates a request forge for given url and method
     *
     * @param Client $client
     * @param string $url
     * @param string $method
     * @param null|string $methodOverride
     */
    public function __construct($client, $url, $method, $methodOverride = null)
	{
		$this->client = $client;
		$this->method = strtoupper($method);
        if ($methodOverride !== null) {
            $this->methodOverride = strtoupper($methodOverride);
        }

        // set url, is no server added -> prepend
        if ( ! preg_match("/^http/mi", $url)) {
            $url = static::$baseUrl[static::$platform] . $url;
        }

        $this->url = $url;
	}

    /**
     * sets whether forge should handle like a mobile or webapp
     *
     * @throws InvalidArgumentException If an unknown endpoint is given
     * @param string $endpoint
     */
    static public function setEndpoint($endpoint)
    {
    	if (in_array($endpoint, [static::ENDPOINT_WEBAPP, static::ENDPOINT_MOBILE])) {
        	static::$endpoint = $endpoint;
    	} else {
    		throw new InvalidArgumentException('Trying to set unknown endpoint.');
    	}
    }

    /**
     * sets the platform of the accounts ps3|xbox360|pc
     *
     * @throws InvalidArgumentException If an unknown platform is given
     * @param string $platform
     */
    static public function setPlatform($platform)
    {
    	if (in_array($platform, [static::PLATFORM_PLAYSTATION, static::PLATFORM_XBOX])) {
        	static::$platform = $platform;
    	} else {
    		throw new InvalidArgumentException('Trying to set unknown platform.');
    	}
    }

    /**
     * @param Client $client $client
     * @param string $url
     * @param string $method
     * @param null|string $methodOverride
     * @return $this
     */
    static public function getForge($client, $url, $method, $methodOverride = null)
    {
        return new static($client, $url, $method, $methodOverride);
    }

    /**
     * if set, endpoint specific headers won't be applied
     *
     * @return $this
     */
    public function removeEndpointHeaders()
    {
        $this->applyEndpointHeaders = false;

        return $this;
    }

    /**
     * EA: session id
     *
     * @param string $sid
     * @return $this
     */
    public function setSid($sid)
	{
		$this->sid = $sid;

		return $this;
	}

    /**
     * EA: pow id
     *
     * @param string $pid
     * @return $this
     */
    public function setPid($pid)
	{
		$this->pid = $pid;

		return $this;
	}

    /**
     * EA: phishing token
     *
     * @param string $phishing
     * @return $this
     */
    public function setPhishing($phishing)
	{
		$this->phishing = $phishing;

		return $this;
	}

    /**
     * EA: nucleus id
     *
     * @param string $nucId
     * @return $this
     */
    public function setNucId($nucId)
	{
		$this->nucId = $nucId;

		return $this;
	}

    /**
     * set route
     *
     * @return $this
     */
    public function setRoute()
	{
		$route = static::$baseUrl[static::$platform];

        // remove port part
        $route = preg_replace("/(:[0-9]*)$/mi", '', $route);
		$this->route = $route;

		return $this;
	}

    /**
     * data will be applied on the requests, if marked as string, the data will be set as a json string
     *
     * @param mixed $data
     * @param bool $asString
     * @return $this
     */
    public function setBody($data, $asString  = false)
	{
		$this->bodyAsString = $asString;
		$this->body = $data;

		return $this;
	}

    /**
     * adds a header to the requests
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;

		return $this;
	}

    /**
     * blacklists a header which will be removed before sending the request
     *
     * @param string $name
     * @return $this
     */
    public function removeHeader($name)
	{
		if (!in_array($name, $this->removedHeaders)) {
			$this->removedHeaders[] = $name;	
		}

		return $this;
	}

    /**
     * sends request and returns the answer as json
     *
     * @return array
     */
    public function getJson()
	{
		$data = $this->sendRequest();

		return $data['response']->json();
	}

    /**
     * sends request and returns the received body
     *
     * @return string
     */
    public function getBody()
	{
		$data = $this->sendRequest();

		return $data['response']->getBody();
	}

    /**
     * returns the request
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        $request = $this->forgeRequestWithCommonHeaders();

        $this
            ->applyBody($request)
            ->applyHeaders($request);

        return $request;
    }

    /**
     * sends the requests and returns the request itself and the response object
     *
     * @return MessageInterface[]
     */
    public function sendRequest()
	{
        $request = $this->getRequest();
		$response = $this->client->send($request);

		return array(
			'request' => $request,
			'response' => $response
		);
	}

    /**
     * applies set headers to the request object
     * adds headers, remove headers and adds - if set - the ea specific requests
     *
     * @param RequestInterface $request
     * @return $this
     */
    private function applyHeaders($request)
	{
        // set obligated headers
        $this->addConfigHeaders($request, static::$obligatedEndpointHeaders[static::$endpoint]);

        // set endpoint specific headers
        if ($this->applyEndpointHeaders === true) {
            $endpointRelatedHeaders = static::$platformHeaders[static::$endpoint];
            $this->addConfigHeaders($request, $endpointRelatedHeaders);
        }

		// add headers
		foreach ($this->headers as $name => $val) {
			$request->removeHeader($name);
			$request->addHeader($name, $val);
		}

        // fut specific headers
        $futRelatedHeaders = static::$futHeaders;
        $this->addConfigHeaders($request, $futRelatedHeaders);

		// remove headers
		foreach ($this->removedHeaders as $name) {
			$request->removeHeader($name);
		}

		return $this;
	}

    /**
     * apply endpoint related headers
     *
     * @param RequestInterface $request
     * @param array[] $headers
     */
    private function addConfigHeaders($request, $headers)
    {
        foreach ($headers as $header) {
            // optional headers
            if ($header[1][0] === '@') {
                $instanceAttribute = $this->{substr($header[1], 1)};
                // set if it is not empty or null
                if ($instanceAttribute !== null && (empty($instanceAttribute) === false || is_string($instanceAttribute))) {
                    $request->setHeader($header[0], $instanceAttribute);
                }
            // defaults
            } else {
                $request->setHeader($header[0], $header[1]);
            }
        }
    }

    /**
     * adds the body as a json string to the request body
     *
     * @param RequestInterface $request
     * @return $this
     */
    private function applyBody($request)
    {
        // set data as json
        if ($this->bodyAsString) {

            $request->setBody(Stream\create(json_encode($this->body)));

            // set as forms or query data
        } elseif ($this->body !== null || $this->methodOverride === 'GET') {

            // if get put parameters in query
            if ($this->method == 'GET') {
                $query = $request->getQuery();
                foreach ($this->body as $name => $value) {
                    $query->set($name, $value);
                }

                // otherwise as form data
            } else {
                if (is_array($this->body)) {
                    foreach ($this->body as $name => $value) {
                        $request->getBody()->setField($name, $value);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * creates a request with common headers which needed for the connector request
     *
     * @return RequestInterface
     */
    private function forgeRequestWithCommonHeaders()
	{
        $request = null;
        $method = $this->method;

        // if mobile and method is overridden, take that one otherwise normal given method
        if (static::$endpoint === static::ENDPOINT_MOBILE && $this->methodOverride !== null) {
            $method = $this->methodOverride;
        }

		return $this->client->createRequest($method, $this->url);
	}
}