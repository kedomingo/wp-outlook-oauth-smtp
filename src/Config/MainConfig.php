<?php

namespace Kedomingo\OutlookOauth\Config;

use Kedomingo\OutlookOauth\Admin\Admin;
use Kedomingo\OutlookOauth\Service\Auth;
use Kedomingo\OutlookOauth\Service\Callback;
use Kedomingo\OutlookOauth\Service\PHPMailerConfigurator;
use League\OAuth2\Client\Provider\GenericProvider;

use function DI\autowire;
use function DI\get;
use Psr\Container\ContainerInterface;

class MainConfig
{
    const OUTLOOK_OAUTH_PLUGIN_SLUG = 'outlook-oauth-settings';
    const OUTLOOK_OAUTH_CREDENTIAL_FILE = __DIR__ . '/credentials.json.enc.php';
    const OUTLOOK_OAUTH_TOKEN_FILE = __DIR__ . '/token.json.enc.php';


    public static function getConfig()
    {
        return [
            'plugin.info' => [
                'page_title' => 'Outlook SMTP OAuth2',
                'menu_title' => 'Outlook SMTP OAuth2',
                'capability' => 'manage_options',
                'menu_slug' => self::OUTLOOK_OAUTH_PLUGIN_SLUG,
            ],
            'plugin.url' => admin_url('admin.php?page='.self::OUTLOOK_OAUTH_PLUGIN_SLUG),
            'plugin.save_action_url' => admin_url('admin-ajax.php?action=outlook_oauth_callback'),
            'plugin.save_action_handler' => 'wp_ajax_outlook_oauth_callback',
            'plugin.token_file' => self::OUTLOOK_OAUTH_TOKEN_FILE,
            'decryptor' => function (ContainerInterface $c) {
                return function () {
                    if (file_exists(self::OUTLOOK_OAUTH_CREDENTIAL_FILE)) {
                        require_once self::OUTLOOK_OAUTH_CREDENTIAL_FILE;
                    }
                    if (defined('OAUTH_CREDENTIALS')) {
                        $decrypted = self::outlook_oauth_decrypt(OAUTH_CREDENTIALS);
                        return json_decode($decrypted, true);
                    }
                    return [];
                };
            },
            'encryptor' => function (ContainerInterface $c) {
                return function (string $username, string $alias, string $clientId, string $clientSecret, string $tenantId) {
                    $credentials = [
                        'username' => sanitize_text_field($username),
                        'alias' => sanitize_text_field($alias),
                        'client_id' => sanitize_text_field($clientId),
                        'client_secret' => sanitize_text_field($clientSecret),
                        'tenant_id' => sanitize_text_field($tenantId),
                    ];
                    $json = json_encode($credentials);
                    $encrypted = self::outlook_oauth_encrypt($json);
                    file_put_contents(self::OUTLOOK_OAUTH_CREDENTIAL_FILE, "<?php define('OAUTH_CREDENTIALS', '$encrypted');");
                };
            },
            'token.encryptor' => function (ContainerInterface $c) {
                return function (string $payload) {
                    $encrypted = self::outlook_oauth_encrypt($payload);
                    file_put_contents(self::OUTLOOK_OAUTH_TOKEN_FILE, "<?php define('OAUTH_TOKEN', '$encrypted');");
                };
            },
            'token.decryptor' => function (ContainerInterface $c) {
                return function () {
                    if (file_exists(self::OUTLOOK_OAUTH_TOKEN_FILE)) {
                        require_once self::OUTLOOK_OAUTH_TOKEN_FILE;
                    }
                    if (defined('OAUTH_TOKEN')) {
                        $decrypted = self::outlook_oauth_decrypt(OAUTH_TOKEN);
                        return json_decode($decrypted, true);
                    }
                    return [];
                };
            },
            Auth::class => autowire()
                ->constructorParameter('tokenDecryptor', get('token.decryptor'))
                ->constructorParameter('tokenEncryptor', get('token.encryptor')),
            Callback::class => autowire()
                ->constructorParameter('tokenEncryptor', get('token.encryptor')),
            PHPMailerConfigurator::class => autowire()
                ->constructorParameter('credentialProvider', get('decryptor')),
            Admin::class => autowire()
                ->constructorParameter('decryptor', get('decryptor'))
                ->constructorParameter('encryptor', get('encryptor'))
                ->constructorParameter('tokenFile', get('plugin.token_file'))
                ->constructorParameter('callbackUrl', get('plugin.save_action_url')),
            GenericProvider::class => function (ContainerInterface $c) {
                $decryptor = $c->get('decryptor');
                $credentials = ($decryptor)();

                return new GenericProvider([
                    'clientId' => $credentials['client_id'] ?? '',
                    'clientSecret' => $credentials['client_secret'],
                    'redirectUri' => 'https://ips-cambodia.com/outlook_callback.php', // If your client is setup with a different callback, then change this
                    'urlAuthorize' => 'https://login.microsoftonline.com/' . ($credentials['tenant_id'] ?? '') . '/oauth2/v2.0/authorize',
                    'urlAccessToken' => 'https://login.microsoftonline.com/' . ($credentials['tenant_id'] ?? '') . '/oauth2/v2.0/token',
                    'urlResourceOwnerDetails' => '',
                    'scopes' => ['https://outlook.office365.com/SMTP.Send offline_access']
                ]);
            },
        ];
    }

    static function outlook_oauth_encrypt(string $data): string
    {
        $key = hash('sha256', AUTH_KEY, true);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    static function outlook_oauth_decrypt(string $encrypted): string
    {
        $key = hash('sha256', AUTH_KEY, true);
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        return openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
}
