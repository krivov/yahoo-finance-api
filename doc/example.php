<?php

require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;

// Create a new client from the factory
$client = ApiClientFactory::createApiClient();

// Or use your own Guzzle client and pass it in
$options = [
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    ],
];
$guzzleClient = new Client($options);
$client = ApiClientFactory::createApiClient($guzzleClient);

// Returns an array of Scheb\YahooFinanceApi\Results\SearchResult
$searchResult = $client->search('Apple');

// Returns an array of Scheb\YahooFinanceApi\Results\HistoricalData
$historicalData = $client->getHistoricalQuoteData('AAPL', ApiClient::INTERVAL_1_DAY, new DateTime('-14 days'), new DateTime('today'));

// Returns an array of Scheb\YahooFinanceApi\Results\DividendData
$historicalDividendData = $client->getHistoricalDividendData('AAPL', new DateTime('-365 days'), new DateTime('today'));

// Returns an array of Scheb\YahooFinanceApi\Results\SplitData
$historicalSplitData = $client->getHistoricalSplitData('AAPL', new DateTime('-5 years'), new DateTime('today'));

// Returns Scheb\YahooFinanceApi\Results\Quote
$exchangeRate = $client->getExchangeRate('USD', 'EUR');

// Returns an array of Scheb\YahooFinanceApi\Results\Quote
$exchangeRates = $client->getExchangeRates([
    ['USD', 'EUR'],
    ['EUR', 'USD'],
]);

// Returns Scheb\YahooFinanceApi\Results\Quote
$quote = $client->getQuote('AAPL');

// Returns an array of Scheb\YahooFinanceApi\Results\Quote
$quotes = $client->getQuotes(['AAPL', 'GOOG']);

// Returns Scheb\YahooFinanceApi\Results\FundamentalTimeseries
$fundamentals = $client->getFundamentalTimeseries('AAPL');

// Returns Scheb\YahooFinanceApi\Results\OptionChain
$optionChain = $client->getOptionChain('AAPL');

$stockSummary = $client->stockSummary('AAPL');
