<?php
/**
 * Plugin Name: Outlook SMTP OAuth2
 * Description: Authenticate with Outlook 365 and send SMTP emails via OAuth2.
 * Version: 1.0.0
 */


use Kedomingo\OutlookOauth\Admin\Admin;
use Kedomingo\OutlookOauth\Config\MainConfig;
use Kedomingo\OutlookOauth\Service\Callback;
use Kedomingo\OutlookOauth\Service\PHPMailerConfigurator;

if (!defined('ABSPATH')) {
    exit;
}

// Autoload dependencies
require_once __DIR__ . '/vendor/autoload.php';


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('outlook365_mailer_get_di_container')) {
    function outlook365_mailer_get_di_container()
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->addDefinitions(MainConfig::getConfig());
        $containerBuilder->useAutowiring(true);

        return $containerBuilder->build();
    }
}

$container = outlook365_mailer_get_di_container();


// Register admin menu
add_action('admin_menu', function () use ($container) {
    $menuParams = $container->get('plugin.info');
    $admin = $container->get(Admin::class);
    $menuParams['callback'] = [$admin, 'renderSettings'];
    call_user_func_array('add_menu_page', array_values($menuParams));
});


// Handle callback
add_action($container->get('plugin.save_action_handler'), function () use ($container) {
    $handler = $container->get(Callback::class);
    $handler->saveToken($_GET['code'] ?? '');

    $pluginUrl = $container->get('plugin.url');
    wp_safe_redirect($pluginUrl);
    exit;
});

add_action('phpmailer_init', function (PHPMailer\PHPMailer\PHPMailer $phpmailer) use ($container) {
    /**
     * @var PHPMailerConfigurator $mailerConfigurator
     */
    error_log('phpmailer_init was called');
    try {
        $mailerConfigurator = $container->get(PHPMailerConfigurator::class);
        $mailerConfigurator->configure($phpmailer);
        error_log("PHPMailer init success");
    } catch (\Throwable $e) {
        error_log("phpmailer_init error: " . $e->getMessage());
    }
});

add_filter('wp_mail_from', function () use ($container) {
    $credentialProvider = $container->get('decryptor');
    $credentials = ($credentialProvider)();

    return $credentials['username'] ?? '';
});

add_filter('wp_mail_from_name', function () use ($container) {
    $credentialProvider = $container->get('decryptor');
    $credentials = ($credentialProvider)();

    return $credentials['alias'] ?? '';
});
