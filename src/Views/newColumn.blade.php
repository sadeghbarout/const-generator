@extends('builder::layout')
@section('content')


    <div id="constApp">
        <form class="mt-4" method="POST" action="{{url('builder/const/column')}}">
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
                <input type="submit" class="btn bg-main">
            </div>
        </form>
    </div>





    @push('scripts')
        <script>
            Vue.createApp({
                data() {
                    return{
                        tables: {!!$tables!!},
                        selectedTable: '',
                        selectedTableCols: '',
                    }
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
            }).mount('#constApp')

        </script>
    @endpush



@endsection
