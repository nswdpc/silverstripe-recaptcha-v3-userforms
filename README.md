# Silverstripe captcha field for userforms

This module provides userforms captcha fields with support for Google's reCAPTCHAv3 and Cloudflare's Turnstile services.

![settings](./docs/settings.png)

## Configuration

All site configuration happens in the [nswdpc/silverstripe-recaptcha-v3](https://github.com/nswdpc/silverstripe-recaptcha-v3) module.

After configuration, editors with relevant permissions can create a field in the CMS and set a score (if supported) or custom action for analytics.

## Requirements

+ [nswdpc/silverstripe-recaptcha-v3](https://github.com/nswdpc/silverstripe-recaptcha-v3)
+ [silverstripe/userforms](https://github.com/silverstripe/silverstripe-userforms)

See [composer.json](./composer.json) for details

## Installation

```
composer require nswdpc/silverstripe-recaptcha-v3-userforms
```

## License

[BSD-3-Clause](./LICENSE.md)

## Documentation

* [Documentation](./docs/en/001_index.md)

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
