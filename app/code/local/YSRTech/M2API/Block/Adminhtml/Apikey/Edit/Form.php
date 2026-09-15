<?php
// app/code/local/YSRTech/M2API/Block/Adminhtml/Apikey/Edit/Form.php

class YSRTech_M2API_Block_Adminhtml_Apikey_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('ysrtech_m2api');
        /** @var YSRTech_M2API_Model_Apikey $model */
        $model = Mage::registry('current_m2api_apikey');

        $form = new Varien_Data_Form(array(
            'id'     => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $model->getId())),
            'method' => 'post',
        ));
        $form->setUseContainer(true);

        // A freshly generated key is handed over exactly once, then dropped
        // from the session.
        $newKey = Mage::getSingleton('adminhtml/session')->getM2apiNewKey(true);
        if ($newKey) {
            $keyFieldset = $form->addFieldset('m2api_new_key', array(
                'legend' => $helper->__('Your New API Key'),
            ));
            $keyFieldset->addField('new_key', 'note', array(
                'text' => '<p><strong>' . $helper->__('Copy this key now. It will not be shown again.') . '</strong></p>'
                    . '<input type="text" readonly="readonly" onclick="this.select()" value="'
                    . $this->escapeHtml($newKey)
                    . '" style="width:100%;font-family:monospace;font-size:13px;padding:4px;" />',
            ));
        }

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => $helper->__('API Key'),
        ));

        if ($model->getId()) {
            $fieldset->addField('key_id', 'hidden', array(
                'name' => 'id',
            ));
            $fieldset->addField('key_hint', 'note', array(
                'label' => $helper->__('Key'),
                'text'  => '<code>' . $this->escapeHtml($model->getKeyHint()) . '</code>',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => $helper->__('Name'),
            'required' => true,
            'note'     => $helper->__('What this key is for, e.g. "ShipStation".'),
        ));
        $fieldset->addField('status', 'select', array(
            'name'   => 'status',
            'label'  => $helper->__('Status'),
            'values' => YSRTech_M2API_Model_Apikey::getStatusOptions(),
        ));
        $fieldset->addField('expires_at', 'date', array(
            'name'   => 'expires_at',
            'label'  => $helper->__('Expires On'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'note'   => $helper->__('Optional. The key stops working at the end of this day (UTC).'),
        ));

        if ($model->getId()) {
            $fieldset->addField('last_used_at', 'note', array(
                'label' => $helper->__('Last Used'),
                'text'  => $model->getLastUsedAt()
                    ? $this->formatDate($model->getLastUsedAt(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true)
                    : $helper->__('Never'),
            ));
            $fieldset->addField('created_at', 'note', array(
                'label' => $helper->__('Created'),
                'text'  => $this->formatDate($model->getCreatedAt(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true),
            ));
        } elseif ($model->getStatus() === null) {
            $model->setStatus(YSRTech_M2API_Model_Apikey::STATUS_ACTIVE);
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
