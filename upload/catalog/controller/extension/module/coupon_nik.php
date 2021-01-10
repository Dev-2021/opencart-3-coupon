<?php
class ControllerExtensionModuleCouponNik extends Controller {
    public function index() {
        $this->load->language('extension/module/coupon_nik');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        if (isset($this->request->get['code'])) {
            $coupon_code = $this->request->get['code'];
        } else {
            $coupon_code = 0;
        }

        $this->load->model('extension/module/coupon_nik');

        $coupon_info = $this->model_extension_module_coupon_nik->getCoupon($coupon_code);

        if ($coupon_info) {
            $coupon_info = $coupon_info[0];
            $url = '';

            $data['breadcrumbs'][] = array(
                'text' => $coupon_info['name'],
                'href' => $this->url->link('extension/module/coupon_nik', $url . '&code=' . $this->request->get['code'])
            );

            $couponUsedCount = $this->model_extension_module_coupon_nik->getCouponUsedCount($coupon_info['coupon_id']);

            if($coupon_info['logged']) {
                if($this->customer->isLogged()) {
                    $couponUsedCountByUser = $this->model_extension_module_coupon_nik->getCouponUsedCountByCustomer($coupon_info['coupon_id'], $this->customer->isLogged());

                    if(strtotime($coupon_info['date_start']) <= strtotime(date('Y-m-d')) && strtotime($coupon_info['date_end']) >= strtotime(date('Y-m-d'))) {
                        if((int)$coupon_info['uses_total'] > (int)$couponUsedCount) {
                            if($coupon_info['uses_customer'] > $couponUsedCountByUser) {
                                $data['can_use'] = true;
                                $data['can_use_message'] = '';
                            } else {
                                // error
                                $data['can_use'] = false;
                                $data['can_use_message'] = 'Вы больше не можете использовать данный купон!';
                            }
                        } else {
                            // error
                            $data['can_use'] = false;
                            $data['can_use_message'] = 'Данный купон закончился';
                        }
                    } else {
                        // error
                        $data['can_use'] = false;
                        $data['can_use_message'] = 'Время действия купона истекло';
                    }
                } else {
                    // error
                    $data['can_use'] = false;
                    $data['can_use_message'] = 'Для использования купона необходимо авторизоваться!';
                }
            } else {
                if(strtotime($coupon_info['date_start']) <= strtotime(date('Y-m-d')) && strtotime($coupon_info['date_end']) >= strtotime(date('Y-m-d'))) {
                    if($coupon_info['uses_total'] > $couponUsedCount) {
                        $data['can_use'] = true;
                        $data['can_use_message'] = '';
                    } else {
                        // error
                        $data['can_use'] = false;
                        $data['can_use_message'] = 'Данный купон закончился';
                    }
                } else {
                    // error
                    $data['can_use'] = false;
                    $data['can_use_message'] = 'Время действия купона истекло';
                }
            }

            $this->document->setTitle($coupon_info['name']);
            $this->document->setDescription($coupon_info['name']);
            $this->document->setKeywords($coupon_info['name']);
            $this->document->addLink($this->url->link('extension/module/coupon_nik', 'code=' . $this->request->get['code']), 'canonical');
            $this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
            $this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');
            $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
            $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
            $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
            $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

            $data['heading_title'] = $coupon_info['name'] . ' - ' . $coupon_info['code'];

//            $data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
            $data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('extension/module/coupon_nik', $data));
        } else {
            $url = '';

            if (isset($this->request->get['path'])) {
                $url .= '&path=' . $this->request->get['path'];
            }

            if (isset($this->request->get['filter'])) {
                $url .= '&filter=' . $this->request->get['filter'];
            }

            if (isset($this->request->get['manufacturer_id'])) {
                $url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
            }

            if (isset($this->request->get['search'])) {
                $url .= '&search=' . $this->request->get['search'];
            }

            if (isset($this->request->get['tag'])) {
                $url .= '&tag=' . $this->request->get['tag'];
            }

            if (isset($this->request->get['description'])) {
                $url .= '&description=' . $this->request->get['description'];
            }

            if (isset($this->request->get['category_id'])) {
                $url .= '&category_id=' . $this->request->get['category_id'];
            }

            if (isset($this->request->get['sub_category'])) {
                $url .= '&sub_category=' . $this->request->get['sub_category'];
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_error'),
                'href' => $this->url->link('extension/module/coupon_nik', $url . '&code=' . $coupon_code)
            );

            $this->document->setTitle($this->language->get('text_error'));

            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function getCoupon() {

        $this->response->setOutput(true);
    }
}