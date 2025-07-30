<?php

namespace APP\plugins\generic\advancedSearch\classes\drivers;

use Meilisearch\Client;

abstract class SearchEngineDriver
{
    abstract public function addSubmissions(array $submissions): void;
}
