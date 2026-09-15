<?php
// app/code/local/YSRTech/M2API/Model/Apikey.php

/**
 * An admin-managed API key. The plaintext key is generated once, handed to the
 * admin, and never stored; requests are matched against its SHA-256 hash.
 */
class YSRTech_M2API_Model_Apikey extends Mage_Core_Model_Abstract
{
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    /** Keys start with this so Auth can tell them apart from JWTs. */
    const KEY_PREFIX = 'm2api_';

    /** Don't write last_used_at more often than this (seconds). */
    const LAST_USED_UPDATE_INTERVAL = 300;

    protected function _construct()
    {
        $this->_init('ysrtech_m2api/apikey');
    }

    public static function getStatusOptions()
    {
        $helper = Mage::helper('ysrtech_m2api');
        return array(
            self::STATUS_ACTIVE   => $helper->__('Active'),
            self::STATUS_INACTIVE => $helper->__('Inactive'),
        );
    }

    public static function hashKey($plain)
    {
        return hash('sha256', $plain);
    }

    /**
     * Generate a fresh key, store its hash/hint on this model and return the
     * plaintext. The caller is responsible for showing it to the admin once.
     */
    public function generateKey()
    {
        $plain = self::KEY_PREFIX . bin2hex(random_bytes(20));
        $this->setKeyHash(self::hashKey($plain));
        $this->setKeyHint(substr($plain, 0, strlen(self::KEY_PREFIX) + 6) . '...');
        return $plain;
    }

    public function loadByPlainKey($plain)
    {
        return $this->load(self::hashKey($plain), 'key_hash');
    }

    public function isUsable()
    {
        if (!$this->getId() || (int)$this->getStatus() !== self::STATUS_ACTIVE) {
            return false;
        }
        $expiresAt = $this->getExpiresAt();
        if ($expiresAt && strtotime($expiresAt . ' UTC') < time()) {
            return false;
        }
        return true;
    }

    /**
     * Record that the key was just used, throttled so busy integrations don't
     * cause a write on every request.
     */
    public function touchLastUsed()
    {
        $last = $this->getLastUsedAt() ? strtotime($this->getLastUsedAt() . ' UTC') : 0;
        if (time() - $last >= self::LAST_USED_UPDATE_INTERVAL) {
            $this->getResource()->updateLastUsed($this->getId());
        }
        return $this;
    }
}
