<?php
class Nacex_Shipping_Model_Carrier_Naxjow extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

	protected $_frompcode;
	protected $_topcode;
	protected $_servicio;
	protected $_sweight;
	protected $_title;
	protected $_code = 'spainpost';

	public function __construct(){
		$this->_title	=	$this->getConfigData('title');
	}

	public function getAllowedMethods(){
		return array($this->_code => $this->getConfigData('msg'));
	}

	/**
	* Collects the shipping rates for Spain Post from the DRC API.
	*
	* @param Mage_Shipping_Model_Rate_Request $data
	* @return Mage_Shipping_Model_Rate_Result
	*/
	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
		// Check if this method is active
		if (!$this->getConfigFlag('active')) {
			return false;
		}

		// Check if this method is even applicable (must ship from Spain)
		$origCountry = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
		$result = Mage::getModel('shipping/rate_result');

		$error = Mage::getModel('shipping/rate_result_error');
		if ($origCountry != "ES") {
			if($this->getConfigData('showmethod')){
				$error->setCarrier($this->_code)
					->setCarrierTitle($this->getConfigData('title'))
					->setErrorMessage($this->getConfigData('specificerrmsg'));
				$result->append($error);
				return $result;
			} else {
				return false;
			}
		}

		//check if cart order value falls between the minimum and maximum order amounts required
		$packagevalue = $request->getBaseCurrency()->convert($request->getPackageValue(), $request->getPackageCurrency());
		$minorderval = (int)$this->getConfigData('min_order_value');
		$maxorderval = (int)$this->getConfigData('max_order_value');
		if(
			/* EL PAQUETE ES MENOR O IGUAL AL MINIMO Y EL MINIMO ESTA HABILITADO*/
			($packagevalue <= $minorderval) && ($minorderval > 0) || 
			/* EL PAQUETE ES MAYOR O IGUAL AL MAXIMO Y EL MAXIMO ESTA HABILITADO*/
			(($maxorderval != 0) && ($packagevalue >= $maxorderval))){
			if($this->getConfigData('showmethod')){
				$error->setCarrier($this->_code)
					->setCarrierTitle($this->getConfigData('title'));
				$currency	=	Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);
				/* SI EL MINIMO Y EL MAXIMO ESTA HABILITADO*/
				if($minorderval != 0 && $maxorderval != 0){
					$errorMsg=Mage::helper('nacex')->__('Package value must be between %s and %s',Mage::app()->getStore()->formatPrice($minorderval),Mage::app()->getStore()->formatPrice($maxorderval));
				/* SI EL MAXIMO ESTA HABILITADO*/
				}elseif($maxorderval != 0){
					$errorMsg=Mage::helper('nacex')->__('Package value must be less than %s',
						Mage::app()->getStore()->formatPrice($maxorderval)
					);
				/* SI EL MINIMO ESTA HABILITADO*/
				}else{
					$errorMsg=Mage::helper('nacex')->__('Package value must be higher than %s',Mage::app()->getStore()->formatPrice($minorderval));
				}
				$error->setErrorMessage($errorMsg);
				$result->append($error);
				return $result;
			} else {
				return false;
			}
		}

		// CODIGO POSTAL DE LA TIENDA
		$this->_frompcode = Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
		// CODIGO POSTAL DEL COMPRADOR
		$this->_topcode = $request->getDestPostcode();
		if ($request->getDestCountryId()) {
			$destCountry = $request->getDestCountryId();
		} else {
			$destCountry = "ES";
		}

		$sweightunit = $this->getConfigData('weight_units');
		$this->_sweight = $request->getPackageWeight()*$sweightunit;

		// En el caso de que sea dentro de españa se muestra
		// en caso contrario no
		if($destCountry == "ES") {
			try {
				$this->_servicio	=	Mage::getSingleton('nacex/standard_data')->setServicio($this->_frompcode,$this->_topcode);
				$_params	=	array(
					'servicio'=>$this->_servicio,
					'sweight'=>$this->_sweight,
					'code'=>$this->_code
					);
				$datosPrecio	=	Mage::getSingleton('nacex/standard_data')->getPrecio($_params);
				$shippingPrice	=	$datosPrecio['price'];
				// set the handling fee type....
				if ($this->getConfigData('handling_type') == 'F') {
					$shippingPrice += $this->getConfigData('handling_fee');
				} else {
					$handlingFee = ($shippingPrice * $this->getConfigData('handling_fee'))/100;
					$shippingPrice += $handlingFee;
				}

				$shippingPrice	+=	$this->getConfigData('xtra');

				$rate = Mage::getModel('shipping/rate_result_method');
				$rate->setCarrier($this->_code);
				$rate->setCarrierTitle($this->getConfigData('title'));
				$rate->setMethod($this->_code);
				$rate->setMethodTitle($this->getConfigData('msg'));
				$rate->setCost($datosPrecio['price']);
				$rate->setPrice($shippingPrice);
				$result->append($rate);
			} catch(Exception $e){
				Mage::logException($e);
				return false;
			}
		} else {
			$error = Mage::getModel('shipping/rate_result_error');
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage($this->getConfigData('specificerrmsg'));
			$result->append($error);
		}
		return $result;
	}
}