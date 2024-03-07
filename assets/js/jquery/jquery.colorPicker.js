/**
 * Really Simple Color Picker in jQuery
 *
 * Copyright (c) 2008 Lakshan Perera (www.laktek.com)
 * Licensed under the MIT (MIT-LICENSE.txt)  licenses.
 *
 */

(function ($) {

	var $colors = [];

	$.fn.colorPicker = function (options) {

		//console.log(options);

		$colors = (typeof options === 'undefined') ? [] : options.colors;

		//console.log($colors);

		//public methods
		$.fn.colorPicker.addColors = function (colorArray) {
			$.fn.colorPicker.defaultColors = $.fn.colorPicker.defaultColors.concat(colorArray);
		};

		$.fn.colorPicker.defaultColors = ($colors.length > 0) ? $colors : ['#d62728', '#1f77b4', '#aec7e8', '#ff7f0e', '#ffbb78', '#2ca02c', '#98df8a', '#ff9896', '#9467bd', '#c5b0d5', '#8c564b', '#c49c94', '#e377c2', '#f7b6d2', '#7f7f7f', '#c7c7c7', '#bcbd22', '#dbdb8d', '#17becf', '#9edae5', '#393b79', '#5254a3', '#6b6ecf', '#9c9ede', '#637939', '#8ca252', '#b5cf6b', '#cedb9c', '#8c6d31', '#bd9e39', '#e7ba52', '#e7cb94', '#843c39', '#ad494a', '#d6616b', '#e7969c', '#7b4173', '#a55194', '#ce6dbd', '#de9ed6', 'FF0F00', '000099', '006600', 'CC6600', '666699', '990099', '999900', '0066CC', 'FF6600', '996666', 'FF0033', '0099FF', '663300', '666600', 'FF00CC', '9900FF', 'FFCC00', '003366', '333333', '99FF00', '#000000', '#993300', '333300', '#000080', '#333399', '#333333', '#800000', '#FF6600', '#808000', '#008000', '#008080', '#0000FF', '#666699', '#808080', '#FF0000', '#FF9900', '#99CC00', '#339966', '#33CCCC', '#3366FF', '#800080', '#999999', '#FF00FF', '#FFCC00', '#FFFF00', '#00FF00', '#00FFFF', '#00CCFF', '#993366', '#C0C0C0', '#FF99CC', '#FFCC99', '#FFFF99', '#CCFFFF', '#99CCFF', '#390', '#099', '#C30', '#939', '#CC0', '#66C', '#0000FF', '#99FF66', '#004400', '#AA0000', '#FF0099', '#FFCC66', '#000080', '#00FF00', '#0000AA', '#005500', '#3399FF', '#3300FF', '#CC99CC', '#CC00CC', '#FFFF99', '#009900', '#FFFF33', '#FFCC33', '#66CCFF', '#FF6633', '#000000', '#1F497D', '#4F81BD', '#C0504D', '#9BBB59', '#8064A2', '#4BACC6', '#F79646', '#FFFF00', '#7F7F7F', '#BFBFBF', '#3F3F3F', '#938953', '#548DD4', '#95B3D7', '#D99694', '#C3D69B', '#B2A2C7', '#A5D0E0', '#FAC08F', '#F2C314', '#A5A5A5', '#262626', '#494429', '#17365D', '#366092', '#953734', '#76923C', '#5F497A', '#92CDDC', '#E36C09', '#C09100', '#7F7F7F', '#0C0C0C', '#1D1B10', '#0F243E', '#244061', '#632423', '#4F6128', '#3F3151', '#31859B', '#974806', '#7F6000'];

		if (this.length > 0) buildSelector();

		return this.each(function (i) {
			buildPicker(this)
		});

	};

	var selectorOwner;
	var selectorShowing = false;

	buildPicker = function (element) {

		//build color picker
		control = $("<div class='color_picker'>&nbsp;</div>");
		control.css('background-color', $(element).val());

		//bind click event to color picker
		control.bind("click", toggleSelector);

		//add the color picker section
		$(element).after(control);

		//add even listener to input box
		$(element).bind("change", function () {

			selectedValue = toHex($(element).val());
			$(element).next(".color_picker").css("background-color", selectedValue);

		});

		//hide the input box
		$(element).hide();

	};

	buildSelector = function () {

		var selector = $("<div id='color_selector'></div>");

		//add color pallete
		$.each($.fn.colorPicker.defaultColors, function (i) {

			var $this = this.replace("#","");
			var swatch = $("<div class='color_swatch'>&nbsp;</div>");

			swatch.css("background-color", "#" + $this);
			swatch.bind("click", function (e) {
				changeColor($(this).css("background-color"))
			});
			swatch.bind("mouseover", function (e) {
				$(this).css("border-color", "#598FEF");
				$("input#color_value").val(toHex($(this).css("background-color")));
			});
			swatch.bind("mouseout", function (e) {
				$(this).css("border-color", "#000");
				$("input#color_value").val(toHex($(selectorOwner).css("background-color")));
			});

			swatch.appendTo(selector);

		});

		//add HEX value field
		hex_field = $("<label for='color_value'>Hex</label><input type='text' size='8' id='color_value'/>");
		hex_field.bind("keydown", function (event) {
			if (event.keyCode == 13) {
				changeColor($(this).val());
			}
			if (event.keyCode == 27) {
				toggleSelector()
			}
		});

		$("<div id='color_custom'></div>").append(hex_field).appendTo(selector);

		$("body").append(selector);
		selector.hide();
	};

	checkMouse = function (event) {
		//check the click was on selector itself or on selectorOwner
		var selector = "div#color_selector";
		var selectorParent = $(event.target).parents(selector).length;
		if (event.target == $(selector)[0] || event.target == selectorOwner || selectorParent > 0) return

		hideSelector();
	};

	hideSelector = function () {
		var selector = $("div#color_selector");

		$(document).unbind("mousedown", checkMouse);
		selector.hide();
		selectorShowing = false
	};

	showSelector = function () {
		var selector = $("div#color_selector");

		//alert($(selectorOwner).offset().top);

		selector.css({
			top: $(selectorOwner).offset().top + ($(selectorOwner).outerHeight()),
			left: $(selectorOwner).offset().left
		});
		hexColor = $(selectorOwner).prev("input").val();
		$("input#color_value").val(hexColor);
		selector.show();

		//bind close event handler
		$(document).bind("mousedown", checkMouse);
		selectorShowing = true
	};

	toggleSelector = function (event) {
		selectorOwner = this;
		selectorShowing ? hideSelector() : showSelector();
	};

	changeColor = function (value) {
		if (selectedValue = toHex(value)) {
			$(selectorOwner).css("background-color", selectedValue);
			$(selectorOwner).prev("input").val(selectedValue).change();

			//close the selector
			hideSelector();
		}
	};

	//converts RGB string to HEX - inspired by http://code.google.com/p/jquery-color-utils
	toHex = function (color) {
		//valid HEX code is entered
		if (color.match(/[0-9a-fA-F]{3}$/) || color.match(/[0-9a-fA-F]{6}$/)) {
			color = (color.charAt(0) == "#") ? color : ("#" + color);
		}
		//rgb color value is entered (by selecting a swatch)
		else if (color.match(/^rgb\(([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]),[ ]{0,1}([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]),[ ]{0,1}([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])\)$/)) {
			var c = ([parseInt(RegExp.$1), parseInt(RegExp.$2), parseInt(RegExp.$3)]);

			var pad = function (str) {
				if (str.length < 2) {
					for (var i = 0, len = 2 - str.length; i < len; i++) {
						str = '0' + str;
					}
				}
				return str;
			};

			if (c.length == 3) {
				var r = pad(c[0].toString(16)), g = pad(c[1].toString(16)), b = pad(c[2].toString(16));
				color = '#' + r + g + b;
			}
		}
		else color = false;

		return color
	};

})(jQuery);


