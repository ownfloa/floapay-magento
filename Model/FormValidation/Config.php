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

namespace FLOA\Payment\Model\FormValidation;

class Config
{
    /** @var \Magento\Framework\Config\DataInterfacei */
    protected $_dataStorage;

    /**
     * Construct
     *
     * @param \Magento\Framework\Config\DataInterface $dataStorage
     */
    public function __construct(\Magento\Framework\Config\DataInterface $dataStorage)
    {
        $this->_dataStorage = $dataStorage;
    }
    
    /**
     * GetFormConfiguration
     *
     * @return void
     */
    public function getFormConfiguration()
    {
        return $this->_dataStorage->get('form-validation', []);
    }

    /**
     * GetFormConfig
     *
     * @param string $country
     * @return void
     */
    public function getFormConfig($country)
    {
        $configs = $this->getFormConfiguration();
        return isset($configs[$country]) ? $configs[$country] : [];
    }

    /**
     * GetJsFormCustomValidations
     *
     * @param string $country
     * @return array
     */
    public function getJsFormCustomValidations($country)
    {
        $result = [];
        $configs = $this->getFormConfiguration();
        $fields = isset($configs[$country]) ? $configs[$country] : [];
        foreach ($fields as $key => $value) {
            if (null !== $name = $this->getValue($value, 'js-validation-name')) {
                $result[] = [
                    'name'    => $name,
                    'pattern' => $this->getValue($value, 'pattern'),
                ];
            }
        }
        return $result;
    }
    
    /**
     * GetValue
     *
     * @param  mixed $config
     * @param  mixed $key
     * @return void
     */
    protected function getValue($config, $key)
    {
        return isset($config['_value'][$key]) ? $config['_value'][$key] : null;
    }
}
