<?php

class MDS_Collivery_Block_Adminhtml_Sales_Order_View_Tab_Collivery extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate( 'collivery/sales/order/view/tab/collivery.phtml' );
    }

    public function getTabLabel()
    {
        return $this->__( 'Collivery' );
    }

    public function getTabTitle()
    {
        return $this->__( 'Collivery' );
    }

    public function getTabClass()
    {
        return '';
    }

    public function getClass()
    {
        return $this->getTabClass();
    }

    public function getTabUrl()
    {
        // http://mydomain.com/admin/sales_order/attack/order_id/1/key/65cbb0c2956dd9413570a2ec8761bef5/
        return $this->getUrl('*/*/mds', array('_current' => true));
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    public function getOrder()
    {
        return Mage::registry( 'current_order' );
    }
}