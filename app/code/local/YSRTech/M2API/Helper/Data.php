<?php
// app/code/local/YSRTech/M2API/Helper/Data.php
class YSRTech_M2API_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_LOGGING_ENABLED = 'ysrtech_m2api/logging/enabled';

    /**
     * Debug logging is off by default (System > Configuration > M2 API > Logging).
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_LOGGING_ENABLED);
    }

    /**
     * Write to one of the module's log files, but only when logging is enabled.
     *
     * @param string $message
     * @param string $file
     */
    public function log($message, $file = 'm2api.log')
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }
        Mage::log($message, null, $file, true);
    }

    /**
     * Returns a direct tracking URL for the given carrier code and tracking number.
     * Used in frontend, adminhtml, and email templates.
     *
     * @param string $carrierCode  e.g. 'ups', 'fedex', 'usps', 'dhl', 'ontrac'
     * @param string $number       tracking number
     * @return string|null         URL string, or null if carrier not mapped
     */
    public function getTrackingUrl($carrierCode, $number)
    {
        $number = urlencode(trim($number));
        $code   = strtolower(trim($carrierCode));

        $urls = array(
            'ups'          => 'https://www.ups.com/track?tracknum=' . $number,
            'fedex'        => 'https://www.fedex.com/apps/fedextrack/?tracknumbers=' . $number,
            'fedexwalleted'=> 'https://www.fedex.com/apps/fedextrack/?tracknumbers=' . $number,
            'usps'         => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $number,
            'stamps_com'   => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $number,
            'dhl'          => 'https://www.dhl.com/en/express/tracking.html?AWB=' . $number,
            'dhlexpress'   => 'https://www.dhl.com/en/express/tracking.html?AWB=' . $number,
            'dhl_express'  => 'https://www.dhl.com/en/express/tracking.html?AWB=' . $number,
            'ontrac'       => 'https://www.ontrac.com/tracking/?number=' . $number,
            'lasership'    => 'https://www.lasership.com/track/' . $number,
            'amazon'       => 'https://track.amazon.com/tracking/' . $number,
            'asendia'      => 'https://tracking.asendia.com/tracking/' . $number,
            'australiapost'=> 'https://auspost.com.au/mypost/track/#/details/' . $number,
            'canadapost'   => 'https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor=' . $number,
            'royalmail'    => 'https://www.royalmail.com/track-your-item#/tracking-results/' . $number,
        );

        return isset($urls[$code]) ? $urls[$code] : null;
    }

    public function logRequest(Zend_Controller_Request_Http $request)
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        $entry = array(
            'timestamp' => date('c'),
            'method'    => $request->getMethod(),
            'url'       => $request->getRequestUri(),
            'body'      => $request->getRawBody(),
        );

        $this->log(json_encode($entry, JSON_PRETTY_PRINT), 'm2api.log');
    }
}
