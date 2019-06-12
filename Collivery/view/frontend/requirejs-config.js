var config = {
  map: {
    '*':{
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
      }
    }
  }
};