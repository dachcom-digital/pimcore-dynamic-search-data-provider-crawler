<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Html;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class LanguageExtractor implements FieldTransformerInterface
{
    protected array $options;

    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer): string|array|null
    {
        if (!$resourceContainer->hasResource()) {
            return null;
        }

        /** @var SpiderResource $resource */
        $resource = $resourceContainer->getResource();

        $stream = $resource->getResponse()->getBody();
        $stream->rewind();
        $html = $stream->getContents();

        $contentLanguage = $resource->getResponse()->getHeaderLine('Content-Language');

        $language = strtolower($this->getLanguageFromResponse($contentLanguage, $html));

        return str_replace('_', '-', $language);
    }

    /**
     * Try to find the document's language by first looking for Content-Language in Http headers than in html
     * attribute and last in content-language meta tag
     */
    protected function getLanguageFromResponse(string $contentLanguage, string $body): string
    {
        $l = $contentLanguage;

        if (empty($l)) {
            //try html lang attribute
            $languages = [];
            preg_match_all('@<html[\n|\r\n]*.*?[\n|\r\n]*lang="(?P<language>\S+)"[\n|\r\n]*.*?[\n|\r\n]*>@si', $body, $languages);
            if ($languages['language']) {
                $l = str_replace(['_', '-'], '', $languages['language'][0]);
            }
        }

        if (empty($l)) {
            //try meta tag
            $languages = [];
            preg_match_all('@<meta\shttp-equiv="content-language"\scontent="(?P<language>\S+)"\s\/>@si', $body, $languages);
            if ($languages['language']) {
                $l = str_replace('_', '', $languages['language'][0]);
            }
        }

        return $l;
    }
}
