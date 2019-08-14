<?php
/**
 * Magento Nacex Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Nacex
 * @package    Nacex_Shipping
 * @copyright  
 * @author	   
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Nacex_Shipping_Model_Standard_Weightunits {
	public function toOptionArray() {
		return array(
			array('value'=>1,		'label'=>Mage::helper('nacex')->__('Gramos')),
			array('value'=>1000,	'label'=>Mage::helper('nacex')->__('Kilogramos')),
		);
	}
}