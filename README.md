## EveryPay Payment Extension for CS Cart - Versions 3.x
Allows you to use EveryPay payment gateway with the CS Cart Store.

## Description

This is the EveryPay payment gateway plugin for CS Cart. Allows merchants to accept credit cards, debit cards with the CS Cart store. It uses a seamless integration, allowing the customer to pay on your website without being redirected away from your CS Cart website.

## Installation
1. Ensure you have version 3.x of CS Cart installed.
2. Download the zip of this repository (https://github.com/everypay/everypay-cscart-3/archive/master.zip).
3. Extract to a local folder
4. Upload all the extracted folders (addons,js,payments,skins) in your shop's base folder (they will be merged)
5. Navigate to CS cart admin menu Addons > Manage Addons.
6. Choose add new addon and upload the zip file

Now the addon show be active. If not activate it manually.

## Configuration

1. Navigate to Administration / Payment Methods.
2. Click the "+" to add a new payment method.
3. Choose EveryPay from the list and choose "cc_outside.tpl" in the template filed
4. Fill in these values according to the language: 
<dl>
    <dt>Ελληνικά</dt>
    <dd>Τίτλος: Χρεωστική / Πιστωτική κάρτα</dd>
    <dd>Περιγραφή: Μέσω του ασφαλούς συστήματος της EveryPay</dd>
    <dt>English</dt>
    <dd>Title: Credit / Debit Card</dd>
    <dd>Description: Through safe Everypay  gateway</dd>
</dl>
5. Click the 'Configure' tab.
6. Enter your Public Key, Private Key, and choose the test mode (Test or not). You can find these in your settings menu (https://dashboard.everypay.gr/settings/api-keys)
7. Enter your desired installment ranges, e.g. [0->100:2 installments], [100->500 : 5 installments], etc.
8. Click 'Save'

### Support

Visit [https://everypay.gr](https://everypay.gr) for support requests or email support@everypay.gr.