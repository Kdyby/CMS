(function () {
	var $ = jQuery;


	/**
	 * For keeping
	 */
	var TwoLevelRegistry = $class({
		constructor: function (types) {
			this.types = types;
		},
		getInstance: function (type, id) {
			if (typeof this.types[type][id] === 'undefined') {
				return null;
			}

			return this.types[type][id];
		},
		callInstance: function (type, id, args) {
			var instance = this.getInstance(type, id);
			if (instance) {
				var obj = args.shift();
				instance.apply(obj, args);
			}
		},
		setInstance: function (type, id, instance) {
			this.types[type][id] = instance;
		}
	});


	/**
	 * Registry instance
	 * @type {TwoLevelRegistry}
	 */
	var StaticRegistry = new TwoLevelRegistry({
		grid: {},
		pageLoadEvent: {}
	});


	/**
	 * @param arr
	 * @return object
	 */
	var EndlessList = $class({
		constructor:function (list, mapper) {
			var me = this;
			this.first = null;
			this.last = null;
			this.list = list;
			this.arr = {};

			$.each(this.list, function (i, value) {
				if (!me.first) {
					me.first = value;
				}
				me.arr[mapper(value)] = value;
				me.last = value;
			});
		},
		get:function (key) {
			return this.arr[key];
		},
		getFirst:function () {
			return this.first;
		},
		getLast:function () {
			return this.last;
		},
		getNextTo:function (item) {
			return this.findNextTo(this.list, item) || this.first;
		},
		getPrevTo:function (item) {
			return this.findNextTo(this.list.slice().reverse(), item) || this.last;
		},
		findNextTo:function (items, searching) {
			var prev, next;
			$.each(items, function (index, value) {
				if (prev === searching) {
					next = value;
					return false;
				}
				prev = value;
			});
			return next;
		}
	});


	var Cell = $class({
		constructor: function (row, column) {
			this.row = row;
			this.name = column;
			this.element = null;
			this.control = null;
			this.value = null;
			this.hasChanged = false;
			this.lastEvent = null;
			this.spinner = $('<img />', {
				src:'/images/spinner.gif',
				style:'float:right'
			});
		},
		setElement: function (element) {
			if (this.element === null) {
				this.value = element.html();
			}

			this.element = element;
			return this;
		},
		wait: function () {
			this.spinner.prependTo(this.element);
		},
		editable: function () {
			this.wait();
			if (!this.control) {
				this.row.loadControl(this);
				return;
			}

			this.setCurrent();
			var control = this.element
				.html($(this.control))
				.find(':first-child');
			control.putCursorAtEnd();
			control.keydown(callback(this, 'handleKeys'));
			control.trigger('inputCreated.grinder', [this]);

			// make arrows work
			this.element.find('input').attr('autocomplete', 'off');
		},
		saveValue: function (event) {
			this.lastEvent = event;

			if (event) {
				event.stopPropagation();
				event.stopImmediatePropagation();
				event.preventDefault();
			}

			var control = this.element.find(':first-child');
			control.trigger('inputRemoved.grinder');
			control.attr('value', control.val());

			this.hasChanged = (control.val() != this.value);
			this.control = this.element.html();
			this.value = control.val();
			this.element.text(this.value);
			this.row.saveItem(this);

			this.unsetCurrent();
		},
		renderOriginal: function () {
			var control = this.element.find(':first-child');
			control.trigger('inputRemoved.grinder', [this]);
			this.element.text(this.value);
			this.unsetCurrent();
		},
		handleKeys: function (event) {
			if (event.altKey || event.metaKey) {
				return; // don't care about meta keys
			}

			this.lastEvent = event;
			if (this.wasEscaped()) {
				this.renderOriginal();

			} else if (this.wasJumpedOut()) {
				this.saveValue(event);
				if (event.shiftKey) {
					this.row.prevCell(this);

				} else {
					this.row.nextCell(this);
				}

			} else if (this.wasSubmitted()) {
				this.saveValue(event);

			} else if (this.wasRowChanged()) {
				this.saveValue(event);
				if (event.arrowUp) {
					this.row.prevRow(this);

				} else {
					this.row.nextRow(this);
				}
			}
		},
		wasEscaped: function () {
			return this.lastEvent.which == 27 // 27 - Esc
				&& !(this.lastEvent.ctrlKey || this.lastEvent.shiftKey);
		},
		wasJumpedOut: function () {
			var tagName = this.lastEvent.target.tagName;
			return this.lastEvent.which == 9 && !this.lastEvent.ctrlKey // 9 - Tab
				&& /select|input/i.test(tagName);
		},
		wasSubmitted: function () {
			if (this.lastEvent.which != 13 || this.lastEvent.which != 10) {
				return false; // 13|10 - Enter
			}

			var tagName = this.lastEvent.target.tagName;
			if (this.lastEvent.ctrlKey) {
				return /textarea/i.test(tagName);

			} else {
				return /input|select/i.test(tagName);
			}
		},
		wasRowChanged: function () {
			this.lastEvent.arrowUp = (this.lastEvent.which === 38); // 38 - up
			this.lastEvent.arrowDown = (this.lastEvent.which === 40); // 40 - down
			if (this.lastEvent.arrowUp || this.lastEvent.arrowDown) {
				var tagName = this.lastEvent.target.tagName;
				return this.lastEvent.ctrlKey || /input/i.test(tagName);
			}
			return false;
		},
		setCurrent: function () {
			this.row.grid.currentCell = this;
		},
		unsetCurrent: function () {
			this.row.grid.currentCell = null;
		}
	});

	var ItemRow = $class({
		constructor: function (grid, itemId) {
			this.grid = grid;
			this.id = itemId;
			this.element = null;
			this.data = {};
			this.controls = null;
			this.cells = {};
		},
		setCells: function (cells) {
			this.cells = new EndlessList(cells, function (cell) {
				return cell.name;
			});
		},
		setElement: function (element) {
			this.element = element;
			return this;
		},
		getCell: function(name) {
			return this.cells.get(name);
		},
		editable: function (column) {
			this.getCell(column).editable();
		},
		loadControl: function (cell) {
			if (this.controls === null) {
				var me = this;
				this.grid.loadFormItem(this.id, function (data) {
					me.controls = data.columns || {};
					me.loadControl(cell);
				});
				return;
			}
			cell.control = this.controls[cell.name];
			cell.editable();
		},
		saveItem: function (cell) {
			this.data[cell.name] = cell.value;

			if (cell.hasChanged) {
				this.grid.save(this, cell);
			}
		},
		nextCell: function (cell) {
			this.cells.getNextTo(cell)
				.editable();
		},
		prevCell: function (cell) {
			this.cells.getPrevTo(cell)
				.editable();
		},
		getData: function () {
			var values = {};
			$.each(this.cells.arr, function (index, cell) {
				values[index] = cell.value;
			});
			return values;
		},
		nextRow: function (cell) {
			this.grid.nextRow(this, cell);
		},
		prevRow: function (cell) {
			this.grid.prevRow(this, cell);
		}
	});


	var GrinderFactory = $class({
		constructor: function (grid) {
			this.grid = grid;
		},
		createGridRows: function () {
			var rows = [], visited = {}, me = this;
			this.findAllCells(this.grid.table).each(function () {
				var cell = $(this);
				var cellInfo = cell.data('grinder-cell');

				if (typeof visited[cellInfo.item] === 'undefined') {
					rows.push(me.createRow(cellInfo.item, cell.parent()));
					visited[cellInfo.item] = true;
				}
			});
			return rows; // grouped rows
		},
		createRow: function (id, rowEl) {
			var row = new ItemRow(this.grid, id);
			row.setCells(this.createCells(row, rowEl));
			row.setElement(rowEl);
			return row;
		},
		createCells: function (row, rowEl) {
			var cells = [];
			this.findAllCells(rowEl).each(function () {
				var cellEl = $(this);
				var cellInfo = cellEl.data('grinder-cell');

				if (cellInfo.item == row.id) {
					var current = new Cell(row, cellInfo.column);
					current.setElement(cellEl);
					cells.push(current);
				}
			});
			return cells;
		},
		findAllCells: function (el) {
			return el.find('[data-grinder-cell]');
		}
	});


	var GrinderPaginator = $class({
		constructor: function (grid) {
			this.grid = grid;
			this.element = $('#' + this.grid.id + '-paginator');
			this.spinner = $('<img />', {
				src:'/images/spinner.gif'
			});

			if (this.isPresent()) {
				this.pageCount = this.element.data('grinder-pagecount');
				this.page = this.element.data('grinder-page');
				this.bindEvents();

			} else {
				this.pageCount = 1;
				this.page = 1;
			}
		},
		bindEvents: function () {
			var me = this;
			this.element.delegate('a.ajax', 'click', function (event) {
				var link = $(this);
				link.html(me.spinner.clone());
				$.get(link.attr('href'));

				event.stopPropagation();
				event.preventDefault();
			});
		},
		isPresent: function () {
			return this.element.length > 0;
		},
		nextPage: function (success) {
			if (this.pageCount === 1) {
				return;
			}

			// defer the cell.editable() call after grid is loaded
			StaticRegistry.setInstance('pageLoadEvent', this.grid.id, success);

			// load next or first page
			this.getPageLink((this.page == this.pageCount) ? 1 : (this.page + 1), true)
				.click();
		},
		prevPage: function (success) {
			if (this.pageCount === 1) {
				return;
			}

			// defer the cell.editable() call after grid is loaded
			StaticRegistry.setInstance('pageLoadEvent', this.grid.id, success);

			// load prev or last page
			this.getPageLink((this.page == 1) ? this.pageCount : (this.page - 1))
				.click();
		},
		getPageLink: function (page, first) {
			var selector = 'a[data-grinder-page="' + page + '"]:';
			return this.element.find(selector + ((typeof first === 'undefined') ? 'last' : 'first'));
		}
	});


	var EditableGrinder = $class({
		constructor: function (grid, options) {
			this.table = $(grid);
			this.table.data('grinder', this);
			this.id = this.table.attr('id');
			this.currentCell = null;
			this.ajaxRequest = null;
			this.checkAllRow = null;

			// static registry
			StaticRegistry.setInstance('grid', this.id, this);

			// default + user options
			this.options = $.extend({
				liveEdit: true,
				datepicker: true
			}, options || {});

			// initialize all rows
			var rowsFactory = new GrinderFactory(this);
			var gridRows = rowsFactory.createGridRows();
			this.items = new EndlessList(gridRows, function (row) {
				return row.id;
			});

			// paginator
			this.paginator = new GrinderPaginator(this);

			// init
			this.bindEvents();

			// there might be waiting request
			StaticRegistry.callInstance('pageLoadEvent', this.id, [this]);
			StaticRegistry.setInstance('pageLoadEvent', this.id, null);
		},
		bindEvents: function () {
			var me = this;

			// cells double click edit
			this.table.delegate('[data-grinder-cell]', 'dblclick', function (event) {
				me.showFormControl(event, this);
				event.stopPropagation();
				event.preventDefault();
			});

			// rows can be selected
			this.table.delegate('tr', 'click', function (event) {
				if (!/td|tr|th/i.test(event.target.tagName)) {
					return;
				}

				var check = $(this).find('input[data-grinder-check-row]:first, input:checkbox.checkAll');
				if (check.length == 0) {
					return;
				}

				if (check.is(':checked')) {
					check.attr('checked', false);

				} else {
					check.attr('checked', true);
				}
				check.change();

				event.stopPropagation();
				event.preventDefault();
			});

			// check all rows
			this.table.find('input[data-grinder-check-row],input[data-grinder-check-all]').each(function () {
				$(this).show()
					.parent()
					.css({'width':'17px', 'text-align':'center'});
			});
			this.table.delegate('input[data-grinder-check-all]', 'change', callback(this, 'checkAllRows'));
			this.table.delegate('input[data-grinder-check-row]', 'change', function (event) {
				var rowCheck = $(this);
				var checkAll = rowCheck.closest('.grinder')
					.find('input[data-grinder-check-all="' + rowCheck.data('grinder-check-row') + '"]');

				checkAll.attr('checked', false);
				me.getCheckAllRow().hide();
				me.setCheckedAll(false);
			});

			// submit data when sorting
			this.table.find('a.ajax.sortable').unbind('click'); // disable ajax sorting
			this.table.delegate('a.sortable', 'click', function (e) {
				if (me.currentCell) {
					me.currentCell.saveValue(e);
				}

				StaticRegistry.setInstance('pageLoadEvent', me.id, null);
				$.get($(this).attr('href'));

				e.stopPropagation();
				e.preventDefault();
			});

			// submit data using Enter
			this.getForm().submit(function(event) {
				if (me.currentCell) {
					me.currentCell.saveValue(event);
					event.preventDefault();
					return false;
				}
			});

			// escape from edit
			var body = $('body');
			body.keydown(callback(this, 'cancelEdit'));

			// live grid
			body.ajaxSuccess(function (event) {
				var table = $('#' + me.id);
				if (!table.hasClass('grinder-initialized')) {
					new EditableGrinder(table, me.options);
					$(this).unbind(event);
				}
			});

			// optionally attach all required events for datepicker to work
			if (this.options.datepicker) {
				this.setupDatepicker();
			}

			// initialized
			this.table.addClass('grinder-initialized');
		},
		checkAllRows: function (event) {
			var check = $(event.target), me = this;
			var column = check.data('grinder-check-all');
			var checks = check.closest('.grinder').find('input[data-grinder-check-row="' + column + '"]');
			if (check.is(':checked')) {
				checks.attr('checked', true);
				this.getCheckAllRow().show();

			} else {
				checks.attr('checked', false);
				this.getCheckAllRow().hide();
				this.setCheckedAll(false);
			}
		},
		getCheckAllRow: function () {
			if (!this.table.is('table')) {
				return $('<tr />');
			}

			if (this.checkAllRow) {
				return this.checkAllRow;
			}

			this.checkAllRow = this.table.find('.checkAll');
			this.checkAllRow.prependTo(this.table.find('tbody'));
			return this.checkAllRow;
		},
		setCheckedAll: function (value) {
			this.table
				.closest('form')
				.find('input[name="checkAll"]')
				.attr('checked', value);
		},
		setupDatepicker: function () {
			var body = $('body');
			if (body.hasEvent('inputCreated.grinder') || body.hasEvent('inputRemoved.grinder')) {
				return; // prevent multiple bindings
			}

			body.delegate('input.date,input[date]', 'inputCreated.grinder', function (event, cell) {
				var input = $(event.target);
				input.datepicker({
					dateFormat: input.data('kdyby-format'),
					onSelect: function () {
						cell.saveValue(event);
					}
				});
				input.focus();
			});

			body.delegate('input.date,input[date]', 'inputRemoved.grinder', function (event) {
				var input = $(event.target);
				if (input.hasClass('hasDatepicker')) {
					input.datepicker("hide");
					input.datepicker("destroy");
				}
			});
		},
		getForm: function () {
			return this.table.closest('form');
		},
		cancelEdit: function (event) {
			if (event.which == 27 && !event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey) { // 27 - Esc
				if (this.currentCell) {
					this.currentCell.renderOriginal(); // close cell form control
				}
			}

			if ($('#' + this.id).length == 0) {
				$(event.target).unbind(event); // cleanup
			}
		},
		showFormControl: function (event, target) {
			if (this.currentCell) {
				this.currentCell.saveValue(event); // close cell form control
			}

			var cellInfo = $(target).data('grinder-cell');
			this.getItemRow(cellInfo.item)
				.editable(cellInfo.column);
		},
		loadFormItem: function (itemId, callback) {
			$.post(this.table.data('grinder-edit'), {'itemId': itemId}, callback, 'json');
		},
		getItemRow: function (id) {
			return this.items.get(id);
		},
		nextRow: function (row, cell) {
			var next = this.items.getNextTo(row), column = cell.name;
			if (next !== this.items.first || !this.paginator.isPresent()) {
				next.getCell(column)
					.editable();

			} else {
				this.paginator.nextPage(function () {
					this.items.first
						.getCell(column)
						.editable();
				});
			}
		},
		prevRow: function (row, cell) {
			var prev = this.items.getPrevTo(row), column = cell.name;
			if (prev !== this.items.last || !this.paginator.isPresent()) {
				prev.getCell(column)
					.editable();

			} else {
				this.paginator.prevPage(function () {
					this.items.last
						.getCell(column)
						.editable();
				});
			}
		},
		save: function (itemRow, invokingCell) {
			if (this.options.liveEdit) {
				this.sendChanges(itemRow);

			} else if (typeof invokingCell !== 'undefined' && invokingCell.wasSubmitted()) {
				this.sendChanges(itemRow);
			}
		},
		sendChanges: function (itemRow) {
			if (this.ajaxRequest) {
				this.ajaxRequest.abort();
			}

			var form = this.getForm();
			var data = form.serializeValues();
			data['entity[' + itemRow.id + ']'] = itemRow.getData();
			data['entity[save]'] = 'Save';
			this.ajaxRequest = $.post(form.attr('action'), data);
		}
	});

	$.fn.extend({
		editableGrid : function (options) {
			return $(this).each(function () {
				new EditableGrinder(this, options);
			});
		}
	});
})();

$(document).ready(function () {
	$('.grinder').editableGrid();
});
