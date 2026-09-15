<?php
// app/code/local/YSRTech/M2API/Block/Adminhtml/Apikey/Edit.php

class YSRTech_M2API_Block_Adminhtml_Apikey_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'ysrtech_m2api';
        $this->_controller = 'adminhtml_apikey';

        parent::__construct();

        $helper = Mage::helper('ysrtech_m2api');
        $model  = Mage::registry('current_m2api_apikey');

        $this->_updateButton('save', 'label', $helper->__('Save'));
        $this->_addButton('saveandcontinue', array(
            'label'   => $helper->__('Save and Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class'   => 'save',
        ), -100);

        if ($model && $model->getId()) {
            $this->_updateButton('delete', 'label', $helper->__('Delete'));
            $this->_addButton('regenerate', array(
                'label'   => $helper->__('Regenerate Key'),
                'onclick' => 'confirmSetLocation(\''
                    . Mage::helper('core')->jsQuoteEscape($helper->__('Generate a new key? The current key will stop working immediately.'))
                    . '\', \'' . $this->getUrl('*/*/regenerate', array('id' => $model->getId())) . '\')',
                'class'   => 'delete',
            ), -50);
        } else {
            $this->_removeButton('delete');
        }

        $this->_formScripts[] = "
            function saveAndContinueEdit() {
                editForm.submit($('edit_form').action + 'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        $model = Mage::registry('current_m2api_apikey');
        if ($model && $model->getId()) {
            return Mage::helper('ysrtech_m2api')->__("Edit API Key '%s'", $this->escapeHtml($model->getName()));
        }
        return Mage::helper('ysrtech_m2api')->__('New API Key');
    }
}
