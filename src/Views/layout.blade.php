<!DOCTYPE html>
<html lang="en">
<head>
    <title >Builder</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <style>
        :root{
            --main-color:#73ff00;
        }

        .bg-main{background-color: var(--main-color) !important;}
        .text-main{color: var(--main-color) !important;}
        .border-main{border:1px solid var(--main-color) !important;}

        input[type="checkbox"] {
            accent-color:var(--main-color) !important;
        }
    </style>
</head>
<body>
<div class="container-fluid mb-5" >

    @php
        $currentMenu = "index";

        if(strpos(request()->path(), "const/table") !== false)
        	$currentMenu = "index";
        elseif(strpos(request()->path(), "const/column") !== false)
            $currentMenu = "const-col";
        elseif(strpos(request()->path(), "vue") !== false)
            $currentMenu = "vue";
    @endphp


    <nav class="navbar navbar-expand-sm bg-dark navbar-dark fixed-top">
        <a class="navbar-brand text-main" href="#">Builder</a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav" >
                <li class="nav-item">
                    <a class="nav-link {{ $currentMenu == "index" ? 'text-light' : '' }}" href="/builder/const/table">New Table</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $currentMenu == "const-col" ? 'text-light' : '' }}" href="/builder/const/column">New Column</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $currentMenu == "vue" ? 'text-light' : '' }}" href="/builder/vue">Vue builder</a>
                </li>
            </ul>
        </div>
    </nav>


    <div class="tab-content">
        <br>
        @if (session('status'))
            <div style="color:green">
                {{ session('status') }}
            </div>
        @endif



        @if(isset($errors))
            @foreach($errors as $e)
                <p style="color:red">{{$e}}</p>
            @endforeach
        @endif
        <br>
        <br>
        <br>
        <br>

        @yield('content')

        <div id="resultDialog" style="position: fixed; bottom:10px;left: 10px;min-width:250px;min-height: 50px; padding:5px;text-align:center; border-radius: 10px ;display: none; display: flex;align-items: center; justify-content: center"></div>


    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.25.0/axios.min.js"></script>

<script>
    function checkResponse(response, callback=null){
        var dialog = $('#resultDialog')

        dialog.text('')
        dialog.show(700);

        if(response.result == 'success'){
            dialog.css({'background-color':'var(--main-color)'})
        } else{
            dialog.css({'background-color':'red','color':'white'})
        }

        dialog.text(response.message)

        setTimeout(()=>{
            dialog.hide(500);
        },4000)

        if(callback != null){
            callback.call(this)
        }
    }
</script>

@stack('scripts')



</body>
</html>





