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

use Magento\Framework\View\Element\Html\Select;

class ShippingMethodsFloa extends Select
{
    /**
     * SetInputName
     *
     * @param string $value
     *
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
     *
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
        $mappingData = [
            ['value' => 'CDS', 'label' => 'Colissimo direct'],
            ['value' => 'CHR', 'label' => 'Chronopost'],
            ['value' => 'COL', 'label' => 'Colissimo'],
            ['value' => 'CRE', 'label' => 'Chronopost/Chronorelais'],
            ['value' => 'KIA', 'label' => 'Kiala'],
            ['value' => 'IMP', 'label' => 'Printed and sent to home'],
            ['value' => 'LSP', 'label' => 'Delivery service plus'],
            ['value' => 'MOR', 'label' => 'Mory'],
            ['value' => 'TNT', 'label' => 'TNT'],
            ['value' => 'TRP', 'label' => 'Transporter'],
            ['value' => 'AE1', 'label' => 'Easydis error'],
            ['value' => 'EA1', 'label' => 'Easydis'],
            ['value' => 'KIB', 'label' => 'KIB'],
            ['value' => 'TNB', 'label' => 'TNT Belgium'],
            ['value' => 'EXP', 'label' => 'Express delivery'],
            ['value' => 'AGE', 'label' => 'Agediss'],
            ['value' => 'EMP', 'label' => 'Carried away'],
            ['value' => 'M30', 'label' => 'Carried away -30'],
            ['value' => 'ADX', 'label' => 'Adrexo'],
            ['value' => 'EY1', 'label' => 'Carried away -30 Easydis'],
            ['value' => 'VIR', 'label' => 'Virtuel'],
            ['value' => 'REG', 'label' => 'Recommandé'],
            ['value' => 'STD', 'label' => 'Normal'],
            ['value' => 'TRK', 'label' => 'Followed'],
            ['value' => 'PRM', 'label' => 'Premium Easydis'],
            ['value' => 'RDO', 'label' => 'Confort (SOGEP) Easydis'],
            ['value' => 'RCO', 'label' => 'Package relay (SOGEP) Cestas'],
            ['value' => 'SO1', 'label' => 'SoColissimo overseas zone 1'],
            ['value' => 'SO2', 'label' => 'SoColissimo overseas zone 2'],
            ['value' => 'RIM', 'label' => 'Immediate pick up in store'],
            ['value' => 'LDR', 'label' => 'LDR'],
            ['value' => 'MAG', 'label' => 'Store delivery'],
            ['value' => 'RDE', 'label' => 'Eco (SOGEP) Easydis'],
            ['value' => 'REL', 'label' => 'Mondial Relay'],
            ['value' => 'FDR', 'label' => 'Direct relay supplier'],
            ['value' => 'TNX', 'label' => 'TNT express relay'],
            ['value' => 'EMX', 'label' => 'Express'],
            ['value' => 'CHX', 'label' => 'Carried away Chronopost relay'],
            ['value' => 'CSX', 'label' => 'Carried away Chronopost deposit'],
            ['value' => 'LDS', 'label' => 'Standard home delivery'],
            ['value' => 'LDE', 'label' => 'Express home delivery'],
            ['value' => 'LCS', 'label' => 'Standard center delivery'],
            ['value' => 'LCE', 'label' => 'Express center delivery'],
            ['value' => 'GLS', 'label' => 'GLS'],
            ['value' => 'STU', 'label' => 'Stuart'],
            ['value' => 'UPS', 'label' => 'UPS'],
            ['value' => 'LCK', 'label' => 'Lockers'],
            ['value' => 'DRO', 'label' => 'Drone delivery'],
            ['value' => 'OT1', 'label' => 'Other shipping method #1'],
            ['value' => 'OT2', 'label' => 'Other shipping method #2'],
            ['value' => 'OT3', 'label' => 'Other shipping method #3'],
        ];

        return $mappingData;
    }
}
