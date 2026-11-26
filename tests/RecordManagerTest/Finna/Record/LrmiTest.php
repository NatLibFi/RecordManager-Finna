<?php

/**
 * LRMI Record Driver Test Class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManagerTest\Finna\Record;

use RecordManager\Finna\Record\Lrmi;

/**
 * LRMI Record Driver Test Class
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class LrmiTest extends \RecordManagerTest\Base\Record\RecordTestBase
{
    /**
     * Test LRMI media types
     *
     * @return void
     */
    public function testMediaTypes()
    {
        $record = $this->createRecord(
            Lrmi::class,
            'lrmi_media_types.xml',
            [],
            'Finna',
            [$this->createMock(\RecordManager\Base\Http\HttpService::class)]
        );
        $fields = $record->toSolrArray();
        $this->assertEquals(
            [
                'video/quicktime',
                'audio/x-wav',
                'video/mp4',
            ],
            $fields['media_type_str_mv']
        );
    }

    /**
     * Test LRMI titles
     *
     * @return void
     */
    public function testTitles()
    {
        $record = $this->createRecord(
            Lrmi::class,
            'lrmi1.xml',
            [],
            'Finna',
            [$this->createMock(\RecordManager\Base\Http\HttpService::class)]
        );
        $fields = $record->toSolrArray();
        $this->assertEquals('Opetuksen ja oppimisen suunnittelu, Learning Design', $fields['title']);
        $this->assertEquals('Opetuksen ja oppimisen suunnittelu, Learning Design', $fields['title_full']);
        $this->assertEquals('Opetuksen ja oppimisen suunnittelu, Learning Design', $fields['title_short']);
        $this->assertEquals('opetuksen ja oppimisen suunnittelu learning design', $fields['title_sort']);
        $this->assertEquals('Designing Learning Processes', $fields['title_en_txt']);
        $this->assertEquals('Opetuksen ja oppimisen suunnittelu, Learning Design', $fields['title_fi_txt']);
        $this->assertEquals('', $fields['title_se_txt']);
        $this->assertEquals('Planering av undevisning och lärande', $fields['title_sv_txt']);
    }
}
