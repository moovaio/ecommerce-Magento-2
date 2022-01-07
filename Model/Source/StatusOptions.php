<?php

namespace Improntus\Moova\Model\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;

/**
 * Class StatusOptions
 *
 * @author Axel Candia
 * @package Improntus\Moova\Model\Source
 */

class StatusOptions implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('sales_order_status');

        $statuses = $connection->query('SELECT * FROM ' .$tableName);
        $response = [
            '' => 'disabled'
        ];
        
        foreach ($statuses as $status) {
            $response = array_merge($response, [
                $status['status'] => $status['status']
            ]);
        }
        return $response;
    }
}
