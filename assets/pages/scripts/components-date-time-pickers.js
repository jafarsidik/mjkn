var ComponentsDateTimePickers = function () {

    var handleDatePickers = function () {

        if (jQuery().datepicker) {
            $('.date-picker').datepicker({
                rtl: App.isRTL(),
                orientation: "left",
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
            $('.date-picker2').datepicker({
                rtl: App.isRTL(),
                orientation: "left",
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
            $('.date-picker3').datepicker({
                rtl: App.isRTL(),
                orientation: "left",
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
            $('.date-picker4').datepicker({
                rtl: App.isRTL(),
                orientation: "left",
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
            //$('body').removeClass("modal-open"); // fix bug when inline picker is used in modal
        }

        /* Workaround to restrict daterange past date select: http://stackoverflow.com/questions/11933173/how-to-restrict-the-selectable-date-ranges-in-bootstrap-datepicker */
    
        // Workaround to fix datepicker position on window scroll
        $( document ).scroll(function(){
            $('#form_modal2 .date-picker').datepicker('place');
            $('#form_modal2 .date-picker2').datepicker('place');
            $('#form_modal2 .date-picker3').datepicker('place');
            $('#form_modal2 .date-picker4').datepicker('place'); //#modal is the id of the modal
        });
    }

    var handleDateRangePickers = function () {
        if (!jQuery().daterangepicker) {
            return;
        }

        $('#defaultrange').daterangepicker({
                opens: (App.isRTL() ? 'left' : 'right'),
                format: 'YYYY-MM-DD',
                separator: ' to ',
                startDate: moment().subtract('days', 29),
                endDate: moment(),
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                    'Last 7 Days': [moment().subtract('days', 6), moment()],
                    'Last 30 Days': [moment().subtract('days', 29), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
                },
                minDate: '01/01/2019',
                maxDate: '12/31/2025',
            },
            function (start, end) {
                $('#defaultrange input').val(start.format('YYYY-MM-DD') + ' s/d ' + end.format('YYYY-MM-DD'));
            }
        );        

    }


    return {
        //main function to initiate the module
        init: function () {
            handleDateRangePickers();
            handleDatePickers();
        }
    };

}();

if (App.isAngularJsApp() === false) { 
    jQuery(document).ready(function() {    
        ComponentsDateTimePickers.init(); 
    });
}