<?php

/**
 * Date support trait test
 *
 * PHP version 8.1
 *
 * Copyright (C) The National Library of Finland 2024.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManagerTest\Finna\Trait;

use RecordManager\Finna\Record\DateSupportTrait;

/**
 * Date support trait test
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class DateSupportTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for dateRangeToStr
     *
     * @return array
     */
    public static function dateRangeToStrProvider(): array
    {
        return [
          'valid date range' => [
            '[1900-01-01 TO 1981-01-01]',
            [
              '1900-01-01',
              '1981-01-01',
            ],
          ],
          'start date is invalid' => [
            '',
            [
              '2066-01-01',
              '1222-01-01',
            ],
          ],
          'end date is invalid' => [
            '',
            [
              '-1999-01-01',
              '2222-999-01',
            ],
          ],
          'both dates are same' => [
            '1960-01-01',
            [
              '1960-01-01',
              '1960-01-01',
            ],
          ],
          'both dates are only years' => [
            '',
            [
              '1950',
              '1981',
            ],
          ],
          'both dates are random strings' => [
            '',
            [
              'mau',
              'miu',
            ],
          ],
          'one date is a random string' => [
            '',
            [
              'mou',
              '1981-01-01',
            ],
          ],
          'range is an empty array' => [
            '',
            [],
          ],
          'dates are 1970-01-01' => [
            '1970-01-01',
            [
              '1970-01-01',
              '1970-01-01',
            ],
          ],
          'start year is negative' => [
            '[-0025-07-12 TO 1200-11-11]',
            [
              '-0025-07-12',
              '1200-11-11',
            ],
          ],
          'both years are negative' => [
            '[-0028-07-12 TO -0011-11-11]',
            [
              '-0028-07-12',
              '-0011-11-11',
            ],
          ],
          'dates are way into the future' => [
            '',
            [
              '25499-07-12',
              '50011-11-11',
            ],
          ],
          'odd dateranges' => [
            '',
            [
              '31.12.69',
              '6.1.70',
            ],
          ],
        ];
    }

    /**
     * Test dateRangeToStr
     *
     * @param string $expected Expected value
     * @param array  $range    Range
     *
     * @dataProvider dateRangeToStrProvider
     *
     * @return void
     */
    public function testDateRangeToStr(string $expected, array $range): void
    {
        $this->assertEquals($expected, $this->getAnonymousClass()->dateRangeToStr($range));
    }

    /**
     * Get an anonymous class for testing purely the trait
     *
     * @return mixed Anonymous class which uses DateSupportTrait
     */
    protected function getAnonymousClass(): mixed
    {
        return new class () {
            use DateSupportTrait;

            /**
             * Store warning
             *
             * @param string $msg Warning message
             *
             * @return void
             */
            public function storeWarning($msg): void
            {
                // Do nothing
            }
        };
    }
}
