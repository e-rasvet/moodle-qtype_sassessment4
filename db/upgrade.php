<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Essay question type upgrade code.
 *
 * @package    qtype
 * @subpackage sassessment
 * @copyright  2018 Kochi-Tech.ac.jp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the sassessment question type.
 *
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_sassessment_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2019041800) {
        $table = new xmldb_table('qtype_sassessment_options');

        $field = new xmldb_field('correctfeedback', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('correctfeedbackformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('partiallycorrectfeedback', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('partiallycorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('incorrectfeedback', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('incorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('stt_core', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('auto_score', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019041800, 'qtype', 'sassessment');

    }

    if ($oldversion < 2019091800) {
        $table = new xmldb_table('qtype_sassessment_options');

        $field = new xmldb_field('speechtotextlang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'en-US',
                'incorrectfeedbackformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019091800, 'qtype', 'sassessment');
    }

    /*
     * Intermediate feedback fields
     */

    if ($oldversion < 2020021400) {
        $table = new xmldb_table('qtype_sassessment_options');

        $field = new xmldb_field('immediatefeedback', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('immediatefeedbackpercent', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020021400, 'qtype', 'sassessment');
    }

    /*
     * Auto-score a speaking response
     */

    if ($oldversion < 2020071000) {
        $table = new xmldb_table('qtype_sassessment_options');

        $field = new xmldb_field('stt_core', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'google', 'speechtotextlang');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('auto_score', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'target_teacher', 'stt_core');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020071000, 'qtype', 'sassessment');
    }

    /*
     * Add open end response if fields
     */
    if ($oldversion < 2020081000) {
        $table = new xmldb_table('qtype_sassessment_options');

        for ($i=1; $i <=5; $i++) {
            $field = new xmldb_field('spokenpoints'.$i.'_status', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('spokenpoints'.$i.'_words', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('spokenpoints'.$i.'_points', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2020081000, 'qtype', 'sassessment');
    }

    return true;
}
