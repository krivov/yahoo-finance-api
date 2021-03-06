<?php

namespace Scheb\YahooFinanceApi;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\AssetProfile;
use Scheb\YahooFinanceApi\Results\DividendData;
use Scheb\YahooFinanceApi\Results\FundamentalTimeseries;
use Scheb\YahooFinanceApi\Results\HistoricalData;
use Scheb\YahooFinanceApi\Results\KeyStatistics;
use Scheb\YahooFinanceApi\Results\Quote;
use Scheb\YahooFinanceApi\Results\SearchResult;
use Scheb\YahooFinanceApi\Results\SplitData;

class ApiClient
{
    const INTERVAL_1_DAY = '1d';
    const INTERVAL_1_WEEK = '1wk';
    const INTERVAL_1_MONTH = '1mo';
    const CURRENCY_SYMBOL_SUFFIX = '=X';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ResultDecoder
     */
    private $resultDecoder;

    public function __construct(ClientInterface $guzzleClient, ResultDecoder $resultDecoder)
    {
        $this->client = $guzzleClient;
        $this->resultDecoder = $resultDecoder;
    }

    /**
     * Search for stocks.
     *
     * @param string $searchTerm
     *
     * @return array|SearchResult[]
     *
     * @throws ApiException
     */
    public function search($searchTerm)
    {
        $url = 'https://finance.yahoo.com/_finance_doubledown/api/resource/searchassist;gossipConfig=%7B%22queryKey%22:%22query%22,%22resultAccessor%22:%22ResultSet.Result%22,%22suggestionTitleAccessor%22:%22symbol%22,%22suggestionMeta%22:[%22symbol%22],%22url%22:%7B%22query%22:%7B%22region%22:%22US%22,%22lang%22:%22en-US%22%7D%7D%7D;searchTerm='
            .urlencode($searchTerm)
            .'?bkt=[%22findd-ctrl%22,%22fin-strm-test1%22,%22fndmtest%22,%22finnossl%22]&device=desktop&feature=canvassOffnet,finGrayNav,newContentAttribution,relatedVideoFeature,videoNativePlaylist,livecoverage&intl=us&lang=en-US&partner=none&prid=eo2okrhcni00f&region=US&site=finance&tz=UTC&ver=0.102.432&returnMeta=true';
        $responseBody = (string) $this->client->request('GET', $url)->getBody();

        return $this->resultDecoder->transformSearchResult($responseBody);
    }

    /**
     * Get historical data for a symbol.
     *
     * @param string    $symbol
     * @param string    $interval
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array|HistoricalData[]
     *
     * @throws ApiException
     */
    public function getHistoricalData($symbol, $interval, \DateTime $startDate, \DateTime $endDate)
    {
        $allowedIntervals = [self::INTERVAL_1_DAY, self::INTERVAL_1_WEEK, self::INTERVAL_1_MONTH];
        if (!in_array($interval, $allowedIntervals)) {
            throw new \InvalidArgumentException('Interval must be one of: '.implode(', ', $allowedIntervals));
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $cookieJar = new CookieJar();

        $initialUrl = 'https://finance.yahoo.com/quote/'.urlencode($symbol).'/history?p='.urlencode($symbol);
        $responseBody = (string) $this->client->request('GET', $initialUrl, ['cookies' => $cookieJar])->getBody();
        $crumb = $this->resultDecoder->extractCrumb($responseBody);

        $dataUrl = 'https://query1.finance.yahoo.com/v7/finance/download/'.urlencode($symbol).'?period1='.$startDate->getTimestamp().'&period2='.$endDate->getTimestamp().'&interval='.$interval.'&events=history&crumb='.urlencode($crumb);
        $responseBody = (string) $this->client->request('GET', $dataUrl, ['cookies' => $cookieJar])->getBody();

        return $this->resultDecoder->transformHistoricalDataResult($responseBody);
    }

    /**
     * Get dividends data for a symbol.
     *
     * @param string    $symbol
     * @param string    $interval
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array|DividendData[]
     *
     * @throws ApiException
     */
    public function getDividendsData($symbol, $interval, \DateTime $startDate, \DateTime $endDate)
    {
        $allowedIntervals = [self::INTERVAL_1_DAY, self::INTERVAL_1_WEEK, self::INTERVAL_1_MONTH];
        if (!in_array($interval, $allowedIntervals)) {
            throw new \InvalidArgumentException('Interval must be one of: '.implode(', ', $allowedIntervals));
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $cookieJar = new CookieJar();

        $initialUrl = 'https://finance.yahoo.com/quote/'.urlencode($symbol).'/history?p='.urlencode($symbol) . '&filter=div';
        $responseBody = (string) $this->client->request('GET', $initialUrl, ['cookies' => $cookieJar])->getBody();
        $crumb = $this->resultDecoder->extractCrumb($responseBody);

        $dataUrl = 'https://query1.finance.yahoo.com/v7/finance/download/'.urlencode($symbol).'?period1='.$startDate->getTimestamp().'&period2='.$endDate->getTimestamp().'&interval='.$interval.'&events=div&crumb='.urlencode($crumb);
        $responseBody = (string) $this->client->request('GET', $dataUrl, ['cookies' => $cookieJar])->getBody();

        return $this->resultDecoder->transformDividendsDataResult($responseBody);
    }

    /**
     * Get splits data for a symbol.
     *
     * @param  string     $symbol
     * @param  string     $interval
     * @param  \DateTime  $startDate
     * @param  \DateTime  $endDate
     *
     * @return array|SplitData[]
     *
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSplitsData($symbol, $interval, \DateTime $startDate, \DateTime $endDate)
    {
        //@TODO: combine and remove duplicate code for getSplitsData, getDividendsData and getHistoricalData
        $allowedIntervals = [self::INTERVAL_1_DAY, self::INTERVAL_1_WEEK, self::INTERVAL_1_MONTH];
        if (!in_array($interval, $allowedIntervals)) {
            throw new \InvalidArgumentException('Interval must be one of: '.implode(', ', $allowedIntervals));
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $cookieJar = new CookieJar();

        $initialUrl = 'https://finance.yahoo.com/quote/'.urlencode($symbol).'/history?p='.urlencode($symbol) . '&filter=div';
        $responseBody = (string) $this->client->request('GET', $initialUrl, ['cookies' => $cookieJar])->getBody();
        $crumb = $this->resultDecoder->extractCrumb($responseBody);

        $dataUrl = 'https://query1.finance.yahoo.com/v7/finance/download/'.urlencode($symbol).'?period1='.$startDate->getTimestamp().'&period2='.$endDate->getTimestamp().'&interval='.$interval.'&events=split&crumb='.urlencode($crumb);
        $responseBody = (string) $this->client->request('GET', $dataUrl, ['cookies' => $cookieJar])->getBody();

        return $this->resultDecoder->transformSplitsDataResult($responseBody);
    }

    /**
     * Get asset profile data for a symbol.
     *
     * @param  string  $symbol
     *
     * @return AssetProfile
     *
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAssetProfile($symbol)
    {
        $cookieJar = new CookieJar();

        $initialUrl = 'https://finance.yahoo.com/quote/'.urlencode($symbol).'/history?p='.urlencode($symbol) . '&filter=div';
        $responseBody = (string) $this->client->request('GET', $initialUrl, ['cookies' => $cookieJar])->getBody();
        $crumb = $this->resultDecoder->extractCrumb($responseBody);

        $dataUrl = 'https://query2.finance.yahoo.com/v10/finance/quoteSummary/'.urlencode($symbol).'?formatted=true&crumb='.urlencode($crumb).'&lang=en-US&region=US&modules=assetProfile&corsDomain=finance.yahoo.com';
        $responseBody = (string) $this->client->request('GET', $dataUrl, ['cookies' => $cookieJar])->getBody();

        return $this->resultDecoder->transformAssetProfileResult($responseBody);
    }

    /**
     * Get quote for a single symbol.
     *
     * @param string $symbol
     *
     * @return Quote|null
     */
    public function getQuote($symbol)
    {
        $list = $this->fetchQuotes([$symbol]);

        return isset($list[0]) ? $list[0] : null;
    }

    /**
     * Get quotes for one or multiple symbols.
     *
     * @param array $symbols
     *
     * @return array|Quote[]
     */
    public function getQuotes(array $symbols)
    {
        return $this->fetchQuotes($symbols);
    }

    /**
     * Get exchange rate for two currencies. Accepts concatenated ISO 4217 currency codes.
     *
     * @param string $currency1
     * @param string $currency2
     *
     * @return Quote|null
     */
    public function getExchangeRate($currency1, $currency2)
    {
        $list = $this->getExchangeRates([[$currency1, $currency2]]);

        return isset($list[0]) ? $list[0] : null;
    }

    /**
     * Retrieves currency exchange rates. Accepts concatenated ISO 4217 currency codes such as "GBPUSD".
     *
     * @param array $currencyPairs List of pairs of currencies
     *
     * @return array|Quote[]
     */
    public function getExchangeRates(array $currencyPairs)
    {
        $currencySymbols = array_map(function (array $currencies) {
            return implode($currencies).self::CURRENCY_SYMBOL_SUFFIX; // Currency pairs are suffixed with "=X"
        }, $currencyPairs);

        return $this->fetchQuotes($currencySymbols);
    }

    /**
     * Fetch quote data from API.
     *
     * @param array $symbols
     *
     * @return array|Quote[]
     */
    private function fetchQuotes(array $symbols)
    {
        $url = 'https://query1.finance.yahoo.com/v7/finance/quote?symbols='.urlencode(implode(',', $symbols));
        $responseBody = (string) $this->client->request('GET', $url)->getBody();

        return $this->resultDecoder->transformQuotes($responseBody);
    }

    /**
     * Fetch fundamentals data from API.
     *
     * @return array|FundamentalTimeseries[]
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFundamentalTimeseries($symbol)
    {
        $fields = ['annualAccountsPayable', 'annualAccountsReceivable', 'annualAccumulatedDepreciation', 'annualBasicAverageShares', 'annualBasicEPS', 'annualBeginningCashPosition', 'annualCapitalExpenditure', 'annualCapitalStock', 'annualCashAndCashEquivalents', 'annualCashCashEquivalentsAndShortTermInvestments', 'annualCashDividendsPaid', 'annualCashFlowFromContinuingFinancingActivities', 'annualChangeInAccountPayable', 'annualChangeInCashSupplementalAsReported', 'annualChangeInInventory', 'annualChangeInWorkingCapital', 'annualChangesInAccountReceivables', 'annualCommonStockIssuance', 'annualCostOfRevenue', 'annualCurrentAccruedExpenses', 'annualCurrentAssets', 'annualCurrentDebt', 'annualCurrentDeferredRevenue', 'annualCurrentLiabilities', 'annualDeferredIncomeTax', 'annualDepreciationAndAmortization', 'annualDilutedAverageShares', 'annualDilutedEPS', 'annualEbitda', 'annualEndCashPosition', 'annualFreeCashFlow', 'annualGainsLossesNotAffectingRetainedEarnings', 'annualGoodwill', 'annualGrossPPE', 'annualGrossProfit', 'annualIncomeTaxPayable', 'annualInterestExpense', 'annualInventory', 'annualInvestingCashFlow', 'annualInvestmentsAndAdvances', 'annualLongTermDebt', 'annualNetIncome', 'annualNetIncomeCommonStockholders', 'annualNetIncomeContinuousOperations', 'annualNetOtherFinancingCharges', 'annualNetOtherInvestingChanges', 'annualNetPPE', 'annualNonCurrentDeferredRevenue', 'annualNonCurrentDeferredTaxesLiabilities', 'annualOperatingCashFlow', 'annualOperatingExpense', 'annualOperatingIncome', 'annualOtherCurrentAssets', 'annualOtherCurrentLiabilities', 'annualOtherIncomeExpense', 'annualOtherIntangibleAssets', 'annualOtherNonCashItems', 'annualOtherNonCurrentAssets', 'annualOtherNonCurrentLiabilities', 'annualOtherShortTermInvestments', 'annualPretaxIncome', 'annualPurchaseOfBusiness', 'annualPurchaseOfInvestment', 'annualRepaymentOfDebt', 'annualRepurchaseOfCapitalStock', 'annualResearchAndDevelopment', 'annualRetainedEarnings', 'annualSaleOfInvestment', 'annualSellingGeneralAndAdministration', 'annualStockBasedCompensation', 'annualStockholdersEquity', 'annualTaxProvision', 'annualTotalAssets', 'annualTotalLiabilitiesNetMinorityInterest', 'annualTotalNonCurrentAssets', 'annualTotalNonCurrentLiabilitiesNetMinorityInterest', 'annualTotalRevenue', 'quarterlyAccountsPayable', 'quarterlyAccountsReceivable', 'quarterlyAccumulatedDepreciation', 'quarterlyBasicAverageShares', 'quarterlyBasicEPS', 'quarterlyBeginningCashPosition', 'quarterlyCapitalExpenditure', 'quarterlyCapitalStock', 'quarterlyCashAndCashEquivalents', 'quarterlyCashCashEquivalentsAndShortTermInvestments', 'quarterlyCashDividendsPaid', 'quarterlyCashFlowFromContinuingFinancingActivities', 'quarterlyChangeInAccountPayable', 'quarterlyChangeInCashSupplementalAsReported', 'quarterlyChangeInInventory', 'quarterlyChangeInWorkingCapital', 'quarterlyChangesInAccountReceivables', 'quarterlyCommonStockIssuance', 'quarterlyCostOfRevenue', 'quarterlyCurrentAccruedExpenses', 'quarterlyCurrentAssets', 'quarterlyCurrentDebt', 'quarterlyCurrentDeferredRevenue', 'quarterlyCurrentLiabilities', 'quarterlyDeferredIncomeTax', 'quarterlyDepreciationAndAmortization', 'quarterlyDilutedAverageShares', 'quarterlyDilutedEPS', 'quarterlyEbitda', 'quarterlyEndCashPosition', 'quarterlyEnterprisesValueEBITDARatio', 'quarterlyEnterprisesValueRevenueRatio', 'quarterlyEnterpriseValue', 'quarterlyForwardPeRatio', 'quarterlyFreeCashFlow', 'quarterlyGainsLossesNotAffectingRetainedEarnings', 'quarterlyGoodwill', 'quarterlyGrossPPE', 'quarterlyGrossProfit', 'quarterlyIncomeTaxPayable', 'quarterlyInterestExpense', 'quarterlyInventory', 'quarterlyInvestingCashFlow', 'quarterlyInvestmentsAndAdvances', 'quarterlyLongTermDebt', 'quarterlyMarketCap', 'quarterlyNetIncome', 'quarterlyNetIncomeCommonStockholders', 'quarterlyNetIncomeContinuousOperations', 'quarterlyNetOtherFinancingCharges', 'quarterlyNetOtherInvestingChanges', 'quarterlyNetPPE', 'quarterlyNonCurrentDeferredRevenue', 'quarterlyNonCurrentDeferredTaxesLiabilities', 'quarterlyOperatingCashFlow', 'quarterlyOperatingExpense', 'quarterlyOperatingIncome', 'quarterlyOtherCurrentAssets', 'quarterlyOtherCurrentLiabilities', 'quarterlyOtherIncomeExpense', 'quarterlyOtherIntangibleAssets', 'quarterlyOtherNonCashItems', 'quarterlyOtherNonCurrentAssets', 'quarterlyOtherNonCurrentLiabilities', 'quarterlyOtherShortTermInvestments', 'quarterlyPbRatio', 'quarterlyPegRatio', 'quarterlyPeRatio', 'quarterlyPretaxIncome', 'quarterlyPsRatio', 'quarterlyPurchaseOfBusiness', 'quarterlyPurchaseOfInvestment', 'quarterlyRepaymentOfDebt', 'quarterlyRepurchaseOfCapitalStock', 'quarterlyResearchAndDevelopment', 'quarterlyRetainedEarnings', 'quarterlySaleOfInvestment', 'quarterlySellingGeneralAndAdministration', 'quarterlyStockBasedCompensation', 'quarterlyStockholdersEquity', 'quarterlyTaxProvision', 'quarterlyTotalAssets', 'quarterlyTotalLiabilitiesNetMinorityInterest', 'quarterlyTotalNonCurrentAssets', 'quarterlyTotalNonCurrentLiabilitiesNetMinorityInterest', 'quarterlyTotalRevenue', 'trailingAccountsPayable', 'trailingAccountsReceivable', 'trailingAccumulatedDepreciation', 'trailingBasicAverageShares', 'trailingBasicEPS', 'trailingBeginningCashPosition', 'trailingCapitalExpenditure', 'trailingCapitalStock', 'trailingCashAndCashEquivalents', 'trailingCashCashEquivalentsAndShortTermInvestments', 'trailingCashDividendsPaid', 'trailingCashFlowFromContinuingFinancingActivities', 'trailingChangeInAccountPayable', 'trailingChangeInCashSupplementalAsReported', 'trailingChangeInInventory', 'trailingChangeInWorkingCapital', 'trailingChangesInAccountReceivables', 'trailingCommonStockIssuance', 'trailingCostOfRevenue', 'trailingCurrentAccruedExpenses', 'trailingCurrentAssets', 'trailingCurrentDebt', 'trailingCurrentDeferredRevenue', 'trailingCurrentLiabilities', 'trailingDeferredIncomeTax', 'trailingDepreciationAndAmortization', 'trailingDilutedAverageShares', 'trailingDilutedEPS', 'trailingEbitda', 'trailingEndCashPosition', 'trailingEnterprisesValueEBITDARatio', 'trailingEnterprisesValueRevenueRatio', 'trailingEnterpriseValue', 'trailingForwardPeRatio', 'trailingFreeCashFlow', 'trailingGainsLossesNotAffectingRetainedEarnings', 'trailingGoodwill', 'trailingGrossPPE', 'trailingGrossProfit', 'trailingIncomeTaxPayable', 'trailingInterestExpense', 'trailingInventory', 'trailingInvestingCashFlow', 'trailingInvestmentsAndAdvances', 'trailingLongTermDebt', 'trailingMarketCap', 'trailingNetIncome', 'trailingNetIncomeCommonStockholders', 'trailingNetIncomeContinuousOperations', 'trailingNetOtherFinancingCharges', 'trailingNetOtherInvestingChanges', 'trailingNetPPE', 'trailingNonCurrentDeferredRevenue', 'trailingNonCurrentDeferredTaxesLiabilities', 'trailingOperatingCashFlow', 'trailingOperatingExpense', 'trailingOperatingIncome', 'trailingOtherCurrentAssets', 'trailingOtherCurrentLiabilities', 'trailingOtherIncomeExpense', 'trailingOtherIntangibleAssets', 'trailingOtherNonCashItems', 'trailingOtherNonCurrentAssets', 'trailingOtherNonCurrentLiabilities', 'trailingOtherShortTermInvestments', 'trailingPbRatio', 'trailingPegRatio', 'trailingPeRatio', 'trailingPretaxIncome', 'trailingPsRatio', 'trailingPurchaseOfBusiness', 'trailingPurchaseOfInvestment', 'trailingRepaymentOfDebt', 'trailingRepurchaseOfCapitalStock', 'trailingResearchAndDevelopment', 'trailingRetainedEarnings', 'trailingSaleOfInvestment', 'trailingSellingGeneralAndAdministration', 'trailingStockBasedCompensation', 'trailingStockholdersEquity', 'trailingTaxProvision', 'trailingTotalAssets', 'trailingTotalLiabilitiesNetMinorityInterest', 'trailingTotalNonCurrentAssets', 'trailingTotalNonCurrentLiabilitiesNetMinorityInterest', 'trailingTotalRevenue'];

        $url = 'https://query1.finance.yahoo.com/ws/fundamentals-timeseries/v1/finance/timeseries/' . $symbol . '?lang=en-US&region=US&symbol=' . $symbol . '&padTimeSeries=true&type='.implode('%2C', $fields).'&merge=false&period1=493590046&period2=' . time() . '&corsDomain=finance.yahoo.com';
        $responseBody = (string) $this->client->request('GET', $url)->getBody();

        return $this->resultDecoder->transformFundamentalTimeseries($responseBody);
    }

    /**
     * Fetch fundamentals data from API.
     *
     * @return array|KeyStatistics
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getKeyStatistics($symbol)
    {
        $url = 'https://finance.yahoo.com/quote/' . $symbol . '/key-statistics?p=' . $symbol;
        $responseBody = (string) $this->client->request('GET', $url)->getBody();

        return $this->resultDecoder->transformKeyStatistics($responseBody);
    }
}
