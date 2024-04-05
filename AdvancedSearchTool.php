<?php

require(dirname(__FILE__, 4) . '/tools/bootstrap.php');

use APP\facades\Repo;
use PKP\cliTool\CommandLineTool;

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
            });

        // TODO: Should array key be submission ID?

        // For this naive implementation, assuming add any submission to an empty index

        echo('Ta-da! 🎉');
    }
}

$tool = new AdvancedSearchTool($argv ?? []);
$tool->execute();
