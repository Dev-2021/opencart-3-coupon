<?php
class ControllerExtensionModuleCouponNik extends Controller {
    private $error = array();

    public function index() {
        $this->load->model('extension/module/coupon_nik');
        $this->load->language('extension/module/coupon_nik');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('marketing/coupon');

        $this->getList();
    }

    public function add() {
        $this->load->language('extension/module/coupon_nik');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/coupon_nik');
        $this->load->model('marketing/coupon');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
            $coupon_template = $this->request->post;

            if(isset($coupon_template['coupon_customer'])) { // coupons for customers
                $this->load->model('customer/customer');
                foreach ($coupon_template['coupon_customer'] as $customer_id) {
                    $customer = $this->model_customer_customer->getCustomer($customer_id);

                    $coupon_template['name'] = $this->language->get('text_coupon_for') . ' ' . $customer['lastname'] . ' ' . $customer['firstname'];
                    $coupon_template['code'] = $this->generateCode();

                    $coupon_id = $this->model_marketing_coupon->addCoupon($coupon_template);
                    if ($coupon_template['coupon_receive_method'] == '1') { // method to receive - by link
                        $coupon_link = $coupon_template['coupon_link'] . '&code=' . $coupon_template['code'];
                    } else {
                        $coupon_link = '';
                    }
                    // save to our db
                    $data = array(
                        'coupon_id'   => $coupon_id,
                        'coupon_code' => $coupon_template['code'],
                        'customer_id' => $customer['customer_id'],
                        'coupon_link' => $coupon_link
                    );
                    $this->model_extension_module_coupon_nik->add($data);

                    if(isset($coupon_template['send']) && $coupon_template['send'] != '0') {
                        // send to customers
                        $send_info = array(
                            'email' => $customer['email'],
                            'name'  => $customer['lastname'] . ' ' . $customer['firstname'],
                            'code'  => $coupon_template['code'],
                            'coupon_alert_heading' => sprintf($this->language->get('text_mailing_heading_title'), ($customer['lastname'] . ' ' . $customer['firstname'])),
                            'coupon_alert_body' => sprintf($this->language->get('text_mailing_body'), $coupon_template['code']),
                            'link' => $coupon_link
                        );
                        $this->sendCoupon($send_info);
                        $this->session->data['sended'] = $this->language->get('text_sended');
                    }
                }

            } else {
                $count = '';
                $names = $this->model_extension_module_coupon_nik->getCouponsByName($this->language->get('text_coupon_number'));

                $last_name = end($names);
                if ($last_name) {
                    $part = explode(' ', $last_name['name']);
                    $count = (int)ltrim($part[1],'№');
                }
                for($i = 0; $i < $coupon_template['coupon_count']; $i++) {
                    if(is_int($count)) {
                        $coupon_template['name'] = $this->language->get('text_coupon_number') . ($count + $i + 1);
                    } else {
                        $coupon_template['name'] = $this->language->get('text_coupon_number') . ($i + 1);
                    }
                    $coupon_template['code'] = $this->generateCode();

                    $coupon_id = $this->model_marketing_coupon->addCoupon($coupon_template);

                    if(isset($coupon_template['coupon_link'])) {
                        if ($coupon_template['coupon_receive_method'] == '1') { // method to receive - by link
                            $coupon_link = $coupon_template['coupon_link'] . '&code=' . $coupon_template['code'];
                        } else {
                            $coupon_link = '';
                        }

                        $data = array(
                            'coupon_id'   => $coupon_id,
                            'coupon_code' => $coupon_template['code'],
                            'customer_id' => '',
                            'coupon_link' => $coupon_link
                        );
                        $this->model_extension_module_coupon_nik->add($data);
                    }

                }
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            $this->response->redirect($this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
		$this->load->language('extension/module/coupon_nik');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/coupon_nik');
        $this->load->model('marketing/coupon');

		if (isset($this->request->get['coupon_id']) && $this->validatePermission()) {
			$this->model_marketing_coupon->deleteCoupon($this->request->get['coupon_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$this->response->redirect($this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

        if (isset($this->request->post['selected']) && $this->validatePermission()) {
            foreach ($this->request->post['selected'] as $coupon_id) {
                $this->model_marketing_coupon->deleteCoupon($coupon_id);
                $this->model_extension_module_coupon_nik->delete($coupon_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            $this->response->redirect($this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

		$this->getList();
	}

    public function repair() {
        $this->load->language('extension/module/coupon_nik');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/coupon_nik');

        if ($this->validatePermission()) {
            $this->model_extension_module_coupon_nik->repairRelations();

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . $this->request->get['filter_name'];
            }

            $this->response->redirect($this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['filter_code'])) {
            $filter_code = $this->request->get['filter_code'];
        } else {
            $filter_code = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'coupon_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_code'])) {
            $url .= '&filter_code=' . urlencode(html_entity_decode($this->request->get['filter_code'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('extension/module/coupon_nik/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['repair'] = $this->url->link('extension/module/coupon_nik/repair', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('extension/module/coupon_nik/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['coupons'] = array();

        $filter_data = array(
            'filter_code' => $filter_code,
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $results = $this->model_extension_module_coupon_nik->getCoupons($filter_data);

        if(!$filter_code) {
            $coupon_total = $this->model_marketing_coupon->getTotalCoupons();
        } else {
            $coupon_total = count($results);
        }

        foreach ($results as $result) {
            $link_path = $this->model_extension_module_coupon_nik->getCouponLink($result['coupon_id']);

            if (!empty($link_path) && isset($link_path['coupon_link']) && !empty($link_path['coupon_link'])) {
                $link = HTTPS_CATALOG . $link_path['coupon_link'];
            } else {
                $link = '';
            }

            $data['coupons'][] = array(
                'coupon_id'  => $result['coupon_id'],
                'name'       => $result['name'] . ($result['discount'] != 0 ? ' (<span class="text-success">' . $this->language->get('entry_discount') . ' ' . (int)$result['discount'] . ($result['type'] == 'P' ? '%' : '') . '</span> ' . (!$result['uses_total'] ? ')' : '') : "") . ($result['uses_total'] ?  ($result['discount'] == 0 ? ' (' : '') . '<span class="text-danger">' . $this->language->get('text_uses') . ' ' . $result['uses_total'] . '</span>)' : ""),
                'code'       => $result['code'],
                'discount'   => $result['discount'],
                'customer_id'=> $result['customer_id'],
                'date_start' => date($this->language->get('date_format_short'), strtotime($result['date_start'])),
                'date_end'   => date($this->language->get('date_format_short'), strtotime($result['date_end'])),
                'coupon_link'=> $link,
                'status'     => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'edit'       => $this->url->link('marketing/coupon/edit', 'user_token=' . $this->session->data['user_token'] . '&coupon_id=' . $result['coupon_id'] . $url, true)
            );
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['sended'])) {
            $data['sended'] = $this->session->data['sended'];

            unset($this->session->data['sended']);
        } else {
            $data['sended'] = '';
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_code'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . '&sort=code' . $url, true);
        $data['sort_discount'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . '&sort=discount' . $url, true);
        $data['sort_date_start'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . '&sort=date_start' . $url, true);
        $data['sort_date_end'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . '&sort=date_end' . $url, true);
        $data['sort_status'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $coupon_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($coupon_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($coupon_total - $this->config->get('config_limit_admin'))) ? $coupon_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $coupon_total, ceil($coupon_total / $this->config->get('config_limit_admin')));

        $data['filter_code'] = $filter_code;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/coupon_nik_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['coupon_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->get['coupon_id'])) {
            $data['coupon_id'] = $this->request->get['coupon_id'];
        } else {
            $data['coupon_id'] = 0;
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['coupon_data'])) {
            $data['error_coupon_data'] = $this->error['coupon_data'];
        } else {
            $data['error_coupon_data'] = '';
        }

        if (isset($this->error['date_start'])) {
            $data['error_date_start'] = $this->error['date_start'];
        } else {
            $data['error_date_start'] = '';
        }

        if (isset($this->error['date_end'])) {
            $data['error_date_end'] = $this->error['date_end'];
        } else {
            $data['error_date_end'] = '';
        }

        $url = '';

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['coupon_id'])) {
			$data['action'] = $this->url->link('extension/module/coupon_nik/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/module/coupon_nik/edit', 'user_token=' . $this->session->data['user_token'] . '&coupon_id=' . $this->request->get['coupon_id'] . $url, true);
		}

        $data['cancel'] = $this->url->link('extension/module/coupon_nik', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $this->load->model('customer/customer_group');

        $data['customer_group'] = array();

        $results = $this->model_customer_customer_group->getCustomerGroups();

        foreach ($results as $result) {
            $data['customer_groups'][] = array(
                'customer_group_id' => $result['customer_group_id'],
                'name'              => $result['name'] . (($result['customer_group_id'] == $this->config->get('config_customer_group_id')) ? $this->language->get('text_default') : null),
            );
        }

        if (isset($this->request->post['date_start'])) {
            $data['date_start'] = $this->request->post['date_start'];
        } elseif (!empty($coupon_info)) {
            $data['date_start'] = ($coupon_info['date_start'] != '0000-00-00' ? $coupon_info['date_start'] : '');
        } else {
            $data['date_start'] = date('Y-m-d', time());
        }

        if (isset($this->request->post['date_end'])) {
            $data['date_end'] = $this->request->post['date_end'];
        } elseif (!empty($coupon_info)) {
            $data['date_end'] = ($coupon_info['date_end'] != '0000-00-00' ? $coupon_info['date_end'] : '');
        } else {
            $data['date_end'] = date('Y-m-d', strtotime('+1 month'));
        }

        $data['code'] = '';

        $this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/coupon_nik_form', $data));
    }

    public function getCustomersByGroup() {
        $json = array();

        if (isset($this->request->get['filter_customer_group_id'])) {
            if (isset($this->request->get['filter_customer_group_id'])) {
                $filter_customer_group_id = $this->request->get['filter_customer_group_id'];
            } else {
                $filter_customer_group_id = '';
            }

            $this->load->model('customer/customer');

            $filter_data = array(
                'filter_customer_group_id'      => $filter_customer_group_id,
            );

            $results = $this->model_customer_customer->getCustomers($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'customer_id' => $result['customer_id'],
                    'name'        => strip_tags(html_entity_decode($result['name'] . ' (' . $result['customer_group'] . ')', ENT_QUOTES, 'UTF-8')),
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function generateCode() {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $code = substr(str_shuffle($permitted_chars), 0, mt_rand(3, 10));


        while($this->validateCode($code)) {
            $code = substr(str_shuffle($permitted_chars), 0, mt_rand(3, 10));
        }

        return $code;
    }

    private function validateCode($code) {
        $coupon_info = $this->model_marketing_coupon->getCouponByCode($code);

        return !empty($coupon_info);
    }

    public function sendCoupons() {
        if($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('extension/module/coupon_nik');
            $this->load->model('customer/customer');
            $coupons = $this->model_extension_module_coupon_nik->getCouponsWithCustomer();
            foreach ($coupons as $coupon) {
                $customer = $results = $this->model_customer_customer->getCustomer($coupon['customer_id']);
//                $link = $this->url->link('extension/module/coupon_nik', '&code=' . $coupon['coupon_code']);

                if (isset($coupon['coupon_link']) && !empty($coupon['coupon_link'])) {
                    $link = HTTPS_CATALOG . $coupon['coupon_link'];
                } else {
                    $link = '';
                }

                $send_info = array(
                    'email' => $customer['email'],
                    'name'  => $customer['lastname'] . ' ' . $customer['firstname'],
                    'code'  => $coupon['coupon_code'],
                    'coupon_alert_heading' => sprintf($this->language->get('text_mailing_heading_title'), ($customer['lastname'] . ' ' . $customer['firstname'])),
                    'coupon_alert_body' => sprintf($this->language->get('text_mailing_body'), $coupon['coupon_code']),
                    'link'  => $link
                );
                $this->sendCoupon($send_info);
            }
        }
    }

    public function sendCouponToCustomer() {
        if(isset($this->request->get['coupon_id']) && $this->request->server['REQUEST_METHOD'] == 'GET') {
            $this->load->model('extension/module/coupon_nik');
            $this->load->model('customer/customer');
            $coupon = $this->model_extension_module_coupon_nik->getCoupon($this->request->get['coupon_id']);

            if(isset($this->request->get['customer_email'])) {
                $customer = $this->model_customer_customer->getCustomerByEmail($this->request->get['customer_email']);
            } else {
                $customer = $this->model_customer_customer->getCustomer($coupon['customer_id']);
            }

            $link_path = $this->model_extension_module_coupon_nik->getCouponLink($this->request->get['coupon_id']);

            if (!empty($link_path) && isset($link_path['coupon_link']) && !empty($link_path['coupon_link'])) {
                $link = HTTPS_CATALOG . $link_path['coupon_link'];
            } else {
                $link = '';
            }

//            $link = $this->url->link('extension/module/coupon_nik', '&code=' . $coupon['code']);
            $send_info = array(
                'email' => $customer['email'],
                'name'  => $customer['lastname'] . ' ' . $customer['firstname'],
                'code'  => $coupon['code'],
                'link'  => $link
            );
            $this->sendCoupon($send_info);
        }
    }

    private function sendCoupon($data) {
        $this->load->language('extension/module/coupon_nik');

        $from = $this->config->get('config_email');

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

        $mail->setTo($data['email']);
        $mail->setFrom($from);
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(html_entity_decode(sprintf($this->language->get('text_you_got_new_coupon')), ENT_QUOTES, 'UTF-8'));

        $mail->setHtml($this->load->view('mail/coupon_alert', $data));

        $mail->send();
    }

    public function install() {
        $this->load->model('extension/module/coupon_nik');

        $this->model_extension_module_coupon_nik->install();
    }

    public function uninstall() {
        if ($this->user->hasPermission('modify', 'extension/module/coupon_nik')) {
            $this->load->model('extension/module/coupon_nik');

            $this->model_extension_module_coupon_nik->uninstall();
        }
    }

    public function configure() {
        $this->load->language('extension/extension/module');

        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
        } else {
            $this->load->model('user/user_group');

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/pp_button');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/pp_button');

            $this->install();

            $this->response->redirect($this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    protected function validatePermission() {
        if (!$this->user->hasPermission('modify', 'extension/module/coupon_nik')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'extension/module/coupon_nik')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        if (!$this->request->post['coupon_count'] && !isset($this->request->post['coupon_customer'])) {
            $this->error['coupon_data'] = $this->language->get('error_coupon_data');
        }

        return !$this->error;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/coupon_nik')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }
}
