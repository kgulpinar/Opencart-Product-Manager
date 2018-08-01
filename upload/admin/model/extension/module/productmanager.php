<?php
class ModelExtensionModuleProductManager extends Model {

	public function getProducts($data = array()) {
		$sql = "SELECT
					p.*, ua.keyword as keyword, pd.name as name, m.name as manufacturer, w.title as weight_class, l.title as length_class,  s.name as stock_status, t.title as tax_class 
				FROM 
					" . DB_PREFIX . "product p 
					LEFT JOIN " . DB_PREFIX . "weight_class_description w ON (p.weight_class_id = w.weight_class_id)
					LEFT JOIN " . DB_PREFIX . "stock_status s ON (p.stock_status_id = s.stock_status_id)
					LEFT JOIN " . DB_PREFIX . "tax_class t ON (p.tax_class_id = t.tax_class_id)
					LEFT JOIN " . DB_PREFIX . "length_class_description l ON (p.length_class_id = l.length_class_id)
					LEFT JOIN " . DB_PREFIX . "product_to_category pt ON (p.product_id = pt.product_id)
					LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
					LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
					LEFT JOIN " . DB_PREFIX . "seo_url ua ON (CONCAT('product_id=', p.product_id) = ua.query)
				WHERE 
					pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (isset($data['price_operation']) && !is_null($data['price_operation'])) {
			if ($data['price_operation'] == 'between') {
				if (isset($data['filter_price_from']) && !is_null($data['filter_price_from'])) {
					$sql .= " AND p.price >= " . (float)$data['filter_price_from'];
				}

				if (isset($data['filter_price_to']) && !is_null($data['filter_price_to'])) {
					$sql .= " AND p.price <= " . (float)$data['filter_price_to'];
				}
			} else {
				switch ($data['price_operation']) {
					case 'more_than' : $operation = '>'; break;
					case 'less_than' : $operation = '<'; break;
					case 'equal_to'  : $operation = '=';
				}

				if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
					$sql .= " AND p.price " . $operation . (float)$data['filter_price'];
				}
			}
		}

		if (isset($data['filter_category']) && !is_null($data['filter_category'])) {
			$sql .= " AND pt.category_id = '" . (int)$data['filter_category'] . "'";
		}

		if (isset($data['quantity_operation']) && !is_null($data['quantity_operation'])) {
			if ($data['quantity_operation'] == 'between') {
				if (isset($data['filter_quantity_from']) && !is_null($data['filter_quantity_from'])) {
					$sql .= " AND p.quantity >= " . (float)$data['filter_quantity_from'];
				}

				if (isset($data['filter_quantity_to']) && !is_null($data['filter_quantity_to'])) {
					$sql .= " AND p.quantity <= " . (float)$data['filter_quantity_to'];
				}
			} else {
				switch ($data['quantity_operation']) {
					case 'more_than' : $operation = '>'; break;
					case 'less_than' : $operation = '<'; break;
					case 'equal_to'  : $operation = '=';
				}

				if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
					$sql .= " AND p.quantity " . $operation . (float)$data['filter_quantity'];
				}
			}
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
		if (isset($data['filter_sku']) && !is_null($data['filter_sku'])) {
			$sql .= " AND p.sku LIKE '" . $this->db->escape($data['filter_sku']) . "%'";
		}
		
		if (!empty($data['filter_manufacturer'])) {
			$sql .= " AND p.manufacturer_id = '" . $this->db->escape($data['filter_manufacturer']) . "'";
		}
		
		$sql .= " GROUP BY p.product_id";

		if (isset($data['sort']) && !empty($data['sort'])) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
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
	
	public function getTotalProducts($data = array()) {
	    
        $sql = "SELECT
					count(DISTINCT p.product_id) as total
				FROM 
					" . DB_PREFIX . "product p 
					LEFT JOIN " . DB_PREFIX . "weight_class_description w ON (p.weight_class_id = w.weight_class_id)
					LEFT JOIN " . DB_PREFIX . "stock_status s ON (p.stock_status_id = s.stock_status_id)
					LEFT JOIN " . DB_PREFIX . "tax_class t ON (p.tax_class_id = t.tax_class_id)
					LEFT JOIN " . DB_PREFIX . "length_class_description l ON (p.length_class_id = l.length_class_id)
					LEFT JOIN " . DB_PREFIX . "product_to_category pt ON (p.product_id = pt.product_id)
					LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
					LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
				WHERE 
					pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
                    
		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (isset($data['price_operation']) && !is_null($data['price_operation'])) {
			if ($data['price_operation'] == 'between') {
				if (isset($data['filter_price_from']) && !is_null($data['filter_price_from'])) {
					$sql .= " AND p.price >= " . (float)$data['filter_price_from'];
				}

				if (isset($data['filter_price_to']) && !is_null($data['filter_price_to'])) {
					$sql .= " AND p.price <= " . (float)$data['filter_price_to'];
				}
			} else {
				switch ($data['price_operation']) {
					case 'more_than' : $operation = '>'; break;
					case 'less_than' : $operation = '<'; break;
					case 'equal_to'  : $operation = '=';
				}

				if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
					$sql .= " AND p.price " . $operation . (float)$data['filter_price'];
				}
			}
		}

		if (isset($data['filter_category']) && !is_null($data['filter_category'])) {
			$sql .= " AND pt.category_id = '" . (int)$data['filter_category'] . "'";
		}

		if (isset($data['quantity_operation']) && !is_null($data['quantity_operation'])) {
			if ($data['quantity_operation'] == 'between') {
				if (isset($data['filter_quantity_from']) && !is_null($data['filter_quantity_from'])) {
					$sql .= " AND p.quantity >= " . (float)$data['filter_quantity_from'];
				}

				if (isset($data['filter_quantity_to']) && !is_null($data['filter_quantity_to'])) {
					$sql .= " AND p.quantity <= " . (float)$data['filter_quantity_to'];
				}
			} else {
				switch ($data['quantity_operation']) {
					case 'more_than' : $operation = '>'; break;
					case 'less_than' : $operation = '<'; break;
					case 'equal_to'  : $operation = '=';
				}

				if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
					$sql .= " AND p.quantity " . $operation . (float)$data['filter_quantity'];
				}
			}
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
		if (isset($data['filter_sku']) && !is_null($data['filter_sku'])) {
			$sql .= " AND p.sku LIKE '" . $this->db->escape($data['filter_sku']) . "%'";
		}
		
		if (!empty($data['filter_manufacturer'])) {
			$sql .= " AND p.manufacturer_id = '" . $this->db->escape($data['filter_manufacturer']) . "'";
		}
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	
	public function getProductName($get_data) {
		$this->load->model('localisation/language');
		$json 			= array();	
		$field			= $get_data['pdata'];
		$product_id		= $get_data['pid'];
		$all_languages	= $this->model_localisation_language->getLanguages();
		$images			= array();
		
		foreach($all_languages as $lang) {
            $images[$lang['language_id']] = 'language/'.$lang['code'].'/'.$lang['code'].'.png';
		}
		
		$query = $this->db->query("SELECT name, language_id FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
		
		foreach  ($query->rows as $row) {
			$json[$row['language_id']] = array('name' => $row['name'], 'image' => $images[$row['language_id']]);	
		}
		
		return $json;
	}
    
    public function getProductUrlAlias($get_data) {
		$this->load->model('localisation/language');
		$json 			= array();	
		$field			= $get_data['pdata'];
		$product_id		= $get_data['pid'];
		$all_languages	= $this->model_localisation_language->getLanguages();
		$images			= array();
		
		foreach($all_languages as $lang) {
            $images[$lang['language_id']] = 'language/'.$lang['code'].'/'.$lang['code'].'.png';
		}
		
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE `query` = 'product_id=" . (int)$product_id . "'");
		
		foreach  ($query->rows as $row) {
			$json[$row['language_id']] = array('keyword' => $row['keyword'], 'image' => $images[$row['language_id']]);	
		}
		
		return $json;
	}
	
	public function getProductCategories($get_data) {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$json 			= array();	
		$product_id		= $get_data['pid'];
		
		$categories = $this->model_catalog_product->getProductCategories($product_id);
		
		foreach ($categories as $category_id) {
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$json[] = array(
					'category_id' => $category_info['category_id'],
					'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
				);
			}
		}

		return $json;
	}

	public function getProductFilters($get_data){
		$this->load->model('catalog/product');
		$this->load->model('catalog/filter');

		$json = array();
		$product_id = $get_data['pid'];

		$filters = $this->model_catalog_product->getProductFilters($product_id);

		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter($filter_id);

			if($filter_info){
				$json[] = array(
					'filter_id' => $filter_info['filter_id'],
					'name' => ($filter_info['group']) ? $filter_info['group'] . ' &gt; ' . $filter_info['name'] : $filter_info['name']
				);
			}
		}

		return $json;
	}

	public function getCurrencySign() {
		$query = $this->db->query("SELECT symbol_left, symbol_right FROM " . DB_PREFIX . "currency WHERE code = '" . $this->db->escape($this->config->get('config_currency')) . "'");
		
		foreach ($query->row as $key => $value) {
			if (!empty($value))
				return $value;
		}

		return false;
	}

	public function getProductOrders($product_id) {
		$orders = array();

		$query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order_product WHERE product_id = '" . (int)$product_id . "'");

		if ($query->num_rows) {
			foreach ($query->rows as $result) {
				$order_query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname, o.total, o.date_added, os.name AS order_status FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS os ON o.order_status_id = os.order_status_id WHERE o.order_id='" . (int)$result['order_id'] . "'");

				foreach ($order_query->rows as $result2) {
					$orders[] = $result2;
				}
			}
		} else return '';

		return $orders;
	}

}