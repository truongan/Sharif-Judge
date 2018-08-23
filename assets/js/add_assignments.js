/*
	Wecode judge
	author: truongan
	date: 20160330
*/

$(document).ready(function(){
	$("form").submit(function(event){	
		$("#start_time").val($("#start_date").val() + " " + $("#start__time").val());
		$("#finish_time").val($("#finish_date").val() + " " + $("#finish__time").val());
	});
	var a = Sortable.create(problem_list, {
		handle : '.list_handle',
		ghostClass: 'list-group-item-secondary',
		chosenClass : 'list-group-item-primary',
		animation: 150,
		filter: '.list_remover',
		onFilter: function (evt) {
			var item = evt.item,
				ctrl = evt.target;
	
			if (Sortable.utils.is(ctrl, ".list_remover")) {  // Click on remove button
				item.parentNode.removeChild(item); // remove sortable item
			}
		}
	});

	$('.all_problems').select2();
});
