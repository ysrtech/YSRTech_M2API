<?php
// app/code/local/YSRTech/M2api/Model/Auth.php
class YSRTech_M2api_Model_Auth extends Mage_Core_Model_Abstract
{
    // Change this via system.xml later; for now hardcode a strong secret
    const SECRET_CONFIG_PATH = 'ysrtech_m2api/auth/secret';
    const DEFAULT_SECRET = 'change_me_to_a_long_random_secret';

    // Token format: base64url(header).base64url(payload).base64url(signature)
    public function issueToken($userType, $userId, $ttlSeconds = 86400)
    {
        $now = time();
        $payload = array(
            'typ' => 'JWT',
        );
        $claims = array(
            'sub' => (string)$userId,
            'usr' => $userType,         // admin|customer
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'ver' => '1.0',
        );

        $secret = $this->getSecret();
        $token = $this->encode($payload, $claims, $secret);
        return $token;
    }

    // Issue a long-lived token for integrations (10 years)
    public function issueLongLivedToken($userType, $userId)
    {
        return $this->issueToken($userType, $userId, 315360000); // 10 years
    }

    public function validateToken($token)
    {
        try {
            list($header, $claims) = $this->decode($token, $this->getSecret());
            if (!is_array($claims) || !isset($claims['exp']) || $claims['exp'] < time()) {
                return false;
            }
            return array(
                'user_id' => $claims['sub'],
                'type' => $claims['usr'],
                'issued_at' => $claims['iat'],
                'expires_at' => $claims['exp']
            );
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    protected function getSecret()
    {
        $secret = Mage::getStoreConfig(self::SECRET_CONFIG_PATH);
        if (!$secret) {
            $secret = self::DEFAULT_SECRET;
        }
        return $secret;
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
