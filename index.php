<?php

require __DIR__ . '/vendor/autoload.php';

use NewTwitchApi\NewTwitchApi;
use NewTwitchApi\HelixGuzzleClient;

$config = [
    'settings' => [
        'twitch' => [
            'clientId' => 'iwiw3fkt0fslz4e01sp3evf4tm1zhh',
            'clientSecret' => 'zn2p9jemzfxcrh6mqwn08fcnvq3w2g',
            'redirectURL' => 'http://localhost/twitchApp/oauth',
            'responseType' => 'code',
            'scope' => 'user:edit+user:read:email',
        ],
    ],
];

$app = new Slim\App($config);

$container = $app->getContainer();

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('templates/', [
//        'cache' => 'tmp/views/',
        'cache' => false,
    ]);

    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

$app->get('/', function ($request, $response, $args) {
    $t = $this->get('settings')['twitch'];

    $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
    $newTwitchApi = new NewTwitchApi($helixGuzzleClient, $t['clientId'], $t['clientSecret']);
    $authUrl = $newTwitchApi->getOauthApi()->getAuthUrl($t['redirectURL'], $t['responseType'], $t['scope']);

    return $this->view->render($response, 'home.html', [
        'authUrl' => $authUrl
    ]);
});

$app->get('/oauth', function ($request, $response, $args) {
    $q = $request->getQueryParams();
    $t = $this->get('settings')['twitch'];

    $helixGuzzleClient = new HelixGuzzleClient($t['clientId']);
    $newTwitchApi = new NewTwitchApi($helixGuzzleClient, $t['clientId'], $t['clientSecret']);
    $resp = $newTwitchApi->getOauthApi()->getUserAccessToken($q['code'], $q['scope']);
    var_dump($resp);
});

$app->run();