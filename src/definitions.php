<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Lits\Config\LibCalConfig;
use Lits\Framework;
use Lits\LibCal\Client;
use Lits\Settings;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface as HttpRequestFactory;
use Psr\Http\Message\StreamFactoryInterface as HttpStreamFactory;

return function (Framework $framework): void {
    $framework->addDefinition(
        Client::class,
        function (
            Settings $settings,
            HttpClient $client,
            HttpRequestFactory $requestFactory,
            HttpStreamFactory $streamFactory
        ): Client {
            assert($settings['libcal'] instanceof LibCalConfig);

            $settings['libcal']->test();

            return new Client(
                $settings['libcal']->host,
                $settings['libcal']->clientId,
                $settings['libcal']->clientSecret,
                $client,
                $requestFactory,
                $streamFactory
            );
        },
    );

    $framework->addDefinition(
        HttpClient::class,
        DI\create(GuzzleHttpClient::class)
    );

    $framework->addDefinition(
        HttpRequestFactory::class,
        DI\create(HttpFactory::class)
    );

    $framework->addDefinition(
        HttpStreamFactory::class,
        DI\get(HttpRequestFactory::class)
    );
};
