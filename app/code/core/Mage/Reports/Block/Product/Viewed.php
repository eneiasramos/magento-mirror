<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Reports Recently Viewed Products Block
 *
 * @category   Mage
 * @package    Mage_Reports
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Reports_Block_Product_Viewed extends Mage_Catalog_Block_Product_Abstract
{
    protected function _hasViewedProductsBefore()
    {
        return Mage::getSingleton('reports/session')->getData('viewed_products');
    }

    public function __construct()
    {
        parent::__construct();
        if ($this->_hasViewedProductsBefore() === false) {
            return $this;
        }
//        $this->setTemplate('reports/product_viewed.phtml');

        $ignore = null;
        if (($product = Mage::registry('product')) && $product->getId()) {
            $ignore = $product->getId();
        }

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $subjectId = $customer->getId();
            $subtype = 0;
        } else {
            $subjectId = Mage::getSingleton('log/visitor')->getId();
            $subtype = 1;
        }
        $collection = Mage::getModel('reports/event')
            ->getCollection()
            ->addRecentlyFiler(Mage_Reports_Model_Event::EVENT_PRODUCT_VIEW, $subjectId, $subtype, $ignore);
        $productIds = array();
        foreach ($collection as $event) {
            $productIds[] = $event->getObjectId();
        }
        unset($collection);

        if (is_null($this->_hasViewedProductsBefore())) {
            Mage::getSingleton('reports/session')->setData('viewed_products', count($productIds) > 0);
        }

        $productCollection = null;
        if ($productIds) {
            $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->addIdFilter($productIds)
                ->addUrlRewrite();
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
            $productCollection->setPageSize(5)->setCurPage(1)->load();

            foreach ($productCollection as $product) {
                $product->setDoNotUseCategoryId(true);
            }
        }
        $this->setRecentlyViewedProducts($productCollection);
    }
}