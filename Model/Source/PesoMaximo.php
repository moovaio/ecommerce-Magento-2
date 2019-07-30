<?php

namespace Improntus\Moova\Model\Source;

/**
 * Class PesoMaximo
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Model\Source
 */
class PesoMaximo implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            '10'  => '10 kg',
            '50'  => '50 kg',
            '100'  => '100 kg'
        ];
    }
}
