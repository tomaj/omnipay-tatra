<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Omnipay\Omnipay;

$gateway = Omnipay::create('ComfortPay');

// #region tls
// The web service certificate is verified with the system CA store. When that store cannot verify it:
$gateway->setCaFile('/etc/ssl/certs/ca-certificates.crt');

// or full control over the TLS connection (it replaces the default context):
$gateway->setStreamContext(stream_context_create([
    'ssl' => ['cafile' => '/etc/tatrabanka/ca.pem', 'verify_peer' => true, 'verify_peer_name' => true],
]));

// through an HTTP proxy
$gateway->setProxyHost('proxy.internal');
$gateway->setProxyPort(3128);
$gateway->setProxyLogin('shop');
$gateway->setProxyPassword('secret');
// #endregion tls

$options = $gateway->checkCard()->getSoapClientOptions();
assert($options['proxy_host'] === 'proxy.internal' && is_resource($options['stream_context']));
echo "ok\n";
