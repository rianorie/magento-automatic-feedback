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
     * Builds an resource uri from the current api version and endpoint
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
        $extensionVersion = Mage::helper('reviewo_automaticfeedback')->getExtensionVersion();
        $phpVersion = phpversion();
        $magentoVersion = Mage::getVersion();
        $storeUrl = Mage::getBaseUrl();

        $client = new Zend_Http_Client($uri, array(
            'ssltransport' => 'tls',
            'timeout' => 5,
            'useragent' => 'Magento Automatic Feedback Extension - '.$extensionVersion,
        ));
        $client->setAuth($this->getConfigData('api_key'), '');
        $client->setHeaders(array(
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
     * Sends the specified order instance to Reviewo
     *
     * Returns the Reviewo Order ID of the created object
     * Return null if there was an error
     *
     * @param $order Mage_Sales_Model_Order
     * @return int|null
     */
    public function createOrder($order)
    {
        $data = array(
            'reference' => $order->getIncrementId(),
            'meta' => array(
                'purchased_at' => $order->getCreatedAtDate()->setTimeZone('UTC')->getIso()
            )
        );

        Mage::dispatchEvent('reviewo_automaticfeedback_create_order', array(
            'data' => $data,
            'order' => $order,
        ));

        // meta should be a json string
        $data['meta'] = json_encode($data['meta']);

        $client = $this->getClient($this->getResourceUri('order'))
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data), "application/json;charset=UTF-8");

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
     * Attempts to get the Reviewo Order ID for a given order instance
     *
     * returns the Reviewo order ID of the order instance if found
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

    public function sendOrders()
    {

        // fetch all orders where we don't have a reviewo order id
        $orders = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('reviewo_order_id', array('null' => true))
            ->setPageSize($this->getConfigData('limit'))
            ->setCurPage(1);

        foreach($orders as $order) {

            // try and find if we have already submitted this order
            $orderId = $this->fetchOrder($order);

            // try and create a reviewo order
            if (!$orderId) {
                $orderId = $this->createOrder($order);
            }

            // try and save the reviewo order id
            if ($orderId) {
                try {
                    $order->setReviewoOrderId($orderId)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Generates a reivewo feedback request for specified order instance
     *
     * Returns the Reviewo Feedback Request ID of the created object
     * Return null if there was an error
     *
     * @param $order Mage_Sales_Model_Order
     * @return int|null
     */
    public function createFeedbackRequest($order)
    {
        $client = $this->getClient($this->getResourceUri('feedback-request'))
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode(array(
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
                'order' => $order->getReviewoOrderId()
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
     * Attempts to get the Reviewo Feedback Request ID for the given order instance
     *
     * returns the Reviewo Feedback Request ID of the order instance if found
     * returns null if unsuccessful
     *
     * @param $order Mage_Sales_Model_Order
     * @return int|null
     */
    public function fetchFeedbackRequest($order)
    {
        $client = $this->getClient($this->getResourceUri('feedback-request'))
            ->setMethod(Zend_Http_Client::GET)
            ->setParameterGet(array(
                'limit' => 1,
                'order__pk' => $order->getReviewoOrderId(),
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
     * Sends new feedback requests to Reviewo and updates the order model with the
     * resulting reviewo feedback request id
     */
    public function sendFeedbackRequests(){

        // fetch all orders where we have a reviewo order id but no feedback request id
        $orders = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('reviewo_feedback_request_id', array('null' => true))
            ->addFieldToFilter('reviewo_order_id', array('notnull' => false))
            ->setPageSize($this->getConfigData('limit'))
            ->setCurPage(1);

        foreach($orders as $order) {

            // try and find if we have an existing reviewo feedback request for this order
            $feedbackRequestId = $this->fetchFeedbackRequest($order);

            // try and create reviewo feedback request
            if (!$feedbackRequestId) {
                $feedbackRequestId = $this->createFeedbackRequest($order);
            }

            // try and save the reviewo feedback request id
            if ($feedbackRequestId) {
                try {
                    $order->setReviewoFeedbackRequestId($feedbackRequestId)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Entry point for cronjob
     */
    public function run()
    {
        if (!$this->getConfigData('active')) { return; }

        $this->sendOrders();
        $this->sendFeedbackRequests();
    }

}
