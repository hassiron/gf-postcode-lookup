<?php

class Postcode_Lookup_Updater {
    private $slug;
    private $pluginData;
    private $pluginFile;
    private $username;
    private $repo;
    private $latestRelease;
    private $accessToken;

    public function __construct($file, $username, $project, $accessToken = '') {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'setTransient']);
        add_filter('plugins_api', [$this, 'setPluginInfo'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'afterInstall'], 10, 3);

        $this->pluginFile = $file;
        $this->username = $username;
        $this->repo = $project;
        $this->accessToken = $accessToken;
    }

    private function setPluginData() {
        $this->slug = plugin_basename($this->pluginFile);
        $this->pluginData = get_plugin_data($this->pluginFile);
    }

    public function setPluginInfo($false, $action, $response) {
        $this->setPluginData();
        $this->getRepositoryRelease();

        if (empty($response->slug) || $response->slug != $this->slug) {
            return false;
        }

        $response->last_updated = $this->latestRelease->published_at;
        $response->slug = $this->slug;
        $response->plugin_name = $this->pluginData['Name'];
        $response->version = $this->latestRelease->tag_name;
        $response->author = $this->pluginData['AuthorName'];
        $response->homepage = $this->pluginData['PluginURI'];

        $link = $this->latestRelease->zipball_url;

        if (!empty($this->accessToken)) {
            $link = add_query_arg([
                'access_token' => $this->accessToken
            ], $link);
        }

        $response->download_link = $link;
        $response->sections = [
            'description' => $this->pluginData['Description'],
            'changelog' => Parsedown::instance()->parse($this->latestRelease->body)
        ];

        $response->requires = $this->getLatestReleaseVersionRequired();
        $response->tested = $this->getLatestReleaseVersionTested();

        return $response;
    }

    private static function getLatestReleaseVersionRequired() {
        $version = null;

        preg_match("/requires:\s([\d\.]+)/i", $this->latestRelease->body, $version);

        if (!empty($version) && is_array($version) && count($version) > 0) {
            $version = $version[1];
        }

        return $version;
    }

    private static function getLatestReleaseVersionTested() {
        $version = null;

        preg_match("/tested:\s([\d\.]+)/i", $this->latestRelease->body, $version);

        if (!empty($version) && is_array($version) && count($version) > 0) {
            $version = $version[1];
        }

        return $version;
    }

    private function getRepositoryRelease() {
        if (!$this->latestRelease) {
            $url = sprintf('https://api.github.com/repos/%s/%s/releases', $this->username, $this->repo);

            if (!empty($this->accessToken)) {
                $url = add_query_arg([
                    'access_token' => $this->accessToken
                ], $url);
            }

            $response = wp_remote_retrieve_body(wp_remote_get($url));

            if (!empty($response)) {
                $releases = json_decode($response);

                if (!empty($releases)) {
                    $this->latestRelease = $response[0];
                }
            }
        }
    }

    public function setTransient($transient) {
        if (!empty($transient->checked)) {
            $this->setPluginData();
            $this->getRepositoryRelease();

            $update = version_compare($this->latestRelease->tag_name, $transient->checked[$this->slug]);

            if ($update === 1) {
                $package = $this->latestRelease->zipball_url;

                if (!empty($this->accessToken)) {
                    $package = add_query_arg([
                        'access_token' => $this->accessToken
                    ], $package);

                    $obj = new stdClass();
                    $obj->slug = $this->slug;
                    $obj->new_version = $this->latestRelease->tag_name;
                    $obj->url = $this->pluginData['PluginURI'];
                    $obj->package = $package;

                    $transient->response[$this->slug] = $obj;
                }
            }
        }

        return $transient;
    }

    public function afterInstall($true, $hook, $result) {
        global $wp_filesystem;

        $this->setPluginData();

        $activated = is_plugin_active($this->slug);
        $pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->slug);
        $wp_filesystem->move($result['destination'], $pluginFolder);
        $result['destination'] = $pluginFolder;

        if ($activated) {
            $activate = activate_plugin($this->slug);
        }

        return $result;
    }
}
