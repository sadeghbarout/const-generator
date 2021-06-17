<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bootstrap Example</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.js"></script>

</head>
<body>
<div class="container-fluid mb-5" id="constApp">


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
                    <form method="post" action="/const/add">
                        @csrf
                        <label>model name</label>
                        <input type="text" name="modalName" placeholder="OrderBasket" value="{{old('modalName')}}">
                        <label style="color: #a4a4a4;font-size: 12px">(PascalCase/camelCase)(individual)</label>
                        <br>

                        <label>Table name</label>
                        <input type="text" name="table" placeholder="order_baskets" value="{{old('table')}}">
                        <label style="color: #a4a4a4;font-size: 12px">(snake_case)(sum)</label>
                        <br>

                        <label>Columns prefix</label>
                        <input type="text" name="colPrefix" placeholder="order_basket" value="{{old('colPrefix')}}">
                        <label style="color: #a4a4a4;font-size: 12px">(snake_case)(individual)</label>
                        <br>

                        <label>with controller</label>
                        <input type="checkbox" name="withController" checked>
                        <br>

                        <label>columns</label>
                        <br>
                        <textarea name="cols" cols="60" rows="20" placeholder="id*
name*=str
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
                        <p>5- enum examples: </p>
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
                    <select class="form-control" name="table" v-model="selectedTable">
                        <option v-for="table in tables" :value="table.table_name">@{{table.table_name}}</option>
                    </select>
                </div>
                <div class="form-group">
                <textarea class="form-control" name="cols" cols="60" rows="20" placeholder="id*
                    signup_step=enum:validation,password" v-html="selectedTableCols">
                </textarea>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-dark">
                </div>
            </form>
        </div>
        {{-- / --}}


    </div>
</div>


<script>
    new Vue({
        el: '#constApp',
        data: {
            tables: {!!$tables!!},
            selectedTable: '',
            selectedTableCols: '',
        },
        methods: {
            tableSelection(table){
                var tablesArray = Object.entries(this.tables)

                this.selectedTableCols = "";

                tablesArray.forEach((t) => {
                    if (t[0] == table) {

                        t[1].cols.forEach((col) => {
                            this.selectedTableCols += col.Field + '=' + col.Type + '\n'
                        })
                    }
                })
            },
        },
        watch: {
            selectedTable: function () {
                this.tableSelection(this.selectedTable);
            }
        },

    })
</script>


</body>
</html>





