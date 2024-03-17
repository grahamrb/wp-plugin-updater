# WP Plugin Updater

This is a super-simple wordpress plugin hosting solution that enables Wordpress to update the plugin automatically. All you need is some php hosting.

## Installation

1. place the update.php file to your hosting somewhere (e.g. https://www.example.com/myplugin/update.php)
2. add any plugin versions you have in the format `pluginname.1.2.3.zip` or `pluginname_1.2.3.zip` (where `pluginname` is the name of your plugin and `1.2.3` is the version number)
3. create a config.ini file with two entries `base_url` which refers to the location where the update.php file is on your server (e.g `base_url=https://www.example.com/myplugin/`) and `plugin_url` which is the information page for your plugin (e.g. `plugin_url=https://www.example.com/myplugin/info`)
4. add the filter below to your plugin, be sure to update the `$update_info_url` variable to either point to your base url with update.php (for low volume plugins) or your base url with update.json.


```php
add_filter('pre_set_site_transient_update_plugins', function ($transient) {

    if (empty($transient->checked)) {
        return $transient;
    }

    $update_info_url = 'https://plugins.example.com/update.php'; //make sure you update this

    $response = wp_remote_get($update_info_url);

    if (is_wp_error($response)) {
        // fail quietly for now
        
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && version_compare($transient->checked[$data['plugin']], $data['new_version'], '<')) {
            $transient->response[$data['plugin']] = (object) [
                'id'          => $data['id'],
                'slug'        => $data['slug'],
                'plugin'      => $data['plugin'],
                'new_version' => $data['new_version'],
                'url'         => $data['url'],
                'package'     => $data['package'],
                'icons'         => array(),
                'banners'       => array(),
                'banners_rtl'   => array(),
                'tested'        => '',
                'requires_php'  => '',
                'compatibility' => new stdClass(),
                //'compatibility' => new \stdClass(), //swap to this if you're in a namespace
            ];

        } else {
            // based on info from: https://make.wordpress.org/core/2020/07/30/recommended-usage-of-the-updates-api-to-support-the-auto-updates-ui-for-plugins-and-themes-in-wordpress-5-5/
            // No update is available.
            $item = (object) array(
                'id'            => $data['plugin'],
                'slug'          => $data['slug'],
                'plugin'        => $data['plugin'],
                'new_version'   => $data['new_version'],
                'url'           => '',
                'package'       => '',
                'icons'         => array(),
                'banners'       => array(),
                'banners_rtl'   => array(),
                'tested'        => '',
                'requires_php'  => '',
                'compatibility' => new \stdClass(),
            );
            // Adding the "mock" item to the `no_update` property is required
            // for the enable/disable auto-updates links to correctly appear in UI.
            $transient->no_update[$data['plugin']] = $item;

        }
    }

    return $transient;
});
```

## tips and tricks

- If your plugin has a large install base then checking for the latest plugin version every time the URL is hit is not good for performance. Instead you can point the filter to `update.json` and set up a cron on your web site to run `php update.php` at a regular interval (e.g. hourly) which will re-generate the update.json with the latest version.
- This updater is specifically designed to look at the file name when determining the version. This allows you to place a different version number in the actual zip file content. This is beneficial when you are developing as you can name the file as 1.0.1 with the actual content as 1.0.0 which will mean Wordpress will always display an update being available.
