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

$capabilities = array(
    'moodle/backup:backupcourse',
    'moodle/backup:userinfo',
    'moodle/restore:restorecourse',
    'moodle/restore:userinfo',
    'moodle/course:create',
    'moodle/site:approvecourse',
);

$systemcontext = context_system::instance();

if (has_capability('local/translate_courses:view', $contextuser)
        && has_all_capabilities($capabilities, $systemcontext)
) {
    $ADMIN->add(
        'courses',
        new admin_externalpage(
            'local_translate_courses',
            get_string('addcourse', 'local_translate_courses'),
            new moodle_url('/local/translate_courses/index.php'),
            $capabilities
        )
    );
}

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_translate_courses_settings', 'Course templates');

    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $default = get_config('local_translate_courses', 'namecategory');

        if ($default === false) {
            $templatecategory = $DB->get_record('course_categories', array('name' => 'Course templates'));

            // Set the new default administrator setting to 'Course templates' if it exists, if not default to 'Miscellaneous'.
            if ($templatecategory !== false) {
                $default = $templatecategory->id;
            } else {
                $default = 1;
            }
        }

        $settings->add(
            new admin_settings_coursecat_select(
                'local_translate_courses/namecategory',
                get_string('namecategory', 'local_translate_courses'),
                get_string('namecategorydescription', 'local_translate_courses'),
                $default
            )
        );

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
                'https://nmt-api.umuganda.digital/api/v1/translate/',
            )
        );
    }
}
