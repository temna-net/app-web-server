<?php

/**
 * Web server sites controller.
 *
 * @category   Apps
 * @package    Web_Server
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_server/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web server sites controller.
 *
 * @category   Apps
 * @package    Web_Server
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_server/
 */

class Sites extends ClearOS_Controller
{
    /**
     * Sites summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('web_server');
        $this->load->library('web_server/Httpd');

        // Load view data
        //---------------

        try {
            $data['sites'] = $this->httpd->get_sites();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('web_server/sites', $data, lang('web_server_web_sites'));
    }

    /**
     * Add view.
     *
     * @param string $site site
     *
     * @return view
     */

    function add($site = NULL)
    {
        $this->_item($site, 'add');
    }

    /**
     * Delete view.
     *
     * @param string $site site
     *
     * @return view
     */

    function delete($site = NULL)
    {
        $confirm_uri = '/app/web_server/sites/destroy/' . $site;
        $cancel_uri = '/app/web_server/sites';
        $items = array($site);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Edit view.
     *
     * @param string $site site
     *
     * @return view
     */

    function edit($site = NULL)
    {
        $this->_item($site, 'edit');
    }

    /**
     * Destroys site.
     *
     * @param string $site site
     *
     * @return view
     */

    function destroy($site = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_server/Httpd');

        // Handle delete
        //--------------

        try {
            $this->httpd->delete_site($site);

            $this->page->set_status_deleted();
            redirect('/web_server/sites');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Common form.
     *
     * @param string $site    site
     * @param string $form_type form type
     *
     * @return view
     */

    function _item($site, $form_type)
    {
        // Load libraries
        //---------------

        $this->lang->load('web_server');
        $this->load->library('web_server/Httpd');
        $this->load->factory('groups/Group_Manager_Factory');

        // Set validation rules
        //---------------------

        $check_exists = ($form_type === 'add') ? TRUE : FALSE;

        $this->form_validation->set_policy('site', 'web_server/Httpd', 'validate_site', TRUE, $check_exists);
        // $this->form_validation->set_policy('aliases', 'web_server/Httpd', 'validate_aliases', TRUE);
        // $this->form_validation->set_policy('ftp', 'web_server/Httpd', 'validate_ftp_state', TRUE);
        // $this->form_validation->set_policy('file', 'web_server/Httpd', 'validate_file_state', TRUE);
        // $this->form_validation->set_policy('group', 'web_server/Httpd', 'validate_group', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            try {
                if ($form_type === 'edit')  {
                    $this->httpd->set_site(
                        $this->input->post('site'),
                        $this->input->post('aliases'),
                        $this->input->post('group'),
                        $this->input->post('ftp'),
                        $this->input->post('file'),
                        FALSE // FIXME
                    );
                } else {
                    $this->httpd->add_site(
                        $this->input->post('site'),
                        $this->input->post('aliases'),
                        $this->input->post('group'),
                        $this->input->post('ftp'),
                        $this->input->post('file'),
                        FALSE // FIXME
                    );
                }

                $this->httpd->reset();

                $this->page->set_status_added();
//                redirect('/web_server/sites');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['form_type'] = $form_type;

            if ($form_type === 'edit')
                $info = $this->httpd->get_site_info($site);

            $data['site'] = empty($info['server_name']) ? '' :  $info['server_name'];
            $data['aliases'] = empty($info['aliases']) ? '' :  $info['aliases'];
            $data['ftp'] = empty($info['ftp']) ? FALSE :  $info['ftp'];
            $data['file'] = empty($info['file']) ? FALSE :  $info['file'];
            $data['group'] = empty($info['group']) ? '' :  $info['group'];

            // FIXME: move this logic to group manager... it's used a lot.
            $normal_groups = $this->group_manager->get_details();
            $builtin_groups = $this->group_manager->get_details('builtin');

            $groups = array_merge($builtin_groups, $normal_groups);

            foreach ($groups as $group => $details)
                $data['groups'][$group] = $group . ' - ' . $details['description'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('web_server/site', $data, lang('web_server_web_site'));
    }
}