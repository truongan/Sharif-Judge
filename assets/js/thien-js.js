$(document).ready(function() {
	$('.databaitap').DataTable( {
    } );
    $('.dataxephang').DataTable( {
        "order": [[ 5, "desc" ]]
    } );
} );



// var editor; // use a global for the submit and return data rendering in the examples
 
// $(document).ready(function() {
//     editor = new $.fn.dataTable.Editor( {
//         ajax: "../php/staff.php",
//         table: "#datatable-problems",
//         fields: [ {
//                 label: "ID:",
//                 name: "id"
//             }, {
//                 label: "Tên",
//                 name: "name"
//             }, {
//                 label: "Độ khó",
//                 name: "difficulty"
//             }, {
//                 label: "Loai",
//                 name: ""
//             }, {
//                 label: "Điểm",
//                 name: "score"
//             }, {
//                 label: "Bạn đạt được",
//                 name: "",
//                 // type: 'datetime'
//             }
//         ]
//     } );
 
//     var table = $('#datatable-problems').DataTable( {
//         lengthChange: false,
//         ajax: "../php/staff.php",
//         columns: [
//             { data: null, render: function ( data, type, row ) {
//                 // Combine the first and last names into a single table field
//                 return data.first_name+' '+data.last_name;
//             } },
//             { data: "id" },
//             { data: "name" },
//             { data: "difficulty" },
//             { data: "score" }
//         ],
//         select: true
//     } );
 
//     // Display the buttons
//     new $.fn.dataTable.Buttons( table, [
//         { extend: "create", editor: editor },
//         { extend: "edit",   editor: editor },
//         { extend: "remove", editor: editor }
//     ] );
 
//     table.buttons().container()
//         .appendTo( $('.col-sm-6:eq(0)', table.table().container() ) );
// } );