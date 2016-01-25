<?php namespace Craft;

class CpTransMissPlugin extends BasePlugin
{

    protected   $_version = '1.0',
                $_schemaVersion = null,
                $_minVersion = '2.5',
                $_pluginName = 'CP Translate Missing Strings',
                $_pluginUrl = 'https://github.com/mmikkel/CpTranslateMissingStrings-Craft',
                $_developer = 'Mats Mikkel Rummelhoff',
                $_developerUrl = 'http://mmikkel.no',
                $_description = 'Translates missing strings inside the CP',
                $_releaseFeedUrl = 'https://raw.githubusercontent.com/mmikkel/CpTranslateMissingStrings-Craft/master/releases.json',
                $_documentationUrl = 'https://github.com/mmikkel/CpTranslateMissingStrings-Craft/blob/master/README.md';

    public function getName()
    {
        return $this->_pluginName;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getSchemaVersion()
    {
        return $this->_schemaVersion;
    }

    public function getDeveloper()
    {
        return $this->_developer;
    }

    public function getDeveloperUrl()
    {
        return $this->_developerUrl;
    }

    public function getPluginUrl()
    {
        return $this->_pluginUrl;
    }

    public function getReleaseFeedUrl()
    {
        return $this->_releaseFeedUrl;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getDocumentationUrl()
    {
        return $this->_documentationUrl;
    }

    public function init()
    {

        parent::init();

        $request = craft()->request;

        if (!$request->isCpRequest() || $request->isAjaxRequest() || craft()->isConsole() || !$this->isCraftRequiredVersion()) {
            return false;
        }

        $this->addResources();

    }

    protected function isCraftRequiredVersion()
    {
        return version_compare(craft()->getVersion(), $this->_minVersion, '>=');
    }

    protected function addResources()
    {

        // Get current language and site translations path
        $language = craft()->language;
        $path = craft()->path->getSiteTranslationsPath();

        // Look for translation file from least to most specific. For example, nl.php gets loaded before nl_nl.php.
        $translationFiles = array();
        $parts = explode('_', $language);
        $totalParts = count($parts);

        // If it's Norwegian Bokmål/Nynorsk, add plain ol' Norwegian as a fallback
        if ($parts[0] === 'nb' || $parts[0] === 'nn')
        {
            $translationFiles[] = 'no';
        }

        for ($i = 1; $i <= $totalParts; $i++)
        {
            $translationFiles[] = implode('_', array_slice($parts, 0, $i));
        }

        // Get translations
        $translations = array();

        if (IOHelper::folderExists($path)) {

            foreach ($translationFiles as $file) {

                $path = $path.$file.'.php';

                if (IOHelper::fileExists($path))
                {

                    $temp = include($path);

                    if (is_array($temp))
                    {
                        // If this is framework data and we're not on en_us, then do some special processing.
                        if (strpos($path, 'framework/i18n/data') !== false && $file !== 'en_us')
                        {
                            $temp = $this->_processFrameworkData($file);
                        }

                        $translations = array_merge($translations, $temp);

                    }
                }
            }
        }

        if (empty($translations)) {
            return false;
        }

        craft()->templates->includeJs('(function(window){
            if (window.Craft) {
                Craft.translations = $.extend(Craft.translations, '.json_encode($translations).');
                var selectors = [
                        "#page-title h1",                   // Page titles
                        "#crumbs a",                        // Segments in breadcrumbs
                        ".fld-field > span",                // Field names inside FLD
                        ".fld-tab .tab > span",             // Tab names inside FLD
                        "#sidebar .heading > span",         // CEI heading
                        "#Assets option",                   // Options inside Asset field settings
                    ],
                    $el;
                $(selectors.join(",")).each(function () {
                    $el = $(this);
                    $el.text(Craft.t($el.text()));
                });
            }
        }(window));');

    }

}
