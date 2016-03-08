<?php
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('gwibidding/bidding');

//Check if the table already exists

if($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('bidding_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity'=>true, 'unsigned'=>true, 'nullable'=>false, 'primary'=>true), 'Bidding Id')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned'=>true, 'nullable'=>false, 'default'=>'1',), 'Customer Id')
        ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigend'=>true, 'nullable'=>false,),'Product ID')
        ->addColumn('email', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => false), 'Customer Email')
        ->addColumn('product_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => false), 'Product Name')
        ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable'=>false), 'SKU')
        ->addColumn('unit_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(16,2), array('nullable'=>false), 'Unit Price')
        ->addColumn('unit_price_offerred', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(16,2), array(), 'Unit Price Offerred')
        ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('nullable'=>false), 'Quantity')
        ->addColumn('qty_offerred', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Quantity Offerred')
        ->addColumn('expect_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array('nullable'=>false), 'Expect Date')
        ->addColumn('expect_date_offerred', Varien_Db_Ddl_Table::TYPE_DATE, null, array(), 'Expect Date Offerred')
        ->addColumn('warehouse_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned'=>false, 'nullable'=>false, 'default'=>'1'), 'Warehouse Id')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array('nullable'=>false, 'default'=>date('Y-m-d H:i:s')), 'Created At')
        ->addColumn('modified_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Modified At')
        ->addcolumn('self_pick', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(), 'Self Pick')
        ->addcolumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable'=>false, 'default'=>'pending',), 'Status')
        ->addcolumn('accepted', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(), 'Accepted')
        ->addIndex($installer->getIdxName('gwibidding/bidding', array('customer_id')), array('customer_id'))
        ->addIndex($installer->getIdxName('gwibidding/bidding', array('product_id')), array('product_id'))
        ->addForeignKey($installer->getFkName('gwibidding/bidding', 'customer_id', 'customer/entity', 'entity_id'),'customer_id', $installer->getTable('customer/entity'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('gwibidding/bidding', 'product_id', 'catalog/product', 'entity_id'),'product_id', $installer->getTable('catalog/product'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);

   $installer->getConnection()->createTable($table);

   $installer->endSetup();
}
