<?php
/**
 * recentchanges plugin for Craft CMS 3.x
 *
 * A Widget showing recent changes to entries.
 *
 * @link      https://github.com/rmdev
 * @copyright Copyright (c) 2018 rmdev
 */

namespace vardump\recentchanges\assetbundles\recentchanges;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * RecentchangesAsset AssetBundle
 *
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php)
 * of the class representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle
 * with a view, all its dependent asset bundles will be automatically registered.
 *
 * http://www.yiiframework.com/doc-2.0/guide-structure-assets.html
 *
 * @author    rmdev
 * @package   Recentchanges
 * @since     1.0.0
 */
class RecentchangesAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@vardump/recentchanges/assetbundles/recentchanges/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        parent::init();
    }
}
