<?php

App::before(function ($request) {

    // Localhost for backward compatibility.
    $trusted_proxies = [
        '127.0.0.1',
    ];

    // CloudFlare proxy IPv4 list. This rarely changes but can be found here: https://www.cloudflare.com/ips/
    $trusted_proxies += [
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '104.16.0.0/12',
        '108.162.192.0/18',
        '131.0.72.0/22',
        '141.101.64.0/18',
        '162.158.0.0/15',
        '172.64.0.0/13',
        '173.245.48.0/20',
        '188.114.96.0/20',
        '190.93.240.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '199.27.128.0/21',
    ];

    // Detect if the traffic is from a local machine or load-balancer.
    if (!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
        // For most environments, we can trust a local IP as if it is a reverse proxy.
        $trusted_proxies += [
            $request->getClientIp()
        ];
    }

    // Enforce https schema on rendering to match the proxy:
    if (
        !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'
    ) {
        // Generic reverse proxy.
        $this->app['url']->forceSchema('https');
    } elseif (!empty($_SERVER['HTTP_CF_VISITOR'])) {
        // Cloudflare SSL proxy.
        $visitor = json_decode($_SERVER['HTTP_CF_VISITOR']);
        if ($visitor->scheme == 'https') {
            $this->app['url']->forceSchema('https');
        }
    }

    // Correct IP detection using Cloudflare, even when behind a load balancer.
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        $request->server->set('REMOTE_ADDR', $_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    // Prevent issues with local IPv6.
    if ($_SERVER['REMOTE_ADDR'] == '::1') {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
    }

    $request->setTrustedProxies($trusted_proxies);
});