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
 * Short answer question definition class.
 *
 * @package    qtype
 * @subpackage cdsa
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');

/**
 * Represents a short answer question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_cdsa_question extends question_graded_by_strategy
        implements question_response_answer_comparer {
    /** @var boolean whether answers should be graded case-sensitively. */
    public $usecase;
    /** @var array of question_answer. */
    public $answers = array();

    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response) &&
                ($response['answer'] || $response['answer'] === '0');
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseenterananswer', 'qtype_cdsa');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

   function get_cat_path($cat_id)
   {
      global $DB;
      $cat_path = '';
      while($cat_id != null && $cat_id !== '0')
      {
         $sql = 'select name, parent from mdl_question_categories where id="' . $cat_id . '"';
         try {
            $query = $DB->get_record_sql($sql);
            if($query)
            {
               $cat_path = $query->name . '/' . $cat_path;
               $cat_id = $query->parent;
            }
          } catch(Exception $e)
          {
             echo $e->getMessage();
          }
      }
      $cat_path = str_replace(' ', '', $cat_path);
      $cat_path = '/' . $cat_path;
      return $cat_path;
   }

   function get_python_question_url()
   {
      global $CFG;
      $cat_path = $this->get_cat_path($this->category);

      $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
      $ans_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pycgi/question' .$cat_path . $this->name;

      return $ans_url;
   }

   function get_answer_url()
   {
      global $USER, $COURSE;
      // Add in the name of the cgi program + userid and course id
      return $this->get_python_question_url() . '/pq/ans/' . $COURSE->id . '_' . $USER->id;
   }

   function get_mark_url($student_answer)
   {
      global $USER, $COURSE;
      // Add in the name of the cgi program + userid and course id
      $query_string = "?resp=" . urlencode($student_answer);
      return $this->get_python_question_url() . '/pq/mark/' . $COURSE->id . '_' . $USER->id . $query_string;
   }

   function get_specification_url()
   {
      global $USER, $COURSE;
      // Add in the name of the cgi program + userid and course id
      return $this->get_python_question_url() . '/pq/spec/' . $COURSE->id . '_' . $USER->id;
   }

   function get_specification_from_url()
   {
      return file_get_contents($this->get_specification_url());
   }

   function get_answer_from_url()
   {
      return file_get_contents($this->get_answer_url());
   }

   function get_mark_from_url($student_answer)
   {
      return file_get_contents($this->get_mark_url($student_answer));
   }

    public function get_answers() {
        // Modified by CDaly

        $ans_text = $this->get_answer_from_url();

        $this->answers = array(
            13 => new question_answer(13, $ans_text, 1, $ans_text . ' is the right answer', FORMAT_HTML),
        );


        //print_object($this->answers);
        return $this->answers;
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {
        if (!array_key_exists('answer', $response) || is_null($response['answer'])) {
            return false;
        }

        return self::compare_string_with_wildcard(
                $response['answer'], $answer->answer, !$this->usecase);
    }

    public static function compare_string_with_wildcard($string, $pattern, $ignorecase) {

        // Normalise any non-canonical UTF-8 characters before we start.
        $pattern = self::safe_normalize($pattern);
        $string = self::safe_normalize($string);

        // Break the string on non-escaped runs of asterisks.
        // ** is equivalent to *, but people were doing that, and with many *s it breaks preg.
        $bits = preg_split('/(?<!\\\\)\*+/', $pattern);

        // Escape regexp special characters in the bits.
        $escapedbits = array();
        foreach ($bits as $bit) {
            $escapedbits[] = preg_quote(str_replace('\*', '*', $bit), '|');
        }
        // Put it back together to make the regexp.
        $regexp = '|^' . implode('.*', $escapedbits) . '$|u';

        // Make the match insensitive if requested to.
        if ($ignorecase) {
            $regexp .= 'i';
        }

        return preg_match($regexp, trim($string));
    }

    /**
     * Normalise a UTf-8 string to FORM_C, avoiding the pitfalls in PHP's
     * normalizer_normalize function.
     * @param string $string the input string.
     * @return string the normalised string.
     */
    protected static function safe_normalize($string) {
        if ($string === '') {
            return '';
        }

        if (!function_exists('normalizer_normalize')) {
            return $string;
        }

        $normalised = normalizer_normalize($string, Normalizer::FORM_C);
        if (is_null($normalised)) {
            // An error occurred in normalizer_normalize, but we have no idea what.
            debugging('Failed to normalise string: ' . $string, DEBUG_DEVELOPER);
            return $string; // Return the original string, since it is the best we have.
        }

        return $normalised;
    }

    public function get_correct_response() {
        $response = parent::get_correct_response();
        if ($response) {
            $response['answer'] = $this->clean_response($response['answer']);
        }

        return $response;
    }

    public function clean_response($answer) {
        // Break the string on non-escaped asterisks.
        $bits = preg_split('/(?<!\\\\)\*/', $answer);

        // Unescape *s in the bits.
        $cleanbits = array();
        foreach ($bits as $bit) {
            $cleanbits[] = str_replace('\*', '*', $bit);
        }

        // Put it back together with spaces to look nice.
        return trim(implode(' ', $cleanbits));
    }

   // We first pass the student response to the MARK url.
   // This returns a string containing the fraction and the feedback
   // This is then parsed and used to create a matching answer for the marking system.
   function get_matching_answer(array $response)
   {
      //print_object($response);
      $stud_answer = $response['answer'];
      $result = $this->get_mark_from_url($stud_answer);
      
      //print($result);
      // Extract the fraction and the feedback
      $first_line = strpos($result, "\n");
      $fraction = substr($result, 0, $first_line);
      $feedback = substr($result, $first_line+1);
      //print("mark = " . $fraction);
      //print("freedback = " . $feedback);

      // Create an answer for this response.
      return new question_answer(14, $stud_answer, $fraction, $feedback, FORMAT_HTML);
   }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $currentanswer = $qa->get_last_qt_var('answer');
            $answer = $this->get_matching_answer(array('answer' => $currentanswer));
            $answerid = reset($args); // Itemid is answer id.
            return $options->feedback && $answer && $answerid == $answer->id;

        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
}
