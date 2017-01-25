#!/usr/bin/php
<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/
/*
 * File Contributed By: Open Source Consulting, S.A.   San Jose, Costa Rica.
 * http://osc.co.cr
 */

// Calculates percent complete statistics for all locales.
// Must be run from tools/i18n directory.

$root_dir = '../../interface/locale';
if (count($argv) > 1) {
    $root_dir = $argv[1];
}

$d = opendir($root_dir);

if ($d) {
    echo "calculating locale statistics...\n";

    $outpath = $root_dir . '/' . 'locale_stats.txt';
    $fh = fopen($outpath, 'w');

    $ignore_dirs = array('.', '..', 'CVS');
    while (false !== ($file = readdir($d))) {
        if (is_dir($root_dir . '/' . $file) && !in_array($file, $ignore_dirs)) {
            $stats = calcStats($root_dir, $file);
            $pct = $stats['pct_complete'];
            $team = $stats['team'];
            fwrite($fh, "$file|$pct|$team\n");
        }
    }
    closedir($d);

    fclose($fh);

    echo "done. stats saved in $outpath\n";
}

function calcStats($root_dir, $locale)
{
    $messages = 0;
    $translations = 0;
    $fuzzy = 0;

    $team = '';

    $path = $root_dir . '/' . $locale . '/LC_MESSAGES/messages.po';
    // echo "<li><b>$path</b>";
    if (file_exists($path)) {
        $lines = file($path);

        $in_msgid = false;
        $in_msgstr = false;
        $found_translation = false;
        $found_msg = false;
        foreach ($lines as $line) {
            // ignore comment lines
            if ($line[0] == '#') {
                continue;
            }

            // Parse out the contributors.
            if (strstr($line, '"Language-Team: ')) {
                $endpos = strpos($line, '\n');
                if ($endpos === false) {
                    $endpos = strlen($line) - 2;
                }
                $len = strlen('"Language-Team: ');
                $field = substr($line, $len, $endpos - $len);
                $names = explode(',', $field);
                foreach ($names as $name) {
                    if ($name != 'none') {
                        if ($team != '') {
                            $team .= ',';
                        }
                        $team .= trim($name);
                    }
                }
            }

            if (strstr($line, 'msgid "')) {
                $in_msgid = true;
                $in_msgstr = false;
                $found_msg = false;
                $found_translation = false;
            }
            if ($in_msgid && !$found_msg && strstr($line, '"') && !strstr($line, '""')) {
                // echo "<li>msgid: $line";
                $found_msg = true;
                $messages++;
            } elseif (strstr($line, 'msgstr "')) {
                $in_msgstr = true;
                $in_msgid = false;
            }
            if ($in_msgstr && $found_msg && !$found_translation) {
                if (strstr($line, '"') && !strstr($line, '""')) {
                    // echo "<li>msgstr: $line";
                    $translations++;
                    $found_translation = true;
                }
            } elseif (strstr($line, '#, fuzzy')) {
                $fuzzy++;
            }
        }
    }
    $translations -= $fuzzy;
    $pct_complete = $messages ? (int)(($translations / $messages) * 100) : 0;

    return array('pct_complete' => $pct_complete, 'team' => $team);
}


?>
