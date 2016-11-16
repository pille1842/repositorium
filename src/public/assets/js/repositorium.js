$(function() {
	$("#toc").tocify({ "showAndHide": false, "extendPage": false });
});

$("#panel-toc-heading").click(function () {
	$("#panel-toc-body").slideToggle();
});

$("#panel-sidebar-heading").click(function () {
	$("#panel-sidebar-body").slideToggle();
	$("#panel-sidebar-body").toggleClass("hidden-xs");
});

$("#reposNewTitle").on('keyup', function () {
	if ($(this).val() == '') return;

	var title = { "title": $(this).val(), "document": $(this).data('document') };
	var ajax = $(this).data('ajax');
	$.get(ajax, title, function (data) {
		if (data.status == 'OK') {
			$("#reposNewFilename").val(data.filename);
		} else {
			console.log(ajax + " responded with error: " + data.error);
		}
	});
});

$("#reposNewFilename").on('keyup', function () {
	if ($(this).val() == '') return;

	var file = { "file": $(this).val() };
	var ajax = $(this).data('ajax');
	$.get(ajax, file, function (data) {
		if (data.status == 'OK') {
			if (data.exists) {
				$("#reposNewFilenameGroup").addClass('has-error');
				$("#reposNewFilenameHelpBlock").html("This file already exists.");
				$("#reposBtnNew").attr("disabled", "disabled");
			} else {
				$("#reposNewFilenameGroup").removeClass('has-error');
				$("#reposNewFilenameHelpBlock").html('');
				$("#reposBtnNew").removeAttr("disabled");
			}
		} else {
			console.log(ajax + " responded with error: " + data.error);
		}
	});
});

$("#reposBtnNew").click(function () {
	var title = { "title": $("#reposNewTitle").val() };
	var filename = $("#reposNewFilename").val();
	var ajax = $(this).data('ajax');
	ajax = ajax.replace('$', filename);

	$("#reposNewForm").attr('action', ajax);
	$("#reposNewForm").submit();
});

var compareA = '';
var compareB = '';

$(".repo-compare-chk-a").click(function () {
    compareA = $(this).data('commit');
    var baseUri = $("#repo-btn-compare").data("compare-uri");
    $("#repo-btn-compare").attr("href", baseUri + compareB + '..' + compareA);
    if (compareB != '') {
        $("#repo-btn-compare").removeAttr("disabled");
    }
});

$(".repo-compare-chk-b").click(function () {
    compareB = $(this).data('commit');
    var baseUri = $("#repo-btn-compare").data("compare-uri");
    $("#repo-btn-compare").attr("href", baseUri + compareB + '..' + compareA);
    if (compareA != '') {
        $("#repo-btn-compare").removeAttr("disabled");
    }
});
