	var dlgTitle = "";
	////////////////////////////////////////////////////////////////////////////
	// menu
	$("#menu").menu({
		position: {at: "left bottom"},
		icons: { submenu: 'ui-icon-blank' }
	});
	$("#ts-menu").show();
	$(window).resize(function() {
		var avgWidth = (($(document).width() - 40) / $(".tsMenuItem").length) - 10;
		$(".tsMenuItem").each(function () {
			$(this).css("min-width", avgWidth + 'px');
		});
	});

	$('#ts-menu li a').click(function (e) {
		e.preventDefault();
		var dlgId = $(this).attr("id");
		var tuneId = $(this).attr("tune_id");
		if ($("#dlg" + dlgId).hasClass('ui-dialog-content') === false) {
			$("#loading").show();
			$('#loading').position({my: "center", at: "center", of: window});
			var dlgDiv = $("<div>", {
				id: "dlg" + dlgId,
				title: ""
			});
			dlg = addDialog(dlgDiv, "left top", $(".ts-dialogs"), false);
			$(".ts-dialogs").prepend(dlg.parent());
			dlg.load('view.php?msq=' + tuneId + '&view=ts-dialog&dialog=' + dlgId, function() {
				dlg.dialog({
					title: dlgTitle
				}).dialog('open');
				$("#dlg" + dlgId).tooltip();
				fixDialogControls();
				fixDialogPositions();
				$("#loading").hide();
				findDialog(dlg);
			});
		}
		else {
			findDialog($("#dlg" + dlgId));
		}
	});

	if ($("#ts-menu").length) {
		var tsMenuTop = $("#ts-menu").offset().top;
		// float the menu when scrolling
		$(window).scroll(function fix_element() {
	    	$('#ts-menu').css(
				$(window).scrollTop() > tsMenuTop
		        	? { 'position': 'fixed', 'top': '2px' }
					: { 'position': 'relative', 'top': 'auto' }
		    );
		    return fix_element;
		}());
	}

	////////////////////////////////////////////////////////////////////////////
	// dialogs

	function findDialog(dlg) {
		var top = dlg.offset().top - 100;
		// the dialog is already opened somewhere, let's find it
		$([document.documentElement, document.body]).animate({
			scrollTop: top
		}, 2000);
	}

	function addDialog(div, at, prevDlg, isAutoOpen) {
		var dialog = div.dialog({
			modal: false,
			draggable: false,
			resizable: true,//false,
			autoOpen: isAutoOpen,
			dialogClass: 'tsDialogClass',
			prependTo: $(".ts-dialogs"),
			position: { my: "left top", at: at, of: prevDlg, collision: "none" },
			width: 'auto',
			resize: function(event, ui) {
				fixDialogPositions();
			},
			open: function( event, ui ) {
				fixDialogPositions();
			},
			close: function(event, ui) {
				$(this).empty().dialog('destroy');
				fixDialogPositions();
			},
		});
		return dialog;
	}

	function fixDialogControls() {
		$(".ts-controlgroup-vertical").each(function () {
			$(this).controlgroup({
				"direction": "vertical"
			});
		});
		$(".ts-controlgroup-horizontal").each(function () {
			$(this).controlgroup({
				"direction": "horizontal"
			});
		});

		$(".ts-slider").each(function () {
			$(this).slider({
				create: function(event, ui) {
					var el = event.target;
					var $slider = $(el);
					var numTicks = 20;
					var spacing =  100 / numTicks;
					$slider.find('.ui-slider-tick-mark').remove();
					for (var i = 0; i <= numTicks; i++) {
						$('<span class="ui-slider-tick-mark"></span>').css('left', (spacing * i) +  '%').appendTo($slider); 
					}
				},
				slide: function(event, ui) {
					$("#" + $(this).attr('input')).val(ui.value);
				},
				value: $(this).attr('value'),
			});
		});

		$(".ui-spinner-input").each(function(i) {
			if ($(this).attr("digits")) {
				var v = parseFloat($(this).val());
				$(this).val(v.toFixed($(this).attr("digits")));
			}
		});
	}

	function fixDialogPositions() {
		var maxWidth = 0;
		var totalHeight = 0;
		var prevDlg = $(".ts-dialogs");
		var at = "left top";
		$(".ts-dialogs>div, .tsDialog").each(function () {
			//.top(totalHeight);
			var dlgContent = $(this).find('.ui-dialog-content');
			if (!dlgContent.length)
				dlgContent = $(this).parent().find('.ui-dialog-content');
			var dlg = dlgContent.data('ui-dialog');
			/*$(this).resizable({
				handles: "e, s, se",
				resize: function(event, ui) {
					fixDialogPositions();
				},
				alsoResize: dlgContent
			});*/
			dlg.option("resizable", false);
			dlg.option("position", { my: "left top", at: at, of: prevDlg, collision: "none" });
			totalHeight += $(this).height() + 10;
			maxWidth = Math.max(maxWidth, $(this).width());
			prevDlg = $(this);
			at = "left bottom+10";
		});

		$(".ts-dialogs").width(maxWidth);
		$(".ts-dialogs").height(totalHeight + 50);
	}

	$(".ts-dialogs").show();
	var prevDlg = $(".ts-dialogs");
	var at = "left top";
	$(".ts-dialogs>div, .tsDialog").each(function () {
		var isAutoOpen = $(".ts-dialogs").attr("isAutoOpen");
		$(this).remove();
		var dialog = addDialog($(this), at, prevDlg, isAutoOpen);
		prevDlg = dialog.parent();
		at = "left bottom+10";
	});

	//alert(totalHeight);

	$(document).ready(function() {

		fixDialogControls();

		fixDialogPositions();

		//alert($('.ts-dialogs').height());
		//$('#footer').css('top', $('html').height() +'px');
		$("#loading").hide();
	});
	
	

	$(window).trigger('resize');

