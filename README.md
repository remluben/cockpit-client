# Cockpit Client

> **NOTE:**
>
> This library can be used with the new [Cockpit - Content Platform](https://github.com/Cockpit-HQ/Cockpit), a brand-new version of the Cockpit headless api-first CMS.
> 
> As for this basic version the API client is readonly. This is likely going to change in future versions.

A PHP API client for a very simple straightforward readonly access to the cockpit headless CMS data.

## Installation

You can install the package via composer:

```shell
composer require remluben/cockpit-client
```

That's it. No more steps required in first place.

## Usage

This section provides usage information. We refer to an instance of the `\Remluben\CockpitClient\Client` class as *client*.

### Basics

At first ensure to setup your *client* with sensible Guzzle settings to avoid timeouts and bad performance. Next you can call any method the client provides you with to fetch data from your Cockpit installation.

```php
// Set up your basic HTTP client, which makes the HTTP requests to Cockpit under
// the hood.
$http = new GuzzleHttp\Client([
    GuzzleHttp\RequestOptions::TIMEOUT => 2.0, // set an appropriate timeout that suits your application and server needs
    GuzzleHttp\RequestOptions::DEBUG => false, // optionally enable in development mode
]);

// Create an instance of the Cockpit client by passing all required data
$client = new Remluben\CockpitClient\Client(
    $http,                                          // The HTTP client
    'https://url-to-cockpit.tld/api/',              // The URL to the API of your Cockpit instance, i.e. https://url-to-cockpit.tld/api/
    'API-1a45a0876a88fb3f042cc6524059a4a11bf3f163', // a static token / api-key for server-side usage, should not expire
);

$results = [];
// Fetch your first content items, i.e. for content model *faqs*
try {
  $results = $client->contentItems('faqs');
}
catch (\Remluben\CockpitClient\Exceptions\ClientException $e) {
  // A client exception happens whenever 
  // - the HTTP client itself rises an exception
  // - the Cockpit API returns non 2xx status codes or runs into issues
}
catch (\Remluben\CockpitClient\Exceptions\InvalidArgumentException $e) {
  // For unintended method calls, invalid parameters or similar problems the
  // client usually throws this exception 
}

// process your results, if any...
foreach ($results as $item) {
  // do something here...
}
```

### Error handling

Handling errors should be quite strait forward. Whenever something unexpected happens, a bad HTTP status code or an error is returned via API response the client reacts by throwing an Exception. 

In other words, this means: only for HTTP status code *200* with a valid JSON response the request is considered as successful.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

This software is released under the [MIT license](LICENSE.md).
