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

namespace FLOA\Payment\Model\Config\Source;

class ShippingMethodsMage extends \Magento\Framework\View\Element\Html\Select
{

    /**
     * SetInputName
     *
     * @param string $value
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
 
    /**
     * SetInputId
     *
     * @param string $value
     * @return StartRangeColumn
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }
 
    /**
     * ToHtml
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }
 
    /**
     * GetSourceOptions
     *
     * @return array
     */
    private function getSourceOptions()
    {
        $objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
        $activeShipping  = $objectManager->create(\Magento\Shipping\Model\Config::class)->getAllCarriers();
        $scopeConfig     = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $methods = [];
        foreach ($activeShipping as $shippingCode => $shippingModel) {
            $carrierTitle = $scopeConfig->getValue('carriers/'. $shippingCode.'/title');
            $methods[] = ['value'=>$shippingCode,'label'=>$carrierTitle];
        }
        return $methods;
    }
}
