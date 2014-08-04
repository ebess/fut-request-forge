<?php

namespace Fut\Request;

use \GuzzleHttp\Client;
use \GuzzleHttp\Message\MessageInterface;
use \GuzzleHttp\Message\RequestInterface;

/**
 * Class Request_Forge
 */
class Forge
{
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
     * @var string
     */
    private static $endpoint = 'WebApp';

    /**
     * @var string
     */
    private static $platform = 'ps';

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
		$this->method = $method;
        $this->methodOverride = $methodOverride;

        // set url, is no server added -> prepend
        if ( ! preg_match("/^http/mi", $url)) {
            if (static::$platform === static::PLATFORM_XBOX) {
                $url = 'https://utas.fut.ea.com' . $url;
            } else {
                $url = 'https://utas.s2.fut.ea.com' . $url;
            }
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
     * @param string $route
     * @return $this
     */
    public function setRoute($route)
	{
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
        // set endpoint specific headers
        if ($this->applyEndpointHeaders === true) {

            if (static::$endpoint == static::ENDPOINT_WEBAPP) {
                $this->addEndpointHeadersWebApp($request);
            } elseif (static::$endpoint == static::ENDPOINT_MOBILE) {
                $this->addEndpointHeadersMobile($request);
            }

        }

		// add headers
		foreach ($this->headers as $name => $val) {
			$request->removeHeader($name);
			$request->addHeader($name, $val);
		}

		// fut specific headers
		if ($this->sid !== null) {
			$request->addHeader('X-UT-SID', $this->sid);
		}

		if ($this->phishing !== null) {
			$request->addHeader('X-UT-PHISHING-TOKEN', $this->phishing);
		}

		if ($this->nucId !== null) {
			$request->addHeader('Easw-Session-Data-Nucleus-Id', $this->nucId);
		}

		// remove headers
		foreach ($this->removedHeaders as $name) {
			$request->removeHeader($name);
		}

		return $this;
	}

    /**
     * adds header for webapp
     *
     * @param RequestInterface $request
     */
    private function addEndpointHeadersWebApp($request)
    {
        $request->setHeader('X-UT-Embed-Error', 'true');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('Accept', 'text/html,application/xhtml+xml,application/json,application/xml;q=0.9,image/webp,*/*;q=0.8');
        $request->setHeader('Referer', 'http://www.easports.com/iframe/fut/?baseShowoffUrl=http%3A%2F%2Fwww.easports.com%2Fuk%2Ffifa%2Ffootball-club%2Fultimate-team%2Fshow-off&guest_app_uri=http%3A%2F%2Fwww.easports.com%2Fuk%2Ffifa%2Ffootball-club%2Fultimate-team&locale=en_GB');
        $request->setHeader('Accept-Language', 'en-US,en;q=0.8');
        $request->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.62 Safari/537.36');

        if ($this->route !== null) {
            $request->setHeader('X-UT-Route', $this->route);
        }

        if ($this->methodOverride !== null) {
            $request->setHeader('X-HTTP-Method-Override', strtoupper($this->methodOverride));
        }
    }

    /**
     * adds headers for mobile
     *
     * @param RequestInterface $request
     */
    private function addEndpointHeadersMobile($request)
    {
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
        $request->setHeader('Accept', 'application/json, text/plain, */*; q=0.01');
        $request->setHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

        if ($this->pid !== null) {
            $request->setHeader('X-POW-SID', $this->pid);
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
			$request->setBody(json_encode($this->body));

        // set as forms or query data
		} elseif ($this->body !== null) {

            // if get put parameters in query
            if ($this->method == 'get') {
                $query = $request->getQuery();
                foreach ($this->body as $name => $value) {
                    $query->set($name, $value);
                }

            // otherwise as form data
            } else {
            	foreach ($this->body as $name => $value) {
                    $request->getBody()->setField($name, $value);
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

		return $this->client->createRequest($method);
	}
}