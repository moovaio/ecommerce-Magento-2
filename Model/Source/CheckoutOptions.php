<?php

namespace Improntus\Moova\Model\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;

/**
 * Class PesoMaximo
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Model\Source
 */
class CheckoutOptions implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('quote_address');
        $fields = $connection->describeTable($tableName);
        $response = [
            '' => 'disabled'
        ];
        foreach ($fields as $column) {
            $response = array_merge($response, [
                $column['COLUMN_NAME'] => $column['COLUMN_NAME']
            ]);
        }
        return $response;
    }
}
