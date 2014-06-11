<?php

require_once(dirname(__FILE__).'/../../leguideexport.php');

class leguideexportcsvModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();
		//$this->setTemplate('csv.tpl');
	}
	public function display()
	{
		$language = Tools::getValue('language');
		if (!empty($language)) $language = (int)$language;
		else $language = (int)Configuration::get('LEGUIDEEXPORT_LANGUAGE');

		$leGuideExport = new LeGuideExport();
		$csv = $leGuideExport->getCSV($language);

		header("Content-Type: text/plain; charset=UTF-8");

		echo $csv;
	}
}

?>
