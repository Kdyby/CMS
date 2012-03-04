(function () {
	$ = jQuery;

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
			this.element
				.html($(this.control))
				.find(':first-child')
				.putCursorAtEnd()
				.keydown(callback(this, 'handleKeys'))
				.trigger('inputCreated.grinder', [this]);
		},
		saveValue: function (event) {
			this.lastEvent = event;

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
				event.stopPropagation();
				event.stopImmediatePropagation();
				event.preventDefault();

				this.saveValue(event);
				if (event.shiftKey) {
					this.row.prevCell(this);

				} else {
					this.row.nextCell(this);
				}

				return false;

			} else if (this.wasSubmitted()) {
				event.stopPropagation();
				event.stopImmediatePropagation();
				event.preventDefault();

				this.saveValue(event);
				return false;
			}
		},
		wasEscaped: function () {
			return this.lastEvent.keyCode == 27 // 27 - Esc
				&& !(this.lastEvent.ctrlKey || this.lastEvent.shiftKey);
		},
		wasJumpedOut: function () {
			return this.lastEvent.keyCode == 9 && !this.lastEvent.ctrlKey // 9 - Tab
				&& /select|input/i.test(this.lastEvent.target.tagName);
		},
		wasSubmitted: function () {
			if (this.lastEvent.keyCode != 13 || this.lastEvent.keyCode != 10) {
				return false; // 13|10 - Enter
			}

			if (this.lastEvent.ctrlKey) {
				return /textarea/i.test(this.lastEvent.target.tagName);

			} else {
				return /input|select/i.test(this.lastEvent.target.tagName);
			}
		},
		setCurrent: function () {
			this.row.grid.currentCell = this;
		},
		unsetCurrent: function () {
			this.row.grid.currentCell = null;
		}
	});

	var ItemRow = $class({
		constructor: function (grid, itemId, cells) {
			this.grid = grid;
			this.id = itemId;
			this.element = null;
			this.data = {};
			this.controls = null;

			var me = this;
			$.each(cells, function (index, value) {
				cells[index] = new Cell(me, index);
				cells[index].setElement(value);
			});

			this.cells = new EndlessList(cells);
		},
		setElement:function (element) {
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
		nextCell:function (cell) {
			this.cells.getNextTo(cell)
				.editable();
		},
		prevCell:function (cell) {
			this.cells.getPrevTo(cell)
				.editable();
		},
		getData: function () {
			var values = {};
			$.each(this.cells.arr, function (index, cell) {
				values[index] = cell.value;
			});
			return values;
		}
	});

	var EditableGrinder = $class({
		constructor: function (grid, options) {
			this.table = $(grid);
			this.id = this.table.attr('id');
			this.rowEl = null;
			this.cellEl = null;
			this.currentCell = null;
			this.ajaxRequest = null;
			this.items = {};

			// default + user options
			this.options = $.extend({
				liveEdit: true,
				datepicker: true
			}, options || {});

			// init
			this.bindEvents();
		},
		bindEvents: function () {
			var me = this;

			// cells double click edit
			this.table.delegate('[data-grinder-cell]', 'dblclick', function (e) {
				me.showFormControl(e, this);
				e.stopPropagation();
				return false;
			});

			// submit data when sorting
			this.table.find('a.ajax.sortable').unbind('click'); // disable ajax sorting
			this.table.delegate('a.sortable', 'click', function (e) {
				if (me.currentCell) {
					me.currentCell.saveValue(e);
				}
				$.get($(this).attr('href'));
				e.stopPropagation();
				e.preventDefault();
				return false;
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
			$('body').keydown(callback(this, 'cancelEdit'));

			// live grid
			$('body').ajaxSuccess(function (event) {
				var table = $('#' + me.id);
				if (!table.hasClass('grinder-initialized')) {
					$(this).unbind(event);
					new EditableGrinder(table, me.options);
				}
			});

			// optionally attach all required events for datepicker to work
			if (this.options.datepicker) {
				this.setupDatepicker();
			}

			// initialized
			this.table.addClass('grinder-initialized');
		},
		setupDatepicker: function () {
			$('body').delegate('input.date,input[date]', 'inputCreated.grinder', function (event, cell) {
				var input = $(event.target);
				input.datepicker({
					dateFormat: input.data('kdyby-format'),
					onSelect: function () {
						cell.saveValue(event);
					}
				});
				input.focus();
			});

			$('body').delegate('input.date,input[date]', 'inputRemoved.grinder', function (event) {
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
			if (event.keyCode == 27 && !event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey) { // 27 - Esc
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

			this.cellEl = $(target);
			this.rowEl = this.cellEl.parent();
			var cellInfo = this.cellEl.data('grinder-cell');

			this.getItemRow(cellInfo.item)
				.editable(cellInfo.column);

			this.cellEl = null;
			this.rowEl = null;
		},
		loadFormItem: function (itemId, callback) {
			$.post(this.table.data('grinder-edit'), {'itemId': itemId}, callback, 'json');
		},
		getItemRow: function (id) {
			if (typeof this.items[id] === 'undefined') {
				this.items[id] = new ItemRow(this, id, this.initRow(id, this.rowEl));
				this.items[id].setElement(this.rowEl);
			}

			return this.items[id];
		},
		initRow: function (id, rowEl) {
			if (rowEl === null) {
				return null;
			}

			var cells = {};
			rowEl.find('[data-grinder-cell]').each(function () {
				var cell = $(this);
				var cellInfo = cell.data('grinder-cell');

				if (cellInfo.item == id) {
					cells[cellInfo.column] = cell;
				}
			});

			return cells;
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
