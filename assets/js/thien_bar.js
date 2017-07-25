/**
 * "Bar" page
 */
shj.com_check_time = null;
shj.check_com = function () {
	if (shj.com_check_time == null)
		shj.com_check_time = moment().add('milliseconds', shj.offset - (shj.com_check_delay * 1000));
	$.ajax({
		type: 'POST',
		url: shj.site_url+'competition/message_fight',
		data: {
			time: shj.com_check_time.format('YYYY-MM-DD HH:mm:ss'),
			shj_csrf_token: shj.csrf_token
		},
		success: function (data) {
			if (data != 'no' ) {
				console.log(data);
				$("#username_fight_yes_no").val(data);
				$("#message_fight").html(" Do you fight with this user " + data + "?");
				$("#fight_yes_no").modal('show');
				setTimeout(function(){ $("#fight_yes_no").modal('hide');  }, 9800);
			}
		}
	});
	shj.com_check_time = moment().add('milliseconds', shj.offset);
}
if(window.location.href.indexOf("competition")!=-1)
	window.setInterval(shj.check_com,(shj.com_check_delay*1000));


shj.check_ketqua = function () {
	console.log("run check ketqua");
	$.ajax({
		type: 'POST',
		url: shj.site_url + 'competition/check_ket_qua',
		data: {
			shj_csrf_token: shj.csrf_token
		},
		success: function (data) {
			console.log(data);
			if (data != 'no' ) {
               	$("#message").attr("class","alert alert-success");
           		$("#message").html("<span class='glyphicon glyphicon-ok-sign'></span>  User " +data+" finish");
           		$("#message_competiton").modal('show');
           		clearInterval(check_com);
           		// window.location = "competition/index";
			}
		}
	});
}
// shj.check_ketqua_drawn = function () {
// 	$.ajax({
// 		type: 'POST',
// 		url: shj.site_url + 'competition/check_ket_qua',
// 		data: {
// 			shj_csrf_token: shj.csrf_token
// 		},
// 		success: function (data) {
// 			if (data == 'no' ) {
//                	$("#message").attr("class","alert alert-success");
//            		$("#message").html("<span class='glyphicon glyphicon-ok-sign'></span>  Match results are drawn");
//            		$("#message_competiton").modal('show');
//            		clearInterval(check_com);
//        			$.ajax({
// 					type: 'POST',
// 					url: shj.site_url + 'competition/check_ket_qua',
// 					data: {
// 						result: 'drawn',
// 						shj_csrf_token: shj.csrf_token
// 					},
// 					success: function (data) {
// 			           		window.location = "competition";
// 						}
// 					}
// 				});
// 			}
// 		}
// 	});
// }

if(window.location.href.indexOf("?check=1")!=-1)
{
	check_com = setInterval(shj.check_ketqua,(shj.com_check_delay*2000));
	// check_drawn = setTimeout(shj.check_ketqua_drawn, shj.check_drawn_time * 60000);
}

function check_time_out() {
	com = setTimeout(check_fight_yes_no, 10000);
}

function check_fight_yes_no(){
	console.log("run yes no");
	$.ajax({
		type: 'POST',
		url: shj.site_url + 'competition/check_yes_no',
		data: {
			shj_csrf_token: shj.csrf_token
		},
		success: function (data) {
			var n = data.indexOf("problems/index");
			if (n != '-1' ) {
				window.location = data+"?check=1";
			}
			else
			{
				$.ajax({
					type: 'POST',
					url: shj.site_url+'competition/delete_com',
					data: {
						userid: data,
						shj_csrf_token: shj.csrf_token
					},
					success: function (data) {
	               		$("#message").html("<span class='glyphicon glyphicon-exclamation-sign'></span> User "+data+" can not fight with you");
	               		$("#message_competiton").modal('show');
					}
				});
			}

		}
	});
}