<?php
namespace Dfe\Avboss\Setup;
use Magento\Catalog\Api\Data\ProductAttributeInterface as A;
use Magento\Catalog\Model\Product as P;
use Magento\Catalog\Model\ResourceModel\Product\Action as PA;
use Magento\Catalog\Model\ResourceModel\Product\Collection as PC;
use Magento\Eav\Api\AttributeOptionManagementInterface as IOptionManagement;
use Magento\Eav\Api\Data\AttributeOptionInterface as IOption;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface as IOptionLabel;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Eav\Model\Entity\Attribute\OptionLabel;
use Magento\Eav\Model\Entity\Attribute\OptionManagement;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\ObjectManager as OM;
use Magento\Framework\DB\Adapter\AdapterInterface as IAdapter;
use Magento\Framework\DB\Adapter\Pdo\Mysql as Adapter;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Framework\Setup\ModuleContextInterface as IModuleContext;
use Magento\Framework\Setup\ModuleDataSetupInterface as IModuleDataSetup;
use Magento\Framework\Setup\UpgradeDataInterface as IUpgradeData;
use Magento\Indexer\Model\Indexer\Collection as IC;
use Magento\Setup\Model\ModuleContext;
// 2018-03-19
/** @final Unable to use the PHP «final» keyword here because of the M2 code generation. */
class UpgradeData implements IUpgradeData {
	/**
	 * 2018-03-19
	 * @override
	 * @see IUpgradeData::upgrade()
	 * @used-by \Magento\Setup\Model\Installer::handleDBSchemaData():
	 *		if ($currentVersion !== '') {
	 *			$status = version_compare($configVer, $currentVersion);
	 *			if ($status == \Magento\Framework\Setup\ModuleDataSetupInterface::VERSION_COMPARE_GREATER) {
	 *				$upgrader = $this->getSchemaDataHandler($moduleName, $upgradeType);
	 *				if ($upgrader) {
	 *					$this->log->logInline("Upgrading $type.. ");
	 *					$upgrader->upgrade($setup, $moduleContextList[$moduleName]);
	 *				}
	 *				if ($type === 'schema') {
 	 *					$resource->setDbVersion($moduleName, $configVer);
	 *				}
	 *				elseif ($type === 'data') {
	 *					$resource->setDataVersion($moduleName, $configVer);
	 *				}
	 *			}
	 *		}
	 *		elseif ($configVer) {
	 *			$installer = $this->getSchemaDataHandler($moduleName, $installType);
	 *			if ($installer) {
	 *				$this->log->logInline("Installing $type... ");
	 *				$installer->install($setup, $moduleContextList[$moduleName]);
	 *			}
	 *			$upgrader = $this->getSchemaDataHandler($moduleName, $upgradeType);
	 *			if ($upgrader) {
	 *				$this->log->logInline("Upgrading $type... ");
	 *				$upgrader->upgrade($setup, $moduleContextList[$moduleName]);
	 *			}
	 *			if ($type === 'schema') {
	 *				$resource->setDbVersion($moduleName, $configVer);
	 *			}
	 *			elseif ($type === 'data') {
	 *				$resource->setDataVersion($moduleName, $configVer);
	 *			}
	 *		}
	 * https://github.com/magento/magento2/blob/2.2.0-RC1.6/setup/src/Magento/Setup/Model/Installer.php#L844-L881
	 * @param IModuleDataSetup $setup
	 * @param IModuleContext $context
	 */
	function upgrade(IModuleDataSetup $setup, IModuleContext $context) {
		$setup->startSetup();
		$this->_context = $context;
		$this->_setup = $setup;
		if ($this->v('0.0.2')) {
			$this->correctPriceAttributes(self::$att_0_0_2);
		}
		if ($this->v('0.0.3')) {
			$this->correctPriceAttributes(self::$att_0_0_3);
		}
		if ($this->v('1.1.2')) {
			$this->cleanDecimalTable();
		}
		/*if ($this->v('1.2.0')) {
			$this->correctPriceAttributes(self::$att_1_2_0_int);
			$this->correctPriceAttributes(self::$att_1_2_0_decimal);
			$this->cleanDecimalTable();
		} */
		$setup->endSetup();
	}

	/**
	 * 2018-03-19
	 * @param string[] $att
	 *
	 */
	private function correctPriceAttributes(array $att) {
		$om = OM::getInstance(); /** @var OM $om */
		$pc = $om->create(PC::class); /** @var PC $pc */
		$pc->addAttributeToSelect($att)->load();
		$eavConfig = $om->get(EavConfig::class); /** @var EavConfig $eavConfig */
		$optionM = $om->get(IOptionManagement::class); /** @var IOptionManagement|OptionManagement $optionM */
		$s = $om->create(EavSetup::class, ['setup' => $this->_setup]); /** @var EavSetup $s */
		/**
		 * 2018-03-19
		 * @param string $c
		 * @param string $label
		 * @return int
		 * @throws LE
		 */
		$fOption = function($c, $label) use($eavConfig, $om, $optionM) {
			$optionLabel = $om->create(IOptionLabel::class); /** @var IOptionLabel|OptionLabel $optionLabel */
			$optionLabel->setStoreId(0);
			$optionLabel->setLabel($label);
			$option = $om->create(IOption::class); /** @var IOption|Option $option */
			$option->setLabel($optionLabel);
			$option->setStoreLabels([$optionLabel]);
			$option->setSortOrder(0);
			$option->setIsDefault(false);
			$optionM->add(A::ENTITY_TYPE_CODE, $c, $option);
			$attribute = $eavConfig->getAttribute(A::ENTITY_TYPE_CODE, $c);
			$optionId = $attribute->getSource()->getOptionId($label);
			return $optionId;
		};
		/**
		 * 2018-03-19
		 * @param string $c
		 * @param string $k
		 * @param string|null $v [optional]
		 */
		$fUpdate = function($c, $k, $v = null) use($eavConfig, $s) {
			$s->updateAttribute(A::ENTITY_TYPE_CODE, $c, $k, $v);
			$eavConfig->clear();
		};
		$pa = $om->get(PA::class); /** @var PA $pa */
		array_map(function($c) use($eavConfig, $fOption, $fUpdate, $pa, $pc) {
			$isDecimal = $this->isDecimal($c); /** @var bool $isDecimal */
			// 2018-03-24 https://magento.stackexchange.com/a/4892
			//$fUpdate($c, 'frontend_input', $isDecimal ? 'multiselect' : 'select');
			//$fUpdate($c, 'backend_type', $isDecimal ? 'varchar' : 'int');
			$fUpdate($c, 'frontend_input', $isDecimal ? 'multiselect' : 'select');
			$fUpdate($c, 'backend_type', $isDecimal ? 'decimal' : 'int');
			$fUpdate($c, 'backend_model');
			$fUpdate($c, 'source_model', Table::class);
			$map = []; /** @var array(string => int) $map */
			//$vals = [];
			foreach ($pc as $p) {/** @var P $p */
				$v = $p[$c];
				if (!is_null($v)) {
					//$vals[]= $v;
					$v = 'total_harmonic_distortion' === $c ? number_format($v, 2, '.', '') : (
						$isDecimal ? strval(floatval($v)) : intval(floatval($v))
					);
					if (!isset($map[$v])) {
						$map[$v] = $fOption($c, $v);
					}
					$pa->updateAttributes([$p->getId()], [$c => $map[$v]], 0);
				}
			}
			//$vals = array_unique($vals);
			//sort($vals);
			//file_put_contents("C:/work/clients/avboss.com/code/var/log/$c.txt", implode("\n", $vals));
			$conn = $this->_setup->getConnection();  /** @var Adapter|IAdapter $conn */
			$conn->update($this->_setup->getTable('amasty_amshopby_filter_setting'),
                ['display_mode' => 0, 'is_multiselect' => 1]
				,['? = filter_code' => "attr_$c"]
			);
		}, $att);
	}

	/**
	 * 2018-03-19
	 * @used-by upgrade()
	 */
	private function cleanDecimalTable() {
		$conn = $this->_setup->getConnection();  /** @var Adapter|IAdapter $conn */
		$s = $conn->select();
		$s->from($this->_setup->getTable('eav_attribute'), ['attribute_id']);
		$s->where('attribute_code IN(?)', [array_filter(self::att(), function($c) {return
			!$this->isDecimal($c)
		;})]);
		//$s->where('attribute_code IN(?)', [self::att()]);
		$conn->fetchCol($s);
		$conn->delete($this->_setup->getTable('catalog_product_entity_decimal'),
			$conn->quoteInto('attribute_id IN (?)', $conn->fetchCol($s))
		);
	}

	/**
	 * 2018-03-24
	 * @used-by cleanDecimalTable()
	 * @used-by correctPriceAttributes()
	 * @param string $c
	 * @return bool
	 */
	private function isDecimal($c) {return in_array($c, self::$att_1_2_0_decimal);}

	/**
	 * 2018-03-19 It checks whether the installed version of the current module is lower than $v.
	 * @used-by upgrade()
	 * @param string $v
	 * @return bool
	 */
	final protected function v($v) {return -1 === version_compare($this->_context->getVersion(), $v);}

	/**
	 * 2018-03-19
	 * @used-by upgrade()
	 * @used-by v()
	 * @var IModuleContext|ModuleContext
	 */
	private $_context;

	/**
	 * 2018-03-19
	 * @used-by upgrade()
	 * @var IModuleDataSetup
	 */
	private $_setup;

	/**
	 * 2018-03-19
	 * @used-by cleanDecimalTable()
	 * @used-by \Mage4\Grouping\Helper\Data::renderCompareSubGroup()
	 * @return string[]
	 */
	static function att() {return array_merge(self::$att_0_0_2, self::$att_0_0_3/*, self::$att_1_2_0_int*/);}

	/**
	 * 2018-03-20
	 * @used-by \Mage4\Grouping\Block\Product\View\Attributes::getAdditionalData()
	 * @var string[]
	 */
	static $fucking = [
		'analog_audio_outputs'
		,'blueray_analog_outputs'
		,'blueray_hdmi_inputs'
		,'blueray_hdmi_outputs'
		,'no_of_component_outputs'
		,'no_of_composite_inputs'
		,'no_of_composite_outputs'
		,'no_of_hdmi_inputs'
		,'no_of_hdmi_outputs'
		,'no_of_usb_ports'
		,'no_of_vga_inputs'
		,'no_of_zones'
		,'projectors_hdmi_inputs'
		,'soundbars_hdmi_output'
		,'soundbars_no_hdmi_inputs'
		,'tv_no_usb_3_0_ports'
		,'no_of_component_inputs'
	];

	/**
	 * 2018-03-19
	 * @used-by att()
	 * @used-by upgrade()
	 * @const string[]
	 */
	private static $att_0_0_2 = [
		'blueray_release_year'
		,'minimum_impedance_ohms'
		,'no_digital_coaxial_inputs'
		,'no_of_component_inputs'
		,'no_of_component_outputs'
		,'no_of_composite_inputs'
		,'no_of_composite_outputs'
		,'no_of_digital_optical_inputs'
		,'no_of_hdmi_inputs'
		,'no_of_hdmi_outputs'
		,'no_of_usb_ports'
		,'no_of_vga_inputs'
		,'no_of_zones'
		,'projectors_release_year'
		,'release_year'
		,'sub_high_fr'
		,'total_harmonic_distortion'
		,'tv_no_of_hdmi_inputs'
		,'tv_release_year'
	];

	/**
	 * 2018-03-19
	 * @used-by att()
	 * @used-by upgrade()
	 * @const string[]
	 */
	private static $att_0_0_3 = ['projectors_hdmi_inputs'];

	/**
	 * 2018-03-24
	 * @used-by att()
	 * @const string[]
	 */
	private static $att_1_2_0_decimal = [
		'bookshelf_woofer_size'
		,'center_midrange_size'
		,'center_woofer_size'
		,'driver_woofer_size'
		,'floor_midrange_size'
		,'floor_woofer_size'
		,'screen_size'
		,'soundbars_midrange_size'
		,'soundbars_popular_driver_size'
		,'soundbars_woofer_size'
		,'ss_center_speaker_size'
		,'ss_front_speakers_size'
		,'ss_subwoofer_size'
		,'ss_surroundspeaker_size'
		,'subwoofers_driver_size'
		,'surround_release_year'
		,'surround_woofer_size'
		,'zoom_lens'
	];

	/**
	 * 2018-03-24
	 * @used-by att()
	 * @const string[]
	 */
	private static $att_1_2_0_int = [
		'audible_noise_eco' // int
		,'audible_noise_full' // int
		,'bookshelf_gen_height' // int
		,'bookshelf_release_year'
		,'bookshelf_tech_max_recom_power' // int
		,'bookshelf_tech_sensitivity' // int
		,'brightness' // int
		,'center_gen_max_recom_power' // int
		,'center_release_year'
		,'center_sensitivity' // int
		,'contrast_ratio' // int
		,'floor_general_height' // int
		,'floor_release_year'
		,'floor_tech_max_recom_power' // int
		,'floor_tech_sensitivity' // int
		,'gen_release_year'
		,'inwall_release_year'
		,'lamp_life_eco' // int
		,'lamp_life_full' // int
		,'n_o_channels' // int
		,'rms_power_watts' // int
		,'screen_size'
		,'soundbars_popular_release_year'
		,'soundbars_popular_width' // int
		,'ss_center_speaker_sensitivity' // int
		,'ss_front_speakers_sensitivity' // int
		,'ss_subwoofer_rms_power' // int
		,'ss_surroundspeaker_sensitivity' // int
		,'subwoofers_peak_power' // int
		,'subwoofers_release_year'
		,'subwoofers_rms_power' // int
		,'surround_gen_height' // int
		,'surround_release_year'
		,'surround_tech_max_recom_power' // int
		,'surround_tech_sensitivity' // int
		,'tech_max_recommended_power' // int
		,'tech_sensitivity' // int
		,'tv_screen_size' // int
	];
}