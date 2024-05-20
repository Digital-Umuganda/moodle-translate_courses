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
 * The definition of the plugin's 'list categories' form.
 *
 * @package     local_translate_courses
 * @copyright   2022 Andrew Caya <andrewscaya@yahoo.ca>
 * @author      Andrew Caya
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_translate_courses;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * The coursetemplateslistform class.
 *
 * This class is this plugin's 'list categories' form.
 */
class coursecategorieslistform extends \moodleform
{

    /**
     * Array that contains the visible and hidden categories to choose from.
     *
     * @var array
     */
    protected $categoriesarray = array();

    /**
     * Variable that contains the default category id.
     *
     * @var int
     */
    protected $defaultcategory;

    /**
     * The form's main definition method.
     *
     * @throws \coding_exception
     */
    protected function definition()
    {
        $mform = $this->_form;

        if (isset($this->_customdata['categoriesarray'])) {
            $this->set_categories_array($this->_customdata['categoriesarray']);
        } else {
            return;
        }

        if (isset($this->_customdata['defaultcategory'])) {
            $this->set_default_category($this->_customdata['defaultcategory']);
        } else {
            return;
        }

        $options = array(
            'multiple' => false,
        );

        $categorieslistraw = $this->get_categories_array();

        $defaultcategory = $this->get_default_category();

        $categorieslistdefault = array_intersect_key($categorieslistraw, array_flip([$defaultcategory]));

        $translatablemods = [
            // 'section',
            'lesson',
            'assign',
            'assignment',
            'checklist',
            'page',
            'quiz',
            'subcourse',
            'wiki',
            'book',
            'choice',
            'label',
            'customfields',
        ];


        $modinfo = get_fast_modinfo(optional_param('cid', 0, PARAM_INT));

        $modstotranslate = ['section' => 'Section'];

        foreach ($modinfo->get_used_module_names() as $key => $modname) {
            if (in_array($key, $translatablemods)) {
                $modstotranslate[$key] = $modname;
            }
        }

        // Make sure that the default category was found, before unsetting it in the original array.
        if (isset($categorieslistdefault[$defaultcategory])) {
            unset($categorieslistraw[$defaultcategory]);
        }

        $categorieslist = $categorieslistdefault + $categorieslistraw;

        $sourcelanguagesasstring = get_config('local_translate_courses', 'source_languages');
        $sourcelanguagesasarray = explode(',', $sourcelanguagesasstring);
        $sourcelanguagesasarraywkeys = [];

        foreach ($sourcelanguagesasarray as $key => $value) {
            $languagecode = explode('(', $value);
            $languagecode = $languagecode[1];
            $languagecode = explode(')', $languagecode);
            $languagecode = $languagecode[0];
            $languagecode = strtolower($languagecode);
            $languagecode = trim($languagecode);
            $sourcelanguagesasarraywkeys[$languagecode] = trim(explode('(', $value)[0]);
        }

        $mform->addElement(
            'autocomplete',
            'sel_src_lang',
            get_string('selectsrclang', 'local_translate_courses'),
            $sourcelanguagesasarraywkeys,
            $options
        );

        $mform->addRule('sel_src_lang', get_string('requiredelement', 'form'), 'required', null, 'client');
        $mform->addRule('sel_src_lang', get_string('requiredelement', 'form'), 'required', null, 'server');

        $targetlanguagesasstring = get_config('local_translate_courses', 'target_languages');
        $targetlanguagesasarray = explode(',', $targetlanguagesasstring);
        $targetlanguagesasarraywkeys = [''];

        foreach ($targetlanguagesasarray as $key => $value) {
            $languagecode = explode('(', $value);
            $languagecode = $languagecode[1];
            $languagecode = explode(')', $languagecode);
            $languagecode = $languagecode[0];
            $languagecode = strtolower($languagecode);
            $languagecode = trim($languagecode);
            $targetlanguagesasarraywkeys[$languagecode] = trim(explode('(', $value)[0]);
        }

        $mform->addElement(
            'autocomplete',
            'sel_tgt_lang',
            get_string('selecttgtlang', 'local_translate_courses'),
            $targetlanguagesasarraywkeys,
            $options
        );

        $mform->addRule('sel_tgt_lang', get_string('requiredelement', 'form'), 'required', null, 'client');
        $mform->addRule('sel_tgt_lang', get_string('requiredelement', 'form'), 'required', null, 'server');

        $currentacceptedmodels = [
            'custom' => 'Mbaza MT',
            'google_translate' => 'Google Translation',
        ];

        $enabledmodelsasstring = get_config('local_translate_courses', 'model');
        $explodedenabledmodels = explode(',', $enabledmodelsasstring);
        $enabledmodels = [];

        foreach ($explodedenabledmodels as $key => $value) {
            $enabledmodels[$value] = $currentacceptedmodels[$value];
        }

        $mform->addElement(
            'autocomplete',
            'sel_trans',
            get_string('selecttranslator', 'local_translate_courses'),
            $enabledmodels,
            $options
        );

        $mform->addRule('sel_trans', get_string('requiredelement', 'form'), 'required', null, 'client');
        $mform->addRule('sel_trans', get_string('requiredelement', 'form'), 'required', null, 'server');

        $mform->addElement(
            'autocomplete',
            'sel_mods',
            get_string('selectmods', 'local_translate_courses'),
            $modstotranslate,
            $options
        );

        $mform->getElement('sel_mods')->setMultiple(true);
        $mform->addRule('sel_mods', get_string('requiredelement', 'form'), 'required', null, 'client');
        $mform->addRule('sel_mods', get_string('requiredelement', 'form'), 'required', null, 'server');

        // When there are two elements, we need a group.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement(
            'button',
            'back',
            get_string('back', 'local_translate_courses'),
            array('onclick' => 'javascript :history.back(-1)')
        );
        $buttonarray[] = &$mform->createElement(
            'submit',
            'coursecategorieslistsubmit',
            get_string('continue', 'local_translate_courses')
        );
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Gets the categories array (form's custom data).
     *
     * @return array
     */
    public function get_categories_array()
    {
        return $this->categoriesarray;
    }

    /**
     * Sets the categories array (form's custom data).
     *
     * @param array $categoriesarray
     *
     * @return array
     */
    public function set_categories_array(array $categoriesarray)
    {
        $this->categoriesarray = $categoriesarray;

        return $this->categoriesarray;
    }

    /**
     * Gets the default category id (form's custom data).
     *
     * @return int
     */
    public function get_default_category()
    {
        return $this->defaultcategory;
    }

    /**
     * Sets the default category id (form's custom data).
     *
     * @param int $defaultcategory
     *
     * @return int
     */
    public function set_default_category($defaultcategory)
    {
        $this->defaultcategory = $defaultcategory;

        return $this->defaultcategory;
    }
}


