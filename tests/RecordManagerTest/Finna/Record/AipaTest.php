<?php

/**
 * Finna AIPA Record Driver Test Class
 *
 * PHP version 8
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
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManagerTest\Finna\Record;

use RecordManager\Finna\Record\Aipa;

/**
 * Finna AIPA Record Driver Test Class
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class AipaTest extends \RecordManagerTest\Base\Record\RecordTestBase
{
    /**
     * Test AIPA record handling
     *
     * @return void
     */
    public function testAipa1()
    {
        $record = $this->createRecord(
            Aipa::class,
            'aipa1.xml',
            [],
            'Finna',
            [
                $this->createMock(\RecordManager\Base\Http\HttpService::class),
                null,
                $this->createMock(\RecordManager\Base\Record\PluginManager::class),
            ],
        );
        $fields = $record->toSolrArray();
        unset($fields['fullrecord']);

        $expected = [
            'allfields' => [
                'oai:aineistopaketit.finna.fi:6888',
                'Moottorisahat metsätyön välineenä Suomessa',
                '2025-10-08T12:09:17Z',
                'Ennen moottorisahaa puiden kaataminen ja katkominen tehtiin käsisahoilla ja kirveillä, '
                    . 'mikä oli hidasta ja fyysisesti raskasta.',
                'https://aineistopaketit.finna.fi/image.jpg',
                'Lusto – Suomen Metsämuseo',
                'fi',
                'aipa-research',
                'moottorisahat',
                'motorsågar',
                'mohtorsahát',
                'chain saws',
                'metsätyö',
                'skogsarbete',
                'vuovdebargu',
                'forest work',
                'Suomen Metsämuseo Lustossa on laaja moottorisahojen kokoelma '
                    . 'ja lisäksi muuta moottoorisahoihin liittyvää aineistoa',
                'Valtaosa Suomen Metsämuseo Luston kokoelman moottorisahoista sijoittuu 1960-80-luvuille.',
                '182',
                'node-6888',
            ],
            'author' => [
                'Lusto – Suomen Metsämuseo',
            ],
            'author2' => [],
            'author_facet' => [
                'Lusto – Suomen Metsämuseo',
            ],
            'author_sort' => 'Lusto – Suomen Metsämuseo',
            'author_corporate' => [],
            'contents' => [
                'Ennen moottorisahaa puiden kaataminen ja katkominen tehtiin käsisahoilla ja kirveillä, '
                    . 'mikä oli hidasta ja fyysisesti raskasta.',
            ],
            'ctrlnum' => [
                'node-6888',
            ],
            'datasource_str_mv' => '__unit_test_no_source__',
            'description' => 'Ennen moottorisahaa puiden kaataminen ja katkominen tehtiin käsisahoilla ja kirveillä, '
                . 'mikä oli hidasta ja fyysisesti raskasta.',
            'doi_str_mv' => [],
            'educational_material_type_str_mv' => 'aipa-research',
            'era_facet' => [],
            'era' => [],
            'file_identifier_str_mv' => [],
            'format' => 'aipa-research',
            'format_ext_str_mv' => 'aipa-research',
            'fulltext' => '',
            'geographic_facet' => [],
            'geographic' => [],
            'hierarchy_parent_title' => [],
            'isbn' => [],
            'issn' => [],
            'language' => [
                'fi',
            ],
            'location_geo' => [],
            'main_date_str' => '2025',
            'main_date' => '2025-01-01T00:00:00Z',
            'media_type_str_mv' => [],
            'online_urls_str_mv' => [],
            'publication_daterange' => '[2025-01-01 TO 2025-12-31]',
            'publishDate' => [
                '2025',
            ],
            'publishDateRange' => [
                '[2025-01-01 TO 2025-12-31]',
            ],
            'publishDateSort' => '2025',
            'publisher' => [],
            'record_format' => 'aipa',
            'search_daterange_mv' => [
                '[2025-01-01 TO 2025-12-31]',
            ],
            'series' => [],
            'source_str_mv' => '__unit_test_no_source__',
            'title_alt' => [],
            'title_en_txt' => '',
            'title_fi_txt' => 'Moottorisahat metsätyön välineenä Suomessa',
            'title_se_txt' => '',
            'title_sv_txt' => '',
            'title_full' => 'Moottorisahat metsätyön välineenä Suomessa',
            'title_short' => 'Moottorisahat metsätyön välineenä Suomessa',
            'title_sort' => 'moottorisahat metsätyön välineenä suomessa',
            'title_sub' => '',
            'title' => 'Moottorisahat metsätyön välineenä Suomessa',
            'topic_facet' => [
                'moottorisahat',
                'motorsågar',
                'mohtorsahát',
                'chain saws',
                'metsätyö',
                'skogsarbete',
                'vuovdebargu',
                'forest work',
            ],
            'topic_id_str_mv' => [
                0 => 'http://www.yso.fi/onto/yso/p10489',
                1 => 'http://www.yso.fi/onto/yso/p2982',
            ],
            'topic' => [
                'moottorisahat',
                'motorsågar',
                'mohtorsahát',
                'chain saws',
                'metsätyö',
                'skogsarbete',
                'vuovdebargu',
                'forest work',
            ],
            'url' => [],
            'usage_rights_str_mv' => [
                'restricted',
            ],
            'usage_rights_ext_str_mv' => [
                'restricted',
            ],
        ];

        $this->compareArray($expected, $fields, 'toSolrArray');
    }
}
