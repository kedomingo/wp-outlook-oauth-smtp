<?php

namespace Kedomingo\OutlookOauth\Admin;

use League\OAuth2\Client\Provider\GenericProvider;
use Throwable;

class Admin
{
    public const SAVE_ACTION = 'outlook_oauth_save_settings';
    public const TEST_ACTION = 'outlook_oauth_test';
    private GenericProvider $provider;
    private $encryptor;
    private $decryptor;
    private string $tokenFile;
    private string $callbackUrl;

    public function __construct(
        GenericProvider $provider,
        callable $encryptor,
        callable $decryptor,
        string $tokenFile,
        string $callbackUrl
    ) {
        $this->provider = $provider;
        $this->encryptor = $encryptor;
        $this->decryptor = $decryptor;
        $this->tokenFile = $tokenFile;
        $this->callbackUrl = $callbackUrl;
    }

    public function renderSettings(): void
    {

        $credentials = ['client_id' => '', 'client_secret' => '', 'tenant_id' => '', 'username' => ''];

        $this->handleCredentialsChange();
        $this->handleTest();

        // Load saved credentials
        try {
            $credentials = ($this->decryptor)();
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Failed to decrypt credentials: ' . esc_html($e->getMessage());
        }

        // Required by the page
        $tokenFile = $this->tokenFile;
        $callbackUrl = $this->callbackUrl;
        $authUrl = $this->provider->getAuthorizationUrl();

        require_once __DIR__ .'/page.php';

        $_SESSION['error'] = '';
        $_SESSION['success'] = '';
        $_SESSION['test_error'] = '';
        $_SESSION['test_success'] = '';
    }

    private function handleCredentialsChange(): void
    {
        $formId = $_POST['form_id'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formId === 'save' && check_admin_referer(self::SAVE_ACTION)) {
            try {
                ($this->encryptor)(
                    $_POST['username'] ?? '',
                    $_POST['alias'] ?? '',
                    $_POST['client_id'] ?? '',
                    $_POST['client_secret'] ?? '',
                    $_POST['tenant_id'] ?? '',
                );
                $_SESSION['success'] = 'Credentials saved successfully.';
            } catch (Throwable $e) {
                $_SESSION['error'] = 'Failed to encrypt/save credentials: ' . esc_html($e->getMessage());
            }
            wp_safe_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    private function handleTest(): void
    {
        $formId = $_POST['form_id'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formId === 'test' && check_admin_referer(self::TEST_ACTION)) {
            try {
                wp_mail($_POST['to_address'], 'Test Subject', 'Test Body');
                $_SESSION['test_success'] = 'Test sent successfully!';
            } catch (\Throwable $e) {
                $_SESSION['test_error'] = 'Test failed! ' . $e->getMessage() . '<br />' . str_replace(
                        $e->getTraceAsString(),
                        "\n",
                        '<br />'
                    );
            }
            wp_safe_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
