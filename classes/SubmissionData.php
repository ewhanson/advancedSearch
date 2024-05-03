<?php

namespace APP\plugins\generic\advancedSearch\classes;

use APP\author\Author;
use APP\core\Application;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\LazyCollection;
use PKP\context\Context;
use PKP\core\Dispatcher;
use PKP\core\PKPApplication;

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
        $data['abstract'] = strip_tags($publication->getData('abstract', $locale) ?? '');
        $data['url'] = $this->getPublicationUrl($this->submission, $publication);
        $data['datePublished'] = $publication->getData('datePublished');

        /** @var LazyCollection<Author> $authors */
        $authors = $publication->getData('authors');
        $authors->each(function (Author $author) use (&$data, $locale) {
            $data['authors'][] = ['name' => $author->getFullName(), 'affiliation' => $author->getAffiliation($locale)];
        });

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
}
