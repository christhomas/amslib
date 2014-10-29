/*
 *	Amslib Datagrid
 * 
 *	Based on: Fuel UX Datagrid
 *	Original work: https://github.com/ExactTarget/fuelux
 *	Original Copyright: Copyright (c) 2012 ExactTarget
 *
 *	Licensed under the MIT license.
 */
var Amslib_Datagrid = my.Amslib_Datagrid = my.Class(Amslib,
{
	ajaxURL: false,
	
	STATIC: {
		autoload: function(){
			Amslib_Datagrid.instances = $(Amslib_Datagrid.options.autoload);
			
			Amslib_Datagrid.instances.each(function(){
				new Amslib_Datagrid(this,{});
			});
		},
		
		options:{
			amslibName:	"Amslib_Datagrid",
			autoload: 	"[data-amslib-datagrid='true']",
			ajaxURL:	"amslib-datagrid-ajax-url",
			
			//	Copied from the original datagrid code
			dataOptions:	{ pageIndex: 0, pageSize: 10 },
			loadingHTML:	'<div class="progress progress-striped active" style="width:50%;margin:auto;"><div class="bar" style="width:100%;"></div></div>',
			itemsText:		'items',
			itemText:		'item'
		},
		
		instances: false,
		
		// Relates to thead .sorted styles in datagrid.less
		SORTED_HEADER_OFFSET: 22
	},
	
	constructor: function(parent,options)
	{
		Amslib_Datagrid.Super.call(this,parent,Amslib_Datagrid.options.amslibName);
		
		this.options = $.extend(true, {}, Amslib_Datagrid.options, options);
		
		this.parent		= this.parent;
		
		//	search for a data tag on the parent, or default to the url, but if the final url 
		//	is the same as the data tag, there is no data tag, so invalidate it
		this.ajaxURL	= this.parent.data(this.options.ajaxURL) || this.options.ajaxURL;
		if(this.ajaxURL == this.options.ajaxURL) this.ajaxURL = false;
		
		this.$tfoot = this.parent.find('tfoot');
		this.$footer = this.parent.find('tfoot th');
		this.$footerchildren = this.$footer.children().show().css('visibility', 'hidden');
		this.$searchcontrol = this.parent.find('.datagrid-search');
		this.$filtercontrol = this.parent.find('.filter');
		this.$pagesize = this.parent.find('.grid-pagesize');
		this.$pageinput = this.parent.find('.grid-pager input');
		this.$pagedropdown = this.parent.find('.grid-pager .dropdown-menu');
		this.$prevpagebtn = this.parent.find('.grid-prevpage');
		this.$nextpagebtn = this.parent.find('.grid-nextpage');
		this.$pageslabel = this.parent.find('.grid-pages');
		this.$countlabel = this.parent.find('.grid-count');
		this.$startlabel = this.parent.find('.grid-start');
		this.$endlabel = this.parent.find('.grid-end');
		
		this.$thead = this.parent.find('thead');
		this.$topheader = this.$thead.find('th[data-datagrid-table-header]');
		this.$colheader = this.$thead.find("tr[data-datagrid-column-header]");
		this.$colheader = this.$colheader.length == 0 ? $('<tr>').appendTo(this.$thead) : this.$colheader;

		this.$tbody = this.parent.find("tbody");
		if(this.$tbody.length == 0) this.$tbody = $('<tbody>').insertAfter(this.$thead);

		// Shim until v3 -- account for FuelUX select or native select for page size:
		this.options.dataOptions.pageSize = this.$pagesize.hasClass('select')
			? parseInt(this.$pagesize.select('selectedItem').value, 10)
			: parseInt(this.$pagesize.val(), 10);

		// Shim until v3 -- account for older search class:
		if (this.$searchcontrol.length <= 0) {
			this.$searchcontrol = this.parent.find('.search');
		}
		
		if(!this.options.dataSource){
			this.options.dataSource = {
				columns: $.proxy(this,"getColumns"),
				data: $.proxy(this,"getData")
			}
		}

		this.columns = this.options.dataSource.columns();

		this.$nextpagebtn.on('click', $.proxy(this,"next"));
		this.$prevpagebtn.on('click', $.proxy(this,"previous"));
		this.$searchcontrol.on('searched cleared', $.proxy(this,"searchChanged"));
		this.$filtercontrol.on('changed', $.proxy(this,"filterChanged"));
		this.$colheader.on('click', 'th', $.proxy(this,"headerClicked"));

		if(this.$pagesize.hasClass('select')) {
			this.$pagesize.on('changed', $.proxy(this,"pagesizeChanged"));
		} else {
			this.$pagesize.on('change', $.proxy(this,"pagesizeChanged"));
		}

		this.$pageinput.on('change', $.proxy(this,"pageChanged"));

		this.renderColumns();

		if (this.options.stretchHeight) this.initStretchHeight();

		this.ajaxURL ? this.renderData() : this.renderComplete();
	},
	
	renderColumns: function () {
		var self = this;

		this.$footer.attr('colspan', this.columns.length);
		this.$topheader.attr('colspan', this.columns.length);

		var colHTML = '';

		$.each(this.columns, function (index, column) {
			colHTML += '<th data-property="' + column.property + '"';
			if (column.sortable) colHTML += ' class="sortable"';
			colHTML += '>' + column.label + '</th>';
		});

		this.$colheader.html(colHTML);
	},

	updateColumns: function ($target, direction) {
		this._updateColumns(this.$colheader, $target, direction);

		if (this.$sizingHeader) {
			this._updateColumns(this.$sizingHeader, this.$sizingHeader.find('th').eq($target.index()), direction);
		}
	},

	_updateColumns: function ($header, $target, direction) {
		var className = (direction === 'asc') ? 'icon-chevron-up' : 'icon-chevron-down';
		$header.find('i.datagrid-sort').remove();
		$header.find('th').removeClass('sorted');
		$('<i>').addClass(className + ' datagrid-sort').appendTo($target);
		$target.addClass('sorted');
	},

	updatePageDropdown: function (data) {
		var pageHTML = '';

		for (var i = 1; i <= data.pages; i++) {
			pageHTML += '<li><a>' + i + '</a></li>';
		}

		this.$pagedropdown.html(pageHTML);
	},

	updatePageButtons: function (data) {
		if (data.page === 1) {
			this.$prevpagebtn.attr('disabled', 'disabled');
		} else {
			this.$prevpagebtn.removeAttr('disabled');
		}

		if (data.page === data.pages) {
			this.$nextpagebtn.attr('disabled', 'disabled');
		} else {
			this.$nextpagebtn.removeAttr('disabled');
		}
	},

	renderData: function () {
		var self = this;

		this.$tbody.html(this.placeholderRowHTML(this.options.loadingHTML));

		this.options.dataSource.data(this.options.dataOptions, function (data) {
			var itemdesc = (data.count === 1) ? self.options.itemText : self.options.itemsText;
			var rowHTML = '';

			self.$footerchildren.css('visibility', function () {
				return (data.count > 0) ? 'visible' : 'hidden';
			});

			self.$pageinput.val(data.page);
			self.$pageslabel.text(data.pages);
			self.$countlabel.text(data.count + ' ' + itemdesc);
			self.$startlabel.text(data.start);
			self.$endlabel.text(data.end);

			self.updatePageDropdown(data);
			self.updatePageButtons(data);
			
			console.log(data.data);

			$.each(data.data, function (index, row) {
				rowHTML += '<tr>';
				$.each(self.columns, function (index, column) {
					rowHTML += '<td>' + row[column.property] + '</td>';
				});
				rowHTML += '</tr>';
			});

			if (!rowHTML) rowHTML = self.placeholderRowHTML('0 ' + self.options.itemsText);

			self.$tbody.html(rowHTML);
			
			self.renderComplete();
		});
	},
	
	renderComplete: function()
	{
		this.stretchHeight();

		this.parent.trigger('loaded');
	},

	placeholderRowHTML: function (content) {
		return $("<tr/>")
					.append("<td/>")
					.css({
						"text-align":		"center",
						"padding":			"20px",
						"border-bottom":	"none"
					})
					.attr("colspan",this.columns.length)
					.text(content);
	},

	headerClicked: function (e) {
		var $target = $(e.target);
		if (!$target.hasClass('sortable')) return;

		var direction = this.options.dataOptions.sortDirection;
		var sort = this.options.dataOptions.sortProperty;
		var property = $target.data('property');

		if (sort === property) {
			this.options.dataOptions.sortDirection = (direction === 'asc') ? 'desc' : 'asc';
		} else {
			this.options.dataOptions.sortDirection = 'asc';
			this.options.dataOptions.sortProperty = property;
		}

		this.options.dataOptions.pageIndex = 0;
		this.updateColumns($target, this.options.dataOptions.sortDirection);
		this.renderData();
	},

	pagesizeChanged: function (e, pageSize) {
		var v = pageSize ? pageSize.value : $(e.target).val();
		
		this.options.dataOptions.pageSize	= parseInt(v, 10)
		this.options.dataOptions.pageIndex	= 0;
		this.renderData();
	},

	pageChanged: function (e) {
		var pageRequested = parseInt($(e.target).val(), 10);
		pageRequested = (isNaN(pageRequested)) ? 1 : pageRequested;
		var maxPages = this.$pageslabel.text();
	
		this.options.dataOptions.pageIndex = (pageRequested > maxPages) 
			? maxPages - 1 
			: pageRequested - 1;

		this.renderData();
	},

	searchChanged: function (e, search) {
		this.options.dataOptions.search = search;
		this.options.dataOptions.pageIndex = 0;
		this.renderData();
	},

	filterChanged: function (e, filter) {
		this.options.dataOptions.filter = filter;
		this.options.dataOptions.pageIndex = 0;
		this.renderData();
	},

	previous: function () {
		this.options.dataOptions.pageIndex--;
		this.renderData();
	},

	next: function () {
		this.options.dataOptions.pageIndex++;
		this.renderData();
	},

	reload: function () {
		this.options.dataOptions.pageIndex = 0;
		this.renderData();
	},

	initStretchHeight: function () {
		this.$gridContainer = this.parent.parent();

		this.parent.wrap('<div class="datagrid-stretch-wrapper">');
		this.$stretchWrapper = this.parent.parent();

		this.$headerTable = $('<table>').attr('class', this.parent.attr('class'));
		this.$footerTable = this.$headerTable.clone();

		this.$headerTable.prependTo(this.$gridContainer).addClass('datagrid-stretch-header');
		this.$thead.detach().appendTo(this.$headerTable);

		this.$sizingHeader = this.$thead.clone();
		this.$sizingHeader.find('tr:first').remove();

		this.$footerTable.appendTo(this.$gridContainer).addClass('datagrid-stretch-footer');
		this.$tfoot.detach().appendTo(this.$footerTable);
	},

	stretchHeight: function () {
		if (!this.$gridContainer) return;

		this.setColumnWidths();

		var targetHeight = this.$gridContainer.height();
		var headerHeight = this.$headerTable.outerHeight();
		var footerHeight = this.$footerTable.outerHeight();
		var overhead = headerHeight + footerHeight;

		this.$stretchWrapper.height(targetHeight - overhead);
	},

	setColumnWidths: function () {
		if (!this.$sizingHeader) return;

		this.parent.prepend(this.$sizingHeader);

		var $sizingCells = this.$sizingHeader.find('th');
		var columnCount = $sizingCells.length;

		function matchSizingCellWidth(i, el) {
			if (i === columnCount - 1) return;

			var $el = $(el);
			var $sourceCell = $sizingCells.eq(i);
			var width = $sourceCell.width();

			// TD needs extra width to match sorted column header
			if ($sourceCell.hasClass('sorted') && $el.prop('tagName') === 'TD') width = width + Amslib_Datagrid.SORTED_HEADER_OFFSET;

			$el.width(width);
		}

		this.$colheader.find('th').each(matchSizingCellWidth);
		this.$tbody.find('tr:first > td').each(matchSizingCellWidth);

		this.$sizingHeader.detach();
	},
	
	//	NOTE: this is an automatic data provider when a customised one is not given in a data source
	getColumns: function(){
		var $this = this;
		
		this.columns = [];
		
		this.$colheader.find("th").each(function(){
			$this.columns.push({
				property:	$(this).data("property") || $(this).text().toLowerCase(),
				label:		$(this).text(),
				sortable:	$(this).data("sortable") || false
			});
		});

		return this.columns;
	},

	//	NOTE: this is an automatic data provider when a customised one is not given in a data source
	getData: function(options, callback){
		console.log("data method");
		var $this = this;
		var data = [];
		var rows = this.parent.find("tbody tr");
		console.log("number rows",rows.length);
		console.log("number tbody",this.parent.find("tbody").length);
		rows.each(function(){
			var c = $(this).find("td");
			var d = {};
			
			for(a=0;a<$this.columns.length;a++){
				d[$this.columns[a].property] = $(c[a]).html();
			}
			console.log(d);
			data.push(d);
		});
		
		rows.remove();
		
		callback({
			data: data,
			start: 0,
			end: 500,
			count: 50,
			pages: 10,
			page: 0
		});
	}
});

$(document).ready(Amslib_Datagrid.autoload);