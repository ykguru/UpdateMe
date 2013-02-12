<?php

/**
 * Copyright (c) 2013 Yakub Kristianto
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package UpdateMe
 * @version 0.0.1
 * @author Yakub Kristianto
 * @copyright Yakub Kristianto 2013
 */

class UpdateMe
{
    const version = '0.0.1';
    private $PATCH_URL = '';
    private $LOCAL_BASE_DIR = '';
    private $LOCAL_BACKUP_DIR = '';
    private $default_directory_mode = '0755';
    private $version_filename = 'version.txt';

    public function __construct($config)
    {
        if (substr($config['PATCH_URL'], -1) != '/') {
            $config['PATCH_URL'] .= '/';
        }

        $this->PATCH_URL = $config['PATCH_URL'];
        $this->LOCAL_BASE_DIR = $config['LOCAL_BASE_DIR'];
        $this->LOCAL_BACKUP_DIR = $config['LOCAL_BACKUP_DIR'];
    }

    public function check_update()
    {

        $local_ver = $this->check_local_version();
        $server_ver = $this->check_server_version();
        if (version_compare($server_ver, $local_ver))
            return $server_ver;
        else
            return FALSE;
    }

    public function check_server_version()
    {
        // Get Patch version
        $ch = curl_init($this->PATCH_URL.$this->version_filename);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $str = curl_exec($ch);
        curl_close($ch);

        $server_vesion = $this->str_to_version_info_($str);
        return $server_vesion;
    }

    public function check_local_version()
    {
        // Get local version
        if (file_exists($path = $this->LOCAL_BACKUP_DIR.$this->version_filename)) {
            $str = file_get_contents($path);
            $local_version = $this->str_to_version_info_($str);
        }
        else
            $local_version = FALSE;

        return $local_version;
    }

    public function get_patch_version($version)
    {
        $ch = curl_init($this->PATCH_URL.$version.'.zip');
        $fp = fopen($this->LOCAL_BACKUP_DIR.$version.'.zip', "w");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return true;
    }

    public function update($version = FALSE)
    {
        $files = $this->get_backed_up_files();

        if ($version === FALSE) {
            // Update to latest version
            $version = key($files);
        }

        if (!isset($files[$version])) {
            throw new Exception('Version '.$version.' not found!');
        }

        // Get list of file in zip & pre-check routine
        $zip = new ZipArchive();
        $zip->open($this->LOCAL_BACKUP_DIR.$files[$version]);
        for( $i = 0; $i < $zip->numFiles; $i++ ) {
            $stat = $zip->statIndex( $i );
            $name = $stat['name'];
            $path = $this->LOCAL_BASE_DIR.$name;

            // It's directory
            if (substr($name, -1) == '/') {
                // Directory not exist? Create it
                if (!file_exists($path)) {
                    mkdir($path, $this->default_directory_mode, TRUE);
                }
            }
            // It's file
            else {

            }
        }

        // Extract the zip file
        $zip->extractTo($this->LOCAL_BASE_DIR);
        $zip->close();

        // Update local version info
        file_put_contents($this->LOCAL_BACKUP_DIR.$this->version_filename, $version);
    }

    /**
     * Get list of files in local backup directory.
     * @return array List of version and filename. Eg: array('1.0.0' => '1.0.0.Zip', '1.0.1' => '1.0.1.zip')
     */
    public function get_backed_up_files()
    {
        $files = scandir($this->LOCAL_BACKUP_DIR);
        $list = array();
        foreach ($files as $file) {
            if (preg_match('/^(\d+\.\d+\.\d+)\.zip$/i', $file, $match) && is_file($this->LOCAL_BACKUP_DIR.$file)) {
                $list[$match[1]] = $file;
            }
        }
        arsort($list);
        return $list;
    }

    public function check_dependencies($return_report = FALSE)
    {
        $complete = TRUE;
        $report = array(
            'extension' => array(
                'curl' => TRUE,
            ),
            'class' => array(
                'ZipArchive' => TRUE,
            ),
        );

        // check if extension loaded
        foreach ($report['extension'] as $ext => &$loaded) {
            if (!$loaded = extension_loaded($ext)) $complete = FALSE;
        }

        // check if class exist
        foreach ($report['class'] as $class => &$exist) {
            if (!$exist = class_exists($class)) $complete = FALSE;
        }

        return ($return_report) ? $report : $complete;
    }

    private function str_to_version_info_($string)
    {
        $lines = explode("\n", $string);
        if (!$lines) return FALSE;

        $line = str_replace("\t", ' ', trim($lines[0]));
        $pieces = explode(" ", $line);
        $latest_version = trim($pieces[0]);
        return $latest_version;
    }

}