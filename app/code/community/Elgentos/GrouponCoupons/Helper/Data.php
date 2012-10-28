<?php

class Elgentos_GrouponCoupons_Helper_Data extends Mage_Core_Helper_Abstract {

    private function generateUniqueId($length = null){
        $rndId = crypt(uniqid(rand(),1));
        $rndId = strip_tags(stripslashes($rndId));
        $rndId = str_replace(array(".", "$"),"",$rndId);
        $rndId = strrev(str_replace("/","",$rndId));
        if (!is_null($rndId)){
            return strtoupper(substr($rndId, 0, $length));
        }
        return strtoupper($rndId);
    }

    private function getAllCustomerGroups(){
        $customerGroups = Mage::getModel('customer/group')->getCollection();
        $groups = array();
        foreach ($customerGroups as $group){
            $groups[] = $group->getId();
        }
        return $groups;
    }

    private function getAllWebsites(){
        $websites = Mage::getModel('core/website')->getCollection();
        $websiteIds = array();
        foreach ($websites as $website){
            $websiteIds[] = $website->getId();
        }
        return $websiteIds;
    }

    /*
     * $coupon[$valueKey],
                        $coupon[$codeKey],
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$usesCustomerKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$usesCouponKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$validFromKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$validToKey] : null),
                        (isset($coupon[$usesCustomerKey]) ? $coupon[$priorityKey] : null),
                        (isset($coupon[$descriptionKey]) ? $coupon[$descriptionKey] : null)

     */

    public function generateRule($amount=20,$couponCode=null,$usesCustomer=null,$usesCoupon=null,$validFrom=null,$validTo=null,$priority=null,$description=null){
        if($couponCode==null) {
            $couponCode = $this->generateUniqueId(10);
        }
        $rule = Mage::getModel('salesrule/rule');
        $rule->setName($couponCode.' (Groupon)');
        if($description==null) {
            $rule->setDescription('Imported Groupon coupon code');
        } else {
            $rule->setDescription($description);
        }
        if($validFrom!=null AND strtotime($validFrom)!=false) {
            $rule->setFromDate(date('Y-m-d',strtotime($validFrom)));
        } else {
            $rule->setFromDate(date('Y-m-d'));
        }
        if($validTo!=null AND strtotime($validTo)!=false) {
            $rule->setToDate(date('Y-m-d',strtotime($validTo)));
        }
        $rule->setCouponCode($couponCode);
        if($usesCoupon!=null) {
            $rule->setUsesPerCoupon($usesCoupon);
        } else {
            $rule->setUsesPerCoupon(1);
        }
        if($usesCustomer!=null) {
            $rule->setUsesPerCustomer($usesCustomer);
        } else {
            $rule->setUsesPerCustomer(1);
        }
        $rule->setCustomerGroupIds($this->getAllCustomerGroups());
        $rule->setIsActive(1);
        $rule->setStopRulesProcessing(0);
        $rule->setIsRss(0);
        $rule->setIsAdvanced(1);
        $rule->setProductIds('');
        if($priority!=null) {
            $rule->setSortOrder($priority);
        } else {
            $rule->setSortOrder(0);
        }
        $rule->setSimpleAction('by_percent');

        $rule->setDiscountAmount($amount);
        $rule->setDiscountQty(0);
        $rule->setDiscountStep(0);
        $rule->setSimpleFreeShipping(0);
        $rule->setApplyToShipping(1);
        $rule->setWebsiteIds($this->getAllWebsites());

        $conditions = array();
        $conditions[1] = array(
                'type' => 'salesrule/rule_condition_combine',
                'aggregator' => 'all',
                'value' => 1,
                'new_child' => ''
        );

        $labels = array();
        $labels[0] = $couponCode.' (Groupon)';
        $rule->setData('conditions',$conditions);
        $rule->setCouponType(2);
        $rule->setStoreLabels($labels);
        $rule->loadPost($rule->getData());
        $rule->save();
    }
}
