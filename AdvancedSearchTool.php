<?php

require_once(dirname(__FILE__, 4) . '/tools/bootstrap.php');
require_once(__DIR__ . '/vendor/autoload.php');


use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\plugins\generic\advancedSearch\classes\drivers\MeilisearchDriver;
use APP\plugins\generic\advancedSearch\classes\drivers\OpenSearchDriver;
use APP\plugins\generic\advancedSearch\classes\drivers\SearchEngineDriver;
use APP\plugins\generic\advancedSearch\classes\SubmissionData;
use APP\submission\Submission;
use PKP\cliTool\CommandLineTool;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\Dispatcher;
use PKP\core\PKPApplication;
use PKP\plugins\Hook;
use PKP\submission\PKPSubmission;

/**
 * Big List of TODOs
 *
 * - [ ] Work only with specific contexts
 * - [ ] Batch submissions, possibly as jobs (though maybe less important when run from CLI)
 */
class AdvancedSearchTool extends CommandLineTool
{
    public function execute(): void
    {
        Hook::add('Request::getBaseUrl', function ($hookName, $args) {
            $baseUrl =& $args[0];
            $baseUrl = Config::getVar('general', 'base_url');
            return Hook::ABORT;
        });

        try {
            $submissionData = $this->getData();

//            $searchEngineDriver = new MeilisearchDriver();
            $searchEngineDriver = new OpenSearchDriver();
            $searchEngineDriver->addSubmissions($submissionData);

            echo('🎉 Import complete! Processed ' . count($submissionData) . ' submission(s).');
        } catch (\Exception $exception) {
            echo('☹️ ' . $exception->getMessage());
        }

    }

    private function getData(): array
    {
        $dispatcher = $this->getDispatcher();

        // TODO: Get via another way
        /** @var Context $context */
        $context = Services::get('context')->get(1);

        return Repo::submission()->getCollector()
            // TODO: Assume single/primary context for the moment
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
            ->getMany()
            ->map(function (Submission $submission) use ($context, $dispatcher) {
                return (new SubmissionData($submission, $context, $dispatcher))->get();
            })
            ->values()
            ->toArray();
    }
    private function getDispatcher(): Dispatcher
    {
        $request = PKPApplication::get()->getRequest();

        $dispatcher = $request->getDispatcher();
        if ($dispatcher === null) {
            $dispatcher = Application::get()->getDispatcher();
        }

        return $dispatcher;
    }
}

$tool = new AdvancedSearchTool($argv ?? []);
$tool->execute();
