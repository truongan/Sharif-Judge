/**
 * Sharif Judge
 * @file shj_functions.js
 * @author TruongAn PhamNguyen <hostmaster@truongan.name.vn>
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */


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

/**
 * setup save button and auto save for html editor and problem statemnt page
 * 
 */

shj.setup_save = function(save_button, post_url, ckeditor_instance){
	$(save_button).click(function(){
		$.ajax({
			type: 'POST',
			url: post_url,
			data: {
				wcj_csrf_name: shj.csrf_token,
				content : ckeditor_instance.getData()
			},
			success: function (response) {
				if (response == "success"){
					$.notify('Change sucessfully saved'
						, {position: 'bottom right', className: 'success', autoHideDelay: 3500});
					$(save_button).removeClass('btn-info').addClass('btn-secondary');
				}
			},
			error: function(response){
				$.notify('Error while saving'
					, {position: 'bottom right', className: 'error', autoHideDelay: 3500});
				//alert('There was problem saving change. You should download the content right now');
			}
		});
	});
	CKEDITOR.on('instanceReady', function(){
		ckeditor_instance.on('change', function(){
			$(save_button).removeClass('btn-secondary').addClass('btn-info');
		});
	});

	window.setInterval(function(){
		if($(save_button).hasClass('btn-info')){
			console.log("saved")
			$(save_button).click();
		} else {
			console.log("no need to save");
		}
	}, 180000);
}

/**
 * Sidebar
 */
$(document).ready(function () {
	// update the clock and countdown timer every 1 second
	shj.update_clock();
	window.setInterval(shj.update_clock, 1000);
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
