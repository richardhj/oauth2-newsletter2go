# Newsletter2Go Provider for OAuth 2.0 Client

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]]()
[![Dependency Status][ico-dependencies]][link-dependencies]

This package provides Newsletter2Go OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Install

Via Composer

``` bash
$ composer require richardhj/oauth2-newsletter2go
```

## Usage

Use the auth key from your Newsletter2Go account to initiate the provider.

```php
$provider = new Newsletter2Go\OAuth2\Client\Provider\Newsletter2Go([
    'authKey' => $authKey,
]);
```

Then use your login credentials to fetch an AccessToken instance.

```php
$accessToken = $provider->getAccessToken(
    'https://nl2go.com/jwt',
    [
        'username' => $username,
        'password' => $password,
    ]
);
```

### Refreshing a token

Initiate the provider as desribed before. Then:

```php
$accessToken = $provider->getAccessToken(
    'https://nl2go.com/jwt_refresh',
    [
        'refresh_token' => $accessToken->getRefreshToken()
    ]
);
```

It is recommended to save the refresh_token (```$refreshToken = $accessToken->getRefreshToken()```) in your application rather than the username and password. Nevertheless: Handle with care!

Visit [the official API documentation](https://docs.newsletter2go.com/#/Authorization) for reference.

## License

The  GNU Lesser General Public License (LGPL).

[ico-version]: https://img.shields.io/packagist/v/richardhj/oauth2-newsletter2go.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-LGPL-brightgreen.svg?style=flat-square
[ico-dependencies]: https://www.versioneye.com/php/richardhj:oauth2-newsletter2go/badge.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/richardhj/oauth2-newsletter2go
[link-dependencies]: https://www.versioneye.com/php/richardhj:oauth2-newsletter2go
