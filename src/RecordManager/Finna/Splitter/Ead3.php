<?php

/**
 * EAD 3 Splitter Class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2012-2021.
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
 * @author   Jukka Lehmus <jlehmus@mappi.helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManager\Finna\Splitter;

/**
 * EAD 3 Splitter Class
 *
 * This is a class for splitting EAD 3 records.
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Jukka Lehmus <jlehmus@mappi.helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class Ead3 extends \RecordManager\Base\Splitter\Ead3
{
    /**
     * Get archive title
     *
     * @return string
     */
    protected function getArchiveTitle(): string
    {
        $defaultTitle = '';
        foreach ($this->doc->archdesc->did->unittitle ?? [] as $title) {
            $attr = $title->attributes();
            if ('' === $defaultTitle) {
                $defaultTitle = (string)$title;
            }
            if (!$attr->lang || in_array($attr->lang, ['fi', 'fin'])) {
                return (string)$title;
            }
        }
        return $defaultTitle;
    }

    /**
     * Get parent unit id for prepending to parent title
     *
     * @param \SimpleXMLElement $parentDid Parent did
     *
     * @return string
     */
    protected function getParentUnitId(\SimpleXMLElement $parentDid): string
    {
        $defaultId = '';
        $ids = [];
        foreach ($parentDid->unitid ?? [] as $unitId) {
            if ('' === $defaultId) {
                $defaultId = (string)$unitId;
            }
            if ((string)$unitId->attributes()->label === 'Analoginen') {
                $ids[] = (string)$unitId;
            }
        }

        $pid = $ids ? implode('+', $ids) : $defaultId;
        $fromLastSlash = strrchr($pid, '/');
        if (false !== $fromLastSlash) {
            $pid = substr($fromLastSlash, 1);
        }

        return $pid;
    }
}
