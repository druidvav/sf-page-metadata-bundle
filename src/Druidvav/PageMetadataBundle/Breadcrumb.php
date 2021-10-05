<?php
namespace Druidvav\PageMetadataBundle;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Breadcrumb
{
    /**
     * @var RouterInterface
     */
    private $router;

    private $transDomain = null;

    protected $url;
    protected $text;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function setTransDomain($domain)
    {
        $this->transDomain = $domain;
    }

    public function setRoute($route, array $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): Breadcrumb
    {
        return $this->setUrl($this->router->generate($route, $parameters, $referenceType));
    }

    public function setUrl($url): Breadcrumb
    {
        $this->url = $url;
        return $this;
    }

    public function setRawText($text): Breadcrumb
    {
        $this->text = $text;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getText()
    {
        return $this->text;
    }
}