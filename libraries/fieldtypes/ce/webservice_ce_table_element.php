<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Default fieldtype file, every fieldtype, except the one overridden, goes through this class
 *
 * @package		webservice
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @link        http://reinos.nl/add-ons//add-ons/entry-api
 * @copyright 	Copyright (c) 2014 Reinos.nl Internet Media
 */


class Webservice_ce_table_element
{
    public $name = 'table';

    // ----------------------------------------------------------------

    /**
     * Preps the data for saving
     *
     * Hint: you only have to format the data likes the publish page
     *
     * @param  mixed $data
     * @param  bool $is_new
     * @param  int $entry_id
     * @return mixed string
     */
    public function webservice_ce_save($data = null, $is_new = false, $entry_id = 0)
    {
        return $data;
    }

    // ----------------------------------------------------------------------

    /**
     * Handles any custom logic after an entry is saved.
     *
     * @param  mixed $data
     * @param  array $inserted_data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_ce_post_save($data = null, $inserted_data = array(), $entry_id = 0)
    {

    }

    // ----------------------------------------------------------------

    /**
     * Validate the field
     *
     * @param  mixed $data
     * @param  bool $is_new
     * @return bool
     */
    public function webservice_ce_validate($data = null, $is_new = false, $entry_id = 0)
    {
        //$this->validate_error = '';
        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * Preprocess the data to be returned
     *
     * @param  mixed $data
     * @param bool|string $free_access
     * @param  int $entry_id
     * @return mixed string
     */
    public function webservice_ce_pre_process($data = null, $entry_id = 0)
    {
        $data = unserialize($data);

        $rows = $data["table_data"]["rows"];
        $cols = $data["table_data"]["cols"];
        $cell = $data["table_data"]["cell"];

        $header = (int) $this->element_settings['settings']["header"];
        if ($header)
            $rows = $rows + 1;

        //create pattern

        $table_pattern = array(
            "rows" => array(),
            "thead" => array(),
            "tbody" => array(),
        );

        //rows

        $cell_index = 0;
        for ($i = 0; $i < $rows; $i++) {
            $table_pattern["rows"][$i] = array(
                "cells" => array(),
            );

            for ($j = 0; $j < $cols; $j++) {
                $table_pattern["rows"][$i]["cells"][$j]["value"] = !empty($cell[$cell_index]) ? $cell[$cell_index] : '';
                $cell_index++;


                //** -------------------------------
                //** replace EE entities
                //** -------------------------------

                $table_pattern["rows"][$i]["cells"][$j]["value"] = preg_replace("/{([_a-zA-Z]*)}/u", "&#123;$1&#125;", $table_pattern["rows"][$i]["cells"][$j]["value"]);
            }
        }

        //thead & tbody

        foreach ($table_pattern["rows"] as $k => $row) {
            if ($k == 0 && $header) {
                $table_pattern["thead"][] = $row;
            } else {
                $table_pattern["tbody"][] = $row;
            }
        }

        return $table_pattern;
    }

    // ----------------------------------------------------------------------

    /**
     * delete field data, before the entry is deleted
     *
     * Hint: EE will mostly do everything for you, because the delete() function will trigger
     *
     * @param  mixed $data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_ce_delete($data = null, $entry_id = 0)
    {

    }

    // ----------------------------------------------------------------------

    /**
     * delete field data, after the entry is deleted
     *
     * Hint: EE will mostly do everything for you, because the delete() function will trigger
     *
     * @param  mixed $data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_ce_post_delete($data = null, $entry_id = 0)
    {

    }
}