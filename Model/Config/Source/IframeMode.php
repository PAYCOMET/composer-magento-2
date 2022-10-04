<?php

namespace Paycomet\Payment\Model\Config\Source;

class IframeMode implements \Magento\Framework\Option\ArrayInterface
{
    private const IFRAMEMODE_EMBEDDED = 'embedded';
    private const IFRAMEMODE_LIGHTBOX = 'lightbox';

    /**
     * Possible iframe modes.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::IFRAMEMODE_EMBEDDED,
                'label' => 'Embedded',
            ],
            [
                'value' => self::IFRAMEMODE_LIGHTBOX,
                'label' => 'Lightbox',
            ],
        ];
    }
}
