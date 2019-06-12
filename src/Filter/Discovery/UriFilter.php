<?php

namespace DsWebCrawlerBundle\Filter\Discovery;

use DsWebCrawlerBundle\Filter\LogDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

class UriFilter implements PreFetchFilterInterface
{
    use LogDispatcher;

    /**
     * @var array
     */
    protected $regexBag = [];

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
                $this->notifyDispatcher($uri, 'uri.match.forbidden');
                return true;
            }
        }

        return false;
    }

}
