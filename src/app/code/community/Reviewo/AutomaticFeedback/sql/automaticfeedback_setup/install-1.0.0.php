<?php
/**
 * #@#LICENCE#@#
 */

/** @var $this Reviewo_AutomaticFeedback_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'reviewo_id', array(
    'type'    => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'comment' => 'Reviewo Order ID',
));

$installer->endSetup();
