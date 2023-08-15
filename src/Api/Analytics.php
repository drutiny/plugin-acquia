<?php

namespace Drutiny\Acquia\Api;

use Drutiny\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

class Analytics {

    /**
     * @var \GuzzleHttp\Promise\PromiseInterface[]
     */
    protected array $promises = [];

    public function __construct(private Settings $settings) {}

    private function getClient(): Client {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push($this->signRequestHandler());
        return new Client(['handler' => $stack, 'base_uri' => $this->settings->get('acquia.analytics.base_uri')]);
    }

    /**
     * Sign the webhook request with a X-Hub-Signature hmac.
     */
    private function signRequestHandler(): callable {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                $hmac = hash_hmac('sha1', $request->getBody(), $this->settings->get('acquia.analytics.secret'));
                return $handler($request->withHeader('X-Hub-Signature', $hmac), $options);
            };
        };
    }

    /**
     * Queue event to sent to analytics.
     */
    public function queueEvent(string $name, array $properties = []):void {
        if (!$this->settings->has('acquia.analytics.base_uri')) {
            return;
        }
        $this->promises[] = $this->getClient()->postAsync($this->settings->get('acquia.analytics.key'), [
            RequestOptions::JSON => [
                'event' => $name,
                'properties' => $properties
            ]
        ]);
    }

    /**
     * Wait for all events to return.
     */
    public function logQueuedEvents(): void {
        Utils::unwrap($this->promises);
        $this->promises = [];
    }

    public function __destruct()
    {
        $this->logQueuedEvents();
    }
}