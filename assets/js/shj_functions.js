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
	$('#top_bar .shj-spinner').css('display', 'block');
}

shj.loading_finish = function()
{
	$('#top_bar .shj-spinner').css('display', 'none');
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
	var now = moment().add('milliseconds', shj.offset);
	$('.timer').html('Server Time: '+now.format('MMM DD - HH:mm:ss'));
	var countdown = shj.finish_time.diff(now);
	if (countdown<=0 && countdown + shj.extra_time.asMilliseconds()>=0){
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

shj.sidebar_open = function(time){
	if (time==0){
		$(".sidebar_text").css('display', 'inline-block');
		$("#sidebar_bottom p").css('display', 'block');
		$("#side_bar").css('width', '173px');
		$("#main_container").css('left', '173px');
	}
	else{
		$("#side_bar").animate({width: '173px'}, time, function(){
			$(".sidebar_text").css('display', 'inline-block');
			$("#sidebar_bottom p").css('display', 'block');
		});
		$("#main_container").animate({'left':'173px'}, time*1.7);
	}
	$("i#collapse").removeClass("fa-caret-square-o-right");
	$("i#collapse").addClass("fa-caret-square-o-left");
}

shj.sidebar_close = function(time){
	if (time==0){
		$(".sidebar_text").css('display', 'none');
		$("#sidebar_bottom p").css('display', 'none');
		$("#side_bar").css('width', '48px');
		$("#main_container").css('left', '48px');
	}
	else{
		$(".sidebar_text").css('display', 'none');
		$("#sidebar_bottom p").css('display', 'none');
		$("#side_bar").animate({width: '48px'}, time);
		$("#main_container").animate({'left': '48px'}, time*1.7);
	}
	$("i#collapse").removeClass("fa-caret-square-o-left");
	$("i#collapse").addClass("fa-caret-square-o-right");
}

shj.toggle_collapse = function(){
	if (shj.sidebar == "open"){
		shj.sidebar = "close";
		shj.sidebar_close(200);
		if (shj.supports_local_storage())
			localStorage.shj_sidebar = 'close';
		else
			$.cookie('shj_sidebar','close',{path:'/', expires: 365});
	}
	else if (shj.sidebar == "close"){
		shj.sidebar = "open";
		shj.sidebar_open(200);
		if (shj.supports_local_storage())
			localStorage.shj_sidebar = 'open';
		else
			$.cookie('shj_sidebar','open',{path:'/', expires: 365});
	}
}



// Notifications
shj.notif_check_time = null;
shj.check_notifs = function () {
	if (shj.notif_check_time == null)
		shj.notif_check_time = moment().add('milliseconds', shj.offset - (shj.notif_check_delay * 1000));
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
	shj.notif_check_time = moment().add('milliseconds', shj.offset);
}




/**
 * Notifications
 */
$(document).ready(function () {
	$('.ttl_n').click(function(){
		var id = $(this).parents('.notif').data('id');
		window.location = shj.site_url+'notifications#number'+id;
	});
	$('.edt_n').click(function () {
		var id = $(this).parents('.notif').data('id');
		window.location = shj.site_url+'notifications/edit/'+id;
	});
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
	if (shj.supports_local_storage())
		shj.sidebar = localStorage.shj_sidebar;
	else
		shj.sidebar = $.cookie('shj_sidebar');

	if (shj.sidebar != 'open' && shj.sidebar != 'close') {
		shj.sidebar = 'open';
		if (shj.supports_local_storage())
			localStorage.shj_sidebar = 'open';
		else
			$.cookie('shj_sidebar', 'open', {path: '/', expires: 365});
	}
	if (shj.sidebar == "open")
		shj.sidebar_open(0);
	else
		shj.sidebar_close(0);

	$("#shj_collapse").click(shj.toggle_collapse);

	// update the clock and countdown timer every 1 second
	shj.update_clock();
	window.setInterval(shj.update_clock, 1000);
});





/**
 * Top Bar
 */
$(document).ready(function () {
	$("#top_bar").hoverIntent({
		over: function () {
			$(this).children(".top_menu").show();
			$(this).addClass('shj_white');
		},
		out: function () {
			$(this).children(".top_menu").hide();
			$(this).removeClass('shj_white');
		},
		selector: '.top_object.shj_menu'
	});
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
						/*
						var checkboxes = $(".select_assignment").children('i').addBack('i');
						checkboxes.removeClass('fa-check-square-o color6').addClass('fa-square-o');
						checkboxes.filter("[data-id='" + id + "']").removeClass('fa-square-o').addClass('fa-check-square-o color6');
						$(".assignment_name").html($('.top_object [data-id="' + id + '"]').parents('.assignment_block').children('.assignment_item').html());
						shj.finish_time = moment(response.finish_time);
						shj.extra_time  = moment.duration(parseInt(response.extra_time, 10), 'seconds');
						shj.update_clock();
						*/
					}
					else
						shj.loading_failed(response.message);
				}
			});
		}
	);
});

/**
 * "Users" page
 */
$(document).ready(function(){
	$('.delete-btn').click(function(){
		var row = $(this).parents('tr');
		var user_id = row.data('id');
		var username = row.children('#un').html();

		var del_submssion = $(this).hasClass('delete_submissions');

		if (del_submssion) $(".modal-title").html("Are you sure you want to delete this user's SUBMISSIONS?");
		else $(".modal-title").html("Are you sure you want to delete this user?");

		$(".modal-body").html('User ID: '+user_id+'<br>Username: '+username+'<br><i class="splashy-warning_triangle"></i> All submissions of this user will be deleted.');
		$(".confirm-user-delete").off();
		$(".confirm-user-delete").click(function(){
			console.log(del_submssion);
			$.ajax({
				type: 'POST',
				url: (del_submssion ? shj.site_url+'users/delete_submissions' : shj.site_url+'users/delete'),
				data: {
					user_id: user_id,
					wcj_csrf_name: shj.csrf_token
				},
				beforeSend: shj.loading_start,
				complete: shj.loading_finish,
				error: shj.loading_error,
				success: function(response){
					if (response.done)
					{
						if (!del_submssion){
							row.animate({backgroundColor: '#FF7676'},1000, function(){row.remove();});
							$.notify('User '+username+' deleted.', {position: 'bottom right', className: 'success', autoHideDelay: 5000});
						} else {
							$.notify('All of User '+username+'\'s submissions deleted.', {position: 'bottom right', className: 'success', autoHideDelay: 5000});
						}
						$("#user_delete").modal("hide");
					}
					else
						shj.loading_failed(response.message);
				}
			});
		});
		$("#user_delete").modal("show");
	});
});



/**
 * Set dir="auto" for all input elements
 */
$(document).ready(function(){
	$('input').attr('dir', 'auto');
});
