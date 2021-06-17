<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bootstrap Example</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container-fluid mb-5">



    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#newTable">New Table</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#newCol">New Column</a>
        </li>
    </ul>
    <div class="tab-content">

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
        <hr>
        <br>



        {{------------------------------------------------------------------------------------}}
        <div class="tab-pane container active" id="newTable">
            <div class="row">
                <div class="col">
                    <form method="post" action="/const/add" >
                        @csrf
                        <label>model name</label>
                        <input type="text" name="modalName" placeholder="category" value="{{old('modalName')}}">
                        <br>

                        <label>Table name</label>
                        <input type="text" name="table" placeholder="categories" value="{{old('table')}}">
                        <br>

                        <label>Columns prefix</label>
                        <input type="text" name="colPrefix" placeholder="category" value="{{old('colPrefix')}}">
                        <br>

                        <label>with controller</label>
                        <input type="checkbox" name="withController" checked>
                        <br>

                        <label>columns</label>
                        <br>
                        <textarea name="cols" cols="60" rows="20" placeholder="id*
            signup_step=enum:validation,password">{{old('cols')}}</textarea>
                        <input type="submit">
                    </form>
                </div>
                <div class="col">
                    <div class="alert alert-info">
                        <p>1- id column will be primary automatically</p>
                        <p>2- * means scope in Model file (ex : name* will generate scopeName function)</p>
                        <p>3- general structure is : colName=type:extras</p>
                        <p>4- types are: text, integer (or int), tinyint, bigint, double, datetime, time, date, bool, enum , string(or str) ,password,phone,email,username</p>
                        <p>5- enum examples:  </p>
                        <p>type=enum:type1,type2,type3</p>
                    </div>
                </div>
            </div>

        </div>
        {{-- / --}}



        {{-----------------------------------------------------------------------------------}}
        <div class="tab-pane container" id="newCol">
            <form class="mt-4" method="POST" action="{{url('const/column')}}">
                @csrf
                <div class="form-group">
                    <label>table</label>
                    <select class="form-control" name="table">
                        @if(sizeof($tables) > 0)
                            @foreach($tables as $table)
                                <option value="{{$table}}">{{$table}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group">
                <textarea class="form-control" name="cols" cols="60" rows="20" placeholder="id*
                    signup_step=enum:validation,password">{{old('cols')}}</textarea>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-dark">
                </div>
            </form>
        </div>
        {{-- / --}}




    </div>
</div>
</body>
</html>





