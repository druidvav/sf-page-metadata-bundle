<?php

namespace Druidvav\PageMetadataBundle;

use Symfony\Contracts\Service\Attribute\Required;

trait PageMetadataAwareTrait
{
    protected PageMetadata $pageMetadata;

    /**
     * @Required
     */
    #[Required]
    public function setPageMetadata(PageMetadata $pageMetadata): void
    {
        $this->pageMetadata = $pageMetadata;
    }
}
