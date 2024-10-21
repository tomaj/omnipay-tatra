Omnipay Tatra module
====================


## **ComfortPay**

### ChargeRequest

Method to create transaction with registered card.

##### Parameters:

- `transactionId` (required) - Unique ID of transaction
- `parentTransactionId` (optional) - Unique ID of parent transaction. Mandatory for transactionType PREAUTH-CONFIRM, PREAUTH-CANCEL and CHARGEBACK
- `transactionType` (required) - transaction type, allowed options: PURCHASE, PREAUTHORIZATION, PREAUTH-CONFIRM, PREAUTH-CANCEL, CHARGEBACK
- `referedCardId` (required) - The registration ID of card
- `ws` (required) - Merchant ID
- `terminalId` (required) - Terminal ID
- `amount` (required) - Amount of transaction
- `currency` (required) - Currency of transaction (ISO 4217 currency codes)
- `vs` (required if e2eReference is empty) - Variable symbol
- `ss` (required if e2eReference is empty) - Specific symbol
- `e2eReference` (required if vs and ss are empty) - E2E reference
- `submerchantId` (required only if sending IPSPS Data)- IPSP Data - Submerchant ID
- `location` (required only if sending IPSPS Data) - IPSP Data - Location, string max length 25 symbols
- `city` (required only if sending IPSPS Data) - IPSP Data  - city, string max length 13 symbols
- `alpha2CountryCode` (required only if sending IPSPS Data) - IPSP Data - ISO 3166-1 alpha-2 code

##### Return:

CardTransactionResponse
- `transactionId` - Unique ID of transaction
- `transactionStatus` - Status code of transaction (see transaction's status codes)
- `transactionApprova` - Autorization code

### CheckCardRequest

Method to check the status of registered card.

##### Parameters:

- `idOfCard` - The registration ID of card

##### Return:

CheckCardResponse
- `status` - (OK, FAIL, UNKNOWN)

### TransactionStatusRequest

Method to check the status of transaction.

##### Parameters:

- `transactionId` - Unique ID of transaction

##### Return:
    
CardTransactionResponse
- `transactionId` - Unique ID of transaction
- `transactionStatus` - Status code of transaction (see transaction's status codes)
- `transactionApproval` - Autorization code

###  ListOfExpiredRequest

Method to get the list of expired cards with expiration date after requested one.

##### Parameters: 
    
- `expDate` (required) -  Expiration date (format: YYYYMMDD)

##### Return:

ListOfExpiredResponse
- `list` - array of card ids

### ListOfExpiredPerIdRequest

Method to get the expiration date of cards specified in request.

##### Parameters:

- `listOfIdCards` - array of card registration IDs (max 1000)

##### Return:
 
ListOfExpiredPerIdResponse
- `listOfIdCards` - array of pairs idOfCard and expiration date

### UnRegisterCardRequest

Method to unregister comfortpay card

##### Parameters:

- `cardId` - card identifier

##### Return:
 
UnRegisterCardResopnse
n/a

##### Transaction's status codes

| Transaction status code | Description | Result |
|---|---|---|
| <0 | Not finished yet  | InProgress  |
| 00 | Approved | Approve |
| 04 | Pick up card | Pickup |
| 05 | Do not honour | Decline |
| 14 | Invalid card number | Decline |
| 51 | Not sufficient funds | Decline |
| 54 | Expired card | Decline |
| 57 | Transaction not allowed for cardholder | Decline |
| 61 | Exceeds withdrawal amount limit | Decline

