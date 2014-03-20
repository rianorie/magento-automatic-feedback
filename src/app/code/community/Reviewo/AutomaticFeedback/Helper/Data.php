<?php
/**
 * #@#LICENCE#@#
 */

class Reviewo_AutomaticFeedback_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Gets the current extension version
     *
     * @return string
     */
    public function getExtensionVersion()
    {
        $version = (string) Mage::getConfig()->getNode()->modules->Reviewo_AutomaticFeedback->version;
        return $version;
    }
}