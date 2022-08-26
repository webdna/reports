/** global: Craft */
/** global: Garnish */
/**
 * Table Element Index View
 */
Reports.DataTable = Garnish.Base.extend({
  $table: null,
  $tableCaption: null,
  $selectedSortHeader: null,
  $statusMessage: null,
  sortDir: 'asc',
  sortAttr: null,

  structureTableSort: null,

  _totalVisiblePostStructureTableDraggee: null,
  _morePendingPostStructureTableDraggee: false,

  _broadcastListener: null,

  init: function ($table, settings) {
	// Set table caption
	this.$table = $($table);

	// Set the sort header
	this.initTableHeaders();

  },

  initTableHeaders: function () {
	const selectedSortAttr = this.$table.find('thead th:first').data('attribute');
	this.sortAttr = selectedSortAttr;
	const $tableHeaders = this.$table
	  .children('thead')
	  .children()
	  .children('[data-attribute]');

	for (let i = 0; i < $tableHeaders.length; i++) {
	  const $header = $tableHeaders.eq(i);
	  const attr = $header.attr('data-attribute');
	  let sortValue = 'none';

	  // Is this the selected sort attribute?
	  if (attr === selectedSortAttr) {
		this.$selectedSortHeader = $header;
		const selectedSortDir = 'asc';
		sortValue = selectedSortDir === 'asc' ? 'ascending' : 'descending';
		this.sortDir = selectedSortDir;
		$header.addClass('ordered ' + selectedSortDir);
		this.makeColumnSortable($header, true);
	  } else {
		// Is this attribute sortable?
		//const $sortAttribute = this.elementIndex.getSortAttributeOption(attr);
		//if ($sortAttribute.length) {
		  this.makeColumnSortable($header);
		//}
	  }

	  $header.attr('aria-sort', sortValue);
	}
  },

  makeColumnSortable: function ($header, sorted = false) {
	$header.addClass('orderable');

	const headerHtml = $header.html();
	//const $instructions = this.$tableCaption.find('[data-sort-instructions]');
	const $headerButton = $('<button/>', {
	  id: `dt-${$header.attr('data-attribute')}`,
	  type: 'button',
	  'aria-pressed': 'false',
	}).html(headerHtml);

	/*if ($instructions.length) {
	  $headerButton.attr('aria-describedby', $instructions.attr('id'));
	}*/

	//if (sorted) {
	  $headerButton.attr('aria-pressed', 'true');
	  $headerButton.on('click', this._handleSortHeaderClick.bind(this));
	/*} else {
	  $headerButton.on(
		'click',
		this._handleUnselectedSortHeaderClick.bind(this)
	  );
	}*/

	$header.empty().append($headerButton);
  },

  _handleSortHeaderClick: function (ev) {
	  var $header = $(ev.currentTarget).closest('th');
	  
	  var attr = $header.attr('data-attribute');
	  var newSortDir = 'asc';
	  if (this.sortAttr == attr) {
		  // Reverse the sort direction
		  newSortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
	  }
	  
	  this.sortAttr = attr;
	  this.sortDir = newSortDir;
	  
	if (this.$selectedSortHeader) {
	  this.$selectedSortHeader.removeClass('ordered asc desc');
	}
	$header.addClass('ordered '+this.sortDir);
	
	this.$selectedSortHeader = $header;
	
	this._sort();
	
  },
  
  _sort: function() {
	var $tbody = this.$table.find('tbody');
	var $rows = $tbody.find('tr');
	
	var column_index;
	this.$table.find('thead th').each(function(i){
		if ($(this).hasClass('ordered')) {
			column_index = i;
		}
	})
	
	var reverse = this.sortDir == 'asc';
	
	$rows.sort(function(a,b){
		var x = (reverse ? a:b).cells[column_index].innerText;
		var y = (reverse ? b:a).cells[column_index].innerText;
		return isNaN(x - y) ? x.localeCompare(y) : x - y;
	})
	
	$tbody.append($rows);
	
  },

});