# Wordpress Plugin for connecting wp_mail to Outlook365 using Oauth flow

Outlook has deprecated the client_id/client_secret basic auth for SMTP and requires Oauth.

This Wordpress plugin will allow you to fetch an Oauth token from Microsoft and use that token for
authenticating and re-authenticating when sending emails using `wp_mail`

## Credentials

There are 3 main things required:
1. Tenant ID
2. Application ID (Client ID)
3. Client Secret

To get these, you must have Admin access to Azure Portal and Outlook 

### 🔹 Step 1: Go to App registrations

Search for App registrations, click from the result then click on New Registration

### 🔹 Step 2: Fill in App Registration Info

- Name: SMTP OAuth App (or anything meaningful)
- Supported account types: Choose: ✅ "Accounts in this organizational directory only" (for a single tenant)
- Redirect URI: Under Redirect URI, select Web and enter  `<YOUR_WP_SITE>/wp-admin/admin-ajax.php?action=outlook_oauth_callback`

✅ Click Register

### 🔹 Step 3: Save App Credentials

Once registered, copy the following

* Directory ID (This is your Tenant ID)
* Application ID (This is your Client ID)


### 🔹 Step 4: Create a Client Secret

In the new app's menu, go to Certificates & secrets 

Under Client secrets, click “+ New client secret” 

Add a description (e.g., SMTP Token) 

Choose an expiry (12 months is fine) 

Click Add 

Copy the value immediately — you won't see it again! 


### 🔹 Step 5: Enable SMTP.Send Permission

Go to API permissions 

Click “+ Add a permission” 

Select Microsoft Graph 

Choose Delegated permissions 

In the search box, search for: 

`SMTP.Send`

Check the box for `SMTP.Send` 

Click Add permissions 

🔔 Don’t click Application permissions — SMTP.Send is only available as Delegated.
### 🔹 Step 6: Grant Admin Consent (if needed)

If you're an admin: 

Click "Grant admin consent for `<YOUR_ORG>`" 

Confirm. 



## Admin Panel

Once you have the credentials required, go to the admin panel and fill out the form and click `Save Credentials`.

<img src="https://raw.githubusercontent.com/kedomingo/wp-outlook-oauth-smtp/refs/heads/main/asset/img1.png" />

After saving the credentials, click on `Begin Auth Flow` and log in to Microsoft using the email address you will use to send. This part is
important because the token you will use for sending emails MUST match the `from` email address - the `username` you put in the credentials form.

If the auth flow was successful, you should see the message `Token exists. You're authorized` instead of the `Begin Auth Flow` button.

<img src="https://raw.githubusercontent.com/kedomingo/wp-outlook-oauth-smtp/refs/heads/main/asset/img2.png" />

You may test sending an email using the form for testing.

## Tokens

The credentials and tokens are stored as encrypted payloads in PHP files (`credentials.json.enc.php` and `token.json.enc.php`), so they won't be visible when fetched.
Important: They use your site's `AUTH_KEY` for encryption and decryption. Make sure your site's `AUTH_KEY` is different from Wordpress' defaults.

The initial token fetched from the `Begin Auth Flow` is saved and refreshed when expired, overwriting old `token.json.enc.php` with a fresh one.
