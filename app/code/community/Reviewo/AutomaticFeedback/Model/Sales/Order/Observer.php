<?php

class Reviewo_AutomaticFeedback_Model_Sales_Order_Observer
{
    const API_ENDPOINT = 'https://reviewo.com/api';
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
        $path = 'sales/automaticfeedback/'.$field;
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
     * Sends the new order details (order id, name and email) to
     * reviewo for the verified reviews functionality
     *
     * @param $observer
     * @return $this
     */
    public function send_new_order($observer)
    {
        $order = $observer->getEvent()->getOrder();

        $client = new Varien_Http_Client();
        $client->setUri($this->getResourceUri('order'))
            ->setMethod('POST')
            ->setAuth(
                $this->getConfigData('api_user'),
                $this->getConfigData('api_key')
            )
            ->setHeaders(array(
                'X-Reviewo-User-Agent' => json_encode(array(
                    'language' => 'php '.phpversion(),
                    'framework' => 'magento '.Mage::getVersion(),
                )),
            ))
            ->setRawData(json_encode(array(
                'reference' => $order->getIncrementId(),
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
            )), "application/json;charset=UTF-8")
            ->request();

        return $this;
    }
}