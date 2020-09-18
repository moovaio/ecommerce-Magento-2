<?php

namespace Improntus\Moova\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();


        $columnaMoovaQuoteId = [
            'type'    => Table::TYPE_INTEGER,
            'nullable' => true,
            'comment' => 'Quote Id de cotizacion moova',
            'default' => null
        ];

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote'), 'moova_quote_id')) {
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'moova_quote_id', $columnaMoovaQuoteId);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order'), 'moova_quote_id')) {
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'moova_quote_id', $columnaMoovaQuoteId);
        }

        $installer->endSetup();
    }
}
