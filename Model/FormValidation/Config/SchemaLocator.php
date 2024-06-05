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

class SchemaLocator implements \Magento\Framework\Config\SchemaLocatorInterface
{
    /** XML schema for config file */
    public const CONFIG_FILE_SCHEMA = 'form_validation.xsd';

    /** Path to corresponding XSD file with validation rules for merged config
     * @var string
     */
    protected $schema = null;

    /** Path to corresponding XSD file with validation rules for separate config files
     * @var string
     */
    protected $perFileSchema = null;

    /**
     * Construct
     *
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $configDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'FLOA_Payment');
        $this->schema = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_FILE_SCHEMA;
        $this->perFileSchema = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_FILE_SCHEMA;
    }
    
    /**
     * GetSchema
     *
     * @return void
     */
    public function getSchema()
    {
        return $this->schema;
    }
    
    /**
     * GetPerFileSchema
     *
     * @return void
     */
    public function getPerFileSchema()
    {
        return $this->perFileSchema;
    }
}
