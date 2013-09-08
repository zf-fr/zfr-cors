# ZfrCors

[![Build Status](https://travis-ci.org/zf-fr/zfr-cors.png?branch=master)](https://travis-ci.org/zf-fr/zfr-cors)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/zf-fr/zfr-cors/badges/quality-score.png?s=47504d5f5a04f88fb40aebbd524d9d241c2ae588)](https://scrutinizer-ci.com/g/zf-fr/zfr-cors/)
[![Coverage Status](https://coveralls.io/repos/zf-fr/zfr-cors/badge.png?branch=master)](https://coveralls.io/r/zf-fr/zfr-cors?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfr/zfr-cors/v/stable.png)](https://packagist.org/packages/zfr/zfr-cors)

ZfrCors is a simple ZF2 module that helps you to deal with Cross-Origin Resource Sharing (CORS).

## What is ZfrCors ?

ZfrCors is a Zend Framework 2 module that allow to easily configure your ZF 2 application so that it automatically
builds HTTP responses that follow the CORS documentation.

### Requirements

* [Zend Framework 2](https://github.com/zendframework/zf2): >= 2.2

### Roadmap

* Integrate more tightly with the ZF Router so that CORS can be configured per route instead than globally
* ?

### Installation

Install the module by typing (or add it to your `composer.json` file):

`php composer.phar require zfr/zfr-cors`

By default, ZfrCors is configured to deny every CORS requests. To change that, you need to copy-paste
the `zfr_cors.global.php.dist` file to your `autoload` folder (don't forget to remove the .dist part!),
and configure it to suit your needs.

This file has a basic documentation for most options.

## Documentation

### What is CORS ?

CORS is a mechanism that allows to perform cross-origin requests from your browser. For instance, let's say that your
website is hosted in the domain `http://example.com`. By default, it won't be allowed to perform AJAX requests to
another domain for security reasons (for instance `http://funny-domain.com`).

With CORS, you can allow your server to serve such requests. For more information, here are very valuable resources:

* [Mozilla documentation about CORS](https://developer.mozilla.org/en-US/docs/HTTP/Access_control_CORS)
* [CORS server flowchart](http://www.html5rocks.com/static/images/cors_server_flowchart.png)

### Event registration

ZfrCors registers a listener (`ZfrCors\Mvc\CorsRequestListener`) to the `MvcEvent::EVENT_ROUTE` route, with a priority
of -1. This means that this listener is executed AFTER the route has been matched. This will allow us, in the future,
to filter CORS requests by route name.

### Configuring the module

As of now, all the various options are set globally for all routes. In the future, we may add a way to support
configuring CORS per routes. Here are the various options you can set:

* allowed_origins: (array) List of allowed origins. To allow any origin, you can use the wildcard (*) character. If
multiple origins are specified, ZfrCors will automatically check the "Origin" header's value, and only return the
allowed domain (if any) in the "Allow-Access-Control-Origin" response header.
* allowed_methods: (array) List of allowed HTTP methods. Those methods will be returned for the preflight request to
indicate the client which methods are allowed. You can even specify custom HTTP verbs.
* allowed_headers: (array) List of allowed headers that will be returned for the preflight request. This indicate to
the client which headers are permitted to be sent when doing the actual request.
* max_age: (int) Maximum age the preflight request is cached by the browser. This prevents the client from sending
a preflight request for each request.
* exposed_headers: (array) List of response headers that are allowed to be read in the client. Please note that some
browsers do not implement this feature correctly.
* allowed_credentials: (boolean) If true, it allows the browser to send cookies along the request.

### Preflight request

If ZfrCors detects a preflight CORS request, a new response will be created and ZfrCors will send the appropriate
headers according to your configuration. The response will be always sent with a 200 status code (OK). No more
processing in the response can be done once the preflight request has been sent.

### Actual request

When the actual request is made, ZfrCors first check it the origin is allowed. If it is not, a new response is
created with a 403 status code (Unauthorized) so that no useless work is done by the server.

Otherwise, ZfrCors will just add the appropriate headers.

### Security concerns

Don't use this module to secure your application! You must use a proper authorization module (like
[BjyAuthorize](https://github.com/bjyoungblood/BjyAuthorize), [ZfcRbac](https://github.com/ZF-Commons/ZfcRbac) or
[SpiffyAuthorize](https://github.com/spiffyjr/spiffy-authorize).

ZfrCors only allows to accept or refuse a cross-origin request, nothing more!
