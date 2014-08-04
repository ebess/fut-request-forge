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

    use GuzzleHttp\Client;
    use GuzzleHttp\Cookie\CookieJar;
    use GuzzleHttp\Subscriber\Cookie as CookieSubscriber;
    use Fut\Request\Forge;

    $client = new Client();
    $cookieJar = new CookieJar();
    $cookieSubscriber = new CookieSubscriber($cookieJar);
    $client->getEmitter()->attach($cookieSubscriber);

    $export = array(
        'nucleusId' => 'my-nucleusId',
        'sessionId' => 'my-sessionId',
        'phishingToken' => 'my-phishingToken'
    );

    Forge::setPlatform(Forge::PLATFORM_PLAYSTATION);
    Forge::setEndpoint(Forge::ENDPOINT_MOBILE);

    // example for playstation accounts to get the credits
    // 3. parameter of the forge factory is the actual real http method
    // 4. parameter is the overridden method for the webapp headers
    $forge = Forge::getForge($client, '/ut/game/fifa14/user/credits', 'post', 'get');
    $json = $forge
        ->setNucId($export['nucleusId'])
        ->setSid($export['sessionId'])
        ->setPhishing($export['phishingToken'])
        ->getJson();

    echo "you have " . $json['credits'] . " coins" . PHP_EOL;
```
