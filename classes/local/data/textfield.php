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
 * Define an object for text fields.
 *
 * @package    local_mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/version.php
 */
class textfield {

    /** @var int */
    private $cmid;
    /** @var string */
    private $field;
    /** @var int */
    private $format;
    /** @var int */
    private $id;
    /** @var int */
    private $sectionid;
    /** @var string */
    private $table;
    /** @var string */
    private $text;
    /** @var int
     * private $tid;*/

    /**
     * @param int $id
     * @param int $sectionid
     * @param string $text
     * @param int $format
     * @param string $table
     * @param string $field
     */
    public function __construct(int $id = null, int $sectionid = null, int $cmid = null, string $text = null, int $format = null,
            string $table = null, string $field = null) {
        $this->id = $id;
        $this->sectionid = $sectionid;
        $this->text = $text;
        $this->format = $format;
        $this->table = $table;
        $this->field = $field;
    }

    /**
     * Getter for cmid.
     *
     * @return int
     */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /**
     * Setter for cmid.
     *
     * @param int $cmid
     */
    public function set_cmid(int $cmid): void {
        $this->cmid = $cmid;
    }

    /**
     * Getter for id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Setter for id.
     *
     * @param int $id
     * throws \InvalidArgumentException
     */
    public function set_id(int $id): void {
        if (is_int($id) && !empty($id)) {
            $this->id = $id;
        } else {
            throw new \InvalidArgumentException($id . ' must be a non-empty int.');
        }
    }

    /**
     * Getter for sectionid.
     *
     * @return int
     */
    public function get_sectionid(): int {
        return $this->sectionid;
    }

    /**
     * Setter for sectionid.
     *
     * @param int $sectionid
     */
    public function set_sectionid(int $sectionid): void {
        $this->sectionid = $sectionid;
    }

    /**
     * Getter for text.
     *
     * @return string
     */
    public function get_text(): string {
        return $this->text;
    }

    /**
     * Setter for text.
     *
     * @param string $text
     */
    public function set_text(string $text): void {
        if (is_string($text) && !empty($text)) {
            $this->text = $text;
        } else {
            throw new \InvalidArgumentException($text . ' must be a non-empty string.');
        }
    }

    /**
     * Getter for format.
     *
     * @return int
     */
    public function get_format(): int {
        return $this->format;
    }

    /**
     * Setter for format.
     *
     * @param int $format
     */
    public function set_format(int $format): void {
        $this->format = $format;
    }

    /**
     * Getter for table.
     *
     * @return string
     */
    public function get_table(): string {
        return $this->table;
    }

    /**
     * Setter for table.
     *
     * @param string $table
     * throws \InvalidArgumentException
     */
    public function set_table(string $table): void {
        if (is_string($table) && !empty($table)) {
            $this->table = $table;
        } else {
            throw new \InvalidArgumentException($table . ' must be a non-empty string.');
        }
    }

    /**
     * Getter for field.
     *
     * @return string
     */
    public function get_field(): string {
        return $this->field;
    }

    /**
     * Setter for field.
     *
     * @param string $field
     * throws \InvalidArgumentException
     */
    public function set_field(string $field): void {
        if (is_string($field) && !empty($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException($field . ' must be a non-empty string.');
        }
    }
}
