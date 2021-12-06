<?php

namespace DsWebCrawlerBundle\Resource\Scaffolder;

use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Resource\ResourceScaffolderInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\DataObject;

class PimcoreElementScaffolder implements ResourceScaffolderInterface
{
    protected ContextDefinitionInterface $contextDefinition;

    public function isBaseResource($resource): bool
    {
        return false;
    }

    public function isApplicable($resource): bool
    {
        if ($resource instanceof Asset\Document) {
            return true;
        }

        if ($resource instanceof Document) {
            return true;
        }

        if ($resource instanceof DataObject\Concrete) {
            return true;
        }

        return false;
    }

    public function setup(ContextDefinitionInterface $contextDefinition, mixed $resource): array
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
