<?php


namespace Kedomingo\OutlookOauth\Service;

use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Gets a fresh token from a refresh token
 */
class Auth {

    private GenericProvider $provider;
    private $tokenDecryptor;
    private $tokenEncryptor;

    /**
     * @param GenericProvider $provider
     * @param string $refreshToken
     */
    public function __construct(GenericProvider $provider, callable $tokenDecryptor, callable $tokenEncryptor)
    {
        $this->provider = $provider;
        $this->tokenEncryptor = $tokenEncryptor;
        $this->tokenDecryptor = $tokenDecryptor;
    }

    public function getToken(): array {

        $oldTokenData = ($this->tokenDecryptor)();

        if (empty($oldTokenData['refresh_token'])) {
            throw new \Exception("Token data has no refresh token!");
        }

        // Still usable
        if ($oldTokenData['expires'] < time()) {
            return $oldTokenData;
        }


        // Get fresh access token using the refresh token
        $newAccessToken = $this->provider->getAccessToken('refresh_token', [
            'refresh_token' => $oldTokenData['refresh_token'],
        ]);

        $tokenPayload = json_encode($newAccessToken->jsonSerialize(), JSON_PRETTY_PRINT);
        ($this->tokenEncryptor)($tokenPayload);

        return json_decode($tokenPayload, true);
    }
}
