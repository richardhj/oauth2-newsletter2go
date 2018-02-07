<?php

/**
 * This file is part of richardhj/oauth2-newsletter2go.
 *
 * Copyright (c) 2016-2018 Richard Henkenjohann
 *
 * @package   richardhj/oauth2-newsletter2go
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2016-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/oauth2-newsletter2go/blob/master/LICENSE LGPL-3.0
 */

namespace Richardhj\Newsletter2Go\OAuth2\Client\Provider;

use BadFunctionCallException;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;


/**
 * Class Newsletter2Go
 *
 * @package Richadhj\Newsletter2Go\OAuth2\Client\Provider
 */
final class Newsletter2Go extends AbstractProvider
{

    /**
     * The endpoint base url
     *
     * @var string
     */
    private static $endpoint = 'https://api.newsletter2go.com';

    /**
     * Map the grant types used per default with the grant type names used by Newsletter2Go
     *
     * @var array
     */
    private static $grantMappings = [
        'password'      => 'https://nl2go.com/jwt',
        'refresh_token' => 'https://nl2go.com/jwt_refresh',
    ];

    /**
     * The user's auth key
     *
     * @var string
     */
    private $authKey;

    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options       An array of options to set on this provider.
     *                             Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *                             Individual providers may introduce more options, as needed.
     *
     * @param array $collaborators An array of collaborators that may be used to
     *                             override this provider's default behavior. Collaborators include
     *                             `grantFactory`, `requestFactory`, and `httpClient`.
     *                             Individual providers may introduce more collaborators, as needed.
     *
     * @throws \InvalidArgumentException If required options are not provided.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->assertRequiredOptions($options);

        $possible   = $this->getConfigurableOptions();
        $configured = array_intersect_key($options, array_flip($possible));

        foreach ($configured as $key => $value) {
            $this->$key = $value;
        }

        // Remove all options that are only used locally
        $options = array_diff_key($options, $configured);

        parent::__construct($options, $collaborators);
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     *
     * @throws \BadFunctionCallException Dead method due of Newsletter2Go API.
     */
    public function getBaseAuthorizationUrl()
    {
        throw new BadFunctionCallException(
            __METHOD__.' is not supported by the Newsletter2Go OAuth implementation.'
        );
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return static::$endpoint.'/oauth/v2/token';
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return static::$endpoint.sprintf(
                '/users?_filter=%s&_expand=true',
                urlencode(sprintf('account_id=="%s"', $token->getValues()['account_id']))
            );
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Builds request options used for requesting an access token.
     *
     * @param  array $params
     *
     * @return array
     */
    protected function getAccessTokenOptions(array $params)
    {
        $options = parent::getAccessTokenOptions($params);

        $options = array_merge_recursive(
            $options,
            [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($this->authKey),
                ],
            ]
        );

        return $options;
    }

    /** @noinspection PhpDocRedundantThrowsInspection
     *
     * Requests an access token using a specified grant and option set.
     *
     * Alter the grant_type as Newsletter2Go has it owns.
     *
     * @param  mixed $grant
     * @param  array $options
     *
     * @return AccessToken
     *
     * @throws IdentityProviderException
     */
    public function getAccessToken($grant, array $options = [])
    {
        if (false !== ($grantDefault = array_search($grant, static::$grantMappings, true))) {

            $options = array_merge(
                $options,
                [
                    'grant_type' => $grant,
                ]
            );

            $grant = $grantDefault;
        }

        return parent::getAccessToken($grant, $options);
    }

    /**
     * Checks a provider response for errors.
     *
     * @param  ResponseInterface $response
     * @param  array|string      $data Parsed response data
     *
     * @return void
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException($data['error'], $response->getStatusCode(), $data);
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array       $response
     * @param  AccessToken $token
     *
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner(reset($response['value']), 'id');
    }

    /**
     * Returns the authorization headers used by this provider.
     *
     * Typically this is "Bearer" or "MAC". For more information see:
     * http://tools.ietf.org/html/rfc6749#section-7.1
     *
     * No default is provided, providers must overload this method to activate
     * authorization headers.
     *
     * @param  mixed|null $token Either a string or an access token instance
     *
     * @return array
     */
    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => 'Bearer '.(($token instanceof AccessToken) ? $token->getToken() : $token),
        ];
    }

    /**
     * Returns all options that can be configured
     *
     * @return array
     */
    private function getConfigurableOptions()
    {
        return array_merge(
            $this->getRequiredOptions(),
            []
        );
    }

    /**
     * Returns all options that are required
     *
     * @return array
     */
    private function getRequiredOptions()
    {
        return [
            'authKey',
        ];
    }

    /**
     * Verifies that all required options have been passed
     *
     * @param  array $options
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function assertRequiredOptions(array $options)
    {
        $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Required options not defined: '.implode(', ', array_keys($missing))
            );
        }
    }
}
