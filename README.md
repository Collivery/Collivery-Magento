MDS Magento Shipping Module
===========================

*MDS Collivery Shipping Module for Magento.*

Adds the cost of shipping to the clients final price,
and allows you to register the shipping from within the plugin.

Installation Instructions
-------------------------
- Find and copy the 'app' folder into the root directory of your Magento installation,
and replace/merge it with the current app folder.
- In Magento Admin Panel, go to System -> Cache Management, and click on "Flush Magento Cache" to reload your Cache!
- Go to System -> Configuration -> Shipping Methods -> MDS Collivery, and enter all your details there.

Your plugin is now installed! However, each product now has 3 extra fields for dimensions (length, width, height).
Shipping won't be available for products without dimensions.

<h3>For Developers</h3>
If you are a developer and need the install file to run a second time without changing the version,
in your Magento Database, just delete code `collivery_setup` from table `core_resource` or execute:

``DELETE FROM `core_resource` WHERE `code` = 'collivery_setup';``

Remember to Refresh your cache to run the install!

Development Information
-----------------------
This plugin is still in development.
If you are a developer, you are most welcome to contribute to its development or check back at a later stage.
You can also get in touch with us through email at 
[integration@collivery.co.za](mailto:integration@collivery.co.za)
if you need any additional information.

<h3>To Do</h3>

<h4>Frontend</h4>
- [ ] Allow users to track their orders
- [x] Add additional fields to checkout page (Location Type, Building Details)
- [x] Validate Address/Add populated fields to Checkout Page

<h4>Backend</h4>
- [ ] Add shipment/tracking info to order and mark order as shipped/completed
- [x] Start with Admin Backend
- [ ] Save Client/Address ID for next time
- [x] Save IDs in order
- [ ] Save IDs in clients address
- [x] Hash info and check/store hashes in a table.

<h4>Documentation</h4>
- [ ] Update Documentation for backend usage

<h4>API</h4>
- [x] Move to MDS Webservice V2

<h3>MDS Web Service</h3>

This plugin makes use of MDS Collivery WSDL.
Please refer to the following document for more information on our API:
[http://collivery.net/integration/api/soap](http://collivery.net/integration/api/soap "MDS Collivery API Developers Manual")

License
--------

MDS Magento Shipping Module is distributed under the terms of the GNU General Public License,
version 3 or later.
