<?php
class ControllerExtensionModuleProductManager extends Controller {
	private $error = array();
	private $moduleVersion = '';
	private $modulePath = '';
	private $moduleName = '';
	private $moduleModel = '';
	private $eventGroup = '';

	public function __construct($registry) {
		parent::__construct($registry);

		$this->config->load('isenselabs/productmanager');

		$this->moduleVersion = $this->config->get('productmanager_version');
		$this->modulePath = $this->config->get('productmanager_path');
		$this->moduleName = $this->config->get('productmanager_name');
		$this->moduleModel = $this->config->get('productmanager_model');
		$this->eventGroup = $this->config->get('productmanager_event_group');
        
		$this->load->language($this->modulePath);
		$this->load->model($this->modulePath);
	}

    public function install() {
        $this->load->model("setting/event");

        // Admin events
        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/common/column_left/before", $this->modulePath . "/injectAdminMenuItem");
    }

    public function uninstall() {
        $this->load->model("setting/event");
        $this->load->model("setting/setting");

        $this->model_setting_event->deleteEventByCode($this->eventGroup);
    	$this->model_setting_setting->deleteSetting($this->moduleName);
    	$this->model_setting_setting->deleteSetting('module_' . $this->moduleName);
    }

    // Events
    public function injectAdminMenuItem($eventRoute, &$data) {
        if ($this->user->hasPermission('access', $this->modulePath)) {
            $this->load->language($this->modulePath);

            foreach ($data["menus"] as &$menu) {
                if ($menu["id"] == "menu-catalog") {
                    array_splice($menu["children"], 2, 0, array(array(
                        'id'       => 'menu-productmanager',
                        'icon'	   => 'fa fa-shopping-cart fa-fw', 
                        'name'	   => $this->language->get('heading_title'),
                        'href'     => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], true),
                        'children' => array()
                    )));
                }
            }
        }
    }

	public function index() {
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->document->addStyle('view/stylesheet/'.$this->moduleName.'/'.$this->moduleName.'.css');
		$this->document->addScript('view/javascript/'.$this->moduleName.'/'.$this->moduleName.'.js');
		
		$this->load->model('catalog/product');
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
        
        $data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['add'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		$data['isMobileDevice'] = $this->isMobile();
		if ($data['isMobileDevice']) {
			$this->document->addStyle('view/stylesheet/' . $this->moduleName . '/jquery-mobile/jquery.mobile-1.4.5.min.css');
	 		$this->document->addScript('view/javascript/' . $this->moduleName . '/jquery-mobile/jquery.mobile-1.4.5.min.js');
		}
		
        $this->model_setting_setting->editSetting('module_' . $this->moduleName, array('module_' . $this->moduleName.'_status' => '1'));

		$data['heading_title'] = $this->language->get('heading_title') . ' ' . $this->moduleVersion;
		
		$data['text_list'] = $this->language->get('text_list');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_model'] = $this->language->get('entry_model');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_limit'] = $this->language->get('entry_limit');
		$data['entry_limit_placeholder'] = $this->language->get('entry_limit_placeholder');
		$data['entry_manufacturer'] = $this->language->get('entry_manufacturer');
		$data['entry_sku'] = $this->language->get('entry_sku');
		$data['entry_category'] = $this->language->get('entry_category');

		$data['button_copy'] = $this->language->get('button_copy');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_togglecolumns'] = $this->language->get('button_togglecolumns');
		$data['button_license'] = $this->language->get('button_license');
			
		$data['user_token'] = $this->session->data['user_token'];

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

		if (isset($this->session->data['validation_success'])) {
			$data['validation_success'] = $this->session->data['validation_success'];

			unset($this->session->data['validation_success']);
		} else {
			$data['validation_success'] = '';
		}
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$this->load->model('localisation/tax_class');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['tax_classes'] = array_merge(array(array('tax_class_id' => 0, 'title' => '--- None ---', 'sort_order' => 0)), $data['tax_classes']);

		$this->load->model('localisation/stock_status');
		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		$this->load->model('localisation/weight_class');
		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		$this->load->model('localisation/length_class');
		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();
		
		$this->load->model('catalog/manufacturer');
		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
		$data['manufacturers'] = array_merge(array(array('manufacturer_id' => 0, 'name' => '--- None ---', 'sort_order' => 0)), $data['manufacturers']);
		
		$data['excelport_link'] = $this->url->link('extension/module/excelport', 'user_token='.$this->session->data['user_token'], 'SSL');
		$excelportConfig = $this->model_setting_setting->getSetting('ExcelPort');
		$data['excelport_error'] = $this->language->get('excelport_error');
		$data['text_export'] = $this->language->get('text_export');
		$data['excelport'] = false;
        
		if (!empty($excelportConfig)) {
			$data['excelport'] = true;
		}
		
		$all_languages	= $this->model_localisation_language->getLanguages();

		$data['lang_images'] = array();
			
		foreach($all_languages as $lang) {
            $data['lang_images'][$lang['language_id']] = 'language/'.$lang['code'].'/'.$lang['code'].'.png';
		}

		$data['asterisk_warning'] 		= $this->language->get('asterisk_warning');
		$data['confirm_bulk'] 			= $this->language->get('confirm_bulk');
		$data['bulk_image_result'] 		= $this->language->get('bulk_image_result');
		$data['modal_close'] 			= $this->language->get('modal_close');
		$data['bulk_modal_heading'] 	= $this->language->get('bulk_modal_heading');
		$data['bulk_modal_text'] 		= $this->language->get('bulk_modal_text');
		$data['showhide_filter']		= $this->language->get('showhide_filter');
		$data['bulk_zip_error']			= $this->language->get('bulk_zip_error');
		$data['upload_file']			= $this->language->get('upload_file');
		$data['bulk_image_help_all']	= $this->language->get('bulk_image_help_all');
		$data['bulk_image_structured']  = $this->language->get('bulk_image_structured');
		$data['bulk_image_structured2'] = $this->language->get('bulk_image_structured2');
		$data['bulk_atleast2'] 			= $this->language->get('bulk_atleast2');

		$data['text_selected_categories'] = $this->language->get('text_selected_categories');
		$data['text_replace_selected']    = $this->language->get('text_replace_selected');
		$data['text_delete_selected']     = $this->language->get('text_delete_selected'); 

		$data['filter_name'] 			= null;
		$data['filter_model'] 			= null;
		$data['filter_category']		= null;
		$data['filter_price'] 			= null;
		$data['filter_quantity'] 		= null;
		$data['filter_status'] 			= null;
		$data['filter_limit'] 			= null;
		$data['filter_sku'] 			= null;
		$data['filter_manufacturer']	= null;

		$data['moduleData']				= $this->config->get($this->moduleName);

		$data['copy'] = $this->url->link($this->modulePath . '/copy', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['delete'] = $this->url->link($this->modulePath . '/delete', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['tableData'] 				= $this->getTableData('');
		
		$data['header'] 				= $this->load->controller('common/header');
		$data['column_left'] 			= $this->load->controller('common/column_left');
		$data['footer'] 				= $this->load->controller('common/footer');

        $data['encodedLicense']         = !empty($data['moduleData']['License']) ? base64_encode(json_encode($data['moduleData']['License'])) : '';
  
        $data['modulePath']             = $this->modulePath;
        $data['isMobileDeviceJson']     = json_encode($this->isMobile());
        $data['tax_classes_json']       = json_encode($data['tax_classes']);
        $data['weight_classes_json']    = json_encode($data['weight_classes']);
        $data['length_classes_json']    = json_encode($data['length_classes']);
        $data['stock_statuses_json']    = json_encode($data['stock_statuses']);
        $data['manufacturers_json']     = json_encode($data['manufacturers']);
        $data['lang_images_json']       = json_encode($data['lang_images']);

		$this->response->setOutput($this->load->view($this->modulePath . '/' . $this->moduleName, $data));
	}
	
	public function getTableData($url = '') {
		$tableData = array(
			'product_id' => array( 
				'name' => 'ID', 
				'sort' => 'p.product_id', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.product_id' . $url, 'SSL'),
				'bulk' => false),
			'sort_order' => array( 
				'name' => 'Sort order', 
				'sort' => 'p.sort_order', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, 'SSL'),
				'bulk' => true),
			'image' => array( 
				'name' => $this->language->get('column_image'), 
				'sort' => '', 
				'url' => '',
				'bulk' => false),
			'image_filename' => array( 
				'name' => $this->language->get('column_image_filename'), 
				'sort' => '', 
				'url' => '',
				'bulk' => false),
			'name' => array( 
				'name' => $this->language->get('column_name'), 
				'sort' => 'pd.name', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, 'SSL'),
				'bulk' => true),
			'category' => array(
				'name' => $this->language->get('column_category'), 
				'sort' => '', 
				'url' => '',
				'bulk' => true),
			'model' => array( 
				'name' => $this->language->get('column_model'), 
				'sort' => 'p.model', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, 'SSL'),
				'bulk' => true),
			'filter' => array(
				'name' => $this->language->get('column_filters'),
				'sort' => '',
				'url' => '',
				'bulk' => true),
			'manufacturer' => array(
				'name' => $this->language->get('entry_manufacturer'), 
				'sort' => 'm.name', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=m.name' . $url, 'SSL'),
				'bulk' => true),
			'sku' => array ( 
				'name' => $this->language->get('entry_sku'), 
				'sort' => 'p.sku', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sku' . $url, 'SSL'),
				'bulk' => true),
			'upc' => array ( 
				'name' => $this->language->get('entry_upc'), 
				'sort' => 'p.upc', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.upc' . $url, 'SSL'),
				'bulk' => true),
			'ean' => array ( 
				'name' => $this->language->get('entry_ean'), 
				'sort' => 'p.ean', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.ean' . $url, 'SSL'),
				'bulk' => true),
			'jan' => array ( 
				'name' => $this->language->get('entry_jan'),
				'sort' => 'p.jan', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.jan' . $url, 'SSL'),
				'bulk' => true),
			'isbn' => array ( 
				'name' => $this->language->get('entry_isbn'),
				'sort' => 'p.isbn', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.isbn' . $url, 'SSL'),
				'bulk' => true),
			'mpn' => array ( 
				'name' => $this->language->get('entry_mpn'),
				'sort' => 'p.mpn', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.mpn' . $url, 'SSL'),
				'bulk' => true),	
			'location' => array ( 
				'name' => $this->language->get('entry_location'), 
				'sort' => 'p.location', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.location' . $url, 'SSL'),
				'bulk' => true),
			'stock_status' => array ( 
				'name' => $this->language->get('entry_stock_status'), 
				'sort' => 's.name', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url, 'SSL'),
				'bulk' => true),
			'tax_class' => array ( 
				'name' => $this->language->get('entry_tax_class'), 
				'sort' => 't.title', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=t.title' . $url, 'SSL'),
				'bulk' => true),
			'keyword' => array (
				'name' => $this->language->get('entry_seo_keyword'),
				'sort' => 'ua.keyword',
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=ua.keyword' . $url, 'SSL'),
				'bulk' => false),
			'weight_class' => array ( 
				'name' => $this->language->get('entry_weight_class'), 
				'sort' => 'w.title', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=w.title' . $url, 'SSL'),
				'bulk' => true),
			'length' => array (  
				'name' => $this->language->get('column_length'), 
				'sort' => 'p.length', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.length' . $url, 'SSL'),
				'bulk' => true),
			'width' => array ( 
				'name' => $this->language->get('column_width'), 
				'sort' => 'p.width', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.width' . $url, 'SSL'),
				'bulk' => true),
			'height' => array ( 
				'name' => $this->language->get('column_height'), 
				'sort' => 'p.height', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.height' . $url, 'SSL'),
				'bulk' => true),
			'length_class' => array ( 
				'name' => $this->language->get('length_class'), 
				'sort' => 'l.title', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=l.title' . $url, 'SSL'),
				'bulk' => true),
			'viewed' => array ( 
				'name' => $this->language->get('entry_viewed'), 
				'sort' => 'p.viewed', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.viewed' . $url, 'SSL'),
				'bulk' => true),				
			'points' => array ( 
				'name' => $this->language->get('entry_reward'), 
				'sort' => 'p.points', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.points' . $url, 'SSL'),
				'bulk' => true),
			'shipping' => array ( 
				'name' => $this->language->get('entry_shipping'), 
				'sort' => 'p.shipping', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.shipping' . $url, 'SSL'),
				'bulk' => true),
			'price' => array ( 
				'name' => $this->language->get('column_price'), 
				'sort' => 'p.price', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, 'SSL'),
				'bulk' => true),
			'specials' => array ( 
				'name' => $this->language->get('column_specials'), 
				'sort' => '', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.specials' . $url, 'SSL'),
				'bulk' => true),
			'discounts' => array(
				'name' => $this->language->get('column_discounts'),
				'sort' => '',
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'),
				'bulk' => true),
			'attributes' => array ( 
				'name' => $this->language->get('column_attributes'), 
				'sort' => '', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.attributes' . $url, 'SSL'),
				'bulk' => true),
			'options' => array ( 
				'name' => $this->language->get('column_options'), 
				'sort' => '', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.options' . $url, 'SSL'),
				'bulk' => true),
			'quantity' => array ( 
				'name' => $this->language->get('column_quantity'), 
				'sort' => 'p.quantity', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, 'SSL'),
				'bulk' => true),
			'minimum' => array (
				'name' => $this->language->get('entry_minimum'), 
				'sort' => 'p.minimum', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.minimum' . $url, 'SSL'),
				'bulk' => true),
			'subtract' => array (
				'name' => $this->language->get('entry_subtract'), 
				'sort' => 'p.subtract', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.subtract' . $url, 'SSL'),
				'bulk' => true),		
			'status' => array ( 
				'name' => $this->language->get('column_status'), 
				'sort' => 'p.status', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, 'SSL'),
				'bulk' => true),
			'weight' => array ( 
				'name' => $this->language->get('column_weight'), 
				'sort' => 'p.weight', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.weight' . $url, 'SSL'),
				'bulk' => true),
			'date_available' => array (
				'name' => $this->language->get('entry_date_available'),
				'sort' => 'p.date_available', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.date_available' . $url, 'SSL'),
				'bulk' => true),
			'date_added' => array ( 
				'name' => $this->language->get('date_added'), 
				'sort' => 'p.date_added', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.date_added' . $url, 'SSL'),
				'bulk' => true),				
			'date_modified' => array ( 
				'name' => $this->language->get('date_modified'), 
				'sort' => 'p.date_modified', 
				'url' => $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.date_modified' . $url, 'SSL'),
				'bulk' => true)			
		);
		
		return $tableData;
	}

	public function getList() {
		$this->load->model('catalog/product');
		
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = null;
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = null;
		}

		$filter_price_from = null;
		$filter_price_to = null;
		$filter_price = null;

		if (isset($this->request->get['price_operation'])) {
			$price_operation = $this->request->get['price_operation'];

			if ($this->request->get['price_operation'] == 'between') {
				$filter_price_from = isset($this->request->get['filter_price_from']) ? $this->request->get['filter_price_from'] : null;
				$filter_price_to   = isset($this->request->get['filter_price_to'])   ? $this->request->get['filter_price_to']   : null;
			} else {
				$filter_price = isset($this->request->get['filter_price']) ? $this->request->get['filter_price'] : null;
			}
		} else {
			$price_operation = null;
		}

		$filter_quantity_from = null;
		$filter_quantity_to = null;
		$filter_quantity = null;

		if (isset($this->request->get['quantity_operation'])) {
			$quantity_operation = $this->request->get['quantity_operation'];

			if ($this->request->get['quantity_operation'] == 'between') {
				$filter_quantity_from = isset($this->request->get['filter_quantity_from']) ? $this->request->get['filter_quantity_from'] : null;
				$filter_quantity_to   = isset($this->request->get['filter_quantity_to'])   ? $this->request->get['filter_quantity_to']   : null;
			} else {
				$filter_quantity = isset($this->request->get['filter_quantity']) ? $this->request->get['filter_quantity'] : null;
			}
		} else {
			$quantity_operation = null;
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = null;
		}
		
		if (isset($this->request->get['filter_limit'])) {
			$filter_limit = $this->request->get['filter_limit'];
		} else {
			$filter_limit = 10;
		}
		
		if (isset($this->request->get['filter_manufacturer'])) {
			$filter_manufacturer = $this->request->get['filter_manufacturer'];
		} else {
			$filter_manufacturer = null;
		}
		
		if (isset($this->request->get['filter_sku'])) {
			$filter_sku = $this->request->get['filter_sku'];
		} else {
			$filter_sku = null;
		}
		
		if (isset($this->request->get['filter_category'])) {
			$filter_category = $this->request->get['filter_category'];
		} else {
			$filter_category = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
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

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['price_operation'])) {
			$url .= '&price_operation=' . $this->request->get['price_operation'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}

		if (isset($this->request->get['quantity_operation'])) {
			$url .= '&quantity_operation=' . $this->request->get['quantity_operation'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_limit'])) {
			$url .= '&filter_limit=' . $this->request->get['filter_limit'];
		}
		
		if (isset($this->request->get['filter_manufacturer'])) {
			$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
		}
		
		if (isset($this->request->get['filter_sku'])) {
			$url .= '&filter_sku=' . $this->request->get['filter_sku'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
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

		$data['products'] = array();

		$filter_data = array(
			'filter_name'	  		=> $filter_name,
			'filter_model'	  		=> $filter_model,
			'price_operation'		=> $price_operation,
			'filter_price_from'		=> $filter_price_from,
			'filter_price_to'		=> $filter_price_to,
			'filter_price'	  		=> $filter_price,
			'quantity_operation'	=> $quantity_operation,
			'filter_quantity_from'	=> $filter_quantity_from,
			'filter_quantity_to'	=> $filter_quantity_to,
			'filter_quantity' 		=> $filter_quantity,
			'filter_status'   		=> $filter_status,
			'filter_manufacturer'	=> $filter_manufacturer,
			'filter_sku'			=> $filter_sku,
			'filter_category'		=> $filter_category,
			'sort'            		=> $sort,
			'order'           		=> $order,
			'start'           		=> ($page - 1) * $filter_limit,
			'limit'           		=>  $filter_limit
		);

		$this->load->model('tool/image');
		$this->load->model('catalog/category');

		$product_total = $this->{$this->moduleModel}->getTotalProducts($filter_data);

		$results = $this->{$this->moduleModel}->getProducts($filter_data);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}

			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $product_special['price'];

					break;
				}
			}

			$discount = false;

			$product_discounts = $this->model_catalog_product->getProductDiscounts($result['product_id']);

			foreach ($product_discounts as $product_discount) {
				if (($product_discount['date_start'] == '0000-00-00' || strtotime($product_discount['date_start']) < time()) && ($product_discount['date_end'] == '0000-00-00' || strtotime($product_discount['date_end']) > time()) && $product_discount['quantity'] > 0) {
					$discount = $product_discount['price'];
				}
			}
			
			$categories = $this->model_catalog_product->getProductCategories($result['product_id']);

			$product_categories = array();
	
			foreach ($categories as $category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);
	
				if ($category_info) {
					$product_categories[] = array(
						'category_id' => $category_info['category_id'],
						'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
					);
				}
			}

			// Filters
			$this->load->model('catalog/filter');

			$filters = $this->model_catalog_product->getProductFilters($result['product_id']);

			$product_filters = array();

			foreach ($filters as $filter_id) {
				$filter_info = $this->model_catalog_filter->getFilter($filter_id);

				if($filter_info){
					$product_filters[] = array(
						'product_id' => $filter_info['filter_id'],
						'name' => ($filter_info['group']) ? $filter_info['group']  . ' &gt; ' . $filter_info['name'] : $filter_info['name']
					);
				}
			}

			// Attributes
			$this->load->model('catalog/attribute');
			
			$product_attributes = $this->model_catalog_product->getProductAttributes($result['product_id']);

			$product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

			$data['products'][] = array(
				'product_id' 		=> $result['product_id'],
				'image'      		=> $image,
				'name'       		=> $result['name'],
				'sort_order'        => $result['sort_order'],
				'model'      		=> $result['model'],
				'price'      		=> $result['price'],
				'special'    		=> $special,
				'discount'			=> $discount,
				'discounts_count'	=> count($product_discounts),
				'specials_count'    => count($product_specials),
				'attributes_count'	=> count($product_attributes),
				'options_count'		=> count($product_options),
				'quantity'   		=> $result['quantity'],
				'status'     		=> ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'       		=> $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, 'SSL'),
                'orders'       		=> $this->url->link($this->modulePath . '/showOrdersPopup', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, 'SSL'),
                'quick_view'        => (HTTP_CATALOG . "index.php?route=product/product&product_id=" . $result['product_id']),
				'sku'		 		=> $result['sku'],
				'upc'		 		=> $result['upc'],
				'ean'		 		=> $result['ean'],
				'jan'		 		=> $result['jan'],
				'isbn' 	     		=> $result['isbn'],
				'mpn'       		=> $result['mpn'],
				'location'	 		=> $result['location'],
				'tax_class'  		=> $result['tax_class'],
				'stock_status' 		=> $result['stock_status'],
				'height'   			=> $result['height'],
				'length'   			=> $result['length'],
				'width'   			=> $result['width'],
				'length_class'   	=> $result['length_class'],
				'weight_class' 		=> $result['weight_class'],
				'manufacturer' 		=> $result['manufacturer'],
				'minimum'	 		=> $result['minimum'],
				'keyword'			=> !empty($result['keyword']) ? $result['keyword'] : '',
				'shipping'			=> ($result['shipping']== 1) ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'points'	 		=> $result['points'],
				'viewed'	 		=> $result['viewed'],				
				'image_filename' 	=> $result['image'],
				'weight'	 		=> $result['weight'],
				'subtract'  		=> ($result['subtract']== 1) ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     		=> ($result['status']== 1) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'shipping_code' 	=> $result['shipping'],
				'subtract_code' 	=> $result['subtract'],
				'status_code' 		=> $result['status'],
				'tax_class_code'	=> $result['tax_class_id'],
				'stock_status_code'	=> $result['stock_status_id'],
				'weight_class_code'	=> $result['weight_class_id'],
				'manufacturer_code'	=> $result['manufacturer_id'],
				'date_added' 		=> $result['date_added'],
				'date_available' 	=> $result['date_available'],
				'date_modified' 	=> $result['date_modified'],
				'category'			=> $product_categories,
				'filter'            => $product_filters
			);
		}

		$data['column_image'] = $this->language->get('column_image');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_action'] = $this->language->get('column_action');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['text_count'] = $this->language->get('text_count');
		$data['text_notset'] = $this->language->get('text_notset');
		$data['text_notset'] = $this->language->get('text_notset');
		$data['text_special_cnt'] = $this->language->get('text_special_cnt');
		$data['text_special_cnt_plu'] = $this->language->get('text_special_cnt_plu');
		$data['text_attr_cnt'] = $this->language->get('text_attr_cnt');
		$data['text_attr_cnt_plu'] = $this->language->get('text_attr_cnt_plu');
		$data['text_opts_cnt'] = $this->language->get('text_opts_cnt');
		$data['text_opts_cnt_plu'] = $this->language->get('text_opts_cnt_plu');
		$data['text_disc_cnt'] = $this->language->get('text_disc_cnt');
		$data['text_disc_cnt_plu'] = $this->language->get('text_disc_cnt_plu');
		
		$data['bulk_edit'] = $this->language->get('bulk_edit');

		$data['button_edit'] = $this->language->get('button_edit');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['button_orders'] = $this->language->get('button_orders');
		$data['quick_edit']	= $this->language->get('quick_edit');
		$data['quick_view'] = $this->language->get('quick_view');

		$data['delete'] = $this->url->link($this->modulePath . '/delete', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/tax_class');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['tax_class_default'] = 0;
		
		$this->load->model('localisation/stock_status');
		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
		$data['stock_status_default'] = 0;

		$this->load->model('localisation/length_class');
		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();
		$data['length_class_default'] = $this->config->get('config_length_class_id');

		$this->load->model('localisation/weight_class');
		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
		$data['weight_class_default'] = $this->config->get('config_weight_class_id');
		
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 40, 40);
		
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

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['price_operation'])) {
			$url .= '&price_operation=' . $this->request->get['price_operation'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}

		if (isset($this->request->get['quantity_operation'])) {
			$url .= '&quantity_operation=' . $this->request->get['quantity_operation'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_sku'])) {
			$url .= '&filter_sku=' . $this->request->get['filter_sku'];
		}
		
		if (isset($this->request->get['filter_manufacturer'])) {
			$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
		}
		
		if (isset($this->request->get['filter_limit'])) {
			$url .= '&filter_limit=' . $this->request->get['filter_limit'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
		}
		
		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_order'] = $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, 'SSL');
		
		$data['tableData'] = $this->getTableData($url);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_manufacturer'])) {
			$url .= '&filter_manufacturer=' . urlencode(html_entity_decode($this->request->get['filter_manufacturer'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_sku'])) {
			$url .= '&filter_sku=' . $this->request->get['filter_sku'];
		}
		
		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
		}

		if (isset($this->request->get['price_operation'])) {
			$url .= '&price_operation=' . $this->request->get['price_operation'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}

		if (isset($this->request->get['quantity_operation'])) {
			$url .= '&quantity_operation=' . $this->request->get['quantity_operation'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
		
		if (isset($this->request->get['filter_limit'])) {
			$url .= '&filter_limit=' . $this->request->get['filter_limit'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $filter_limit;
		$pagination->url = $this->url->link($this->modulePath . '/getList', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', 'SSL');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $filter_limit) + 1 : 0, ((($page - 1) * $filter_limit) > ($product_total - $filter_limit)) ? $product_total : ((($page - 1) * $filter_limit) + $filter_limit), $product_total, ceil($product_total / $filter_limit));

		$data['filter_name'] = $filter_name;
		$data['filter_model'] = $filter_model;
		$data['filter_price'] = $filter_price;
		$data['filter_quantity'] = $filter_quantity;
		$data['filter_status'] = $filter_status;
		$data['filter_manufacturer'] = $filter_manufacturer;
		$data['filter_limit'] = $filter_limit;
		$data['filter_sku'] = $filter_sku;
		$data['filter_category'] = $filter_category;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_list', $data));	
	}

	public function delete() {
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->deleteProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

		}

		$this->response->redirect($this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'));
	}
	
	public function copy() {
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->copyProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->response->redirect($this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'));
	}
	
	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateCopy() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	public function getproductname() {
		$json = array();
        
		if (isset($this->request->post)) {
			$json = $this->{$this->moduleModel}->getProductName($this->request->post);
		}
        
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
    
    public function getproducturlalias() {
		$json = array();
        
		if (isset($this->request->post)) {
			$json = $this->{$this->moduleModel}->getProductUrlAlias($this->request->post);
		}
        
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function getproductcategories() {
		
		$json = array();
		if (isset($this->request->post)) {
			$json = $this->{$this->moduleModel}->getProductCategories($this->request->post);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function getproductfilters(){
		$json = array();

		if(isset($this->request->post)){
			$json = $this->{$this->moduleModel}->getProductFilters($this->request->post);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function updateproduct() {
		$json = array();

		if (isset($this->request->post)) {
			$field 				= $this->request->post['pdata'];
			$value 				= isset($this->request->post['pvalue']) ? $this->request->post['pvalue'] : '';
			$product_id   		= $this->request->post['pid'];
			$language_id 		= $this->config->get('config_language_id');
			if(!empty($this->request->post['plang'])) {
				$plang = $this->request->post['plang'];
			}

			if ($field == 'name') {
				$this->db->query("UPDATE " . DB_PREFIX . "product_description SET name = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$plang . "'");
            } else if ($field == 'keyword') {
				$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET keyword = '" . $this->db->escape($value) . "' WHERE query = 'product_id=" . (int)$product_id . "' AND language_id = '" . (int)$plang . "'");
			} else if ($field == 'weight_class') {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET weight_class_id = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "'");		
			} else if ($field == 'length_class') {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET length_class_id = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "'");		
			} else if ($field == 'tax_class') {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET tax_class_id = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "'");
			} else if ($field == 'manufacturer') {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET manufacturer_id = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "'");
			} else if ($field == 'stock_status') {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET stock_status_id = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$product_id . "'");			
			} else if ($field == 'category') {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
				
				if (isset($value) && is_array($value)) {
					foreach ($value as $category_id) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
					}
				}

			} else if ($field == 'filter') {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

				if(isset($value) && is_array($value)){
					foreach ($value as $filter_id) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
					}
				}

			} else if ($field === 'image_filename') {
				$old_image_query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
				
				if($old_image_query->num_rows > 0) {
					$old_image = $old_image_query->row['image'];
				} else {
					$old_image = "";
				}

				$path_to_image = dirname(DIR_APPLICATION).DIRECTORY_SEPARATOR.'image'.DIRECTORY_SEPARATOR;

				if(!empty($old_image)) {
					if(is_writable($path_to_image.$old_image)) {
						if(rename($path_to_image.$old_image, $path_to_image.$value)) {
							$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($value) . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
						}
					} else {
						$json['error'] = "Error: You do not have sufficient rights to perform this operation!";
					}
				}
			} else if ($field == 'specials') {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
				if (isset($this->request->post['product_special'])) {
					foreach ($this->request->post['product_special'] as $product_special) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
					}
				}
			} else if ($field == 'attributes') { 
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
				if (!empty($this->request->post['product_attribute'])) {
					foreach ($this->request->post['product_attribute'] as $product_attribute) {
						if ($product_attribute['attribute_id']) {
							foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
							}
						}
					}
				}
			} else if ($field == 'discounts') {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

				if (isset($this->request->post['product_discount'])) {
					foreach ($this->request->post['product_discount'] as $product_discount) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
					}
				}
			} else if ($field == 'options') { 
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

				if (isset($this->request->post['product_option'])) {
					foreach ($this->request->post['product_option'] as $product_option) {
						if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
							if (isset($product_option['product_option_value'])) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

								$product_option_id = $this->db->getLastId();

								foreach ($product_option['product_option_value'] as $product_option_value) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
								}
							}
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
						}
					}
				}
			} else {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $field . " = '" . $this->db->escape($value) . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
			}
			
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$this->load->model('catalog/category');
			$this->load->model('catalog/filter');

			$json['product'] = $this->model_catalog_product->getProduct($product_id);
            
            $seo_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE `query` = 'product_id=" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "' LIMIT 1");
            if ($seo_query->num_rows) {
                $json['product']['keyword'] = $seo_query->row['keyword'];
            } else {
                $json['product']['keyword'] = '';
            }
            
			if (is_file(DIR_IMAGE . $json['product']['image'])) {
				$json['product_image'] = $this->model_tool_image->resize($json['product']['image'], 40, 40);
			} else {
				$json['product_image'] = $this->model_tool_image->resize('no_image.png', 40, 40);
			}
			
			if($field == 'category'){
				$categories = $this->model_catalog_product->getProductCategories($product_id);
				$product_categories = array();
				foreach ($categories as $category_id) {
					$category_info = $this->model_catalog_category->getCategory($category_id);
		
					if ($category_info) {
						$product_categories[] = array(
							'category_id' => $category_info['category_id'],
							'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
						);
					}
				}


				$json['categories'] = $product_categories;
			}

			if($field == 'filter'){
				$filters = $this->model_catalog_product->getProductFilters($product_id);
				$product_filters = array();
				foreach ($filters as $filter_id) {
					$filter_info = $this->model_catalog_filter->getFilter($filter_id);

					if($filter_info){
						$product_filters[] = array(
							'product_id' => $filter_info['filter_id'],
							'name' => ($filter_info['group']) ? $filter_info['group']  . ' &gt; ' . $filter_info['name'] : $filter_info['name']
						);
					}
				}

				$json['filters'] = $product_filters;
			}

			$json['field'] = $field;
			$json['value'] = $value;
			$json['product_id'] = $product_id;
			$json['success'] = 'success';
		}
		
		if($json['field'] == "specials" || $json['field'] == "attributes" || $json['field'] == "options") {
			exit;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function updateproductbulk() {
		$json = array();
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('catalog/filter');

		if (isset($this->request->post)) {
			$field 				= $this->request->post['pdata'];
			$value 				= isset($this->request->post['pvalue']) ? $this->request->post['pvalue'] : '';
			$ptype     			= isset($this->request->post['ptype'])  ? $this->request->post['ptype']  : '';
			$product_id   		= $this->request->post['pid'];
			$language_id 		= $this->config->get('config_language_id');
			if(!empty($this->request->post['plang']) ) {
				$plang = $this->request->post['plang'];
			}

			$product_id = explode(",", $product_id);
			$json['products']	= array();
			$i = 0;
			foreach ($product_id as $pid) {
				$pid_info = $this->model_catalog_product->getProduct($pid);
				if ($field!='weight_class' && $field!='tax_class' && $field!='length_class' && $field!='manufacturer' && $field!='stock_status' && $field!='category' && $field!='specials' && $field!='specials_bulk' && $field!='attributes' && $field!='attributes_bulk' && $field!='discounts_bulk' && $field!='options' && $field!='options_bulk' && $field != 'filter') {
					$new_value = str_replace('%value%', $pid_info[$field], $value);
				} else {
					$new_value = $value;	
				}
				if ($field == 'name') {
					$this->db->query("UPDATE " . DB_PREFIX . "product_description SET name = '" . $this->db->escape($new_value) . "' WHERE product_id = '" . (int)$pid . "' AND language_id = '" . (int)$plang . "'");
				} else if ($field == 'weight_class') {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET weight_class_id = '" . $this->db->escape($new_value) . "' WHERE product_id = '" . (int)$pid . "'");		
				} else if ($field == 'length_class') {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET length_class_id = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int)$pid . "'");		
				} else if ($field == 'tax_class') {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET tax_class_id = '" . $this->db->escape($new_value) . "' WHERE product_id = '" . (int)$pid . "'");
				} else if ($field == 'manufacturer') {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET manufacturer_id = '" . $this->db->escape($new_value) . "' WHERE product_id = '" . (int)$pid . "'");
				} else if ($field == 'stock_status') {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET stock_status_id = '" . $this->db->escape($new_value) . "' WHERE product_id = '" . (int)$pid . "'");			
				} else if  ($field == 'quantity' || $field == 'minimum' || $field == 'points' || $field == 'viewed') {
					eval( '$result = (' . $new_value . ');' );
					$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $field . " = '" . (int)$result . "', date_modified = NOW() WHERE product_id = '" . (int)$pid . "'");
				} else if ($field == 'sort_order') {
					eval('$result = (' . $new_value . ');');
					$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $field . " = '" . $this->db->escape($result) . "', date_modified = NOW() WHERE product_id = '" . (int)$pid . "'");
				} else if ($field == 'price') {
					eval( '$result = (' . $new_value . ');' );
					$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $field . " = '" . $this->db->escape($result) . "', date_modified = NOW() WHERE product_id = '" . (int)$pid . "'");
				} else if ($field == 'category') {
					if (strpos($ptype, 'replace') !== false) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$pid . "'");
						if (isset($value) && is_array($value)) {
							foreach ($value as $category_id) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$pid . "', category_id = '" . (int)$category_id . "'");
							}
						}
					} else if (strpos($ptype, 'add') !== false) {
						if (isset($value)) {
							foreach ($value as $category_id) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$pid . "', category_id = '" . (int)$category_id . "'");
							}
						}
					} else if (strpos($ptype, 'delete') !== false) {
						if (isset($value)) {
							foreach ($value as $category_id) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$pid . "' AND category_id = '" . (int)$category_id  . "'");
							}
						}
					}
				} else if($field == 'filter') {
					if(strpos($ptype, 'replace') !== false) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$pid . "'");
						if(isset($value) && is_array($value)){
							foreach ($value as $filter_id) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$pid . "', filter_id = '" . (int)$filter_id . "'");
							}
						}
					} else if(strpos($ptype, 'add') !== false) {
						if(isset($value)) {
							foreach ($value as $filter_id) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$pid . "', filter_id = '" . (int)$filter_id . "'");
							}
						}
					} else if (strpos($ptype, 'delete') !== false) {
						if(isset($value)) {
							foreach ($value as $filter_id) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$pid . "' AND filter_id = '" . (int)$filter_id . "'");
							}
						}
					}

				} else if ($field == 'weight') {
					eval( '$result = (' . $new_value . ');' );
					$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $field . " = '" . (float)$result . "', date_modified = NOW() WHERE product_id = '" . (int)$pid . "'");
				} else if ($field == 'specials') {
					if(isset($this->request->post['product_ids'])) {
						$product_ids = explode(",", $this->request->post['product_ids']);
						foreach($product_ids as $product_id) {
							$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
							if (isset($this->request->post['product_special'])) {
								foreach ($this->request->post['product_special'] as $product_special) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
								}
							}
						}
					}
				} else if ($field == 'specials_bulk') {
					if(isset($this->request->post['product_ids'])) {
						$product_ids = explode(",", $this->request->post['product_ids']);

						foreach($product_ids as $product_id) {
							if (strpos('replace', $ptype) !== false) {
								$this->db->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = '" . (int)$product_id . "'");
							} elseif(strpos('add_on_top', $ptype) !== false) {
								$query = $this->db->query("SELECT `price`, `priority` FROM `" . DB_PREFIX . "product_special` WHERE (`date_start` <= NOW() OR `date_start` = '0000-00-00') AND (`date_end` >= NOW() OR `date_end` = '0000-00-00') AND `product_id`='" . (int)$product_id . "' ORDER BY `priority` ASC");

								if ($query->num_rows) {
									$base_price = $query->row['price'];
									$base_priority = $query->row['priority'] - 1;
								}
							}

							$product_specials = !empty($this->request->post['specials']) ? $this->request->post['specials'] : '';

							if (!empty($product_specials[0]['price'])) {

								foreach ($product_specials as $special) {
									$product_data = $this->model_catalog_product->getProduct($product_id);

									$special_data = array(
										'customer_group_id' => $special['customer_group'],
										'priority'			=> isset($base_priority) ? $base_priority : 0,
										'price'				=> isset($base_price) ? $base_price : $product_data['price'],
										'date_start'		=> '',
										'date_end'			=> ''
									);

									//Priority
									if (!empty($special['priority'])) {
										$special_data['priority'] = $special['priority'];
									}

									//Price
									if (!empty($special['price'])) {
										if ($special['price_operation_type'] == 'fixed') {
											$price_value = $special['price'];
										} elseif ($special['price_operation_type'] == 'percent') {
											$price_value = $special['price'] * (isset($base_price) ? $base_price : $product_data['price']) / 100;
										}

										switch($special['price_operation']) {
											case 'add' 		: $special_data['price'] += $price_value; break;
											case 'subtract' : $special_data['price'] -= $price_value; break;
											case 'multiply' : $special_data['price'] *= $price_value; break;
											case 'divide'   : $special_data['price'] /= $price_value; break;
										}
									}

									//Date Start
									if (!empty($special['date_start'])) {
										$date_start = new DateTime($special['date_start']);
										$special_data['date_start'] = $date_start->format('Y-m-d');
									}

									//Date End
									if (!empty($special['date_end'])) {
										$date_end = new DateTime($special['date_end']);
										$special_data['date_end'] = $date_end->format('Y-m-d');
									}

									//Query
									if (!empty($special_data)) {
										if ($special['customer_group'] == 0) { 
                                            $this->load->model('customer/customer_group');
                                            $customer_groups = $this->model_customer_customer_group->getCustomerGroups();

											foreach ($customer_groups as $customer_group) {
												$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group['customer_group_id'] . "', priority = '" . (int)$special_data['priority'] . "', price = '" . (float)$special_data['price'] . "', date_start = '" . $this->db->escape($special_data['date_start']) . "', date_end = '" . $this->db->escape($special_data['date_end']) . "'");
											}
										} else { 
											$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$special_data['customer_group_id'] . "', priority = '" . (int)$special_data['priority'] . "', price = '" . (float)$special_data['price'] . "', date_start = '" . $this->db->escape($special_data['date_start']) . "', date_end = '" . $this->db->escape($special_data['date_end']) . "'");
										}
									}	
								}
							}
						}
					}
				} else if ($field == 'discounts_bulk') {
					if(isset($this->request->post['product_ids'])) {
						$product_ids = explode(",", $this->request->post['product_ids']);

						foreach($product_ids as $product_id) {
							if (strpos('replace', $ptype) !== false) {
								$this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = '" . (int)$product_id . "'");
							} elseif(strpos('add_on_top', $ptype) !== false) {
								$query = $this->db->query("SELECT `price`, `priority` FROM `" . DB_PREFIX . "product_discount` WHERE (`date_start` <= NOW() OR `date_start` = '0000-00-00') AND (`date_end` >= NOW() OR `date_end` = '0000-00-00') AND `product_id`='" . (int)$product_id . "' ORDER BY `priority` ASC");

								if ($query->num_rows) {
									$base_price = $query->row['price'];
									$base_priority = $query->row['priority'] - 1;
								}
							}

							$product_discounts = !empty($this->request->post['discounts']) ? $this->request->post['discounts'] : '';

							if (!empty($product_discounts[0]['price'])) {

								foreach ($product_discounts as $discount) {
									$product_data = $this->model_catalog_product->getProduct($product_id);

									$discount_data = array(
										'customer_group_id' => $discount['customer_group_id'],
										'quantity'			=> $discount['quantity'],
										'priority'			=> isset($base_priority) ? $base_priority : 0,
										'price'				=> isset($base_price) ? $base_price : $product_data['price'],
										'date_start'		=> '',
										'date_end'			=> ''
									);

									//Priority
									if (!empty($discount['priority'])) {
										$discount_data['priority'] = $discount['priority'];
									}

									//Price
									if (!empty($discount['price'])) {
										if ($discount['price_operation_type'] == 'fixed') {
											$price_value = $discount['price'];
										} elseif ($discount['price_operation_type'] == 'percent') {
											$price_value = $discount['price'] * (isset($base_price) ? $base_price : $product_data['price']) / 100;
										}

										switch($discount['price_operation']) {
											case 'add' 		: $discount_data['price'] += $price_value; break;
											case 'subtract' : $discount_data['price'] -= $price_value; break;
											case 'multiply' : $discount_data['price'] *= $price_value; break;
											case 'divide'   : $discount_data['price'] /= $price_value; break;
										}
									}

									//Date Start
									if (!empty($discount['date_start'])) {
										$date_start = new DateTime($discount['date_start']);
										$discount_data['date_start'] = $date_start->format('Y-m-d');
									}

									//Date End
									if (!empty($discount['date_end'])) {
										$date_end = new DateTime($discount['date_end']);
										$discount_data['date_end'] = $date_end->format('Y-m-d');
									}

									//Query
									if (!empty($discount_data)) {
										if ($discount['customer_group_id'] == 0) { 
                                            $this->load->model('customer/customer_group');
                                            $customer_groups = $this->model_customer_customer_group->getCustomerGroups();

											foreach ($customer_groups as $customer_group) {
												$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group['customer_group_id'] . "', quantity = '" . (int)$discount_data['quantity'] . "', priority = '" . (int)$discount_data['priority'] . "', price = '" . (float)$discount_data['price'] . "', date_start = '" . $this->db->escape($discount_data['date_start']) . "', date_end = '" . $this->db->escape($discount_data['date_end']) . "'");
											}
										} else { 
											$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$discount_data['customer_group_id'] . "', quantity = '" . (int)$discount_data['quantity'] . "', priority = '" . (int)$discount_data['priority'] . "', price = '" . (float)$discount_data['price'] . "', date_start = '" . $this->db->escape($discount_data['date_start']) . "', date_end = '" . $this->db->escape($discount_data['date_end']) . "'");
										}
									}	
								}
							}
						}
					}
				} else if ($field == 'attributes_bulk') {
					if(isset($this->request->post['product_ids'])) {
						$product_ids = explode(",", $this->request->post['product_ids']);
						foreach($product_ids as $product_id) {
							if (strpos('replace', $ptype) !== false) { 
								$this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '" . (int)$product_id . "'");
							}
							if (!empty($this->request->post['product_attribute'])) {
								foreach ($this->request->post['product_attribute'] as $product_attribute) {
									if ($product_attribute['attribute_id']) {
										$this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id`='" . (int)$product_id . "' AND `attribute_id`='" . (int)$product_attribute['attribute_id'] . "'");
										foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
											$this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = '" . (int)$product_id . "', `attribute_id` = '" . (int)$product_attribute['attribute_id'] . "', `language_id` = '" . (int)$language_id . "', `text` = '" .  $this->db->escape($product_attribute_description['text']) . "'");
										}
									}
								}
							}
						}
					}
				} else if ($field == 'options_bulk') {
					if(isset($this->request->post['product_ids'])) {
						$product_ids = explode(",", $this->request->post['product_ids']);
						foreach($product_ids as $product_id) {
							$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
							$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

							if (isset($this->request->post['product_option'])) {
								foreach ($this->request->post['product_option'] as $product_option) {
									if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
										if (isset($product_option['product_option_value'])) {
											$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

											$product_option_id = $this->db->getLastId();

											foreach ($product_option['product_option_value'] as $product_option_value) {
												$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
											}
										}
									} else {
										$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
									}
								}
							}
						}
					}
					
				} else {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $field . " = '" . $this->db->escape($new_value) . "', date_modified = NOW() WHERE product_id = '" . (int)$pid . "'");
				}
				
				$json['products'][$i] = $this->model_catalog_product->getProduct($pid);

				if ($field == 'category') {
					$categories = $this->model_catalog_product->getProductCategories($pid);
					$product_categories = array();
					foreach ($categories as $category_id) {
						$category_info = $this->model_catalog_category->getCategory($category_id);
			
						if ($category_info) {
							$product_categories[] = array(
								'category_id' => $category_info['category_id'],
								'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
							);
						}
					}
					$json['products'][$i]['categories'] = $product_categories;
				}

				if ($field == 'filter') {
					$filters = $this->model_catalog_product->getProductFilters($pid);
					$product_filters = array();
					foreach ($filters as $filter_id) {
						$filter_info = $this->model_catalog_filter->getFilter($filter_id);

						if($filter_info) {
							$product_filters[] = array (
								'filter_id' => $filter_info['filter_id'],
								'name' => ($filter_info['group']) ? $filter_info['group'] . ' &gt; ' . $filter_info['name'] : $filter_info['name']
							);
						}
					}
					$json['products'][$i]['filters'] = $product_filters;
				}

				$i++;
			}

			$json['field'] = $field;
			$json['value'] = $value;
			$json['product_id'] = $product_id;
			$json['success'] = 'success';
		}

		if($json['field'] == "specials" || $json['field'] == "specials_bulk" || $json['field'] == "attributes_bulk" || $json['field'] == "options_bulk"  || $json['field'] == "attributes" || $json['field'] == "options") {
			exit;
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function bulkupload() {
		$json = array();

		if (isset($this->request->post['method'])) {
			$method = $this->request->post['method'];
		} else {
			$error = true;
			$json['error'][] = 'There was an error obtaining the data from the form. Please, contact support for help on that.';
		}

		if(isset($_FILES) ) {
			$error = false;
			$files = array();
		
			$uploaddir = DIR_IMAGE.'catalog/';
			foreach($_FILES as $file) {
				if(move_uploaded_file($file['tmp_name'], $uploaddir .basename($file['name']))) {
					$files[] = $uploaddir .$file['name'];
				} else {
					$error = true;
				}
			}
			
			if ($error) {
				$json['error'][] = 'There was an error uploading your files.';
			}
		} else {
			$json['error'][] = 'There was an unexpected error. Please try again.';
		}

		$current_folder = time();
		$unzipping = false;
		$zip = new ZipArchive;
		$res = $zip->open($files[0]);
		$update_map = array();
		if ($res === true) {
			for($x=0;$x < $zip->numFiles; $x++) {
				$filename = $zip->getNameIndex($x);

				if (preg_match('/\.(jpe?g|gif|png)$/', basename($filename))) {
					$filePath = $uploaddir.'productmanager'.DIRECTORY_SEPARATOR.$current_folder . DIRECTORY_SEPARATOR . ltrim($filename, '/');

					if (!file_exists(dirname($filePath))) {
						mkdir(dirname($filePath), 0755, true);
					}
					
					file_put_contents($filePath, $zip->getFromIndex($x));
					if ($method == "add_as_mains") {
						preg_match('/^(\d+)\.(jpg|png)$/', basename($filename), $matches);
						$product_id = $matches[1];
						$update_map[$product_id] = $filePath;
					} else {
						$update_map[] = $filePath;
					}
				}
			}
			$zip->close();
			$unzipping = true;
		} else {
			$json['error'][] = 'We were not able to unzip the file.';
		}
		
		if (file_exists($files[0])) {
			unlink($files[0]);
		}

		if ($unzipping) {
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$totalChanges = 0;

			switch ($method) { 
				case "add_as_mains" :
									foreach ($update_map as $product_id => $file) {
										$image_url = str_replace(DIR_IMAGE,'',$file);
										
										$product_info = $this->model_catalog_product->getProduct($product_id);
										if (!empty($product_info)) {
											$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($image_url) . "' WHERE product_id = '" . (int)$product_id . "'");
											$new_data = $this->model_catalog_product->getProduct($product_id);
											if (is_file(DIR_IMAGE . $new_data['image'])) {
												$product_image = $this->model_tool_image->resize($new_data['image'], 40, 40);
											} else {
												$product_image = $this->model_tool_image->resize('no_image.png', 40, 40);
											}
											$json['products'][] = array(
												'id' => $new_data['product_id'],
												'image' => $new_data['image'],
												'product_image' => $product_image
											);
											$totalChanges++;		
										}
									}

									if ($totalChanges == 0) {
										$error = true;
										$json['error'][] = 'There were no matching product IDs with the names of the images in the zip.';
									}
				break;
				case "add_to_each" : 
									//code for additional here
									$selected = isset($this->request->post['selected']) ? explode(',', $this->request->post['selected']) : '';
									
									foreach ($selected as $product_id) {
										foreach ($update_map as $file) {
											$image_url = str_replace(DIR_IMAGE, '', $file);

											$product_info = $this->model_catalog_product->getProduct($product_id);

											if ($product_info) {
												$this->db->query("INSERT INTO `" . DB_PREFIX . "product_image` SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($image_url) . "', sort_order='0'");

												$totalChanges++;
											}
										}
									}
				break;
			}

			$json['total'] = $totalChanges;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}

	public function showSpecialsForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');

		$data['column_specials'] = $this->language->get('column_specials');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_special_add'] = $this->language->get('button_special_add');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['product_special'])) {
			$product_specials = $this->request->post['product_special'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_specials = $this->model_catalog_product->getProductSpecials($this->request->get['product_id']);
		} else {
			$product_specials = array();
		}

		$data['product_specials'] = array();

		foreach ($product_specials as $product_special) {
			$data['product_specials'][] = array(
				'customer_group_id' => $product_special['customer_group_id'],
				'priority'          => $product_special['priority'],
				'price'             => $product_special['price'],
				'date_start'        => ($product_special['date_start'] != '0000-00-00') ? $product_special['date_start'] : '',
				'date_end'          => ($product_special['date_end'] != '0000-00-00') ? $product_special['date_end'] :  ''
			);
		}

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		
		$data['action'] = $this->url->link($this->modulePath . '/updateproduct','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_specials', $data));
	}

	public function showSpecialsBulkForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');
		
		$data['column_specials_bulk'] = $this->language->get('column_specials_bulk');
		$data['column_specials'] = $this->language->get('column_specials');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_actions'] = $this->language->get('entry_actions');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_special_add'] = $this->language->get('button_special_add');
		$data['operation_multiply'] = $this->language->get('operation_multiply');
		$data['operation_divide'] = $this->language->get('operation_divide');
		$data['operation_add'] = $this->language->get('operation_add');
		$data['operation_subtract'] = $this->language->get('operation_subtract');
		$data['all_customer_groups'] = $this->language->get('all_customer_groups');
		$data['special_bulk_type'] = $this->language->get('special_bulk_type');
		$data['special_bulk_replace'] = $this->language->get('special_bulk_replace');
		$data['special_bulk_add_on_top'] = $this->language->get('special_bulk_add_on_top');
		$data['special_replace_helper'] = $this->language->get('special_replace_helper');
		$data['special_add_on_top_helper'] = $this->language->get('special_add_on_top_helper');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['currency'] = $this->{$this->moduleModel}->getCurrencySign();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_specials_bulk', $data));
	}

	public function showDiscountsForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');

		$data['column_discounts'] = $this->language->get('column_discounts');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_discount_add'] = $this->language->get('button_discount_add');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['product_discount'])) {
			$product_discounts = $this->request->post['product_discount'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);
		} else {
			$product_discounts = array();
		}

		$data['product_discounts'] = array();

		foreach ($product_discounts as $product_discount) {
			$data['product_discounts'][] = array(
				'customer_group_id' => $product_discount['customer_group_id'],
				'quantity'          => $product_discount['quantity'],
				'priority'          => $product_discount['priority'],
				'price'             => $product_discount['price'],
				'date_start'        => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
				'date_end'          => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
			);
		}

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['action'] = $this->url->link($this->modulePath . '/updateproduct','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_discounts', $data));
	}

	public function showDiscountsBulkForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');
		
		$data['column_discounts_bulk'] = $this->language->get('column_discounts_bulk');
		$data['column_discounts'] = $this->language->get('column_discounts');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_actions'] = $this->language->get('entry_actions');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_special_add'] = $this->language->get('button_special_add');
		$data['operation_multiply'] = $this->language->get('operation_multiply');
		$data['operation_divide'] = $this->language->get('operation_divide');
		$data['operation_add'] = $this->language->get('operation_add');
		$data['operation_subtract'] = $this->language->get('operation_subtract');
		$data['all_customer_groups'] = $this->language->get('all_customer_groups');
		$data['discount_bulk_type'] = $this->language->get('discount_bulk_type');
		$data['discount_bulk_replace'] = $this->language->get('discount_bulk_replace');
		$data['discount_bulk_add_on_top'] = $this->language->get('discount_bulk_add_on_top');
		$data['discount_replace_helper'] = $this->language->get('discount_replace_helper');
		$data['discount_add_on_top_helper'] = $this->language->get('discount_add_on_top_helper');
		$data['button_discount_add'] = $this->language->get('button_discount_add');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['currency'] = $this->{$this->moduleModel}->getCurrencySign();

		$data['user_token'] = $this->session->data['user_token'];

		$data['action'] = $this->url->link($this->modulePath . '/updateproductbulk','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_discounts_bulk', $data));
	}

	public function showAttributesForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');
		
		$data['column_attributes'] = $this->language->get('column_attributes');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_attribute'] = $this->language->get('entry_attribute');
		$data['entry_text'] = $this->language->get('entry_text');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_attribute_add'] = $this->language->get('button_attribute_add');


		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
		}

		// Attributes
		$this->load->model('catalog/attribute');

		if (isset($this->request->post['product_attribute'])) {
			$product_attributes = $this->request->post['product_attribute'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_attributes = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
		} else {
			$product_attributes = array();
		}

		$data['product_attributes'] = array();

		foreach ($product_attributes as $product_attribute) {
			$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

			if ($attribute_info) {
				$data['product_attributes'][] = array(
					'attribute_id'                  => $product_attribute['attribute_id'],
					'name'                          => $attribute_info['name'],
					'product_attribute_description' => $product_attribute['product_attribute_description']
				);
			}
		}

		$data['action'] = $this->url->link($this->modulePath . '/updateproduct','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_attributes', $data));
	}

	public function showAttributesBulkForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');

		$data['column_attributes_bulk'] = $this->language->get('column_attributes_bulk');
		$data['column_attributes'] = $this->language->get('column_attributes');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_attribute'] = $this->language->get('entry_attribute');
		$data['entry_text'] = $this->language->get('entry_text');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_attribute_add'] = $this->language->get('button_attribute_add');
		$data['tab_text_insert'] = $this->language->get('tab_text_insert');
		$data['tab_text_delete'] = $this->language->get('tab_text_delete');
		$data['button_text_save'] = $this->language->get('button_text_save');
		$data['button_text_delete'] = $this->language->get('button_text_delete');
		$data['text_delete_info_attr'] = $this->language->get('text_delete_info_attr');
		$data['attr_bulk_type'] = $this->language->get('attr_bulk_type');
		$data['attr_bulk_replace'] = $this->language->get('attr_bulk_replace');
		$data['attr_bulk_add'] = $this->language->get('attr_bulk_add');
		$data['attr_replace_helper'] = $this->language->get('attr_replace_helper');
		$data['attr_add_helper'] = $this->language->get('attr_add_helper');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
		}

		// Attributes
		$this->load->model('catalog/attribute');

		if (isset($this->request->post['product_attribute'])) {
			$product_attributes = $this->request->post['product_attribute'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_attributes = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
		} else {
			$product_attributes = array();
		}

		$data['product_attributes'] = array();

		foreach ($product_attributes as $product_attribute) {
			$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

			if ($attribute_info) {
				$data['product_attributes'][] = array(
					'attribute_id'                  => $product_attribute['attribute_id'],
					'name'                          => $attribute_info['name'],
					'product_attribute_description' => $product_attribute['product_attribute_description']
				);
			}
		}

		$data['action'] = $this->url->link($this->modulePath . '/updateproductbulk','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_attributes_bulk', $data));
	}

	public function showOptionsBulkForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');

		$data['column_options_bulk'] = $this->language->get('column_options_bulk');
		$data['column_options'] = $this->language->get('column_options');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_option'] = $this->language->get('entry_option');
		$data['entry_required'] = $this->language->get('entry_required');
		$data['entry_text'] = $this->language->get('entry_text');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_subtract'] = $this->language->get('entry_subtract');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_option_points'] = $this->language->get('entry_option_points');
		$data['entry_weight'] = $this->language->get('entry_weight');
		$data['entry_points'] = $this->language->get('entry_points');

		$data['tab_text_insert'] = $this->language->get('tab_text_insert');
		$data['tab_text_delete'] = $this->language->get('tab_text_delete');
		$data['button_text_save'] = $this->language->get('button_text_save');
		$data['button_text_delete'] = $this->language->get('button_text_delete');
		$data['text_delete_info_opts'] = $this->language->get('text_delete_info_opts');

		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['entry_option_value'] = $this->language->get('entry_option_value');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_option_value_add'] = $this->language->get('button_option_value_add');


		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
		}

		// Options
		$this->load->model('catalog/option');

		if (isset($this->request->post['product_option'])) {
			$product_options = $this->request->post['product_option'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);
		} else {
			$product_options = array();
		}

		$data['product_options'] = array();

		foreach ($product_options as $product_option) {
			$product_option_value_data = array();

			if (isset($product_option['product_option_value'])) {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$product_option_value_data[] = array(
						'product_option_value_id' => $product_option_value['product_option_value_id'],
						'option_value_id'         => $product_option_value['option_value_id'],
						'quantity'                => $product_option_value['quantity'],
						'subtract'                => $product_option_value['subtract'],
						'price'                   => $product_option_value['price'],
						'price_prefix'            => $product_option_value['price_prefix'],
						'points'                  => $product_option_value['points'],
						'points_prefix'           => $product_option_value['points_prefix'],
						'weight'                  => $product_option_value['weight'],
						'weight_prefix'           => $product_option_value['weight_prefix']
					);
				}
			}

			$data['product_options'][] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => isset($product_option['value']) ? $product_option['value'] : '',
				'required'             => $product_option['required']
			);
		}

		$data['option_values'] = array();

		foreach ($data['product_options'] as $product_option) {
			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
				if (!isset($data['option_values'][$product_option['option_id']])) {
					$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
				}
			}
		}
		
        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['action'] = $this->url->link($this->modulePath . '/updateproduct','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_options_bulk', $data));
	}

	public function showOptionsForm() {
		$this->language->load('catalog/product');

		$this->load->model('catalog/product');

		$data['column_options'] = $this->language->get('column_options');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_option'] = $this->language->get('entry_option');
		$data['entry_required'] = $this->language->get('entry_required');
		$data['entry_text'] = $this->language->get('entry_text');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_subtract'] = $this->language->get('entry_subtract');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_option_points'] = $this->language->get('entry_option_points');
		$data['entry_weight'] = $this->language->get('entry_weight');
		$data['entry_points'] = $this->language->get('entry_points');

		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['entry_option_value'] = $this->language->get('entry_option_value');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['button_option_value_add'] = $this->language->get('button_option_value_add');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
		}

		// Options
		$this->load->model('catalog/option');

		if (isset($this->request->post['product_option'])) {
			$product_options = $this->request->post['product_option'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);
		} else {
			$product_options = array();
		}

		$data['product_options'] = array();

		foreach ($product_options as $product_option) {
			$product_option_value_data = array();

			if (isset($product_option['product_option_value'])) {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$product_option_value_data[] = array(
						'product_option_value_id' => $product_option_value['product_option_value_id'],
						'option_value_id'         => $product_option_value['option_value_id'],
						'quantity'                => $product_option_value['quantity'],
						'subtract'                => $product_option_value['subtract'],
						'price'                   => $product_option_value['price'],
						'price_prefix'            => $product_option_value['price_prefix'],
						'points'                  => $product_option_value['points'],
						'points_prefix'           => $product_option_value['points_prefix'],
						'weight'                  => $product_option_value['weight'],
						'weight_prefix'           => $product_option_value['weight_prefix']
					);
				}
			}

			$data['product_options'][] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => isset($product_option['value']) ? $product_option['value'] : '',
				'required'             => $product_option['required']
			);
		}

		$data['option_values'] = array();

		foreach ($data['product_options'] as $product_option) {
			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
				if (!isset($data['option_values'][$product_option['option_id']])) {
					$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
				}
			}
		}

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['action'] = $this->url->link($this->modulePath . '/updateproduct','user_token='.$this->session->data['user_token'],'SSL');
 
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_options', $data));
	}

	public function showOrdersPopup() {
		$this->language->load('sale/order');

		if (isset($this->request->get['product_id']) && !empty($this->request->get['product_id'])) {
			$product_id = $this->request->get['product_id'];
		}

		$data['heading_title']     = $this->language->get('heading_title');
		$data['text_order_id']     = $this->language->get('text_order_id');
		$data['text_customer']     = $this->language->get('text_customer');
		$data['column_status']     = $this->language->get('column_status');
		$data['column_total']      = $this->language->get('column_total');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_action']     = $this->language->get('column_action');
		$data['user_token']			   = $this->session->data['user_token'];

		$data['orders'] = array();
		$data['orders'] = $this->{$this->moduleModel}->getProductOrders($product_id);

		if (!empty($data['orders'])) {
			foreach ($data['orders'] as &$order) {
				$order['total'] = $this->currency->format($order['total'], $this->config->get('config_currency'));
				$order['info_link'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id']);
			}
		}

		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->modulePath . '/productmanager_orders', $data)); 
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
			$this->load->model('catalog/product');
			
			$this->load->model('catalog/option');

			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}

			if (isset($this->request->get['filter_model'])) {
				$filter_model = $this->request->get['filter_model'];
			} else {
				$filter_model = '';
			}

			if (isset($this->request->get['limit'])) {
				$limit = $this->request->get['limit'];
			} else {
				$limit = 5;
			}

			$filter_data = array(
				'filter_name'  => $filter_name,
				'filter_model' => $filter_model,
				'start'        => 0,
				'limit'        => $limit
			);

			$results = $this->{$this->moduleModel}->getProducts($filter_data);

			foreach ($results as $result) {
				$option_data = array();

				$product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

				foreach ($product_options as $product_option) {
					$option_info = $this->model_catalog_option->getOption($product_option['option_id']);

					if ($option_info) {
						$product_option_value_data = array();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$option_value_info = $this->model_catalog_option->getOptionValue($product_option_value['option_value_id']);

							if ($option_value_info) {
								$product_option_value_data[] = array(
									'product_option_value_id' => $product_option_value['product_option_value_id'],
									'option_value_id'         => $product_option_value['option_value_id'],
									'name'                    => $option_value_info['name'],
									'price'                   => (float)$product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
									'price_prefix'            => $product_option_value['price_prefix']
								);
							}
						}

						$option_data[] = array(
							'product_option_id'    => $product_option['product_option_id'],
							'product_option_value' => $product_option_value_data,
							'option_id'            => $product_option['option_id'],
							'name'                 => $option_info['name'],
							'type'                 => $option_info['type'],
							'value'                => $product_option['value'],
							'required'             => $product_option['required']
						);
					}
				}

				$json[] = array(
					'product_id' => $result['product_id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'model'      => $result['model'],
					'option'     => $option_data,
					'price'      => $result['price']
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function license() {
		$this->load->model('setting/setting');

		if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
            $this->request->post[$this->moduleName]['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
        }

        if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
            $this->request->post[$this->moduleName]['License'] = base64_encode($_POST['cHRpbWl6YXRpb24ef4fe']);
        }

    	$this->model_setting_setting->editSetting($this->moduleName, $this->request->post, $this->config->get('config_store_id'));
		
    	$this->session->data['validation_success'] = $this->language->get('validation_success');

    	exit;
	}

	// detect if the device is mobile
	private function isMobile() {
		$useragent = $this->request->server["HTTP_USER_AGENT"];

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)) || strpos(strtolower($useragent), 'ipad')) {

            return true;
		}

		return false;
	}
}