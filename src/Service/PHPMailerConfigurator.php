<?php

namespace Kedomingo\OutlookOauth\Service;

use League\OAuth2\Client\Provider\GenericProvider;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use Exception;

class PHPMailerConfigurator {

    private GenericProvider $provider;
    private Auth $auth;
    private $credentialProvider;

    /**
     * @param GenericProvider $provider
     * @param Auth $auth
     */
    public function __construct(GenericProvider $provider, Auth $auth, callable $credentialProvider)
    {
        $this->provider = $provider;
        $this->auth = $auth;
        $this->credentialProvider = $credentialProvider;
    }

    /**
     * IMPORTANT: THE Originating email address must be the user authenticated to generate the auth token
     *
     * @throws Exception
     */
    public function configure(PHPMailer $mail): void
    {
        $token = $this->auth->getToken();
        $credentials = ($this->credentialProvider)();

        // Set up PHPMailer
        $mail->isSMTP();
        $mail->Host       = 'smtp.office365.com';
        $mail->Port       = 587;
        $mail->SMTPSecure = 'STARTTLS';
        $mail->SMTPAuth   = true;
        $mail->AuthType   = 'XOAUTH2';

        $mail->setOAuth(
            new OAuth(
                [
                    'provider'     => $this->provider,
                    'clientId'     => $credentials['client_id'] ?? '',
                    'clientSecret' => $credentials['client_secret'] ?? '',
                    'refreshToken' => $token['refresh_token'],
                    'userName'     => $credentials['username'],
                ]
            )
        );

        $mail->setFrom($credentials['username'], $credentials['alias']);
    }
}
