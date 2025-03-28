<?php

declare(strict_types=1);

namespace OpenAI\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Config;
use OpenAI\Contracts\ResponseContract;
use OpenAI\Laravel\Testing\OpenAIFake;
use OpenAI\Responses\StreamResponse;
use OpenAI\Client;

/**
 * @method static \OpenAI\Resources\Assistants assistants()
 * @method static \OpenAI\Resources\Audio audio()
 * @method static \OpenAI\Resources\Chat chat()
 * @method static \OpenAI\Resources\Completions completions()
 * @method static \OpenAI\Resources\Embeddings embeddings()
 * @method static \OpenAI\Resources\Edits edits()
 * @method static \OpenAI\Resources\Files files()
 * @method static \OpenAI\Resources\FineTunes fineTunes()
 * @method static \OpenAI\Resources\Images images()
 * @method static \OpenAI\Resources\Models models()
 * @method static \OpenAI\Resources\Moderations moderations()
 * @method static \OpenAI\Resources\Threads threads()
 */
final class OpenAI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'openai';
    }

    /**
     * @param  array<array-key, ResponseContract|StreamResponse|string>  $responses
     */
    public static function fake(array $responses = []): OpenAIFake /** @phpstan-ignore-line */
    {
        $fake = new OpenAIFake($responses);
        self::swap($fake);

        return $fake;
    }

    /**
     * Create a custom OpenAI client with the provided configuration.
     * 
     * @param string $apiKey The OpenAI API key to use
     * @param string|null $baseUri Optional custom base URI
     * @param string|null $organization Optional organization ID
     * @return \OpenAI\Client A new OpenAI client instance
     */
    public static function custom(string $apiKey, ?string $baseUri = null, ?string $organization = null): \OpenAI\Client
    {
        $timeout = \Illuminate\Support\Facades\Config::get('openai.request_timeout', 30);
        
        $factory = new \OpenAI\Factory();
        $client = $factory->withApiKey($apiKey)
            // ->withHttpHeader('OpenAI-Beta', 'assistants=v1')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $timeout]));
        
        if ($baseUri !== null) {
            $client = $client->withBaseUri($baseUri);
        }
        
        if ($organization !== null) {
            $client = $client->withOrganization($organization);
        }

        return $client->make();
    }
}
