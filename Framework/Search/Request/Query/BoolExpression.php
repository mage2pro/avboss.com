<?php
namespace Dfe\Avboss\Framework\Search\Request\Query;
use Magento\Framework\Search\Request\Query\BoolExpression as Sb;
use Magento\Framework\Search\Request\Query\Filter;
use Magento\Framework\Search\Request\QueryInterface as IQuery;
// 2018-03-24
/** @final Unable to use the PHP «final» keyword here because of the M2 code generation. */
class BoolExpression extends Sb {
	/**
	 * 2018-03-24
	 * @param Sb $o
	 * @param $k
	 * @return IQuery|null
	 */
	static function getMust_(Sb $o, $k) {return isset($o->must[$k]) ? $o->must[$k] : null;}

	/**
	 * 2018-03-24
	 * @param Sb $o
	 * @param $k
	 * @param $f
	 */
	static function setMust_(Sb $o, $k, IQuery $f) {$o->must[$k] = $f;}

	/**
	 * 2018-03-24
	 * @param Sb $o
	 * @param $k
	 * @param IQuery $f
	 */
	static function setShould(Sb $o, $k, IQuery $f) {$o->should[$k] = $f;}

	/**
	 * 2018-03-24
	 * @param Sb $o
	 * @param $k
	 */
	static function unsetMust(Sb $o, $k) {
		if (isset($o->must[$k])) {
			unset($o->must[$k]);
		}
	}
}