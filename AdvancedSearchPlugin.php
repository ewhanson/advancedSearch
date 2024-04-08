<?php

namespace APP\plugins\generic\advancedSearch;

require_once(__DIR__ . '/vendor/autoload.php');

use PKP\plugins\GenericPlugin;

class AdvancedSearchPlugin extends GenericPlugin
{

    /**
     * @inheritDoc
     */
    public function getDisplayName()
    {
        return __('plugins.generic.advancedSearch.displayName');
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return __('plugins.generic.advancedSearch.description');
    }
}
