<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Html;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextExtractor implements FieldTransformerInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'content_start_indicator'         => '<!-- main-content -->',
            'content_end_indicator'           => '<!-- /main-content -->',
            'content_exclude_start_indicator' => null,
            'content_exclude_end_indicator'   => null,
        ];

        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));

        $resolver->setAllowedTypes('content_start_indicator', ['string']);
        $resolver->setAllowedTypes('content_end_indicator', ['string']);
        $resolver->setAllowedTypes('content_exclude_start_indicator', ['null', 'string']);
        $resolver->setAllowedTypes('content_exclude_end_indicator', ['null', 'string']);
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer)
    {
        if (!$resourceContainer->hasAttribute('html')) {
            return null;
        }

        if (!$resourceContainer->hasResource()) {
            return null;
        }

        $html = $resourceContainer->getAttribute('html');

        $content = $this->extract($this->options, $html);
        $content = $this->cleanHtml($content);

        return $content;

    }

    /**
     * @param array  $options
     * @param string $html
     *
     * @return string
     */
    protected function extract(array $options, $html)
    {
        $documentHasDelimiter = false;
        $documentHasExcludeDelimiter = false;

        $searchStartIndicator = $options['content_start_indicator'];
        $searchEndIndicator = $options['content_end_indicator'];
        $searchExcludeStartIndicator = $options['content_exclude_start_indicator'];
        $searchExcludeEndIndicator = $options['content_exclude_end_indicator'];

        //now limit to search content area if indicators are set and found in this document
        if (!empty($searchStartIndicator)) {
            $documentHasDelimiter = strpos($html, $searchStartIndicator) !== false;
        }

        //remove content between exclude indicators
        if (!empty($searchExcludeStartIndicator)) {
            $documentHasExcludeDelimiter = strpos($html, $searchExcludeStartIndicator) !== false;
        }

        if ($documentHasDelimiter && !empty($searchStartIndicator) && !empty($searchEndIndicator)) {

            preg_match_all(
                '%' . $searchStartIndicator . '(.*?)' . $searchEndIndicator . '%si',
                $html,
                $htmlSnippets
            );

            $html = '';
            if (is_array($htmlSnippets[1])) {
                foreach ($htmlSnippets[1] as $snippet) {
                    if ($documentHasExcludeDelimiter
                        && !empty($searchExcludeStartIndicator)
                        && !empty($searchExcludeEndIndicator)) {
                        $snippet = preg_replace(
                            '#(' . preg_quote($searchExcludeStartIndicator) . ')(.*?)(' . preg_quote($searchExcludeEndIndicator) . ')#si',
                            ' ',
                            $snippet
                        );
                    }

                    $html .= ' ' . $snippet;
                }
            }
        }

        return $html;
    }

    /**
     * @param string $html
     *
     * @return string|string[]|null
     */
    protected function cleanHtml($html)
    {
        $text = preg_replace([
            '@(<script[^>]*?>.*?</script>)@si', // Strip out javascript
            '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments including CDATA
        ], '', $html);

        $text = strip_tags($text);
        $text = preg_replace('@[ \t\n\r\f]+@', ' ', $text);

        return $text;
    }
}