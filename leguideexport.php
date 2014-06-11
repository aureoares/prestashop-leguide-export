<?php

if(!defined('_PS_VERSION_'))
	exit;

class LeGuideExport extends Module
{
	public $separator = ';';

	public $fields = array(
		'categoria' => 'category_name',
		'referencia_interna' => 'product_reference',
		'título' => 'product_name',
		'descripción' => 'product_description',
		'precio' => 'product_price_final',
		'URL_producto' => 'product_url',
		'URL_imagen' => 'image_url',
		'gastos_de_envío' => 'shipping_cost',
		'disponibilidad' => 'product_availability',
		'plazo_de_entrega' => 'delivery_time',
		'garantía' => 'product_warranty'
	);

	public function __construct()
	{
		$this->name = 'leguideexport';
		$this->tab = 'administration';
		$this->version = '1.0';
		$this->author = 'Áureo Ares';
		//$this->module_key = 'b62c9e46aab38aea7a9de75ec877dec8'; // for addons.prestashop.com
		$this->need_instance = 0;
		parent::__construct();

		$this->displayName = $this->l('LeGuide Export');
		$this->description = $this->l('Export your catalogue to LeGuide (Mercamania, Choozen, Pikengo, Dooyoo ...).');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		// Extra
	}

	public function install()
	{
		if (Shop::isFeatureActive()) Shop::setContext(Shop::CONTEXT_ALL);
		if (!parent::install()) return false;
		if (!Configuration::updateValue('LEGUIDEEXPORT_CATEGORIES', '')) return false;
		if (!Configuration::updateValue('LEGUIDEEXPORT_USEWHITELIST', 0)) return false;
		if (!Configuration::updateValue('LEGUIDEEXPORT_DELIVERYTIME', '')) return false;
		if (!Configuration::updateValue('LEGUIDEEXPORT_SHIPPINGCOST', '')) return false;
		if (!Configuration::updateValue('LEGUIDEEXPORT_LANGUAGE', (int)Configuration::get('PS_LANG_DEFAULT'))) return false;

		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall()) return false;
		if (!Configuration::deleteByName('LEGUIDEEXPORT_CATEGORIES')) return false;
		if (!Configuration::deleteByName('LEGUIDEEXPORT_USEWHITELIST')) return false;
		if (!Configuration::deleteByName('LEGUIDEEXPORT_DELIVERYTIME')) return false;
		if (!Configuration::deleteByName('LEGUIDEEXPORT_SHIPPINGCOST')) return false;
		if (!Configuration::deleteByName('LEGUIDEEXPORT_LANGUAGE')) return false;

		return true;
	}

	/*
	 * Configuration form for Prestashop 1.5
	 * */
	public function displayForm()
	{
		// Get default Language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$languages = Language::getLanguages();
		$categories = unserialize(Configuration::get('LEGUIDEEXPORT_CATEGORIES'));
		$useWhiteList = (int)Configuration::get('LEGUIDEEXPORT_USEWHITELIST');
		$deliveryTime = (string)Configuration::get('LEGUIDEEXPORT_DELIVERYTIME');
		$shippingCost = (float)Configuration::get('LEGUIDEEXPORT_SHIPPINGCOST');
		$categoriesToExport = array();
		foreach($categories as $key => $id_category)
			if($id_category)
				$categoriesToExport[] = array('id_category' => $id_category);

		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
			),
			'input' => array(
				array(
					'type' => 'select',
					'label' => $this->l('Language'),
					'desc' => $this->l('Select a default language'),
					'name' => 'LEGUIDEEXPORT_LANGUAGE',
					'required' => true,
					'options' => array(
						'query' => $languages,
						'id' => 'id_lang',
						'name' => 'name'
					)
				),
				array(
					'type' => 'text',
					'label' => $this->l('Delivery time'),
					'desc' => $this->l('General delivery time for all products'),
					'name' => 'LEGUIDEEXPORT_DELIVERYTIME',
					'size' => 50,
					'required' => false
				),
				array(
					'type' => 'text',
					'label' => $this->l('Shipping cost'),
					'desc' => $this->l('General shipping cost for all products'),
					'name' => 'LEGUIDEEXPORT_SHIPPINGCOST',
					'size' => 20,
					'required' => false
				),
				array(
					'type'		=> 'radio',
					'label'		=> $this->l('White/Black list'),
					'desc'		=> $this->l('Use the selected categories as a white or a black list?'),
					'name'		=> 'LEGUIDEEXPORT_USEWHITELIST',
					'required'	=> true,
					'class'		=> 't',
					'is_bool'	=> false,
					'values'	=> array(
						array(
							'id'	=> 'LEGUIDEEXPORT_USEWHITELIST_ON',
							'value'	=> 1,
							'label'	=> $this->l('White list: export only the selected categories.')
						),
						array(
							'id'	=> 'LEGUIDEEXPORT_USEWHITELIST_OFF',
							'value'	=> 0,
							'label'	=> $this->l('Black list: export all categories except the selected ones.')
						)
					)
				),
				array(
					'type' => 'categories',
					'label' => $this->l('Categories'),
					'desc' => $this->l('Select all the categories you want to export or ignore (depending if you are using a white or a black list).'),
					'name' => 'LEGUIDEEXPORT_CATEGORIES',
					'values' => array(
						'use_radio' => 0,
						'input_name' => 'LEGUIDEEXPORT_CATEGORIES[]',
						'selected_cat' => 0,
						'use_context' => 1,
						'use_search' => 1,
						'selected_cat' => $categoriesToExport, // Category::getSimpleCategories(1)
						'disabled_categories' => array(),
						'top_category' => new Category(1),
						'trads' => array(
							'Root' => array(
								'id_category' => 1,
								'name' => $this->l('Root')
							),
							'Collapse All' => $this->l('Collapse All'),
							'Expand All' => $this->l('Expand All'),
							'Check All' => $this->l('Check All'),
							'Uncheck All' => $this->l('Uncheck All'),
							'search' => $this->l('search'),
							'selected' => $this->l('selected'),
						)
					)
				)
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes -> Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
			'save' => array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current value
		$helper->fields_value['LEGUIDEEXPORT_CATEGORIES'] = Configuration::get('LEGUIDEEXPORT_CATEGORIES');
		$helper->fields_value['LEGUIDEEXPORT_USEWHITELIST'] = Configuration::get('LEGUIDEEXPORT_USEWHITELIST');
		$helper->fields_value['LEGUIDEEXPORT_LANGUAGE'] = Configuration::get('LEGUIDEEXPORT_LANGUAGE');
		$helper->fields_value['LEGUIDEEXPORT_DELIVERYTIME'] = Configuration::get('LEGUIDEEXPORT_DELIVERYTIME');
		$helper->fields_value['LEGUIDEEXPORT_SHIPPINGCOST'] = Configuration::get('LEGUIDEEXPORT_SHIPPINGCOST');

		return $helper->generateForm($fields_form);
	}

	public function getContent()
	{
		$output = '';

		if (Tools::isSubmit('submit'.$this->name))
		{
			// Handling configuration form submitted fields
			$categories = Tools::getValue('LEGUIDEEXPORT_CATEGORIES');
			foreach($categories as $key => $category)
				$categories[$key] = (int)$categories[$key];
			$categories = serialize($categories);
			$useWhiteList = (int)Tools::getValue('LEGUIDEEXPORT_USEWHITELIST');
			$language = (int)Tools::getValue('LEGUIDEEXPORT_LANGUAGE');
			$deliveryTime = (string)Tools::getValue('LEGUIDEEXPORT_DELIVERYTIME');
			$shippingCost = $this->formatPrice((float)Tools::getValue('LEGUIDEEXPORT_SHIPPINGCOST'));
			Configuration::updateValue('LEGUIDEEXPORT_USEWHITELIST', $useWhiteList);
			Configuration::updateValue('LEGUIDEEXPORT_CATEGORIES', $categories);
			Configuration::updateValue('LEGUIDEEXPORT_LANGUAGE', $language);
			Configuration::updateValue('LEGUIDEEXPORT_DELIVERYTIME', $deliveryTime);
			Configuration::updateValue('LEGUIDEEXPORT_SHIPPINGCOST', $shippingCost);
		}
		$language = (int)Configuration::get('LEGUIDEEXPORT_LANGUAGE');
		$output .= $this->displayForm();
		$output .= '
			<fieldset style="margin-top:10px; margin-bottom:10px;">
				<legend>'.$this->l('URL of the exported CSV file: ').'</legend>
				<p><a target="_blank" href="'.$this->context->link->getModuleLink('leguideexport', 'csv', array(), false, $language, null).'">'.$this->context->link->getModuleLink('leguideexport', 'csv', array(), false, $language, null).'</p>
				<p><strong>'.$this->l('In other languages:').'</strong></p>';
		$languages = Language::getLanguages();
		foreach ($languages as $key => $lang)
			if ($lang['id_lang'] != $language)
			{
				$csv_url = $this->context->link->getModuleLink('leguideexport', 'csv', array('language'=>$lang['id_lang']), false, $language, null);
				$output .= '
				<p><a target="_blank" href="'.$csv_url.'">'.$csv_url.' ('.$lang['name'].')</p>';
			}
		$output .= '
			</fieldset>';
		return $output;
	}

	public function sanitizeForCSV($data)
	{
		$data = (string)$data;
		$data = str_replace(array("\r", "\r\n", "\n"), " ", $data);
		$data = strip_tags($data);
		$data = str_replace("\"", "''", $data);
		return $data;
	}

	public function formatPrice($price)
	{
		$price = (float)$price;
		$price = round($price, 2);
		$price = (string)$price;
		$price = str_replace('.', ',', $price);
		return $price;
	}

	public function getCSV($id_lang = false)
	{
		$categories = unserialize(Configuration::get('LEGUIDEEXPORT_CATEGORIES'));
		$categories = implode(',', $categories);
		$useWhiteList = (int)Configuration::get('LEGUIDEEXPORT_USEWHITELIST');
		if ($id_lang === false) $id_lang = (int)Configuration::get('LEGUIDEEXPORT_LANGUAGE');
		else $id_lang = (int)$id_lang;
		$categoryFilterCondition = 'AND P.id_category_default '.(($useWhiteList) ? 'IN' : 'NOT IN').' ('.$categories.')';
		$sql = '
		SELECT P.id_product AS id_product, P.id_category_default AS id_category, P.reference AS product_reference, P.id_supplier AS id_supplier, P.id_manufacturer AS id_manufacturer, P.active AS active, P.available_for_order AS available, P.price AS product_price, 
			CL.name AS category_name, CL.link_rewrite AS category_rewrite, 
			S.name AS supplier_name, M.name AS manufacturer_name, 
			PL.name AS product_name, PL.link_rewrite AS product_rewrite, PL.description AS product_description, PL.description_short AS product_description_short, 
			C.id_category AS id_category, 
			I.id_image AS id_image, IL.legend AS image_legend 
		FROM `'._DB_PREFIX_.'product` P 
			INNER JOIN `'._DB_PREFIX_.'product_lang` PL ON P.id_product = PL.id_product 
			INNER JOIN `'._DB_PREFIX_.'supplier` S ON S.id_supplier = P.id_supplier 
			INNER JOIN `'._DB_PREFIX_.'manufacturer` M ON M.id_manufacturer = P.id_manufacturer 
			INNER JOIN `'._DB_PREFIX_.'category` C ON P.id_category_default = C.id_category 
			INNER JOIN `'._DB_PREFIX_.'category_lang` CL ON CL.id_category = P.id_category_default 
			INNER JOIN `'._DB_PREFIX_.'image` I ON I.id_product = P.id_product 
			INNER JOIN `'._DB_PREFIX_.'image_lang` IL ON I.id_image = IL.id_image 
		WHERE P.price >= 0 
			AND PL.id_lang = '.$id_lang.' 
			AND CL.id_lang = '.$id_lang.' 
			AND P.active = 1 
			AND P.available_for_order = 1 
			'.((!empty($categories)) ? $categoryFilterCondition : '').' 
			AND IL.id_lang = '.$id_lang.' 
			AND I.cover = 1 
		ORDER BY category_name, product_name, product_reference;';
		$data = Db::getInstance()->ExecuteS($sql);

		$csv = '';
		// Add CSV header fields (first line)
		$fieldNames = array_keys($this->fields);
		foreach ($fieldNames as $key => $name)
			$fieldNames[$key] = '"'.$fieldNames[$key].'"'; // Double quotes are mandatory
		$csv .= implode($this->separator, $fieldNames)."\n";
		foreach ($data as $key => $product)
		{
			$product['id_product'] = (int)$product['id_product'];
			$fieldValues = array();
			$productObj = new Product($product['id_product'], false, $id_lang, null, null);
			$link = new Link();
			$product['product_name'] = $this->sanitizeForCSV($product['product_name']);
			$product['product_reference'] = $this->sanitizeForCSV($product['product_reference']);
			$product['product_description_short'] = $this->sanitizeForCSV($product['product_description_short']);
			$product['product_description'] = $this->sanitizeForCSV($product['product_description']);
			$product['category_name'] = $this->sanitizeForCSV($product['category_name']);
			$product['product_url'] = $productObj->getLink();
			$product['image_url'] = $link->getImageLink($product['image_legend'], $product['id_product'].'-'.$product['id_image'], null);
			$product['shipping_cost'] = $this->sanitizeForCSV(Configuration::get('LEGUIDEEXPORT_SHIPPINGCOST'));
			$product['product_availability'] = 0;
			$product['delivery_time'] = $this->sanitizeForCSV(Configuration::get('LEGUIDEEXPORT_DELIVERYTIME'));
			$product['product_warranty'] = "";
			$product['product_price_final'] = $this->formatPrice(Product::getPriceStatic($product['id_product'], true, null, 2));

			foreach ($this->fields as $name => $code)
				$fieldValues[] = '"'.$product[$code].'"';
			$csv .= implode($this->separator, $fieldValues)."\n";
		}
		return $csv;
	}
}
?>
