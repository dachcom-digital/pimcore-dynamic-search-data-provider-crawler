<?php

namespace DsWebCrawlerBundle\Resource\Scaffolder;

use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Resource\ResourceScaffolderInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\DataObject;

class PimcoreElementScaffolder implements ResourceScaffolderInterface
{
    /**
     * @var ContextDefinitionInterface
     */
    protected $contextDefinition;

    /**
     * {@inheritdoc}
     */
    public function isBaseResource($resource)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($resource): bool
    {
        if ($resource instanceof Asset\Document) {
            return true;
        } elseif ($resource instanceof Document) {
            return true;
        } elseif ($resource instanceof DataObject\Concrete) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setup(ContextDefinitionInterface $contextDefinition, $resource): array
    {
        $this->contextDefinition = $contextDefinition;

        $type = null;
        $dataType = null;

        if ($resource instanceof Asset\Document) {
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
