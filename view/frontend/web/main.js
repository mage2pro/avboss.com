// 2018-03-15 Dmitry Fedyuk https://mage2.pro/u/dmitry_fedyuk
// https://www.upwork.com/d/contracts/19718474
require(['jquery', 'domReady!'], function($) {
	var $table = $('#product-comparison');
	var $tbody = $table.children('tbody').last();
	var $tr = $tbody.children('tr');
	var number = function(v) {return v.replace(/[^\.\-\d]/g,'');};
	var gt = function(v, moda) {
		return 'Yes' === moda && 'No' === v || parseFloat(number(v)) < parseFloat(number(moda));
	};
	$tr.each(function() {
		var map = {};
		$(this).children('td').each(function() {
			var $this = $(this);
			var v = $.trim($this.text());
			if (!map.hasOwnProperty(v)) {
				map[v] = [];
			}
			map[v].push($this);
		});
		var moda = null;
		var modaCount = 0;
		$.each(map, function(v, items) {
			if (items.length > modaCount || items.length === modaCount && v !== moda && gt(v, moda)) {
				moda = v;
				modaCount = items.length;
			}
		});
		$.each(map, function(v, items) {
			if (v !== moda || 1 === modaCount) {
				$.each(items, function(index, $item) {
					$item.css('background', '#add8e6');
				});
			}
		});
	});
});