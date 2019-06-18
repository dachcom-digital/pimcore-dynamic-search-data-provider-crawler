<?php

namespace DsWebCrawlerBundle\Transformer\Field\Html;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class LanguageExtractor implements FieldTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function transformData(array $options, string $dispatchTransformerName, DataContainerInterface $transformedData): ?FieldContainerInterface
    {
        if (!$transformedData->hasDataAttribute('resource')) {
            return null;
        }

        /** @var Resource $resource */
        $resource = $transformedData->getDataAttribute('resource');

        $stream = $resource->getResponse()->getBody();
        $stream->rewind();
        $html = $stream->getContents();

        $contentLanguage = $resource->getResponse()->getHeaderLine('Content-Language');

        $language = strtolower($this->getLanguageFromResponse($contentLanguage, $html));
        $language = str_replace('_', '-', $language);

        return new FieldContainer($language);

    }

    /**
     * @param string $contentLanguage
     * @param string $body
     *
     * Try to find the document's language by first looking for Content-Language in Http headers than in html
     * attribute and last in content-language meta tag
     *
     * @return string
     */
    protected function getLanguageFromResponse($contentLanguage, $body)
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