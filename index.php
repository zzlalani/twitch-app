<?php

session_start();
require __DIR__ . '/vendor/autoload.php';

define('TOKEN', 'token');

use NewTwitchApi\NewTwitchApi;
use NewTwitchApi\HelixGuzzleClient;

$config = [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => true,
        'twitch' => [
            'clientId' => 'iwiw3fkt0fslz4e01sp3evf4tm1zhh',
            'clientSecret' => 'zn2p9jemzfxcrh6mqwn08fcnvq3w2g',
            'redirectURL' => 'http://localhost/oauth',
            'responseType' => 'code',
            'scope' => 'user:edit+user:read:email+bits:read+channel_subscriptions+whispers:read',
        ],
    ],
];

$app = new Slim\App($config);

$container = $app->getContainer();

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('templates/', [
        'cache' => false,
    ]);

    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

// Read data from HelixGuzzleClient Response
$container['dataRead'] = function ($c) {
    return function ($body) {
        return $body->getBody()->read($body->getBody()->getSize());
    };
};

// Check the token middleware.
$loggedInMiddleware = function ($request, $response, $next) {
    $route = $request->getAttribute('route');
    $routeName = $route->getName();

    # Define routes that user does not have to be logged in with. All other routes, the user
    # needs to be logged in with.
    $publicRoutesArray = array(
        'home',
        'oauth'
    );

    if (!isset($_SESSION[TOKEN]) && !in_array($routeName, $publicRoutesArray)) {
        $response = $response->withRedirect('/');
    } else {
        // Proceed as normal...
        $response = $next($request, $response);
    }

    return $response;
};

// Apply the middleware to every request.
$app->add($loggedInMiddleware);

$app->get('/', function ($request, $response, $args) {
    $t = $this->get('settings')['twitch'];

    $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
    $newTwitchApi = new NewTwitchApi($helixGuzzleClient, $t['clientId'], $t['clientSecret']);
    $authUrl = $newTwitchApi->getOauthApi()->getAuthUrl($t['redirectURL'], $t['responseType'], $t['scope']);

    return $this->view->render($response, 'home.html', [
        'authUrl' => $authUrl
    ]);
})->setName('home');

$app->get('/oauth', function ($request, $response, $args) {
    $q = $request->getQueryParams();
    $t = $this->get('settings')['twitch'];

    try {
        $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
        $newTwitchApi = new NewTwitchApi($helixGuzzleClient, $t['clientId'], $t['clientSecret']);
        $resp = $newTwitchApi->getOauthApi()->getUserAccessToken($q['code'], $t['redirectURL']);
        $_SESSION[TOKEN] = $this->get('dataRead')($resp);
        return $response->withRedirect('/set');
    }
    catch (Exception $e) {
        return $response->withRedirect('/');
    }
})->setName('oauth');

$app->get('/set', function ($request, $response, $args) {
    return $this->view->render($response, 'set.html', []);
});

$app->post('/set', function ($request, $response, $args) {
    $b = $request->getParsedBody();
    $streamerName = filter_var($b['streamer_name'], FILTER_SANITIZE_STRING);
    return $response->withRedirect('/streamer/' . $streamerName);
});

$app->get('/streamer/{streamer_name}', function ($request, $response, $args) {
    $t = $this->get('settings')['twitch'];
    try {
        $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
        $newTwitchApi = new NewTwitchApi($helixGuzzleClient, $t['clientId'], $t['clientSecret']);
        $resp = $newTwitchApi->getUsersApi()->getUserByUsername($args['streamer_name']);
        $userData = json_decode($this->get('dataRead')($resp), true);

        $session = json_decode($_SESSION[TOKEN], true);

        return $this->view->render($response, 'streamer.html', [
            'streamerName' => $args['streamer_name'],
            'name' => $userData['data'][0]['display_name'],
            'img' => $userData['data'][0]['profile_image_url'],
            'desc' => $userData['data'][0]['description'],
            'channelId' => $userData['data'][0]['id'],
            'clientId' => $t['clientId'],
            'accessToken' => $session['access_token'],
        ]);
    }
    catch (Exception $e) {
        return $response->withRedirect('/');
    }
});

$app->get('/me', function ($request, $response, $args) {
    $t = $this->get('settings')['twitch'];
    try {
        $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
        $newTwitchApi = new NewTwitchApi($helixGuzzleClient, $t['clientId'], $t['clientSecret']);

        $session = json_decode($_SESSION[TOKEN], true);
        $resp = $newTwitchApi->getUsersApi()->getUserByAccessToken($session['access_token']);
        $userData = json_decode($this->get('dataRead')($resp), true);

        return $response->withRedirect('/streamer/' . $userData['data'][0]['login']);
    }
    catch (Exception $e) {
        return $response->withRedirect('/');
    }
});

$app->get('/logout', function ($request, $response, $args) {
    $t = $this->get('settings')['twitch'];
    try {
        $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
        $session = json_decode($_SESSION[TOKEN], true);
        $helixGuzzleClient->post('https://id.twitch.tv/oauth2/revoke?client_id='.$t['clientId'].'&token='.$session['access_token']);
        session_destroy();
        return $response->withRedirect('/');
    }
    catch (Exception $e) {
        return $response->withRedirect('/');
    }
});


$app->run();