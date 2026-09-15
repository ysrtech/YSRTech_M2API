<?php
// app/code/local/YSRTech/M2API/sql/ysrtech_m2api_setup/install-0.2.0.php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

// Admin-managed API keys. Only a SHA-256 hash of each key is stored; the
// plaintext is shown to the admin once, at creation time.
$table = $installer->getConnection()
    ->newTable($installer->getTable('ysrtech_m2api/apikey'))
    ->addColumn('key_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
    ), 'Key ID')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
    ), 'Label (e.g. ShipStation)')
    ->addColumn('key_hash', Varien_Db_Ddl_Table::TYPE_TEXT, 64, array(
        'nullable' => false,
    ), 'SHA-256 of the key')
    ->addColumn('key_hint', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
        'nullable' => false,
    ), 'First characters of the key, for display')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default'  => 1,
    ), 'Status')
    ->addColumn('admin_user_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => true,
    ), 'Admin user who created the key')
    ->addColumn('expires_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
    ), 'Expires At (UTC)')
    ->addColumn('last_used_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
    ), 'Last Used At (UTC)')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => false,
    ), 'Created At (UTC)')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => false,
    ), 'Updated At (UTC)')
    ->addIndex(
        $installer->getIdxName('ysrtech_m2api/apikey', array('key_hash'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('key_hash'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex($installer->getIdxName('ysrtech_m2api/apikey', array('status')), array('status'))
    ->setComment('M2 API Keys');

$installer->getConnection()->createTable($table);

// Seed a random signing secret for password-issued tokens so no install ever
// runs on a shared/default secret.
$installer->setConfigData(
    'ysrtech_m2api/auth/secret',
    Mage::helper('core')->encrypt(bin2hex(random_bytes(32)))
);

$installer->endSetup();
