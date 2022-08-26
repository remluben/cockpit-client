<?php

/**
 * @author Benjamin Ulmer <ulmer.benjamin@gmail.com>
 * @license MIT
 * @link https://github.com/remluben/cockpit-client/
 * @see https://getcockpit.com
 */

namespace Remluben\CockpitClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Middleware as HttpMiddleware;
use GuzzleHttp\HandlerStack as HttpHandlerStack;
use GuzzleHttp\Exception\RequestException as HttpRequestException;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;
use GuzzleHttp\RequestOptions;
use Remluben\CockpitClient\Exceptions\ClientException;
use Remluben\CockpitClient\Exceptions\InvalidArgumentException;

/**
 * The API client
 */
class Client
{
    protected $url;
    protected $token;
    protected $client;

    private $history = [];

    /**
     * @param \GuzzleHttp\Client $client
     * @param string $url        API base endpoint URL, https://example.tld/{project}/
     * @param string $token      the static token for serverside usage, does not expire
     */
    public function __construct(HttpClient $client, string $url, string $token)
    {
        $this->client = $client;
        $this->url = rtrim($url, '/') . '/';
        $this->token = $token;

        $stack = $this->client->getConfig()['handler'] ?? HttpHandlerStack::create();
        $stack->push(HttpMiddleware::history($this->history));
    }

    /**
     * Returns the last request made or null if no request has been made yet
     * with this client.
     *
     * @return \GuzzleHttp\Psr7\Request|null
     */
    public function getLastRequest(): ?HttpRequest
    {
        return $this->history[0]['request'] ?? null;
    }

    /**
     * Returns the last response or null if no request has been made yet with
     * this client.
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function getLastResponse(): ?HttpResponse
    {
        $response = $this->history[0]['response'] ?? null;

        if ($response === null) {
            return $response;
        }

        // for convenience make sure the body pointer is set to the beginning,
        // to ensure the next getBody()->getContents() call returns the full
        // content

        $response->getBody()->rewind();

        return $response;
    }

    /**
     * Returns the full list of menus by making a request to
     * https://example.tld/api/pages/menus
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#menus
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function menus(): array
    {
        return $this->request('GET', $this->url('pages/menus'));
    }

    /**
     * Returns the menu data of a given menu by making a request to
     * https://example.tld/api/pages/menu/{name}
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#menu
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function menu($name): array
    {
        return $this->request('GET', $this->url(sprintf('pages/menu/%s', $name)));
    }

    /**
     * Returns the full list of pages by making a request to
     * https://example.tld/api/pages/pages
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#pages
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function pages(): array
    {
        return $this->request('GET', $this->url('pages/pages'));
    }

    /**
     * Returns a page by id by making a request to
     * https://example.tld/api/pages/page/{id}
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#fetching-a-page-by-id
     *
     * @param string $id the page's id
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function page(string $id): array
    {
        // TODO: improve by adding additional parameters: locale, populate

        return $this->request('GET', $this->url(sprintf('pages/page/%s', $id), [

        ]));
    }

    /**
     * Returns a page by route by making a request to
     * https://example.tld/api/pages/page?route={route}
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#page
     *
     * @param string $route the page's route (from menu) i.e. /home (note that the slash is added automatically)
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function pageByRoute(string $route): array
    {
        // TODO: improve by adding additional parameters: locale, populate

        return $this->request('GET', $this->url('pages/page', [
            'route' => $route,
        ]));
    }

    /**
     * Returns the full list of routes for all pages by making a request to
     * https://example.tld/api/pages/routes
     *
     * @see http://docs.cockpit.projects.dev.remluben.at/api/endpoints/#routes
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function routes(): array
    {
        // TODO: improve by adding additional parameters: locale

        return $this->request('GET', $this->url('pages/routes', [

        ]));
    }

    /**
     * Returns the pages' settings by making a request to
     * https://example.tld/api/pages/settings
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#settings
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function settings(): array
    {
        // TODO: improve by adding additional parameters: locale

        return $this->request('GET', $this->url('pages/settings', [

        ]));
    }

    /**
     * Returns the pages' sitemap by making a request to
     * https://example.tld/api/pages/sitemap
     *
     * @see https://getcockpit.com/documentation/pro/pages/api#sitemap
     *
     * @return array
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    public function sitemap(): array
    {
        return $this->request('GET', $this->url('pages/sitemap'));
    }

    /**
     * Returns all (a list of) content items for a given content model
     * https://example.tld/api/content/items/{model}
     *
     * @see  https://getcockpit.com/documentation/core/api/content#get-content-items-model
     *
     * @param  string $model the content model to retrieve items for
     * @param  array  $options (optional, default: []) the filter options
     *                as defined in the docs
     *
     * @return array
     *
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     * @throws \Remluben\CockpitClient\Exceptions\InvalidArgumentException
     */
    public function contentItems(string $model, array $params = []): array
    {
        if (!trim($model)) {
            throw new InvalidArgumentException(sprintf(
                "Invalid parameter '\$model' for %s::contentItems(\$model). Make sure to provide a valid string content model name.",
                get_class($this)
            ));
        }

        $whitelist = [
            'locale',
            'filter',
            'sort',
            'fields',
            'limit',
            'skip',
            'populate',
        ];

        $invalid = array_diff_key($params, array_flip($whitelist));

        if ($invalid) {
            throw new InvalidArgumentException(sprintf(
                "Invalid parameter '\$params' for %s::contentItems(\$model, \$params). '%s' are not valid parameters. Make sure to provide only valid parameters with the following keys allowed: '%s'.",
                get_class($this),
                implode(', ', array_keys($invalid)),
                implode(', ', $whitelist)
            ));
        }

        return $this->request('GET', $this->url(
            sprintf('content/items/%s', $model),
            $params
        ));
    }

    /**
     * Internal request wrapper for making the http requests to api. Sets some
     * default options. Returns the response data for successful requests if
     * available.
     *
     * @param string $method
     * @param string $url
     * @param array  $options, the \GuzzleHttp\Client::request() options
     *
     * @return array
     *
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    private function request(string $method, string $url, array $options = [])
    {
        $options = array_merge([
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'api-key' => $this->token
            ]
        ], $options);

        // reset history, only last request and response will be stored

        $this->history = [];

        try {
            $this->client->request($method, $url, $options);
        }
        catch(HttpRequestException $e) {
            $exception = new ClientException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );

            $exception->setRequest($e->getRequest());

            if ($e->getResponse()) {
                $exception->setResponse($e->getResponse());
            }

            throw $exception;
        }

        return $this->process($this->getLastResponse(), $this->getLastRequest());
    }

    /**
     * Process the given response and return data
     *
     * @param  \GuzzleHttp\Psr7\Response $response
     * @param  \GuzzleHttp\Psr7\Request  $request
     *
     * @return array
     *
     * @throws \Remluben\CockpitClient\Exceptions\ClientException
     */
    private function process(HttpResponse $response, HttpRequest $request): array
    {
        $result = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200) {
            $message = isset($result['error']) ? $result['error'] :
                "Invalid request. Check request and response details for further information";

            throw ClientException::make(
                $response,
                $request,
                $message
            );
        }

        if (!is_array($result)) {
            throw ClientException::make(
                $response,
                $request,
                "Unexpected response type: could not parse JSON."
            );
        }

        // Check for errors despite 200 status code...
        if (isset($result['error'])) {
            throw ClientException::make(
                $response,
                $request,
                $result['error']
            );
        }

        return $result;
    }

    /**
     * Builds the request URL for given path and optional additional parameters
     *
     * @param  string $path (optional)
     * @param  array  $params (optional)
     *
     * @return string
     */
    private function url(string $path = '', array $params = []): string
    {
        return sprintf('%s%s?%s', $this->url, ltrim($path, '/'), http_build_query($params));
    }

}
