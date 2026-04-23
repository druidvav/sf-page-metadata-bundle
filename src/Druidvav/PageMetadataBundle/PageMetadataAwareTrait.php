<?php

namespace Druidvav\PageMetadataBundle;

trait PageMetadataAwareTrait
{
    protected PageMetadata $pageMetadata;

    /**
     * @required
     */
    public function setPageMetadata(PageMetadata $pageMetadata)
    {
        $this->pageMetadata = $pageMetadata;
    }
}
