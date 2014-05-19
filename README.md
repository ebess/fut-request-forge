Connector class for mobile endpoint of Fifa 14 Ultimate Team.
Also you can use composer to install the connectors

 composer.json
```json
    require {
        "fut/request-forge": "dev-master"
    }
```

Example: (also see example.php)
```php
        require_once __DIR__ . "/vendor/autoload.php";
        require_once __DIR__ . "/autoload.php";

        use Guzzle\Http\Client;
        use Guzzle\Plugin\Cookie\CookiePlugin;
        use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

        $client = new Client(null);
        $cookieJar = new ArrayCookieJar();
        $cookiePlugin = new CookiePlugin($cookieJar);
        $client->addSubscriber($cookiePlugin);

        Fut\Request\Forge::setPlatform('ps');
        Fut\Request\Forge::setEndpoint('WebApp');

        // example for playstation accounts to get the credits
        // 3. parameter of the forge factory is the actual real http method
        // 4. parameter is the overridden method for the webapp headers
        $forge = Fut\Request\Forge::getForge($client, '/ut/game/fifa14/user/credits', 'post', 'get');
        $json = $forge
            ->setNucId($nuc)
            ->setSid($sid)
            ->setPhishing($phishing)
            ->getJson();

        echo "you have " . $json['credits'] . " coins" . PHP_EOL;
```
