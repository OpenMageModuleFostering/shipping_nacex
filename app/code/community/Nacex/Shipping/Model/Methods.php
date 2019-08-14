<?php
class Nacex_Shipping_Model_Methods {
	public function toOptionArray() {
		return array(
			array('value'=>'NAXJOW',	'label'=>Mage::helper('nacex')->__('Nacex: Solo Ida')),
			array('value'=>'NAXGAB',	'label'=>Mage::helper('nacex')->__('Nacex: Ida y Vuelta')),
		);
	}
}