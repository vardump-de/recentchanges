<?php
/**
 * recentchanges plugin for Craft CMS 4.x
 *
 * A Widget showing recent changes to entries.
 *
 * @link      https://github.com/rmdev
 * @copyright Copyright (c) 2018-2022 rmdev
 */

namespace vardump\recentchanges\widgets;

use Craft;
use craft\base\Widget;
use craft\elements\Entry;
use craft\models\Section;
use vardump\recentchanges\assetbundles\recentchanges\RecentchangesAsset;

/**
 * recentchanges Widget
 *
 * Dashboard widgets allow you to display information in the Admin CP Dashboard.
 * Adding new types of widgets to the dashboard couldn’t be easier in Craft
 *
 * https://craftcms.com/docs/plugins/widgets
 *
 * @author    rmdev
 * @package   Recentchanges
 * @since     1.0.0
 */
class RecentchangesWidget extends Widget
{

    // Public Properties
    // =========================================================================

    /**
     * @var string|int[] The section IDs that the widget should pull entries from
     */
    public string $section = '*';

    /**
     * string The site ID that the widget should pull entries from
     */
    public ?int $siteId = null;

    /**
     * int The total number of entries that the widget should show
     */
    public int $limit = 10;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('recentchanges', 'RecentchangesWidget');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@vardump/recentchanges/assetbundles/recentchanges/dist/img/RecentchangesWidget-icon.svg');
    }

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    public static function maxColspan(): ?int
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->siteId === null) {
            $this->siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['siteId', 'limit'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * Returns the component’s settings HTML.
     * @return string|null
     */
    public function getSettingsHtml(): null|string
    {
        return Craft::$app->getView()->renderTemplate(
            'recentchanges/_components/widgets/RecentchangesWidget_settings',
            [
                'widget' => $this
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        if (is_numeric($this->section)) {
            $section = Craft::$app->getSections()->getSectionById($this->section);

            if ($section) {
                $title = Craft::t('recentchanges', 'Recent Changes | {section}', [
                    'section' => Craft::t('site', $section->name)
                ]);
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (!isset($title)) {
            $title = Craft::t('recentchanges', 'Recent Changes');
        }

        // See if they are pulling entries from a different site
        $targetSiteId = $this->_getTargetSiteId();

        if ($targetSiteId !== false && $targetSiteId != Craft::$app->getSites()->getCurrentSite()->id) {
            $site = Craft::$app->getSites()->getSiteById($targetSiteId);

            if ($site) {
                $title = Craft::t('app', '{title} ({site})', [
                    'title' => $title,
                    'site' => Craft::t('site', $site->name),
                ]);
            }
        }

        return $title;
    }

    /**
     * Returns the widget's body HTML.
     *
     * @return string|false The widget’s body HTML, or `false` if the widget
     *                      should not be visible. (If you don’t want the widget
     *                      to be selectable in the first place, use {@link isSelectable()}.)
     */
    public function getBodyHtml(): null|string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(RecentchangesAsset::class);
        $entries = $this->_getEntries();

        return $view->renderTemplate('recentchanges/_components/widgets/RecentchangesWidget_body',
            [
                'entries' => $entries
            ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     *
     * @return array
     */
    private function _getEntries(): array
    {
        $targetSiteId = $this->_getTargetSiteId();

        if ($targetSiteId === false) {
            // Hopeless
            return [];
        }

        // Normalize the target section ID value.
        $editableSectionIds = $this->_getEditableSectionIds();
        $targetSectionId = $this->section;

        if (!$targetSectionId || $targetSectionId === '*' || !in_array($targetSectionId, $editableSectionIds, false)) {
            $targetSectionId = array_merge($editableSectionIds);
        }

        if (!$targetSectionId) {
            return [];
        }

        $query = Entry::find();
        $query->status(null);
        $query->siteId($targetSiteId);
        $query->sectionId($targetSectionId);
        $query->editable(true);
        $query->limit($this->limit ?: 100);
        $query->orderBy('elements.dateUpdated desc');

        return $query->all();
    }

    /**
     * Returns the Channel and Structure section IDs that the user is allowed to edit.
     *
     * @return array
     */
    private function _getEditableSectionIds(): array
    {
        $sectionIds = [];

        foreach (Craft::$app->getSections()->getEditableSections() as $section) {
            if ($section->type != Section::TYPE_SINGLE) {
                $sectionIds[] = $section->id;
            }
        }

        return $sectionIds;
    }

    /**
     * Returns the target site ID for the widget.
     */
    private function _getTargetSiteId(): int|false
    {
        if (!Craft::$app->getIsMultiSite()) {
            return $this->siteId;
        }

        // Make sure that the user is actually allowed to edit entries in the current site. Otherwise grab entries in
        // their first editable site.

        // Figure out which sites the user is actually allowed to edit
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // If they aren't allowed to edit *any* sites, return false
        if (empty($editableSiteIds)) {
            return false;
        }

        // Figure out which site was selected in the settings
        $targetSiteId = $this->siteId;

        // Only use that site if it still exists and they're allowed to edit it.
        // Otherwise go with the first site that they are allowed to edit.
        if (!in_array($targetSiteId, $editableSiteIds, false)) {
            $targetSiteId = $editableSiteIds[0];
        }

        return $targetSiteId;
    }
}
