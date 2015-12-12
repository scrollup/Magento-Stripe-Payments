<?php
/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   Copyright (c) 2015 District Commerce (http://districtcommerce.com)
 *
 */

class District_Stripe_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DASHBOARD_PAYMENTS_URL = 'https://dashboard.stripe.com/payments/';

    /**
    * Sets the API key for interfacing with Stripe API
    *
    * @param   none
    * @return  none
    */
    public function setApiKey()
    {
        try {
            \Stripe\Stripe::setApiKey(Mage::getStoreConfig('payment/stripe/api_secret_key'));
        } catch (Exception $e) {
            Mage::throwException('District Stripe: Could not set API key');
        }
    }

    /**
    * Retrieve a customer from Stripe
    *
    * @param   string $token
    * @return  Stripe_Customer $customer
    */
    public function retrieveCustomer($token)
    {
        $this->setApiKey();

        try {
            $customer = \Stripe\Customer::retrieve(Mage::helper('core')->decrypt($token));
        } catch(Exception $e) {
            //Fail silently
            Mage::log('District Stripe: Could not retrieve customer');
        }

        return $customer;
    }

    /**
    * Get a customer from Database
    *
    * @param   none
    * @return  District_Stripe_Model_Customer
    */
    public function getCustomer()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $model = Mage::getModel('stripe/customer');

        return $model->load($customer->getId(), 'customer_id');
    }

    /**
    * Get payments dashboard URL
    *
    * @param   none
    * @return  string DASHBOARD_PAYMENTS_URL
    */
    public function getPaymentsDashboardUrl()
    {
        return self::DASHBOARD_PAYMENTS_URL;
    }

    /**
    * Create a customer
    *
    * @param   string $token
    * @return  Stripe_Customer $stripeCustomer
    */
    public function createCustomer($token)
    {
        //Set API Key
        $this->setApiKey();

        //Create the customer
        try {

            //Get customer object
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            //Create customer in Stripe
            $stripeCustomer = \Stripe\Customer::create(array(
                'source' => $token,
                'email' => $customer->getEmail()
            ));

            //Create stripe customer in magento
            $model = Mage::getModel('stripe/customer');
            $model->setCustomerId($customer->getId());
            $model->setToken(Mage::helper('core')->encrypt($stripeCustomer->id));
            $model->save();

        } catch (Exception $e) {
            //Silently fail, don't stop transaction
            Mage::log('Stripe: Could not create customer');
        }

        return $stripeCustomer;
    }

    /**
    * Get Failed Orders Count
    *
    * @param   string $orderId
    * @return  District_Stripe_Model_Mysql4_Order_Failed_Collection
    */
    public function getFailedOrdersCount($orderId)
    {
        //Count failed orders
        return Mage::getModel('stripe/order_failed')
            ->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->getSize();
    }

}
