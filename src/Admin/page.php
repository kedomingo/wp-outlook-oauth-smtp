<?php

use Kedomingo\OutlookOauth\Admin\Admin;

?>
<div class="wrap">
    <h1>Outlook SMTP OAuth2</h1>


    <h2>Credentials</h2>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="notice notice-error"><p> <?php echo esc_attr($_SESSION['error']) ?></div>
    <?php
    endif ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="notice notice-success"><p> <?php echo esc_attr($_SESSION['success']) ?></div>
    <?php
    endif ?>

    <form method="post">

        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wp_nonce_field(Admin::SAVE_ACTION)
        ?>
        <input type="hidden" name="form_id" value="save"/>
        <table class="form-table">
            <tr>
                <th scope="row">Username (email address). Important! This must be the email you will use during the auth
                    flow
                </th>
                <td><input type="text" name="username" value="<?php echo esc_attr($credentials['username'] ?? '') ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row">Alias (The name from which the emails will be sent)</th>
                <td><input type="text" name="alias" value="<?php echo esc_attr($credentials['alias'] ?? '') ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row">Tenant ID</th>
                <td><input type="text" name="tenant_id" value="<?php echo esc_attr($credentials['tenant_id'] ?? '') ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row">Client ID</th>
                <td><input type="text" name="client_id" value="<?php echo esc_attr($credentials['client_id'] ?? '') ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row">Client Secret (Do not save with empty secret, otherwise it will overwrite your current secret)</th>
                <td><input type="text" name="client_secret" value="" class="regular-text"></td>
            </tr>
        </table>

        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo get_submit_button('Save Credentials')
        ?>
    </form>

    <hr>
    <h2>Authorization</h2>

    <?php if (!empty($tokenFile) && file_exists($tokenFile)): ?>
        <p><strong>Token exists.</strong> You're authorized.</p><br />

        <?php if (!empty($authUrl)): ?>
            <a href='<?php echo esc_attr($authUrl) ?>' class='button button-primary'>Re-authenticate</a>
        <?php endif; ?>

    <?php else: ?>
        <?php if (!empty($authUrl) && !empty($credentials['client_id']) && !empty($credentials['client_secret'])): ?>
            <a href='<?php echo esc_attr($authUrl) ?>' class='button button-primary'>Begin Auth Flow</a>
        <?php else: ?>
            <p>Please save valid credentials first.</p>
        <?php endif; ?>
    <?php endif; ?>

    <hr>
    <h2>Test</h2>

    <?php
    if ($_SESSION['test_error']): ?>
        <div class="notice notice-error"><p> <?php echo esc_attr($_SESSION['test_error']) ?></div>
    <?php
    endif ?>
    <?php
    if ($_SESSION['test_success']): ?>
        <div class="notice notice-success"><p> <?php echo esc_attr($_SESSION['test_success']) ?></div>
    <?php
    endif ?>

    <form method="post">
        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wp_nonce_field(Admin::TEST_ACTION)
        ?>
        <input type="hidden" name="form_id" value="test"/>
        <table class="form-table">
            <tr>
                <th scope="row">From email</th>
                <td><?php echo esc_attr($credentials['username'] ?? '') ?></td>
            </tr>
            <tr>
                <th scope="row">From name</th>
                <td><?php echo esc_attr($credentials['alias'] ?? '') ?></td>
            </tr>
            <tr>
                <th scope="row">To address</th>
                <td><input type="text" name="to_address" class="regular-text"></td>
            </tr>
        </table>

        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo get_submit_button('Test')
        ?>
    </form>


    <hr/>
    <h2>Instructions: How to get client_id, client_secret, and tenant_id</h2>

    <h3>🔹 Step 1: Go to App registrations</h3>

    Search for App registrations, click from the result then click on New Registration

    <h3>🔹 Step 2: Fill in App Registration Info</h3>

    Name: SMTP OAuth App (or anything meaningful) <br/>

    Supported account types: <br/>
    Choose: <br/>

    ✅ "Accounts in this organizational directory only" (for a single tenant)

    Redirect URI: <br/>
    Under Redirect URI (optional), select Web and enter: <br/>

    <strong><?php echo esc_attr($callbackUrl ?? '') ?></strong>

    <br/>
    ✅ Click Register

    <h3>🔹 Step 3: Save App Credentials</h3>

    Once registered, copy: <br/>

    <strong>Application (client) ID</strong> <br/>
    <strong>Directory (tenant) ID</strong>


    <h3>🔹 Step 4: Create a Client Secret</h3>

    In the new app's menu, go to Certificates & secrets <br/>

    Under Client secrets, click “+ New client secret” <br/>

    Add a description (e.g., SMTP Token) <br/>

    Choose an expiry (12 months is fine) <br/>

    Click Add <br/>

    Copy the value immediately — you won't see it again! <br/>


    <h3>🔹 Step 5: Enable SMTP.Send Permission</h3>

    Go to API permissions <br/>

    Click “+ Add a permission” <br/>

    Select Microsoft Graph <br/>

    Choose Delegated permissions <br/>

    In the search box, search for: <br/>

    SMTP.Send <br/>

    Check the box for SMTP.Send <br/>

    Click Add permissions <br/>

    🔔 Don’t click Application permissions — SMTP.Send is only available as Delegated.
    <h3>🔹 Step 6: Grant Admin Consent (if needed)</h3>

    If you're an admin: <br/>

    Click “Grant admin consent for &lt;your org&gt;” <br/>

    Confirm. <br/>
</div>
