# ZfrCors

[![Build Status](https://travis-ci.org/zf-fr/zfr-cors.png?branch=master)](https://travis-ci.org/zf-fr/zfr-cors)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/zf-fr/zfr-cors/badges/quality-score.png?s=47504d5f5a04f88fb40aebbd524d9d241c2ae588)](https://scrutinizer-ci.com/g/zf-fr/zfr-cors/)
[![Coverage Status](https://coveralls.io/repos/zf-fr/zfr-cors/badge.png?branch=master)](https://coveralls.io/r/zf-fr/zfr-cors?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfr/zfr-cors/v/stable.png)](https://packagist.org/packages/zfr/zfr-cors)

ZfrCors is a simple ZF2 module that helps you to deal with Cross-Origin Resource Sharing (CORS).

## What is CORS ?

CORS is a mechanism that allows to perform cross-origin requests from your browser. For instance, let's say that your
website is hosted in the domain `http://example.com`. By default, you won't be allowed to perform AJAX requests to
another domain for security reasons (for instance `http://funny-domain.com`).

With CORS, you can allow your server to serve such requests. For more information, here are very valuable resources:

* [Mozilla documentation about CORS](https://developer.mozilla.org/en-US/docs/HTTP/Access_control_CORS)
* [CORS server flowchart](http://www.html5rocks.com/static/images/cors_server_flowchart.png)

## What is ZfrCors ?

ZfrCors is a Zend Framework 2 module that allow to easily configure your ZF 2 application so that it automatically
builds HTTP responses that follow the CORS documentation.

### Requirements

* Zend Framework 2: >= 2.2

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

### Security concerns

When using CORS requests, you have to distinguish two situations:

* Simple requests (GET, POST) with no custom header: a CORS request is directly made to your server. ZfrCors
will automatically check if the value inside the "Origin" header is allowed by your config. If this is the
case, ZfrRest will set the appropriate response answers, and let your application populate the response
normally. Otherwise, ZfrCors will automatically return a response with a 403 (Unauthorized) status code.
* Complex requests (PUT, DELETE...): your browser will first make a so-called "preflight" request. This is
a simple OPTIONS request that ask your server the permission to make the request. ZfrRest will automatically
create a preflight response and directly returns. Based on this answer, the browser will decide if it has
authorization to perform the real request.

However, please don't use this module for securing your API. You still MUST have a solid security layer in
your application!
