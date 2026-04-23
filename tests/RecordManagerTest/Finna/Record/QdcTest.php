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
     * Test QDC record handling
     *
     * @return void
     */
    public function testQdc1()
    {
        $record = $this->createRecord(
            Qdc::class,
            'qdc1.xml',
            [],
            'Finna',
            [$this->createMock(\RecordManager\Base\Http\HttpService::class)]
        );
        $fields = $record->toSolrArray();
        unset($fields['fullrecord']);

        $expected = [
            'record_format' => 'qdc',
            'ctrlnum' => [
                '10138_331330',
            ],
            'allfields' => [
                'Urine : The potential, value chain and its sustainable management',
                'Is that even a real title',
                'Ei',
                'Joo',
                'Viskari, Eeva-Liisa',
                'Lehtoranta, Suvi',
                'Malila, Riikka',
                'urine',
                'fertilizer',
                'value chain',
                'agriculture',
                'nutrient recovery',
                'virtsa',
                'lannoitteet',
                'ravinteet',
                'uudelleenkäyttö',
                'maatalous',
                '2021-06-16T06:31:44Z',
                '2021',
                'Article',
                'okm_type',
                'okm_type_2',
                'other_type',
                'Eeva-Liisa Viskari, Suvi Lehtoranta, Riikka Malila. Urine : The potential, value chain and its'
                    . ' sustainable management. Sanitation Value Chain (2021) 5, 1, pages 10-12. '
                    . 'https://doi.org/10.34416/svc.00029',
                '2432-5058',
                'http://hdl.handle.net/10138/331330',
                'https://doi.org/10.34416/svc.00029',
                'en',
                'Sanitation Value Chain 5:1',
                'Sanitation Research Collection',
                'CC BY-NC-ND 4.0',
                'Sanitation Project, Research Institute for Humanity and Nature',
                'http://dx.doi.org/https://doi.org/10.34416/svc.00029',
                '10138_331330',
            ],
            'language' => [
                'en',
            ],
            'format' => 'Article',
            'format_ext_str_mv' => 'Article',
            'author' => [
                'Viskari, Eeva-Liisa',
                'Lehtoranta, Suvi',
                'Malila, Riikka',
            ],
            'author2' => [],
            'author_corporate' => [],
            'author_sort' => 'Viskari, Eeva-Liisa',
            'author_facet' => [
                'Viskari, Eeva-Liisa',
                'Lehtoranta, Suvi',
                'Malila, Riikka',
            ],
            'hierarchy_parent_title' => [
                'Sanitation Research Collection',
            ],
            'title_full' => 'Urine : The potential, value chain and its sustainable management',
            'title' => 'Urine : The potential, value chain and its sustainable management',
            'title_en_txt' => 'Urine : The potential, value chain and its sustainable management',
            'title_fi_txt' => 'Joo',
            'title_se_txt' => '',
            'title_sv_txt' => '',
            'title_short' => 'Urine',
            'title_sub' => 'The potential, value chain and its sustainable management',
            'title_sort' => 'urine the potential value chain and its sustainable management',
            'title_alt' => [
                'Is that even a real title',
                'Ei',
                'Joo',
            ],
            'publisher' => [
                'Sanitation Project, Research Institute for Humanity and Nature',
            ],
            'publishDate' => [
                '2021',
            ],
            'publishDateSort' => '2021',
            'publishDateRange' => [
                '[2021-01-01 TO 2021-12-31]',
            ],
            'main_date_str' => '2021',
            'main_date' => '2021-01-01T00:00:00Z',
            'publication_daterange' => '[2021-01-01 TO 2021-12-31]',
            'search_daterange_mv' => [
                '[2021-01-01 TO 2021-12-31]',
            ],
            'era' => [],
            'era_facet' => [],
            'geographic' => [],
            'geographic_facet' => [],
            'location_geo' => [],
            'usage_rights_str_mv' => [
                'CC BY-NC-ND 4.0',
            ],
            'usage_rights_ext_str_mv' => [
                'CC BY-NC-ND 4.0',
            ],
            'source_str_mv' => '__unit_test_no_source__',
            'datasource_str_mv' => '__unit_test_no_source__',
            'isbn' => [],
            'issn' => [
                '2432-5058',
            ],
            'doi_str_mv' => [
                '10.34416/svc.00029',
            ],
            'topic_facet' => [
                'urine',
                'fertilizer',
                'value chain',
                'agriculture',
                'nutrient recovery',
                'virtsa',
                'lannoitteet',
                'ravinteet',
                'uudelleenkäyttö',
                'maatalous',
            ],
            'topic' => [
                'urine',
                'fertilizer',
                'value chain',
                'agriculture',
                'nutrient recovery',
                'virtsa',
                'lannoitteet',
                'ravinteet',
                'uudelleenkäyttö',
                'maatalous',
            ],
            'url' => [
                'http://hdl.handle.net/10138/331330',
                'https://doi.org/10.34416/svc.00029',
                'http://dx.doi.org/https://doi.org/10.34416/svc.00029',
            ],
            'online_urls_str_mv' => [],
            'media_type_str_mv' => [],
            'file_identifier_str_mv' => [],
            'thumbnail' => '',
            'contents' => [],
            'description' => '',
            'series' => [
                'Sanitation Value Chain 5:1',
            ],
            'fulltext' => '',
          ];

        $this->compareArray($expected, $fields, 'toSolrArray');

        $keys = $record->getWorkIdentificationData();

        $expected = [
            [
                'authors' => [
                    [
                        'type' => 'author',
                        'value' => 'Viskari, Eeva-Liisa',
                    ],
                ],
                'authorsAltScript' => [
                ],
                'titles' => [
                    [
                        'type' => 'title',
                        'value' => 'urine the potential value chain and its'
                            . ' sustainable management',
                    ],
                    [
                        'type' => 'title',
                        'value' => 'Urine : The potential, value chain and its'
                            . ' sustainable management',
                    ],
                ],
                'titlesAltScript' => [
                ],
            ],
        ];

        $this->compareArray($expected, $keys, 'getWorkIdentificationData');
    }

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
                $this->createMock(\RecordManager\Base\Http\HttpService::class),
            ]
        );
        $fields = $fields->toSolrArray();
        $this->assertEquals($expected, $fields['search_daterange_mv']);
        $this->assertEquals($expected, $fields['publishDateRange']);
    }

    /**
     * Test coverage.
     *
     * @return void
     */
    public function testCoverage()
    {
        $spatial = [
            'Helsinki',
            'Vantaa',
        ];
        $temporal = [
            '2010',
            '2010-luku',
        ];
        $geocoding = [
            'POINT(27.1826451 63.5694237)',
            'POINT(20.0 60.0)',
        ];
        $fields = $this->createRecord(
            Qdc::class,
            'qdc_dateranges.xml',
            [],
            'Finna',
            [
                $this->createMock(\RecordManager\Base\Http\HttpService::class),
            ]
        );
        $fields = $fields->toSolrArray();
        $this->assertEquals($spatial, $fields['geographic']);
        $this->assertEquals($spatial, $fields['geographic_facet']);
        $this->assertEquals($geocoding, $fields['location_geo']);
        $this->assertEquals($temporal, $fields['era']);
        $this->assertEquals($temporal, $fields['era_facet']);
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
                $this->createMock(\RecordManager\Base\Http\HttpService::class),
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
     * Test getResourceIdentifiers
     *
     * @return void
     */
    public function testGetResourceIdentifiers()
    {
        $fields = $this->createRecord(
            Qdc::class,
            'qdc_media_types.xml',
            [],
            'Finna',
            [
                $this->createMock(\RecordManager\Base\Http\HttpService::class),
            ]
        );
        $fields = $fields->toSolrArray();
        $this->assertEquals(
            [
                'powerpoint_1',
                'jpg_2',
            ],
            $fields['file_identifier_str_mv']
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
            [$this->createMock(\RecordManager\Base\Http\HttpService::class)]
        );
        $fields = $record->toSolrArray();
        $this->compareArray(
            [
                'unhandled language Veryodd',
                'unhandled language verylonglanguagehere',
                'unhandled language EnGb',
                'unhandled language caT',
                'unhandled language po,tt',
                'unhandled language ,',
                'unhandled language EMPTY_VALUE',
            ],
            $record->getProcessingWarnings(),
            'getProcessingWarnings'
        );
        $this->compareArray(
            [
                'fi',
                'jp',
                'sv',
                'en',
                'nr',
            ],
            $fields['language'],
            'LanguageCheckAfterWarnings'
        );
    }

    /**
     * Test original identifiers
     *
     * @return void
     */
    public function testOriginalIds(): void
    {
        $fields = $this->createRecord(
            Qdc::class,
            'qdc_original_ids.xml',
            [],
            'Finna',
            [
                $this->createMock(\RecordManager\Base\Http\HttpService::class),
            ]
        );
        $fields = $fields->toSolrArray();

        $this->assertEquals(
            [
                '10000_12345',
                'original/id',
            ],
            $fields['ctrlnum']
        );
    }
}
