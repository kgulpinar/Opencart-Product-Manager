var productManagerPath = 'extension/module/productmanager';

$(document).ready(function(){
	$('input[type=file]').on('change', prepareUpload);
	$('#bulk-image-upload-form').on('submit', uploadFiles);
	$('a[data-operation]').on('click', filterOperationsHandler);

	if (!String.prototype.format) {
	  String.prototype.format = function() {
	    var args = arguments;
	    return this.replace(/{(\d+)}/g, function(match, number) { 
	      return typeof args[number] != 'undefined' ? args[number] : match;
	    });
	  };
	}
});

function getSelectedProducts() {
	var pid = [];

	$('input[name^="selected"]:checked').each(function(index, element) {
        pid[index] = $(element).val();
    });

    return pid;
}

function filterOperationsHandler(event) {
	var target = $(event.target);
	var text = $(event.target).html() + ' <i class="fa fa-caret-down"></i>';
	var button = target.closest('.input-group-btn').find('button');
	var preClick = button.html();
	var operationValue = '';

	switch(target.data('operation')) {
		case '>'  : operationValue = 'more_than'; break;
		case '<'  : operationValue = 'less_than'; break;
		case '><' : operationValue = 'between';	  break;
		case '='  : operationValue = 'equal_to';  break;
	}

	target.closest('div.input-group').find('input[type=hidden]').val(operationValue);
	button.html(text);

	if (text.indexOf("Between") > -1) {
		target.closest('div.input-group').find('input[type=text]').hide();
		button.animate({width: "100%"}, 250);
		button.css('border-radius', '3px');
		target.closest('.form-group').find('div[id$=wrapper]').slideDown();
	}

	if (preClick.indexOf("Between") > -1 && text.indexOf("Between") == -1) {
		var currentWidth = button.width();
		var autoWidth = button.css('width', 'auto').width() + parseInt(button.css('padding-left').match(/\d+/)[0]) + parseInt(button.css('padding-right').match(/\d+/)[0]);
		button.width(currentWidth).animate({width: autoWidth}, 250, function() {button.css('width', 'auto').css('border-radius', '');});

		target.closest('.form-group').find('div[id$=wrapper]').slideUp();
		target.closest('div.input-group').find('input[type=text]').show();
	}
};


// Prepare for upload
function prepareUpload(event) {
	files = event.target.files;
};

// Upload files
function uploadFiles(e) {
 	e.stopPropagation();
    e.preventDefault();
	$('#modalErrors').html('');
	$('#modalResult').html('');
    var data = new FormData();
    var status = true;
    var method = $('select[name=\'upload_type\']').val();

    data.append('method', method);

    if (method == "add_to_each") {
    	var selected = [];
    	var checkedInputs =  $('input[name^=selected]:checked');
    	
    	if (checkedInputs.length > 0) {
	    	checkedInputs.each(function() {
	    		selected.push($(this).val());
	    	});

    		data.append('selected', selected);
    	} else {
    		status = false;
    		alert('Please check some products from the main board in order to use this function');
    	}
    }

	$.each(files, function(key, value){
        data.append(key, value);
		if (!value.type.match(/application\/.*zip.*/)) {
			alert(bulk_zip_error);
			status = false;
		}
    });

	if (status) {
		$.ajax({
			url: 'index.php?route=' + productManagerPath + '/bulkupload&user_token=' + user_token,
			type: 'POST',
			data: data,
			cache: false,
			dataType: 'json',
			processData: false,
			contentType: false,
			success: function(data, textStatus, jqXHR)
			{
				if (data['error']) {
					$('#modalErrors').show();
					for (i = 0; i<data['error'].length; i++) {
						$('#modalErrors').append('<br /><div class="alert alert-danger"><strong>' + (i+1) + '</strong>. ' + data['error'][i] + '</div><br />');
					}
				} else {
					$('#modalResult').show(400);

					if (method == "add_as_mains") {
						$('#modalResult').html('<br /><div class="alert alert-warning"><strong>' + data['total'] + '</strong> ' + bulk_image_result + '</div>');
					} else {
						$('#modalResult').html('<br /><div class="alert alert-warning">A total of <strong>' + data['total'] + '</strong> images have been uploaded to <strong>' + selected.length + '</strong> products.');
					}

					for (i = 0; i<data['products'].length; i++) {
						var element = 'thumb-image' + data['products'][i]['id'];
						$('#'+element).find('img').attr('width', '40px');
						$('#'+element).find('img').attr('height', '40px');
						$('#'+element).find('img').attr('src', data['products'][i]['product_image']);
						$('.editable.' + data['products'][i]['id'] + '.image_filename').html(data['products'][i]['image']);
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				alert('ERRORS: ' + textStatus);
				console.log('ERRORS: ' + textStatus);
			}
		});
	}
};

var columns = ['product_id','image','name','price','quantity','status'];

// Update table
function updateTable() {
	$('.table-productManager td[data-key]').hide();
	$(columns).each(function(index, element) {
	   $('.table-productManager td[data-key="' + element + '"]').show(); 
	});	
};

// Update Columns
function updateColumns(el) {
	var key = $(el).attr('data-key');
	var index = columns.indexOf(key);
	if (index != -1) {
		columns.splice(index, 1);
	}
	
	if ($(el).is(':checked')) {
		columns.push(key);
	}
	localStorage.setItem('columns', JSON.stringify(columns));
	$('#popoverHiddenContent input[data-key="'+key+'"]').attr('checked', $(el).is(':checked'));
	updateTable();
}

function getURL(curr_page) {
	if (curr_page == undefined) {
		curr_page = 1;
	}

	var sort = '';
	var order = '';

	$('tr.sorting td a').each(function() {
		if ($(this).hasClass('desc') || $(this).hasClass('asc')) {
			sort = $(this).attr('href').match(/sort=[a-zA-Z-_.]*/)[0].slice(5);
			order = $(this).attr('class') == 'asc' ? 'ASC' : 'DESC';
		}
	});

	var url = 'index.php?route=' + productManagerPath + '/getList&user_token=' + user_token + '&page=' + curr_page + '&sort=' + sort + '&order=' + order;	

	var copyURL = '?route=' + productManagerPath + '/copy&user_token=' + user_token + '&page=' + curr_page + '&sort=' + sort + '&order=' + order;
	
	var filter_name = $('input[name=\'filter_name\']').val();
	if (filter_name) {
		copyURL += '&filter_name=' + encodeURIComponent(filter_name);
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

	var filter_model = $('input[name=\'filter_model\']').val();
	if (filter_model) {
		copyURL += '&filter_model=' + encodeURIComponent(filter_model);
		url += '&filter_model=' + encodeURIComponent(filter_model);
	}
	
	var price_operation = $('input[name=\'price_operation\']').val();
	url += '&price_operation=' + encodeURIComponent(price_operation);
	copyURL += '&price_operation=' + encodeURIComponent(price_operation);

	if (price_operation == 'between') {
		var filter_price_from = $('input[name=\'filter_price_from\']').val();
		if (filter_price_from) {
			copyURL += '&filter_price_from=' + encodeURIComponent(filter_price_from);
			url += '&filter_price_from=' + encodeURIComponent(filter_price_from);
		}

		var filter_price_to = $('input[name=\'filter_price_to\']').val();
		if (filter_price_to) {
			copyURL += '&filter_price_to=' + encodeURIComponent(filter_price_to);
			url += '&filter_price_to=' + encodeURIComponent(filter_price_to);
		}
	} else {
		var filter_price = $('input[name=\'filter_price\']').val();
		if (filter_price) {
			copyURL += '&filter_price=' + encodeURIComponent(filter_price);
			url += '&filter_price=' + encodeURIComponent(filter_price);
		}
	}

	var quantity_operation = $('input[name=\'quantity_operation\']').val();
	copyURL += '&quantity_operation=' + encodeURIComponent(quantity_operation);
	url += '&quantity_operation=' + encodeURIComponent(quantity_operation);

	if (quantity_operation == 'between') {
		var filter_quantity_from = $('input[name=\'filter_quantity_from\']').val();
		if (filter_quantity_from) {
			copyURL += '&filter_quantity_from=' + encodeURIComponent(filter_quantity_from);
			url += '&filter_quantity_from=' + encodeURIComponent(filter_quantity_from);
		}

		var filter_quantity_to = $('input[name=\'filter_quantity_to\']').val();
		if (filter_quantity_to) {
			copyURL += '&filter_quantity_to=' + encodeURIComponent(filter_quantity_to);
			url += '&filter_quantity_to=' + encodeURIComponent(filter_quantity_to);
		}
	} else {
		var filter_quantity = $('input[name=\'filter_quantity\']').val();
		if (filter_quantity) {
			copyURL += '&filter_quantity=' + encodeURIComponent(filter_quantity);
			url += '&filter_quantity=' + encodeURIComponent(filter_quantity);
		}
	}

	var filter_status = $('select[name=\'filter_status\']').val();
	if (filter_status != '*') {
		copyURL += '&filter_status=' + encodeURIComponent(filter_status);
		url += '&filter_status=' + encodeURIComponent(filter_status);
	}
	
	var filter_limit = $('input[name=\'filter_limit\']').val();
	if (filter_limit) {
		copyURL += '&filter_limit=' + encodeURIComponent(filter_limit);
		url += '&filter_limit=' + encodeURIComponent(filter_limit);
	} else {
		copyURL += '&filter_limit=10';
		url += '&filter_limit=10';
	}
	
	var filter_manufacturer = $('input[name=\'filter_manufacturer_id\']').val();
	var filter_manufacturer_label = $('input[name=\'filter_manufacturer\']').val();
	if (filter_manufacturer_label) {
		copyURL += '&filter_manufacturer=' + encodeURIComponent(filter_manufacturer);
		url += '&filter_manufacturer=' + encodeURIComponent(filter_manufacturer);
	}
	
	var filter_category = $('input[name=\'filter_category\']').val();
	if (filter_category) {
		copyURL += '&filter_category=' + encodeURIComponent(filter_category);
		url += '&filter_category=' + encodeURIComponent(filter_category);
	}
	
	var filter_sku = $('input[name=\'filter_sku\']').val();
	if (filter_sku) {
		copyURL += '&filter_sku=' + encodeURIComponent(filter_sku);
		url += '&filter_sku=' + encodeURIComponent(filter_sku);
	}

	$('#button-copy').attr('formaction', location.origin + location.pathname + copyURL);

	return url;
}

// Get the main list
$(document).ready(function(){
	 $.ajax({
		url: getURL(),
		type: 'get',
		dataType: 'html',
		success: function(data) {
			$("#productsWrapper").html(data);
			updateTable();
		}

	 });
	 
	 if (window.localStorage && window.localStorage['columns']) {
		 var savedColumns = JSON.parse(localStorage.getItem('columns'));
		 if (savedColumns.length) {
		 	columns = savedColumns;
		 }
		 $(columns).each(function( index , value) {
			$('.tableColumnToggle[data-key="' + value + '"]').attr('checked',true);
		 });	 
	 }
	 
	 $("#tableColumns").popover({
        html : true, 
        content: function() {
          return $('#popoverHiddenContent').html();
        },
        title: function() {
          return $('#popoverHiddenTitle').html();
        },
		placement: 'bottom',
		template: '<div class="popover popover-medium"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title" style="font-size: 12px; font-weight: inherit;display:block;"></h3><div class="popover-content"><p></p></div></div></div>',
    });
	
	$('#bulk-image-upload').on('show.bs.modal', function () {
		$('#modalErrors').html('');
		$('#modalResult').html('');
		$('#modalErrors').hide();
		$('#modalResult').hide();
	});
});

// Button filter
$(document).ready(function(){
	$('#button-filter').on('click', function() {
		var curr_page = $('.pagination ul li.active span').html();

		$.ajax({
			url: getURL(curr_page),
			type: 'get',
			dataType: 'html',
			data: {
				columns: columns
			},
			success: function(data) {		
				$("#productsWrapper").html(data);
				updateTable();
			}
		});
	});
});

// Product name autocomplete
$(document).ready(function(){
	$('input[name=\'filter_name\']').autocomplete({
		'source': function(request, response) {
			$.ajax({
				url: 'index.php?route=' + productManagerPath + '/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
				dataType: 'json',
				success: function(json) {
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['product_id']
						}
					}));
				}
			});
		},
		'select': function(item) {
			$('input[name=\'filter_name\']').val(item['label']);
		}
	});
});

// Product category autocomplete
$(document).ready(function(){
	$('input[name=\'filter_category_name\']').autocomplete({
		'source': function(request, response) {
			$.ajax({
				url: 'index.php?route=catalog/category/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
				dataType: 'json',			
				success: function(json) {
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['category_id']
						}
					}));
				}
			});
		},
		'select': function(item) {
			$('input[name=\'filter_category_name\']').val(item['label']);
			$('input[name=\'filter_category\']').val(item['value']);
		}
	});
	
	
	$('input[name=\'filter_category_name\']').on('blur', function(e) {
		if ($('input[name=\'filter_category_name\']').val() == "") {
			$('input[name=\'filter_category\']').val("");
		}
	});
	
});

// Model autocomplete
$(document).ready(function(){
	$('input[name=\'filter_model\']').autocomplete({
		'source': function(request, response) {
			$.ajax({
				url: 'index.php?route=' + productManagerPath + '/autocomplete&user_token=' + user_token + '&filter_model=' +  encodeURIComponent(request),
				dataType: 'json',
				success: function(json) {
					response($.map(json, function(item) {
						return {
							label: item['model'],
							value: item['product_id']
						}
					}));
				}
			});
		},
		'select': function(item) {
			$('input[name=\'filter_model\']').val(item['label']);
		}
	});
});

// Manufacturer autocomplete
$(document).ready(function(){
	$('input[name=\'filter_manufacturer\']').autocomplete({
		'source': function(request, response) {
			$.ajax({
				url: 'index.php?route=catalog/manufacturer/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
				dataType: 'json',			
				success: function(json) {
					json.unshift({
						manufacturer_id: 0,
						name: '--- None ---'
					});
					
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['manufacturer_id']
						}
					}));
				}
			});
		},
		'select': function(item) {
			$('input[name=\'filter_manufacturer_id\']').val(item['value']);
			$('input[name=\'filter_manufacturer\']').val(item['label']);
		}	
	});
});

// Save the bulk changes
function doSaveBulk(pid, pdata, pvalue, plang, ptype) {
	plang = plang || 0;

	$.ajax({
		url: "index.php?route=" + productManagerPath + "/updateproductbulk&user_token=" + user_token,
		type: "POST",
		dataType: "json",
		data: {
			pid: pid,
			pdata: pdata,
			pvalue: pvalue,
			plang : plang,
			ptype : ptype
		},
		success: function (resp) {
			if(resp['error']) {
				alert(resp['error']);
			} else if (resp['success']) {
				//$('.after-edit-bulk').remove();
				//$('.sorting').children('td[data-key="' + pdata + '"]').find('i').show();
						
				for (i = 0; i<resp['products'].length; i++) {
					// status & shipping & subtract
					if (resp['field']=='status' || resp['field']=='shipping' || resp['field']=='subtract') {
						var text_y = text_enabled;
						var text_n = text_disabled;
						if (resp['field']=='shipping' || resp['field']=='subtract') {
							text_y = text_yes;	
							text_n = text_no;
						}
						if (resp['products'][i][resp['field']] == 0) {
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html(text_n);
						} else {
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html(text_y);
						}
						$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).attr('data-' + resp['field'], resp['products'][i][resp['field']]);
					// quantity
					} else if (resp['field']=='quantity') {
						$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).removeClass("label-warning label-danger label-success");
						$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html(resp['products'][i][resp['field']]);
						if (resp['products'][i]['quantity'] <= 0) { 
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).addClass("label-warning");
						} else if (resp['products'][i]['quantity'] <= 5) {
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).addClass("label-danger");
						} else { 
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).addClass("label-success");
						}								
					// stock_status & manufacturer & weight_class & tax_class
					} else if (pdata == 'tax_class' || pdata == 'weight_class' || pdata == 'stock_status' || pdata == 'manufacturer' || pdata == 'length_class') {
						var selectable = tax_classes;
						var selectable_id = 'tax_class_id';
						var selectable_name = 'title';
						if (pdata == 'weight_class') {
							selectable = weight_classes;
							selectable_id = 'weight_class_id';
							selectable_name = 'title';
						} else if (pdata == 'stock_status') {
							selectable = stock_statuses;
							selectable_id = 'stock_status_id';
							selectable_name = 'name';
						} else if (pdata == 'length_class') {
							selectable = length_classes;
							selectable_id = 'length_class_id';
							selectable_name = 'title';	
						} else if (pdata == 'manufacturer') {
							selectable = manufacturers;
							selectable_id = 'manufacturer_id';
							selectable_name = 'name';
						}
						for (var j = 0; j < selectable.length; j++) {
							if (resp['products'][i][selectable_id] == selectable[j][selectable_id]) {
								$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html(selectable[j][selectable_name]);
								$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html(selectable[j][selectable_name]).attr('data-' + resp['field'], resp['products'][i][selectable_id])
								break;
							}
						}								
					} else if (pdata == 'category') {

						if (resp['products'][i]['categories'].length > 0) {
							var categories = '';
							for (var z = 0; z<resp['products'][i]['categories'].length;z++) {
								categories += '<li>' + resp['products'][i]['categories'][z]['name'] + '</li>';
							}
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html('<ul>' + categories + '</ul>');
						} else {
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html('<center>- - -</center>');
						}

					} else if(pdata == 'filter') {
						if(resp['products'][i]['filters'].length > 0) {
							var filters = '';
							for(var z = 0; z < resp['products'][i]['filters'].length; z++){
								filters += '<li>' + resp['products'][i]['filters'][z]['name'] + '</li>';
							}
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html('<ul>' + filters + '</ul>');
						} else {
							$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html('<center>- - -</center>')
						}
					} else {
						$('.editable.' + resp['products'][i]['product_id'] + '.' + resp['field']).html(resp['products'][i][resp['field']]);
					}					
				}
			}
		}
	});
}

// Save the result
function doSave(pid, pdata, pvalue, plang) {		
	plang 	= plang || 0;
	
	$.ajax({
		url: "index.php?route=" + productManagerPath + "/updateproduct&user_token=" + user_token,
		type: "POST",
		dataType: "json",
		data: {
			pid: pid,
			pdata: pdata,
			pvalue: pvalue,
			plang: plang
		},
		success: function (resp) {
			var field = $('.editable.' + resp['product_id'] + '.' + resp['field']);

			if(resp['error']) {
				alert(resp['error']);
			} else if (resp['success']) {
				// status & shipping & subtract
				if (resp['field']=='image') {
					var element = 'thumb-image' + resp['product_id'];
					$('#'+element).find('img').attr('width', '40px');
					$('#'+element).find('img').attr('height', '40px');
					$('#'+element).find('img').attr('src', resp['product_image']);
					$('.editable.' + resp['product_id'] + '.image_filename').html(resp['product']['image']);
				} else if (resp['field']=='status' || resp['field']=='shipping' || resp['field']=='subtract') {
					var text_y = text_enabled;
					var text_n = text_disabled;
					if (resp['field']=='shipping' || resp['field']=='subtract') {
						text_y = text_yes;	
						text_n = text_no;
					}
					
					if (resp['product'][resp['field']] == 0) {
						field.html(text_n);
					} else {
						field.html(text_y);
					}
					field.attr('data-' + resp['field'], resp['product'][resp['field']]);
				// quantity
				} else if (resp['field']=='quantity') {
					field.removeClass("label-warning label-danger label-success");
					field.html(resp['product'][resp['field']]);
					if (resp['product']['quantity'] <= 0) { 
						field.addClass("label-warning");
					} else if (resp['product']['quantity'] <= 5) {
						field.addClass("label-danger");
					} else { 
						field.addClass("label-success");
					}
				// stock_status & manufacturer & weight_class & tax_class
				} else if (pdata == 'tax_class' || pdata == 'weight_class' || pdata == 'stock_status' || pdata == 'manufacturer' || pdata=='length_class') {
					var selectable = tax_classes;
					var selectable_id = 'tax_class_id';
					var selectable_name = 'title';
					if (pdata == 'weight_class') {
						selectable = weight_classes;
						selectable_id = 'weight_class_id';
						selectable_name = 'title';
					} else if (pdata == 'stock_status') {
						selectable = stock_statuses;
						selectable_id = 'stock_status_id';
						selectable_name = 'name';	
					} else if (pdata == 'length_class') {
						selectable = length_classes;
						selectable_id = 'length_class_id';
						selectable_name = 'title';
					} else if (pdata == 'manufacturer') {
						selectable = manufacturers;
						selectable_id = 'manufacturer_id';
						selectable_name = 'name';
					}
					
					for (var j = 0; j < selectable.length; j++) {
						if (resp['product'][selectable_id] == selectable[j][selectable_id]) {
							field.html(selectable[j][selectable_name]);
							field.html(selectable[j][selectable_name]).attr('data-' + resp['field'], resp['product'][selectable_id])
							break;
						}
					}													
				} else if (pdata == 'category') {
					if (resp['categories'].length > 0) {
						var categories = '';
						for (i = 0; i<resp['categories'].length;i++) {
							categories += '<li>' + resp['categories'][i]['name'] + '</li>';
						}
						field.html('<ul>' + categories + '</ul>');
					} else {
						field.html('<center>- - -</center>');
					}
				} else if (pdata =='filter') {
					if (resp['filters'].length > 0) {
						var filters = '';
						for(i = 0; i < resp['filters'].length; i++){
							filters += '<li>' + resp['filters'][i]['name'] + '</li>';
						}
						field.html('<ul>' + filters + '</ul>');
					} else {
						field.html('<center>- - -</center>');
					}

				} else if (pdata == 'image_filename') {
					field.html(resp['product']['image']);
                } else if (pdata == 'keyword') {
                    field.html(resp['product']['keyword']);
				} else { 
					// everything else
					field.html(resp['product'][resp['field']]);
				}
				
				if ($('.after-edit', field.parents('.edit-td')).length == 0) {
					field.show();
				}
			}
		}
	});
}

$(document).on('click', function(e) {
	window.temp_target = e.target;
	
	if ($('#product-category').length > 0) {
		if ($(e.target).parents('.after-edit[data-key="category"]').length > 0 || $(e.target).is('.after-edit[data-key="category"]')) {
			return;
		} else {
			$('.after-edit').blur();
			$('.after-edit[data-key="category"]').remove();
            $('.editable.category').show();
		}
	}
	
	if ($('#product-filter').length > 0) {
		if ($(e.target).parents('.after-edit[data-key="filter"]').length > 0 || $(e.target).is('.after-edit[data-key="filter"]')) {
			return;
		} else {
			$('.after-edit').blur();
			$('.after-edit[data-key="filter"]').remove();
		}
	}
	
	if ($('#bulk-category').length > 0) {
		if ($(e.target).parents('.after-edit-bulk[data-key="category"]').length > 0 || $(e.target).is('.after-edit-bulk[data-key="category"]')) {
			return;
		} else {
			$('.after-edit-bulk').blur();
			$('.after-edit-bulk[data-key="category"]').remove();
		}
	}

	if($('#bulk-filter').length > 0){
		if ($(e.target).parents('.after-edit-bulk[data-key="filter"]').length > 0 || $(e.target).is('.after-edit-bulk[data-key="filter"]')){
			return;
		}
		else {
			$('.after-edit-bulk').blur();
			$('.after-edit-bulk[data-key="filter"]').remove();
		}
	}
});

// Edit fields
function editable() {
	// Editable fields	
	$('#form-product').on('click', '.bulk-edit', function(e) {
		if ($(this).find('.after-edit-bulk').size()) return;
		var pdata = $(this).attr('data-key');
		var pid = [];
		pid = getSelectedProducts();

		if (pid.length <= 1) {
			alert(bulk_atleast2);	
		} else {
			$(this).hide();
			if (pdata == 'name') {
				var element = $(this).parents('td');
				$.each(lang_images, function(key, value) {
					element.append('<div class="after-edit-bulk input-group" style="width:200px;"><span class="input-group-addon" id="basic-addon1"><img src="' + value + '" /></span><input type="text" data-key="' + pdata + '" data-id="' + pid + '" data-lang-id="' + key + '" class="form-control after-edit-bulk" value="%value%" /></div>');
				});
			} else if (pdata == 'category') {
				var element = $(this).parents('td');

				$.ajax({
					url: "index.php?route=" + productManagerPath + "/getproductcategories&user_token=" + user_token,
					type: "POST",
					dataType: "json",
					data: {
						pid: pid,
						pdata: pdata
					},
					success: function (resp) {
						element.append('<div class="after-edit-bulk" data-key="' + pdata + '" data-id="' + pid + '"><select class="form-control" style="margin-top: 5px; margin-bottom: 5px;" name="bulk_cat_type"><option value="addCats">' + text_selected_categories.format('categories') + '</option><option value="replaceCats">' + text_replace_selected.format('categories') + '</option><option value="deleteCats">' + text_delete_selected.format('categories') + '</option></select><input type="text" name="category-bulk" value="" id="input-category" class="form-control" autocomplete="off"><div id="bulk-category" class="well well-sm after-edit well-category" style="height: 85px; text-align: left;     font-weight: normal; overflow: auto;">');
						$.each(resp, function(key, value) {
							$('.well-category').append('<div id="bulk-category' + value.category_id + '"><i class="fa fa-minus-circle"></i> ' + value.name + '<input type="hidden" name="product_category[]" value="' + value.category_id + '"></div>');
						});
						element.append('</div></div>');
										
						// Category autocomplete
						$('input[name=\'category-bulk\']').autocomplete({
							'source': function(request, response) {
								$.ajax({
									url: 'index.php?route=catalog/category/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
									dataType: 'json',			
									success: function(json) {
										response($.map(json, function(item) {
											return {
												label: item['name'],
												value: item['category_id']
											}
										}));
									}
								});
							},
							'select': function(item) {
								$('input[name=\'category-bulk\']').val('');
								$('#bulk_category' + item['value']).remove();
								$('#bulk-category').append('<div id="bulk_category' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="bulk_category[]" value="' + item['value'] + '" /></div>');	
							}
						});
						
						// Category delete
						$('#bulk-category').on('click', '.fa-minus-circle', function(event) {
							event.stopPropagation();
							
							$(this).parent().remove();
							var pvalue = [];
							var ptype = $("select[name='bulk_cat_type']").val();

							$("input[name='bulk_category[]']").each(function(index, element) {
								pvalue[index] = $(this).val();
							});
							doSaveBulk(pid, pdata, pvalue);
						});	
						
						$('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
					}
				});

			} else if (pdata == 'filter') {
				var element = $(this).parents('td');

				$.ajax({
					url: "index.php?route=" + productManagerPath + "/getproductfilters&user_token=" + user_token,
					type: "POST",
					dataType: "json",
					data: {
						pid: pid,
						pdata: pdata
					},
					success: function (resp) {
						element.append('<div class="after-edit-bulk" data-key="' + pdata + '" data-id="' + pid + '"><select class="form-control" style="margin-top: 5px; margin-bottom: 5px;" name="bulk_fil_type"><option value="addFilters">' + text_selected_categories.format('filters') + '</option><option value="replaceFilters">' + text_replace_selected.format('filters') + '</option><option value="deleteFilters">' + text_delete_selected.format('filters') + '</option></select><input type="text" name="bulk_filter" value="" id="input-filter" class="form-control" autocomplete="off"><div id="bulk-filter" class="well well-sm after-edit well-filter" style="height: 85px; text-align: left;     font-weight: normal; overflow: auto;">');
						$.each(resp, function(key, value) {
							$('.well-filter').append('<div id="bulk-filter' + value.filter_id + '"><i class="fa fa-minus-circle"></i> ' + value.name + '<input type="hidden" name="product_filter[]" value="' + value.filter_id + '"></div>');
						});
						element.append('</div></div>');
										
						// Filter autocomplete
						$('input[name=\'bulk_filter\']').autocomplete({
							'source': function(request, response) {
								$.ajax({
									url: 'index.php?route=catalog/filter/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
									dataType: 'json',			
									success: function(json) {
										response($.map(json, function(item) {
											return {
												label: item['name'],
												value: item['filter_id']
											}
										}));
									}
								});
							},
							'select': function(item) {
								$('input[name=\'bulk_filter\']').val('');
								$('#bulk_filter' + item['value']).remove();
								$('#bulk-filter').append('<div id="bulk_filter' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="bulk_filter[]" value="' + item['value'] + '" /></div>');	
							}
						});
						
						// Filter delete
						$('#bulk-filter').on('click', '.fa-minus-circle', function(event) {
							event.stopPropagation();
							
							$(this).parent().remove();
							var pvalue = [];
							var ptype = $("select[name='bulk_fil_type']").val();

							$("input[name='bulk_filter[]']").each(function(index, element) {
								pvalue[index] = $(this).val();
							});
							doSaveBulk(pid, pdata, pvalue);
						});	
						
						$('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
					}
				});

			} else if (pdata == 'status' || pdata == 'shipping' || pdata == 'subtract') {
				var text_y = text_enabled;
				var text_n = text_disabled;
				if (pdata=='shipping' || pdata=='subtract') {
					text_y = text_yes;	
					text_n = text_no;
				}
				var select_status = '<select name="status" data-key="' + pdata + '" data-id="' + pid + '" class="form-control after-edit-bulk">';
				if ($(this).find('span.editable').attr('data-' + pdata) == 1) {
					select_status += '<option value="1" selected="selected">' + text_y + '</option>';
					select_status += '<option value="0">' + text_n + '</option>';	
				} else {
					select_status += '<option value="1">' + text_y + '</option>';
					select_status += '<option value="0" selected="selected">' + text_n + '</option>';	
				}
				select_status += '</select>';
				$(this).parents('td').append(select_status);
			} else if (pdata == 'tax_class' || pdata == 'weight_class' || pdata == 'stock_status' || pdata == 'manufacturer' || pdata == 'length_class') {
				var select_select = '<select name="status" data-key="' + pdata + '" data-id="' + pid + '" class="form-control after-edit-bulk">';
				var selectable = tax_classes;
				var selectable_id = 'tax_class_id';
				var selectable_name = 'title';
				if (pdata == 'weight_class') {
					selectable = weight_classes;
					selectable_id = 'weight_class_id';
					selectable_name = 'title';
				} else if (pdata == 'stock_status') {
					selectable = stock_statuses;
					selectable_id = 'stock_status_id';
					selectable_name = 'name';	
				} else if (pdata == 'length_class') {
					selectable = length_classes;
					selectable_id = 'length_class_id';
					selectable_name = 'title';
				} else if (pdata == 'manufacturer') {
					selectable = manufacturers;
					selectable_id = 'manufacturer_id';
					selectable_name = 'name';
				}
				for (var i = 0; i < selectable.length; i++) {
					select_select += '<option value="' + selectable[i][selectable_id] + '">' + selectable[i][selectable_name] + '</option>';
				}
				select_select +='</select>';
				$(this).parents('td').append(select_select);
			} else if(pdata == 'specials') {
				$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showSpecialsBulkForm&product_id=0&user_token=' + user_token);
				$('#module-edit-custom-modal').show();
				$('.bulk-edit').show();
			} else if(pdata == 'attributes') {
				$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showAttributesBulkForm&product_id=0&user_token=' + user_token);
				$('#module-edit-custom-modal').show();
			 	$('.bulk-edit').show();
			} else if(pdata == 'options') {
				$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showOptionsBulkForm&product_id=0&user_token=' + user_token);
				$('#module-edit-custom-modal').show();
			 	$('.bulk-edit').show();
			} else if(pdata == 'discounts') {
				$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showDiscountsBulkForm&product_id=0&user_token=' + user_token);
				$('#module-edit-custom-modal').show();
			 	$('.bulk-edit').show();
			} else {				
				$(this).parents('td').append('<input type="text" data-key="' + pdata + '" data-id="' + pid + '" class="form-control after-edit-bulk" value="%value%" />');
			}
			//$(this).parents('td').find('.after-edit-bulk').focus();
			// $('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
		}
	});

	var typeEvent = isMobileDevice ? 'taphold' : 'dblclick'; // solve the double click problem under mobile devices

	// Show the edit fields
	$('#form-product').on(typeEvent, '.edit-td', function(e){
		if ($(this).find('.after-edit').size()) return;
		$('#form-product').on('selectstart', function (event) {
			event.preventDefault();
		});
		
		var pid = $(this).parents('tr').attr('data-id');
		var pdata = $(this).attr('data-key');

		if (/*(pdata.indexOf('image') < 0) ||*/ (pdata.indexOf('product_id', 0) == 0)) {
			// silence
		// status & shipping and subtract
		} else if (pdata == 'name') {
			$(this).find('span.editable').hide();
			var element = $(this);
			$.ajax({
				url: "index.php?route=" + productManagerPath + "/getproductname&user_token=" + user_token,
				type: "POST",
				dataType: "json",
				data: {
					pid: pid,
					pdata: pdata
				},
				success: function (resp) {
					$.each(resp, function(key, value) {
						element.append('<div class="after-edit input-group" style="width:200px;"><span class="input-group-addon" id="basic-addon1"><img src="'+ value['image'] + '" /></span><input type="text" data-key="' + pdata + '" data-lang-id="' + key + '" data-id="' + pid + '" class="form-control after-edit" value="' + value['name'] + '" /></div>');
					});
					
					$('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
				}
			});
		} else if (pdata == 'keyword') {
			$(this).find('span.editable').hide();
			var element = $(this);
			$.ajax({
				url: "index.php?route=" + productManagerPath + "/getproducturlalias&user_token=" + user_token,
				type: "POST",
				dataType: "json",
				data: {
					pid: pid,
					pdata: pdata
				},
				success: function (resp) {
					$.each(resp, function(key, value) {
						element.append('<div class="after-edit input-group" style="width:200px;"><span class="input-group-addon" id="basic-addon1"><img src="'+ value['image'] + '" /></span><input type="text" data-key="' + pdata + '" data-lang-id="' + key + '" data-id="' + pid + '" class="form-control after-edit" value="' + value['keyword'] + '" /></div>');
					});
					
					$('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
				}
			});
		} else if (pdata == 'category') {
			$(this).find('span.editable').hide();
			var element = $(this);
			
			$.ajax({
				url: "index.php?route=" + productManagerPath + "/getproductcategories&user_token=" + user_token,
				type: "POST",
				dataType: "json",
				data: {
					pid: pid,
					pdata: pdata
				},
				success: function (resp) {
					element.append('<div class="after-edit" data-key="' + pdata + '" data-id="' + pid + '"><input type="text" name="category" value="" id="input-category" class="form-control" autocomplete="off"><div id="product-category" class="well well-sm well-category" style="height: 85px; overflow: auto;">');
					$.each(resp, function(key, value) {
						$('.well-category').append('<div id="product-category' + value.category_id + '"><i class="fa fa-minus-circle"></i> ' + value.name + '<input type="hidden" name="product_category[]" value="' + value.category_id + '"></div>');
					});
					element.append('</div></div>');
									
					// Category autocomplete
					$('input[name=\'category\']').autocomplete({
						'source': function(request, response) {
							$.ajax({
								url: 'index.php?route=catalog/category/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
								dataType: 'json',			
								success: function(json) {
									response($.map(json, function(item) {
										return {
											label: item['name'],
											value: item['category_id']
										}
									}));
								}
							});
						},
						'select': function(item) {
							$('#product-category' + item['value']).remove();
							$('#product-category').append('<div id="product-category' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_category[]" value="' + item['value'] + '" /></div>');	
						}
					});
					
					// Category delete
					$('#product-category').on('click', '.fa-minus-circle', function(event) {
						event.stopPropagation();
						
						$(this).parent().remove();
						var pvalue = [];
						$("input[name='product_category[]']").each(function(index, element) {
							pvalue[index] = $(this).val();
						});
						doSave(pid, pdata, pvalue);
					});	
					
					$('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
				}
			});
		} else if (pdata == 'filter') {
			$(this).find('span.editable').hide();
			var element = $(this);
			
			$.ajax({
				url: "index.php?route=" + productManagerPath + "/getproductfilters&user_token=" + user_token,
				type: "POST",
				dataType: "json",
				data: {
					pid: pid,
					pdata: pdata
				},
				success: function (resp) {
					element.append('<div class="after-edit" data-key="' + pdata + '" data-id="' + pid + '"><input type="text" name="filter" value="" id="input-filter" class="form-control" autocomplete="off"><div id="product-filter" class="well well-sm well-filter" style="height: 85px; overflow: auto;">');
					$.each(resp, function(key, value) {
						$('.well-filter').append('<div id="product-filter' + value.filter_id + '"><i class="fa fa-minus-circle"></i> ' + value.name + '<input type="hidden" name="product_filter[]" value="' + value.filter_id + '"></div>');
					});
					element.append('</div></div>');
									
					// Filter autocomplete
					$('#input-filter').autocomplete({
						'source': function(request, response) {
							$.ajax({
								url: 'index.php?route=catalog/filter/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
								dataType: 'json',			
								success: function(json) {
									response($.map(json, function(item) {
										return {
											label: item['name'],
											value: item['filter_id']
										}
									}));
								}
							});
						},
						'select': function(item) {
							$('#product-filter' + item['value']).remove();
							$('#product-filter').append('<div id="product-filter' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_filter[]" value="' + item['value'] + '" /></div>');	
						}
					});
					
					// Filter delete
					$('#product-filter').on('click', '.fa-minus-circle', function(event) {
						event.stopPropagation();
						
						$(this).parent().remove();
						var pvalue = [];
						$("input[name='product_filter[]']").each(function(index, element) {
							pvalue[index] = $(this).val();
						});
						doSave(pid, pdata, pvalue);
					});	
					
					$('[data-key="' + pdata + '"][data-id="' + pid + '"]').first().focus();
				}
			});

		} else if (pdata == 'status' || pdata == 'shipping' || pdata == 'subtract') {
			var text_y = text_enabled;
			var text_n = text_disabled;
			if (pdata=='shipping' || pdata=='subtract') {
				text_y = text_yes;	
				text_n = text_no;
			}
 			$(this).find('span.editable').hide();
			var select_status = '<select name="status" data-key="' + pdata + '" data-id="' + pid + '" class="form-control after-edit">';
			if ($(this).find('span.editable').attr('data-' + pdata) == 1) {
				select_status += '<option value="1" selected="selected">' + text_y + '</option>';
				select_status += '<option value="0">' + text_n + '</option>';	
			} else {
				select_status += '<option value="1">' + text_y + '</option>';
				select_status += '<option value="0" selected="selected">' + text_n + '</option>';	
			}
			select_status += '</select>';
			$(this).append(select_status);
			$(this).parent().find('.after-edit').focus();
		} else if (pdata == 'tax_class' || pdata == 'weight_class' || pdata == 'stock_status' || pdata == 'manufacturer' || pdata == 'length_class') {
			var select_select = '<select name="status" data-key="' + pdata + '" data-id="' + pid + '" class="form-control after-edit">';
			var selectable = tax_classes;
			var selectable_id = 'tax_class_id';
			var selectable_name = 'title';
			if (pdata == 'weight_class') {
				selectable = weight_classes;
				selectable_id = 'weight_class_id';
				selectable_name = 'title';
			} else if (pdata == 'stock_status') {
				selectable = stock_statuses;
				selectable_id = 'stock_status_id';
				selectable_name = 'name';	
			} else if (pdata == 'length_class') {
				selectable = length_classes;
				selectable_id = 'length_class_id';
				selectable_name = 'title';
			} else if (pdata == 'manufacturer') {
				selectable = manufacturers;
				selectable_id = 'manufacturer_id';
				selectable_name = 'name';
			}
			for (var i = 0; i < selectable.length; i++) {
				if ($(this).find('span.editable').attr('data-' + pdata) == selectable[i][selectable_id]) {
				  select_select += '<option value="' + selectable[i][selectable_id] + '" selected="selected">' + selectable[i][selectable_name] + '</option>';
			  } else {
				  select_select += '<option value="' + selectable[i][selectable_id] + '">' + selectable[i][selectable_name] + '</option>';
			  }
				
			}
			select_select +='</select>';
			$(this).find('span.editable').hide();
			$(this).append(select_select);
			$(this).parent().find('.after-edit').focus();
		// everything else
		} else {
			$(this).find('span.editable').hide();
			$(this).append('<input type="text" data-key="' + pdata + '" data-id="' + pid + '" class="form-control after-edit" value="' + $(this).find('span.editable').text() + '" />');
			$(this).parent().find('.after-edit').focus();
		}
		
	});
	
	// What happens on blur
	$('#form-product').on('blur', '.after-edit', function(e){
		var pid = $(this).attr('data-id');
		var pdata = $(this).attr('data-key');
		if (pdata == 'category' || pdata == 'filter') {
			if ($(temp_target).parents('.after-edit[data-key="category"]').length > 0 || $(temp_target).is('.after-edit[data-key="category"]') || 
				$(temp_target).parents('.after-edit[data-key="filter"]').length > 0 || $(temp_target).is('.after-edit[data-key="filter"]')) {
				return false;
			}
		}
		
		$('[data-key="' + pdata + '"][data-id="' + pid + '"]', $(this).parent()).each(function(index, element) {
			var pvalue = $(this).val();
			var plang = $(this).attr('data-lang-id');
			
			if (pdata == 'category') {
				pvalue = [];
				$("input[name='product_category[]']").each(function(index, element) {
					pvalue[index] = $(this).val();
				});
			}

			else if(pdata == 'filter'){
				pvalue = [];
				$("input[name='product_filter[]'").each(function(index, element){
					pvalue[index] = $(this).val();
				});
			}

			doSave(pid, pdata, pvalue, plang);
		});
		
		if (pdata != 'category') {
			if (!$(e.relatedTarget).is('[data-key="' + pdata + '"][data-id="' + pid + '"]') || $(e.relatedTarget).length == 0) {
				var related = $('[data-key="' + pdata + '"][data-id="' + pid + '"]');
				$(related).add(related.parents('.after-edit')).remove();
			}
		}
	});
	
	// What happens on key down
	$('#form-product').on('keydown', '.after-edit', function(e) {
		if (e.keyCode == 13) {
			e.stopPropagation();
			e.preventDefault();
			e.stopImmediatePropagation();
			this.blur();
		}
	});
	
	// Save bulk on blur
	$('#form-product').on('blur', '.after-edit-bulk', function(e){
		var pdata = $(this).attr('data-key');

		if ($(e.relatedTarget).is('input')) {
			return false;
		}

		if (!$(e.relatedTarget).is('[data-key="' + pdata + '"]') || $(e.relatedTarget).length == 0) {
			if (pdata == 'category' || pdata == 'filter') {
				if (($(temp_target).parents('.after-edit-bulk[data-key="category"]').length > 0 || $(temp_target).is('.after-edit-bulk[data-key="category"]') || ($(temp_target).parents('.after-edit-bulk[data-key="filter"]').length > 0 || $(temp_target).is('.after-edit-bulk[data-key="filter"]')))) {
					
					return false;
				}
			}

			if (confirm(confirm_bulk)) {
				$('[data-key="' + pdata + '"]:not(i)', $(this).parents('[data-key="' + pdata + '"]')).each(function(index, element) {
					var pid = $(this).attr('data-id');
					var pvalue = $(this).val();
					var plang = $(this).attr('data-lang-id');
					
					if (pdata == 'category') {
						var ptype = $("select[name='bulk_cat_type']").val();
						pvalue = [];
						$("input[name='bulk_category[]']").each(function(index, element) {
							pvalue[index] = $(this).val();
						});
					} else if (pdata == 'filter'){
						var ptype = $("select[name='bulk_fil_type']").val();
						pvalue = [];
						$("input[name='bulk_filter[]']").each(function(index, element){
							pvalue[index] = $(this).val();
						});
					}

					doSaveBulk(pid, pdata, pvalue, plang, ptype);
				});
			}
		}
		
		var related = $('.after-edit-bulk[data-key="' + pdata + '"]');
			
		$(related).add(related.parents('.after-edit-bulk')).remove();
		$('.sorting').children('td[data-key="' + pdata + '"]').find('i').show();
		
		e.stopPropagation();
		e.preventDefault();
		e.stopImmediatePropagation();
	});
	
	// Save bulk on keydown
	$('.sorting').on('keydown', '.after-edit-bulk', function(e) {
		if (e.keyCode == 13) {
			e.stopPropagation();
			e.preventDefault();
			e.stopImmediatePropagation();
			this.blur();
		}
	});
	
	// Image Manager
	$('#form-product').on('click', 'a[data-toggle=\'image-manager\']', function(e) {
		e.preventDefault();
		var element = this;
		var parent = $(element).parent();
		
		$(element).popover({
			html: true,
			placement: 'right',
			trigger: 'manual',
			content: function() {
				return '<button type="button" class="btn btn-primary button-image-click"><i class="fa fa-pencil"></i></button> <button type="button" class="btn btn-danger button-clear-click"><i class="fa fa-trash-o"></i></button>';
			}
		});
		
		$(element).popover('toggle');		
	
		$('.button-image-click', parent).on('click', function() {
			$('#modal-image').remove();
	
			$.ajax({
				url: 'index.php?route=common/filemanager&user_token=' + getURLVar('user_token') + '&target=' + $(element).parent().find('input').attr('id') + '&thumb=' + $(element).attr('id') + '&productmanager=1',
				dataType: 'html',
				beforeSend: function() {
					$('.button-image-click i', parent).replaceWith('<i class="fa fa-circle-o-notch fa-spin"></i>');
					$('.button-image-click', parent).prop('disabled', true);
				},
				complete: function() {
					$('.button-image-click i', parent).replaceWith('<i class="fa fa-upload"></i>');
					$('.button-image-click', parent).prop('disabled', false);
				},
				success: function(html) {
					$('body').append('<div id="modal-image" class="modal">' + html + '</div>');
					var pr_id = $(element).parents('tr').attr('data-id');
					$('body').attr('data-current-product-id', pr_id);
					
					$('#modal-image').modal('show');
				}
			});
	
			$(element).popover('hide');
		});
	
		$('.button-clear-click', parent).on('click', function() {
			var r = confirm("Are you sure that you want to remove the main product image?");
			if (r == true) {
				doSave($(element).parents('tr').attr('data-id'), 'image', '');
			}
			$(element).popover('hide');
		});
	});
	
	// Images track on AJAX requests
	$(document).ajaxComplete(function(e, xhr, settings) {
	  	if (settings.url.toLowerCase().indexOf("common/filemanager") >= 0) {
			var pr_id = $('body').attr('data-current-product-id');
			$('a.thumbnail').on('click', function(e) {
				e.preventDefault();
				doSave(pr_id, 'image', $(this).parent().find('input').attr('value'));
				$('#modal-image').modal('hide');
			});
		}
	});
	
	// Quick Edit Button
	$('#form-product').on('click', '.module-edit', function(e) {
		e.preventDefault();
		var product_id = $(this).parents('.edit-tr').attr('data-id');
		$('#module-edit-modal #module-edit-iframe').attr('src', 'index.php?route=catalog/product/edit&product_id=' + product_id + '&user_token=' + user_token);
	});
	
	// Quick Edit iFrame
	$('#module-edit-modal #module-edit-iframe').on('load', function(event) {
		event.preventDefault();
		event.stopImmediatePropagation();
		var curr_page = $('.pagination ul li.active span').html();

		var iframe = $('#module-edit-modal #module-edit-iframe');
		var current_url = document.getElementById("module-edit-iframe").contentWindow.location.href;
	
		iframe.contents().find('form').on('submit', function(event) {
			iframe.addClass('loading');
		});

		if (current_url.indexOf('catalog/product') > -1 && current_url.indexOf('catalog/product/') < 0) {
			setTimeout(function(){
				$('#module-edit-modal').modal('hide');
			}, 150);
			$.ajax({
				url: getURL(curr_page),
				type: 'get',
				dataType: 'html',
				success: function(data) {		
					$("#productsWrapper").html(data);
					updateTable();
				}
		
			 });
		} else {
			iframe.contents().find('html,body').css({
				height: 'auto'
			});

			iframe.contents().find('#header,#content .page-header .breadcrumb,#column-left,#footer, a.btn-default').hide();
			iframe.contents().find('#content').css({
				transition: 'none',
				margin: '0',
				padding: '20px 0 0'
			});
	
			iframe.removeClass('loading');
	
			$('#module-edit-modal').modal('show');
		}
	});
    
    //View Orders Button
	$('#form-product').on('click', '.view-orders', function(e) {
		e.preventDefault();
		var product_id = $(this).parents('.edit-tr').attr('data-id');
		$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showOrdersPopup&product_id=' + product_id + '&user_token=' + user_token);
		$('#module-edit-custom-modal').modal('show');
	});

	// Quick Edit Specials
	$('#module-edit-custom-modal #module-edit-custom-iframe, #module-edit-custom-bulk-modal #module-edit-custom-bulk-iframe').load(function(event) {
		event.preventDefault();

		var curr_page = $('.pagination ul li.active span').html();

		var iframe = $('#module-edit-custom-modal #module-edit-custom-iframe');
		var current_url = document.getElementById("module-edit-custom-iframe").contentWindow.location.href;
		
		iframe.contents().find('form').on('submit', function(event) {
			iframe.addClass('loading');
		});

		var setting_page = "";
		var bulk = false;

		if(current_url.indexOf(productManagerPath + '/showSpecialsForm') > 0) {
			setting_page = "specials";
		} else if(current_url.indexOf(productManagerPath + '/showSpecialsBulkForm') > 0) {
			setting_page = "specials_bulk";
			bulk = true;
		} else if(current_url.indexOf(productManagerPath + '/showAttributesBulkForm') > 0) {
			setting_page = "attributes_bulk";
			bulk = true;
		} else if(current_url.indexOf(productManagerPath + '/showOptionsBulkForm') > 0) {
			setting_page = "options_bulk";
			bulk = true;
		} else if(current_url.indexOf(productManagerPath + '/showDiscountsBulkForm') > 0) {
			setting_page = "discounts_bulk";
			bulk = true;
		} else if(current_url.indexOf(productManagerPath + '/showAttributesForm') > 0) {
			setting_page = "attributes";
		} else if(current_url.indexOf(productManagerPath + '/showOptionsForm') > 0) {
			setting_page = "options";
        } else if(current_url.indexOf(productManagerPath + '/showDiscountsForm') > 0) {
			setting_page = "discounts";
		} else if(current_url.indexOf(productManagerPath + '/showOrdersPopup') > 0) {
			setting_page = "orders";
		}

		if 	(current_url.indexOf(productManagerPath) > -1 && 
				(	current_url.indexOf(productManagerPath + '/showSpecialsBulkForm') < 0 && 
					current_url.indexOf(productManagerPath + '/showAttributesBulkForm') < 0 && 
					current_url.indexOf(productManagerPath + '/showOptionsBulkForm') < 0 &&
					current_url.indexOf(productManagerPath + '/showDiscountsBulkForm') < 0 &&
					current_url.indexOf(productManagerPath + '/showAttributesForm') < 0 &&
					current_url.indexOf(productManagerPath + '/showSpecialsForm') < 0 && 
					current_url.indexOf(productManagerPath + '/showOptionsForm') < 0 &&
					current_url.indexOf(productManagerPath + '/showOrdersPopup') < 0 &&
					current_url.indexOf(productManagerPath + '/showDiscountsForm') < 0
				)
			) {
			setTimeout(function() {
				$('#module-edit-custom-modal').modal('hide');
			}, 150);
			$.ajax({
				url: getURL(curr_page),
				type: 'get',
				dataType: 'html',
				success: function(data) {
					$("#productsWrapper").html(data);
					updateTable();
				}
			 });
		} else {
			iframe.contents().find('html,body').css({
				height: 'auto'
			});
	
			iframe.contents().find('#content').css({
				transition: 'none',
				margin: '0',
				padding: '20px 0 0'
			});
			
			var product_id = getParameterFromIframe(iframe,'product_id');

			if(product_id == '0') {
				iframe.contents().find('form').attr('action',"index.php?route=" + productManagerPath + "/updateproductbulk&user_token=" + user_token);
			}					
			
			$('<input />').attr('type', 'hidden')
			          .attr('name', "pid")
			          .attr('value', product_id)
			          .appendTo(iframe.contents().find('form#form-product'));

			$('<input />').attr('type', 'hidden')
			          .attr('name', "pdata")
			          .attr('value', setting_page)
			          .appendTo(iframe.contents().find('form#form-product'));

			iframe.removeClass('loading');
			
			if(bulk) {
				iframe.contents().find('button.btn-submit').click(function(e){
				e.stopPropagation(); 
			    e.stopImmediatePropagation();
				    if (confirm("Are you sure that you want to bulk edit the selected products?")){
				    	if (iframe.attr('src').indexOf('Specials') > 1 || iframe.attr('src').indexOf('Discounts') > 1) {
				    		var form = iframe.contents().find('form');	
				    		if (validateForm(form)) { 
				    			form.find('btn-submit').click();
				    		} else {
				    			e.preventDefault();
				    		}
				    	}
				    } else {
				    	e.preventDefault();
				    }
			    });
			    iframe.contents().find('button.btn-delete').click(function(e) {
			    	e.stopPropagation();
			    	e.stopImmediatePropagation();
		    		if (confirm("Are you sure that you want to bulk remove all specials for the selected products?")) {
		    			if (iframe.attr('src').indexOf('Specials') > 1) {
			    			var form = iframe.contents().find('form');
			    			form.find('input[name$=\'price]\']').attr('value', '0');
			    			form.find('btn-submit').click();
		    			}
		    		}
			    })
			}		

			if(setting_page == 'attributes_bulk' || setting_page == 'options_bulk') {
				iframe.contents().find('#tab-delete button').click(function(e) {
					e.preventDefault();
			    	e.stopPropagation();
			    	e.stopImmediatePropagation();

			    	if(setting_page == 'attributes_bulk') {
			    		var confirm_message = "Are you sure that you want to delete the entire Attributes data the selected products?";
			    	} else if(setting_page == 'options_bulk') {
			    		var confirm_message = "Are you sure that you want to delete the entire Options data the selected products?";
			    	}

			    	if (confirm(confirm_message)){
			    		if(setting_page == 'attributes_bulk') {
			    			iframe.contents().find('#tab-attribute table tr[id^="attribute-row"]').remove();
			    		} else if(setting_page == 'options_bulk') {
			    			iframe.contents().find('a[href^="tab-option"]').parent().remove();
			    			iframe.contents().find('#tab-attribute table tr[id^="tab-option"]').remove();
			    		} 
				    	iframe.contents().find('form').submit();
				    }
				});
			}

			
			$('#module-edit-custom-modal').modal('show');
		}
	});
	
	$('#form-product').on('click', '.popup-specials', function(e) {
		e.preventDefault();
		var product_id = $(this).parents('.edit-tr').attr('data-id');
		$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showSpecialsForm&product_id=' + product_id + '&user_token=' + user_token);
		
	});

	$('#form-product').on('click', '.popup-attributes', function(e) {
		e.preventDefault();
		var product_id = $(this).parents('.edit-tr').attr('data-id');
		$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showAttributesForm&product_id=' + product_id + '&user_token=' + user_token);
		
	});

	$('#form-product').on('click', '.popup-discounts', function(e) {
		e.preventDefault();

		var product_id = $(this).parents('.edit-tr').attr('data-id');
		$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showDiscountsForm&product_id=' + product_id + '&user_token=' + user_token);
		
	});

	$('#form-product').on('click', '.popup-options', function(e) {
		e.preventDefault();
		var product_id = $(this).parents('.edit-tr').attr('data-id');
		$('#module-edit-custom-modal #module-edit-custom-iframe').attr('src', 'index.php?route=' + productManagerPath + '/showOptionsForm&product_id=' + product_id + '&user_token=' + user_token);
	});
}

function getParameterFromIframe(iframe,name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(iframe.attr('src'));
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

function validateForm(form) {
	var inputs = form.find('input');
	var error = false;	
	var priceInputs = form.find('input[placeholder=Price]');

	priceInputs.each(function(i) {
		if ($(this).parent().find('input[name$=\'operation]\']').val().length < 1) {
			$(this).parent().find('button:nth(0)').addClass('btn-danger');
			error = true;
		}

		if ($(this).parent().parent().find('input[name$=\'type]\']').val().length < 1) {
			$(this).parent().find('button').last().addClass('btn-danger');
			error = true;
		}
	});

	return !error;
}