<?php
// app/code/local/YSRTech/M2API/sql/m2api_setup/install-0.1.0.php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

// No schema changes for Phase 1.
// Tokens and audit tables will be added in Phase 2.

$installer->endSetup();

