<?php
/**
 * @package     Arastta eCommerce
 * @copyright   2015-2018 Arastta Association. All rights reserved.
 * @copyright   See CREDITS.txt for credits and other copyright notices.
 * @license     GNU GPL version 3; see LICENSE.txt
 * @link        https://arastta.org
 */

class ControllerCommonForgotten extends Controller {
    private $error = array();

    public function index() {
        if ($this->user->isLogged() && isset($this->request->get['token']) && ($this->request->get['token'] == $this->session->data['token'])) {
            $this->response->redirect($this->url->link('common/dashboard', '', 'SSL'));
        }

        if (!$this->config->get('config_password')) {
            $this->response->redirect($this->url->link('common/login', '', 'SSL'));
        }

        $this->load->language('common/forgotten');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('user/user');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->language('mail/forgotten');

            $code = sha1(uniqid(mt_rand(), true));

            $this->model_user_user->editCode($this->request->post['email'], $code);

            // Add secret keyword if set up
            if ($this->config->get('config_sec_admin_keyword')) {
                $admin_keyword = '&' . $this->config->get('config_sec_admin_keyword');
            } else {
                $admin_keyword = '';
            }

            $subject = sprintf($this->language->get('text_subject'), $this->config->get('config_name'));

            $message  = sprintf($this->language->get('text_greeting'), $this->config->get('config_name')) . "\n\n";
            $message .= $this->language->get('text_change') . "\n\n";
            $message .= $this->url->link('common/reset', 'code=' . $code . $admin_keyword, 'SSL') . "\n\n";
            $message .= sprintf($this->language->get('text_ip'), $this->request->server['REMOTE_ADDR']) . "\n\n";

            $mail = new Mail($this->config->get('config_mail'));
            $mail->setTo(html_entity_decode($this->request->post['email'], ENT_QUOTES, 'UTF-8'));
            $mail->setFrom(html_entity_decode($this->config->get('config_email'), ENT_QUOTES, 'UTF-8'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();

            $this->session->data['success'] = $this->language->get('text_success');

            $args = '';
            if (!empty($this->request->post['admin_keyword'])) {
                $args = '&' . $this->request->post['admin_keyword'];
            }

            $this->response->redirect($this->url->link('common/login', $args, 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_your_email'] = $this->language->get('text_your_email');
        $data['text_email'] = $this->language->get('text_email');

        $data['entry_email'] = $this->language->get('entry_email');

        $data['button_reset'] = $this->language->get('button_reset');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', '', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('common/forgotten', 'token=' . '', 'SSL')
        );
        
        $data['action'] = $this->url->link('common/forgotten', '', 'SSL');

        $data['cancel'] = $this->url->link('common/login', '', 'SSL');

        if (isset($this->request->post['email'])) {
            $data['email'] = $this->request->post['email'];
        } else {
            $data['email'] = '';
        }
        
        $this->load->model('tool/image');

        if ($this->config->get('config_image') && is_file(DIR_IMAGE . $this->config->get('config_image'))) {
            $data['thumb'] = $this->model_tool_image->resize($this->config->get('config_image'), 85, 85);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 85, 85);
        }    
        
        $data['store'] = array(
            'name' => $this->config->get('config_name'),
            'href' => ($this->request->server['HTTPS']) ? HTTPS_CATALOG : HTTP_CATALOG
        );

        $data['admin_keyword'] = '';

        if ($this->config->get('config_sec_admin_keyword')) {
            if (isset($this->request->get[$this->config->get('config_sec_admin_keyword')])) {
                $data['admin_keyword'] = $this->config->get('config_sec_admin_keyword');
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('common/forgotten.tpl', $data));
    }

    protected function validate() {
        if (!isset($this->request->post['email'])) {
            $this->error['warning'] = $this->language->get('error_email');
        } elseif (!$this->model_user_user->getTotalUsersByEmail($this->request->post['email'])) {
            $this->error['warning'] = $this->language->get('error_email');
        }

        return !$this->error;
    }
}
