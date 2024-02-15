<?php

/**
 * Finna QDC Record Driver Test Class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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

namespace RecordManagerTest\Finna\Record;

use RecordManager\Finna\Record\Qdc;

/**
 * Finna QDC Record Driver Test Class
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class QdcTest extends \RecordManagerTest\Base\Record\RecordTestBase
{
    /**
     * Test dateranges.
     *
     * @return void
     */
    public function testDateRanges()
    {
        $expected = [
            '[1800-01-01 TO 1801-12-31]',
            '[1802-01-01 TO 1803-12-31]',
            '[1804-01-01 TO 1805-12-31]',
            '[1806-01-01 TO 1807-12-31]',
            '[1808-01-01 TO 1809-12-31]',
            '[1810-01-01 TO 1810-12-31]',
            '[1811-01-01 TO 1811-12-31]',
            '[1812-01-01 TO 1812-12-31]',
            '[1813-01-01 TO 1813-12-31]',
            '[1814-01-01 TO 1814-12-31]',
            '[1819-01-01 TO 1820-12-31]',
            '[1821-01-01 TO 1822-12-31]',
            '[1823-01-01 TO 1823-12-31]',
            '[-2020-01-01 TO 0015-12-31]',
            '[-2022-01-01 TO -0021-12-31]',
            '[-2024-01-01 TO -0023-12-31]',
            '[-2026-01-01 TO -2026-12-31]',
            '[2027-01-01 TO 2027-12-31]',
            '[2028-01-01 TO 2028-12-31]',
            '[2029-01-01 TO 2029-12-31]',
            '[0004-01-01 TO 0004-12-31]',
            '[3006-01-01 TO 3006-12-31]',
            '[0008-01-01 TO 0008-12-31]',
            '[3010-01-01 TO 3010-12-31]',
        ];
        $fields = $this->createRecord(
            Qdc::class,
            'qdc_dateranges.xml',
            [],
            'Finna',
            [
                $this->createMock(\RecordManager\Base\Http\ClientManager::class),
            ]
        );
        $fields = $fields->toSolrArray()['search_daterange_mv'];
        $this->assertEquals($expected, $fields);
    }

    /**
     * Test media types
     *
     * @return void
     */
    public function testMediaTypes()
    {
        $fields = $this->createRecord(
            Qdc::class,
            'qdc_media_types.xml',
            [],
            'Finna',
            [
                $this->createMock(\RecordManager\Base\Http\ClientManager::class),
            ]
        );
        $fields = $fields->toSolrArray();

        $this->assertEquals(
            [
                'application/vnd.ms-powerpoint',
                'image/jpeg',
                'image/png',
                'video/mp4',
            ],
            $fields['media_type_str_mv']
        );
    }

    /**
     * Test QDC processing warnings handling
     *
     * @return void
     */
    public function testQdcLanguageWarnings()
    {
        $record = $this->createRecord(
            Qdc::class,
            'qdc_language_warnings.xml',
            [],
            'Finna',
            [$this->createMock(\RecordManager\Base\Http\ClientManager::class)]
        );
        $fields = $record->toSolrArray();
        $this->compareArray(
            [
                'unhandled language Veryodd',
                'unhandled language verylonglanguagehere',
                'unhandled language EnGb',
                'unhandled language caT',
            ],
            $record->getProcessingWarnings(),
            'getProcessingWarnings'
        );
        $this->compareArray(
            [
                'fi',
            ],
            $fields['language'],
            'LanguageCheckAfterWarnings'
        );
    }
}
