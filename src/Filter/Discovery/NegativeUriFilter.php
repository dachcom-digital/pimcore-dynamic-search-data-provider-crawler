<?php

namespace DsWebCrawlerBundle\Filter\Discovery;

use DsWebCrawlerBundle\Filter\LogDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

class NegativeUriFilter implements PreFetchFilterInterface
{
    use LogDispatcher;

    /**
     * @var array
     */
    public $regexBag = [];

    /**
     * @param array                    $regexBag
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function __construct(array $regexBag, $dispatcher)
    {
        $this->regexBag = $regexBag;
        $this->setDispatcher($dispatcher);
    }

    /**
     * @param UriInterface $uri
     *
     * @return bool
     * @throws \Exception
     */
    public function match(UriInterface $uri)
    {
        foreach ($this->regexBag as $regex) {
            if (preg_match($regex, $uri->toString())) {
                return false;
            }
        }

        $this->notifyDispatcher($uri, 'uri.match.invalid');

        return true;
    }
}
