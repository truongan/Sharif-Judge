/**
 * Wecode judge
 * @file submit_page.js
 * @author truongan
 */

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}

function get_template(){
    $.ajax({
        cache: true,
        type: 'POST',
        url: shj.site_url + 'submit/template',
        data: {
            wcj_csrf_name: shj.csrf_token,
            assignment: 1,
            problem: 1
        }
    });
}
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}


$(document).ready(function(){
    $("select#problems").change(function(){
        var v = $(this).val();
        $('select#languages').empty();
        //$('<option value="0" selected="selected">-- Select Language --</option>').appendTo('select#languages');
        if (v==0)
            return;
        for (var i=0;i<shj.p[v].length;i++)
            $('<option value="'+shj.p[v][i]+'">'+shj.p[v][i]+'</option>').appendTo('select#languages');
        $("#problem_link").attr('href', "/problems/{{ user.selected_assignment.id }}/" + $(this).val());

    });

    var editor = ace.edit("editor");

    var theme = getCookie("code_theme");
    if (theme == "") theme = "dawn";

    editor.setTheme("ace/theme/" + theme);
    $("#theme").val(theme);

    editor.session.setMode("ace/mode/c_cpp");
    $("form").submit(function(){
    	$("textarea").val(editor.getSession().getValue());
    });

    $("select[name=language]").change(function(){

    	var lang_to_mode = {"C++":"c_cpp"
    		, Java:"java"
    		, "Python 2":"python"
    		, "Python 3":"python"
    	};
    	editor.session.setMode("ace/mode/" + lang_to_mode[$(this).val()]);
    });

    $("#theme").change(function(){
    	t = $(this).val();
    	editor.setTheme("ace/theme/" + t);
    	setCookie('code_theme', t, 30);
    });
});
