<?php
class Nacex_Shipping_Model_Carrier_Spainpost extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

	public $_regions = array(
		'ANDALUCIA'							=>array('04','11','14','18','21','23','29','41'),
		'ARAGON'								=>array('22','44','50'),
		'BALEARES'							=>array('07'),
		'CANARIAS'							=>array('35','38'),
		'CANTABRIA'							=>array('39'),
		'CASTILLA Y LEON'					=>array('05','09','24','34','37','40','42','47','49'),
		'CASTILLA LA MANCA'				=>array('02','13','16','19','45'),
		'CATALUÑA'							=>array('08','17','25','43'),
		'CEUTA'								=>array('51'),
		'COMUNIDAD DE MADRID'			=>array('28'),
		'COMUNIDAD FORAL DE NAVARRA'	=>array('31'),
		'COMUNIDAD VALENCIANA'			=>array('03','12','46'),
		'EUSKADI - PAIS VASCO'			=>array('01','20','48'),
		'EXTREMADURA'						=>array('06','10'),
		'GALICIA'							=>array('15','27','32','36'),
		'LA RIOJA'							=>array('26'),
		'MELILLA'							=>array('52'),
		'PRINCIPADO DE ASTURIAS'		=>array('33'),
		'MURCIA'								=>array('30')
	);
	

	public $_prov = array( "01"=>"ALAVA", "02"=>"ALBACETE", "03"=>"ALICANTE", "04"=>"ALMERIA","33"=>"ASTURIAS","05"=>"AVILA", "06"=>"BADAJOZ", "08"=>"BARCELONA", "09"=>"BURGOS", "10"=>"CACERES", "11"=>"CADIZ", "39"=>"CANTABRIA", "12"=>"CASTELLON", "51"=>"CEUTA", "13"=>"CIUDAD REAL", "14"=>"CORDOBA", "15"=>"CORUÑA, LA", "16"=>"CUENCA", "17"=>"GIRONA", "18"=>"GRANADA", "19"=>"GUADALAJARA", "20"=>"GUIPUZCOA", "21"=>"HUELVA", "22"=>"HUESCA", "07"=>"ILLES BALEARS", "23"=>"JAEN", "24"=>"LEON","25"=>"LLEIDA", "27"=>"LUGO", "28"=>"MADRID", "29"=>"MALAGA", "52"=>"MELILLA", "30"=>"MURCIA", "31"=>"NAVARRA", "32"=>"OURENSE", "34"=>"PALENCIA", "35"=>"PALMAS, LAS", "36"=>"PONTEVEDRA", "26"=>"RIOJA, LA", "37"=>"SALAMANCA", "38"=>"SANTA CRUZ DE TENERIFE", "40"=>"SEGOVIA", "41"=>"SEVILLA", "42"=>"SORIA", "43"=>"TARRAGONA","44"=>"TERUEL", "45"=>"TOLEDO", "46"=>"VALENCIA", "47"=>"VALLADOLID", "48"=>"VIZCAYA", "49"=>"ZAMORA", "50"=>"ZARAGOZA");
	
	protected $_frompcode;
	protected $_topcode;
	protected $_servicio;
	protected $_sweight;
	protected $_code = 'spainpost';

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

		if ($origCountry != "ES") {
			return false;
		}

		//check if cart order value falls between the minimum and maximum order amounts required
		$packagevalue = $request->getBaseCurrency()->convert($request->getPackageValue(), $request->getPackageCurrency());
		$minorderval = $this->getConfigData('min_order_value');
		$maxorderval = $this->getConfigData('max_order_value');
		if($packagevalue <= $minorderval || (($maxorderval != '0') && $packagevalue >= $maxorderval)){
			return false;
		}

		$result = Mage::getModel('shipping/rate_result');

		// CODIGO POSTAL DE LA TIENDA
		$this->_frompcode = Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
		// CODIGO POSTAL DEL COMPRADOR
		$this->_topcode = $request->getDestPostcode();

		if ($request->getDestCountryId()) {
			$destCountry = $request->getDestCountryId();
		} else {
			$destCountry = "ES";
		}

		$sweightunit = 1; //$this->getConfigData('weight_units');
		$this->_sweight = $request->getPackageWeight()*$sweightunit;

		// En el caso de que sea dentro de españa se muestra
		// en caso contrario no
		if($destCountry == "ES") {
			$this->setServicio();

			$price=($this->getPrecio() * 1.16) + 1;

			$method = Mage::getModel('shipping/rate_result_method');
			
			$type = $this->getConfigData('type');
			$qty = $request->getPackageQty();

			$tot = ($qty * $price);

			if ($this->getConfigData('type') == 'O') { // by order
				$shippingPrice = $price;
			} elseif ($type == 'I') { // by item
				$shippingPrice = ($qty * $price);
			} else {
				$shippingPrice = false;
			}

			// set the handling fee type....
			$calculateHandlingFee = $this->getConfigData('handling_type');
			$handlingFee = $this->getConfigData('handling_fee');
			if ($this->getConfigData('handling_type') == 'F') {
				$shippingPrice += $this->getConfigData('handling_fee');
			} else {
				$handlingFee = ($shippingPrice * $this->getConfigData('handling_fee'))/100;
				$shippingPrice += $handlingFee;
			}

			$method->setCarrier('spainpost');
			$method->setCarrierTitle($this->getConfigData('title'));
			$method->setMethod($this->_shipping_method);
			$method->setMethodTitle($this->getConfigData('title') . ": {$this->_shipping_method}");
			$method->setPrice($shippingPrice);
			$method->setCost($shippingPrice);
			$result->append($method);
		} else {
			return false;
		}
		return $result;
	}

	public function getRegion($topcode,$compare=null){
		$ind=substr($topcode,0,2);
		foreach($this->_regions as $id => $val){
			if(in_array($ind,$val)){
				$region=$id;
				continue;
			}
		}
		if($compare !== null){
			$compare=$this->getRegion($compare);
			if($region === $compare)
				return true;
			else
				return false;
		}
		echo "<!--REGION: {$region}-->";
		return $region;
	}

	/**
	* Get allowed shipping methods
	*
	* @return array
	*/
	public function getAllowedMethods() {
		return array('spainpost' => $this->getConfigData('name'));
	}

	public function getPrecio(){
		switch($this->_servicio){
			case 'PROVINCIAL':
				$this->_shipping_method = 'PACK';
				if($this->_sweight <= '2000') {
					$this->_shipping_method = 'BAG';
					$price = 6.01;
				} elseif(($this->_sweight > '2000') && ($this->_sweight <='5000')){
					$price = 6.74;
				} elseif(($this->_sweight > '5000') && ($this->_sweight <='10000')){
					$price = 8.49;
				} else {
					$price = 8.49;
					$peso=$this->_sweight - 10000;
					$price += (ceil($peso / 5000)) * 1.91;
				}
				break;
			case 'REGIONAL':
				$this->_shipping_method = 'PACK';
				if($this->_sweight <= '2000') {
					$this->_shipping_method = 'BAG';
					$price = 7.11;
				} elseif(($this->_sweight > '2000') && ($this->_sweight <='5000')){
					$price = 18.88;
				} elseif(($this->_sweight > '5000') && ($this->_sweight <='10000')){
					$price = 25.84;
				} else {
					$price = 25.84;
					$peso=$this->_sweight - 10000;
					$price += (ceil($peso / 5000)) * 3.62;
				}
				break;
			case 'NACIONAL':
				$this->_shipping_method = 'PACK';
				if($this->_sweight <= '2000') {
					$this->_shipping_method = 'BAG';
					$price = 7.58;
				} elseif(($this->_sweight > '2000') && ($this->_sweight <='5000')){
					$price = 9.57;
				} elseif(($this->_sweight > '5000') && ($this->_sweight <='10000')){
					$price = 13.54;
				} else {
					$price = 13.54;
					$peso=$this->_sweight - 10000;
					$price += (ceil($peso / 5000)) * 3.96;
				}
				break;
			case 'NACIONAL_BALEARES':
				$this->_shipping_method = 'PACK';
				if($this->_sweight <= '2000') {
					$this->_shipping_method = 'BAG';
					$price = 10.56;
				} elseif(($this->_sweight > '2000') && ($this->_sweight <='4000')){
					$price = 16.48;
				} else {
					$price = 16.48;
					$peso=$this->_sweight - 4000;
					$price += (ceil($peso / 2000)) * 4;
				}
				break;
			case 'INTRAISLAS':
				$this->_shipping_method = 'PACK';
				if($this->_sweight <= '2000') {
					$this->_shipping_method = 'BAG';
					$price = 6.18;
				} elseif(($this->_sweight > '5000')){
					$price = 6.95;
				} elseif(($this->_sweight <='10000')){
					$price = 8.76;
				} else {
					$price = 8.76;
					$peso=$this->_sweight - 10000;
					$price += (ceil($peso / 5000)) * 1.99;
				}
				break;
			
		}
		echo "<!--METODO: {$this->_shipping_method}-->";
		echo "<!--PRECIO: {$price}-->";
		return $price;
	}
	
	
	/*	
	*	Setea el tipo de servicio segun la ubicacion del cliente 
	*	con respecto a la tienda
	*/
	public function setServicio(){
		$code_store	= substr($this->_frompcode,0,2);
		switch($code_store){
			// PARA LA TIENDA EN BALEARES
			case '07':
				if($this->getRegion($code_store,$this->_topcode)){
					// PRECIO INTRAISLAS
					$this->_servicio='INTRAISLAS';
				} else {
					// PRECIO NACIONAL PARA TODA ESPAÑA
					$this->_servicio='NACIONAL_BALEARES';
				}
				break;
			// PARA LA TIENDA EN EL RESTO DE ESPAÑA
			default:
				if($this->_frompcode == $this->_topcode){
					// PRECIO PROVINCIAL
					$this->_servicio='PROVINCIAL';
				} else	{
					$id_region = $this->getRegion($this->_topcode,$this->_frompcode);
					if($id_region == true){
						// PRECIO REGIONAL PARA PROVINCIAS DE LA MISMA REGION
						$this->_servicio='REGIONAL';
					} else {
						// PRECIO NACIONAL PARA TODA ESPAÑA
						$this->_servicio='NACIONAL';
					}
				}
				break;
		}
		echo "<!--SERVICIO: {$this->_servicio}-->";
	}
}