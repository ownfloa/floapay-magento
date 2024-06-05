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

namespace FLOA\Payment\Model;

class FloaPayLogger
{
    /** @var \Zend_Log */
    private $logger;

    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/floa-' . date('Ymd') . '.txt');
        $this->logger = new \Zend_Log();
        $this->logger->addWriter($writer);
    }

    public function info($message)
    {
        $this->logger->info($message);
    }

    public function debug($message)
    {
        $this->logger->debug($message);
    }
}
