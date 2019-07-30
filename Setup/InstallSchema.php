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

        $columnaAltura = [
            'type'    => Table::TYPE_INTEGER,
            'nullable'=> true,
            'comment' => 'Altura de la calle de la dirección del cliente',
            'default' => null
        ];

        $columnaPiso = [
            'type'    => Table::TYPE_INTEGER,
            'nullable'=> true,
            'comment' => 'Piso de la calle de la dirección del cliente',
            'default' => null
        ];

        $columnaDepartamento = [
            'type'    => Table::TYPE_TEXT,
            'nullable'=> true,
            'comment' => 'Departamento de la calle de la dirección del cliente',
            'default' => null
        ];

        $columnaObservaciones = [
            'type'    => Table::TYPE_TEXT,
            'nullable'=> true,
            'comment' => 'Observaciones del cliente',
            'default' => null
        ];

        $columnaMoovaQuoteId = [
            'type'    => Table::TYPE_INTEGER,
            'nullable'=> true,
            'comment' => 'Quote Id de cotizacion moova',
            'default' => null
        ];

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order_address'), 'altura'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order_address'), 'altura', $columnaAltura);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote_address'), 'altura'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote_address'), 'altura', $columnaAltura);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order_address'), 'piso'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order_address'), 'piso', $columnaPiso);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote_address'), 'piso'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote_address'), 'piso', $columnaPiso);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order_address'), 'departamento'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order_address'), 'departamento', $columnaDepartamento);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote_address'), 'departamento'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote_address'), 'departamento', $columnaDepartamento);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order_address'), 'observaciones'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order_address'), 'observaciones', $columnaObservaciones);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote_address'), 'observaciones'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote_address'), 'observaciones', $columnaObservaciones);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote'), 'moova_quote_id'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'moova_quote_id', $columnaMoovaQuoteId);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order'), 'moova_quote_id'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'moova_quote_id', $columnaMoovaQuoteId);
        }

        $installer->endSetup();
    }
}