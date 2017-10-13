<?php

/**
 * This file is part of richardhj/oauth2-newsletter2go.
 *
 * Copyright (c) 2016-2017 Richard Henkenjohann
 *
 * @package   richardhj/oauth2-newsletter2go
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2016-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/oauth2-newsletter2go/blob/master/LICENSE LGPL-3.0
 */

namespace Richardhj\Newsletter2Go\OAuth2\Client\Provider;

use BadFunctionCallException;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;


/**
 * Class Newsletter2Go
 *
 * @package Richadhj\Newsletter2Go\OAuth2\Client\Provider
 */
class Newsletter2Go extends AbstractProvider
{

    /**
     * The endpoint base url
     *
     * @var string
     */
    protected static $endpoint = 'https://api.newsletter2go.com';


    /**
     * Map the grant types used per default with the grant type names used by Newsletter2Go
     *
     * @var array
     */
    protected static $grantMappings = [
        'password'      => 'https://nl2go.com/jwt',
        'refresh_token' => 'https://nl2go.com/jwt_refresh',
    ];


    /**
     * The user's auth key
     *
     * @var string
     */
    protected $authKey;


    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getBaseAuthorizationUrl()
    {
        throw new BadFunctionCallException(
            ' is not supported by the Newsletter2Go OAuth implementation'
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return static::$endpoint.'/oauth/v2/token';
    }


    /**
     * {@inheritdoc}
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return static::$endpoint.sprintf(
            '/users?_filter=%s&_expand=true',
            urlencode(sprintf('account_id=="%s"', $token->getValues()['account_id']))
        );
    }


    /**
     * {@inheritdoc}
     */
    protected function getDefaultScopes()
    {
        return [];
    }


    /**
     * Add authorization header
     *
     * {@inheritdoc}
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


    /**
     * Alter the grant_type as Newsletter2Go has it owns
     *
     * {@inheritdoc}
     */
    public function getAccessToken($grant, array $options = [])
    {
        if (false !== ($grantDefault = array_search($grant, static::$grantMappings))) {

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
     * {@inheritdoc}
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException($data['error'], $response->getStatusCode(), $data);
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner(reset($response['value']), 'id');
    }


    /**
     * {@inheritdoc}
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
    protected function getConfigurableOptions()
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
    protected function getRequiredOptions()
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
     * @throws InvalidArgumentException
     */
    protected function assertRequiredOptions(array $options)
    {
        $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Required options not defined: '.implode(', ', array_keys($missing))
            );
        }
    }
}
