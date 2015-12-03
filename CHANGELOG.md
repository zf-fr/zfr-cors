# 1.2.1

- Ensure that the vary header is set when no origin is set [#31]

# 1.2.0

- You can now use the wildcard character for allowing domains. You can now use "https://*.example.com" rather
that manually specifying all subdomains.

# 1.1.2

- ZfrCors now properly detects a CORS request if the scheme is different.

# 1.1.1

- ZfrCors now properly detects a CORS request if the port is different.

# 1.1.0

- Segregate preflight vs. inflight CORS requests. Preflight detection continues
  to happen during the "route" event. However, inflight requests are detected
  now during the "finish" event in order to ensure they operate on the same
  response object as will be sent back to the client.
  ([#16](https://github.com/zf-fr/zfr-cors/pull/16))

# 1.0.2

- Properly set "Access-Control-Allow-Credentials" for normal requests if credentials are allowed ([#13](https://github.com/zf-fr/zfr-cors/pull/13)).

# 1.0.1

- ZfrCors previously needed you to add the host URI in the allowed origins array. This was obviously wrong, so
now if your app is hosted on "example.com", you don't need to add "example.com" as your allowed origins, as it should
be automatically allowed.

# 1.0.0

- Initial release
