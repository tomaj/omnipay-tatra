<?php

declare(strict_types=1);

// What exactly is signed: builds a TatraPay request, puts the signed string together from the fields of the redirect
// as the bank does, and checks the library's signature against one computed independently by the openssl CLI.
// Runs offline; set OPENSSL to the binary (OpenSSL 3, not LibreSSL) and OPENSSL_CONF to openssl-legacy.cnf for DES.

require __DIR__ . '/vendor/autoload.php';

use Omnipay\Core\Sign\KeyType;
use Omnipay\TatraPay\Gateway;

$openssl = getenv('OPENSSL') ?: 'openssl';

/**
 * Runs a shell pipeline that reads the signed string on stdin and returns its trimmed output.
 */
function cli(string $pipeline, string $input): string
{
    $process = proc_open(['/bin/sh', '-c', $pipeline], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $output = trim((string) stream_get_contents($pipes[1]));
    $error = trim((string) stream_get_contents($pipes[2]));
    if (proc_close($process) !== 0) {
        throw new RuntimeException("{$pipeline} failed: {$error}");
    }

    return $output;
}

// #region keys
$keys = [
    'HMAC' => hash('sha512', 'example HMAC key'),        // 128 hex characters
    'AES' => hash('sha256', 'example AES key'),          // 64 hex characters
    'DES' => 'Exampl3!',                                 // 8 characters
];
// #endregion keys

foreach ($keys as $type => $key) {
    // #region request
    $gateway = new Gateway();
    $gateway->initialize(['mid' => '9999', 'sharedSecret' => $key]);

    $request = $gateway->purchase([
        'amount' => '24.90',
        'currency' => 'EUR',
        'vs' => '2026001',
        'ss' => '77',
        'cs' => '0308',
        'rurl' => 'https://shop.example/payment/return',
        'rem' => 'orders@shop.example',
    ]);
    $request->setTimestamp('24112026101500'); // set to the current UTC time by purchase(); fixed for a stable output
    $fields = $request->send()->getRedirectData();
    // #endregion request

    // #region signed
    // TatraPay purchase: MID AMT CURR VS SS CS RURL (+ REM TIMESTAMP with HMAC), no separators
    $signed = $fields['MID'] . $fields['AMT'] . $fields['CURR'] . $fields['VS'] . $fields['SS'] . $fields['CS'] . $fields['RURL'];
    if (KeyType::fromSecret($key) === KeyType::Hmac) {
        $signed .= $fields['REM'] . $fields['TIMESTAMP'];
    }
    // #endregion signed

    // #region openssl
    $expected = match ($type) {
        'HMAC' => cli("{$openssl} dgst -sha256 -mac HMAC -macopt hexkey:{$key} | sed 's/.*= //'", $signed),
        'AES' => strtoupper(substr(cli("{$openssl} dgst -sha1 -binary | head -c 16 | {$openssl} enc -aes-128-ecb -K " . substr($key, 0, 32) . ' | xxd -p -c 256', $signed), 0, 32)),
        'DES' => strtoupper(substr(cli("{$openssl} dgst -sha1 -binary | {$openssl} enc -des-ecb -provider legacy -provider default -K " . bin2hex($key) . ' | xxd -p -c 256', $signed), 0, 16)),
    };
    // #endregion openssl

    $library = $fields['HMAC'] ?? $fields['SIGN'];
    echo "{$type} (", isset($fields['HMAC']) ? 'HMAC' : 'SIGN', " field)\n";
    echo "  signed:  {$signed}\n";
    echo "  library: {$library}\n";
    echo '  openssl: ', hash_equals($expected, $library) ? 'same' : "DIFFERENT: {$expected}", "\n";
}
