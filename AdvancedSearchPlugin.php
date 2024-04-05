<?php

namespace APP\plugins\generic\advancedSearch;

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
