
if (typeof DNAReports === typeof undefined) {
	DNAReports = {
		Options: {}
	};
}

DNAReports.Options.DateRange = Garnish.Base.extend({
	
	startDate: null,
	endDate: null,
	dynamicDateRange: null,
	
	init: function($container, settings) {
		this.$container = $container;
		this.setSettings(settings, DNAReports.Options.DateRange.defaults);
		
		this.startDateInput = $('<input type="hidden" name="options[dateRange][startDate]" value="">').appendTo(this.$container);
		this.endDateInput = $('<input type="hidden" name="options[dateRange][endDate]" value="">').appendTo(this.$container);
		this.dateRangeInput = $('<input type="hidden" name="options[dateRange][type]" value="">').appendTo(this.$container);
		
		DNAReports.createDateRangePicker({
			selected: this.settings.selected,
			startDate: this.settings.startDate,
			endDate: this.settings.endDate,
			options: this.settings.options,
			onChange: function(startDate, endDate, value) {
				this.dynamicDateRange = value;
				this.startDate = startDate;
				this.endDate = endDate;
				this.dateRangeInput.val(value);
				this.startDateInput.val('');
				this.endDateInput.val('');
				
				if (value == 'Custom') {
					if (startDate) {
						this.startDateInput.val(this.formatDate(startDate));
					}
					if (endDate) {
						this.endDateInput.val(this.formatDate(endDate));
					}
				}
			}.bind(this),
		}).appendTo(this.$container);
	},
	
	formatDate: function(date) {
		return date.getFullYear()+'-'+((date.getMonth()+1).toString().padStart(2, '0'))+'-'+(date.getDate().toString().padStart(2, '0'));
	}
	
}, {
	defaults: {
		selected: null,
		startDate: null,
		endDate: null,
		options: [
			'Today',
			'Yesterday',
			'This Week',
			'Last Week',
			'This Month',
			'Last Month',
			'This Year',
			'Past 7 Days',
			'Past 30 Days',
			'Past 90 Days',
			'Past Year',
			'This Financial Year',
		]
	}
});

DNAReports.createDateRangePicker = function (config) {
	var now = new Date();
	var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
	config = $.extend(
	  {
		class: '',
		options: {},
		onChange: $.noop,
		selected: null,
		startDate: null,
		endDate: null,
	  },
	  config
	);
	

	var $menu = $('<div/>', {class: 'menu'});
	var $ul = $('<ul/>', {class: 'padded'}).appendTo($menu);
	var $allOption = $('<a/>')
	  .addClass('sel')
	  .text(Craft.t('app', 'All'))
	  .data('handle', 'All');

	$('<li/>').append($allOption).appendTo($ul);
	

	var option;
	var selectedOption;
	for (var i = 0; i < config.options.length; i++) {
		
		var handle = config.options[i];
		option = {
			label: Craft.t('app', config.options[i]),
			
		};

	  var $li = $('<li/>');
	  var $a = $('<a/>', {text: option.label})
		.data('handle', handle);

	  if (config.selected && handle == config.selected) {
		selectedOption = $a[0];
	  }

	  $li.append($a);
	  $li.appendTo($ul);
	}
	
	if (config.selected && config.selected == 'All') {
		selectedOption = $allOption[0];
	}

	$('<hr/>').appendTo($menu);

	var $flex = $('<div/>', {class: 'flex flex-nowrap padded'}).appendTo($menu);
	var $startDate = Craft.ui.createDateField({label: Craft.t('app', 'From')})
	  .appendTo($flex)
	  .find('input');
	var $endDate = Craft.ui.createDateField({label: Craft.t('app', 'To')})
	  .appendTo($flex)
	  .find('input');

	// prevent ESC keypresses in the date inputs from closing the menu
	var $dateInputs = $startDate.add($endDate);
	$dateInputs.on('keyup', function (ev) {
	  if (
		ev.keyCode === Garnish.ESC_KEY &&
		$(this).data('datepicker').dpDiv.is(':visible')
	  ) {
		ev.stopPropagation();
	  }
	});

	// prevent clicks in the datepicker divs from closing the menu
	$startDate.data('datepicker').dpDiv.on('mousedown', function (ev) {
	  ev.stopPropagation();
	});
	$endDate.data('datepicker').dpDiv.on('mousedown', function (ev) {
	  ev.stopPropagation();
	});

	var menu = new Garnish.Menu($menu, {
	  onOptionSelect: function (option) {
		var $option = $(option);
		$btn.text($option.text());
		menu.setPositionRelativeToAnchor();
		$menu.find('.sel').removeClass('sel');
		$option.addClass('sel');

		// Update the start/end dates
		$startDate.datepicker('setDate', $option.data('startDate'));
		$endDate.datepicker('setDate', $option.data('endDate'));

		config.onChange(
		  $option.data('startDate') || null,
		  $option.data('endDate') || null,
		  $option.data('handle')
		);
	  },
	});
	

	$dateInputs.on('change', function () {
	  // Do the start & end dates match one of our options?
	  let startDate = $startDate.datepicker('getDate');
	  let endDate = $endDate.datepicker('getDate');
	  let startTime = startDate ? startDate.getTime() : null;
	  let endTime = endDate ? endDate.getTime() : null;


	  let $options = $ul.find('a');
	  let $option;
	  let foundOption = false;

	  for (let i = 0; i < $options.length; i++) {
		$option = $options.eq(i);
		if (
		  startTime === ($option.data('startTime') || null) &&
		  endTime === ($option.data('endTime') || null)
		) {
		  menu.selectOption($option[0]);
		  foundOption = true;
		  config.onChange(null, null, $option.data('handle'));
		  break;
		}
	  }

	  if (!foundOption) {
		$menu.find('.sel').removeClass('sel');
		$flex.addClass('sel');

		if (!startTime && !endTime) {
		  $btn.text(Craft.t('app', 'All'));
		} else if (startTime && endTime) {
		  $btn.text($startDate.val() + ' - ' + $endDate.val());
		} else if (startTime) {
		  $btn.text(Craft.t('app', 'From {date}', {date: $startDate.val()}));
		} else {
		  $btn.text(Craft.t('app', 'To {date}', {date: $endDate.val()}));
		}
		menu.setPositionRelativeToAnchor();

		config.onChange(startDate, endDate, 'Custom');
	  }
	});

	menu.on('hide', function () {
	  $startDate.datepicker('hide');
	  $endDate.datepicker('hide');
	});

	let btnClasses = 'btn menubtn';
	if (config.class) {
	  btnClasses = btnClasses + ' ' + config.class;
	}

	let $btn = $('<button/>', {
	  type: 'button',
	  class: btnClasses,
	  'data-icon': 'date',
	  text: Craft.t('app', 'All'),
	});

	new Garnish.MenuBtn($btn, menu);

	if (selectedOption) {
	  menu.selectOption(selectedOption);
	}

	if (config.startDate) {
	  $startDate.datepicker('setDate', config.startDate);
	}

	if (config.endDate) {
	  $endDate.datepicker('setDate', config.endDate);
	}

	if (config.startDate || config.endDate) {
	  $dateInputs.trigger('change');
	}

	return $btn;
  }