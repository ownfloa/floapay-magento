<?php
/**
 * 2021 Floa BANK
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    FLOA Bank
 * @copyright 2021 FLOA Bank
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace FLOA\Payment\Block\Adminhtml\System\Config\Form\Field;

class ShippingMethods extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_mageItemRenderer;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_floaItemRenderer;

    /**
     * Construct
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * _construct
     *
     * @return void
     */
    protected function _construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
        $cacheManager = $objectManager->get(\Magento\Framework\App\Cache\Manager::class);

        $deliveryMethodsMap = $scopeConfig->getValue('floa/delivery_methods/mapping');
        $actualConfig = json_decode($deliveryMethodsMap ? $deliveryMethodsMap : '[]', true);
        if (!$actualConfig) {
            $carriers = $objectManager->create(\Magento\Shipping\Model\Config::class)->getAllCarriers();
            $finalDataBeforeUpdate = [];
            if ($carriers) {
                foreach ($carriers as $carrierCode => $carrierInfo) {
                    $finalDataBeforeUpdate['option_extra_attr_' . $this->_getShippingMageRenderer()->calcOptionHash($carrierCode)] = [
                        'carrier_name' => $carrierCode,
                        'mapping_code' => '',
                    ];
                }
            }
            $configWriter->save('floa/delivery_methods/mapping', json_encode($finalDataBeforeUpdate));
            $cacheManager->flush(['config']);
        }
        $this->addColumn(
            'carrier_name',
            [
                'label' => __('Carrier Name'),
                'renderer' => $this->_getShippingMageRenderer(),
            ]
        );
        $this->addColumn(
            'mapping_code',
            [
                'label' => __('Mapping Code'),
                'renderer' => $this->_getShippingFloaRenderer(),
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

    /**
     * _getShippingMageRenderer
     *
     * @return \Magento\Framework\Data\Form\Element\Factory
     */
    protected function _getShippingMageRenderer()
    {
        if (!$this->_mageItemRenderer) {
            $this->_mageItemRenderer = $this->getLayout()->createBlock(
                \FLOA\Payment\Model\Config\Source\ShippingMethodsMage::class,
                '',
                ['is_render_to_js_template' => true]
            );
        }

        return $this->_mageItemRenderer;
    }

    /**
     * _getShippingFloaRenderer
     *
     * @return \Magento\Framework\Data\Form\Element\Factory
     */
    protected function _getShippingFloaRenderer()
    {
        if (!$this->_floaItemRenderer) {
            $this->_floaItemRenderer = $this->getLayout()->createBlock(
                \FLOA\Payment\Model\Config\Source\ShippingMethodsFloa::class,
                '',
                ['is_render_to_js_template' => true]
            );
        }

        return $this->_floaItemRenderer;
    }
}
