<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

// #region snippet
use Omnipay\ComfortPay\Message\AbstractSoapRequest;
use Omnipay\ComfortPay\TransactionType;
use Omnipay\Omnipay;

/**
 * A SoapClient on the bundled WSDL that answers with canned XML instead of calling Tatra banka.
 */
final class CannedSoapClient extends \SoapClient
{
    public ?string $lastRequest = null;

    public function __construct(private readonly string $body)
    {
        parent::__construct(AbstractSoapRequest::WSDL, ['trace' => true, 'soap_version' => SOAP_1_2, 'cache_wsdl' => WSDL_CACHE_NONE]);
    }

    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false, ?string $uriParserClass = null): string
    {
        $this->lastRequest = $request;

        return '<?xml version="1.0" encoding="UTF-8"?><env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"'
            . ' xmlns:t="urn:tatrabanka:ibanking:Teleplatba"><env:Body>' . $this->body . '</env:Body></env:Envelope>';
    }
}

$client = new CannedSoapClient('<t:doCardTransactionResponse><res><transactionId>555</transactionId>'
    . '<transactionStatus>00</transactionStatus><transactionApproval>A1B2</transactionApproval></res></t:doCardTransactionResponse>');

$gateway = Omnipay::create('ComfortPay');
$gateway->initialize(['ws' => '1234', 'terminalId' => '5678', 'certPath' => '/dev/null', 'certPass' => 'x']);
$gateway->setSoapClient($client); // no test mode: the request is built and parsed for real

$response = $gateway->charge([
    'transactionId' => '555',
    'transactionType' => TransactionType::Purchase,
    'referedCardId' => '111111',
    'amount' => '9.90',
    'currency' => 'EUR',
    'vs' => '2026030',
    'ss' => '202612',
])->send();

assert($response->isSuccessful() && $response->getTransactionApproval() === 'A1B2');
assert(str_contains((string) $client->lastRequest, '<referedCardId>111111</referedCardId>'));
// #endregion snippet

echo "ok\n";
