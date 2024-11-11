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
namespace local_mlangremover\local\data;
/**
 * Decorate the translatable object to operate language.
 *
 * @package    local_mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/version.php
 */
class multilangfield {
    /** @var array */
    private $languages;
    /** @var string */
    private $textcontent;
    /** @var textfield */
    private $textfield;

    /**
     * Constructor obviously.
     *
     * @param textfield $field
     */
    public function __construct(textfield $field) {
        $this->textfield = $field;
        $this->textcontent = $this->textfield->get_text();
        $this->findlangs();
    }

    /**
     * Builds languages an array of string of iso codes or 'other'.
     *
     * @return void
     */
    private function findlangs() {
        $pattern = '/\{mlang\s+([a-z]{2}|other)\}.*?\{mlang\}/is';
        $this->languages = [];
        if (preg_match_all($pattern, $this->textcontent, $matches)) {
            // Iterate over the matches and extract the language codes.
            foreach ($matches[1] as $lang) {
                // Add the language code to the array if it's not already there.
                if (!in_array($lang, $this->languages)) {
                    $this->languages[] = $lang;
                }
            }
        }
    }

    /**
     * Extracts the text for a given language code from a multilang string, concatenating multiple instances.
     *
     * @param string $lang The language code to extract (e.g., 'en', 'fr', 'other').
     * @return string The concatenated text for the specified language, or an empty string if not found.
     */
    public function extract_language($lang, bool $retunrallifnotfound = true) {
        if (!$this->has_multilang($this->textcontent)) {
            return $this->textcontent;
        }
        // Define the pattern to match the specified language's multilang tags.
        $pattern = '/\{mlang\s+' . preg_quote($lang, '/') . '\}(.*?)\{mlang\}/is';

        // Initialize the result variable.
        $result = '';

        // Use preg_match_all to find all matches.
        if (preg_match_all($pattern, $this->textcontent, $matches)) {
            // Concatenate all the matched text segments.
            foreach ($matches[1] as $segment) {
                $result .= $segment . ' ';
            }
            // Trim any trailing whitespace.
            $result = trim($result);
        }

        // Return the concatenated text or an empty string if not found.
        return $result === '' && $retunrallifnotfound ? $this->textcontent : $result;
    }

    /**
     * Filters the text to keep or remove specified languages, preserving their multilang tags.
     *
     * @param array $langs An array of language codes to keep or remove (e.g., ['en', 'fr', 'other']).
     * @param bool $keep If true, keeps only the specified languages; if false, removes the specified languages.
     * @return string The filtered text with the specified languages either kept or removed, preserving their tags.
     */
    public function filter_languages_with_tags($langs, $keep = true) {
        if (!$this->has_multilang($this->textcontent)) {
            return $this->textcontent;
        }
        // Define the pattern to match all multilang tags.
        $pattern = '/\{mlang\s+([a-z]{2}|other)\}(.*?)\{mlang\}/is';

        // Initialize the result variable.
        $result = '';

        // Use preg_match_all to find all matches.
        if (preg_match_all($pattern, $this->textcontent, $matches, PREG_SET_ORDER)) {
            // Iterate over the matches
            foreach ($matches as $match) {
                // Check if the language code is in the array of languages to keep or remove.
                $in_array = in_array($match[1], $langs);
                if (($keep && $in_array) || (!$keep && !$in_array)) {
                    // Append the text segment with its tags to the result.
                    $result .= '{mlang ' . $match[1] . '}' . $match[2] . '{mlang} ';
                }
            }
            // Trim any trailing whitespace.
            $result = trim($result);
        }

        // Return the filtered text with tags.
        return $result;
    }

    /**
     * As the title says.
     *
     * @param string $t
     * @return bool
     */
    private function has_multilang(string $t): bool {
        return str_contains($t, '{mlang}');
    }

    /**
     * Getter for textfield.
     *
     * @return textfield
     */
    public function get_textfield(): textfield {
        return $this->textfield;
    }

    /**
     * Getter for languages.
     *
     * @return array
     */
    public function get_languages(): array {
        return $this->languages;
    }
}
