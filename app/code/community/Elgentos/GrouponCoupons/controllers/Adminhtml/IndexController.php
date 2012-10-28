<?php

class Elgentos_GrouponCoupons_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    protected function _initAction($title=null) {
        $this->loadLayout();
        if($title!=null) {
            $this->getLayout()->getBlock('head')->setTitle($title.' / Magento Admin');
        }
        $this->_setActiveMenu('promo/index');

        return $this;
    }

    public function indexAction() {
        $this->_initAction('Groupon Coupons Import')->renderLayout();
    }

    public function columnsAction() {
        try {
            if(isset($_FILES['csvFile'])) {
                $content = array();
                ini_set("auto_detect_line_endings", true);
                $file = new SplFileObject($_FILES['csvFile']['tmp_name']);
                $file->setFlags(SplFileObject::READ_CSV);
                $file->setCsvControl(',','');
                foreach ($file as $key=>$row) {
                    if($key==0) {
                        $headers = $row;
                    } else {
                        $content[] = $row;
                    }
                }
                if(count($content)) {
                    Mage::getModel('core/session')->setCouponContent($content);
                }
                Mage::getModel('core/session')->setHeaders($headers);
                $this->_initAction('Groupon Coupons Import')->renderLayout();
            }
        } catch(Exception $e) {
            Mage::getModel('core/session')->addError($e->getMessage());
            $this->_redirectReferer();
        }
    }

    public function uploadAction() {
        try {
            $i=0;
            $content = Mage::getModel('core/session')->getCouponContent();
            $valueKey = $this->getRequest()->getParam('valueKey');
            $codeKey = $this->getRequest()->getParam('codeKey');
            $usesCustomerKey = $this->getRequest()->getParam('usesCustomerKey');
            $usesCouponKey = $this->getRequest()->getParam('usesCouponKey');
            $validFromKey = $this->getRequest()->getParam('validFromKey');
            $validToKey = $this->getRequest()->getParam('validToKey');
            $priorityKey = $this->getRequest()->getParam('priorityKey');
            $descriptionKey = $this->getRequest()->getParam('descriptionKey');
            $errors = array();
            foreach($content as $coupon) {
                try {
                    Mage::helper('grouponcoupons')->generateRule(
                        $coupon[$valueKey],
                        $coupon[$codeKey],
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$usesCustomerKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$usesCouponKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$validFromKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$validToKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$priorityKey] : null),
                        (isset($coupon[$descriptionKey]) ? $coupon[$descriptionKey] : null)
                    );
                    $i++;
                } catch(Exception $e) {
                    $errors[] = $e->getMessage() . ' (code '.$coupon[$codeKey].')';
                }
            }

            if($errors) {
                Mage::getModel('core/session')->addError(implode("<br />",$errors));
            }
            Mage::getModel('core/session')->addSuccess($i." ".Mage::helper('grouponcoupons')->__('coupons have been imported.'));
            Mage::getModel('core/session')->setCouponContent();
            Mage::getModel('core/session')->setHeaders();
            $this->_redirect('adminhtml/promo_quote');
        } catch(Exception $e) {
            Mage::getModel('core/session')->addError($e->getMessage());
            $this->_redirectReferer();
        }
    }
}
