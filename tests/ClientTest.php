<?php

/**
 * @author Benjamin Ulmer <ulmer.benjamin@gmail.com>
 * @license MIT
 * @link https://github.com/remluben/cockpit-client/
 * @see https://getcockpit.com
 */

namespace Remluben\CockpitClient\Tests;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Remluben\CockpitClient\Client;
use Remluben\CockpitClient\Exceptions\ClientException;
use Remluben\CockpitClient\Exceptions\InvalidArgumentException;

class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_null_for_last_request_if_no_request_was_sent()
    {
        $client = $this->client(new MockHandler([
            $this->response(),
        ]));

        $this->assertNull(
            $client->getLastRequest(),
            'The last request is `null` if no request was sent.'
        );
    }

    /**
     * @test
     */
    public function it_returns_the_last_request_if_at_least_one_request_was_sent()
    {
        $client = $this->client(new MockHandler([
            $this->response(),
        ]));

        $client->settings();

        $this->assertIsObject(
            $client->getLastRequest(),
            'The last request is available if at least one request was sent.'
        );
    }

    /**
     * @test
     */
    public function it_returns_null_for_last_response_if_no_request_was_sent()
    {
        $client = $this->client(new MockHandler([
            $this->response(),
        ]));

        $this->assertNull(
            $client->getLastResponse(),
            'The last response is `null` if no request was sent.'
        );
    }

    /**
     * @test
     */
    public function it_returns_the_last_response_if_at_least_one_request_was_sent()
    {
        $client = $this->client(new MockHandler([
            $this->response(),
        ]));

        $client->settings();

        $this->assertIsObject(
            $client->getLastResponse(),
            'The last request is available if at least one request was sent.'
        );
    }

    /**
     * @test
     */
    public function it_throws_its_own_exception_instead_of_passing_the_guzzle_client_exception()
    {
        $client = $this->client(new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'Error Communicating with server.',
                new Request('GET', 'ping-pong')
            ),
        ]));

        $this->expectException(ClientException::class);

        $client->settings();
    }

    /**
     * @test
     */
    public function it_throws_an_exception_containing_request_and_response()
    {
        $client = $this->client(new MockHandler([
            $this->response(500),
        ]));

        $exception = null;

        try {
            $client->settings();
        }
        catch (ClientException $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(ClientException::class, $exception);

        $this->assertNotNull($exception->getRequest());

        $this->assertNotNull($exception->getResponse());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_invalid_method_params()
    {
        $client = $this->client(new MockHandler([
            $this->response(200),
        ]));

        $this->expectException(InvalidArgumentException::class);

        $client->contentItems(' ');

        $this->expectException(InvalidArgumentException::class);

        $client->contentItems(null);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_reading_data_for_an_unavailable_content_model()
    {
        $client = $this->client(new MockHandler([
            $this->response(404, '{"error":"Model <unknown-model> not found"}'),
        ]));

        $this->expectException(ClientException::class);

        $client->contentItems('unknown-model');
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_reading_content_items_using_invalid_params()
    {
        $client = $this->client(new MockHandler([
            $this->response(200),
        ]));

        $this->expectException(InvalidArgumentException::class);

        $client->contentItems('faq', [
            'unknown-param' => 10
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_missing_data_when_reading_content_items()
    {
        $client = $this->client(new MockHandler([
            $this->response(200, ''),
        ]));


        $this->expectException(ClientException::class);

        $client->contentItems('faq');
    }


    /**
     * @test
     */
    public function it_returns_an_array_of_items_when_requesting_a_valid_content_model_using_all_params()
    {
        $client = $this->client(new MockHandler([
            $this->response(200),
        ]));

        $results = $client->contentItems('faq', [
            'locale' => 'de',
            'filter' => '',
            'sort' => '',
            'fields' => '',
            'limit' => 10,
            'skip' => 5,
            'populate' => 0
        ]);

        $this->assertIsArray(
            $results,
    'Reading the API returns an array for an available content model using all URL params.'
        );

        $this->assertNotEmpty(
            $results,
            'Reading the API returns a non-empty array for an available content model using all URL params.'
        );
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_items_when_requesting_for_a_valid_content_model()
    {
        $client = $this->client(new MockHandler([
            $this->response(200, '[{"id":1},{"id":2},{"id":3}]'),
        ]));

        $response = $client->contentItems('faq');

        $this->assertIsArray(
            $response,
            'Reading the API returns an array for an available content model.'
        );

        $this->assertNotEmpty(
            $response,
            'Reading the API returns a non-empty array for an available content model with items.'
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_array_of_items_when_requesting_for_a_valid_but_empty_content_model()
    {
        $client = $this->client(new MockHandler([
            $this->response(200, '[]'),
        ]));

        $response = $client->contentItems('faq');

        $this->assertIsArray(
            $response,
            'Reading the API returns an array for an available but empty content model.'
        );

        $this->assertEmpty(
            $response,
            'Reading the API returns an empty array for an available but empty content model.'
        );
    }

    /**
     * @test
     */
    public function it_refreshes_the_last_request_foreach_request_made()
    {
        $client = $this->client(new MockHandler([
            $this->response(200, '{"data":[{"request":"first"}]}'),
            $this->response(200, '{"data":[{"request":"second"}]}'),
        ]));

        $client->contentItems('one');
        $first = $client->getLastRequest();

        $client->contentItems('two');
        $second = $client->getLastRequest();

        $this->assertNotEquals(
            $first->getRequestTarget(),
            $second->getRequestTarget(),
            'The first request contains a different request target than the second.',
        );
    }

     /**
      * @test
      */
     public function it_refreshes_the_last_response_foreach_request_made()
     {
         $client = $this->client(new MockHandler([
             $this->response(200, '{"data":[{"request":"first"}]}'),
             $this->response(200, '{"data":[{"request":"second"}]}')
         ]));

         $client->contentItems('one');
         $first = $client->getLastResponse();

         $client->contentItems('two');
         $second = $client->getLastResponse();

         $this->assertNotEquals(
             $first->getBody()->getContents(),
             $second->getBody()->getContents(),
             'The first response contains different data than the second.',
         );
     }

    //
    // helper methods
    //

    /**
     * @see  https://docs.guzzlephp.org/en/stable/testing.html
     *
     * @param  \GuzzleHttp\Handler\MockHandler $mock
     * @param  string                          $url
     * @param  string                          $token
     *
     * @return \Remluben\CockpitClient\Client
     */
    private function client(MockHandler $mock, ?string $url = null, ?string $token = null): Client
    {

        return new Client(
            new GuzzleHttpClient([
                'handler' => HandlerStack::create($mock)
            ]),
            $url ?? 'https://www.remluben.at',
            $token ?? 'an-invalid-token'
        );

    }

    private function response(int $code = 200, string $data = null, array $headers = []): Response
    {
        if ($data === null) {
            $data = '{"foo":{"bar":"baz"}}';
        }

        return new Response($code, $headers, $data);
    }
}
