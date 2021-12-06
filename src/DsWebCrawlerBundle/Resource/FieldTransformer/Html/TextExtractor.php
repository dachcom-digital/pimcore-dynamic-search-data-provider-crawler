<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Html;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextExtractor implements FieldTransformerInterface
{
    protected array $options;

    public function configureOptions(OptionsResolver $resolver): void
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

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer): ?string
    {
        if (!$resourceContainer->hasAttribute('html')) {
            return null;
        }

        if (!$resourceContainer->hasResource()) {
            return null;
        }

        $html = $resourceContainer->getAttribute('html');

        $content = $this->extract($this->options, $html);

        return $this->cleanHtml($content);
    }

    protected function extract(array $options, string $html): string
    {
        $documentHasDelimiter = false;
        $documentHasExcludeDelimiter = false;

        $searchStartIndicator = $options['content_start_indicator'];
        $searchEndIndicator = $options['content_end_indicator'];
        $searchExcludeStartIndicator = $options['content_exclude_start_indicator'];
        $searchExcludeEndIndicator = $options['content_exclude_end_indicator'];

        //now limit to search content area if indicators are set and found in this document
        if (!empty($searchStartIndicator)) {
            $documentHasDelimiter = str_contains($html, $searchStartIndicator);
        }

        //remove content between exclude indicators
        if (!empty($searchExcludeStartIndicator)) {
            $documentHasExcludeDelimiter = str_contains($html, $searchExcludeStartIndicator);
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

    protected function cleanHtml(string $html): string|array|null
    {
        $text = preg_replace([
            '@(<script[^>]*?>.*?</script>)@si', // Strip out javascript
            '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments including CDATA
        ], '', $html);

        $text = strip_tags($text);

        return preg_replace('@[ \t\n\r\f]+@', ' ', $text);
    }
}
