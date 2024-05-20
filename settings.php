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
 * Global Settings
 *
 * @package   local_translate_courses
 * @copyright 2017 onwards, emeneo (www.emeneo.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $ADMIN, $USER, $DB, $PAGE;

$contextuser = context_user::instance($USER->id);

$viewcoursetemplates = has_capability('local/translate_courses:view', $contextuser);

$ADMIN->add(
    'courses',
    new admin_externalpage(
        'local_translate_courses',
        get_string('addcourse', 'local_translate_courses'),
        new moodle_url('/local/translate_courses/index.php'),
    )
);

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_translate_courses_settings', get_string('pluginname', 'local_translate_courses'));

    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $options = array(
            1 => get_string('jumpto_coursepage', 'local_translate_courses'),
            2 => get_string('jumpto_coursesettingspage', 'local_translate_courses')
        );

        $settings->add(
            new admin_setting_configselect(
                'local_translate_courses/jump_to',
                get_string('jumpto', 'local_translate_courses'),
                '',
                1,
                $options
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_translate_courses/mturl',
                get_string('mturl', 'local_translate_courses'),
                '',
                'https://nmt-api.umuganda.digital/api/v1/translate_html/translate_page',
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_translate_courses/toggle_translation',
                get_string('enable_translation', 'local_translate_courses'),
                get_string('toggle_translation', 'local_translate_courses'),
                1
            )
        );

        $settings->add(
            new admin_setting_configtextarea(
                'local_translate_courses/source_languages',
                new lang_string('source_languages', 'local_translate_courses'),
                '',
                'English(en),Kinyarwanda(rw)'
            )
        );

        $settings->add(
            new admin_setting_configtextarea(
                'local_translate_courses/target_languages',
                new lang_string('target_languages', 'local_translate_courses'),
                '',
                'Kinyarwanda(rw),English(en)'
            )
        );

        $settings->add(
            new admin_setting_configmultiselect(
                'local_translate_courses/model',
                get_string('model', 'local_translate_courses'),
                '',
                ['custom'],
                ['custom' => 'Mbaza MT', 'google_translate' => 'Google Translate']
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'local_translate_courses/toggle_content_library_switching',
                get_string('enable_content_library_switching', 'local_translate_courses'),
                get_string('toggle_content_library_switching', 'local_translate_courses'),
                1
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_translate_courses/google_translate_api_key',
                get_string('google_translate_api_key', 'local_translate_courses'),
                '',
                null,
            )
        );
    }
}


