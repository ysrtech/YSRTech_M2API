<?php
// app/code/local/YSRTech/M2API/Block/Adminhtml/Apikey/Grid.php

class YSRTech_M2API_Block_Adminhtml_Apikey_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('m2apiApikeyGrid');
        $this->setDefaultSort('key_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $this->setCollection(Mage::getModel('ysrtech_m2api/apikey')->getCollection());
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('ysrtech_m2api');

        $this->addColumn('key_id', array(
            'header' => $helper->__('ID'),
            'index'  => 'key_id',
            'width'  => '50px',
            'type'   => 'number',
        ));
        $this->addColumn('name', array(
            'header' => $helper->__('Name'),
            'index'  => 'name',
        ));
        $this->addColumn('key_hint', array(
            'header'   => $helper->__('Key'),
            'index'    => 'key_hint',
            'filter'   => false,
            'sortable' => false,
        ));
        $this->addColumn('status', array(
            'header'  => $helper->__('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'options' => YSRTech_M2API_Model_Apikey::getStatusOptions(),
            'width'   => '80px',
        ));
        $this->addColumn('expires_at', array(
            'header'  => $helper->__('Expires'),
            'index'   => 'expires_at',
            'type'    => 'datetime',
            'default' => $helper->__('Never'),
        ));
        $this->addColumn('last_used_at', array(
            'header'  => $helper->__('Last Used'),
            'index'   => 'last_used_at',
            'type'    => 'datetime',
            'default' => $helper->__('Never'),
        ));
        $this->addColumn('created_at', array(
            'header' => $helper->__('Created'),
            'index'  => 'created_at',
            'type'   => 'datetime',
        ));
        $this->addColumn('action', array(
            'header'    => $helper->__('Action'),
            'width'     => '60px',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption' => $helper->__('Edit'),
                    'url'     => array('base' => '*/*/edit'),
                    'field'   => 'id',
                ),
                array(
                    'caption' => $helper->__('Delete'),
                    'url'     => array('base' => '*/*/delete'),
                    'field'   => 'id',
                    'confirm' => $helper->__('Delete this API key? Anything using it will stop working.'),
                ),
            ),
            'filter'    => false,
            'sortable'  => false,
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $helper = Mage::helper('ysrtech_m2api');

        $this->setMassactionIdField('key_id');
        $this->getMassactionBlock()->setFormFieldName('key_ids');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => $helper->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => $helper->__('Delete the selected API keys? Anything using them will stop working.'),
        ));
        $this->getMassactionBlock()->addItem('status', array(
            'label'      => $helper->__('Change Status'),
            'url'        => $this->getUrl('*/*/massStatus'),
            'additional' => array(
                'visibility' => array(
                    'name'   => 'status',
                    'type'   => 'select',
                    'class'  => 'required-entry',
                    'label'  => $helper->__('Status'),
                    'values' => YSRTech_M2API_Model_Apikey::getStatusOptions(),
                ),
            ),
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
