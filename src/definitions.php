<?php

declare(strict_types=1);

use Desarrolla2\Cache\Memory;
use Desarrolla2\Cache\PhpFile;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Lits\Config\LibCalConfig;
use Lits\Framework;
use Lits\LibCal\Client;
use Lits\Settings;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface as HttpRequestFactory;
use Psr\Http\Message\StreamFactoryInterface as HttpStreamFactory;
use Psr\SimpleCache\CacheInterface as Cache;

use function DI\create;
use function DI\get;

return function (Framework $framework): void {
    $framework->addDefinition(
        Client::class,
        function (
            Settings $settings,
            HttpClient $client,
            HttpRequestFactory $requestFactory,
            HttpStreamFactory $streamFactory,
            Cache $cache
        ): Client {
            assert($settings['libcal'] instanceof LibCalConfig);

            $settings['libcal']->test();

            return new Client(
                $settings['libcal']->host,
                $settings['libcal']->clientId,
                $settings['libcal']->clientSecret,
                $client,
                $requestFactory,
                $streamFactory,
                $cache
            );
        },
    );

    $framework->addDefinition(
        Cache::class,
        function (Settings $settings): Cache {
            assert($settings['libcal'] instanceof LibCalConfig);

            if (!is_null($settings['libcal']->cache)) {
                return new PhpFile($settings['libcal']->cache);
            }

            return new Memory();
        }
    );

    $framework->addDefinition(
        HttpClient::class,
        create(GuzzleHttpClient::class)
    );

    $framework->addDefinition(
        HttpRequestFactory::class,
        create(HttpFactory::class)
    );

    $framework->addDefinition(
        HttpStreamFactory::class,
        get(HttpRequestFactory::class)
    );
};
