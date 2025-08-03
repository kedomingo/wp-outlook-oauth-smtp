<?php

namespace Kedomingo\OutlookOauth\Admin;

use League\OAuth2\Client\Provider\GenericProvider;
use Throwable;

class Admin
{

    private const SAVE_ACTION = 'outlook_oauth_save_settings';
    private const TEST_ACTION = 'outlook_oauth_test';
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
        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        $credentials = ['client_id' => '', 'client_secret' => '', 'tenant_id' => '', 'username' => ''];

        $this->handleCredentialsChange();
        $this->handleTest();

        // Load saved credentials
        try {
            $credentials = ($this->decryptor)();
        } catch (Throwable $e) {
            $error = 'Failed to decrypt credentials: ' . esc_html($e->getMessage());
        }

        $s = <<<EOF
        <div class="wrap">
            <h1>Outlook SMTP OAuth2</h1>
            
            
            <h2>Credentials</h2>
            %s %s
            <form method="post">
                
                %s 
                <input type="hidden" name="form_id" value="save" />
                <table class="form-table">
                    <tr>
                        <th scope="row">Username (email address). Important! This must be the email you will use during the auth flow</th>
                        <td><input type="text" name="username" value="%s" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Alias (The name from which the emails will be sent)</th>
                        <td><input type="text" name="alias" value="%s" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Tenant ID</th>
                        <td><input type="text" name="tenant_id" value="%s" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Client ID</th>
                        <td><input type="text" name="client_id" value="%s" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td><input type="text" name="client_secret" placeholder="%s" class="regular-text"></td>
                    </tr>
                </table>
                
                %s
            </form>

            <hr>
            <h2>Authorization</h2>
            %s
            
            <hr>
            <h2>Test</h2>
            %s
            
            <hr />
            <h2>Instructions: How to get client_id, client_secret, and tenant_id</h2>
            %s
        </div>
EOF;

        $authUrl = $this->provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->provider->getState();

        $button = $authUrl
            ? "<a href='$authUrl' class='button button-primary'>Begin Auth Flow</a>"
            : '<p>Please save valid credentials first.</p>';

        $reauth = $authUrl
            ? "<a href='$authUrl' class='button button-primary'>Re-authenticate</a>"
            : '';

        echo sprintf(
            $s,
            $error ? '<div class="notice notice-error"><p>' . $error . '</div>' : '',
            $success ? '<div class="notice notice-success"><p>' . $success . '</div>' : '',
            wp_nonce_field(self::SAVE_ACTION),
            esc_attr($credentials['username']),
            esc_attr($credentials['alias']),
            esc_attr($credentials['tenant_id']),
            esc_attr($credentials['client_id']),
            !empty($credentials['client_secret']) ? '*****' : '', // esc_attr($credentials['client_secret']),
            get_submit_button('Save Credentials'),
            file_exists(
                $this->tokenFile
            ) ? '<p><strong>Token exists.</strong> You\'re authorized.</p><br />' . $reauth : $button,
            $this->test(esc_attr($credentials['username']), esc_attr($credentials['alias'])),
            $this->instructions($this->callbackUrl)
        );

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

    private function test(string $username, string $alias): string
    {
        $error = $_SESSION['test_error'] ?? '';
        $success = $_SESSION['test_success'] ?? '';

        $s = <<<EOF
            %s %s
            <form method="post">
                %s 
                <input type="hidden" name="form_id" value="test" />
                <table class="form-table">
                    <tr>
                        <th scope="row">From email</th>
                        <td>%s</td>
                    </tr>
                    <tr>
                        <th scope="row">From name</th>
                        <td>%s</td>
                    </tr>
                    <tr>
                        <th scope="row">To address</th>
                        <td><input type="text" name="to_address" class="regular-text"></td>
                    </tr>
                </table>
                
                %s
            </form>
        
EOF;

        return sprintf(
            $s,
            $error ? '<div class="notice notice-error"><p>' . $error . '</div>' : '',
            $success ? '<div class="notice notice-success"><p>' . $success . '</div>' : '',
            wp_nonce_field(self::TEST_ACTION),
            $username,
            $alias,
            get_submit_button('Test')
        );
    }

    private function instructions(string $callbackUrl): string
    {
        $s = <<<EOF
             <h3>🔹 Step 1: Go to App registrations</h3>
            
            Search for App registrations, click from the result then click on New Registration
            
            <h3>🔹 Step 2: Fill in App Registration Info</h3>
            
                Name: SMTP OAuth App (or anything meaningful) <br />
            
                Supported account types: <br />
                Choose: <br />
            
                    ✅ "Accounts in this organizational directory only" (for a single tenant)
            
                Redirect URI: <br />
                Under Redirect URI (optional), select Web and enter: <br />
            
                <strong>%s</strong>
            
                <br />
                ✅ Click Register
            
            <h3>🔹 Step 3: Save App Credentials</h3>
            
            Once registered, copy: <br />
            
                <strong>Application (client) ID</strong> <br />
                <strong>Directory (tenant) ID</strong>
            
            
            <h3>🔹 Step 4: Create a Client Secret</h3>
            
                In the new app's menu, go to Certificates & secrets <br />
            
                Under Client secrets, click “+ New client secret” <br />
            
                    Add a description (e.g., SMTP Token) <br />
            
                    Choose an expiry (12 months is fine) <br />
            
                Click Add <br />
            
                Copy the value immediately — you won't see it again! <br />
            
            
            <h3>🔹 Step 5: Enable SMTP.Send Permission</h3>
            
                Go to API permissions <br />
            
                Click “+ Add a permission” <br />
            
                Select Microsoft Graph <br />
            
                Choose Delegated permissions <br />
            
                In the search box, search for: <br />
            
                SMTP.Send <br />
            
                Check the box for SMTP.Send <br />
            
                Click Add permissions <br />
            
            🔔 Don’t click Application permissions — SMTP.Send is only available as Delegated.
            <h3>🔹 Step 6: Grant Admin Consent (if needed)</h3>
            
            If you're an admin: <br />
            
                Click “Grant admin consent for <your org>” <br />
            
                Confirm. <br />

EOF;
        return sprintf($s, $callbackUrl);
    }
}
