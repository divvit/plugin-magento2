<?php
/**
 * Module for Divvit export
 * Copyright (C) 2017  2vmodules.com
 * 
 * This file is part of V2modules/Divvit.
 * 
 * V2modules/Divvit is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace V2modules\Divvit\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $table_divvit_cookie = $setup->getConnection()->newTable($setup->getTable('v2modules_divvit_customer_cookie'));

        
        $table_divvit_cookie->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,'auto_increment' => true),
            'Entity ID'
        );
        
        $table_divvit_cookie->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false,'auto_increment' => false],
            'Customet id'
        );
		
		$table_divvit_cookie->addColumn(
            'cookie_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false,'auto_increment' => false],
            'Data'
        );
		$table_divvit_cookie->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        );
		$table_divvit_cookie->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Update at'
        );

        $setup->getConnection()->createTable($table_divvit_cookie);

        $setup->endSetup();
    }
}
