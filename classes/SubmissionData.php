<?php

namespace APP\plugins\generic\advancedSearch\classes;

use APP\author\Author;
use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\LazyCollection;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\Dispatcher;
use PKP\core\PKPApplication;
use PKP\search\SearchFileParser;

class SubmissionData
{
    public function __construct(
        private readonly Submission $submission,
        private readonly Context    $context,
        private ?Dispatcher         $dispatcher = null,
    )
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->getDispatcher();
        }
    }

    /**
     * Get submission data as an associative array, ready for ingestion.
     *
     * @return array
     * @throws \Exception
     */
    public function get(): array
    {
        // Get submission data and put in format search index is expecting, e.g. associative array
        $publication = $this->submission->getCurrentPublication();
        $locale = $this->submission->getDefaultLocale();

        $data = [];

        $data['id'] = $this->submission->getId();
        $data['title'] = $publication->getLocalizedTitle();
        $data['url'] = $this->getPublicationUrl($this->submission, $publication);
        $data['datePublished'] = $publication->getData('datePublished');

        /** @var LazyCollection<Author> $authors */
        $authors = $publication->getData('authors');
        $authors->each(function (Author $author) use (&$data, $locale) {
            $data['authors'][] = ['name' => $author->getFullName(), 'affiliation' => $author->getAffiliation($locale)];
        });
        $data['doi'] = $publication->getDoi();

        $data['abstract'] = strip_tags($publication->getData('abstract', $locale) ?? '');
        $data['fullText'] = $this->getFullText();

        return $data;
    }

    /**
     * The article URL need to be hand-constructed using the dispatcher
     */
    private function getPublicationUrl(Submission $submission, Publication $publication): string
    {
        return $this->dispatcher->url(
            Application::get()->getRequest(),
            PKPApplication::ROUTE_PAGE,
            $this->context->getPath(),
            'article',
            'view',
            $publication->getData('urlPath') ?? $submission->getId(),
            null,
            null,
            true,
        );
    }

    /**
     * Helper to get dispatcher if not provided
     */
    private function getDispatcher(): Dispatcher
    {
        $request = Application::get()->getRequest();

        $dispatcher = $request->getDispatcher();
        if ($dispatcher === null) {
            $dispatcher = Application::get()->getDispatcher();
        }

        return $dispatcher;
    }

    private function getFullText(): string
    {
        $text = '';
        $galleys = Repo::galley()
            ->getCollector()
            ->filterByPublicationIds([$this->submission->getData('currentPublicationId')])
            ->getMany()
            ->toArray();

        // Give precedence to HTML galleys, as they're quickest to parse
        usort($galleys, function($a, $b) {
            return $a->getFileType() == 'text/html'?-1:1;
        });

        // Provide the full-text.
        $fileService = Services::get('file');
        foreach ($galleys as $galley) {
            $galleyFile = Repo::submissionFile()->get($galley->getData('submissionFileId'));
            if (!$galleyFile) continue;

            $filepath = $fileService->get($galleyFile->getData('fileId'))->path;
            $mimeType = $fileService->fs->mimeType($filepath);
            if ($mimeType == 'text/html') {
                static $purifier;
                if (!$purifier) {
                    $config = \HTMLPurifier_Config::createDefault();
                    $config->set('HTML.Allowed', 'p');
                    $config->set('Cache.SerializerPath', 'cache');
                    $purifier = new \HTMLPurifier($config);
                }
                // Remove non-paragraph content
                $text = $purifier->purify(file_get_contents(Config::getVar('files', 'files_dir') . '/' . $filepath));

                // Remove empty paragraphs
            } else {
                $parser = SearchFileParser::fromFile($galleyFile);
                if ($parser && $parser->open()) {
                    while(($s = $parser->read()) !== false) $text .= $s;
                    $parser->close();
                }
            }
            // Use the first parseable galley.
            if (!empty($text)) break;
        }

        return $text;
    }
}
