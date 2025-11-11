<!-- JAVASCRIPT -->
<script src="{{ URL::asset('assets/libs/jquery/jquery.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap/bootstrap.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/metismenu/metismenu.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/simplebar/simplebar.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/node-waves/node-waves.min.js')}}"></script>
<script src="{{ URL::asset('assets/libs/toastr/toastr.min.js')}}"></script>
<script src="{{ URL::asset('assets/js/pages/toastr.init.js')}}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ asset('/assets/libs/select2/select2.min.js') }}"></script>
<script src="{{ URL::asset('/assets/libs/datatables/datatables.min.js') }}"></script>

<!-- App js -->
<script src="{{ URL::asset('assets/js/app.min.js')}}"></script>
<script src="{{ URL::asset('assets/myScripts.js')}}"></script>
@yield('script')
@if(session('_message'))
    <script>
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": 300,
            "hideDuration": 1000,
            "timeOut": 5000,
            "extendedTimeOut": 1000,
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
        Command: toastr['{{ session('_type') ?? 'info'}}']('{{session('_message')}}', '{{ session('_description') ?? ' ' }}')
    </script>
    @php(message_clear())
@endif
<script>
    function updateSidebarClass() {
        const sidebarElement = $("#site-body");
        const sidebarValue = getCookie('sidebar');

        if (sidebarValue === "min") {
            sidebarElement.addClass("vertical-collpsed");
        }
    }

    // Call the function on page load to apply the class initially
    $(document).ready(function () {
        updateSidebarClass();
    });
    function switchTheme(){
        $.ajax({
            url: '{!! route('switchTheme') !!}',
            type: "post", //send it through post method
            data: {
                user_id: "{!! auth()->id() !!}"
            },
            success:function (response) {
                if (response === 'light')
                {
                    $("#bootstrap-style").attr('href', '/assets/css/bootstrap.min.css');
                    $("#app-style").attr('href', '/assets/css/app.min.css');
                }else
                {
                    $("#bootstrap-style").attr('href', '/assets/css/bootstrap-dark.min.css');
                    $("#app-style").attr('href', '/assets/css/app-dark.min.css');
                }
                console.log(response);
            },
            error:function(err){
                show(err.message)
            }
        });
    }
    function show(message,timeout=10000) {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": true,
            "showDuration": "60000",
            "hideDuration": "1000",
            "timeOut": timeout,
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
        toastr.error(message)
    }
</script>
@foreach($errors->all() as $error)
    <script>
        show("{{ $error }}")
    </script>
@endforeach
@yield('script-bottom')
