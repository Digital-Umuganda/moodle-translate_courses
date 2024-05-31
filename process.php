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
 * Process file.
 *
 * @package   local_translate_courses
 * @copyright 2017 onwards, emeneo (www.emeneo.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once (dirname(__FILE__) . '/../../config.php');

@ini_set('max_execution_time', 0);

global $CFG, $DB, $USER;

require_once ($CFG->dirroot . '/course/externallib.php');
require_once ($CFG->dirroot . '/course/format/lib.php');
require_once (__DIR__ . '/lib.php');

require_login();
require_sesskey();

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

// TODO: check option users further.
$fullname = optional_param('course_name', '', PARAM_RAW);
$shortname = optional_param('course_short_name', '', PARAM_RAW);
$categoryid = optional_param('cateid', 1, PARAM_INT);
$courseid = optional_param('cid', 0, PARAM_INT);
$sourcelanguage = optional_param('srclang', current_language(), PARAM_RAW);
$targetlanguage = optional_param('tgtlang', 'rw', PARAM_RAW);
$translator = optional_param('translator', 'custom', PARAM_RAW);
$modstotranslate = optional_param('modstotranslate', '', PARAM_RAW);
$modstotranslate = json_decode($modstotranslate);

// print_r([$sourcelanguage, $targetlanguage, $translator, $_GET]);

if (isset($categoryid) && !empty($categoryid)) {
    $context = context_coursecat::instance($categoryid);
} else {
    $context = context_user::instance($USER->id);
}

$options = array(
    array('name' => 'blocks', 'value' => 1),
    array('name' => 'activities', 'value' => 1),
    array('name' => 'filters', 'value' => 1)
);
$visible = 1;

$startdatetime = optional_param('start_datetime', '', PARAM_RAW);
$enddatetime = optional_param('end_datetime', '', PARAM_RAW);
$location = optional_param('location', '', PARAM_RAW);
$coursedate = optional_param('course_date', '', PARAM_RAW);

if (!empty($startdatetime)) {
    $startdatetime = strtotime($coursedate . ' ' . $startdatetime);
}

if (!empty($enddatetime)) {
    $enddatetime = strtotime($coursedate . ' ' . $enddatetime);
}

if (!$fullname || !$shortname || !$categoryid || !$courseid) {
    exit(json_encode(array('status' => 2, 'id' => $courseid, 'cateid' => $categoryid, 'fullname' => $fullname, 'shortname' => $shortname)));
}

$externalobj = new core_course_external();

try {
    // Check if the course exists.
    $course = $DB->get_record('course', array('fullname' => $fullname));
    if ($course) {
        \core\notification::error('Course name already exists. Please use another name for the course.');
        exit(json_encode(array('status' => 0)));
    }
    
    $course = $DB->get_record('course', array('shortname' => $shortname));
    if ($course) {
        \core\notification::error('Course shortname already exists. Please use another shortname for the course.');
        exit(json_encode(array('status' => 0)));
    }

    // Make sure the course's sections have proper labels.
    // See the set_label() method in /backup/util/ui/backup_ui_setting.class.php.
    // The set_label() method sanitizes the section name using PARAM_CLEANHTML (as of Moodle 3.11).
    $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section');

    foreach ($sections as $section) {
        if (isset($section->name) && clean_param($section->name, PARAM_CLEANHTML) === '') {
            course_get_format($section->course)->inplace_editable_update_section_name($section, 'sectionname', null);

            $section->name = null;
            $DB->update_record('course_sections', $section);
        }
    }

    $res = $externalobj->duplicate_course($courseid, $fullname, $shortname . rand(10, 10000), $categoryid, $visible, $options);
} catch (moodle_exception $e) {
    \core\notification::error($e);
    exit(json_encode(array('status' => 0)));
}

sleep(3);

if (isset($res['id'])) {
    try {
        $course = $DB->get_record('course', array('id' => $res['id']));

        // Hide course
        $course->visible = 0;
        $course->visibleold = 0;

        $course->summary = generate_translation($course->summary, $sourcelanguage, $targetlanguage, $translator);

        $DB->update_record('course', $course);

        if (in_array('section', $modstotranslate)) {
            // Translate sections
            $sections = $DB->get_records('course_sections', array('course' => $res['id']));

            foreach ($sections as $section) {
                if ($section->name != null) {
                    $section->name = generate_translation($section->name, $sourcelanguage, $targetlanguage, $translator);
                }

                if ($section->summary != null) {
                    $section->summary = generate_translation($section->summary, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('course_sections', $section);
            }
        }

        if (in_array('lesson', $modstotranslate)) {
            // Translate lessons
            $lessons = $DB->get_records_sql("SELECT lp.id, lp.contents, l.name, l.intro, lp.lessonid FROM mdl_lesson l RIGHT JOIN mdl_lesson_pages lp ON l.id = lp.lessonid WHERE l.course = :resid", ['resid' => $res['id']]);

            foreach ($lessons as $key => $lesson) {
                $lessonDataObject = new stdClass;

                $lessonDataObject->id = $lesson->lessonid;
                if ($lesson->name != null) {
                    $lessonDataObject->name = generate_translation($lesson->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($lesson->intro != null) {
                    $lessonDataObject->intro = generate_translation($lesson->intro, $sourcelanguage, $targetlanguage, $translator);
                }
                $DB->update_record('lesson', $lessonDataObject);

                $dataObject = new stdClass;

                if ($lesson->contents != null) {
                    $dataObject->id = $lesson->id;
                    $dataObject->contents = generate_translation($lesson->contents, $sourcelanguage, $targetlanguage, $translator);
                    $DB->update_record('lesson_pages', $dataObject);
                }
            }
        }

        if (in_array('assign', $modstotranslate)) {
            // Translate assignments
            $assignments = $DB->get_records('assign', array('course' => $res['id']));

            foreach ($assignments as $key => $assignment) {
                $assignmentDataObject = new stdClass;

                $assignmentDataObject->id = $assignment->id;
                if ($assignment->name != null) {
                    $assignmentDataObject->name = generate_translation($assignment->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($assignment->intro != null) {
                    $assignmentDataObject->intro = generate_translation($assignment->intro, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($assignment->activity != null) {
                    $assignmentDataObject->activity = generate_translation($assignment->activity, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('assign', $assignmentDataObject);
            }
        }

        if (in_array('checklist', $modstotranslate)) {
            // Translate checklist
            $checkLists = $DB->get_records('checklist', array('course' => $res['id']));

            foreach ($checkLists as $key => $checkList) {
                $checkListDataObject = new stdClass;

                $checkListDataObject->id = $checkList->id;
                if ($checkList->name != null) {
                    $checkListDataObject->name = generate_translation($checkList->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($checkList->intro != null) {
                    $checkListDataObject->intro = generate_translation($checkList->intro, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('checklist', $checkListDataObject);

                $checkListItems = $DB->get_records('checklist_item', array('checklist' => $checkList->id));

                foreach ($checkListItems as $key => $checkListItem) {
                    $checkListItemDataObject = new stdClass;

                    $checkListItemDataObject->id = $checkListItem->id;
                    if ($checkListItem->displaytext != null) {
                        $checkListItemDataObject->displaytext = generate_translation($checkListItem->displaytext, $sourcelanguage, $targetlanguage, $translator);
                    }

                    $DB->update_record('checklist_item', $checkListItemDataObject);
                }
            }
        }

        if (in_array('page', $modstotranslate)) {
            // Translate pages
            $pages = $DB->get_records('page', array('course' => $res['id']));

            foreach ($pages as $key => $page) {
                if ($pages[$key]->name != null) {
                    $pages[$key]->name = generate_translation($page->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($pages[$key]->intro != null) {
                    $pages[$key]->intro = generate_translation($page->intro, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($pages[$key]->content != null) {
                    $pages[$key]->content = generate_translation($page->content, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('page', $pages[$key]);
            }
        }

        if (in_array('quiz', $modstotranslate)) {
            // Translate quizzes
            $quizzes = $DB->get_records('quiz', array('course' => $res['id']));

            foreach ($quizzes as $key => $quiz) {
                if ($quiz->name != null) {
                    $quiz->name = generate_translation($quiz->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($quiz->intro != null) {
                    $quiz->intro = generate_translation($quiz->intro, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('quiz', $quiz);

                // Translate quiz questions
                $quizQuestions = $DB->get_records_sql("SELECT q.id, q.questiontext, q.name, q.generalfeedback FROM mdl_quiz_slots slot LEFT JOIN mdl_question_references qr ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = slot.id LEFT JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid LEFT JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id LEFT JOIN mdl_question q ON q.id = qv.questionid WHERE slot.quizid = :quizid", ['quizid' => $quiz->id]);

                foreach ($quizQuestions as $key => $question) {
                    $questiondata = new stdClass;

                    $questiondata->id = $question->id;
                    if ($question->name != null) {
                        $questiondata->name = generate_translation($question->name, $sourcelanguage, $targetlanguage, $translator);
                    }
                    if ($question->questiontext != null) {
                        $questiondata->questiontext = generate_translation($question->questiontext, $sourcelanguage, $targetlanguage, $translator);
                    }
                    if ($question->generalfeedback != null) {
                        $questiondata->generalfeedback = generate_translation($question->generalfeedback, $sourcelanguage, $targetlanguage, $translator);
                    }

                    $DB->update_record('question', $questiondata);

                    // Translate question answers
                    $questionAnswers = $DB->get_records('question_answers', array('question' => $question->id));

                    foreach ($questionAnswers as $questionAnswer) {
                        $questionAnswerData = new stdClass;
                        $questionAnswerData->id = $questionAnswer->id;
                        if ($questionAnswer->answer != null) $questionAnswerData->answer = generate_translation($questionAnswer->answer, $sourcelanguage, $targetlanguage, $translator);
                        if ($questionAnswer->feedback != null) $questionAnswerData->feedback = generate_translation($questionAnswer->feedback, $sourcelanguage, $targetlanguage, $translator);

                        $DB->update_record('question_answers', $questionAnswerData);
                    }
                }
            }
        }

        if (in_array('subcourse', $modstotranslate)) {
            // Translate sub courses
            $subCourses = $DB->get_records('subcourse', array('course' => $res['id']));

            foreach ($subCourses as $key => $subcourse) {
                if ($subcourse->name != null) {
                    $subcourse->name = generate_translation($subcourse->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($subcourse->intro != null) {
                    $subcourse->intro = generate_translation($subcourse->intro, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('subcourse', $subcourse);
            }
        }

        if (in_array('wiki', $modstotranslate)) {
            // Translate wikis
            $wikis = $DB->get_records('wiki', array('course' => $res['id']));

            foreach ($wikis as $wiki) {
                if ($wiki->name != null) {
                    $wiki->name = generate_translation($wiki->name, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($wiki->intro != null) {
                    $wiki->intro = generate_translation($wiki->intro, $sourcelanguage, $targetlanguage, $translator);
                }
                if ($wiki->firstpagetitle != null) {
                    $wiki->firstpagetitle = generate_translation($wiki->firstpagetitle, $sourcelanguage, $targetlanguage, $translator);
                }

                $DB->update_record('wiki', $wiki);

                $subWikis = $DB->get_records('wiki_subwikis', array('wikiid' => $wiki->id));

                foreach ($subWikis as $key => $subWiki) {
                    $wikiPages = $DB->get_records('wiki_pages', array('subwikiid' => $subWiki->id));

                    foreach ($wikiPages as $key => $wikiPage) {
                        if ($wikiPage->title != null) {
                            $wikiPage->title = generate_translation($wikiPage->title, $sourcelanguage, $targetlanguage, $translator);
                        }
                        if ($wikiPage->cachedcontent != null) {
                            $wikiPage->cachedcontent = generate_translation($wikiPage->cachedcontent, $sourcelanguage, $targetlanguage, $translator);
                        }

                        $DB->update_record('wiki_pages', $wikiPage);
                    }
                }
            }
        }

        if (in_array('book', $modstotranslate)) {
            // Translate books
            $books = $DB->get_records('book', array('course' => $res['id']));

            foreach ($books as $key => $book) {
                $book->name = generate_translation($book->name, $sourcelanguage, $targetlanguage, $translator);
                $book->intro = generate_translation($book->intro, $sourcelanguage, $targetlanguage, $translator);

                $DB->update_record('book', $book);

                // Translate book chapters
                $bookChapters = $DB->get_records('book_chapters', array('bookid' => $book->id));

                foreach ($bookChapters as $key => $bookChapter) {
                    $bookChapter->title = generate_translation($bookChapter->title, $sourcelanguage, $targetlanguage, $translator);
                    $bookChapter->content = generate_translation($bookChapter->content, $sourcelanguage, $targetlanguage, $translator);

                    $DB->update_record('book_chapters', $bookChapter);
                }
            }
        }

        if (in_array('choice', $modstotranslate)) {
            // Translate choices
            $choices = $DB->get_records('choice', array('course' => $res['id']));

            foreach ($choices as $key => $choice) {
                $choice->name = generate_translation($choice->name, $sourcelanguage, $targetlanguage, $translator);
                $choice->intro = generate_translation($choice->intro, $sourcelanguage, $targetlanguage, $translator);

                $DB->update_record('choice', $choice);

                // Translate choice options
                $choiceOptions = $DB->get_records('choice_options', array('choiceid' => $choice->id));

                foreach ($choiceOptions as $key => $choiceOption) {
                    $choiceOption->text = generate_translation($choiceOption->text, $sourcelanguage, $targetlanguage, $translator);

                    $DB->update_record('choice_options', $choiceOption);
                }
            }
        }

        if (in_array('label', $modstotranslate)) {
            // Translate labels (text and media areas)
            $labels = $DB->get_records('label', array('course' => $res['id']));

            foreach ($labels as $key => $label) {
                $label->name = generate_translation($label->name, $sourcelanguage, $targetlanguage, $translator);
                $label->intro = generate_translation($label->intro, $sourcelanguage, $targetlanguage, $translator);

                $DB->update_record('label', $label);
            }
        }

        if (in_array('feedback', $modstotranslate)) {
            $feedbacks = $DB->get_records('feedback', array('course' => $res['id']));

            foreach ($feedbacks as $key => $feedback) {
                $feedback->name = generate_translation($feedback->name, $sourcelanguage, $targetlanguage, $translator);
                $feedback->intro = generate_translation($feedback->intro, $sourcelanguage, $targetlanguage, $translator);

                $DB->update_record('feedback', $feedback);
            }
        }

        // Translate custom fields
        $time = new DateTime("now", core_date::get_user_timezone_object());

        $timestamp = $time->getTimestamp();

        $context = context_course::instance($courseid);

        save_related_courses($res['id'], $courseid);
        save_related_courses($courseid, $res['id']);

        updateCustomField('course_language', $res['id'], 'intvalue', 9, $context->id);

        // Update course language
        $languages_field = $DB->get_record('customfield_field', array('shortname' => 'languages'));
        $configdataoptionsarray = json_decode($languages_field->configdata, true);
        $configdataoptionsarray = preg_split("/\s*\n\s*/", trim($configdataoptionsarray['options']));

        $languagekey = 0;
        foreach ($configdataoptionsarray as $key => $value) {
            $language = explode('<', explode('>', $configdataoptionsarray[$key])[1])[0];
            if (array_search($language, get_string_manager()->get_list_of_languages()) == $targetlanguage) {
                $languagekey = $key;
            }
        }

        updateCustomField('languages', $res['id'], 'value', $languagekey, $context->id);
        updateCustomField('status', $res['id'], 'intvalue', 1, $context->id);

        // Translate objectives custom field
        $objectives_fielddata = getCustomFieldData('objectives', $res['id']);
        $objectives = $objectives_fielddata->value;
        if (!empty($objectives) && $objectives != null) {
            $translated_objectives = generate_translation($objectives, $sourcelanguage, $targetlanguage, $translator);

            // Update objectives custom field
            updateCustomField('objectives', $res['id'], 'value', $translated_objectives, $context->id);
        }

        // Translate target audience custom field
        $target_audience_fielddata = getCustomFieldData('target_audience', $res['id']);
        $target_audience = $target_audience_fielddata->value;
        if (!empty($target_audience) && $target_audience != null) {
            $translated_target_audience = generate_translation($target_audience, $sourcelanguage, $targetlanguage, $translator);

            // Update target audience custom field
            updateCustomField('target_audience', $res['id'], 'value', $translated_target_audience, $context->id);
        }

        // Translate certification custom field
        $certification_fielddata = getCustomFieldData('certification', $res['id']);
        $certification = $certification_fielddata->value;
        if (!empty($certification) && $certification != null) {
            $translated_certification = generate_translation($certification, $sourcelanguage, $targetlanguage, $translator);

            // Update certification custom field
            updateCustomField('certification', $res['id'], 'value', $translated_certification, $context->id);
        }

        // Translate course contact custom field
        $course_contact_fielddata = getCustomFieldData('course_contact', $res['id']);
        $course_contact = $course_contact_fielddata->value;
        if (!empty($course_contact) && $course_contact != null) {
            $translated_course_contact = generate_translation($course_contact, $sourcelanguage, $targetlanguage, $translator);

            // Update course contact custom field
            updateCustomField('course_contact', $res['id'], 'charvalue', $translated_course_contact, $context->id);
        }

        if (!empty($startdatetime)) {
            $course->startdate = $startdatetime;
            $course->enddate = $enddatetime;
            $DB->update_record('course', $course);
        }

        if (!empty($location)) {
            $eventoption = $DB->get_record(
                'course_format_options',
                array(
                    'courseid' => $course->id,
                    'format' => 'event',
                    'name' => 'location'
                )
            );
            $eventoption->value = $location;
            $DB->update_record('course_format_options', $eventoption);
        }

        // Purge caches
        purge_caches();
    } catch (\Exception $e) {
        \core\notification::error($e->getMessage());
        exit(json_encode(array('status' => 0, "message" => $e->getMessage())));
    }

    exit(json_encode(array('status' => 1, 'id' => $res['id'], 'shortname' => $res['shortname'])));
} else {
    \core\notification::error(get_string('failed', 'local_translate_courses'));
    exit(json_encode(array('status' => 0)));
}