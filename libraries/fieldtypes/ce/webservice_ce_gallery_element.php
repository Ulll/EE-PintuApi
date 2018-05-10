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


class Webservice_ce_gallery_element
{
    public $name = 'gallery';

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
        $upload_preferences = $this->get_file_upload_preferences();

        if (!is_array($data))
        {
            $data = @unserialize($data);
        }

        if(!is_array($data))
        {
            return false;
        }
        if (!isset($data["gallery_id"]))
        {
            $tmp["gallery_id"] = "x";
            $tmp["gallery_data"] = $data;
            $data = $tmp;
        }

        $images["images"] = array();

        //each

        if (isset($data["gallery_data"]["dir"]))
            foreach ($data["gallery_data"]["dir"] as $image_id => $dir_id)
                if ($data["gallery_data"]["dir"][$image_id]) {
                    $cell["name"] = $data["gallery_data"]["name"][$image_id];

                    //get file_ext

                    $ext_parts = (explode(".", $cell["name"]));
                    $ext = (count($ext_parts) > 1) ? end($ext_parts) : '';

                    $cell["extension"] = str_replace('jpeg', 'jpg', strtolower($ext));

                    //fetch preferences

                    if (isset($upload_preferences[$data["gallery_data"]["dir"][$image_id]]))
                    {
                        $cell["dir"] = $upload_preferences[$data["gallery_data"]["dir"][$image_id]]["url"];
                        $cell["server_path"] = $upload_preferences[$data["gallery_data"]["dir"][$image_id]]["server_path"];
                        $cell["image"] = $cell["dir"] . $cell["name"];

                        //get file size
                        $cell["size"] = filesize($upload_preferences[$data["gallery_data"]["dir"][$image_id]]["server_path"] . $cell["name"]);

                        if ($cell["size"] > 1024 * 1024 * 1024)
                        {
                            $cell["size"] = round($cell["size"] / (1024 * 1024 * 1024), 2) . 'GB';
                        }
                        if ($cell["size"] > 1024 * 1024)
                        {
                            $cell["size"] = round($cell["size"] / (1024 * 1024), 2) . 'MB';
                        }
                        if ($cell["size"] > 1024)
                        {
                            $cell["size"] = round($cell["size"] / 1024, 2) . 'kB';
                        }
                        else
                        {
                            $cell["size"] = $cell["size"] . 'B';
                        }

                    }
                    else
                    {
                        $cell["dir"] = "";
                        $cell["server_path"] = "";
                        $cell["image"] = "";
                        $cell["size"] = "0B";
                    }

                    $cell["caption"] = @$data["gallery_data"]["caption"][$image_id];
                    $cell["url"] = @$data["gallery_data"]["url"][$image_id];


                    //thumb

                    if (version_compare(APP_VER, '2.2.0', '<'))
                    {
                        if (file_exists($cell["server_path"] . '_thumbs/thumb_' . $cell["name"]))
                        {
                            $thumb = $cell["dir"] . '_thumbs/thumb_' . $cell["name"];
                        }
                        else
                        {
                            $thumb = PATH_CP_GBL_IMG . 'default.png';
                        }
                    }
                    else
                    {
                        ee()->load->library('filemanager');
                        $thumb_info = ee()->filemanager->get_thumb($cell["name"], $data["gallery_data"]["dir"][$image_id]);
                        $thumb = $thumb_info['thumb'];
                    }


                    $cell["thumb"] = $thumb;

                    //append

                    $images["images"][] = $cell;
                }

        return $images["images"];
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

    /**
     * Get Upload Preferences
     *
     * @param	integer Preference ID
     * @return	array
     */
    function get_file_upload_preferences()
    {
        global $ce_upload_preferences;

        if (isset($ce_upload_preferences))
        {
            return $ce_upload_preferences;
        }

        $ce_upload_preferences = array_filter((array) ee()->config->item('upload_preferences'));
        if (!empty($ce_upload_preferences))
        {
            return $ce_upload_preferences;
        }

        $ce_upload_preferences = array();

        ee()->db->from('upload_prefs');
        ee()->db->order_by('name');

        foreach (ee()->db->get()->result_array() as $row)
        {
            $ce_upload_preferences[$row["id"]] = $row;
        }

        return $ce_upload_preferences;
    }
}