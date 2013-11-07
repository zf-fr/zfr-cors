# 1.0.2

- Properly set "Access-Control-Allow-Credentials" for normal requests if credentials are allowed. [#13](https://github.com/zf-fr/zfr-cors/pull/13)

# 1.0.1

- ZfrCors previously needed you to add the host URI in the allowed origins array. This was obviously wrong, so
now if your app is hosted on "example.com", you don't need to add "example.com" as your allowed origins, as it should
be automatically allowed.

# 1.0.0

- Initial release
