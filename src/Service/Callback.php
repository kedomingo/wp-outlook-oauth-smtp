<?php

namespace Kedomingo\OutlookOauth\Service;

use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;


/**
 * Saves the access token from a fresh auth flow
 */
class Callback
{
    private GenericProvider $provider;
    private $tokenEncryptor;

    /**
     * @param GenericProvider $provider
     */
    public function __construct(GenericProvider $provider, callable $tokenEncryptor)
    {
        $this->provider = $provider;
        $this->tokenEncryptor = $tokenEncryptor;
    }

    /**
     * @throws GuzzleException
     * @throws IdentityProviderException
     */
    public function saveToken(string $authCode): void
    {
        $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $authCode,
        ]);
        ($this->tokenEncryptor)(json_encode($accessToken->jsonSerialize(), JSON_PRETTY_PRINT));

        $_SESSION['success'] = 'oauth credentials saved';
    }
}
