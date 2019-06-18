# CHANGELOG

## 2.0.1 - YYYY-MM-DD

### Added

- Nothing.

### Changed

- Nothing. 

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.0 - 2019-06-18

### Added

- [#59](https://github.com/zf-fr/zfr-cors/pull/59) Added support for php 7.1, 7.2 and 7.3 versions

### Changed

- [#54](https://github.com/zf-fr/zfr-cors/pull/54) [BC Break] Changed `\ZfrCors\Mvc\CorsRequestListener` event `MvcEvent::EVENT_ROUTE` priority with has slight chance to cause BC Break. 

### Deprecated

- Nothing.

### Removed

- [#59](https://github.com/zf-fr/zfr-cors/pull/59) Removed support for hhvm, php 5.6 and 7.0 versions

### Fixed

- [#53](https://github.com/zf-fr/zfr-cors/pull/53), [#54](https://github.com/zf-fr/zfr-cors/pull/54) Method Routes preflight
- [#55](https://github.com/zf-fr/zfr-cors/pull/55) Fixed `README.md` documentation for route-based configurations
- [#56](https://github.com/zf-fr/zfr-cors/pull/56) Fixed issue with `zendframework/zend-http` v2.8
- [#58](https://github.com/zf-fr/zfr-cors/pull/58), [#57](https://github.com/zf-fr/zfr-cors/pull/57) The second step of the CORS request need router params

# 1.5.0

- You may now configure rules per-route within zend-mvc route configuration.
  When detected, these will override any rules that were general to the
  application. See the ["Configuring the Module" section of the
  README](README.md#configuring-the-module) for full details.

# 1.4.1

- ZfrCors now properly disallows `Access-Control-Allow-Origin: *` when the
  credentials flag is true. [#35]
- The `CorsRequestListener` now no longer raises an exception when triggered
  during `EVENT_FINISH` if the `Origin` header is invalid, and instead just
  returns early. That condition is already found during pre-flight, which allows
  ignoring it when returning the response. [#47]

# 1.4.0

- ZfrCors will now return a 400 error if an invalid `Origin` value is sent.

# 1.3.1

- Add compatibility with Zend Component Installer

# 1.3.0

- Provides compatibility with ZF3 components (especially ServiceManager v3 and EventManager v3) [#37]

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
