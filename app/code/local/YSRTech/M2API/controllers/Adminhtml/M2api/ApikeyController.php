<?php
// app/code/local/YSRTech/M2API/controllers/Adminhtml/M2api/ApikeyController.php

class YSRTech_M2API_Adminhtml_M2api_ApikeyController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/ysrtech_m2api')
            ->_title($this->__('System'))
            ->_title($this->__('M2 API Keys'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $model = $this->_loadKey();
        if ($model === false) {
            return;
        }

        // Re-populate the form after a failed save
        $formData = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($formData)) {
            $model->addData($formData);
        }

        Mage::register('current_m2api_apikey', $model);

        $this->_initAction()
            ->_title($model->getId() ? $model->getName() : $this->__('New API Key'))
            ->renderLayout();
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if (!$data) {
            $this->_redirect('*/*/');
            return;
        }

        $model = $this->_loadKey();
        if ($model === false) {
            return;
        }

        $session = Mage::getSingleton('adminhtml/session');
        try {
            $name = isset($data['name']) ? trim((string)$data['name']) : '';
            if ($name === '') {
                Mage::throwException($this->__('Please enter a name for the key.'));
            }

            $model->setName($name)
                ->setStatus(isset($data['status']) ? (int)$data['status'] : YSRTech_M2API_Model_Apikey::STATUS_ACTIVE)
                ->setExpiresAt($this->_parseExpiresAt(isset($data['expires_at']) ? $data['expires_at'] : ''));

            $plainKey = null;
            if (!$model->getId()) {
                $plainKey = $model->generateKey();
                $model->setAdminUserId(Mage::getSingleton('admin/session')->getUser()->getId());
            }

            $model->save();
            $session->addSuccess($this->__('The API key has been saved.'));

            if ($plainKey !== null) {
                // Shown once on the edit page, then discarded.
                $session->setM2apiNewKey($plainKey);
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            }
            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            }
            $this->_redirect('*/*/');
            return;
        } catch (Mage_Core_Exception $e) {
            $session->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addError($this->__('An error occurred while saving the API key.'));
        }

        $session->setFormData($data);
        if ($model->getId()) {
            $this->_redirect('*/*/edit', array('id' => $model->getId()));
        } else {
            $this->_redirect('*/*/new');
        }
    }

    /**
     * Replace the key's secret with a new one. The old key stops working
     * immediately; the new one is shown once on the edit page.
     */
    public function regenerateAction()
    {
        $model = $this->_loadKey();
        if ($model === false) {
            return;
        }
        if (!$model->getId()) {
            $this->_redirect('*/*/');
            return;
        }

        $session = Mage::getSingleton('adminhtml/session');
        try {
            $plainKey = $model->generateKey();
            $model->save();
            $session->setM2apiNewKey($plainKey);
            $session->addSuccess($this->__('A new key has been generated. The previous key no longer works.'));
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addError($this->__('An error occurred while regenerating the key.'));
        }
        $this->_redirect('*/*/edit', array('id' => $model->getId()));
    }

    public function deleteAction()
    {
        $model = $this->_loadKey();
        if ($model === false) {
            return;
        }
        $session = Mage::getSingleton('adminhtml/session');
        if ($model->getId()) {
            try {
                $model->delete();
                $session->addSuccess($this->__('The API key has been deleted.'));
            } catch (Exception $e) {
                Mage::logException($e);
                $session->addError($this->__('An error occurred while deleting the API key.'));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('key_ids');
        $session = Mage::getSingleton('adminhtml/session');
        if (!is_array($ids) || empty($ids)) {
            $session->addError($this->__('Please select at least one API key.'));
        } else {
            try {
                foreach ($ids as $id) {
                    Mage::getModel('ysrtech_m2api/apikey')->load((int)$id)->delete();
                }
                $session->addSuccess($this->__('Total of %d API key(s) were deleted.', count($ids)));
            } catch (Exception $e) {
                Mage::logException($e);
                $session->addError($this->__('An error occurred while deleting the API keys.'));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massStatusAction()
    {
        $ids = $this->getRequest()->getParam('key_ids');
        $status = (int)$this->getRequest()->getParam('status');
        $session = Mage::getSingleton('adminhtml/session');
        if (!is_array($ids) || empty($ids)) {
            $session->addError($this->__('Please select at least one API key.'));
        } else {
            try {
                foreach ($ids as $id) {
                    Mage::getModel('ysrtech_m2api/apikey')->load((int)$id)->setStatus($status)->save();
                }
                $session->addSuccess($this->__('Total of %d API key(s) were updated.', count($ids)));
            } catch (Exception $e) {
                Mage::logException($e);
                $session->addError($this->__('An error occurred while updating the API keys.'));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Load the key named by the "id" param, or a fresh model if none.
     * Redirects to the grid and returns false if the id doesn't exist.
     *
     * @return YSRTech_M2API_Model_Apikey|false
     */
    protected function _loadKey()
    {
        $model = Mage::getModel('ysrtech_m2api/apikey');
        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This API key no longer exists.'));
                $this->_redirect('*/*/');
                return false;
            }
        }
        return $model;
    }

    /**
     * Form posts YYYY-MM-DD; keys expire at the end of that day, UTC.
     *
     * @return string|null
     */
    protected function _parseExpiresAt($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || strtotime($value . ' UTC') === false) {
            Mage::throwException($this->__('The expiry date must be in YYYY-MM-DD format.'));
        }
        return $value . ' 23:59:59';
    }

    protected function _isAllowed(): bool
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/ysrtech_m2api');
    }
}
