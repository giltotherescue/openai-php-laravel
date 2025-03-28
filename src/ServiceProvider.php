<?php

declare(strict_types=1);

namespace OpenAI\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use OpenAI;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;
use OpenAI\Laravel\Commands\InstallCommand;
use OpenAI\Laravel\Exceptions\ApiKeyIsMissing;

/**
 * @internal
 */
final class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClientContract::class, static function (): Client {
            $apiKey = config('openai.api_key');
            $organization = config('openai.organization');
            $baseUri = config('openai.base_uri');
            $timeout = config('openai.request_timeout', 30);

            if (! is_string($apiKey) || ($organization !== null && ! is_string($organization))) {
                throw ApiKeyIsMissing::create();
            }

            $factory = new \OpenAI\Factory();
            $client = $factory->withApiKey($apiKey)
                // ->withHttpHeader('OpenAI-Beta', 'assistants=v1')
                ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $timeout]));

            if ($organization !== null) {
                $client = $client->withOrganization($organization);
            }

            if (is_string($baseUri)) {
                $client = $client->withBaseUri($baseUri);
            }

            return $client->make();
        });

        $this->app->alias(ClientContract::class, 'openai');
        $this->app->alias(ClientContract::class, Client::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/openai.php' => config_path('openai.php'),
            ]);

            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            Client::class,
            ClientContract::class,
            'openai',
        ];
    }

    public function rebindClient(string $newApiKey, string $newBaseUri): void
    {
        $this->app->singleton(ClientContract::class, function () use ($newApiKey, $newBaseUri) {
            $organization = config('openai.organization');
            $timeout = config('openai.request_timeout', 30);

            $factory = new \OpenAI\Factory();
            $client = $factory->withApiKey($newApiKey)
                // ->withHttpHeader('OpenAI-Beta', 'assistants=v1')
                ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $timeout]));

            $client = $client->withBaseUri($newBaseUri);
            
            if ($organization !== null) {
                $client = $client->withOrganization($organization);
            }

            return $client->make();
        });

        // Re-alias to ensure the rebinding is effective
        $this->app->alias(ClientContract::class, 'openai');
        $this->app->alias(ClientContract::class, Client::class);
    }
}
