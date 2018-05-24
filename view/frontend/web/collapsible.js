// 2018-05-23
define([
	'df-lodash', 'jquery'
], function(_, $) {'use strict'; return function() {return $.widget('mage.collapsible', $.mage.collapsible, {
	/**
	 * 2018-05-23
	 * @override
	 * @see mage/collapsible::_create()
	 * @private
	 */
	_create: function() {
		var $e = this.element;
		var isFirst = $e.data('df-avboss-group-first');
		if ('undefined' !== typeof isFirst) {
			var NS = ['df.avboss.group', $e.attr('id')].join('.');
			var v = _.get(window, NS);
			this.options.active = 'undefined' !== typeof v ? v : isFirst;
			$e.on('dimensionsChanged', function(e, d) {
				_.set(window, NS, d.opened);
			});
		}
		this._super();
	}
});};});