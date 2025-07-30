<?php

namespace APP\plugins\generic\advancedSearch\classes\drivers;

use APP\plugins\generic\advancedSearch\classes\drivers\SearchEngineDriver;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;

class OpenSearchDriver extends SearchEngineDriver
{
    private Client $client;
    public function __construct()
    {
        $this->client = (new ClientBuilder())
            ->setHosts(['http://localhost:9200'])
            ->setSSLVerification(false)
            ->build();
    }

    public function addSubmissions(array $submissions): void
    {
        foreach ($submissions as $submission) {
            try {
                $results = $this->client->index([
                    'index' => 'submissions',
                    'id' => $submission['id'],
                    'body' => $submission
                ]);
            } catch (\Exception $exception) {
                dump($exception);
            }
        }
    }
}
