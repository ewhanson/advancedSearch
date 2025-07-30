<?php

namespace APP\plugins\generic\advancedSearch\classes\drivers;

use APP\plugins\generic\advancedSearch\classes\drivers\SearchEngineDriver;
use Meilisearch\Client;

class MeilisearchDriver extends SearchEngineDriver
{
    private Client $client;
    public function __construct()
    {
        $this->client = new Client('http://localhost:7700');
    }

    public function addSubmissions(array $submissions): void
    {
        $response = $this->client->index('submissions')->addDocuments($submissions);
    }
}
