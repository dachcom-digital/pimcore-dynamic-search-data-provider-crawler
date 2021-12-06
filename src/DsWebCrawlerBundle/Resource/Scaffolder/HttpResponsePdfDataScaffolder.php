<?php

namespace DsWebCrawlerBundle\Resource\Scaffolder;

use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Resource\ResourceScaffolderInterface;
use Pimcore\Document\Adapter\Ghostscript;
use Pimcore\Model\Asset;
use VDB\Spider\Resource as DataResource;

class HttpResponsePdfDataScaffolder implements ResourceScaffolderInterface
{
    protected ContextDefinitionInterface $contextDefinition;
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function isBaseResource($resource): bool
    {
        return true;
    }

    public function isApplicable($resource): bool
    {
        if (!$resource instanceof DataResource) {
            return false;
        }

        $contentTypeInfo = $resource->getResponse()->getHeaderLine('Content-Type');
        $parts = explode(';', $contentTypeInfo);
        $mimeType = trim($parts[0]);

        if ($mimeType === 'application/pdf') {
            return true;
        }

        return false;
    }

    public function setup(ContextDefinitionInterface $contextDefinition, $resource): array
    {
        $this->contextDefinition = $contextDefinition;

        if (!$resource instanceof DataResource) {
            return [];
        }

        $host = $resource->getUri()->getHost();
        $uri = $resource->getUri()->toString();

        $statusCode = $resource->getResponse()->getStatusCode();

        if ($statusCode !== 200) {
            $this->log('debug', sprintf('skip transform [ %s ] because of wrong status code [ %s ]', $uri, $statusCode));

            return [];
        }

        $pdfData = $this->extractPdfData($resource);

        if ($pdfData === null) {
            return [];
        }

        return [
            'uri'         => $uri,
            'host'        => $host,
            'pdf_content' => $pdfData['pdfContent'],
            'asset_meta'  => $pdfData['assetMeta']
        ];
    }

    protected function extractPdfData(DataResource $resource): ?array
    {
        $assetTmpDir = PIMCORE_SYSTEM_TEMP_DIRECTORY;

        try {
            $pdfToTextBin = Ghostscript::getPdftotextCli();
        } catch (\Exception $e) {
            $pdfToTextBin = false;
        }

        if ($pdfToTextBin === false) {
            $this->log('DEBUG', 'Cannot index PDF Document: no pdf to text converter found');

            return null;
        }

        $textFileTmp = uniqid('t2p-');

        $tmpFile = $assetTmpDir . DIRECTORY_SEPARATOR . $textFileTmp . '.txt';
        $tmpPdfFile = $assetTmpDir . DIRECTORY_SEPARATOR . $textFileTmp . '.pdf';

        $stream = $resource->getResponse()->getBody();
        $stream->rewind();
        $contents = $stream->getContents();

        file_put_contents($tmpPdfFile, $contents);

        $verboseCommand = \Pimcore::inDebugMode() ? '' : '-q ';

        try {
            $cmd = $verboseCommand . $tmpPdfFile . ' ' . $tmpFile;
            exec($pdfToTextBin . ' ' . $cmd);
        } catch (\Exception $e) {
            $this->log('ERROR', $e->getMessage());
        }

        $pdfContent = null;
        if (is_file($tmpFile)) {
            $fileContent = file_get_contents($tmpFile);
            $pdfContent = preg_replace("/\r|\n/", ' ', $fileContent);
            $pdfContent = preg_replace('/[^\p{L}\d ]/u', '', $pdfContent);
            $pdfContent = preg_replace('/\n[\s]*/', "\n", $pdfContent);

            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            if (file_exists($tmpPdfFile)) {
                unlink($tmpPdfFile);
            }
        }

        $assetMeta = $this->getAssetMeta($resource);

        return [
            'pdfContent' => $pdfContent,
            'assetMeta'  => $assetMeta
        ];
    }

    protected function getAssetMeta(DataResource $resource): array
    {
        $link = $resource->getUri()->toString();

        $assetMetaData = [
            'language'     => 'all',
            'country'      => 'all',
            'key'          => false,
            'restrictions' => false,
            'id'           => false
        ];

        if (empty($link) || !is_string($link)) {
            return $assetMetaData;
        }

        $restrictions = false;
        $pathFragments = parse_url($link);
        $assetPath = $pathFragments['path'];

        $asset = Asset::getByPath($assetPath);

        if (!$asset instanceof Asset) {
            return $assetMetaData;
        }

        $assetMetaData['restrictions'] = $restrictions;

        //check for assigned language
        $languageProperty = $asset->getProperty('assigned_language');
        if (!empty($languageProperty)) {
            $assetMetaData['language'] = $languageProperty;
        }

        //checked for assigned country
        $countryProperty = $asset->getProperty('assigned_country');
        if (!empty($countryProperty)) {
            $assetMetaData['country'] = $countryProperty;
        }

        $assetMetaData['key'] = $asset->getKey();
        $assetMetaData['id'] = $asset->getId();

        return $assetMetaData;
    }

    protected function log(string $level, string $message): void
    {
        $contextName = $this->contextDefinition instanceof ContextDefinitionInterface ? $this->contextDefinition->getName() : '--';
        $this->logger->log($level, $message, 'http_response_pdf', $contextName);
    }
}
