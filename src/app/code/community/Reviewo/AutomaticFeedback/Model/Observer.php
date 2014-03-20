<?php
/**
 * #@#LICENCE#@#
 */

class Reviewo_AutomaticFeedback_Model_Observer
{
    const API_ENDPOINT = 'https://www.reviewo.com/api';
    const API_VERSION = 'v1';

    public function __construct()
    {

    }

    /**
     * Gets a field value from the store config
     *
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        $path = 'sales/reviewo_automaticfeedback/'.$field;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Builds an resource uri from the current api version
     * and endpoint
     *
     * @param string $method
     * @return string
     */
    public function getResourceUri($method='')
    {
        return self::API_ENDPOINT.'/'.self::API_VERSION.'/'.$method.'/';
    }

    /**
     * Builds a Zend_Http_Client instance with auth and headers set
     *
     * @param $uri string
     * @return Zend_Http_Client
     */
    public function getClient($uri)
    {
        $extensionVersion = Mage::helper('automaticfeedback')->getExtensionVersion();
        $phpVersion = phpversion();
        $magentoVersion = Mage::getVersion();
        $storeUrl = Mage::getBaseUrl();

        $client = new Zend_Http_Client($uri, array(
            'ssltransport' => 'tls',
            'timeout' => 5,
        ));
        $client->setAuth(
            $this->getConfigData('api_user'),
            $this->getConfigData('api_key')
        );
        $client->setHeaders(array(
            'useragent' => 'Magento Automatic Feedback Extension - '.$extensionVersion,
            'x-user-agent' => json_encode(array(
                'php' => $phpVersion,
                'magento' => $magentoVersion,
                'extension' => $extensionVersion,
                'store' => $storeUrl
            )),
        ));
        return $client;
    }

    /**
     * Send the specified order to the orders endpoint
     *
     * returns the reviewo order id of the created object if successful
     * returns null if unsuccessful
     *
     * @param $order Mage_Sales_Model_Order
     * @return int|null
     */
    public function createOrder($order)
    {
        $client = $this->getClient($this->getResourceUri('order'))
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode(array(
                'reference' => $order->getIncrementId(),
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
                'purchased_at' => $order->getCreatedAtStoreDate()->getIso()
            )), "application/json;charset=UTF-8");

        try {
            $response = $client->request();
        } catch (Exception $e) {
            Mage::logException($e);
            return null;
        }

        if ($response->isError()) {
            return null;
        }

        $decoded = json_decode($response->getBody(), true);

        if (!$decoded || !isset($decoded['id'])) {
            return null;
        }

        return $decoded['id'];
    }

    /**
     * Attempts to get the reviewo order id for a given order instance
     *
     * returns the reviewo order id of the order instance if found
     * returns null if unsuccessful
     *
     * @param $order Mage_Sales_Model_Order
     * @return int|null
     */
    public function fetchOrder($order)
    {
        $client = $this->getClient($this->getResourceUri('order'))
            ->setMethod(Zend_Http_Client::GET)
            ->setParameterGet(array(
                'limit' => 1,
                'reference' => $order->getIncrementId(),
            ));

        try {
            $response = $client->request();
        } catch (Exception $e) {
            Mage::logException($e);
            return null;
        }

        if ($response->isError()) {
            return null;
        }

        $decoded = json_decode($response->getBody(), true);

        if (!$decoded || !isset($decoded['objects'][0]['id'])) {
            return null;
        }

        return $decoded['objects'][0]['id'];
    }

    /**
     * Entry point for cronjob
     */
    public function sendOrders()
    {
        if (!$this->getConfigData('active')) { return; }

        $orders = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('reviewo_id', array('null' => true));

        foreach($orders as $order) {
            $orderId = $this->fetchOrder($order);
            $orderId = $orderId ? $orderId : $this->createOrder($order);
            if ($orderId) {
                try {
                    $order->setReviewoId($orderId)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }
}
