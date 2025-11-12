<?php
// ============================================
// FILE: app/code/local/YSRTech/M2API/sql/m2api_setup/install-1.0.0.php
// ============================================

$installer = $this;
$installer->startSetup();

// Create token table for API authentication
$table = $installer->getConnection()
    ->newTable($installer->getTable('m2api_token'))
    ->addColumn('token_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Token ID')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Customer ID')
    ->addColumn('admin_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Admin ID')
    ->addColumn('token', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
    ), 'Token String')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => false,
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
    ), 'Created At')
    ->addColumn('expires_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => false,
    ), 'Expires At')
    ->addIndex($installer->getIdxName('m2api_token', array('token')), array('token'))
    ->addIndex($installer->getIdxName('m2api_token', array('customer_id')), array('customer_id'))
    ->addIndex($installer->getIdxName('m2api_token', array('admin_id')), array('admin_id'))
    ->setComment('M2 API Access Tokens');

$installer->getConnection()->createTable($table);

$installer->endSetup();