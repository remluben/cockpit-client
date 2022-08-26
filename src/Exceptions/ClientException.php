<?php

/**
 * @author Benjamin Ulmer <ulmer.benjamin@gmail.com>
 * @license MIT
 * @link https://github.com/remluben/cockpit-client/
 * @see https://getcockpit.com
 */

namespace Remluben\CockpitClient\Exceptions;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Basic library exception thrown by API client
 */
class ClientException extends Exception
{
    private $request;
    private $response;

    /**
     * @param  \GuzzleHttp\Psr7\Response $response
     * @param  \GuzzleHttp\Psr7\Request $request
     * @param  string $message
     */
    public static function make(Response $response, Request $request, string $message): ClientException
    {
        return (new static($message))
            ->setRequest($request)
            ->setResponse($response);
    }

    public function __toString()
    {
        return sprintf(
            "%s \nRequest: %s \nResponse: %s \n\n",
            parent::__toString(),
            $this->getRequest()->getRequestTarget(),
            $this->responseToString($this->getResponse())
        );
    }

    /**
     * Returns the HTTP status code of the response returned for the request,
     * that failed and resulted in this exception.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    private function responseToString(Response $response = null): string
    {
        if (!$response) {
            return '';
        }

        $data = [
            'status' => $response->getStatusCode(),
            'body' => $response->getBody(),
        ];

        $data = array_map(
            function($value, $key) {
                return sprintf('%s: %s', $key, $value);
            },
            $data,
            array_keys($data)
        );

        return implode("\n", $data);
    }
}
