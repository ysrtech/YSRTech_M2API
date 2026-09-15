<?php
// app/code/local/YSRTech/M2API/Model/Auth.php

/**
 * Bearer-token authentication. Two kinds of token are accepted:
 *
 *  - API keys ("m2api_..."), created in the admin panel under
 *    System > M2 API Keys. Long-lived, revocable, admin-level.
 *  - JWT-style tokens issued by the username/password endpoints. Short-lived,
 *    signed with a key derived from the install's crypt key.
 */
class YSRTech_M2API_Model_Auth extends Mage_Core_Model_Abstract
{
    const TOKEN_TTL_CONFIG_PATH = 'ysrtech_m2api/auth/token_ttl';
    const DEFAULT_TOKEN_TTL     = 86400;
    const KEY_DERIVATION_CONTEXT = 'ysrtech_m2api/jwt';

    /**
     * Token format: base64url(header).base64url(payload).base64url(signature)
     *
     * @param string   $userType   admin|customer
     * @param int      $userId
     * @param int|null $ttlSeconds defaults to the configured lifetime
     */
    public function issueToken($userType, $userId, $ttlSeconds = null)
    {
        if ($ttlSeconds === null) {
            $ttlSeconds = (int)Mage::getStoreConfig(self::TOKEN_TTL_CONFIG_PATH);
            if ($ttlSeconds <= 0) {
                $ttlSeconds = self::DEFAULT_TOKEN_TTL;
            }
        }

        $now = time();
        $header = array(
            'typ' => 'JWT',
        );
        $claims = array(
            'sub' => (string)$userId,
            'usr' => $userType,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'ver' => '1.0',
        );

        return $this->encode($header, $claims, $this->getSecret());
    }

    /**
     * @return array|false  ['user_id', 'type' (admin|customer), 'issued_at', 'expires_at', 'api_key_id'?]
     */
    public function validateToken($token)
    {
        if (!is_string($token) || $token === '') {
            return false;
        }
        if (strpos($token, YSRTech_M2API_Model_Apikey::KEY_PREFIX) === 0) {
            return $this->validateApiKey($token);
        }
        return $this->validateJwt($token);
    }

    protected function validateApiKey($plain)
    {
        /** @var YSRTech_M2API_Model_Apikey $key */
        $key = Mage::getModel('ysrtech_m2api/apikey')->loadByPlainKey($plain);
        if (!$key->isUsable()) {
            return false;
        }
        $key->touchLastUsed();

        return array(
            'user_id'    => (int)$key->getAdminUserId(),
            'type'       => 'admin',
            'api_key_id' => (int)$key->getId(),
            'issued_at'  => strtotime($key->getCreatedAt() . ' UTC'),
            'expires_at' => $key->getExpiresAt() ? strtotime($key->getExpiresAt() . ' UTC') : null,
        );
    }

    protected function validateJwt($token)
    {
        try {
            list($header, $claims) = $this->decode($token, $this->getSecret());
            if (!is_array($claims)
                || !isset($claims['exp'], $claims['sub'], $claims['usr'])
                || $claims['exp'] < time()
                || !in_array($claims['usr'], array('admin', 'customer'), true)
            ) {
                return false;
            }
            return array(
                'user_id'    => $claims['sub'],
                'type'       => $claims['usr'],
                'issued_at'  => isset($claims['iat']) ? $claims['iat'] : null,
                'expires_at' => $claims['exp'],
            );
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Signing key derived from the install's crypt key (app/etc/local.xml).
     * Nothing to store or seed; rotating the crypt key via System > Manage
     * Encryption Key invalidates outstanding tokens. Deriving rather than
     * using the key raw keeps the signing bytes distinct from the
     * encryption bytes.
     */
    protected function getSecret()
    {
        $cryptKey = (string)Mage::getConfig()->getNode('global/crypt/key');
        if ($cryptKey === '') {
            throw new Exception('Magento crypt key is not configured (app/etc/local.xml)');
        }
        return hash_hmac('sha256', self::KEY_DERIVATION_CONTEXT, $cryptKey);
    }

    protected function b64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function encode(array $header, array $claims, $secret)
    {
        $h = $this->b64url(json_encode($header));
        $p = $this->b64url(json_encode($claims));
        $sig = $this->b64url(hash_hmac('sha256', "{$h}.{$p}", $secret, true));
        return "{$h}.{$p}.{$sig}";
    }

    protected function decode($token, $secret)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Malformed token');
        }
        list($h, $p, $s) = $parts;
        $signed = $this->b64url(hash_hmac('sha256', "{$h}.{$p}", $secret, true));
        if (!hash_equals($signed, $s)) {
            throw new Exception('Invalid signature');
        }
        $header = json_decode(base64_decode(strtr($h, '-_', '+/')), true);
        $claims = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        return array($header, $claims);
    }
}
