/**
 * Sharif Judge
 * @file shj_functions.js
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */

// selectText is used for "Select All" when viewing a submitted code
jQuery.fn.selectText = function(){
	var doc = document
		, element = this[0]
		, range, selection
		;
	if (doc.body.createTextRange) {
		range = document.body.createTextRange();
		range.moveToElementText(element);
		range.select();
	} else if (window.getSelection) {
		selection = window.getSelection();
		range = document.createRange();
		range.selectNodeContents(element);
		selection.removeAllRanges();
		selection.addRange(range);
	}
};

shj.html_encode = function(value) {
	return $('<div/>').text(value).html();
}

shj.supports_local_storage = function() {
	try {
		return 'localStorage' in window && window['localStorage'] !== null;
	} catch(e){
		return false;
	}
}

shj.loading_start = function()
{
	$('#top_bar .shj-spinner').removeClass("d-none");
}

shj.loading_finish = function()
{
	$('#top_bar .shj-spinner').addClass("d-none");
}

shj.loading_error = function()
{
	$.notify('An error encountered while processing your request. Check your network connection.'
		, {position: 'bottom right', className: 'error', autoHideDelay: 3500});
}

shj.loading_failed = function(message)
{
	$.notify('Request failed. Server says: ' + message
		, {position: 'bottom right', className: 'error', autoHideDelay: 3500});
}

shj.sync_server_time = function () {
	$.ajax({
		type: 'POST',
		url: shj.site_url + 'server_time',
		data: {
			wcj_csrf_name: shj.csrf_token
		},
		success: function (response) {
			shj.offset = moment(response).diff(moment());
		}
	});
}

shj.update_clock = function(){
	if (Math.abs(moment().diff(shj.time))>3500){
		//console.log('moment: '+moment()+' time: '+time+' diff: '+Math.abs(moment().diff(time)));
		shj.sync_server_time();
	}
	shj.time = moment();
	var now = moment().add(shj.offset, 'milliseconds');
	$('.timer').html('Server time: '+now.format('DD/MM - HH:mm:ss'));


	var countdown = shj.finish_time.diff(now);

	if (isNaN(countdown)){
		countdown = 0;
	}
	if (countdown<=0 && countdown + shj.extra_time.asMilliseconds()>0){
		countdown = countdown + shj.extra_time.asMilliseconds();
		$("div#extra_time").css("display","block");
	}
	else
		$("div#extra_time").css("display","none");
	if (countdown<=0){
		countdown=0;
	}

	countdown = Math.floor(moment.duration(countdown).asSeconds());
	var seconds = countdown%60; countdown=(countdown-seconds)/60;
	var minutes = countdown%60; countdown=(countdown-minutes)/60;
	var hours = countdown%24; countdown=(countdown-hours)/24;
	var days = countdown;

	$("#time_days").html( days + "☀️" + hours + ":" + minutes + ":" + seconds);
}

// Notifications
shj.notif_check_time = null;
shj.check_notifs = function () {
	if (shj.notif_check_time == null)
		shj.notif_check_time = moment().add(shj.offset - (shj.notif_check_delay * 1000), 'milliseconds');
	$.ajax({
		type: 'POST',
		url: shj.site_url+'notifications/check',
		data: {
			time: shj.notif_check_time.format('YYYY-MM-DD HH:mm:ss'),
			wcj_csrf_name: shj.csrf_token
		},
		success: function (data) {
			if (data == "new_notification") {
				$.notify('New Notification', {position: 'bottom right', className: 'error', autoHideDelay: 300});
				alert("New Notification");
			}
		}
	});
	shj.notif_check_time = moment().add(shj.offset, 'milliseconds');
}




/**
 * Notifications
 */
$(document).ready(function () {
	$('.del_n').click(function () {
		var notif = $(this).parents('.notif');
		var id = $(notif).data('id');

		$(".confirm-notifycation-delete").off();
		$(".confirm-notifycation-delete").click(function(){
			$.ajax({
				type: 'POST',
				url: shj.site_url + 'notifications/delete',
				data: {
					id: id,
					wcj_csrf_name: shj.csrf_token
				},
				beforeSend: shj.loading_start,
				complete: shj.loading_finish,
				error: shj.loading_error,
				success: function (response) {
					if (response.done) {
						notif.animate({backgroundColor: '#FF7676'}, 1000, function () {
							notif.remove();
						});
						$.notify('Notification deleted'	, {position: 'bottom right', className: 'success', autoHideDelay: 5900});
					}
					else
						shj.loading_failed(response.message);
				}
			});
		});
		$("#notification_delete").modal("show");
	});

	if ( shj.check_for_notifications )
		window.setInterval(shj.check_notifs,(shj.notif_check_delay*1000));

});

/**
 * Sidebar
 */
$(document).ready(function () {
	// update the clock and countdown timer every 1 second
	shj.update_clock();
	window.setInterval(shj.update_clock, 1000);
});


/**
 * Top Bar
 */
$(document).ready(function () {
	$(".select_assignment").click(
		function () {
			var id = $(this).children('i').addBack('i').data('id');
			$.ajax({
				type: 'POST',
				url: shj.site_url + 'assignments/select',
				data: {
					assignment_select: id,
					wcj_csrf_name : shj.csrf_token
				},
				beforeSend: shj.loading_start,
				complete: shj.loading_finish,
				error: shj.loading_error,
				success: function (response) {
					if (response.done)
					{
						/*
							truongan: if we are at assignment list and chaging seleced assignments
							update countdown and select assigment list is not enough
							reload page is safer.
						*/
						//window.location.href = window.location.href;
						location.reload();
					}
					else
						shj.loading_failed(response.message);
				}
			});
		}
	);
});


/**
 * Set for all input elements
 */
$(document).ready(function(){
	$('input').attr('dir', 'auto');
	$('.custom-file-input').change(function(){
		if ($(this).prop("files").length == 0)
		{
			$(this).parent().find("label.custom-file-label").html("").removeClass("text-muted");
		}

		var span = $(this).parent().find("label.custom-file-label");
		var length = span.width() / parseFloat($("body").css("font-size"));
		console.log(span);
		//Ellipsis file name
		var name = $(this).prop("files")[0].name;
		if(length < 4) name = name.substr(0,3);
		else if (name.length > length) name = name.substr(0, length - 3) + "...";
		console.log(name);
		span.html(name).addClass("text-muted");
	});
});
