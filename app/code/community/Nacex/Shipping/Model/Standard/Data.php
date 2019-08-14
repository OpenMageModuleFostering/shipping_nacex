<?php
class Nacex_Shipping_Model_Standard_Data {

	public function getRegion($topcode,$compare=null){
		$region	=	null;
		$ind=substr($topcode,0,2);
		foreach($this->getRegions() as $id => $val){
			if(in_array($ind,$val)){
				$region	=	$id;
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
		return $region;
	}

	/*	
	*	Setea el tipo de servicio segun la ubicacion del cliente 
	*	con respecto a la tienda
	*/
	public function setServicio($_frompcode,$_topcode){
		$code_store	=	substr($_frompcode,0,2);
		$_topcode	=	substr($_topcode,0,2);
		$servicio	=	'';
		if($code_store == '07' || substr($_topcode,0,2) == '07'){
				if($this->getRegion($code_store,$_topcode)){
					// PRECIO INTRAISLAS
					return	'INTRAISLAS';
				} else {
					// PRECIO NACIONAL PARA TODA ESPAÑA
					return	'NACIONAL_BALEARES';
				}
		} else {
			// PARA LA TIENDA EN EL RESTO DE ESPAÑA
				if($code_store == $_topcode){
					// PRECIO PROVINCIAL
					return	'PROVINCIAL';
				} else	{
					$id_region = $this->getRegion($_topcode,$code_store);
					if($id_region == true){
						// PRECIO REGIONAL PARA PROVINCIAS DE LA MISMA REGION
						return	'REGIONAL';
					} else {
						// PRECIO NACIONAL PARA TODA ESPAÑA
						return	'NACIONAL';
					}
				}
		}
	}

	public function getRegions(){
		return array(
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
	}

	public function getPrecio($params){
		$code	=	(isset($params['code'])) ? $params['code'] : '';
		$servicio	=	$params['servicio'];
		$sweight	=	$params['sweight'];
		if(!$servicio) Mage::log(Mage::helper('nacex')->__('Servicio no disponible, alerta en linea: #%s de "%s"',__LINE__,__FILE__),Zend_Log::ALERT);
		if(!$sweight) Mage::log(Mage::helper('nacex')->__('Peso no disponible. Se tomara el importe minimo del servicio, para una mayor precision por favor cargue el peso de los productos'),Zend_Log::ALERT);
		$params	=	array('price'=>0);
		try {
			switch($servicio){
				case 'PROVINCIAL':
					$params['shipping_method'] = 'PACK';
					if($sweight <= '2000') {
						$params['shipping_method'] = 'BAG';
						$params['price'] = 6.01;
					} elseif(($sweight > '2000') && ($sweight <='5000')){
						$params['price'] = 6.74;
					} elseif(($sweight > '5000') && ($sweight <='10000')){
						$params['price'] = 8.49;
					} else {
						$params['price'] = 8.49;
						$peso=$sweight - 10000;
						$params['price'] += (ceil($peso / 5000)) * 1.91;
					}
					break;
				case 'REGIONAL':
					$params['shipping_method'] = 'PACK';
					if($sweight <= '2000') {
						$params['shipping_method'] = 'BAG';
						$params['price'] = 7.11;
					} elseif(($sweight > '2000') && ($sweight <='5000')){
						$params['price'] = 8.76;
					} elseif(($sweight > '5000') && ($sweight <='10000')){
						$params['price'] = 11.78;
					} else {
						$params['price'] = 11.78;
						$peso=$sweight - 10000;
						$params['price'] += (ceil($peso / 5000)) * 3.62;
					}
					break;
				case 'NACIONAL':
					$params['shipping_method'] = 'PACK';
					if($sweight <= '2000') {
						$params['shipping_method'] = 'BAG';
						$params['price'] = 7.58;
					} elseif(($sweight > '2000') && ($sweight <='5000')){
						$params['price'] = 9.57;
					} elseif(($sweight > '5000') && ($sweight <='10000')){
						$params['price'] = 13.54;
					} else {
						$params['price'] = 13.54;
						$peso=$sweight - 10000;
						$params['price'] += (ceil($peso / 5000)) * 3.96;
					}
					break;
				case 'NACIONAL_BALEARES':
					$params['shipping_method'] = 'PACK';
					if($sweight <= '2000') {
						$params['shipping_method'] = 'BAG';
						$params['price'] = 10.56;
					} elseif(($sweight > '2000') && ($sweight <='4000')){
						$params['price'] = 16.48;
					} else {
						$params['price'] = 16.48;
						$peso=$sweight - 4000;
						$params['price'] += (ceil($peso / 2000)) * 4;
					}
					break;
				case 'INTRAISLAS':
					$params['shipping_method'] = 'PACK';
					if($sweight <= '2000') {
						$params['shipping_method'] = 'BAG';
						$params['price'] = 6.18;
					} elseif(($sweight <= '5000')){
						$params['price'] = 6.95;
					} elseif(($sweight <='10000')){
						$params['price'] = 8.76;
					} else {
						$params['price'] = 8.76;
						$peso=$sweight - 10000;
						$params['price'] += (ceil($peso / 5000)) * 1.99;
					}
					break;
				default:
					$params['shipping_method'] = 'BAG';
					$params['price'] = 6.01;
					break;
			}
		} catch (Exception $e) {
			Mage::logException($e);
		}
		switch($code){
			case 'naxgab':
				// En caso de ida  y vuelta duplica el valor
				$params['price'] *= 2;
				break;
		}
		return $params;
	}

	public function getProvincias(){
		return array(
			"01"=>"ALAVA",			"02"=>"ALBACETE",			"03"=>"ALICANTE",			"04"=>"ALMERIA",
			"33"=>"ASTURIAS",
			"05"=>"AVILA",			"06"=>"BADAJOZ",			"08"=>"BARCELONA",			"09"=>"BURGOS",			"10"=>"CACERES",			"11"=>"CADIZ",			"39"=>"CANTABRIA",			"12"=>"CASTELLON",			"51"=>"CEUTA",			"13"=>"CIUDAD REAL",			"14"=>"CORDOBA",			"15"=>"CORUÑA, LA",			"16"=>"CUENCA",			"17"=>"GIRONA",			"18"=>"GRANADA",			"19"=>"GUADALAJARA",			"20"=>"GUIPUZCOA",			"21"=>"HUELVA",			"22"=>"HUESCA",			"07"=>"ILLES BALEARS",			"23"=>"JAEN",			"24"=>"LEON",
			"25"=>"LLEIDA",			"27"=>"LUGO",			"28"=>"MADRID",			"29"=>"MALAGA",			"52"=>"MELILLA",			"30"=>"MURCIA",			"31"=>"NAVARRA",			"32"=>"OURENSE",			"34"=>"PALENCIA",			"35"=>"PALMAS, LAS",			"36"=>"PONTEVEDRA",			"26"=>"RIOJA, LA",			"37"=>"SALAMANCA",			"38"=>"SANTA CRUZ DE TENERIFE",			"40"=>"SEGOVIA",			"41"=>"SEVILLA",			"42"=>"SORIA",			"43"=>"TARRAGONA",
			"44"=>"TERUEL",			"45"=>"TOLEDO",			"46"=>"VALENCIA",			"47"=>"VALLADOLID",			"48"=>"VIZCAYA",			"49"=>"ZAMORA",			"50"=>"ZARAGOZA");
	}
}