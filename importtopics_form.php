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
 *
 * @author      Bikram Kawan <bikram@sproutlabs.com.au>
 * @copyright   (c) Sproutlabs Pty Ltd.
 * @license     GNU General Public License version 3
 */
    require_once($CFG->libdir.'/formslib.php');



    /**
     * Form definition for the plugin
     *
     */
    class import_topics_form extends moodleform {

        /**
         * Define the form's contents
         * @see moodleform::definition()
         */
        public function definition()
        {


            // File picker
            $this->_form->addElement('header', 'identity', get_string('importcsv', mod_ojt_plugin::PLUGIN_NAME));

            $this->_form->addElement('filepicker', mod_ojt_plugin::FORMID_FILES, null, null, $this->_customdata['options']);
            $this->_form->addHelpButton(mod_ojt_plugin::FORMID_FILES, 'importcsv', mod_ojt_plugin::PLUGIN_NAME);
            $this->_form->addRule(mod_ojt_plugin::FORMID_FILES, null, 'required', null, 'client');
            $this->add_action_buttons(true, get_string('import', mod_ojt_plugin::PLUGIN_NAME));
            

        } // definition



        /**
         * Validate the submitted form data
         * @see moodleform::validation()
         */
        public function validation($data, $files)
        {
            global $USER;
            $result = array();
            return $result;
        } // validation
        
    }

    class import_completions_form extends moodleform {

    /**
     * Define the form's contents
     * @see moodleform::definition()
     */
    public function definition()
    {


        // File picker
        $this->_form->addElement('header', 'identity', get_string('importcompletion', mod_ojt_plugin::PLUGIN_NAME));

        $this->_form->addElement('filepicker', mod_ojt_plugin::FORMID_FILES, null, null, $this->_customdata['options']);
        $this->_form->addHelpButton(mod_ojt_plugin::FORMID_FILES, 'importcompletion', mod_ojt_plugin::PLUGIN_NAME);
        $this->_form->addRule(mod_ojt_plugin::FORMID_FILES, null, 'required', null, 'client');
        $this->add_action_buttons(true, get_string('import', mod_ojt_plugin::PLUGIN_NAME));


    } // definition



    /**
     * Validate the submitted form data
     * @see moodleform::validation()
     */
    public function validation($data, $files)
    {
        global $USER;
        $result = array();
        return $result;
    } // validation

} 