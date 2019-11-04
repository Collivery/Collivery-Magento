var config = {
  deps:[
    "MDS_Collivery/js/action/shared-ajax-mixin",
  ],
  map: {
    '*':{
      'Magento_Checkout/template/shipping-address/address-renderer/default.html':
          'MDS_Collivery/template/shipping-address/address-renderer/default.html',
      'Magento_Checkout/template/shipping-information/address-renderer/default.html':
          'MDS_Collivery/template/shipping-information/address-renderer/default.html',
      'Magento_Checkout/template/billing-address/details.html':
          'MDS_Collivery/template/billing-address/details.html',
    }
  },
  config: {
    mixins: {
      'Magento_Checkout/js/action/set-billing-address': {
        'MDS_Collivery/js/action/set-billing-address-mixin': true
      },
      'Magento_Checkout/js/action/set-shipping-information': {
        'MDS_Collivery/js/action/set-shipping-information-mixin': true
      },
      'Magento_Checkout/js/view/billing-address': {
        'MDS_Collivery/js/view/billing-address': true
      },
      'Magento_Checkout/js/view/shipping-address/address-renderer/default' : {
        'MDS_Collivery/js/view/address-renderer-extension' : true
      },
    }
  }
};