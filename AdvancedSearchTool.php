<?php

require_once(dirname(__FILE__, 4) . '/tools/bootstrap.php');
require_once(__DIR__ . '/vendor/autoload.php');


use APP\facades\Repo;
use APP\plugins\generic\advancedSearch\classes\SearchEngineDriver;
use PKP\cliTool\CommandLineTool;
use PKP\core\PKPApplication;

class AdvancedSearchTool extends CommandLineTool
{
    public function execute(): void
    {

        $submissionData = Repo::submission()->getCollector()
            // Assume single/primary context for the moment
            ->filterByContextIds([1])
            ->filterByStatus([\PKP\submission\PKPSubmission::STATUS_PUBLISHED])
            ->getMany()
            ->map(function (\APP\submission\Submission $submission) {
                // Get submission data and put in format search index is expecting, e.g. associative array
                $publication = $submission->getCurrentPublication();
                $locale = $publication->getDefaultLocale();

                $data = [];

                $data['id'] = $submission->getId();
                $data['title'] = $publication->getLocalizedTitle();
                $data['abstract'] = $publication->getData('abstract', $locale);
                // TODO: URL, Authors, full text?

               return $data;
            })
            ->values()
            ->toArray();

        $searchEngineDriver = new SearchEngineDriver();
        $searchEngineDriver->addSubmissions($submissionData);


        // TODO: Should array key be submission ID?


        // For this naive implementation, assuming add any submission to an empty index

        echo('Ta-da! 🎉');
    }
}

$tool = new AdvancedSearchTool($argv ?? []);
$tool->execute();
