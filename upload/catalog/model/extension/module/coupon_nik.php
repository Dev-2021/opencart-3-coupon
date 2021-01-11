<?php
class ModelExtensionModuleCouponNik extends Model {
    public function getCoupons($data = array()) {
        $sql = "SELECT `coupon_id`, `name`, `code`, `discount`, `date_start`, `date_end`, `status` FROM " . DB_PREFIX . "coupon";

        if (!empty($data['filter_code'])) {
            $sql .= " WHERE code LIKE '" . $this->db->escape($data['filter_code']) . "%'";
        }

        $sort_data = array(
            'name',
            'code',
            'discount',
            'date_start',
            'date_end',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function isExist($code) {
        $sql = "SELECT `coupon_id` FROM " . DB_PREFIX . "customer_coupon WHERE `coupon_code` = '" . $this->db->escape($code) . "'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function useCoupon($code, $customer_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_coupon WHERE `coupon_code` = '" . $this->db->escape($code) . "'");
        $coupon_info = $query->rows[0];
        $this->db->query("INSERT INTO " . DB_PREFIX . "customer_coupon SET `coupon_id` = '" . (int)$coupon_info['coupon_id'] . "', coupon_code = '" . $this->db->escape($code) . "', customer_id = '" . (int)$customer_id . "', coupon_link = '" . $this->db->escape($coupon_info['coupon_link']) . "'");
    }

    public function getCoupon($code) {
        $sql = "SELECT * FROM " . DB_PREFIX . "coupon WHERE `code` = '" . $this->db->escape($code) . "'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCouponUsedCount($coupon_id) {
        $sql = "SELECT COUNT(`coupon_id`) as q FROM " . DB_PREFIX . "coupon_history WHERE `coupon_id` = '" . (int)$coupon_id . "'";

        $query = $this->db->query($sql);

        return $query->rows[0]['q'];
    }

    public function getCouponUsedCountByCustomer($coupon_id, $customer_id) {
        $sql = "SELECT COUNT(`coupon_id`) as q FROM " . DB_PREFIX . "coupon_history WHERE `coupon_id` = '" . (int)$coupon_id . "' AND `customer_id` = '" . (int)$customer_id . "'";

        $query = $this->db->query($sql);

        return $query->rows[0]['q'];
    }

    public function log($data) {
        // if ($this->config->has('payment_stripe_logging') && $this->config->get('payment_stripe_logging')) {
        $log = new Log('mailing.log');

        $log->write($data);
        // }
    }
}