var config = {
  deps:[
    "MDS_Collivery/js/action/shared-ajax-mixin",
  ],
  map: {
    '*':{
    }
  },
  config: {
    mixins: {
      'Magento_Checkout/js/action/set-billing-address': {
        'MDS_Collivery/js/action/set-billing-address-mixin': true
      },
      'Magento_Checkout/js/action/set-shipping-information': {
        'MDS_Collivery/js/action/set-shipping-information-mixin': true
      }
    }
  }
};