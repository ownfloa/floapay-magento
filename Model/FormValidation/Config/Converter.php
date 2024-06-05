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

namespace FLOA\Payment\Model\FormValidation\Config;

use Magento\Framework\Module\Dir;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    public const CONFIG_DIR = 'form-validation-fields' . DIRECTORY_SEPARATOR . 'country';

    /** @var \Magento\Framework\Xml\Parser */
    protected $parser;

    /** @var string */
    protected $configDir;
    
    /**
     * Construct
     *
     * @param \Magento\Framework\Xml\Parser $parser
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     * @return void
     */
    public function __construct(
        \Magento\Framework\Xml\Parser $parser,
        \Magento\Framework\Module\Dir\Reader $moduleReader
    ) {
        $this->parser = $parser;
        $configDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'FLOA_Payment');
        $this->configDir = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_DIR;
    }
    
    /**
     * Convert
     *
     * @param  mixed $source
     * @return void
     */
    public function convert($source)
    {
        $countryItems = $source->getElementsByTagName('country');

        $configs = [];
        foreach ($countryItems as $item) {
            /** @var \DOMNode $item */
            $iso = $item->attributes->getNamedItem('iso')->nodeValue;
            $validator = $item->attributes->getNamedItem('validator')->nodeValue;
            $config = $this->parser->load($this->configDir . DIRECTORY_SEPARATOR . $validator)->xmlToArray();
            $fields = $config['config']['_value']['fields']['field'];
            $configs[$iso] = $this->formatFields($fields);
        }
        return ['form-validation' => $configs];
    }

    /**
     * FormatFields
     *
     * @param array $fields
     * @return array
     */
    private function formatFields($fields)
    {
        $results = [];
        foreach ($fields as $field) {
            $results[$field['_attribute']['name']] = $field;
        }

        return $results;
    }
}
