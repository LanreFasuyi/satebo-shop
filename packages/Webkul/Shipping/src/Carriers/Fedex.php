<?php

namespace Webkul\Shipping\Carriers;

use Config;
use 
Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Checkout\Facades\Cart;


use FedEx\RateService\Request;
use FedEx\RateService\ComplexType;
use FedEx\RateService\SimpleType;

/**
 * Class Rate.
 *
 */
class Fedex extends AbstractShipping
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'fedex';

   
    /**
     * Returns rate for flatrate
     *
     * @return CartShippingRate|false
     */
    public function calculate()
    {
         $rateRequest = new ComplexType\RateRequest();


         //authentication & client details
    $rateRequest->WebAuthenticationDetail->UserCredential->Key = "AQoSNQCWxbIrSY5P";

    
    $rateRequest->WebAuthenticationDetail->UserCredential->Password = 'yTua3Ut6HLrd8B5gcCuW4ysz4';





    
    $rateRequest->ClientDetail->AccountNumber = "510087640";
    $rateRequest->ClientDetail->MeterNumber = "256510421";


    $rateRequest->TransactionDetail->CustomerTransactionId = '';


//version
$rateRequest->Version->ServiceId = 'crs';
$rateRequest->Version->Major = 24;
$rateRequest->Version->Minor = 0;
$rateRequest->Version->Intermediate = 0;

$rateRequest->ReturnTransitAndCommit = true;

//shipper
$rateRequest->RequestedShipment->PreferredCurrency = 'USD';
$rateRequest->RequestedShipment->Shipper->Address->StreetLines = ['10 Fed Ex Pkwy'];
$rateRequest->RequestedShipment->Shipper->Address->City = 'Memphis';
$rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = 'TN';
$rateRequest->RequestedShipment->Shipper->Address->PostalCode = 38115;
$rateRequest->RequestedShipment->Shipper->Address->CountryCode = 'US';

//recipient
$rateRequest->RequestedShipment->Recipient->Address->StreetLines = ['13450 Farmcrest Ct'];
$rateRequest->RequestedShipment->Recipient->Address->City = 'Herndon';
$rateRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = 'VA';
$rateRequest->RequestedShipment->Recipient->Address->PostalCode = 20171;
$rateRequest->RequestedShipment->Recipient->Address->CountryCode = 'US';

//shipping charges payment
$rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = SimpleType\PaymentType::_SENDER;

//rate request types
$rateRequest->RequestedShipment->RateRequestTypes = [SimpleType\RateRequestType::_PREFERRED, SimpleType\RateRequestType::_LIST];






$cart = Cart::getCart();

$items_count = count($cart->items);





$rateRequest->RequestedShipment->PackageCount = $items_count;


$custom_items = []; 
for($i=0; $i<$items_count; $i++){

    $obj = new ComplexType\RequestedPackageLineItem();
    $custom_items[] = $obj ; 
}

//create package line items
$rateRequest->RequestedShipment->RequestedPackageLineItems = $custom_items;


for($i=0; $i<$items_count; $i++){

    $item = $cart->items[$i]; 
    // echo json_encode($item);

//custom_package 
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->Weight->Value = 1;
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->Weight->Units = SimpleType\WeightUnits::_LB;
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->Dimensions->Length = $item->length;
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->Dimensions->Width = $item->width;
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->Dimensions->Height = $item->height;
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->Dimensions->Units = SimpleType\LinearUnits::_IN;
$rateRequest->RequestedShipment->RequestedPackageLineItems[$i]->GroupPackageCount = $item->quantity;


}

$rateServiceRequest = new Request();
//$rateServiceRequest->getSoapClient()->__setLocation(Request::PRODUCTION_URL); //use production URL

$rateReply = $rateServiceRequest->getGetRatesReply($rateRequest); // send true as the 2nd argument to return the SoapClient's stdClass response.

  if (! $this->isAvailable()) {
            return false;
        }



        $objects = []; 
      

          






if (!empty($rateReply->RateReplyDetails)) {
    foreach ($rateReply->RateReplyDetails as $rateReplyDetail) {


      // var_dump($rateReplyDetail);
        // var_dump($rateReplyDetail->ServiceType);


        $object = new CartShippingRate(); 
        $object->carrier = 'fedex';
        $object->carrier_title = $this->getConfigData('title');
        $object->method = 'fedex';
        $object->method_title = $this->getConfigData('title');

        $object->method_description = $rateReplyDetail->ServiceType;
        $object->price = 0;
        $object->base_price = 0;


        

        $object->method_description =  $rateReplyDetail->ServiceType;
       




       

        if (!empty($rateReplyDetail->RatedShipmentDetails)) {
            foreach ($rateReplyDetail->RatedShipmentDetails as $ratedShipmentDetail) {
                // var_dump($ratedShipmentDetail->ShipmentRateDetail->RateType . ": " . $ratedShipmentDetail->ShipmentRateDetail->TotalNetCharge->Amount);



                  $object->base_price += $ratedShipmentDetail->ShipmentRateDetail->TotalNetCharge->Amount; 
              
                
            }
        }


        return $object;
        // echo "<hr />";

        array_push($objects, $object);

    }
}



       



       







return $objects;

    }
}