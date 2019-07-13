<?php

namespace DsWebCrawlerBundle\Resource\Scaffolder;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Resource\ResourceScaffolderInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\DataObject;

class PimcoreElementScaffolder implements ResourceScaffolderInterface
{
    /**
     * @var ContextDataInterface
     */
    protected $contextData;

    /**
     * {@inheritDoc}
     */
    public function isBaseResource($resource)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable($resource): bool
    {
        if ($resource instanceof Asset) {
            return true;
        } elseif ($resource instanceof Document) {
            return true;
        } elseif ($resource instanceof DataObject\Concrete) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setup(ContextDataInterface $contextData, $resource): array
    {
        $this->contextData = $contextData;

        $type = null;
        $dataType = null;

        if ($resource instanceof Asset) {
            $type = 'asset';
            $dataType = $resource->getType();
        } elseif ($resource instanceof Document) {
            $type = 'document';
            $dataType = $resource->getType();
        } elseif ($resource instanceof DataObject\Concrete) {
            $type = 'object';
            $dataType = $resource->getType();
        }

        return [
            'type'      => $type,
            'data_type' => $dataType
        ];
    }
}